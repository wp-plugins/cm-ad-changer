<?php
/**
 * CM Ad Changer
 *
 * @author CreativeMinds (http://ad-changer.cminds.com)
 * @copyright Copyright (c) 2013, CreativeMinds
 */

/**
 * Plugin activation
 *
 */
function ac_activate()
{
    global $wpdb, $table_prefix; // have to use $table_prefix

    /*
      $wpdb->query('DROP TABLE IF EXISTS '.$table_prefix.CAMPAIGNS_TABLE);

      $wpdb->query('DROP TABLE IF EXISTS '.$table_prefix.CATEGORIES_TABLE);

      $wpdb->query('DROP TABLE IF EXISTS '.$table_prefix.CAMPAIGN_CATEGORIES_REL_TABLE);

      $wpdb->query('DROP TABLE IF EXISTS '.$table_prefix.HISTORY_TABLE);

      $wpdb->query('DROP TABLE IF EXISTS '.$table_prefix.IMAGES_TABLE);

      $wpdb->query('DROP TABLE IF EXISTS '.$table_prefix.PERIODS_TABLE);
     */

    $wpdb->query('CREATE TABLE IF NOT EXISTS `' . $table_prefix . CAMPAIGNS_TABLE . '` (
				  `campaign_id` int(11) NOT NULL AUTO_INCREMENT,
				  `title` varchar(100) NOT NULL,
				  `link` varchar(200) NOT NULL,
				  `selected_banner` int(11) NOT NULL,
				  `use_selected_banner` tinyint(1) NOT NULL DEFAULT \'1\',
				  `max_clicks` bigint(20) NOT NULL,
				  `max_impressions` bigint(20) NOT NULL,
				  `comment` text NOT NULL,
				  `status` tinyint(4) NOT NULL,
				  PRIMARY KEY (`campaign_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');

    $wpdb->query('CREATE TABLE IF NOT EXISTS `' . $table_prefix . IMAGES_TABLE . '` (
				  `image_id` int(11) NOT NULL AUTO_INCREMENT,
				  `campaign_id` int(11) NOT NULL,
				  `title` varchar(50) NOT NULL,
				  `title_tag` varchar(200) NOT NULL,
				  `alt_tag` varchar(200) NOT NULL,
				  `link` varchar(150) NOT NULL,
				  `weight` tinyint(4) NOT NULL,
				  `filename` varchar(50) NOT NULL,
				  PRIMARY KEY (`image_id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');

    $uploadDir = wp_upload_dir();
    $baseDir   = $uploadDir['basedir'] . '/' . AC_UPLOAD_PATH;
    $tmpDir    = $baseDir . AC_TMP_UPLOAD_PATH;
    if(!is_dir($tmpDir))
    {
        if(!wp_mkdir_p($tmpDir)) {
            echo 'Error: Your WP uploads folder is not writable! The plugin requires a writable uploads folder in order to work.';
            exit;
        }
    }
}

/**
 * Plugin deactivation
 */
function ac_deactivate()
{
    global $wpdb;
    /*
      $wpdb->query('DROP TABLE IF EXISTS '.CAMPAIGNS_TABLE);

      $wpdb->query('DROP TABLE IF EXISTS '.CATEGORIES_TABLE);

      $wpdb->query('DROP TABLE IF EXISTS '.CAMPAIGN_CATEGORIES_REL_TABLE);

      $wpdb->query('DROP TABLE IF EXISTS '.HISTORY_TABLE);

      $wpdb->query('DROP TABLE IF EXISTS '.IMAGES_TABLE);

      $wpdb->query('DROP TABLE IF EXISTS '.PERIODS_TABLE);

      // cleaning uploads folder
      if($handle = opendir(WP_CONTENT_DIR . '/uploads/'.AC_UPLOAD_PATH.AC_TMP_UPLOAD_PATH)){
      while (false !== ($entry = readdir($handle)))
      if(file_exists(WP_CONTENT_DIR . '/uploads/'.AC_UPLOAD_PATH.AC_TMP_UPLOAD_PATH.$entry)&&$entry!='.'&&$entry!='..')
      unlink(WP_CONTENT_DIR . '/uploads/'.AC_UPLOAD_PATH.AC_TMP_UPLOAD_PATH.$entry);

      rmdir(WP_CONTENT_DIR . '/uploads/'.AC_UPLOAD_PATH.AC_TMP_UPLOAD_PATH);
      }

      if($handle = opendir(WP_CONTENT_DIR . '/uploads/'.AC_UPLOAD_PATH)){
      while (false !== ($entry = readdir($handle)))
      if(file_exists(WP_CONTENT_DIR . '/uploads/'.AC_UPLOAD_PATH.$entry)&&$entry!='.'&&$entry!='..')
      unlink(WP_CONTENT_DIR . '/uploads/'.AC_UPLOAD_PATH.$entry);

      rmdir(WP_CONTENT_DIR . '/uploads/'.AC_UPLOAD_PATH);
      }
     */
}

/**
 * Top menu rendering
 */
function ac_top_menu()
{
    global $submenu;
    $current_slug = $_GET['page'];
    ?>
    <style type="text/css">
        .subsubsub li+li:before {content:'| ';}
    </style>
    <ul class="subsubsub">
        <?php foreach($submenu['ac_server'] as $menu): ?>
            <li><a href="admin.php?page=<?php echo $menu['2']; ?>" <?php echo ($_GET['page'] == $menu[2]) ? 'class="current"' : ''; ?>><?php echo $menu[0]; ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

add_action("in_plugin_update_message-" . basename(__FILE__) . "/" . basename(dirname(__FILE__)), 'red_warnOnUpgrade');

function red_warnOnUpgrade()
{
    ?>
    <div style="margin-top: 1em"><span style="color: red; font-size: larger">STOP!</span> Do <em>not</em> click &quot;update automatically&quot; as you will be <em>downgraded</em> to the free version of CM Ad Changer. Instead, download the Pro update directly from <a href="http://ad-changer.cminds.com//">http://ad-changer.cminds.com/</a>.</div>
    <div style="font-size: smaller">CM Ad Changer does not use WordPress's standard update mechanism. We apologize for the inconvenience!</div>
    <?php
}

/* * ******************** */
/* AJAX */
/* * ******************** */

add_action('wp_ajax_ac_upload_image', 'ac_upload_image');

/**
 * Uploading the images to tmp folder
 */
function ac_upload_image()
{
    $uploadedfile     = $_FILES['file'];
    $upload_overrides = array('test_form' => false);

    $validate = wp_check_filetype_and_ext($uploadedfile['tmp_name'], $uploadedfile['name'], array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'));

    if(!$validate['ext'])
    {
        die(__('Error: Invalid file extension!'));
    }

    if((int) $uploadedfile['size'] > 2000000)
    {
        die(__('Error: File too big!'));
    }

    $uploadDir = wp_upload_dir();
    $baseDir   = $uploadDir['basedir'] . '/' . AC_UPLOAD_PATH;
    $tmpDir    = $baseDir . AC_TMP_UPLOAD_PATH;

    if(($handle = opendir($baseDir)) !== FALSE)
    {
        $existing_files = array();
        while(false !== ($entry          = readdir($handle)))
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

add_action('wp_ajax_acs_get_month_report', 'acs_get_month_report');

/**
 * Loaging page with month report
 */
function acs_get_month_report()
{
    ac_load_page('ac_server_month_report');
    exit;
}

add_action('wp_ajax_acs_get_clients_logs', 'acs_get_clients_logs');

/**
 * Loading page with clients logs
 */
function acs_get_clients_logs()
{
    ac_load_page('ac_server_clients_logs');
    exit;
}

add_action('wp_ajax_acs_get_history', 'acs_get_history');

/**
 * Getting paged history
 */
function acs_get_history()
{
    ac_load_page('ac_server_history');
    exit;
}

add_action('wp_ajax_acs_export_history', 'acs_export_history');

/**
 * Exporting whole history
 */
function acs_export_history()
{
    header("Content-Type: text/csv");
    header('Content-Disposition: attachment; filename="file.csv"');
    $history = AC_Data::get_history(0);

    $out_history    = array();
    $out_history[0] = array('Event', 'Campaign Name', 'Banner Name', 'Referer URL', 'Date');
    foreach($history as $index => $rec)
    {
        $out_history[$index + 1]['event_type']    = $rec->event_type;
        $out_history[$index + 1]['campaign_name'] = $rec->campaign_title;
        $out_history[$index + 1]['banner_name']   = $rec->title;
        $out_history[$index + 1]['client_domain'] = $rec->referer_url;
        $out_history[$index + 1]['regdate']       = $rec->regdate;
    }
    ac_outputCSV($out_history);
    exit;
}

add_action('wp_ajax_acs_empty_history', 'acs_empty_history');

/**
 * Emptying history
 */
function acs_empty_history()
{
    AC_Data::empty_history();
    ac_load_page('ac_server_history');
    exit;
}

/* * ******************** */
/* EVENTS */
/* * ******************** */

/**
 * Events trigger
 * @return Boolean
 * @param String   $event_name  Event name
 * @param Array   $args  Arguments
 */
function ac_trigger_event($event_name, $args)
{
    global $wpdb;

    switch($event_name)
    {
        case 'new_impression':
            if(!isset($args['campaign_id']) || !is_numeric($args['campaign_id']) || !isset($args['banner_id']) || !is_numeric($args['banner_id']) || !isset($args['http_referer'])) return false;

            $wpdb->query($wpdb->prepare('INSERT INTO ' . HISTORY_TABLE . ' SET `event_type`="impression", `campaign_id`=%d, `banner_id`=%d, `referer_url`=%s', $args['campaign_id'], $args['banner_id'], $args['http_referer']));
            return true;
        case 'new_click':
            if(!isset($args['campaign_id']) || !is_numeric($args['campaign_id']) || !isset($args['banner_id']) || !is_numeric($args['banner_id']) || !isset($args['http_referer'])) return false;

            $wpdb->query($wpdb->prepare('INSERT INTO ' . HISTORY_TABLE . ' SET `event_type`="click", `campaign_id`=%d, `banner_id`=%d, `referer_url`=%s', $args['campaign_id'], $args['banner_id'], $args['http_referer']));
            return true;
    }
    return false;
}

/**
 * Pagination rendering
 * @return String
 * @param Int   $current_page  Page number, min 1
 */
function ac_pagination($current_page = 1)
{
    global $wpdb;

    $radius      = 3;
//	$base_url = get_bloginfo('wpurl').'/wp-admin/admin.php?page=ac_server_history'; // not ajax
    $base_url    = get_bloginfo('wpurl') . '/wp-admin/admin-ajax.php?action=acs_get_history'; // ajax
    $total       = $wpdb->get_var('SELECT count(*) FROM ' . HISTORY_TABLE);
    $total_pages = ceil($total / AC_HISTORY_PER_PAGE_LIMIT);
    if($total_pages == 1) return '';
    $html        = '<div class="asc_pagination">';

    // Before current page
    if($current_page > 1)
    {
        $html .= '<a href="' . $base_url . '&acs_page=1">First</a>';
        $html .= '<a href="' . $base_url . '&acs_page=' . ($current_page - 1) . '">Previous</a>';
        for($i = ($current_page <= $radius ? 1 : $current_page - $radius); $i < $current_page; $i++) $html .= '<a href="' . $base_url . '&acs_page=' . $i . '">' . $i . '</a>';
    }

    // Current page
    $html .= '<span class="acs_current_page">' . $current_page . '</span>';

    // After current page
    if($current_page < $total_pages)
    {
        for($i = $current_page + 1; $i <= ($total_pages - $current_page < $radius ? $total_pages : $current_page + $radius); $i++) $html .= '<a href="' . $base_url . '&acs_page=' . $i . '">' . $i . '</a>';

        $html .= '<a href="' . $base_url . '&acs_page=' . ($current_page + 1) . '">Next</a>';
        $html .= '<a href="' . $base_url . '&acs_page=' . $total_pages . '">Last</a>';
    }
    $html .= '</div>';

    return $html;
}

/**
 * Random weighted key finder
 * @return Int
 * @param Array   $weights  Array of positive integers
 */
function ac_get_random_banner_index($weights = array())
{

    asort($weights);

    $weights_sum = array_sum($weights);

    if($weights_sum == 0) return array_rand($weights, 1);

    $rand_num = rand(1, $weights_sum);

    $diapasons        = array();
    $weights_sum      = 0;
    $prev_weights_sum = 0;
    $res              = array();
    foreach($weights as $cur_key => $weight)
    {
        $weights_sum += $weight;
        $diapasons[$cur_key] = array($prev_weights_sum + 1, $weights_sum);
        $prev_weights_sum    = $weights_sum;
        if($rand_num <= $diapasons[$cur_key][1] && $rand_num >= $diapasons[$cur_key][0]) $res[]               = $cur_key;
    }

    $res_rand_key = array_rand($res, 1);

    return $res[$res_rand_key];
}

/**
 * Normalizing weights, till sum = 100
 * @return Array
 * @param Array   $weights  Array of positive integers
 */
function ac_normalize_weights($weights)
{

    $sum = array_sum($weights);
    if($sum == 0) return $weights;

    $rates           = array();
    foreach($weights as $index => $weight) $weights[$index] = round($weight / $sum * 100);

    $new_sum = array_sum($weights);

    $rand_key = array_rand($weights, 1);

    if($new_sum != 100) $weights[$rand_key] += 100 - $new_sum;
    return $weights;
}

/**
 * Converter of array to html list
 * @return String
 * @param Array   $data  Array of strings
 * @param String  $title Title of list
 * @param String   $class  CSS class of list
 */
function ac_format_list($data, $title = '', $class = '')
{
    $ret_html = '';
    if(!empty($title)) $ret_html = '<strong>' . $title . '</strong><br/>';
    $ret_html .= '<ul' . (!empty($class) ? 'class="' . $class . '"' : '') . ' >';
    foreach($data as $field => $value) $ret_html .= '<li>' . $field . '= ' . (!empty($value) ? $value : '- empty -') . '</li>';
    $ret_html .= '</ul>';
    return $ret_html;
}

// function from http://us1.php.net/manual/ru/function.fputcsv.php
/**
 * Outputs array to csv file
 * @param Array   $data  Array
 */
function ac_outputCSV($data)
{

    $outstream = fopen("php://output", 'w');

    function __outputCSV(&$vals, $key, $filehandler)
    {
        fputcsv($filehandler, $vals, ';', '"');
    }

    array_walk($data, '__outputCSV', $outstream);

    fclose($outstream);
}
?>
