<?php
if( !defined('ABSPATH') )
{
    exit;
}
define('KB', 1024);
define('MB', 1048576);
define('GB', 1073741824);
define('TB', 1099511627776);
class CMAdChangerBackend
{
    public static $calledClassName;
    protected static $instance = NULL;
    protected static $cssPath = NULL;
    protected static $jsPath = NULL;
    protected static $viewsPath = NULL;
    protected static $currentPage = NULL;
    protected static $currentSubpage = NULL;
    protected static $currentActionVars = array();
    protected static $errors = array();
    protected static $success = '';
    
    const PAGE_YEARLY_OFFER = 'https://www.cminds.com/store/cm-wordpress-plugins-yearly-membership/';

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

        self::$cssPath = CMAC_PLUGIN_URL . 'backend/assets/css/';
        self::$jsPath = CMAC_PLUGIN_URL . 'backend/assets/js/';
        self::$viewsPath = CMAC_PLUGIN_DIR . 'backend/views/';

        self::$currentPage = filter_input(INPUT_GET, 'page');

        add_action('admin_menu', array(self::$calledClassName, 'cmac_admin_menu'));
        add_action('admin_enqueue_scripts', array(self::$calledClassName, 'cmac_admin_stylesheets'));

        add_action('admin_notices', array(self::$calledClassName, 'cmac_glossary_admin_notice_wp33'));

        add_action('wp_ajax_cmac_event_dispatcher', array(self::$calledClassName, 'cmac_event_dispatcher'));
        add_action('wp_ajax_nopriv_cmac_event_dispatcher', array(self::$calledClassName, 'cmac_event_dispatcher'));

