<?php
Route::set('ext-getConfig', 'ext-direct/getConfig', array())
    	->defaults(array(
    		'controller' => 'Extdirect',
    		'action'     => 'getConfig'
    	));

Route::set('ext-router', 'ext-direct/router', array())
    	->defaults(array(
    		'controller' => 'Extdirect',
    		'action'     => 'router'
    	));