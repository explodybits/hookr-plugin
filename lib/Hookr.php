<?php !defined('ABSPATH') && exit;

/**
 * Hookr - The WordPress Hook/API Plugin.
 * 
 * @package Hookr
 */    
class Hookr extends Hookr_Plugin {

    const CONTEXT_HIDDEN = 0x0001;
    const CONTEXT_HEADER = 0x0002;
    const CONTEXT_MIDDLE = 0x0004;
    const CONTEXT_FOOTER = 0x0008;
    
    const KEY_REGEX = '__[hsc]\d{5}\s';
    const KEY_IGNORE = 0x0001;
    const KEY_TEXT = 0x0002;
    
    protected $hooks = array();
    protected $context;
    protected $visible = false;
    protected $abort = false;
    protected $error = null;
    protected $settings = null;
    protected $scripts = array();
    protected $comments = array();
    protected $actions = array('detail' , 'enable');
    protected $buffer = null;
        
    protected function __construct()
    {
        parent::__construct();        
        $this->context = self::CONTEXT_HIDDEN;
        $this->clear_cache();
        
        define('HOOKR_CONTEXT_HIDDEN', self::CONTEXT_HIDDEN);
        define('HOOKR_CONTEXT_HEADER', self::CONTEXT_HEADER);
        define('HOOKR_CONTEXT_MIDDLE', self::CONTEXT_MIDDLE);
        define('HOOKR_CONTEXT_FOOTER', self::CONTEXT_FOOTER);
    }
    
    /**#@+
     * Register Callbacks
     */

    /**
     * Registers the JS assets.
     * 
     * @return void
     */
    function register_scripts()
    {
        if (is_admin()) {
            wp_enqueue_script('bs-loophole', plugins_url('assets/js/bs-loophole.min.js', HOOKR_PLUGIN_FILE), array('jquery'), null, true);
        }
        
        if ($this->is_enabled()) {
            wp_enqueue_style('qtip', plugins_url('assets/css/jquery.qtip.min.css', HOOKR_PLUGIN_FILE));        
            wp_enqueue_script('hookr', plugins_url('assets/js/hookr.min.js', HOOKR_PLUGIN_FILE), array('jquery'), null, true);                    
            
        } else {
            wp_enqueue_script('hookr', plugins_url('assets/js/hookr.min.js', HOOKR_PLUGIN_FILE), array('jquery'), null, true);                    
        }
        
        wp_localize_script('hookr', 'hookr', array(
            'ajax_url' => admin_url('admin-ajax.php?hookr=true')
        ));                                    
    }

    /**
     * Registers the CSS assets.
     * 
     * @return void
     */    
    function register_styles()
    {
        if (is_admin()) {
            wp_enqueue_style('screen-admin', plugins_url('assets/css/screen-admin.min.css', HOOKR_PLUGIN_FILE));          
        } else {
            wp_enqueue_style('screen', plugins_url('assets/css/screen.min.css', HOOKR_PLUGIN_FILE));            
        }        
    }
   
    /**
     * Registers the hooks.
     * 
     * @return void
     */    
    protected function register_hooks()
    {
        if (empty($_POST) && $this->is_enabled()) {

            add_filter('all', array($this, 'track_hook'), 0);
                                    
            add_action('get_header', array($this, 'set_context_header'), 0);
            add_action('admin_head', array($this, 'set_context_header'), 0);
            add_action('login_head', array($this, 'set_context_header'), 0);
            
            add_action('get_footer', array($this, 'set_context_footer'), 0);
            add_action('admin_head', array($this, 'set_context_footer'), 0);
            add_action('login_footer', array($this, 'set_context_footer'), 0);
            
            add_filter('body_class', array($this, 'set_context_middle'), PHP_INT_MAX);
            add_filter('admin_body_class', array($this, 'set_context_middle'), PHP_INT_MAX);
            add_filter('login_body_class', array($this, 'set_context_middle'), PHP_INT_MAX);
            
            add_action('loop_start', array($this, 'set_context_middle'), PHP_INT_MAX);
            add_action('parse_query', array($this, 'set_context_limbo'), PHP_INT_MAX);            
        }
        
        if (is_admin()) {
            add_action('admin_menu', array($this, 'register_page'));
            add_action('admin_init', array($this, 'register_settings'));
            add_action('admin_enqueue_scripts', array($this, 'register_scripts'));
            add_filter('plugin_action_links_' . plugin_basename($this->get_file()), array($this, 'register_actions'));
        } else {
            add_action('wp_enqueue_scripts', array($this, 'register_scripts'));                            
        }

        add_action('admin_bar_menu', array($this, 'register_menu'), 99);        
        add_action('wp_ajax_ajax', array($this, 'ajax'));
        add_action('wp_ajax_nopriv_ajax', array($this, 'ajax'));
    }
    
    /**
     * Unregisters the hooks.
     * 
     * @return void
     */    
    protected function unregister_hooks()
    {
        remove_filter('all', array($this, 'track_hook'), 0);
        
        remove_action('get_header', array($this, 'set_context_header'), 0);
        remove_action('admin_head', array($this, 'set_context_header'), 0);
        remove_action('login_head', array($this, 'set_context_header'), 0);
        
        remove_action('get_footer', array($this, 'set_context_footer'), 0);
        remove_action('admin_head', array($this, 'set_context_footer'), 0);
        remove_action('login_footer', array($this, 'set_context_footer'), 0);
        
        remove_filter('body_class', array($this, 'set_context_middle'), PHP_INT_MAX);
        remove_filter('admin_body_class', array($this, 'set_context_middle'), PHP_INT_MAX);
        remove_filter('login_body_class', array($this, 'set_context_middle'), PHP_INT_MAX);
        
        remove_action('loop_start', array($this, 'set_context_middle'), PHP_INT_MAX);
        remove_action('parse_query', array($this, 'set_context_limbo'), PHP_INT_MAX);
        
        remove_action('wp_ajax_ajax', array($this, 'ajax'));
        remove_action('wp_ajax_nopriv_ajax', array($this, 'ajax'));
        
        remove_filter('plugin_action_links_' . plugin_basename($this->get_file()), array($this, 'register_actions'));        
    }
    
    /**
     * Registers the admin options page.
     * 
     * @return void
     */    
    function register_page()
    {
	add_submenu_page(
            'tools.php',
            __CLASS__,
            __CLASS__,
            'manage_options',
            $this->get_slug(),
            array($this, 'settings_page')
	);        
    }
    