        add_action('wp_ajax_ac_upload_image', array(self::$calledClassName, 'cmac_upload_image'));
        add_action('wp_ajax_nopriv_ac_upload_image', array(self::$calledClassName, 'cmac_upload_image'));
    }
    public static function admin_head()
    {
        echo '<style type="text/css">
        		#toplevel_page_cmtt_menu_option a[href*="cm-wordpress-plugins-yearly-membership"] {color: white;}
    			a[href*="cm-wordpress-plugins-yearly-membership"]:before {font-size: 16px; vertical-align: middle; padding-right: 5px; color: #d54e21;
    				content: "\f487";
				    display: inline-block;
					-webkit-font-smoothing: antialiased;
					font: normal 16px/1 \'dashicons\';
    			}
    			#toplevel_page_cmtt_menu_option a[href*="cm-wordpress-plugins-yearly-membership"]:before {vertical-align: bottom;}

        	</style>';
    }
    /**
     * Pages dispatcher
     * @param String   $ac_page  Page slug
     */
    public static function cmac_load_page($ac_page = null)
    {
        if( empty($ac_page) )
        {
            $ac_page = self::$currentPage;
        }

        $php_info = cminds_parse_php_info();

        if( (!isset($php_info['gd']) || !is_array($php_info['gd'])) && $ac_page != 'cmac_about' )
        {
            self::$errors[] = 'GD library is not installed! Please install this module to use Ad Changer Server';
        }

        self::cmac_display_admin_page($ac_page);
    }

    /**
     * General function for displaying admin pages - includes navigation bar and template
     * @global type $plugin_page
     * @param type $pageName
     * @return type
     */
    public static function cmac_display_admin_page($pageName)
    {
        if( empty($pageName) )
        {
            return;
        }

        $realPageName = str_ireplace(CMAC_PREFIX, '', $pageName);

        extract(self::$currentActionVars);

        ob_start();
        require_once self::$viewsPath . 'admin_' . $realPageName . '.php';
        $content = ob_get_contents();
        ob_end_clean();
        require_once self::$viewsPath . 'admin_template.php';
    }

    /**
     * Shows admin menu
     * @global string $submenu
     */
    public static function cmac_admin_menu()
    {
        global $submenu;
        $current_user = wp_get_current_user();
        if( !user_can($current_user, 'manage_options') )
        {
            return;
        }

        $settings_page = add_menu_page(CMAC_NAME, CMAC_NAME, 'manage_options', CMAC_MENU_OPTION, array(self::$calledClassName, 'cmac_load_page'), self::$cssPath . 'images/cm-ad-changer-icon.png');

        $campaigns_subpage = add_submenu_page(CMAC_MENU_OPTION, 'Campaigns', 'Campaigns', 'manage_options', 'cmac_campaigns', array(self::$calledClassName, 'cmac_load_page'));
        $about_subpage = add_submenu_page(CMAC_MENU_OPTION, 'About', 'About', 'manage_options', 'cmac_about', array(self::$calledClassName, 'cmac_load_page'));
        $pro_version_subpage = add_submenu_page(CMAC_MENU_OPTION, 'Pro Version', 'Pro Version', 'manage_options', 'cmac_pro', array(self::$calledClassName, 'cmac_load_page'));

        add_action($campaigns_subpage, array(self::$calledClassName, 'cmac_campaigns_action'), 0);
        add_action($settings_page, array(self::$calledClassName, 'cmac_settings_action'), 0);
        $submenu[CMAC_MENU_OPTION][0][0] = 'Settings';
        
        if( current_user_can('manage_options') ){
            $submenu[CMAC_MENU_OPTION][999] = array('Yearly membership offer', 'manage_options', self::PAGE_YEARLY_OFFER);
            add_action('admin_head', array(__CLASS__, 'admin_head'));
        }
    }

    /**
     * Function being fired before the Campaings tab is displayed.
     *
     * It should handle the $_POST request and prepare the data for the view.
     */
    public static function cmac_campaigns_action()
    {
        $data = array();
        $fields_data = array();

        if( !empty($_POST) )
        {
            $data = CMAC_Data::cmac_handle_campaigns_post($_POST);
            if( !empty($data) )
            {
                $bannersInfo = array();
                foreach($data['fields_data'] as $key => $value)
                {
                    if( strpos($key, 'banner_') === 0 )
                    {
                        $fieldKey = str_replace('banner_', '', $key);
                        if( is_array($value) )
                        {
                            foreach($value as $bannerKey => $fieldValue)
                            {
                                $bannersInfo[$bannerKey][$fieldKey] = $fieldValue;
                            }
                        }
                        unset($data[$key]);
                    }
                }

                $fields_data = $data['fields_data'];
                $fields_data['banners'] = $bannersInfo;

                if( isset($data['errors']) && !empty($data['errors']) )
                {
                    self::$errors = $data['errors'];
                }
            }
            else
            {
                $fields_data['category_ids'] = isset($_POST['category_ids']) ? $_POST['category_ids'] : null;
                $fields_data['category_title'] = isset($_POST['category_title']) ? $_POST['category_title'] : null;
                self::$success = 'Campaign was successfully stored!';
            }
        }
        else
        {
            if( isset($_GET['action']) )
            {
                if( isset($_GET['campaign_id']) && is_numeric($_GET['campaign_id']) )
                {
                    switch($_GET['action'])
                    {
                        case 'edit':
                            $fields_data = CMAC_Data::cmac_get_campaign($_GET['campaign_id']);
                            if( isset($_GET['saved']) && $_GET['saved'] == '1' )
                            {
                                self::$success = 'Campaign was successfully stored!';
                            }
                            break;
                        case 'delete':
                            CMAC_Data::cmac_remove_campaign($_GET['campaign_id']);
                            self::$success = 'Campaign was removed from database!';
                            break;
                    }
                }
            }
        }

        $campaigns = CMAC_Data::cmac_get_campaigns();

        self::$currentActionVars['campaigns'] = $campaigns;
        self::$currentActionVars['data'] = $data;
        self::$currentActionVars['fields_data'] = $fields_data;
    }

    /**
     * Function being fired before the Settings tab is displayed.
     *
     * It should handle the $_POST request and prepare the data for the view.
     */
    public static function cmac_settings_action()
    {
        if( !empty($_POST) )
        {
            $data = CMAC_Data::cmac_handle_settings_post($_POST);
            if( !empty($data) )
            {
                $fields_data = $data['fields_data'];
                if( isset($data['errors']) && !empty($data['errors']) && is_array($data['errors']) )
                {
                    self::$errors = $data['errors'];
                }
            }
            else
            {
                $fields_data['acs_active'] = $_POST['acs_active'];
                $fields_data['acs_div_wrapper'] = $_POST['acs_div_wrapper'];
                $fields_data['acs_class_name'] = $_POST['acs_class_name'];
                $fields_data['acs_custom_css'] = $_POST['acs_custom_css'];
                $fields_data['acc_campaign_id'] = $_POST['acc_campaign_id'];
                $success = 'Settings were successfully stored!';
            }
        }
        else
        {
            $fields_data['acs_active'] = get_option('acs_active', null);
            $fields_data['acs_div_wrapper'] = get_option('acs_div_wrapper', '0');
            $fields_data['acs_class_name'] = get_option('acs_class_name', '');
            $fields_data['acs_custom_css'] = get_option('acs_custom_css', '');
        }

        self::$currentActionVars['fields_data'] = $fields_data;
    }

    /**
     * Include admin stylesheets
     */
    public static function cmac_admin_stylesheets()
    {
        wp_enqueue_style('jqueryUIStylesheet', self::$cssPath . 'jquery-ui-1.10.3.custom.css');
        wp_enqueue_style('adChangerStylesheet', self::$cssPath . 'style.css');

        /*
         * Load some of the scripts and styles on the campaings tab only
         */
        if( self::$currentPage == 'cmac_campaigns' )
        {
            wp_enqueue_style('datePickerUIStylesheet', self::$jsPath . 'datepicker/smoothness.datepick.css');
            wp_enqueue_style('speechBubblesStylesheet', self::$jsPath . 'speechbubbles/speechbubbles.css');

            wp_enqueue_script('ad-changer-campaigns-admin-js', self::$jsPath . 'campaigns.js', array(), '1.0.0');
            wp_enqueue_script('plupload-full-js', self::$jsPath . 'plupload/plupload.full.js', array(), '1.0.0');
            wp_enqueue_script('plupload-queue-js', self::$jsPath . 'plupload/jquery.plupload.queue/jquery.plupload.queue.js', array(), '1.0.0');
            wp_enqueue_script('jquery-ui-datepicker');
//            wp_enqueue_script('datepicker', self::$jsPath . 'datepicker/jquery.datepick.js', array('jquery'), '1.0.0');
            wp_enqueue_script('speechBubbles', self::$jsPath . 'speechbubbles/speechbubbles.js', array(), '1.0.0');
        }

        wp_enqueue_script('ad-changer-admin-js', self::$jsPath . 'scripts.js', array(), '1.0.0');
        wp_enqueue_script('mouseWheel', self::$jsPath . '/jquery-ui/jquery.mousewheel.js', array('jquery'), '1.0.0');

        $int_version = (int) str_replace('.', '', get_bloginfo('version'));
        if( $int_version < 100 )
        {
            $int_version *= 10; // will be 340 or 341 or 350 etc
        }

        if( $int_version > 320 )
        {
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-widget');
            wp_enqueue_script('jquery-ui-tooltip');
            wp_enqueue_script('jquery-ui-tabs');
            wp_enqueue_script('jqueryUIPosition');
            wp_enqueue_script('jquery-ui-button');
            
        }
        else
        {
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-widget');
            wp_enqueue_script('jqueryUIPosition');
            wp_enqueue_script('jquery-ui-tabs');
            wp_enqueue_script('jquery-ui-button');
        }

        if( $int_version >= 350 )
        {
            wp_enqueue_script('jquery-ui-spinner');
            wp_enqueue_script('jquery-ui-tooltip');
        }


        if( $int_version < 350 )
        {
            wp_enqueue_script('jQueryMissingUI', plugins_url('assets/js/jquery-ui/missing_ui.js', __FILE__), array(), '1.0.0');
        }
    }

    /**
     * Displays the horizontal navigation bar
     * @global string $submenu
     * @global type $plugin_page
     */
    public static function cmac_showNav()
    {
        global $submenu, $plugin_page;
        $submenus = array();
        $scheme = is_ssl() ? 'https://' : 'http://';
        $adminUrl = str_replace($scheme . $_SERVER['HTTP_HOST'], '', admin_url());
        $currentUri = str_replace($adminUrl, '', $_SERVER['REQUEST_URI']);

        if( isset($submenu[CMAC_MENU_OPTION]) )
        {
            $thisMenu = $submenu[CMAC_MENU_OPTION];
            foreach($thisMenu as $item)
            {
                $slug = $item[2];
                $isCurrent = ($slug == $plugin_page || strpos($item[2], '.php') === strpos($currentUri, '.php'));
                $isExternalPage = strpos($item[2], 'http') !== FALSE;
                $isNotSubPage = $isExternalPage || strpos($item[2], '.php') !== FALSE;
                $url = $isNotSubPage ? $slug : get_admin_url(null, 'admin.php?page=' . $slug);

                if( $isCurrent )
                {
                    self::$currentSubpage = $item;
                }
                $submenus[] = array(
                    'link'    => $url,
                    'title'   => $item[0],
                    'current' => $isCurrent,
                    'target'  => $isExternalPage ? '_blank' : ''
                );
            }

            $errors = self::$errors;
            $success = self::$success;

            require_once self::$viewsPath . 'admin_nav.php';
        }
    }

    /**
     * Adds a notice about wp version lower than required 3.3
     * @global type $wp_version
     */
    public static function cmac_glossary_admin_notice_wp33()
    {
        global $wp_version;

        if( version_compare($wp_version, '3.3', '<') )
        {
            $message = __('CM Ad Changer requires Wordpress version 3.3 or higher to work properly.');
            cminds_show_message($message, true);
        }
    }

    /**
     * Dispatches the AJAX events
     * @return type
     */
    public static function cmac_event_dispatcher()
    {
        $args = filter_input_array(INPUT_POST);

        $result = CMAC_Data::cmac_event_save($args);
        return $result;
    }

    /**
     * Uploading the images to tmp folder
     */
    public static function cmac_upload_image()
    {
        $maxSize = 2*MB;
        $uploadedfile = $_FILES['file'];
        $upload_overrides = array('test_form' => false);

        $validate = wp_check_filetype_and_ext($uploadedfile['tmp_name'], $uploadedfile['name'], array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'));

        if( !$validate['ext'] )
        {
            die(__('Error: Invalid file extension!'));
        }
        if(preg_match("/gif/", $uploadedfile['type'])){
            $maxSize = 5*MB;
        }
        if( (int) $uploadedfile['size'] > $maxSize )
        {
            die(__('Error: File too big!'));
        }

        $uploadDir = wp_upload_dir();
        $baseDir = $uploadDir['basedir'] . '/' . CMAC_UPLOAD_PATH;
        $tmpDir = $baseDir . CMAC_TMP_UPLOAD_PATH;

        if( ($handle = opendir($baseDir)) !== FALSE )
        {
            $existing_files = array();
            while(false !== ($entry = readdir($handle)))
            {
                $existing_files[] = $entry;
            }

            do
            {
                $new_filename = rand(1000000, 9999999) . '.' . $validate['ext'];
            }
            while(in_array($new_filename, $existing_files));
            move_uploaded_file($uploadedfile['tmp_name'], $tmpDir . $new_filename);

            echo $new_filename;
        }
        else
        {
            die(__('Error: Could not open the uploads folder! Please ensure the WP uploads folder is present and writable!'));
        }
        exit;
    }

}