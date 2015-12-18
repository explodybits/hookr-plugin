<?php !defined('ABSPATH') && exit;

/**
 * @package Hookr
 * @subpackage Hookr_Plugin
 */
abstract class Hookr_Plugin extends Hookr_Singleton {

    /**
     * Returns the Hookr_Plugin derived instance.
     * 
     * @static
     * @access protected
     * @param string $class
     * @return object Instance of Hookr_Plugin
     */
    protected static function get_instance($class = __CLASS__)
    {        
        $instance = parent::get_instance($class);
        
        add_action('plugins_loaded', array($instance, 'load'), 0);
        add_action('init', array($instance, 'loaded'), 0);
                
        if (is_admin()) {
            $file = $instance->get_file();            
            register_activation_hook($file, array($instance, 'activate'));
            register_deactivation_hook($file, array($instance, 'deactivate'));
        }

        return $instance;
    }    
    
    /**
     * Executes after 'plugins_loaded' hook.
     * 
     * @return void
     */
    public function load()
    {}
    
    /**
     * Executes after 'init' hook.
     * 
     * @return void
     */    
    public function loaded()
    {}
    
    /**
     * Executes during plugin activation.
     * 
     * @return void
     */        
    public function activate()
    {}

    /**
     * Executes during plugin deactivation.
     * 
     * @return void
     */    
    public function deactivate()
    {}

    /**
     * Creates an <input> name attribute value in array format.
     * 
     * @param string $name
     * @return string
     */
    function get_field_name($name)
    {
        return sprintf('%s[%s]', $this->get_slug(), str_replace('-', '_', sanitize_title($name)));
    }
    
    /**
     * Echos the <input> name attribute value.
     * 
     * @see self::get_field_name()
     * @param string $name
     */
    function field_name($name)
    {
        echo $this->get_field_name($name);
    }

    /**
     * Creates an element id attribute value in WP's slug format.
     * 
     * @param string $id
     * @return string
     */
    
    function get_field_id($id)
    {
        return sprintf('%s-%s', $this->get_slug(), str_replace('_', '-', sanitize_title($id)));
    }
    
    /**
     * Echos the element id attribute value.
     * 
     * @see self::get_field_id()
     * @param string $id
     */    
    function field_id($id)
    {
        echo $this->get_field_id($id);
    }
    
    /**
     * Returns the contents of a rendered template.
     * 
     * @see self::render()
     * @param string $tpl
     * @param array $data
     * @return string
     */
    function get_render($tpl, $data = array())
    {
        extract($data);
        return require dirname($tpl) . DS . basename($tpl, '.php') . '.php';
    }
    
    /**
     * Echos the renderd template.
     * 
     * @param string $tpl
     * @param array $data
     */
    function render($tpl, $data = array())
    {
        echo $this->get_render($tpl, $data);
    }
    
    /**
     * Returns entry-point file for plugin.
     * @throws Exception
     */
    public function get_file()
    {
        throw new Exception(__METHOD__ . 'must be overridden.');
    }       

    /**
     * Returns the slug representing this class.
     * 
     * @staticvar string $slug
     * @return string
     */
    public function get_slug()
    {
        static $slug;
        
        if (null === $slug)
            return strtolower(str_replace('_', '-', get_called_class()));
        
        return $slug;
    }       
    
    /**
     * Plugin entry-point.
     * 
     * @throws Exception
     */
    public static function init()
    {
        throw new Exception(__METHOD__ . 'must be overridden.');
    }
};