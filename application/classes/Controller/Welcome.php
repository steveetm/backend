<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Welcome extends Controller {
        /**
         * @remotable
         */
	public function action_index()
	{
		$this->response->body('hello, world!');
                return [1,2,3,4];
	}
        
        /**
         * @remotable
         *
         */
        public function onlyremote() {
            $results=DB::select('*')->from('testtable')->execute();
            $response=[];

            foreach ($results as $result) {
                $response[]=['html'=>$result['img']];
            }
            return $response;
           
            
        }

} // End Welcome
