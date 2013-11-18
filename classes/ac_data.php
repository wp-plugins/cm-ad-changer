<?php
/**
 * CM Ad Changer 
 *
 * @author CreativeMinds (http://ad-changer.cminds.com)
 * @copyright Copyright (c) 2013, CreativeMinds
 */

class AC_Data{
	
	/**
	 * Performs campaign storage
	 * @return Array
	 * @param Array   $data  Array of fields
	 */		
	function ac_handle_campaigns_post($data){
		global $wpdb;
		$errors = array();
	
		// VALIDATIONS START
		if(empty($data))
			return array('errors'=>array('No data entered'),'fields_data'=>$data);
			
		if(!isset($data['banner_weight'])||!is_array($data['banner_weight']))
			$data['banner_weight'] = array();
		$campaigns = $wpdb->get_results('SELECT * FROM '.CAMPAIGNS_TABLE);
	
		if($campaigns&&count($campaigns)>=(int)get_option('acs_max_campaigns_no',CAMPAIGNS_LIMIT))
			$errors[]='Campaigns limit achieved('.get_option('acs_max_campaigns_no',CAMPAIGNS_LIMIT).')';			
	
		if(empty($data['title']))
			$errors[] = 'Campaign Name field is empty';

		if(strlen($data['comment'])>500)
			$errors[] = 'Note is too long';
	
		if(isset($data['campaign_id'])&&!is_numeric($data['campaign_id']))
			$errors[]='Unknown campaign';
			
		if($data['use_selected_banner']=='1'&&!empty($data['banner_filename'])&&empty($data['selected_banner']))
			$errors[] = 'Please select a banner';
		
		$banner_weight_sum=0;
		$banners_natural = true;
		foreach($data['banner_weight'] as $banner_weight){
			if(!is_numeric($banner_weight)||((int)$banner_weight!=(float)$banner_weight)||$banner_weight<0){
				$errors[]='Please enter numeric positive Banner Weights';
				$banners_natural = false;
				break;
			}
			
			$banner_weight_sum+=(int)$banner_weight;
		}
		
		if($banners_natural)
			if($banner_weight_sum>1000)
				$errors[]='Banner Weight sum is too big';	
	
		if(!empty($errors))
			return array('errors' => $errors, 'fields_data' => $data);
	
		// VALIDATIONS END
		
		// 1. Inserting the campaign record
	
		if(!isset($data['campaign_id'])){
			$wpdb->query($wpdb->prepare('INSERT INTO '.CAMPAIGNS_TABLE.' SET `title`=%s, `link`=%s, `use_selected_banner`=%d, `max_impressions`=%d, `max_clicks`=%d, `comment`=%s, `status`=%d',
										$data['title'],
										$data['link'],
										$data['use_selected_banner'],
										$data['max_impressions'],
										$data['max_clicks'],
										$data['comment'],
										isset($data['status'])?1:0));
										
			$new_campaign_id = $wpdb->insert_id;
		}else{
			$wpdb->query($wpdb->prepare('UPDATE '.CAMPAIGNS_TABLE.' SET `title`=%s, `link`=%s, `use_selected_banner`=%d, `max_impressions`=%d, `max_clicks`=%d, `comment`=%s, `status`=%d WHERE `campaign_id`="'.$data['campaign_id'].'"',
										$data['title'],
										$data['link'],
										$data['use_selected_banner'],
										$data['max_impressions'],
										$data['max_clicks'],
										$data['comment'],
										isset($data[status])?1:0));
			$new_campaign_id = $data['campaign_id'];	
		}

		// 2. Inserting banner images
	
		$new_filenames	   = array();
		if(isset($new_campaign_id)){
			if(!isset($data['banner_filename'])||!is_array($data['banner_filename']))
				$data['banner_filename'] = array();
			$existing_filenames=$wpdb->get_col('SELECT filename FROM '.IMAGES_TABLE.' WHERE `campaign_id`="'.$new_campaign_id.'"');
			
			$deleted_filenames = array();

			foreach($existing_filenames as $existing_filename)
				if(!in_array($existing_filename,$data['banner_filename']))
					$deleted_filenames[]=$existing_filename;
					
			foreach($data['banner_filename'] as $data_filename)
				if(!in_array($data_filename,$existing_filenames))
					$new_filenames[]=$data_filename;					
					
			// cleaning images folder					
			if(!empty($deleted_filenames))
				foreach($deleted_filenames as $deleted_filename){
					if(!in_array($deleted_filename,$data['banner_filename'])){
						if(file_exists(WP_CONTENT_DIR . '/uploads/'.AC_UPLOAD_PATH.$deleted_filename))
							unlink(WP_CONTENT_DIR . '/uploads/'.AC_UPLOAD_PATH.$deleted_filename);
					}
					
					$wpdb->query('DELETE FROM '.IMAGES_TABLE.' WHERE `campaign_id`       ="'.$new_campaign_id.'" AND `filename`="'.$deleted_filename.'"');
				}
		}
	
		$selected_banner_id = null;

		if(isset($data['banner_title'])&&!empty($data['banner_title'])){
			$data['banner_weight'] = ac_normalize_weights($data['banner_weight']);
			foreach($data['banner_title'] as $banner_index=>$banner_title){
				if(in_array($data['banner_filename'][$banner_index],$new_filenames)){
					@$image_file_content=file_get_contents(WP_CONTENT_DIR . '/uploads/'.AC_UPLOAD_PATH.AC_TMP_UPLOAD_PATH.$data['banner_filename'][$banner_index]);
				
					if($image_file_content){
						$f=fopen(WP_CONTENT_DIR . '/uploads/'.AC_UPLOAD_PATH.$data['banner_filename'][$banner_index],'w+');
						fwrite($f,$image_file_content);
						fclose($f);
					}
					
					$wpdb->query($wpdb->prepare('INSERT INTO '.IMAGES_TABLE.' SET `campaign_id`=%d, `title`=%s, `title_tag`=%s, `alt_tag`=%s, `link`=%s, `weight`=%d, `filename`=%s',
												$new_campaign_id, 
												$banner_title,
												$data['banner_title_tag'][$banner_index], 
												$data['banner_alt_tag'][$banner_index], 
												$data['banner_link'][$banner_index], 
												$data['banner_weight'][$banner_index], 
												$data['banner_filename'][$banner_index]));
												
					if($data['banner_filename'][$banner_index]==$data['selected_banner'])
						$selected_banner_id = $wpdb->insert_id;												
				}else{
					$wpdb->query($wpdb->prepare('UPDATE '.IMAGES_TABLE.' SET `title`=%s, `title_tag`=%s, `alt_tag`=%s, `link`=%s, `weight`=%d WHERE `filename`=%s',
												$banner_title,
												$data['banner_title_tag'][$banner_index], 
												$data['banner_alt_tag'][$banner_index], 
												$data['banner_link'][$banner_index], 
												$data['banner_weight'][$banner_index],
												$data['banner_filename'][$banner_index]));
					if($data['banner_filename'][$banner_index]==$data['selected_banner'])					
						$selected_banner_id = $wpdb->get_var('SELECT `image_id` FROM '.IMAGES_TABLE.' WHERE `filename`="'.$data['banner_filename'][$banner_index].'"');
				}
			}
		}
		// cleaning tmp folder
		if($handle = opendir(WP_CONTENT_DIR . '/uploads/'.AC_UPLOAD_PATH.AC_TMP_UPLOAD_PATH))
			while (false !== ($entry = readdir($handle))) 
				if(file_exists(WP_CONTENT_DIR . '/uploads/'.AC_UPLOAD_PATH.AC_TMP_UPLOAD_PATH.$entry)&&$entry!='.'&&$entry!='..')
					unlink(WP_CONTENT_DIR . '/uploads/'.AC_UPLOAD_PATH.AC_TMP_UPLOAD_PATH.$entry);
	
	
		// updating campaigns : setting selected banner
		$wpdb->query('UPDATE '.CAMPAIGNS_TABLE.' SET `selected_banner`="'.($selected_banner_id?$selected_banner_id:'0').'" WHERE campaign_id="'.$new_campaign_id.'"');

		if(!empty($wpdb->last_error))
			return array('errors'=>array('Database error'),'fields_data'=>$data);
		
		if(empty($errors))
			return array();
		
	}