    /**
     * Registers the admin bar menu.
     * 
     * @param object $wp_admin_bar
     * @return void
     */    
    function register_menu($wp_admin_bar)
    {
        $uri = plugins_url('assets/images/hookr-white.svg', HOOKR_PLUGIN_FILE);
        
        $wp_admin_bar->add_node(array(
            'id' => $this->get_slug(),
            'title' => sprintf('<img src="%s" />', $uri),
            'href' => admin_url('tools.php?page=' . $this->get_slug()),
            'meta' => array(
                'title' => 'Click for additional settings'
            )
        ));
        
        if ($this->is_enabled() && !$this->is_tools_page()) {        
            $wp_admin_bar->add_node(array(
                'parent' => $this->get_slug(),
                'id' => sprintf('%s-search', $this->get_slug()),
                'title' => '<input type="search" placeholder="Filter the things &hellip;" />'
            ));
        }
        
        if (!is_admin()) {
            $wp_admin_bar->add_node(array(
                'parent' => $this->get_slug(),
                'id' => sprintf('%s-enabled', $this->get_slug()),
                'title' => $this->get_render('fields/enable', array('settings' => (object)$this->get_settings()))
            ));
        }
    }
    
    /**
     * Registers the plugin settings.
     * 
     * @return void
     */    
    function register_settings()
    {
        register_setting($this->get_slug(), $this->get_slug(), array($this, 'settings_validate'));
    }
    
    /**
     * Registers the plugin actions.
     * 
     * @return void
     */    
    function register_actions($actions)
    {
        return array_merge(
            array(
              'settings' => '<a href="' . get_bloginfo('wpurl') . sprintf('/wp-admin/tools.php?page=%s">Settings</a>', $this->get_slug())
            ),
            $actions
	);
    }
    
    /**#@-*/
    
    /**#@+
     * Setting Functions
     */
    
    /**
     * Renders the plugin options page.
     * 
     * @return void.
     */    
    function settings_page()
    {
        $data = array('title' => 'The WordPress Hook/API Plugin');
        $data = array_merge($data, array('settings' => (object)$this->get_settings()));
        $this->render('settings-page', $data);        
    }
    
    /**
     * Renders the fields for use on the plugin options page.
     * 
     * @see self::settings_page()
     * @return string The pre-rendered markup.
     */
    function settings_fields()
    {
        return settings_fields($this->get_slug());
    }
    
    /**
     * Validates the settings posted from the plugin options page.
     * 
     * @param array $args The plugin settings.
     * @return array
     */
    function settings_validate($args)
    {
        foreach ($this->get_settings_empty() as $key => $val) {

            if (!@isset($args[$key])) {
                $args[$key] = $val;
            }
            
            switch ($key) {
                
                case 'public_ignore':
                case 'admin_ignore':
                case 'public_watch':
                case 'admin_watch':
                    $args[$key] = trim($args[$key]);
                    break;

                case 'hookr':
                case 'enabled':
                    $args[$key] = intval($args[$key]);                    
                    break;
                
                default:                    
                    break;                
            }            
        }
        
        return $args;
    }
    
    /**
     * Returns the "raw" plugin options (unmodified).
     * 
     * @return array Plugin settings
     */
    protected function get_settings_raw()
    {
        return get_option($this->get_slug());        
    }
    
    /**
     * Returns the filters/runtime plugin settings object.
     * 
     * @staticvar array $settings
     * @return object The plugin settings object.
     */
    protected function get_settings()
    {
        static $settings;
        
        if (null === $settings) {

            $settings = (array)$this->get_settings_raw();                  
            $settings += (array)$this->get_settings_empty();
                        
            if (empty($settings))
                return $this->get_settings_default();

            if (is_admin()) {
                $keys = preg_grep('/^(?!public)/', array_keys($settings));
            } else {
                $keys = preg_grep('/^(?!admin)/', array_keys($settings));            
            }
                        
            $settings = (object)$settings;
            $settings->context = array();

            foreach ($keys as $key) {

                $val = @isset($settings->{$key}) ? $settings->{$key} : '';
                $key = preg_replace('/^(admin|public)_/i', '', $key);
                
                switch ($key) {
                    case 'watch':
                    case 'ignore':
                        $val = array_filter(preg_split('/[\r\n]+/', trim($val)));
                        break;
                    default:
                        break;                
                }
                
                $settings->{$key} = $val;

                if (false !== strpos($key, 'ctx') && $val > 0)
                   $settings->context[] = $val;
            }            
        }
        
        return $settings;
    }
    
    /**
     * Returns the plugin's default settings.
     * 
     * @staticvar stdClass $default
     * @return object
     */
    protected function get_settings_default()
    {
        static $default;

        if (null === $default) {

            $default = new stdClass();
            $default->enabled = true;
            $default->hookr = false;

            $default->mode = new stdClass();
            $default->mode->trace = true;        
            $default->mode->actions = true;
            $default->mode->filters = true;
            $default->mode->off_canvas = false;
            $default->mode->ignore = implode("\n", array(
                'gettext',
                'ngettext_with_context',
                'sanitize_*',
                'esc_*',
                'clean_url',
                'no_texturize_*',
                'set_url_scheme',
                'attribute_escape',
                'after_plugin_row*',
                'switch_blog'
            ));
            $default->mode->watch = '';
            $default->mode->ctx_hidden = 0;
            $default->mode->ctx_header = self::CONTEXT_HEADER;
            $default->mode->ctx_middle = self::CONTEXT_MIDDLE;
            $default->mode->ctx_footer = self::CONTEXT_FOOTER;

            foreach (array('public', 'admin') as $mode) {
                foreach ($default->mode as $key => $val) {
                    $key = sprintf('%s_%s', $mode, $key);
                    $default->{$key} = $val;                    
                    if ('admin_trace' === $key)
                        $default->{$key} = false;
                }
            }

            unset($default->mode);
        }          

        return clone $default;        
    }
    
    /**
     * Returns an "empty" settings object.
     * 
     * @staticvar stdClass $empty
     * @return object The settigs object with empty values.
     */
    protected function get_settings_empty()
    {
        static $empty;

        if (null === $empty) {

            $empty = $this->get_settings_default();

            foreach ($empty as $key => $val) {
                switch (gettype($val)) {
                    case 'int':
                    case 'float':                        
                    case 'bool':
                    case 'boolean':
                        $empty->$key = 0;
                        break;   
                    case 'array':
                        $empty->$key = array();
                        break;
                    case 'string':
                        $empty->$key = '';
                        break;
                    default:
                        $empty->$key = null;                        
                        break;
                }                               
            } 
        }
        
        return $empty;        
    }
    
    /**
     * Return a specific setting from the plugin settings object.
     * 
     * @param string $key
     * @param string $val
     * @return mixed The value of the specified key
     */
    protected function get_setting($key, $val = '')
    {
        return @isset($this->settings->{$key}) ? $this->settings->{$key} : $val;
    }
    /**#@-*/
    
    /**#@+
     * Context Callbacks
     */
    
    /**
     * Sets the runtime context to "header"
     * 
     * @return void
     */
    function set_context_header()
    {
        $this->context = self::CONTEXT_HEADER;
        $this->visible = false;
    }

