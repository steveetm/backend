<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Providers extends Controller
{


    /**
     * @remotable
     * @sessionwriteclose
     *
     */
    public function getProviders()
    {
        $results = DB::select('*')->from('providers')->execute();
        return $results->as_array();

    }
    /**
     * @remotable
     * @sessionwriteclose
     *
     */
    public function getProvider($params)
    {
        $id=$params->id;
        if (!is_numeric($id)) {
            //throw new Exception('not valid');
            return false;
        }
        $results = DB::select('*')->from('providers')->where('idProvider','=',$params->id)->execute();

        return $results->as_array();


    }

    /**
     * @remotable
     * @sessionwriteclose
     *
     */
    public function updateUser($params)
    {
        DB::update('users')->set((array)json_decode(json_encode($params),true))->where('id','=',$params->id)->execute();

        return true;


    }
    /**
     * @remotable
     * @sessionwriteclose
     *
     */
    public function deleteUser($params)
    {
        DB::delete('users')->where('id','=',$params->id)->execute();

        return true;


    }
} // End Welcome
