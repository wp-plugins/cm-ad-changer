<?php
/*
  Plugin Name: CM Ad Changer Server
  Plugin URI: http://ad-changer.cminds.com/
  Description: Ad Changer Server. Manage, Track and Report Advertising Campaigns Across Sites
  Author: CreativeMindsSolutions
  Version: 1.0.0
*/

/**
 * CM Ad Changer
 *
 * @author CreativeMinds (http://ad-changer.cminds.com)
 * @copyright Copyright (c) 2013, CreativeMinds
 */


define('AC_PLUGIN_PATH',WP_PLUGIN_DIR . '/' . basename(dirname(__FILE__)));
define('AC_PLUGIN_URL',plugins_url('', __FILE__));
define('AC_PLUGIN_FILE',__FILE__);
require_once AC_PLUGIN_PATH . '/config.php';
require_once AC_PLUGIN_PATH . '/functions.php';
require_once AC_PLUGIN_PATH . '/classes/ac_data.php';
require_once AC_PLUGIN_PATH . '/classes/ac_requests.php';
require_once AC_PLUGIN_PATH . '/classes/ac_client.php';
require_once AC_PLUGIN_PATH . '/widget.php';

add_shortcode( 'cm_ad_changer', array('AC_Client','banner_output') );
add_action('wp_ajax_acc_trigger_click_event', array('AC_Client','trigger_click_event'));
register_activation_hook(__FILE__, 'ac_activate');
register_deactivation_hook(__FILE__, 'ac_deactivate');


add_action( 'admin_init', 'ac_init' );
function ac_init(){
	wp_register_style( 'adChangerStylesheet', plugins_url('assets/css/style.css', __FILE__) );
//	wp_register_style( 'pluploadStylesheet', plugins_url('assets/css/plupload/jquery.plupload.queue.css', __FILE__) );
	wp_register_style( 'datePickerUIStylesheet', plugins_url('assets/js/datepicker/smoothness.datepick.css', __FILE__) );
	wp_register_style( 'jqueryUIStylesheet', plugins_url('assets/css/jquery-ui/smoothness/jquery-ui-1.10.3.custom.css', __FILE__) );
	wp_register_style( 'speechBubblesStylesheet', plugins_url('assets/js/speechbubbles/speechbubbles.css', __FILE__) );
}

add_action( 'admin_menu', 'ac_menu' );
function ac_menu() {
	global $submenu;
	$settings_page = add_menu_page('Ad Changer ', 'Ad Changer', 'manage_options', 'ac_server', 'ac_load_page',plugin_dir_url( __FILE__ ).'/assets/images/icon.png',55);
	$campaigns_subpage = add_submenu_page( 'ac_server', 'Campaigns', 'Campaigns', 10, 'ac_server_campaigns', 'ac_load_page' );
	$about_subpage = add_submenu_page( 'ac_server', 'About', 'About', 10, 'ac_server_about', 'ac_load_page' );
	$pro_version_subpage = add_submenu_page( 'ac_server', 'Pro Version', 'Pro Version', 10, 'ac_server_pro_version', 'ac_load_page' );

	add_action( 'admin_print_styles-' . $settings_page, 'ac_admin_styles' );
	add_action( 'admin_print_styles-' . $campaigns_subpage, 'ac_admin_styles' );
	add_action( 'admin_print_styles-' . $about_subpage, 'ac_admin_styles' );
	add_action( 'admin_print_styles-' . $history_subpage, 'ac_admin_styles' );

	$submenu['ac_server'][0][0] = 'Settings';
}

