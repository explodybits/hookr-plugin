<?php !defined('ABSPATH') && exit;

/**#@+
 * Output Functions
 */
if (!function_exists('print_p')) {    
    /**
     * Prints human-readable information about a variable, but <pre> wrapped.
     * 
     * @param mixed The expression to be printed. 
     * @return boolean
     */    
    function print_p($expression)
    {
        ob_start();
        echo '<pre>';
        print_r($expression, false);
        echo '</pre>';
        return 1;
    };
}

if (!function_exists('print_e')) {
    /**
     * Prints human-readable information about a variable to the PHP error log.
     * 
     * @param mixed The expression to be printed.
     * @return boolean
     */   
    function print_e($expression)
    {
        error_log(print_r($expression, true));
        return 1;
    };
}
/**#@-*/

/**#@+
 * Helper Functions
 */
if (!function_exists('is_debug')) {
    /**
     * Returns whether or not debug is enabled.
     * 
     * @return boolean
     */    
    function is_debug()
    {
        return defined('WP_DEBUG') && true === WP_DEBUG;
    };
}

if (!function_exists('is_ajax')) {
    /**
     * Returns whether or not debug is enabled.
     * 
     * @return boolean
     */    
    function is_ajax($admin = true)
    {
        return ($admin && is_admin()) && (strtolower(@$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || defined('DOING_AJAX')) && !defined('DOING_AUTOSAVE');
    };
}
/**#@-*/

if (!function_exists('is_user_logging_out')) {
    /**
     * Returns whether or not the user is logging out.
     * 
     * @return bool
     */
    function is_user_logging_out()
    {
        return is_user_logged_in() && isset($_REQUEST['action']) && 'logout' === strtolower($_REQUEST['action']);
    };
};

if (!function_exists('is_user_logging_in')) {
    /**
     * Returns whether or not the user is logging in.
     * 
     * @return bool
     */    
    function is_user_logging_in()
    {
        return !is_user_logged_in() && @isset($_REQUEST['user_login']);
    };
};

if (!function_exists('is_admin_action')) {
    /**
     * Is an admin action being performed?
     * 
     * @return bool
     */    
    function is_admin_action()
    {
        return is_admin() && @isset($_REQUEST['action']);
    };
}

/**
 * Plugin entry-point helper
 * 
 * @return object
 */
function hookr()
{
    return Hookr::init();
};

/**
 * SPL autoload helper
 * 
 * @param string $class
 */
function hookr_spl_autoload($class)
{    
    $file = strtr($class, '\\_', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR) . '.php';

    if (!function_exists('stream_resolve_include_path') ||
        false !== stream_resolve_include_path($file))
        include $file;
};

/**
 * Takes arguments and converts to a readable/formatted string.
 * 
 * @param mixed $arg
 * @param int $indent
 * @return string
 */
function hookr_parse_arg($arg, $indent = 0)
{
    if ($indent >= HOOKR_MAX_RECURSION)
        return '[RECURSION]';
    
    if (is_object($arg))
        if ($arg instanceof stdClass) {
            $arg = str_replace('array', 'stdClass', hookr_parse_arg((array)$arg, $indent));
        } else {            
            $arg = sprintf("%s(\n%s", get_class($arg),  str_repeat('    ', $indent + 1))
                 . trim(preg_replace('/^array\(|[ ]*\),?$/', '', hookr_parse_arg(get_object_vars($arg), $indent)))
                 . sprintf("\n%s)", str_repeat('    ', $indent));
        }
    else if (is_array($arg)) {
        
        $args = array();

        foreach ($arg as $k => $v) {
                        
            if (preg_match('/salt|pw|password/i', $k))
                $v = '[REMOVED FOR SECURITY PURPOSES]';
            
            if ($arg === $v)
                continue;
            
            if (!is_int($k))
                $args[] = "'" . $k . '\' => '. hookr_parse_arg($v, $indent + 1);
            else
                $args[] = hookr_parse_arg($v, $indent + 1);
        }

        $arg = sprintf("array(\n%s", str_repeat('    ', $indent + 1))
             . implode(sprintf(",\n%s",  str_repeat('    ', $indent + 1)), $args)
             . sprintf("\n%s)", str_repeat('    ', $indent));
        
    } else {
        if (is_resource($arg))
            $arg = '(resource)';
        else if (is_null($arg))
            $arg = 'NULL';
        else if (is_bool($arg))
            $arg = true === $arg ? 'TRUE' : 'FALSE';
        else if (is_string($arg)) {
            if (false !== strpos($arg, '"'))
                $arg = "'" . str_replace("'", "\\'", $arg) . "'";
            else if (false !== strpos($arg, "'"))
                $arg = '"' . str_replace('"', '\"', $arg) . '"';
            else
                $arg = "'" . $arg . "'";

            $arg = htmlentities($arg);
        }
        else if (empty($arg))
            $arg = '(empty)';
    }

    $arg = preg_replace('/\([ \r\n]+\)/ms', '()', $arg);
        
    return $arg;
};

