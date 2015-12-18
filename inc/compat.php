<?php !defined('ABSPATH') && exit;

/**#@+
 * Polyfills
 */
if (!function_exists('array_flatten')) {
    
    /**
     * Flattens a multidimensional array.
     * 
     * @param array Input array
     * @return arrray
     */    
    function array_flatten($array)
    {
        return iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($array)), false);
    };
}

if (!function_exists('boolval')) {
    function boolval($var) {
        return (false === $var || true === $var || 0 === $var || 1 === $var) ? (bool)$var : false;
    }
}

if (!function_exists('stream_resolve_include_path')) {

    /**
     * Resolve filename against the include path.
     *
     * stream_resolve_include_path was introduced in PHP 5.3.2. This is kinda a PHP_Compat layer for those not using that version.
     * 
     * @see http://php.net/manual/en/function.stream-resolve-include-path.php#115229
     * 
     * @param Integer $length
     * @return String
     * @access public
     */
    function stream_resolve_include_path($filename)
    {
        $paths = PATH_SEPARATOR == ':' ?
            preg_split('#(?<!phar):#', get_include_path()) :
            explode(PATH_SEPARATOR, get_include_path());
        foreach ($paths as $prefix) {
            $ds = substr($prefix, -1) == DIRECTORY_SEPARATOR ? '' : DIRECTORY_SEPARATOR;
            $file = $prefix . $ds . $filename;

            if (file_exists($file)) {
                return $file;
            }
        }

        return false;
    };
}
/**#@-*/