function ac_admin_styles()
{
//	wp_enqueue_style( 'pluploadStylesheet' );
    wp_enqueue_style('adChangerStylesheet');
    wp_enqueue_style('datePickerStylesheet');
    wp_enqueue_style('datePickerUIStylesheet');
    wp_enqueue_style('jqueryUIStylesheet');

    if($_GET['page'] == 'ac_server_campaigns')
    {
        wp_enqueue_style('speechBubblesStylesheet');
        wp_enqueue_script('script-name', plugins_url('assets/js/scripts.js', __FILE__), array(), '1.0.0', true);
        wp_enqueue_script('plupload-full-js', plugins_url('assets/js/plupload/plupload.full.js', __FILE__), array(), '1.0.0', true);
        wp_enqueue_script('plupload-queue-js', plugins_url('assets/js/plupload/jquery.plupload.queue/jquery.plupload.queue.js', __FILE__), array(), '1.0.0', true);
        wp_enqueue_script('datepicker', plugins_url('assets/js/datepicker/jquery.datepick.js', __FILE__), array('jquery'), '1.0.0', true);
        wp_enqueue_script('speechBubbles', plugins_url('assets/js/speechbubbles/speechbubbles.js', __FILE__), array(), '1.0.0', true);
    }

	$int_version = (int)str_replace('.','',get_bloginfo('version'));
	if($int_version<100)
		$int_version *= 10; // will be 340 or 341 or 350 etc

	if($int_version>320){
		wp_enqueue_script( 'jqueryUIWPCore', includes_url().'js/jquery/ui/jquery.ui.core.min.js', array(), '1.0.0', true );
		wp_enqueue_script( 'jqueryUIWPWidget', includes_url().'js/jquery/ui/jquery.ui.widget.min.js', array(), '1.0.0', true );
		wp_enqueue_script( 'jqueryUIWPPosition', includes_url().'js/jquery/ui/jquery.ui.position.min.js', array(), '1.0.0', true );
		wp_enqueue_script( 'jqueryUIWPTabs', includes_url().'js/jquery/ui/jquery.ui.tabs.min.js', array(), '1.0.0', true );
		wp_enqueue_script( 'jqueryUIWPButton', includes_url().'js/jquery/ui/jquery.ui.button.min.js', array(), '1.0.0', true );
	}else{
		wp_enqueue_script( 'jqueryUIWPCore', includes_url().'js/jquery/ui.core.js', array(), '1.0.0', true );
		wp_enqueue_script( 'jqueryUIWPWidget', includes_url().'js/jquery/ui.widget.js', array(), '1.0.0', true );
		wp_enqueue_script( 'jqueryUIWPPosition', includes_url().'js/jquery/ui.position.js', array(), '1.0.0', true );

		wp_enqueue_script( 'jqueryUIWPTabs', includes_url().'js/jquery/ui.tabs.js', array(), '1.0.0', true );
		wp_enqueue_script( 'jqueryUIWPButton', includes_url().'js/jquery/ui.button.js', array(), '1.0.0', true );
	}

	if($int_version>=350){
		wp_enqueue_script( 'jqueryUIWPSpinner', includes_url().'js/jquery/ui/jquery.ui.spinner.min.js', array(), '1.0.0', true );
		wp_enqueue_script( 'jqueryUIWPTooltips', includes_url().'js/jquery/ui/jquery.ui.tooltip.min.js', array(), '1.0.0', true );
	}
     
     
	wp_enqueue_script('mouseWheel', plugins_url('assets/js/jquery-ui/jquery.mousewheel.js', __FILE__), array('jquery'), '1.0.0', true);

	if($int_version<350)
		wp_enqueue_script( 'jQueryMissingUI', plugins_url('assets/js/jquery-ui/missing_ui.js', __FILE__), array(), '1.0.0', true );	
}


/**
 * pages dispatcher
 * @param String   $ac_page  Page slug
 */
function ac_load_page($ac_page=null){
	global $label_descriptions;

	if(empty($ac_page))
		$ac_page = $_GET['page'];

	$plugin_data = get_plugin_data(AC_PLUGIN_FILE);
	switch($ac_page){

		case 'ac_server':
			if(!empty($_POST)){
				$data= AC_Data::ac_handle_settings_post($_POST);
				if(!empty($data)){
					$fields_data = $data['fields_data'];
					if(isset($data['errors'])&&!empty($data['errors']))
						$errors = $data['errors'];
				}else{
					$fields_data['acs_active']=$_POST['acs_active'];
					$fields_data['acs_max_campaigns_no']=$_POST['acs_max_campaigns_no'];
					$fields_data['acs_div_wrapper']=$_POST['acs_div_wrapper'];
					$fields_data['acs_class_name']=$_POST['acs_class_name'];
					$fields_data['acs_custom_css']=$_POST['acs_custom_css'];
					$fields_data['acc_campaign_id']=$_POST['acc_campaign_id'];
					$success = 'Settings were successfully stored!';
				}
			}else{
				$fields_data['acs_active'] = get_option('acs_active',null);
				$fields_data['acs_max_campaigns_no'] = get_option('acs_max_campaigns_no','10');
				$fields_data['acs_div_wrapper'] = get_option('acs_div_wrapper','0');
				$fields_data['acs_class_name'] = get_option('acs_class_name','');
				$fields_data['acs_custom_css'] = get_option('acs_custom_css','');
			}
			require_once AC_PLUGIN_PATH . '/views/settings.php';
			break;

		case 'ac_server_campaigns':

			global $wpdb;
			$data = array();
			$fields_data = array();
			$success = null;

			if(!empty($_POST)){
				$data= AC_Data::ac_handle_campaigns_post($_POST);
				if(!empty($data)){
					$fields_data = $data['fields_data'];
					if(isset($data['errors'])&&!empty($data['errors']))
						$errors = $data['errors'];
				}else{
					$fields_data['category_ids']=$_POST['category_ids'];
					$fields_data['category_title']=$_POST['category_title'];
					$success = 'Campaign was successfully stored!';
				}
			}else{
				if(isset($_GET['action'])){
					if(isset($_GET['campaign_id'])&&is_numeric($_GET['campaign_id'])){
						switch($_GET['action']){
							case 'edit':
								$fields_data=AC_Data::ac_get_campaign($_GET['campaign_id']);
								break;
							case 'delete':
								AC_Data::ac_remove_campaign($_GET['campaign_id']);
								$success = 'Campaign was removed from database!';
								break;
						}
					}
				}
			}

			$campaigns=AC_Data::ac_get_campaigns();
			require_once AC_PLUGIN_PATH . '/views/campaigns.php';
			break;

		case 'ac_server_about':
			require_once AC_PLUGIN_PATH . '/views/about.php';
			break;
		case 'ac_server_pro_version':
			require_once AC_PLUGIN_PATH . '/views/pro_version.php';
			break;
	}
}


/********************************/
/* Handling API Requests */
/********************************/

add_action('wp_loaded',array('AC_Requests','handle_api_requests'));
?>