    /**
     * Sets the runtime context to "middle"
     * 
     * @return void
     */    
    function set_context_middle($mixed = null)
    {
        $this->context = self::CONTEXT_MIDDLE;
        $this->visible = true;        
        return $mixed;
    }
    
    /**
     * Sets the runtime context to "footer"
     * 
     * @return void
     */    
    function set_context_footer()
    {
        $this->context = self::CONTEXT_FOOTER;
        $this->visible = false;        
    }    
    
    /**
     * Sets the runtime context to "middle" (eventually)
     * 
     * @return void
     */    
    function set_context_limbo()
    {        
        static $queries;

        $this->visible = false;
        
        if (null === $queries)
            $queries = -1;
            
        if (1 === ++$queries) {
            $this->set_context_middle();
            remove_action('parse_query', array($this, __FUNCTION__), PHP_INT_MAX);            
        }        
    }
    /**#@-*/

    /**#@+
     * Hook Methods 
     */
    
    /**
     * Echos (if applicable) the hook's tag & stores instance.
     * 
     * This is really where the magic happens... just sayin'
     * 
     * @return void
     */
    function track_hook()
    {
        if (is_user_logging_in() || $this->is_tools_page()) {
            $this->unregister_hooks();
            ob_get_clean();
            return;
        }
        
        $args = func_get_args();
        $tag = strtolower(array_shift($args));

        if (false === $this->is_tag_watch($tag) ||
            true === $this->is_tag_ignore($tag) ||
            !in_array($this->context, $this->settings->context)) {
            return;
        }
        
        if (null === ($hook = $this->create_hook($tag, $args)))
            return;
        
        if ($this->settings->trace) {
            
            foreach (debug_backtrace() as $caller) {   

                $caller = (object)$caller;

                if (!@isset($caller->function))
                    continue;
                
                if (0 === strpos($caller->function, 'do_action') ||
                    0 === strpos($caller->function, 'apply_filters')) {
                    $hook->caller = $caller;
                    $hook->caller->file = DS . str_replace(ABSPATH, '', $hook->caller->file);
                    unset($hook->caller->args);
                    break;
                }                
            }
        }
                        
        if ('shutdown' === $hook->tag) {
            $this->visible = false;
            return;
        }
        
        if ($hook->visible)
            echo $hook->key;        
    }
    
    /**
     * Sets the runtime visibility for the hooks.
     * 
     * First, this is an expensive function; it attempts to correctly render
     * hooks based on whether or not they are within the <body> element.
     * 
     * @param string $tag
     */
    protected function track_visible($tag)
    {
        switch ($this->context) {

            case self::CONTEXT_HIDDEN:            
            case self::CONTEXT_HEADER:
                
                if ($this->visible)
                    break;
                
                if (false !== strrpos(ob_get_contents(), '<body'))
                    $this->visible = true;
                
                break;
            
            case self::CONTEXT_FOOTER:
                
                if (false !== strrpos(ob_get_contents(), '</body>'))
                    $this->visible = false;
                        
                break;
            
            default:
                break;
        };
    }    

    /**
     * Creates a generic hook instance.
     * 
     * @global array $wp_filter
     * @global array $wp_actions
     * @staticvar stdClass $hook
     * @staticvar int $index
     * @param string $tag
     * @param array $args
     * @return \stdClass
     */
    protected function create_hook($tag, $args = array())
    {
        global $wp_filter, $wp_actions;
        static $hook, $index;
        
        if (null === $hook) {
            
            $index = -1;
            
            $hook = new stdClass();
            $hook->index = -1;
            $hook->tag = '';
            $hook->type = '';
            $hook->args = array();
            $hook->value = '';
            $hook->context = null;
            $hook->visible = false;
            $hook->key = '';
            $hook->node = null;            
        }
                
        $type =  @isset($wp_actions[$tag]) ? 'action' : 'filter';

        if ('action' === $type && !$this->settings->actions ||
            'filter' === $type && !$this->settings->filters)
            return null;
        
        $this->track_visible($tag);
                
        $clone = clone $hook;        
        $clone->index = ++$index;
        $clone->tag = $tag;
        $clone->type = $type;
        $clone->args = count($args);    
        $clone->value = hookr_parse_arg(array_shift($args));
        $clone->value_hilite = $clone->value;
        
        // TODO(cs) This is duplicated... maybe move to a filter.
        if (preg_match('/salt|pw|password/i', $clone->tag))
            $clone->value = '[REMOVED FOR SECURITY PURPOSES]';
        
        $clone->context = $this->context;
        $clone->visible = $this->visible;                
                
        $clone->key = $this->create_key($clone->index);
        $clone->node = null;      
        $this->hooks[trim($clone->key)] = $clone;

        return $clone;
    }    
    
    /**
     * Returns a hook by key.
     * 
     * @param string $key
     * @return object
     */
    protected function get_hook($key)
    {
        $hook = @isset($this->hooks[$key])
              ? $this->hooks[$key]
              : null;
        
        return $hook;
    }
    /**#@-*/
    
    /**#@+
     * Runtime Flag Methods
     */
    
    /**
     * Should the plugin abort?
     * 
     * @return boolean
     */
    protected function is_abort()
    {
        return $this->abort ||
               is_user_logging_out() ||
               $this->is_update_page() ||
               // defined('DOING_AJAX') && DOING_AJAX ||
               defined('IFRAME_REQUEST') && IFRAME_REQUEST ||
               defined('WP_INSTALLING') && WP_INSTALLING ||
               defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ||
               is_ajax() && !@isset($_REQUEST[$this->get_slug()]);                
    }
    
    /**
     * Is the plugin enabled?
     * 
     * @return boolean
     */
    protected function is_enabled()
    {
        return null !== $this->settings && 1 == $this->settings->enabled;
    }
    
    /**
     * Is the current request for the plugin options page?
     * 
     * @return boolean
     */
    protected function is_tools_page()
    {
        return is_admin() && (false !== stripos($_SERVER['REQUEST_URI'], 'tools') && $this->get_slug() === @$_GET['page']);
    }
    
    /**
     * Should the tag be ignored?
     * 
     * @staticvar array $tags
     * @param string $tag
     * @return boolean
     */
    protected function is_tag_ignore($tag)
    {
        static $tags;
        
        if (null === $tags)
            $tags = $this->_filter_wildcard($this->settings->ignore);
            
        $flag = in_array($tag, $this->settings->ignore);
        
        if (!$flag) {
            foreach ($tags as $ignore) {
                if (false !== stripos($tag, $ignore)) {
                    $flag = true;
                    break;
                }
            }
        }
        
        return $flag;
    }
    