	/**
	 * Performs settings storage
	 * @return Array
	 * @param Array   $data  Array of fields
	 */
	function ac_handle_settings_post($data){
		$errors = array();

		if(empty($data['acs_max_campaigns_no']))
			$errors[] = 'Maximal Number of Campaigns is not set';
		
		if(!is_numeric($data['acs_max_campaigns_no']))
			$errors[] = 'Please specify valid Maximal Number of Campaigns';
		
		if((int)$data['acs_max_campaigns_no']>CAMPAIGNS_LIMIT)
			$errors[] = 'Maximal Number of Campaigns must be less than '.CAMPAIGNS_LIMIT;
		
		if(is_numeric($data['acs_max_campaigns_no'])&&(int)$data['acs_max_campaigns_no']<1)
			$errors[] = 'Maximal Number of Campaigns must be greater than 1';

		$campaigns = self::ac_get_campaigns();
		if(count($campaigns)>$data['acs_max_campaigns_no'])
			$errors[] = 'Number of Campaigns in database is greater than specified Maximal Number of Campaigns';
		
		if(!empty($errors))
			return array('errors'=>$errors,'data'=>$data);
		
		if(isset($_POST['acs_active'])){
			if ( get_option( 'acs_active' ) !== false ){
				update_option( 'acs_active', 1 );
			}else{
				add_option( 'acs_active', 1 );	
			}
		}else{
			if ( get_option( 'acs_active' ) !== false ){
				update_option( 'acs_active', 0 );
			}else{		
				add_option( 'acs_active', 0 );			
			}
		}
	
		if(isset($_POST['acs_max_campaigns_no'])){
			if ( get_option( 'acs_max_campaigns_no' ) !== false ){
				update_option( 'acs_max_campaigns_no', $_POST['acs_max_campaigns_no'] );
			}else{
				add_option( 'acs_max_campaigns_no', $_POST['acs_max_campaigns_no'] );
			}
		}

		if(isset($_POST['acc_campaign_id'])){
			if ( get_option( 'acc_campaign_id' ) !== false ){
				update_option( 'acc_campaign_id', $_POST['acc_campaign_id'] );
			}else{
				add_option( 'acc_campaign_id','' );
			}
		}		
	
		if(isset($_POST['acs_custom_css'])){
			if ( get_option( 'acs_custom_css' ) !== false ){
				update_option( 'acs_custom_css', $_POST['acs_custom_css'] );
			}else{
				add_option( 'acs_custom_css', $_POST['acs_custom_css'] );	
			}
						
		}else{
			if ( get_option( 'acs_custom_css' ) !== false ){
				update_option( 'acs_custom_css', '' );
			}else{
				add_option( 'acs_custom_css', '' );	
			}		
		}		
		return array();
	}

