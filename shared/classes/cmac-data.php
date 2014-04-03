<?php
/**
 * CM Ad Changer
 *
 * @author CreativeMinds (http://ad-changer.cminds.com)
 * @copyright Copyright (c) 2013, CreativeMinds
 */
CMAC_Data::instance();

class CMAC_Data
{
    public static $calledClassName;
    protected static $instance = NULL;
    protected static $campaignsTable = NULL;
    protected static $imagesTable = NULL;
    protected static $historyTable = NULL;

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
        global $wpdb;

        if( empty(self::$calledClassName) )
        {
            self::$calledClassName = __CLASS__;
        }

        self::$campaignsTable = $wpdb->prefix . CMAC_CAMPAIGNS_TABLE;
        self::$imagesTable = $wpdb->prefix . CMAC_IMAGES_TABLE;
        self::$historyTable = $wpdb->prefix . CMAC_HISTORY_TABLE;
    }

    /**
     * Performs campaign storage
     * @return Array
     * @param Array   $data  Array of fields
     */
    public static function cmac_handle_campaigns_post($data)
    {
        global $wpdb;
        $errors = array();

        /*
         * VALIDATIONS START
         */
        if( empty($data) )
        {
            return array('errors' => array('No data entered'), 'fields_data' => $data);
        }

        if( !isset($data['banner_weight']) || !is_array($data['banner_weight']) )
        {
            $data['banner_weight'] = array();
        }
        $campaigns = $wpdb->get_results('SELECT * FROM ' . self::$campaignsTable);

        if( empty($data['title']) )
        {
            $errors[] = 'Campaign Name field is empty';
        }

        if( strlen($data['comment']) > 500 )
        {
            $errors[] = 'Note is too long';
        }

        if( isset($data['campaign_id']) && !is_numeric($data['campaign_id']) )
        {
            $errors[] = 'Unknown campaign';
        }

        if( !isset($data['banner_display_method']) )
        {
            $errors[] = 'Please select "Banner display method"';
        }

        if( isset($data['banner_display_method']) && $data['banner_display_method'] == 'selected' && !empty($data['banner_filename']) && empty($data['selected_banner']) )
        {
            $errors[] = 'Please select a banner';
        }

        $banner_weight_sum = 0;
        $banners_natural = true;
        foreach($data['banner_weight'] as $banner_weight)
        {
            if( !is_numeric($banner_weight) || ((int) $banner_weight != (float) $banner_weight) || $banner_weight < 0 )
            {
                $errors[] = 'Please enter numeric positive Banner Weights';
                $banners_natural = false;
                break;
            }

            $banner_weight_sum+=(int) $banner_weight;
        }

        if( $banners_natural )
        {
            if( $banner_weight_sum > 1000 )
            {
                $errors[] = 'Banner Weight sum is too big';
            }
        }

        if( !empty($errors) )
        {
            return array('errors' => $errors, 'fields_data' => $data);
        }

        // VALIDATIONS END
        // 1. Inserting the campaign record
        if( !isset($data['campaign_id']) )
        {
            $wpdb->query($wpdb->prepare('INSERT INTO ' . self::$campaignsTable . ' SET `title`=%s, `link`=%s, `banner_display_method`=%s, `max_impressions`=%d, `max_clicks`=%d, `comment`=%s, `status`=%d', $data['title'], $data['link'], $data['banner_display_method'], $data['max_impressions'], $data['max_clicks'], $data['comment'], isset($data['status']) ? 1 : 0));

            $new_campaign_id = $wpdb->insert_id;
        }
        else
        {
            $wpdb->query($wpdb->prepare('UPDATE ' . self::$campaignsTable . ' SET `title`=%s, `link`=%s, `banner_display_method`=%s, `max_impressions`=%d, `max_clicks`=%d, `comment`=%s, `status`=%d WHERE `campaign_id`="' . $data['campaign_id'] . '"', $data['title'], $data['link'], $data['banner_display_method'], $data['max_impressions'], $data['max_clicks'], $data['comment'], isset($data['status']) ? 1 : 0));
            $new_campaign_id = $data['campaign_id'];
        }

        // 2. Inserting banner images

        $new_filenames = array();
        if( isset($new_campaign_id) )
        {
            if( !isset($data['banner_filename']) || !is_array($data['banner_filename']) )
            {
                $data['banner_filename'] = array();
            }
            $existing_filenames = $wpdb->get_col('SELECT filename FROM ' . self::$imagesTable . ' WHERE `campaign_id`="' . $new_campaign_id . '"');

            $deleted_filenames = array();

            foreach($existing_filenames as $existing_filename)
            {
                if( !in_array($existing_filename, $data['banner_filename']) )
                {
                    $deleted_filenames[] = $existing_filename;
                }
            }

            foreach($data['banner_filename'] as $data_filename)
            {
                if( !in_array($data_filename, $existing_filenames) )
                {
                    $new_filenames[] = $data_filename;
                }
            }

            // cleaning images folder
            if( !empty($deleted_filenames) )
            {
                foreach($deleted_filenames as $deleted_filename)
                {
                    if( !in_array($deleted_filename, $data['banner_filename']) )
                    {
                        if( file_exists(WP_CONTENT_DIR . '/uploads/' . CMAC_UPLOAD_PATH . $deleted_filename) ) unlink(WP_CONTENT_DIR . '/uploads/' . CMAC_UPLOAD_PATH . $deleted_filename);
                    }

                    $wpdb->query('DELETE FROM ' . self::$imagesTable . ' WHERE `campaign_id`       ="' . $new_campaign_id . '" AND `filename`="' . $deleted_filename . '"');
                }
            }
        }

        $selected_banner_id = '0';

        if( isset($data['banner_title']) && !empty($data['banner_title']) )
        {
            $data['banner_weight'] = self::cmac_normalize_weights($data['banner_weight']);
            foreach($data['banner_title'] as $banner_index => $banner_title)
            {
                if( in_array($data['banner_filename'][$banner_index], $new_filenames) )
                {
                    @$image_file_content = file_get_contents(WP_CONTENT_DIR . '/uploads/' . CMAC_UPLOAD_PATH . CMAC_TMP_UPLOAD_PATH . $data['banner_filename'][$banner_index]);

                    if( $image_file_content )
                    {
                        $f = fopen(WP_CONTENT_DIR . '/uploads/' . CMAC_UPLOAD_PATH . $data['banner_filename'][$banner_index], 'w+');
                        fwrite($f, $image_file_content);
                        fclose($f);
                    }

                    $wpdb->query($wpdb->prepare('INSERT INTO ' . self::$imagesTable . ' SET `campaign_id`=%d, `title`=%s, `title_tag`=%s, `alt_tag`=%s, `link`=%s, `weight`=%d, `filename`=%s', $new_campaign_id, $banner_title, $data['banner_title_tag'][$banner_index], $data['banner_alt_tag'][$banner_index], $data['banner_link'][$banner_index], $data['banner_weight'][$banner_index], $data['banner_filename'][$banner_index]));

                    if( $data['banner_filename'][$banner_index] == $data['selected_banner'] )
                    {
                        $selected_banner_id = $wpdb->insert_id;
                    }
                }
                else
                {
                    $wpdb->query($wpdb->prepare('UPDATE ' . self::$imagesTable . ' SET `title`=%s, `title_tag`=%s, `alt_tag`=%s, `link`=%s, `weight`=%d WHERE `filename`=%s', $banner_title, $data['banner_title_tag'][$banner_index], $data['banner_alt_tag'][$banner_index], $data['banner_link'][$banner_index], $data['banner_weight'][$banner_index], $data['banner_filename'][$banner_index]));
                    if( $data['banner_filename'][$banner_index] == $data['selected_banner'] )
                    {
                        $selected_banner_id = $wpdb->get_var('SELECT `image_id` FROM ' . self::$imagesTable . ' WHERE `filename`="' . $data['banner_filename'][$banner_index] . '"');
                    }
                }
            }
        }
        /*
         * cleaning tmp folder
         */
        if( $handle = opendir(WP_CONTENT_DIR . '/uploads/' . CMAC_UPLOAD_PATH . CMAC_TMP_UPLOAD_PATH) )
        {
            while(false !== ($entry = readdir($handle)))
            {
                if( file_exists(WP_CONTENT_DIR . '/uploads/' . CMAC_UPLOAD_PATH . CMAC_TMP_UPLOAD_PATH . $entry) && $entry != '.' && $entry != '..' )
                {
                    unlink(WP_CONTENT_DIR . '/uploads/' . CMAC_UPLOAD_PATH . CMAC_TMP_UPLOAD_PATH . $entry);
                }
            }
        }

        // updating campaigns : setting selected banner
        $wpdb->query('UPDATE ' . self::$campaignsTable . ' SET `selected_banner`="' . ($selected_banner_id ? $selected_banner_id : '0') . '" WHERE campaign_id="' . $new_campaign_id . '"');

        if( !empty($wpdb->last_error) )
        {
            return array('errors' => array('Database error'), 'fields_data' => $data);
        }

        if( empty($errors) )
        {
            return array();
        }
    }

    /**
     * Performs settings storage
     * @return Array
     * @param Array   $data  Array of fields
     */
    public static function cmac_handle_settings_post($data)
    {
        $errors = array();

        $campaigns = self::cmac_get_campaigns();

        if( !empty($errors) )
        {
            return array('errors' => $errors, 'data' => $data);
        }

        if( isset($_POST['acs_active']) )
        {
            update_option('acs_active', 1);
        }
        else
        {
            update_option('acs_active', 0);
        }

        if( isset($_POST['acc_campaign_id']) )
        {
            if( get_option('acc_campaign_id') !== false )
            {
                update_option('acc_campaign_id', $_POST['acc_campaign_id']);
            }
            else
            {
                add_option('acc_campaign_id', '');
            }
        }

        if( isset($_POST['acs_custom_css']) )
        {
            if( get_option('acs_custom_css') !== false )
            {
                update_option('acs_custom_css', $_POST['acs_custom_css']);
            }
            else
            {
                add_option('acs_custom_css', $_POST['acs_custom_css']);
            }
        }
        else
        {
            if( get_option('acs_custom_css') !== false )
            {
                update_option('acs_custom_css', '');
            }
            else
            {
                add_option('acs_custom_css', '');
            }
        }
        return array();
    }

    /**
     * Gets list of all campaigns
     * @return Array
     */
    public static function cmac_get_campaigns()
    {
        global $wpdb;

        $sql = 'SELECT c.*, count(ci.image_id) as banners_cnt '
                . 'FROM ' . self::$campaignsTable . ' as c '
                . 'LEFT JOIN ' . self::$imagesTable . ' as ci ON ci.campaign_id=c.campaign_id
                    GROUP BY c.campaign_id';

        $campaigns = $wpdb->get_results($sql);

        foreach($campaigns as $campaign_index => $campaign)
        {
            $campaigns[$campaign_index]->impressions_cnt = self::cmac_get_impressions_cnt($campaign->campaign_id);
            $campaigns[$campaign_index]->clicks_cnt = self::cmac_get_clicks_cnt($campaign->campaign_id);
        }
        return $campaigns;
    }

    /**
     * Gets single campaign
     * @return Array
     * @param Int   $campaign_id  Campaign ID
     */
    public static function cmac_get_campaign($campaign_id)
    {
        global $wpdb;

        $campaign = $wpdb->get_row('SELECT c.* FROM ' . self::$campaignsTable . ' c WHERE c.campaign_id="' . $campaign_id . '"', ARRAY_A);
        $images = $wpdb->get_results('SELECT ci.* FROM ' . self::$imagesTable . ' ci WHERE ci.campaign_id="' . $campaign_id . '"', ARRAY_A);

        if( $images )
        {
            foreach($images as $image)
            {
                $image['banner_clicks_cnt'] = self::cmac_get_banner_clicks_cnt($image['image_id']);
                $image['banner_impressions_cnt'] = self::cmac_get_banner_impressions_cnt($image['image_id']);
                $campaign['banners'][] = $image;

                if( $image['image_id'] == $campaign['selected_banner'] )
                {
                    $campaign['selected_banner_file'] = $image['filename'];
                    $campaign['selected_banner_title_tag'] = $image['title_tag'];
                    $campaign['selected_banner_alt_tag'] = $image['alt_tag'];
                    $campaign['selected_banner_link'] = $image['link'];
                    $campaign['selected_banner_id'] = $image['image_id'];
                }
            }
        }

        return $campaign;
    }

    /**
     * Gets impressions count for a campaign
     * @return Int
     * @param Int   $campaign_id  Campaign ID
     */
    public static function cmac_get_impressions_cnt($campaign_id)
    {
        global $wpdb;
        $impressions_cnt = $wpdb->get_var('SELECT count(*) FROM ' . self::$historyTable . ' WHERE `event_type`="impression" AND `campaign_id`="' . $campaign_id . '"');
        return $impressions_cnt;
    }

    /**
     * Gets clicks count for a campaign
     * @return Int
     * @param Int   $campaign_id  Campaign ID
     */
    public static function cmac_get_banner_clicks_cnt($banner_id)
    {
        global $wpdb;
        $clicks_cnt = $wpdb->get_var('SELECT count(*) FROM ' . self::$historyTable . ' WHERE `event_type`="click" AND `banner_id`="' . $banner_id . '"');
        return $clicks_cnt;
    }

    /**
     * Gets impressions count for a banner
     * @return Int
     * @param Int   $banner_id  Banner ID
     */
    public static function cmac_get_banner_impressions_cnt($banner_id)
    {
        global $wpdb;
        $impressions_cnt = $wpdb->get_var('SELECT count(*) FROM ' . self::$historyTable . ' WHERE `event_type`="impression" AND `banner_id`="' . $banner_id . '"');
        return $impressions_cnt;
    }

    /**
     * Gets clicks count for a banner
     * @return Int
     * @param Int   $banner_id  Banner ID
     */
    public static function cmac_get_clicks_cnt($campaign_id)
    {
        global $wpdb;
        $clicks_cnt = $wpdb->get_var('SELECT count(*) FROM ' . self::$historyTable . ' WHERE `event_type`="click" AND `campaign_id`="' . $campaign_id . '"');
        return $clicks_cnt;
    }

    /**
     * Removes campaign and all related data
     * @param Int   $campaign_id  Campaign ID
     */
    public static function cmac_remove_campaign($campaign_id)
    {
        global $wpdb;

        $images = $wpdb->get_col('SELECT filename FROM ' . self::$imagesTable . ' WHERE `campaign_id`="' . $campaign_id . '"');

        foreach($images as $image)
        {
            if( file_exists(WP_CONTENT_DIR . '/uploads/' . CMAC_UPLOAD_PATH . $image) )
            {
                unlink(WP_CONTENT_DIR . '/uploads/' . CMAC_UPLOAD_PATH . $image);
            }
        }

        $wpdb->query('DELETE FROM ' . self::$campaignsTable . ' WHERE `campaign_id`="' . $campaign_id . '"');
        $wpdb->query('DELETE FROM ' . self::$imagesTable . ' WHERE `campaign_id`="' . $campaign_id . '"');
    }

    /**
     * Gets category
     * @return Array
     * @param Int   $category_id  Category ID
     */
    public static function cmac_get_category($category_id)
    {
        global $wpdb;
        return $wpdb->get_row('SELECT * FROM ' . CATEGORIES_TABLE . ' WHERE `category_id`="' . $category_id . '"');
    }

    /**
     * Gets banner
     * @return Array
     */
    public static function cmac_get_banner($params = array())
    {
        CMAdChangerShared::cmac_log(self::$calledClassName . '::cmac_get_banner()');

        if( get_option('acs_active') !== '1' )
        {
            return array('error' => CMAC_API_ERROR_9);
        }

        if( empty($params['campaign_id']) || !is_numeric($params['campaign_id']) )
        {
            return array('error' => CMAC_API_ERROR_2);
        }

        $campaign = self::cmac_get_campaign($params['campaign_id']);
        if( empty($campaign) )
        {
            return array('error' => CMAC_API_ERROR_3);
        }

        if( (int) $campaign['status'] == 0 )
        {
            return array('error' => AC_API_ERROR_5);
        }

        if( isset($campaign['use_selected_banner']) && empty($campaign['selected_banner_id']) )
        {
            return array('error' => AC_API_ERROR_6);
        }

        if( CMAC_Data::cmac_get_impressions_cnt($campaign['campaign_id']) >= $campaign['max_impressions'] && (int) $campaign['max_impressions'] > 0 )
        {
            return array('error' => AC_API_ERROR_10);
        }

        if( CMAC_Data::cmac_get_clicks_cnt($campaign['campaign_id']) >= $campaign['max_clicks'] && (int) $campaign['max_clicks'] > 0 )
        {
            return array('error' => AC_API_ERROR_11);
        }

        $selectedBannerInfo = self::cmac_get_selected_banner_info($campaign);

        $ret_array = $selectedBannerInfo;
        $ret_array['image'] = get_bloginfo('wpurl') . '/wp-content/uploads/' . CMAC_UPLOAD_PATH . $selectedBannerInfo['filename'];

        if( !empty($selectedBannerInfo['link']) )
        {
            $ret_array['banner_link'] = $selectedBannerInfo['link'];
        }
        elseif( !empty($campaign['link']) )
        {
            $ret_array['banner_link'] = $campaign['link'];
        }

        CMAdChangerShared::cmac_log('Returning response from ' . self::$calledClassName . ':cmac_get_banner()');

        return $ret_array;
    }

    /**
     * Gets the information array about the banner (according to display method)
     * @param type $campaign
     * @return type
     */
    public static function cmac_get_selected_banner_info($campaign)
    {
        if( $campaign['banner_display_method'] == 'selected' )
        {
            $bannerInfo = self::cmac_get_banner_info($campaign, $campaign['selected_banner_id']);
            return $bannerInfo;
        }

        if( $campaign['banner_display_method'] == 'random' )
        {
            $random_banner_index = self::cmac_get_random_banner_index($campaign);
            $bannerInfo = self::cmac_get_banner_info($campaign, $random_banner_index);
            return $bannerInfo;
        }

        $bannerInfo = apply_filters('cmac_additional_display_method', $campaign);
        return $bannerInfo;
    }

    /**
     * Gets the information about specific banner
     * @param type $campaign
     * @param type $banner_id
     * @return type
     */
    public static function cmac_get_banner_info($campaign, $banner_id)
    {
        if( $campaign['banners'] )
        {
            foreach($campaign['banners'] as $banner)
            {
                if( $banner['image_id'] == $banner_id || !$banner_id )
                {
                    return $banner;
                }
            }
        }

        return array();
    }

    /**
     * Normalizing weights, till sum = 100
     * @return Array
     * @param Array   $weights  Array of positive integers
     */
    public static function cmac_normalize_weights($weights)
    {
        $sum = array_sum($weights);
        if( $sum == 0 )
        {
            return $weights;
        }

        foreach($weights as $index => $weight)
        {
            $weights[$index] = round($weight / $sum * 100);
        }

        $new_sum = array_sum($weights);
        $rand_key = array_rand($weights, 1);

        if( $new_sum != 100 )
        {
            $weights[$rand_key] += 100 - $new_sum;
        }
        return $weights;
    }

    /**
     * Random weighted key finder
     * @return Int
     * @param Array   $weights  Array of positive integers
     */
    public static function cmac_get_random_banner_index($campaign)
    {
        if( !empty($campaign) && !empty($campaign['banners']) )
        {
            foreach($campaign['banners'] as $banner)
            {
                $weights[$banner['image_id']] = $banner['weight'];
            }
        }

        asort($weights);

        if( array_sum($weights) == 0 )
        {
            return array_rand($weights, 1);
        }

        $rand_num = rand(1, array_sum($weights));

        $diapasons = array();
        $weights_sum = 0;
        $prev_weights_sum = 0;
        $res = array();
        foreach($weights as $cur_key => $weight)
        {
            $weights_sum += $weight;
            $diapasons[$cur_key] = array($prev_weights_sum + 1, $weights_sum);
            $prev_weights_sum = $weights_sum;
            if( $rand_num <= $diapasons[$cur_key][1] && $rand_num >= $diapasons[$cur_key][0] )
            {
                $res[] = $cur_key;
            }
        }

        $res_rand_key = array_rand($res, 1);
        return $res[$res_rand_key];
    }

    /**
     * Save the event information
     * @global type $wpdb
     * @return boolean
     */
    public static function cmac_event_save($args)
    {
        global $wpdb;

        $event_name = isset($args['event']) ? $args['event'] : 'click';

        CMAdChangerShared::cmac_log('Triggering ' . $event_name . ' event');

        if( !isset($args['campaign_id']) || !is_numeric($args['campaign_id']) )
        {
            return array('error' => 'Missing "campaign_id"');
        }

        if( !isset($args['banner_id']) || !is_numeric($args['banner_id']) )
        {
            return array('error' => 'Missing "banner_id"');
        }

        if( !isset($args['http_referer']) )
        {
            return array('error' => 'Missing "http_referer');
        }

        $country_name = '';

        switch($event_name)
        {
            default:
            case 'click':
                $wpdb->query($wpdb->prepare('INSERT INTO ' . self::$historyTable . ' SET event_type="click", campaign_id=%d, banner_id=%d, referer_url=%s, webpage_url=%s, remote_ip=%s, remote_country=%s, campaign_type=%s', $args['campaign_id'], $args['banner_id'], $args['http_referer'], $args['webpage_url'], $args['remote_ip'], $country_name, $args['campaign_type']));
                return true;
            case 'impression':
                $wpdb->query($wpdb->prepare('INSERT INTO ' . self::$historyTable . ' SET event_type="impression", campaign_id=%d, banner_id=%d, referer_url=%s, webpage_url=%s, remote_ip=%s, remote_country=%s, campaign_type=%s', $args['campaign_id'], $args['banner_id'], $args['http_referer'], $args['webpage_url'], $args['remote_ip'], $country_name, $args['campaign_type']));
                return true;
        }
        return false;
    }

}