    /**
     * Is the tag being watched?
     * 
     * @staticvar array $tags
     * @param string $tag
     * @return boolean
     */
    protected function is_tag_watch($tag)
    {
        static $tags;
                
        if (null === $tags)
            $tags = $this->_filter_wildcard($this->settings->watch);

        if (empty($this->settings->watch))            
            return null;
        
        $flag = in_array($tag, $this->settings->watch);

        if (!$flag) {
            foreach ($tags as $watch) {
                if (false !== stripos($tag, $watch)) {
                    $flag = true;
                    break;
                }
            }
        }

        return $flag;
    }

    /**
     * Utility method to filter wildcard tags.
     * 
     * @param array $array
     * @return array
     */
    protected function _filter_wildcard($array)
    {
        $tags = preg_grep('/[^\*]+\*/', $array);
        
        foreach ($tags as $k => $v) {
            $tags[$k] = trim($tags[$k], '*');
        }
        
        return array_values($tags);
    }
    
    /**
     * Is the current request for the update page?
     * 
     * @return boolean
     */
    protected function is_update_page()
    {
        return is_admin() && (false !== stripos($_SERVER['REQUEST_URI'], 'update') && @isset($_REQUEST['action']));
    }      
    /**#-*/
    
    /**#@+
     * Node Methods
     */
    
    /**
     * Filters nodes for actions/filters.
     * 
     * @param DOMNode $node
     */
    protected function filter_nodes($node)
    {        
        switch ($node->nodeType) {
            
            case 1:          
                $this->filter_node($node);
                break;
            
            case 8:                
                break;
            
            case 3:
                
                $value = $node->nodeValue;
                
                if (!$this->has_key($value))
                    break;

                $element = null;
                $child = null;
                $parent = $node->parentNode;

                if ($this->ignore_node($parent->tagName)) {
                    $element = $parent = $parent->parentNode;
                } else {                    
                    $element = $this->get_target_node($node);                    
                }
                
                $this->node_add_class($element, $this->get_slug());                
                $child = $this->get_child_node($element);
                    
                $action = null;                    
                $ids = array();
                $keys = $this->parse_keys($value);
                
                foreach ($keys as $key) {

                    $value = str_replace($key . ' ', '', $value);
                    
                    if (null === ($hook = $this->get_hook($key)))
                        continue;

                    $hook = $this->hooks[$key];
                
                    if ('action' === $hook->type) {

                        if (null === $parent)
                            continue;                                

                        $hook->node = $this->create_node($parent);
                        $parent->insertBefore($hook->node, $node);
                        $hook->node->appendChild($this->create_node('strong', $hook->tag));
                        $this->set_node_attrs($hook->node, array(
                            'id' => $this->get_field_id($hook->index)
                           ,'class' => $this->get_field_id($hook->type)
                           ,'title' => $hook->tag
                           ,'data-hookr' => $this->encode_keys($hook->index)                          
                        ));
                        
                    } else {

                        $this->node_add_class($element, $this->get_field_id($hook->index));
                        $class = $this->get_field_id('filter');
                        
                        if (!$this->node_has_class($element, $class)) {                                      
                            $this->node_add_class($element, $class);
                            $marker = $this->create_node($element);                                    
                            $element->insertBefore($marker, $child);
                            $marker->setAttribute('class', $this->get_field_id('marker'));
                        }
                    }

                    unset($hook->node);

                    $ids[] = $hook->index;                    
                }                
                
                if ($element->hasAttribute('data-hookr')) {
                    $ids = array_merge(
                        $this->decode_keys($element->getAttribute('data-hookr'))
                       ,$ids
                    );
                }

                sort($ids);
                $element->setAttribute('data-hookr', $this->encode_keys($ids));
                $node->nodeValue = $value;
                
                break;
            
            default:
                break;
        }
        
        if ($node->childNodes && $node->childNodes->length)
            foreach ($node->childNodes as $node)
                $this->filter_nodes($node);        
    }
 
    /**
     * Filters element nodes for actions/filters.
     * 
     * @param DOMNode $node
     * @return void
     */
    protected function filter_node($node)
    {
        if (1 !== $node->nodeType)
            return $node;
                
        $keys = array();

        foreach ($node->attributes as $name => $attr) {
            if ($this->has_key($name))
                $keys[] = $name;
            else if ($this->has_key($attr->value)) {
                $value = '';
                foreach ($this->parse_keys($attr->value) as $key) {
                    $keys[] = $key;
                    $value = preg_replace($key . ' ', '', $attr->value);
                }
                $node->setAttribute($name, $value);
            }
        }
        
        $keys = array_filter($keys);
        
        if (empty($keys) || 'script' === strtolower($node->tagName))
            return;
                                
        $this->node_add_class($node, $this->get_slug());
        $child = $this->get_child_node($node);
                
        foreach ($keys as $key) {            
            if ($node->hasAttribute($key))
                $node->removeAttribute($key);
        }        
        
        $keys = $this->unique_keys($keys);
        $ids = array();
        
        foreach ($keys as $key) {

            if ($node->hasAttribute($key))
                $node->removeAttribute($key);

            if (!@isset($this->hooks[$key]))
                continue;
            
            $hook = $this->hooks[$key];
            $ids[] = $hook->index;
            
            if ('filter' === $hook->type) {
                
                $this->node_add_class($node, $this->get_field_id($hook->index));                
                $class = $this->get_field_id('filter');
                
                if (!$this->node_has_class($node, $class)) {  
                    $this->node_add_class($node, $class);
                    $marker = $this->create_node('span');
                    $node->insertBefore($marker, $child);
                    $marker->setAttribute('class', $this->get_field_id('marker'));
                }
                
                continue;                
            }
            
            $hook->node = $this->create_node($node);
            $node->insertBefore($hook->node, $child);
            $hook->node->appendChild($this->create_node('strong', $hook->tag));
            $this->set_node_attrs($hook->node, array(
                'id' => $this->get_field_id($hook->index)
               ,'class' => $this->get_field_id($hook->type)
               ,'data-hookr' => $this->encode_keys($hook->index)
            ));
                                    
            unset($hook->node);
        }
        
        sort($ids);
        $node->setAttribute('data-hookr', $this->encode_keys($ids));                
    }
    
    /**
     * Returns a list of keys in a specific node.
     * 
     * @param DOMNode $node
     * @param int $type
     * @return array List of keys.
     */
    protected function filter_keys($node, $type = self::KEY_IGNORE)
    {
        $keys = array();
                
        if (!($node instanceof DOMNode))
            return $keys;
        
        switch ($type) {

            case self::KEY_TEXT:                
                switch ($node->nodeType) {
                    case 3:
                        $keys = $this->parse_keys($node->nodeValue);
                        break;

                    default:
                        break;
                };
                break;

            case self::KEY_IGNORE:
            default:
                switch ($node->nodeType) {

                    case 8:
                        $keys = $this->parse_keys($node->nodeValue);
                        break;

                    case 1:
                        if ('style' === strtolower($node->tagName) ||
                            'script' === strtolower($node->tagName))
                            $keys = $this->parse_keys($node->nodeValue);
                        break;

                    default:
                        break;
                };
            break;
        }

        $keys = array_filter($keys);

        if ($node->childNodes && $node->childNodes->length > 0)
            foreach ($node->childNodes as $node)
                $keys = array_merge($keys, $this->filter_keys($node, $type));

        return $keys;
    }

