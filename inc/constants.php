<?php !defined('ABSPATH') && exit;

// obvs
!defined('PS') && define('PS', PATH_SEPARATOR);
!defined('DS') && define('DS', DIRECTORY_SEPARATOR);

!defined('E_DEPRECATED') && define('E_DEPRECATED', 8192);
!defined('E_USER_DEPRECATED') && define('E_USER_DEPRECATED', 16384);

define('HOOKR_PLUGIN_DIR', dirname(dirname(__FILE__)) . DS); // Plugin DIR
define('HOOKR_PLUGIN_FILE', HOOKR_PLUGIN_DIR . '/hookr.php'); // Plugin FILE
define('HOOKR_MAX_RECURSION', 10);
