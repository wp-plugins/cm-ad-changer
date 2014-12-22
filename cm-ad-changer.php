<?php
/*
  Plugin Name: CM Ad Changer
  Plugin URI: http://ad-changer.cminds.com/
  Description: Ad Changer. Manage, Track and Report Advertising Campaigns on your site
  Version: 1.4.4
  Author: CreativeMindsSolutions
  Author URI: http://plugins.cminds.com/
 */

// Exit if accessed directly
if( !defined('ABSPATH') )
{
    exit;
}

/**
 * Main plugin class file.
 * What it does:
 * - checks which part of the plugin should be affected by the query frontend or backend and passes the control to the right controller
 * - manages installation
 * - manages uninstallation
 * - defines the things that should be global in the plugin scope (settings etc.)
 * @author CreativeMindsSolutions - Marcin Dudek
 */
class CMAdChanger
{
    public static $calledClassName;
    protected static $instance = NULL;
    public static $message = '';

    /**
     * Main Instance
     *
     * Insures that only one instance of class exists in memory at any one
     * time. Also prevents needing to define globals all over the place.
     *
     * @since 1.0
     * @static
     * @staticvar array $instance
     * @return The one true AKRSubscribeNotifications
     */
    public static function instance()
    {
        $class = __CLASS__;
        if( !isset(self::$instance) && !( self::$instance instanceof $class ) )
        {
            self::$instance = new $class;
        }
        return self::$instance;
    }

    public function __construct()
    {
        if( empty(self::$calledClassName) )
        {
            self::$calledClassName = __CLASS__;
        }

        self::setupConstants();

        if( is_admin() )
        {
            /*
             * Backend
             */
            require_once CMAC_PLUGIN_DIR . '/backend/cm-ad-changer-backend.php';
            $CMAdChangerBackendInstance = CMAdChangerBackend::instance();
        }
        else
        {
            /*
             * Frontend
             */
            require_once CMAC_PLUGIN_DIR . '/frontend/cm-ad-changer-frontend.php';
            $CMAdChangerFrontendInstance = CMAdChangerFrontend::instance();
        }

        /*
         * Shared
         */
        require_once CMAC_PLUGIN_DIR . '/shared/cm-ad-changer-shared.php';
        $CMAdChangerSharedInstance = CMAdChangerShared::instance();
    }

    /**
     * Setup plugin constants
     *
     * @access private
     * @since 1.1
     * @return void
     */
    private static function setupConstants()
    {
        /**
         * Define Plugin prefix
         *
         * @since 1.2.5
         */
        if( !defined('CMAC_PREFIX') )
        {
            define('CMAC_PREFIX', 'cmac_');
        }

        /**
         * Define Plugin Version
         *
         * @since 1.0
         */
        if( !defined('CMAC_VERSION') )
        {
            define('CMAC_VERSION', '1.2.5');
        }

        /**
         * Define Plugin Directory
         *
         * @since 1.0
         */
        if( !defined('CMAC_PLUGIN_DIR') )
        {
            define('CMAC_PLUGIN_DIR', plugin_dir_path(__FILE__));
        }

        /**
         * Define Plugin URL
         *
         * @since 1.0
         */
        if( !defined('CMAC_PLUGIN_URL') )
        {
            define('CMAC_PLUGIN_URL', plugin_dir_url(__FILE__));
        }

        /**
         * Define Plugin File Name
         *
         * @since 1.0
         */
        if( !defined('CMAC_PLUGIN_FILE') )
        {
            define('CMAC_PLUGIN_FILE', __FILE__);
        }

        /**
         * Define Plugin Slug name
         *
         * @since 1.0
         */
        if( !defined('CMAC_SLUG_NAME') )
        {
            define('CMAC_SLUG_NAME', 'cm-ad-changer');
        }

        /**
         * Define Plugin name
         *
         * @since 1.0
         */
        if( !defined('CMAC_NAME') )
        {
            define('CMAC_NAME', 'CM Ad Changer');
        }

        /**
         * Define Plugin basename
         *
         * @since 1.0
         */
        if( !defined('CMAC_PLUGIN') )
        {
            define('CMAC_PLUGIN', plugin_basename(__FILE__));
        }
    }