    /**
     * Creates a new DOMElement.
     * 
     * @param DOMNode|string $node
     * @param string $value
     * @return \DOMElement
     */
    protected function create_node($node, $value = '')
    {
        $node = $node instanceof DOMNode
              ? $this->get_node_tag($node)
              : $node;

        return new DOMElement($node, $value);        
    }    
    
    /**
     * Ignore the specific tag?
     * 
     * @staticvar array $tags
     * @param string $tag
     * @return boolean
     */
    protected function ignore_node($tag)
    {
        static $tags;
        
        if (null === $tags) {
            $tags = apply_filters('hookr_ignore_nodes', array('script', 'style', 'textarea', 'input'));
            $tags = array_map('strtolower', array_map('trim', $tags));
        }
        
        return in_array(strtolower($tag), $tags);
    }
    
    /**
     * For a parent node, return the child's tag name.
     * 
     * @param DOMNode $node
     * @return string
     */
    protected function get_node_tag($node)
    {
        $tag = '';

        if (! ($node instanceof DOMNode))
            return $tag;

        switch (strtolower($node->tagName)) {

            case 'ul':
            case 'ol':                
                $tag = 'li';
                break;

            case 'dl':
                $tag = 'dd';
                break;

            case 'body':
            case 'address':
            case 'article':
            case 'aside':
            case 'blockquote':
            case 'dd':
            case 'div':
            case 'fieldset':
            case 'figcaption':
            case 'figure':
            case 'footer':
            case 'form':
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
            case 'header':
            case 'hgroup':
            case 'main':
            case 'nav':
            case 'noscript':
            case 'output':
            case 'section':
            case 'table':
            case 'video':
                $tag = 'div';
                break;

            case 'pre':
                $tag = 'code';
                break;

            case 'p':
            default:
                $tag = 'span';
                break;            
        }

        return $tag;
    }    
    
    /**
     * Sets the attributes on a given DOMNode.
     * 
     * @param DOMNode $node
     * @param array $attrs
     * @return void
     */
    protected function set_node_attrs($node, $attrs = array())
    {
        if (empty($attrs) || !method_exists($node, 'setAttribute'))
            return;

        foreach ($attrs as $name => $value)
            $node->setAttribute($name, $value);
    }
    
    /**
     * Find the target node to be annotated.
     * 
     * @param DOMNode $node
     * @return DOMNode
     */
    protected function get_target_node($node)
    {
        $parent = $node->parentNode;

        while (null !== ($element = $node->nextSibling)) {            
            if (1 !== $element->nodeType) {
                $node = $element;
                continue;
            }
            break;
        }
        
        if (null === $element)
            $element = $parent;

        if ($this->ignore_node($element->tagName))
            $element = $parent;

        return $element;
    }
    
    /**
     * Returns the child node to be annotated.
     * 
     * @param DOMNode $node
     * @param DOMXPath $xpath
     * @return DOMNode
     * @throws UnexpectedValueException
     */
    protected function get_child_node($node, $xpath = null)
    {
        if (null !== $xpath) {
            
            // TODO(cs) Future...
            throw UnexpectedValueException($xpath);

        } else {

            $child = $node->firstChild;

            if (null === $child) {
                $node->appendChild(new DOMText());
                $child = $node->firstChild;
            } else if (1 === $child->nodeType) {
                if ($this->ignore_node($child->tagName)) {
                    $node->insertBefore(new DOMText(), $child);
                    $child = $node->firstChild;
                }
            }
        }

        return $child;
    }
    
    /**
     * Does the given node have CSS class?
     * 
     * @param DOMNode $node
     * @param string $class
     * @return boolean
     */
    protected function node_has_class($node, $class)
    {
        if (!method_exists($node, 'getAttribute'))
            return false;

        return false !== strpos($node->getAttribute('class'), $class);        
    }

    /**
     * Add a CSS class to the given node.
     * 
     * @param DOMNode $node
     * @param string $class
     * @return void
     */
    protected function node_add_class($node, $class)
    {
        if ($this->node_has_class($node, $class) ||
            !method_exists($node, 'getAttribute'))
            return;

        $class = (array)$class;        
        $class = array_merge(
            preg_split('/\s+/', $node->getAttribute('class'))
           ,$class
        );        
        $class = array_map('trim', array_filter($class));

        $node->setAttribute('class', implode(' ', $class));
    }    
    /**#@-*/
    
    /**#@+
     * Key Methods
     */
    
    /**
     * Decodes string of keys into array.
     * 
     * @param string $keys
     * @return array
     */
    protected function decode_keys($keys)
    {
        return array_map('absint', explode(',', trim($keys, '[]')));
    }
    
    /**
     * Encodes an array of keys into a string.
     * 
     * @param array $keys
     * @return string
     */
    protected function encode_keys($keys)
    {
        return '[' . implode(',', (array)$keys) . ']';
    }
    
    /**
     * Converts index key to '__h00001' (raw) format.
     * 
     * @param int $index
     * @param char $char
     * @return string
     */
    protected function create_key($index, $char = 'h')
    {
        return sprintf("__%s%'.05d ", $char, $index);
    }    
    
    /**
     * Strip keys
     * 
     * @param string $buffer
     * @return string
     */
    protected function strip_keys($buffer)
    {
        return preg_replace('~' . self::KEY_REGEX . '~', '', $buffer);
    }
    
    /**
     * Converts raw string key into it's index.
     * 
     * @param string $key
     * @return int
     */
    protected function parse_key($key)
    {
        return absint(preg_replace('/[^0-9]/', '', $key));        
    }    
    
    /**
     * Returns the list of raw keys from a chunk of html/text.
     * 
     * @param string $chunk
     * @return array
     */
    protected function parse_keys($chunk)
    {
        $keys = array();
        preg_match_all('~' . self::KEY_REGEX . '~', $chunk, $keys);
        return array_map('trim', array_filter(array_shift($keys)));
    }
    
    /**
     * Returns a unique raw key list.
     * 
     * @param array $keys
     * @return array
     */
    protected function unique_keys($keys)
    {
        for ($i = 0, $c = count($keys); $i < $c; ++$i) {
            $key = $keys[$i];
            if (!@isset($this->hooks[$key]))
                continue;
            $key = $this->hooks[$key]->tag;
            $keys[$key] = $keys[$i];
            unset($keys[$i]);
        }

        return array_values($keys);
    }    
    
