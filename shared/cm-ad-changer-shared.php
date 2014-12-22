<?php
if( !defined('ABSPATH') )
{
    exit;
}

class CMAdChangerShared
{
    protected static $instance = NULL;
    public static $labels = array();
    public static $calledClassName;

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
        self::setupOptions();
        self::loadClasses();
        self::registerActions();
        self::registerShortcodes();

        if( get_option('cmac_afterActivation', 0) == 1 )
        {
            add_action('admin_notices', array(self::$calledClassName, '__showProMessage'));
        }
    }

    /**
     * Shows the message about Pro versions on activate
     */
    public static function __showProMessage()
    {
        /*
         * Only show to admins
         */
        if( current_user_can('manage_options') )
        {
            ?>
            <div id="message" class="updated fade">
                <p><strong>New !! Pro versions of Ad Changer are <a href="http://ad-changer.cminds.com/"  target="_blank">available here</a></strong></p>
            </div>
            <?php
            delete_option('cmac_afterActivation');
        }
    }

    /**
     * Register the plugin's shared actions (both backend and frontend)
     */
    private static function registerActions()
    {

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
        define('CMAC_DEBUG', '1');
        define('CMAC_UPLOAD_PATH', 'ac_uploads/');
        define('CMAC_TMP_UPLOAD_PATH', 'tmp/');

        define('CMAC_CAMPAIGNS_TABLE', 'cm_campaigns');
        define('CMAC_IMAGES_TABLE', 'cm_campaign_images');
        define('CMAC_PERIODS_TABLE', 'cm_campaign_periods');
        define('CMAC_HISTORY_TABLE', 'cm_campaign_history');
        define('CMAC_MANAGERS_TABLE', 'cm_campaign_managers');

        define('CMAC_CAMPAIGNS_LIMIT', '100');
        define('CMAC_BANNERS_PER_CAMPAIGN_LIMIT', '50');
        define('CMAC_HISTORY_PER_PAGE_LIMIT', '50');

        define('CMAC_MENU_OPTION', 'cmac_settings');
        define('CMAC_ABOUT_OPTION', 'cmac_about');
        define('CMAC_PRO_OPTION', 'cmac_pro');
        define('CMAC_SETTINGS_OPTION', 'cmac_settings');
        define('CMAC_IMPORTEXPORT_OPTION', 'cmac_importexport');

        define('CMAC_API_ERROR_1', 'Client host unknown');
        define('CMAC_API_ERROR_2', 'Campaign ID not set');
        define('CMAC_API_ERROR_3', 'Campaign not found');
        define('CMAC_API_ERROR_4', 'Client host is not registered');
        define('CMAC_API_ERROR_5', 'Campaign is inactive');
        define('CMAC_API_ERROR_6', 'There is no image to display');
        define('CMAC_API_ERROR_7', 'Unknown error');
        define('CMAC_API_ERROR_8', 'Unknown action');
        define('CMAC_API_ERROR_9', 'Server is inactive');
        define('CMAC_API_ERROR_10', 'Maximum impressions achieved');
        define('CMAC_API_ERROR_11', 'Maximum clicks achieved');
        define('CMAC_API_ERROR_12', 'Banner ID is not set');
        define('CMAC_API_ERROR_13', 'Campaign is not active today');

        self::$labels = array(
            'acs_active'           => 'Server status, if set than server will accept connections from Ad Changer clients',
            'acs_div_wrapper'      => 'Div Wrapper (server side) - Will add div around banner on server side',
            'acs_class_name'       => 'Class Name - Will set the class name for div',
            'acs_custom_css'       => 'Custom CSS will be injected into body before banner is shown and only on post or pages where campaign is active. Example: #featured.has-badge {margin-bottom: 85px;}',
            /*
             * CAMPAIGNS
             */
            'title'                => 'Campaign Name. For internal use only',
            'campaign_id'          => 'Campaign ID. When referring to campaign in shortcode only use campaign id',
            'comment'              => 'Campaign Notes - This is only for internal use of campaign manager',
            'link'                 => 'Campaign Target URL - Target URL specified in banner will override this. WARNING: Clicks are counted only if it is set!',
            'status'               => 'Campaign Status - if set campaign will be active ',
            'max_impressions'      => 'Leave it 0 to remove limit or set to max number allowed',
            'max_clicks'           => 'Leave it 0 to remove limit or set to max number allowed',
            'use_selected_banner'  => 'Display Banner Method (Selected - Will only serve selected banner or Random will serve random banner based on banner weight',
            'categories'           => 'Approved domains - List of URL of approved clients. If not specified all clients will be served.',
            'active_dates'         => 'Active Dates - List of dates when campaign is active. If not set than campaign is active on all dates.',
            'active_week_days'     => 'Week Days description',
            'campaign_images'      => 'Campaign Images - All banners for this campaign',
            'banner_title'         => 'Banner Name',
            'banner_title_tag'     => 'Banner Title - Will appear in banner img title',
            'banner_alt_tag'       => 'Banner Alt - Will appear in banner img alt',
            'banner_link'          => 'Banner Target URL - Will override campaign target url if specified',
            'banner_weight'        => 'Banner Weight - Will define what is the relative amount of impressions for this banner in compare to other banners in the campaign',
            'custom_js'            => 'Custom JavaScript - JS function to add to banner once clicked Example: alert(\'Hello\');',
        );
    }

    /**
     * Setup plugin options
     *
     * @access private
     * @since 1.1
     * @return void
     */
    private static function setupOptions()
    {

        /*
         * Adding additional options
         */
        do_action('cmac_setup_options');
    }

    /**
     * Create custom post type
     */
    public static function cmac_create_post_types()
    {
        return;
    }

    /**
     * Create taxonomies
     */
    public static function cmac_create_taxonomies()
    {
        return;
    }

    /**
     * Load plugin's required classes
     *
     * @access private
     * @since 1.1
     * @return void
     */
    private static function loadClasses()
    {
        /*
         * Load the file with shared global functions
         */
        require_once CMAC_PLUGIN_DIR . "shared/functions.php";
        require_once CMAC_PLUGIN_DIR . "shared/classes/cmac-widget.php";
        require_once CMAC_PLUGIN_DIR . "shared/classes/cmac-data.php";
        require_once CMAC_PLUGIN_DIR . "shared/classes/cmac-client.php";

        do_action('cmac_loadClasses');
    }

    public static function registerShortcodes()
    {
        if( !shortcode_exists('cm_ad_changer') )
        {
            add_shortcode('cm_ad_changer', array('CMAC_Client', 'banner_output'));
        }
    }

    public function registerFilters()
    {
        return;
    }

    public static function initSession()
    {
        if( !session_id() )
        {
            session_start();
        }
    }

    public static function cmac_log($message)
    {
        if( CMAC_DEBUG != '1' )
        {
            return;
        }
        $file = CMAC_PLUGIN_DIR . 'log.txt';
        if(!is_file($file)){
            touch($file);
            chmod($file, 0777);
        }
        $f = fopen($file, 'a');
        fwrite($f, date('Y-m-d H:i:s') . ': ' . $message . "\n");
        fclose($f);
    }

}