<?php

defined('SYSPATH') or die('No direct script access.');


/*
 * TODO wrong place
 */

function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float) $usec + (float) $sec);
}

class Controller_Extdirect extends Controller {

    private $api;
    private $classCache;

    public function __construct(\Request $request, \Response $response) {
        $this->classCache = [];
        $this->api = [];
        $this->config = Kohana::$config->load('extdirect');

        parent::__construct($request, $response);
    }

    private function readClass($className) {
        
    }

    private function convertApi($apiArray) {
        $actions = [];
        foreach ($apiArray as $aname => &$a) {
            $methods = array();
            foreach ($a['methods'] as $mname => &$m) {
                if (isset($m['len'])) {
                    $md = array(
                        'name' => $mname,
                        'len' => $m['len']
                    );
                } else {
                    $md = array(
                        'name' => $mname,
                        'params' => $m['params']
                    );
                }
                if (isset($m['formHandler']) && $m['formHandler']) {
                    $md['formHandler'] = true;
                }
                $methods[] = $md;
            }
            $actions[$aname] = $methods;
        }
        return $actions;
    }

    private function getAnnotations(ReflectionMethod $method) {
        $doc = $method->getDocComment();
        preg_match_all('/\@(.*?)\n/', $doc, $annotations);
        return $annotations[1];
    }

    private function isBlacklisted(ReflectionMethod $method) {

        $blacklist = $this->config['blacklist'];

        $blacklisted = false;
        /*
         * TODO not so happy with that.
         */
        foreach ($blacklist as $listelem) {
            preg_match($listelem, $method->getName(), $matches);
            if (count($matches) > 0) {
                $blacklist = true;
                break;
            }
        }
        return $blacklisted;
    }

    private function isRemotableMethod(ReflectionMethod $method) {
        return in_array('remotable', $this->getAnnotations($method));
    }

    private function isFormHandler(ReflectionMethod $method) {
        return in_array('formHandler', $this->getAnnotations($method));
    }

    private function isStrict(ReflectionMethod $method) {
        return !in_array('noStrict', $this->getAnnotations($method));
    }

    private function isExportable(ReflectionMethod $method) {
        $exportAll = $this->config['exportAllPublic'];
        return ($method->isPublic() &&
                ($this->isRemotableMethod($method) || $exportAll) &&
                (!$this->isFormHandler($method)) &&
                !$this->isBlacklisted($method));
    }

    private function isExportableFormHandler(ReflectionMethod $method) {
        $exportAll = $this->config['exportAllPublic'];
        $remotable = $this->isRemotableMethod($method);
        $formHandler = $this->isFormHandler($method);
        $blacklist = $this->isBlacklisted($method);
        return ($method->isPublic() &&
                ($this->isRemotableMethod($method) || $exportAll) &&
                ($this->isFormHandler($method)) &&
                !$this->isBlacklisted($method));
    }

    private function getApi()
    {

        $cache = Cache::instance();

        if (!$api = $cache->get('directapi')) {
            return $this->generateApi();
        } else {
            return $api;
        }
    }
    private function generateApi() {
        $classes = $this->config['classes'];
        $cache = Cache::instance();

        $api = [];
        foreach ($classes as $class) {
            $classFile = Kohana::find_file('classes/Controller', $class);
            include_once($classFile);
            $reflClass = new ReflectionClass('Controller_' . $class);
            $methods = $reflClass->getMethods();
            $api[$reflClass->getName()] = array();
            $api[$reflClass->getName()]['methods'] = array();

            foreach ($methods as $method) {
                $rmethod = array();

                if ($this->isExportable($method)) {

                    $len = $method->getNumberOfParameters();
                    $name = $method->getName();
                    if (!$this->isStrict($method)) {
                        $rmethod['params'] = [];
                        $rmethod['strict'] = false;
                    } else {
                        $rmethod['len'] = $len;
                    }
                    $rmethod['formHandler'] = false;
                    $api[$reflClass->getName()]['methods'][$name] = $rmethod;
                }

                if ($this->isExportableFormHandler($method)) {
                    $len = $method->getNumberOfRequiredParameters();
                    $name = $method->getName();

                    $rmethod['formHandler'] = true;
                    $rmethod['len'] = 0;

                    $api[$reflClass->getName()]['methods'][$name] = $rmethod;
                }
            }
        }

        $this->api = $api;
        $cache->set('directapi',$this->api);
        return $this->api;
    }

