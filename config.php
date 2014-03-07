<?php
/**
 * CM Ad Changer
 *
 * @author CreativeMinds (http://ad-changer.cminds.com)
 * @copyright Copyright (c) 2013, CreativeMinds
 */
define('CMAC_DEBUG', '1');
define('AC_UPLOAD_PATH', 'ac_uploads/');
define('AC_TMP_UPLOAD_PATH', 'tmp/');
define('CAMPAIGNS_TABLE', $table_prefix . 'cm_campaigns'); // $table_prifix comes from Wordpress core
define('IMAGES_TABLE', $table_prefix . 'cm_campaign_images');

define('CAMPAIGNS_LIMIT', '100');
define('BANNERS_PER_CAMPAIGN_LIMIT', '50');
define('AC_HISTORY_PER_PAGE_LIMIT', '50');

define('AC_API_ERROR_1', 'Client host unknown');
define('AC_API_ERROR_2', 'Campaign ID not set');
define('AC_API_ERROR_3', 'Campaign not found');
define('AC_API_ERROR_4', 'Client host is not registered');
define('AC_API_ERROR_5', 'Campaign is inactive');
define('AC_API_ERROR_6', 'There is no image to display');
define('AC_API_ERROR_7', 'Unknown error');
define('AC_API_ERROR_8', 'Unknown action');
define('AC_API_ERROR_9', 'Server is inactive');
define('AC_API_ERROR_10', 'Maximum impressions achieved');
define('AC_API_ERROR_11', 'Maximum clicks achieved');
define('AC_API_ERROR_12', 'Banner ID is not set');
define('AC_API_ERROR_13', 'Campaign is not active today');

$label_descriptions = array(// SETTINGS
    'acs_active'           => 'Server status, if set than server will accept connections from Ad Changer clients',
    'acs_max_campaigns_no' => 'Maximal Number of Campaigns in server',
    'acs_div_wrapper'      => 'Div Wrapper (server side) - Will add div around banner on server side',
    'acs_class_name'       => 'Class Name - Will set the class name for div',
    'acs_custom_css'       => 'Custom CSS will be injected into body before banner is shown and only on post or pages where campaign is active. Example: #featured.has-badge {margin-bottom: 85px;}',
    // CAMPAIGNS
    'title'                => 'Campaign Name. For internal use only',
    'campaign_id'          => 'Campaign ID. When referring to campaign in shortcode only use campaign id',
    'comment'              => 'Campaign Notes - This is only for internal use of campaign manager',
    'link'                 => 'Campaign Target URL - Target URL specified in banner will override this',
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
