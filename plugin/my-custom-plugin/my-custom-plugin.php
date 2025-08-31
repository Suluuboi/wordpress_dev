<?php
/**
 * Plugin Name: My Custom Plugin
 * Plugin URI: https://yourwebsite.com/
 * Description: A sample WordPress plugin for development.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: my-custom-plugin
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MCP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MCP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MCP_VERSION', '1.0.0');

/**
 * Main Plugin Class
 */
class MyCustomPlugin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('my-custom-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Add shortcode
        add_shortcode('my_custom_shortcode', array($this, 'custom_shortcode'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'my-custom-plugin-style',
            MCP_PLUGIN_URL . 'assets/css/style.css',
            array(),
            MCP_VERSION
        );
        
        wp_enqueue_script(
            'my-custom-plugin-script',
            MCP_PLUGIN_URL . 'assets/js/script.js',
            array('jquery'),
            MCP_VERSION,
            true
        );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('My Custom Plugin Settings', 'my-custom-plugin'),
            __('My Custom Plugin', 'my-custom-plugin'),
            'manage_options',
            'my-custom-plugin',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('my_custom_plugin_settings');
                do_settings_sections('my_custom_plugin_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mcp_sample_option"><?php _e('Sample Option', 'my-custom-plugin'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="mcp_sample_option" name="mcp_sample_option" 
                                   value="<?php echo esc_attr(get_option('mcp_sample_option')); ?>" />
                            <p class="description"><?php _e('This is a sample option.', 'my-custom-plugin'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Custom shortcode
     */
    public function custom_shortcode($atts) {
        $atts = shortcode_atts(array(
            'message' => __('Hello from My Custom Plugin!', 'my-custom-plugin'),
        ), $atts);
        
        return '<div class="my-custom-plugin-message">' . esc_html($atts['message']) . '</div>';
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Add default options
        add_option('mcp_sample_option', 'Default value');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize the plugin
new MyCustomPlugin();
