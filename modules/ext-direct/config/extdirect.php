<?php

defined('SYSPATH') OR die('No direct access allowed.');

return array
    (
    /* Match the case, please */
    'classes' => ['Welcome'],
    'exportAllPublic' => false,
    /* We dont want to export __construct, __destroct, etc. Never. Ever. Even with exportAllPublic. NOPE */
    'blacklist' => ['/^__.*/']
);