    private function doRpc($request) {
        $time_start = microtime_float();
        $API = $this->getApi();
        try {
            if (!isset($API[$request->action])) {
                throw new Exception('Call to undefined action: ' . $request->action);
            }


            $action = $API[$request->action];


            $method = $action['methods'][$request->method];
            if (!$method) {
                throw new Exception("Call to undefined method: $method on action $action");
            }

            $r = array(
                'type' => 'rpc',
                'tid' => $request->tid,
                'action' => $request->action,
                'method' => $request->method
            );

            $controllerFile = Kohana::find_file('classes/Controller', str_replace('Controller_', '', $request->action));
            include_once ($controllerFile);

            if (isset($method['len'])) {
                $params = isset($request->data) && is_array($request->data) ? $request->data : array();
            } else {
                $params = array(
                    $request->data
                );
            }

            if (array_key_exists($request->action, $this->classCache)) {
                $o = $this->classCache[$request->action];
            } else {
                $this->classCache[$request->action] = new $request->action($this->request, $this->response);
                $o = $this->classCache[$request->action];
            }


            $ret = call_user_func_array(array(
                $o,
                $request->method
                    ), $params);
            if (is_object($ret) && get_class($ret) == 'ExtResponse') {
                $r['result'] = $ret->getResult();
                $r['statusCode'] = $ret->getCode();
            } else {
                $r['result'] = $ret;
                $r['response'] = $this->response->body();

                $r['statusCode'] = 200;
            }
            $time_end = microtime_float();
            $time = $time_end - $time_start;
            $r['runTime'] = $time;
        } catch (Exception $e) {
            if (ob_get_length() > 0) {
                $output = ob_get_contents();
                ob_end_clean();
                $r['extra'] = $output;
            }

            if ($e->getCode() >= 200) {
                // set_status_header($e->getCode(), $e->getMessage());
            } else {
                //   set_status_header(200, $e->getMessage());
            }

            $errorId = uniqid();
            $r['type'] = 'exception';
            $r['name'] = get_class($e);
            $r['data']['code'] = $e->getCode();
            $r['data']['exception'] = get_class($e);
            $r['data']['message'] = $e->getMessage();
            $r['data']['line'] = $e->getLine();
            $r['data']['file'] = $e->getFile();
            $r['data']['errorId'] = $errorId;
            $r['data']['backtrace'] = 'not_supported';
            $r['data']['queriesRan'] = 'not_supported'; //Mysql::$queriesRan;
        }


        return $r;
    }

    public function action_getConfig() {
        header('Access-Control-Allow-Headers: x-requested-with, content-type');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Origin: ' . '*');
        header('Content-Type: text/javascript');

        $jsCode = [

            "url" => $this->config['url'],
            "type" => "remoting",
            "timeout" => 3000,
            "actions" => $this->convertApi($this->getApi())
        ];


        $this->response->body('Ext.ns("Ext.app"); Ext.app.REMOTING_API =' . json_encode($jsCode));
    }

    public function action_router() {

        $isForm = false;
        $isUpload = false;

        header("Access-Control-Allow-Headers: X-Requested-With,X-Prototype-Version,Content-Type,Cache-Control,Pragma,Origin,Content-Length");
        header('Access-Control-Allow-Credentials: true');

        header('Access-Control-Allow-Origin: *');
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            return;
        }
        $api = $this->getApi();

        if (isset($_POST['extAction'])) { // form post
            $isForm = true;
            $isUpload = $_POST['extUpload'] == 'true';
            $data = new BogusAction();
            $data->action = $_POST['extAction'];
            $data->method = $_POST['extMethod'];
            $data->tid = isset($_POST['extTID']) ? $_POST['extTID'] : null; // not set for upload
            $data->data = array(
                $_POST,
                $_FILES
            );
        } else {
            $post = file_get_contents("php://input");

            if (isset($post)) {
                header('Content-Type: text/javascript');
                $data = json_decode($post);
            } else {
                die('Invalid request.');
            }
        }

        $response = null;

        if (is_array($data)) {
            $response = array();
            foreach ($data as $d) {
                $response[] = $this->doRpc($d);
            }
        } else {
            $response = $this->doRpc($data);
        }
        if ($isForm && $isUpload) {
            $response='<html><body><textarea>';
            $response.=json_encode($response);
            $response.='</textarea></body></html>';
        } else {
            $response=json_encode($response);
        }

        $this->response->body($response);
    }

}