	/**
	 * Gets list of all campaigns
	 * @return Array
	 */
	function ac_get_campaigns(){
		global $wpdb;
	
		$campaigns = $wpdb->get_results('SELECT c.*, count(ci.image_id) as banners_cnt FROM '.CAMPAIGNS_TABLE.' as c
																		LEFT JOIN '.IMAGES_TABLE.' as ci ON ci.campaign_id=c.campaign_id
																		GROUP BY c.campaign_id
																		');			
																		
		foreach($campaigns as $campaign_index=>$campaign){
			$campaigns[$campaign_index]->impressions_cnt = self::ac_get_impressions_cnt($campaign->campaign_id);
			$campaigns[$campaign_index]->clicks_cnt = self::ac_get_clicks_cnt($campaign->campaign_id);
		}
		return $campaigns;
	}

	/**
	 * Gets single campaign
	 * @return Array
	 * @param Int   $campaign_id  Campaign ID
	 */
	function ac_get_campaign($campaign_id){
		global $wpdb;
	
		$ret_fields=array('campaign_id'=>$campaign_id);
	
		$campaign = $wpdb->get_row('SELECT c.* FROM '.CAMPAIGNS_TABLE.' c WHERE c.campaign_id="'.$campaign_id.'"');
	//	var_dump($campaign);
	
		$ret_fields['title'] = $campaign->title;
		$ret_fields['link'] = $campaign->link;
		$ret_fields['use_selected_banner'] = $campaign->use_selected_banner;
		$ret_fields['selected_banner_id']=$campaign->selected_banner;
		$ret_fields['max_impressions'] = $campaign->max_impressions;
		$ret_fields['max_clicks'] = $campaign->max_clicks;
		$ret_fields['comment'] = $campaign->comment;
		if($campaign->status=='1')
			$ret_fields['status'] = '1';
	   
		$images = $wpdb->get_results('SELECT ci.image_id, ci.title, ci.title_tag, ci.alt_tag, ci.link, ci.weight,  ci.filename FROM '.IMAGES_TABLE.' ci 
												   WHERE ci.campaign_id="'.$campaign_id.'"');
	//	var_dump($images);											   
		foreach($images as $image){
			$ret_fields['banner_id'][$image->image_id] = $image->image_id;
			$ret_fields['banner_filename'][$image->image_id] = $image->filename;
			$ret_fields['banner_title'][$image->image_id] = $image->title;
			$ret_fields['banner_title_tag'][$image->image_id] = $image->title_tag;
			$ret_fields['banner_alt_tag'][$image->image_id] = $image->alt_tag;
			$ret_fields['banner_link'][$image->image_id] = $image->link;
			$ret_fields['banner_weight'][$image->image_id] = $image->weight;
			$ret_fields['banner_clicks_cnt'][$image->image_id] = self::ac_get_banner_clicks_cnt($image->image_id);
			$ret_fields['banner_impressions_cnt'][$image->image_id] = self::ac_get_banner_impressions_cnt($image->image_id);
		
			if($image->image_id==$campaign->selected_banner){
				$ret_fields['selected_banner']=$image->filename;
			}
		}

	//	var_dump($ret_fields);
		return $ret_fields;
	}

	/**
	 * Gets impressions count for a campaign
	 * @return Int
	 * @param Int   $campaign_id  Campaign ID
	 */
	function ac_get_impressions_cnt($campaign_id){
		global $wpdb;

		$impressions_cnt = $wpdb->get_var('SELECT count(*) FROM '.HISTORY_TABLE.' WHERE `event_type`="impression" AND `campaign_id`="'.$campaign_id.'"');

		return $impressions_cnt;
	}

	/**
	 * Gets clicks count for a campaign
	 * @return Int
	 * @param Int   $campaign_id  Campaign ID
	 */
	function ac_get_banner_clicks_cnt($banner_id){
		global $wpdb;

		$clicks_cnt = $wpdb->get_var('SELECT count(*) FROM '.HISTORY_TABLE.' WHERE `event_type`="click" AND `banner_id`="'.$banner_id.'"');

		return $clicks_cnt;
	}

	/**
	 * Gets impressions count for a banner
	 * @return Int
	 * @param Int   $banner_id  Banner ID
	 */
	function ac_get_banner_impressions_cnt($banner_id){
		global $wpdb;

		$impressions_cnt = $wpdb->get_var('SELECT count(*) FROM '.HISTORY_TABLE.' WHERE `event_type`="impression" AND `banner_id`="'.$banner_id.'"');

		return $impressions_cnt;
	}

	/**
	 * Gets clicks count for a banner
	 * @return Int
	 * @param Int   $banner_id  Banner ID
	 */
	function ac_get_clicks_cnt($campaign_id){
		global $wpdb;

		$clicks_cnt = $wpdb->get_var('SELECT count(*) FROM '.HISTORY_TABLE.' WHERE `event_type`="click" AND `campaign_id`="'.$campaign_id.'"');

		return $clicks_cnt;
	}

	/**
	 * Removes campaign and all related data
	 * @param Int   $campaign_id  Campaign ID
	 */
	function ac_remove_campaign($campaign_id){
		global $wpdb;
	
		$images=$wpdb->get_col('SELECT filename FROM '.IMAGES_TABLE.' WHERE `campaign_id`="'.$campaign_id.'"');

		foreach($images as $image)
			if(file_exists(WP_CONTENT_DIR . '/uploads/'.AC_UPLOAD_PATH.$image))
				unlink(WP_CONTENT_DIR . '/uploads/'.AC_UPLOAD_PATH.$image);
	
		$wpdb->query('DELETE FROM '.CAMPAIGNS_TABLE.' WHERE `campaign_id`="'.$campaign_id.'"');
		$wpdb->query('DELETE FROM '.CAMPAIGN_CATEGORIES_REL_TABLE.' WHERE `campaign_id`="'.$campaign_id.'"');
		$wpdb->query('DELETE FROM '.IMAGES_TABLE.' WHERE `campaign_id`="'.$campaign_id.'"');
		$wpdb->query('DELETE FROM '.PERIODS_TABLE.' WHERE `campaign_id`="'.$campaign_id.'"');
	}

	/**
	 * Gets category
	 * @return Array
	 * @param Int   $category_id  Category ID
	 */
	function ac_get_category($category_id){
		global $wpdb;
		return $wpdb->get_row('SELECT * FROM '.CATEGORIES_TABLE.' WHERE `category_id`="'.$category_id.'"');
	}
	
	/**
	 * Gets paged history
	 * @return Array
	 * @param Int   $page  Page Number
	 * @param Int   $output_type OBJECT OF ARRAY
	 */
	function get_history($page=1,$output_type = OBJECT){
		global $wpdb;
		
		if(!is_numeric($page)||$page<0)
			return array();
		
		if($page > 0){
			$offset = ($page-1)*AC_HISTORY_PER_PAGE_LIMIT;
			$limit = AC_HISTORY_PER_PAGE_LIMIT;
		}else
			$limit = false;

		$history = $wpdb->get_results('SELECT h.*, c.title as campaign_title, c.campaign_id, i.* FROM '.HISTORY_TABLE.' h
										LEFT JOIN '.CAMPAIGNS_TABLE.' c ON c.campaign_id=h.campaign_id
										LEFT JOIN '.IMAGES_TABLE.' i ON i.image_id=h.banner_id 
									   ORDER BY h.regdate DESC'.($limit!==false?' LIMIT '.$offset.', '.AC_HISTORY_PER_PAGE_LIMIT:''), $output_type);
		return $history;
	}
	
	/**
	 * Gets months
	 * @return Array
	 */
	function get_history_months(){
		global $wpdb;
		
		$months = $wpdb->get_results('SELECT DISTINCT DATE_FORMAT(regdate,"%M") as month, DATE_FORMAT(regdate,"%Y") as year FROM '.HISTORY_TABLE.' 
									  GROUP BY year, month
									  ORDER BY regdate DESC');
		$ret_months=array();
		foreach($months as $index=>$month)
			if($month->year==date('Y'))
				$ret_months[]=$month->month;
			else
				$ret_months[]=$month->month.', '.$month->year;
		return $ret_months;
	}
	
	/**
	 * Gets info for one month
	 * @return Array
	 * @param String   $month  month and year
	 * @param Int   $campaign_id Campaign ID
	 */
	function get_history_month($month,$campaign_id=null){
		global $wpdb;
		
		$month = explode(', ',$month);
		
		if(count($month)==1)
			$month[1]=date('Y');
		
		$month_details = $wpdb->get_results('SELECT count(*) as cnt, i.title, i.filename, "impressions" as event_type
											FROM '.HISTORY_TABLE.' h
											INNER JOIN '.IMAGES_TABLE.' i ON h.banner_id=i.image_id
											WHERE DATE_FORMAT(h.regdate,"%M")="'.$month[0].'" AND 
												  DATE_FORMAT(h.regdate,"%Y")="'.$month[1].'" AND
												  h.event_type="impression"
												  '.($campaign_id?' AND h.campaign_id='.$campaign_id:'').'
											GROUP BY i.title
										UNION
											SELECT count(*) as cnt, i.title, i.filename, "clicks" as event_type
											FROM '.HISTORY_TABLE.' h
											INNER JOIN '.IMAGES_TABLE.' i ON h.banner_id=i.image_id
											WHERE DATE_FORMAT(h.regdate,"%M")="'.$month[0].'" AND 
												  DATE_FORMAT(h.regdate,"%Y")="'.$month[1].'" AND
												  h.event_type="click"
												  '.($campaign_id?' AND h.campaign_id='.$campaign_id:'').'
											GROUP BY i.title
										ORDER BY title');
		
		$banners_stats = array();
		foreach($month_details as $index=>$record){
			$banners_stats[$record->title]['filename'] = $record->filename;
			if($record->event_type=='impressions')
				$banners_stats[$record->title]['impressions'] = $record->cnt;
			if($record->event_type=='clicks')
				$banners_stats[$record->title]['clicks'] = $record->cnt;				
		}
		
		foreach($banners_stats as $title=>$stats){
			if(!isset($stats['impressions']))
				$banners_stats[$title]['impressions'] = '0';
			if(!isset($stats['clicks']))
				$banners_stats[$title]['clicks'] = '0';
		}

		return $banners_stats;
	}
	
	/**
	 * removes all records from history table
	 */
	function empty_history(){
		global $wpdb;
		$wpdb->query('TRUNCATE TABLE '.HISTORY_TABLE);
	}
	
	/**
	 * Gets clients logs
	 * @return Array
	 */
	function get_clients_logs(){
		global $wpdb;
		
		$client_domains = $wpdb->get_col('SELECT referer_url FROM '.HISTORY_TABLE.'
											GROUP BY referer_url
											ORDER BY referer_url ASC, regdate DESC');
							
		$clients_logs = array();
		if($client_domains&&!empty($client_domains))
			foreach($client_domains as $domain)
				$clients_logs[] = $wpdb->get_row('SELECT h.referer_url, c.title as campaign_name, h.regdate, i.filename, i.title as banner_name FROM '.HISTORY_TABLE.' h
														INNER JOIN '.CAMPAIGNS_TABLE.' c ON c.campaign_id=h.campaign_id
														INNER JOIN '.IMAGES_TABLE.' i ON h.banner_id=i.image_id
														WHERE h.referer_url = "'.$domain.'"
														ORDER BY h.regdate DESC
														LIMIT 1');
		return $clients_logs;
	}
}

