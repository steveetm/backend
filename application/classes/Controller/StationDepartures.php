<?php defined('SYSPATH') or die('No direct script access.');

class Controller_StationDepartures extends Controller
{


    /**
     * @remotable
     * @sessionwriteclose
     *
     * Gives back a full list departures, including the stationid, providerid, and departure informations.
     *
     * @return [array]
     */
    public function getDepartures()
    {

        $results = DB::select('*')->from('stationdepartures')->execute();
        return $results->as_array();

    }

    /**
     * @remotable
     * @sessionwriteclose
     *
     */
    public function getDeparturesAfterTime($params)
    {
        $dow=$params->dow;
        $time=$params->time;
        $results = DB::select('*')->from('stationdepartures')->where('time','>',$time)->execute();
        return $results->as_array();

    }


} // End Welcome