    public static function _install($networkwide)
    {
        global $wpdb;

        if( function_exists('is_multisite') && is_multisite() )
        {
            /*
             * Check if it is a network activation - if so, run the activation function for each blog id
             */
            if( $networkwide )
            {
                /*
                 * Get all blog ids
                 */
                $blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM {$wpdb->blogs}"));
                foreach($blogids as $blog_id)
                {
                    switch_to_blog($blog_id);
                    self::__install();
                }
                restore_current_blog();
                return;
            }
        }

        self::__install();
    }

    private static function __install()
    {
        /*
         *  have to use $table_prefix
         */
        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . CMAC_CAMPAIGNS_TABLE . ' (
				  campaign_id int(11) NOT NULL AUTO_INCREMENT,
				  title varchar(100) NOT NULL,
				  link varchar(200) NOT NULL,
				  selected_banner int(11) NOT NULL DEFAULT "0",
				  banner_display_method enum("random","selected","all") NOT NULL,
				  max_clicks bigint(20) NOT NULL,
				  max_impressions bigint(20) NOT NULL,
				  comment text NOT NULL,
				  custom_js text NOT NULL,
				  active_week_days varchar(30) NOT NULL,
				  status tinyint(4) NOT NULL,
				  send_notifications tinyint(4) NOT NULL,
				  PRIMARY KEY  (campaign_id)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
        dbDelta($sql);
        $sql = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . CMAC_IMAGES_TABLE . ' (
				  image_id int(11) NOT NULL AUTO_INCREMENT,
				  campaign_id int(11) NOT NULL,
				  parent_image_id int(11) NOT NULL DEFAULT "0",
				  title varchar(50) NOT NULL DEFAULT "",
				  title_tag varchar(200) NOT NULL DEFAULT "",
				  alt_tag varchar(200) NOT NULL DEFAULT "",
				  link varchar(150) NOT NULL DEFAULT "",
				  weight tinyint(4) NOT NULL DEFAULT "0",
				  filename varchar(50) NOT NULL DEFAULT "",
				  PRIMARY KEY  (image_id)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
        dbDelta($sql);

        self::__createUploadsDir();
        self::__resetOptions();
    }

    private static function __createUploadsDir()
    {
        add_action('admin_notices', array(self::$calledClassName, 'showErrorMessage'));

        $uploadDir = wp_upload_dir();
        if( isset($uploadDir['error']) && !empty($uploadDir['error']) )
        {
            self::$message = 'Error: ' . $uploadDir['error'];
            return;
        }

        $baseDir = $uploadDir['basedir'] . '/' . CMAC_UPLOAD_PATH;
        $tmpDir = $baseDir . CMAC_TMP_UPLOAD_PATH;

        if( !is_dir($tmpDir) )
        {
            if( !wp_mkdir_p($tmpDir) )
            {
                self::$message = 'Error: Your WP uploads folder is not writable! The plugin requires a writable uploads folder in order to work.';
                return;
            }
        }
    }

    public static function showErrorMessage()
    {
        if( !empty(self::$message) )
        {
            cminds_show_message(self::$message, true);
        }
    }

    private static function __resetOptions()
    {
        return;
    }

    public static function _uninstall()
    {
        return;
    }

    public function registerAjaxFunctions()
    {
        return;
    }

}

/**
 * The main function responsible for returning the one true plugin class
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $marcinPluginPrototype = MarcinPluginPrototypePlugin(); ?>
 *
 * @since 1.0
 * @return object The one true CM_Micropayment_Platform Instance
 */
function CMAdChangerInit()
{
    return CMAdChanger::instance();
}

$CMAdChanger = CMAdChangerInit();

register_activation_hook(__FILE__, array('CMAdChanger', '_install'));
register_deactivation_hook(__FILE__, array('CMAdChanger', '_uninstall'));