    /**
     * Returns whether or not a chunk of html/text contains a raw key.
     * 
     * @param string $chunk
     * @return boolean
     */
    protected function has_key($chunk)
    {
        return false !== strpos($chunk, '__h') || false !== strpos($chunk, '__s');
    }
    /**#@-*/
    
    /**#@+
     * Buffer Methods
     */
    
    /**
     * Pre-filters the nodes containing raw keys.
     * 
     * @param string $buffer
     * @return string
     */
    protected function pre_filter_nodes($buffer)
    {
        if (preg_match_all('/<(?![!\/\\\])([^ >]+)[^>].*?>/ms', $buffer, $matches)) {
            list($nodes, $tags) = $matches;
            for ($i = 0, $c = count($nodes); $i < $c; ++$i) {
                $node = $nodes[$i];
                if (false === strpos($node, '__h'))
                    continue;
                $tag = $tags[$i];
                $alpha = $omega = $node;                
                $keys = $this->parse_keys($node);
                if (!empty($keys)) {
                    foreach ($keys as $key)
                        $omega = str_replace($key, '', $omega);
                    $keys = array_map(array($this, 'parse_key'), $keys);
                    $omega = str_replace('<' . $tag, '<' . $tag . ' data-hookr="' . $this->encode_keys($keys) . '"', $omega);
                    $buffer = str_replace($alpha, $omega, $buffer); 
                }
            }
        }
        
        return $buffer;
    }
    
    /**
     * Pre-filters raw keys from begining of document to <html>.
     * 
     * This is needed to prevent the DOMDocument parser from vomitting. Since
     * Hookr doens't know what context it render's it's keys, pre-filtering is
     * required to prevent corrupted output.
     * 
     * @param string $buffer
     * @return string
     */
    protected function pre_filter_html($buffer)
    {
        $chunk = substr($buffer, 0, stripos($buffer, '<html') + 5);
        return $this->pre_filter_offcanvas($buffer, $chunk);
    }
    
    /**
     * Post-filters <html> with various runtime attributes.
     * 
     * @param DOMDocument $dom
     * @return void
     */    
    protected function post_filter_html($dom)
    {
        $element = $dom->getElementsByTagName('html')->item(0);
        $this->set_node_attrs($element, array(
            'data-hookr-url' => plugins_url('', HOOKR_PLUGIN_FILE) . DIRECTORY_SEPARATOR
           ,'data-hookr-key' => $this->get_request_key()
        ));
    }
    
    /**
     * Pre-filters raw keys between <head> to <body>.
     * 
     * @param string $buffer
     * @return string
     */    
    protected function pre_filter_body($buffer)
    {
        $start = stripos($buffer, '</head>') + 7;
        $end = stripos($buffer, '<body');
        $chunk = substr($buffer, $start, $end - $start);
        return $this->pre_filter_offcanvas($buffer, $chunk);
    }
    
    /**
     * Post-filters <body> with various runtime attributes/element creation.
     * 
     * @param DOMDocument $dom
     * @return void
     */
    protected function post_filter_body($dom)
    {
        $element = $dom->getElementsByTagName('body')->item(0);
        $class = preg_split('/\s+/', preg_replace('/hookr(-.+)?/', '', $element->getAttribute('class')));
        $element->removeAttribute('data-hookr');
        $element->setAttribute('class', implode(' ', array_filter($class)));
        $class = array();

        if (is_admin()) {
            $xpath = new DOMXpath($dom);
            $elements = $xpath->query("//*[@id='wpcontent']");
            if ($elements->length)
                $element = $elements->item(0);
        }

        if ($this->settings->off_canvas) {
            $node = $this->create_node($element);
            $element->insertBefore($node, $element->firstChild);
            $this->set_node_attrs($node, array(
                'id' => $this->get_field_id('offcanvas')
               ,'class' => $this->get_slug()
            ));
            $child = $this->get_child_node($node);        
        }
        
        $ids = array();

        foreach($this->hooks as $hook) {

            $ids[] = $hook->index;

            if (true === $hook->visible ||
                'filter' === $hook->type ||
                !$this->settings->off_canvas)
                continue;
            
            $hook->node = $this->create_node($node);
            $node->insertBefore($hook->node, $child);
            $hook->node->appendChild($this->create_node('strong', $hook->tag));
            $this->set_node_attrs($hook->node, array(
                'id' => $this->get_field_id($hook->index)
               ,'class' => $this->get_field_id($hook->type)
               ,'title' => $hook->tag
               ,'data-hookr' => $this->encode_keys($hook->index)
            ));
        }
        
        #$marker = $this->create_node($node);
        #$node->appendChild($marker);
        #$marker->setAttribute('class', $this->get_field_id('marker'));
        
        if ($this->settings->off_canvas)
            $node->setAttribute('data-hookr', $this->encode_keys($ids));  
        
        $element->setAttribute('class', implode(' ', array_filter($class)));            
    }

    /**
     * Pre-filters comments within buffer.
     * 
     * Converts comments to raw key, for later replacement. This is needed 
     * because of various conditional comments that will corrupt the output of 
     * $dom->saveHTML().
     * 
     * @param string $buffer
     * @return string
     */
    protected function pre_filter_comments($buffer)
    {
        if (is_admin()) {
            if (preg_match_all('/(<!--(.*?)-->)/ims', $buffer, $comments)) {    
                list($comments, $values) = $comments;
                for ($i = 0, $c = count($comments); $i < $c; ++$i) {
                    $comment = $comments[$i];
                    $value = trim($values[$i]);
                    if (empty($value))
                        continue;
                    $key = '<!--' . $this->create_key($i, 'c') . '-->';
                    $buffer = str_replace($comment, $key, $buffer);
                    $this->comments[$key] = $comment;
                }
            }
        }
        
        return $buffer;
    }    
    
    /**
     * Post-filters comments within buffer.
     * 
     * Converts raw keys to their original values.
     * 
     * @param string $buffer
     * @return string
     */
    protected function post_filter_comments($buffer)
    {
        foreach ($this->comments as $key => $comment) {
            $buffer = str_replace($key, $comment, $buffer);
        }
        
        return $buffer;
    }
    
    /**
     * Pre-filters scripts within buffer.
     * 
     * For whatever reason, the DOMDocument object has absolutely terrible 
     * performance when it comes to large inline-scripts. To prevent Hookr from 
     * lagging too much on the admin side, the "ginormous" scripts have to be 
     * removed.
     * 
     * @param string $buffer
     * @return string
     */    
    protected function pre_filter_scripts($buffer)
    {
        if (is_admin()) {
            if (preg_match_all('/(<script[^>]*?>(.*?)<\/script>)/ims', $buffer, $scripts)) {    
                list($scripts, $values) = $scripts;
                for ($i = 0, $c = count($scripts); $i < $c; ++$i) {
                    $script = $scripts[$i];
                    $value = trim($values[$i]);
                    if (empty($value))
                        continue;
                    $key = '<!--' . $this->create_key($i, 's') . '-->';
                    $buffer = str_replace($script, $key, $buffer);
                    $this->scripts[$key] = $script;
                }
            }
        }
        
        return $buffer;
    }
    
