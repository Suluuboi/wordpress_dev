<?php

/**
 * Plugin Integrations for Storage Limit Manager
 * Handles integration with third-party plugins like Elementor
 *
 * @package StorageLimitManager
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SLM_Plugin_Integrations Class
 */
class SLM_Plugin_Integrations
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('plugins_loaded', array($this, 'init_integrations'));
    }

    /**
     * Initialize plugin integrations
     */
    public function init_integrations()
    {
        // Elementor integration
        if (defined('ELEMENTOR_VERSION')) {
            $this->init_elementor_integration();
        }

        // WooCommerce integration
        if (class_exists('WooCommerce')) {
            $this->init_woocommerce_integration();
        }

        // Contact Form 7 integration
        if (defined('WPCF7_VERSION')) {
            $this->init_cf7_integration();
        }

        // Gravity Forms integration
        if (class_exists('GFForms')) {
            $this->init_gravity_forms_integration();
        }

        // WP Bakery integration
        if (defined('WPB_VC_VERSION')) {
            $this->init_wpbakery_integration();
        }

        // Beaver Builder integration
        if (class_exists('FLBuilder')) {
            $this->init_beaver_builder_integration();
        }

        // Divi integration
        if (defined('ET_BUILDER_VERSION')) {
            $this->init_divi_integration();
        }
    }

    /**
     * Initialize Elementor integration
     */
    private function init_elementor_integration()
    {
        // Hook into Elementor's AJAX actions
        add_action('wp_ajax_elementor_ajax', array($this, 'check_elementor_upload'), 1);
        add_action('wp_ajax_nopriv_elementor_ajax', array($this, 'check_elementor_upload'), 1);

        // Hook into Elementor's media upload
        add_filter('elementor/core/files/assets/url', array($this, 'check_elementor_asset_upload'), 10, 2);

        // Add storage info to Elementor editor
        add_action('elementor/editor/before_enqueue_scripts', array($this, 'enqueue_elementor_storage_script'));
    }

    /**
     * Check Elementor upload requests
     */
    public function check_elementor_upload()
    {
        // Check if this is an upload-related action
        $actions = isset($_POST['actions']) ? $_POST['actions'] : '';
        
        if (empty($actions)) {
            return;
        }

        // Decode Elementor actions
        $decoded_actions = json_decode(stripslashes($actions), true);
        
        if (!is_array($decoded_actions)) {
            return;
        }

        // Look for upload actions
        foreach ($decoded_actions as $action) {
            if (isset($action['action']) && $this->is_upload_action($action['action'])) {
                // Use the main upload handler
                $upload_handler = StorageLimitManager::instance()->upload_handler;
                $upload_handler->check_ajax_upload();
                break;
            }
        }
    }

    /**
     * Check if action is upload-related
     *
     * @param string $action Action name
     * @return bool
     */
    private function is_upload_action($action)
    {
        $upload_actions = array(
            'upload_image',
            'upload_file',
            'media_upload',
            'upload_media',
            'import_template',
            'library_direct_actions'
        );

        foreach ($upload_actions as $upload_action) {
            if (strpos($action, $upload_action) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check Elementor asset uploads
     *
     * @param string $url Asset URL
     * @param string $file_path File path
     * @return string
     */
    public function check_elementor_asset_upload($url, $file_path)
    {
        if (file_exists($file_path)) {
            $file_size = filesize($file_path);
            $calculator = StorageLimitManager::instance()->storage_calculator;
            
            if ($calculator->would_exceed_limit($file_size)) {
                // Prevent the upload by returning empty URL
                return '';
            }
        }
        
        return $url;
    }

    /**
     * Enqueue storage script for Elementor editor
     */
    public function enqueue_elementor_storage_script()
    {
        $restrictions = StorageLimitManager::instance()->upload_handler->get_upload_restrictions();
        
        wp_enqueue_script(
            'slm-elementor-integration',
            SLM_PLUGIN_URL . 'assets/elementor-integration.js',
            array('jquery'),
            SLM_VERSION,
            true
        );

        wp_localize_script('slm-elementor-integration', 'slm_elementor_data', array(
            'restrictions' => $restrictions,
            'messages' => array(
                'storage_exceeded' => __('Storage limit exceeded. Please delete some files before uploading.', 'storage-limit-manager'),
                'upload_blocked' => __('Upload blocked due to storage limits.', 'storage-limit-manager')
            )
        ));
    }

    /**
     * Initialize WooCommerce integration
     */
    private function init_woocommerce_integration()
    {
        // Hook into WooCommerce product image uploads
        add_action('wp_ajax_woocommerce_upload_image', array($this, 'check_woocommerce_upload'), 1);
        add_filter('woocommerce_product_gallery_attachment_ids', array($this, 'validate_product_images'));
    }

    /**
     * Check WooCommerce uploads
     */
    public function check_woocommerce_upload()
    {
        $upload_handler = StorageLimitManager::instance()->upload_handler;
        $upload_handler->check_ajax_upload();
    }

    /**
     * Validate WooCommerce product images
     *
     * @param array $attachment_ids
     * @return array
     */
    public function validate_product_images($attachment_ids)
    {
        // This is called when product images are saved
        // We can add additional validation here if needed
        return $attachment_ids;
    }

    /**
     * Initialize Contact Form 7 integration
     */
    private function init_cf7_integration()
    {
        add_filter('wpcf7_upload_file', array($this, 'check_cf7_upload'), 10, 2);
    }

    /**
     * Check Contact Form 7 uploads
     *
     * @param array $file File data
     * @param string $name Field name
     * @return array
     */
    public function check_cf7_upload($file, $name)
    {
        if (isset($file['size'])) {
            $calculator = StorageLimitManager::instance()->storage_calculator;
            
            if ($calculator->would_exceed_limit($file['size'])) {
                $file['error'] = __('File upload failed: Storage limit exceeded.', 'storage-limit-manager');
            }
        }
        
        return $file;
    }

    /**
     * Initialize Gravity Forms integration
     */
    private function init_gravity_forms_integration()
    {
        add_filter('gform_upload_path', array($this, 'check_gravity_forms_upload'), 10, 2);
    }

    /**
     * Check Gravity Forms uploads
     *
     * @param string $path Upload path
     * @param int $form_id Form ID
     * @return string
     */
    public function check_gravity_forms_upload($path, $form_id)
    {
        // Check if we have files being uploaded
        if (!empty($_FILES)) {
            $upload_handler = StorageLimitManager::instance()->upload_handler;
            $upload_handler->check_ajax_upload();
        }
        
        return $path;
    }

    /**
     * Initialize WP Bakery integration
     */
    private function init_wpbakery_integration()
    {
        add_action('wp_ajax_vc_upload_image', array($this, 'check_wpbakery_upload'), 1);
    }

    /**
     * Check WP Bakery uploads
     */
    public function check_wpbakery_upload()
    {
        $upload_handler = StorageLimitManager::instance()->upload_handler;
        $upload_handler->check_ajax_upload();
    }

    /**
     * Initialize Beaver Builder integration
     */
    private function init_beaver_builder_integration()
    {
        add_action('wp_ajax_fl_builder_upload', array($this, 'check_beaver_builder_upload'), 1);
    }

    /**
     * Check Beaver Builder uploads
     */
    public function check_beaver_builder_upload()
    {
        $upload_handler = StorageLimitManager::instance()->upload_handler;
        $upload_handler->check_ajax_upload();
    }

    /**
     * Initialize Divi integration
     */
    private function init_divi_integration()
    {
        add_action('wp_ajax_et_fb_ajax_save', array($this, 'check_divi_upload'), 1);
        add_action('wp_ajax_et_builder_upload_image', array($this, 'check_divi_upload'), 1);
    }

    /**
     * Check Divi uploads
     */
    public function check_divi_upload()
    {
        $upload_handler = StorageLimitManager::instance()->upload_handler;
        $upload_handler->check_ajax_upload();
    }

    /**
     * Get list of integrated plugins
     *
     * @return array
     */
    public function get_integrated_plugins()
    {
        $plugins = array();

        if (defined('ELEMENTOR_VERSION')) {
            $plugins['elementor'] = array(
                'name' => 'Elementor',
                'version' => ELEMENTOR_VERSION,
                'status' => 'integrated'
            );
        }

        if (class_exists('WooCommerce')) {
            $plugins['woocommerce'] = array(
                'name' => 'WooCommerce',
                'version' => WC_VERSION,
                'status' => 'integrated'
            );
        }

        if (defined('WPCF7_VERSION')) {
            $plugins['contact-form-7'] = array(
                'name' => 'Contact Form 7',
                'version' => WPCF7_VERSION,
                'status' => 'integrated'
            );
        }

        if (class_exists('GFForms')) {
            $plugins['gravity-forms'] = array(
                'name' => 'Gravity Forms',
                'version' => GFForms::$version,
                'status' => 'integrated'
            );
        }

        return $plugins;
    }
}
