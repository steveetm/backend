<?php

defined('SYSPATH') OR die('No direct access allowed.');

return array
    (
    /* Match the case, please */
    'classes' => ['Welcome','Providers','Users','Stations','StationDepartures'],
    'exportAllPublic' => false,
    /* We don't want to export __construct, __destruct, etc. Never. Ever. Even with exportAllPublic. NOPE */
    'blacklist' => ['/^__.*/'],
    'url' => 'http://127.0.0.1/backend/ext-direct/router'
);