    /**
     * Post-filters scripts within buffer.
     * 
     * Converts raw keys to their original values.
     * 
     * @param string $buffer
     * @return string
     */    
    protected function post_filter_scripts($buffer)
    {
        foreach ($this->scripts as $key => $script) {
            $script = $this->strip_keys($script);
            $buffer = str_replace($key, $script, $buffer);
        }
        
        return $buffer;
    }
    
    /**
     * Pre-filters raw keys from given chunk of html/text.
     * 
     * @see pre_filter_html
     * @see pre_filter_body
     * @param string $buffer
     * @param string $chunk
     * @return string
     */    
    protected function pre_filter_offcanvas($buffer, $chunk)
    {
        $keys = $this->parse_keys($chunk);
        
        if (empty($keys))
            return $buffer;
        
        foreach ($keys as $key) {
            $this->hooks[$key]->visible = false;
        }
        
        $keys = implode(' ', $keys) . ' ';
        $buffer = str_replace($keys, '', $buffer);
        
        if (0 !== stripos($buffer, '<body'))
            $buffer = str_ireplace('<body', '<body ' . $keys, $buffer);                 
                
        return $buffer;
    }
    
    /**
     * Pre-filter buffer - calls self::pre_filter_* methods.
     * 
     * @see pre_filter_html
     * @see pre_filter_body
     * @param string $buffer
     * @return string
     */
    protected function pre_filter_buffer($buffer)
    {
        $buffer = $this->pre_filter_html($buffer);
        $buffer = $this->pre_filter_body($buffer);
        return $buffer;
    }
    
    /**
     * Post-filter buffer for any last-chance changes.
     * 
     * @param string $buffer
     * @return string
     */
    function post_filter_buffer($buffer)
    {
        $hooks = array_values($this->hooks);    
        $script = '<script id="script-hooks">' . json_encode($hooks, JSON_NUMERIC_CHECK) . '</script>';
        
        // Elegance vs performance, the struggle is real.        
        $buffer = str_ireplace('</body>', $script . '</body>', $buffer);
        
        return $buffer;
    }

    /**
     * Filters the raw keys from text nodes.
     * 
     * @param DOMDocument $dom
     * @param string $buffer
     * @return string
     */
    protected function filter_text($dom, $buffer)
    {
        $keys = $this->filter_keys($dom->documentElement, self::KEY_TEXT);          

        foreach ($keys as $key)
            $buffer = str_replace($key, '', $buffer);
        
        return $buffer;
    }
    
    /**
     * Filters the raw keys from nodes that are not rendered/off-canvas.
     * 
     * @param DOMDocument $dom
     * @param string $buffer
     * @return string
     */
    protected function filter_ignore($dom, $buffer)
    {
        $keys = $this->filter_keys($dom->documentElement, self::KEY_IGNORE);          

        foreach ($keys as $key)
            $buffer = str_replace($key, '', $buffer);
        
        return $buffer;
    }

    /**
     * Filters buffer by converting raw keys to inline annotations.
     * 
     * @param string $buffer
     * @return string
     */
    protected function filter_buffer($buffer)
    {
        if (!$this->is_enabled())
            return $buffer;
        
        $buffer = $this->pre_filter_buffer($buffer);
        $buffer = $this->pre_filter_nodes($buffer);
        $buffer = $this->pre_filter_comments($buffer);
        $buffer = $this->pre_filter_scripts($buffer);
        
        $dom = new DOMDocument();
        $dom->strictErrorChecking = false;
        $dom->validateOnParse = false;
        @$dom->loadHTML(mb_convert_encoding($buffer, 'HTML-ENTITIES', 'UTF-8'));
             
        if (null === $dom->documentElement) {
            // TODO(cs) fix...
            $buffer = $this->post_filter_scripts($buffer);
            $buffer = $this->post_filter_comments($buffer); 
            $buffer = $this->strip_keys($buffer);
            echo $buffer;
            return;
        }

        $this->filter_nodes($dom->documentElement);    
        $this->post_filter_html($dom);
        $this->post_filter_body($dom);        
        $buffer = $dom->saveHTML();
        
        $buffer = $this->post_filter_scripts($buffer);
        $buffer = $this->post_filter_comments($buffer);       
        $buffer = $this->filter_ignore($dom, $buffer);
        $buffer = $this->filter_text($dom, $buffer);
        $buffer = $this->post_filter_buffer($buffer);
        $this->set_cache(array_values($this->hooks));
                
        return $buffer;
    }    
    /**#@-*/
    
    /**#@+
     * Caching Mehods
     */
    
    /**
     * Returns the cache filename.
     * 
     * @param string|null $key
     * @return string
     */
    protected function get_cache_file($key = null)
    {
        $key = null === $key ? $this->get_request_key() : $key;
        $file = HOOKR_PLUGIN_DIR . 'cache' . DS . $key . '.php';                
        return $file;
    }
    
    /**
     * Write a value to the cache.
     * 
     * @param mixed $val
     * @param null|string $key
     * @return bool
     */
    protected function set_cache($val, $key = null)
    {
        $file = $this->get_cache_file($key);

        $contents = "<?php !defined('ABSPATH') && exit; ?>\n";
        $contents .= json_encode($val, JSON_NUMERIC_CHECK);
        
        return file_put_contents($file, $contents);        
    }

    /**
     * Returns value of the cache.
     * 
     * @param null|string $key
     * @return null|json
     */
    protected function get_cache($key = null)
    {
        $json = null;
        $file = $this->get_cache_file($key);
        if (is_file($file)) {
            $contents = explode("\n", file_get_contents($file));
            $json = json_decode(trim(array_pop($contents)));
        }
        return $json;
    }

    /**
     * Clears the cache.
     * 
     * @todo Make expire configurable via admin.
     * @return void
     */
    protected function clear_cache($force = false)
    {
        if (false === $force && 0 !== time() % 10)
            return;
        
        $files = glob(HOOKR_PLUGIN_DIR . 'cache' . DS . '*');
        
        foreach ($files as $file) {
            
            $time = filectime($file);
            
            if ($force || $time + 180 < time())
                unlink($file);            
        }        
    }
    
    /**
     * Returns a generic cache key.
     * 
     * @param null|string $key
     * @return string
     */
    protected function get_request_key($key = null)
    {
        $keys = array($key);
        $keys[] = SECURE_AUTH_SALT;
        $keys[] = $_SERVER['REQUEST_URI'];
        $keys = array_merge($keys, $_REQUEST);
        $keys = array_unique(array_values($keys));
        
        return md5(implode('', $keys));
    }
    
