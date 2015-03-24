<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Users extends Controller
{


    /**
     * @remotable
     * @sessionwriteclose
     *
     */
    public function getUser($id)
    {
        $results = DB::select('*')->from('users')->where('id','=',$id)->execute();

        return $results->as_array();


    }
    /**
     * @remotable
     * @sessionwriteclose
     *
     */
    public function getUsers()
    {
        $results = DB::select('*')->from('users')->execute();

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
