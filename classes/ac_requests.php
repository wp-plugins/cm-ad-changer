<?php
/**
 * CM Ad Changer 
 *
 * @author CreativeMinds (http://ad-changer.cminds.com)
 * @copyright Copyright (c) 2013, CreativeMinds
 */

class AC_Requests{

	/**
	 * Accepts API requests
	 */
	public function handle_api_requests(){ // call by wp_loaded action

		if(isset($_GET['acs_action'])){
			switch($_GET['acs_action']){
				case "get_banner":
					self::get_banner();
					break;
				case "ping_server":
					self::ping_server();
					break;
				case 'trigger_click_event':
					self::trigger_click_event();
				default:
					return self::show_error(AC_API_ERROR_8);
					return;
			}
			exit;
		}
	}

	/**
	 * Gets banner
	 * @return Array
	 */
	public function get_banner($http_referer = null, $campaign_id = null){
		cmac_log("AC_Request::get_banner()");
		if(!$http_referer&&isset($_SERVER['HTTP_REFERER']))
			$http_referer = $_SERVER['HTTP_REFERER'];
			
		if(!$campaign_id&&isset($_GET['campaign_id']))
			$campaign_id = $_GET['campaign_id'];
			
		if(!isset($http_referer)||empty($http_referer))
			return self::show_error(AC_API_ERROR_1);

		if(get_option( 'acs_active' ) !== '1')
			return self::show_error(AC_API_ERROR_9);		
		if(!isset($campaign_id)||empty($campaign_id)||!is_numeric($campaign_id))
			return self::show_error(AC_API_ERROR_2);
		
		$campaign = AC_Data::ac_get_campaign($campaign_id);

		// Checking if the client site belogns to this campaign

		if(strpos($http_referer,get_bloginfo('wpurl'))!==false||empty($campaign['categories'])) // the request is from server side? Or no domain limitations set?
			$campaign_from_right_category = true;
		else{ // check if request comes from registered domain
			$campaign_from_right_category = false;
			foreach($campaign['categories'] as $category_id){
				$category = AC_Data::ac_get_category($category_id);
				if(strpos($http_referer,$category->category_title)!==false){
					$campaign_from_right_category = true;
					break;
				}
			}
	
			if(!$campaign_from_right_category)
				return self::show_error(AC_API_ERROR_4);
		}
		
		if((int)$campaign['status']==0)
			return self::show_error(AC_API_ERROR_5);
		
		// Making the return array
	
		unset($campaign['category_ids'], $campaign['category_title'], $campaign['categories']);
	
		if($campaign['use_selected_banner']&&empty($campaign['selected_banner_id']))
			return self::show_error(AC_API_ERROR_6);

		if(AC_Data::ac_get_impressions_cnt($campaign['campaign_id'])>=$campaign['max_impressions']&&(int)$campaign['max_impressions']>0)
			return self::show_error(AC_API_ERROR_10);

		if(AC_Data::ac_get_clicks_cnt($campaign['campaign_id'])>=$campaign['max_clicks']&&(int)$campaign['max_clicks']>0)
			return self::show_error(AC_API_ERROR_11);

		if($campaign['banner_display_method']=='selected'){
			$campaign['selected_banner'] = get_bloginfo('wpurl').'/wp-content/uploads/'.AC_UPLOAD_PATH.$campaign['selected_banner'];
			$campaign['selected_banner_title_tag'] = $campaign['banner_title_tag'][$campaign['selected_banner_id']];
			$campaign['selected_banner_alt_tag'] = $campaign['banner_alt_tag'][$campaign['selected_banner_id']];
			$campaign['selected_banner_link'] = $campaign['banner_link'][$campaign['selected_banner_id']];
			$campaign['selected_banner_id'] = $campaign['banner_id'][$campaign['selected_banner_id']];
		}else{
			$random_banner_index = ac_get_random_banner_index($campaign['banner_weight']);
			$campaign['selected_banner'] = get_bloginfo('wpurl').'/wp-content/uploads/'.AC_UPLOAD_PATH.$campaign['banner_filename'][$random_banner_index];
			$campaign['selected_banner_title_tag'] = $campaign['banner_title_tag'][$random_banner_index];
			$campaign['selected_banner_alt_tag'] = $campaign['banner_alt_tag'][$random_banner_index];
			$campaign['selected_banner_link'] = $campaign['banner_link'][$random_banner_index];
			$campaign['selected_banner_id'] = $campaign['banner_id'][$random_banner_index];
		}

		$campaign_active = true;
		if(!$campaign_active)
			return self::show_error(AC_API_ERROR_13);
	
		unset($campaign['date_from'],$campaign['hours_from'],$campaign['mins_from'],$campaign['date_till'],$campaign['hours_to'],$campaign['mins_to']);
	
		$ret_array['banner_id'] = $campaign['selected_banner_id'];
		$ret_array['image'] = $campaign['selected_banner'];
		$ret_array['title_tag'] = $campaign['selected_banner_title_tag'];
		$ret_array['alt_tag'] = $campaign['selected_banner_alt_tag'];
		
		if(!empty($campaign['selected_banner_link']))
			$ret_array['banner_link'] =$campaign['selected_banner_link'];
		elseif(!empty($campaign['link']))
			$ret_array['banner_link'] =$campaign['link'];

		cmac_log('Returning response from AC_Request:get_banner()');
		if(isset($_GET['acs_action'])){
			echo json_encode($ret_array);
			exit;
		}else
			return $ret_array;

	}

	/**
	 * Checks the server status
	 */
	private function ping_server(){
		if(get_option( 'acs_active' ) === '1')
			echo json_encode(array('success'=>'1','message'=>'CM Ad Changer Server is ON'));
		else 
			echo json_encode(array('error'=>'1','message'=>'CM Ad Changer Server is OFF'));
	}

	/**
	 * Performs error response
	 */	
	private function show_error($error){
		if(isset($_GET['acs_action'])){
			echo json_encode(array('error'=>$error));
			exit;
		}else
			return array('error'=>$error);	
	}
}
?>
