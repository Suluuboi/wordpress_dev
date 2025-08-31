<?php

/**
 * Main Storage Limit Manager Class
 *
 * @package StorageLimitManager
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Storage Limit Manager Class
 */
class StorageLimitManager
{
    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * The single instance of the class
     *
     * @var StorageLimitManager
     */
    protected static $_instance = null;

    /**
     * Admin instance
     *
     * @var SLM_Admin
     */
    public $admin = null;

    /**
     * Storage calculator instance
     *
     * @var SLM_Storage_Calculator
     */
    public $storage_calculator = null;

    /**
     * Upload handler instance
     *
     * @var SLM_Upload_Handler
     */
    public $upload_handler = null;

    /**
     * Settings manager instance
     *
     * @var SLM_Settings
     */
    public $settings = null;

    /**
     * AJAX handler instance
     *
     * @var SLM_Ajax
     */
    public $ajax = null;

    /**
     * Main StorageLimitManager Instance
     *
     * Ensures only one instance of StorageLimitManager is loaded or can be loaded.
     *
     * @static
     * @return StorageLimitManager - Main instance
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * StorageLimitManager Constructor
     */
    public function __construct()
    {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Define plugin constants
     */
    private function define_constants()
    {
        $this->define('SLM_PLUGIN_FILE', SLM_PLUGIN_PATH . 'storage-limit-manager.php');
        $this->define('SLM_PLUGIN_BASENAME', plugin_basename(SLM_PLUGIN_FILE));
        $this->define('SLM_VERSION', $this->version);
    }

    /**
     * Define constant if not already set
     *
     * @param string $name
     * @param string|bool $value
     */
    private function define($name, $value)
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * Include required core files
     */
    public function includes()
    {
        // Core classes
        include_once SLM_PLUGIN_PATH . 'includes/class-slm-settings.php';
        include_once SLM_PLUGIN_PATH . 'includes/class-slm-storage-calculator.php';
        include_once SLM_PLUGIN_PATH . 'includes/class-slm-upload-handler.php';
        include_once SLM_PLUGIN_PATH . 'includes/class-slm-ajax.php';

        // Admin classes
        if (is_admin()) {
            include_once SLM_PLUGIN_PATH . 'includes/admin/class-slm-admin.php';
        }
    }

    /**
     * Hook into actions and filters
     */
    private function init_hooks()
    {
        register_activation_hook(SLM_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(SLM_PLUGIN_FILE, array($this, 'deactivate'));

        add_action('init', array($this, 'init'), 0);
    }

    /**
     * Init StorageLimitManager when WordPress Initialises
     */
    public function init()
    {
        // Before init action
        do_action('slm_before_init');

        // Set up localisation
        $this->load_plugin_textdomain();

        // Initialize classes
        $this->settings = new SLM_Settings();
        $this->storage_calculator = new SLM_Storage_Calculator();
        $this->upload_handler = new SLM_Upload_Handler();
        $this->ajax = new SLM_Ajax();

        if (is_admin()) {
            $this->admin = new SLM_Admin();
        }

        // Init action
        do_action('slm_init');
    }

    /**
     * Load Localisation files
     */
    public function load_plugin_textdomain()
    {
        $locale = is_admin() && function_exists('get_user_locale') ? get_user_locale() : get_locale();
        $locale = apply_filters('plugin_locale', $locale, 'storage-limit-manager');

        unload_textdomain('storage-limit-manager');
        load_textdomain('storage-limit-manager', WP_LANG_DIR . '/storage-limit-manager/storage-limit-manager-' . $locale . '.mo');
        load_plugin_textdomain('storage-limit-manager', false, plugin_basename(dirname(SLM_PLUGIN_FILE)) . '/languages');
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Include settings class if not already loaded
        if (!class_exists('SLM_Settings')) {
            include_once SLM_PLUGIN_PATH . 'includes/class-slm-settings.php';
        }

        // Include storage calculator if not already loaded
        if (!class_exists('SLM_Storage_Calculator')) {
            include_once SLM_PLUGIN_PATH . 'includes/class-slm-storage-calculator.php';
        }

        // Set default options
        $settings = new SLM_Settings();
        $settings->set_default_settings();

        // Calculate initial usage
        $calculator = new SLM_Storage_Calculator();
        $calculator->recalculate_total_usage();

        // Trigger activation hook
        do_action('slm_activated');
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Trigger deactivation hook
        do_action('slm_deactivated');
    }

    /**
     * Get the plugin url
     *
     * @return string
     */
    public function plugin_url()
    {
        return untrailingslashit(plugins_url('/', SLM_PLUGIN_FILE));
    }

    /**
     * Get the plugin path
     *
     * @return string
     */
    public function plugin_path()
    {
        return untrailingslashit(plugin_dir_path(SLM_PLUGIN_FILE));
    }

    /**
     * Get the template path
     *
     * @return string
     */
    public function template_path()
    {
        return apply_filters('slm_template_path', 'storage-limit-manager/');
    }

    /**
     * Get Ajax URL
     *
     * @return string
     */
    public function ajax_url()
    {
        return admin_url('admin-ajax.php', 'relative');
    }
}