    /**
     * Renders & returns a template file.
     * 
     * @param string $tpl
     * @param array $data
     * @return string
     */
    function get_render($tpl, $data = array())
    {
        ob_start();
        $tpl = HOOKR_PLUGIN_DIR . DS . 'tpl' . DS . $tpl;
        parent::get_render($tpl, $data);
        return ob_get_clean();
    }
    /**#@-*/
    
    /**
     * Ajax entrypoint
     */
    function ajax()
    {
        switch (strtolower($_REQUEST['type'])) {
            
            case 'detail';
                $this->ajax_detail();
                break;
                
            case 'enable':
                $this->ajax_enable();                
                break;
            
            default:
                break;            
        };
        
        die();
    }
    
    /**
     * Ajax callback for hook details.
     * 
     * @return string
     */
    protected function ajax_detail()
    {
        extract($_REQUEST);
        
        $cache_key = md5($key . $id);
        
        if (null === ($detail = $this->get_cache($cache_key))) {

            if (null === $json = $this->get_cache($key)) {
                $this->render('hook-detail');
                return;
            }

            if (!@isset($json[$id])) {
                $this->render('hook-detail');            
                return;
            }
            
            $hook = $json[$id];
            $hook->annotation = null;
            
            $hook->value = preg_replace('/(array)\(/', '<span class="arr">$1</span>(', $hook->value);
            $hook->value = preg_replace('/([a-z_]+(?<!array))\(/i', '<span class="obj">$1</span>(', $hook->value);
            $hook->value = preg_replace('/(\')([^\']+)(\' =>)/', '$1<span class="key">$2</span>$3', $hook->value);
            $hook->value = preg_replace('/(NULL|TRUE|FALSE|\(empty\))/', '<span class="val">$1</span>', $hook->value);
            $hook->value = preg_replace('/( => ["\'])([^"\']+)(["\'])/', '$1<span class="str">$2</span>$3', $hook->value);            
            $hook->value = preg_replace('/^(["\'])([^"\']+)(["\'])$/', '$1<span class="str">$2</span>$3', $hook->value);
            $hook->value = preg_replace('/([ ]{4,}["\'](?!<))([^"\']+)((?!>)["\'])/', '$1<span class="str">$2</span>$3', $hook->value);                  
            $hook->value = preg_replace('/([ ]+)(\d+(\.\d+)?)(,?[\n])/', '$1<span class="num">$2$3</span>$4', $hook->value);            
            
            if (@isset($hook->caller)) {
                
                $caller = $hook->caller;

                $contents = file_get_contents(ABSPATH . $caller->file);
                $contents = explode("\n", $contents);
                $buffer = array();
                $invoker = $contents[$caller->line - 1];
                
                for ($i = $caller->line - 2, $j= 0; $i > 0; --$i) {

                    $line = $content = $contents[$i];

                    if (!preg_match('/^[ \t]*\/?\*/', $line))
                        continue;

                    if (empty($line))
                        ++$j;

                    if ($j > 2 || false !== strpos($content, '/*')) {
                        $buffer[] = $line;
                        break;
                    }

                    $buffer[] = $line;
                }

                $buffer = array_reverse($buffer);
                $block = implode("\n", $buffer);
                $hook->caller->invoker = $invoker;
                $hook->caller->block = $block;        
                
                if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
                    $block = new Hookr_DocComment(implode("\n", $buffer));
                    $hook->annotation = new Hookr_Annotation($block);
                }                
            }

            $detail = $this->get_render('hook-detail', array('hook' => $hook));
            $this->set_cache($detail, $cache_key);
        }
        
        header('Cache-control: public');
        echo $detail;
    }
    
    /**
     * Ajax callback for enabling/disabling
     * 
     * @return string
     */
    protected function ajax_enable()
    {
        extract($_REQUEST);
                        
        $settings = (object)$this->get_settings_raw();        
        // TODO(cs) This is fucked up... fix it.
        $settings->enabled = 'true' === $enabled ? 1 : 0;
       
        update_option($this->get_slug(), (array)$settings);
        echo 1;
    }
    
    /**
     * Abort hook callback.
     * 
     * @param mixed $mixed
     * @return mixed
     */
    function abort($mixed)
    {
        $this->abort = true;
        return $mixed;
    }
    
    /**
     * Error callback.
     * 
     * Something bad happened... mmmkay?
     * 
     * @param mixed $mixed
     * @return mixed
     */
    function error($mixed)
    {        
        if (null !== $this->error)
            return $mixed;
     
        list($error, $message, $file, $line, $context) = func_get_args();
        
        $error = null;
        
        if ($error instanceof Exception) {
            
            $this->error = $error;
            
        } else {
            
            if (is_wp_error($error)) {
                $error = new Hookr_Exception(
                    $error->get_error_message(),
                    $error->get_error_code()                
                );
            } else {
                $error = new Hookr_Exception($message, $error, $file, $line);
            }
            
            $code = $error->getCode();
            
            $levels = array(
                E_WARNING,
                E_STRICT,
                E_NOTICE,                
                E_DEPRECATED,
                E_CORE_WARNING,                
                E_USER_WARNING,
                E_USER_NOTICE,
                E_USER_DEPRECATED
            );
            
            foreach ($levels as $level) {
                if (0 !== $code & $level) {
                    $this->error = $error;
                    break;
                }
            }            
        }
        
        $this->abort = true;
        
        return $mixed;
    } 
    
    /**
     * Load hook callback.
     * 
     * @return void
     */
    function load()
    {        
        $this->settings = $this->get_settings();
        
        if ($this->is_abort())
            return;
        
        ob_start(array($this, 'buffer'));

        $this->register_hooks();
    }
    
    /**
     * Output buffer callback.
     * 
     * @param string $buffer
     * @return string
     */
    function buffer($buffer)
    {
        if (false === $this->abort) {
            $buffer = $this->filter_buffer($buffer);
        }
                    
        return $buffer;        
    }

    /**
     * Loaded hook callback.
     * 
     * @return void
     */
    function loaded()
    {
        add_action('wp_enqueue_scripts', array($this, 'register_styles'), 0);
        add_action('admin_enqueue_scripts', array($this, 'register_styles'), 0);                
        add_action('login_enqueue_scripts', array($this, 'register_styles'), 0);        
    }
    
    /**
     * Activate hook callback.
     * 
     * @return void
     */
    function activate()
    {        
        add_option($this->get_slug(), (array)$this->get_settings_default());
    }
    
    /**
     * Returns the plugin entry-point.
     * 
     * @return string
     */
    function get_file()
    {        
        return HOOKR_PLUGIN_FILE;
    }    
    
    /**
     * Plugin init.
     * 
     * @return object
     */
    static function init()
    {        
        return parent::get_instance(__CLASS__);
    }   
};