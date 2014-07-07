<?php
/**
 * CM Ad Changer
 *
 * @author CreativeMinds (http://ad-changer.cminds.com)
 * @copyright Copyright (c) 2013, CreativeMinds
 */
?>

<script type="text/javascript">
    var base_url = '<?php echo get_bloginfo('wpurl') ?>';
    var plugin_url = '<?php echo CMAC_PLUGIN_URL ?>';
    var upload_tmp_path = '<?php echo cmac_get_upload_url() . CMAC_TMP_UPLOAD_PATH; ?>';
    var banners_limit = <?php echo CMAC_BANNERS_PER_CAMPAIGN_LIMIT; ?>;
    var next_banner_index = 0;
    var label_descriptions = new Object();
    label_descriptions.banner_title = '<?php echo CMAdChangerShared::$labels['banner_title']; ?>';
    label_descriptions.banner_title_tag = '<?php echo CMAdChangerShared::$labels['banner_title_tag']; ?>';
    label_descriptions.banner_alt_tag = '<?php echo CMAdChangerShared::$labels['banner_alt_tag']; ?>';
    label_descriptions.banner_link = '<?php echo CMAdChangerShared::$labels['banner_link']; ?>';
    label_descriptions.banner_weight = '<?php echo CMAdChangerShared::$labels['banner_weight']; ?>';
</script>

<input type="submit" value="Create new Campaign" class="right clear" id="new_campaign_button" />
<div class="clear"></div>

<?php if( !empty($campaigns) ) : ?>

    <div class="campaigns_list_table_head">
        <div style="text-align: left !important;">Campaign Name</div>
        <div>Campaign ID</div>
        <div>Images</div>
        <div>Clicks</div>
        <div>Impressions</div>
        <div>Status</div>
        <div>Actions</div>
    </div>

    <div class="campaigns_list_scroll clear">
        <table id="campaigns_list" class="ads_list" cellspacing=0 cellpadding=0 border=0>
            <tbody>
                <?php foreach($campaigns as $campaign) : ?>
                    <tr campaign_id="<?php echo $campaign->campaign_id ?>"<?php echo isset($fields_data['campaign_id']) && $fields_data['campaign_id'] == $campaign->campaign_id ? ' class="selected_campaign"' : '' ?>>
                        <td>
                            <a href="<?php echo get_bloginfo('wpurl') ?>/wp-admin/admin.php?page=<?php echo $pageName ?>&action=edit&campaign_id=<?php echo $campaign->campaign_id ?>" class="field_tip" title="<?php echo $campaign->comment ?>"><?php echo $campaign->title; ?></a>
                        </td>
                        <td><?php echo $campaign->campaign_id; ?></td>
                        <td><?php echo $campaign->banners_cnt; ?></td>
                        <td><?php echo!is_null($campaign->clicks_cnt) ? $campaign->clicks_cnt : '-'; ?></td>
                        <td><?php echo!is_null($campaign->impressions_cnt) ? $campaign->impressions_cnt : '-'; ?></td>
                        <td><?php echo ($campaign->status == '1' ? 'Active' : 'Inactive') ?></td>
                        <td class="actions">
                            <a href="<?php echo get_bloginfo('wpurl') ?>/wp-admin/admin.php?page=<?php echo $pageName ?>&action=edit&campaign_id=<?php echo $campaign->campaign_id ?>"><img src="<?php echo self::$cssPath . 'images/edit.png' ?>" /></a>
                            <a href="<?php echo get_bloginfo('wpurl') ?>/wp-admin/admin.php?page=<?php echo $pageName ?>&action=delete&campaign_id=<?php echo $campaign->campaign_id ?>" class="delete_campaign_link"><img src="<?php echo self::$cssPath . 'images/trash.png' ?>" /></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<div class="ac-edit-form">
    <form id="campaign_form" class="clear ac-form" <?php echo (isset($fields_data['title']) || (isset($_GET['acs_admin_action']) && $_GET['acs_admin_action'] == 'new_campaign' && empty($_POST)) ? 'style="display:block !important"' : '') ?> method="post">
        <div class="right" style="margin-bottom: 5px;">
            <input type="submit" value="<?php echo (isset($fields_data['campaign_id']) ? 'Save' : 'Add') ?>" name="submit" id="submit_button" class="right">

        </div>
        <div id="ac-fields" class="clear">
            <ul>
                <li><a href="#campaign_fields">Campaign Settings</a></li>
                <li><a href="#banners_fields">Campaign Banners</a></li>
            </ul>
            <table cellspacing=0 cellpadding=0 border=0 class="clear" id="campaign_fields">
                <tr>
                    <td>
                        <label class="ac-form-label" for="title" >Campaign Name <span class="required" style="color:red">*</span> </label><div class="field_help" title="<?php echo CMAdChangerShared::$labels['title'] ?>"></div><br/>
                        <?php
                        if( isset($fields_data) && isset($fields_data['campaign_id']) )
                        {
                            echo '<input type="hidden" name="campaign_id" value="' . $fields_data['campaign_id'] . '" />';
                            echo '<br><strong>Campaign ID <div class="field_help" title="' . CMAdChangerShared::$labels['campaign_id'] . '"></div> :' . $fields_data['campaign_id'] . '</strong>';
                        }
                        ?>
                    </td>
                    <td>
                        <input type="text" aria-required="true" value="<?php echo (isset($fields_data['title']) ? $fields_data['title'] : '') ?>" name="title" id="title" /></br>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label class="ac-form-label" for="comment" class="clear" >Campaign Notes</label>
                        <div class="field_help" title="<?php echo CMAdChangerShared::$labels['comment'] ?>"></div>
                    </td>
                    <td>
                        <textarea value="<?php echo (isset($fields_data['comment']) ? stripslashes($fields_data['comment']) : '') ?>" name="comment" id="comment"><?php echo (isset($fields_data['comment']) ? stripslashes($fields_data['comment']) : '') ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label class="ac-form-label" for="link" >Target URL</label>
                        <div class="field_help" title="<?php echo CMAdChangerShared::$labels['link'] ?>"></div>
                    </td>
                    <td>
                        <input type="text" aria-required="false" value="<?php echo (isset($fields_data['link']) ? $fields_data['link'] : '') ?>" name="link" id="link" />
                    </td>
                </tr>
                <tr>
                    <td>
                        <label class="ac-form-label" for="status">Campaign Status</label>
                        <div class="field_help" title="<?php echo CMAdChangerShared::$labels['status'] ?>"></div>
                    </td>
                    <td>
                        <input type="checkbox" aria-required="true" name="status" id="status" <?php echo (isset($fields_data['status']) ? 'checked=checked' : '') ?> />&nbsp;Active
                    </td>
                </tr>
            </table>
            <table cellspacing=0 cellpadding=0 border=0 id="banners_fields">
                <tr>
                    <td>
                        <label class="ac-form-label" for="use_random_banner">Display Method</label>
                        <div class="field_help" title="<?php echo CMAdChangerShared::$labels['use_selected_banner'] ?>"></div>
                    </td>
                    <td>
                        <input type="radio" aria-required="true" name="banner_display_method" id="use_random_banner" <?php echo (isset($fields_data['banner_display_method']) && $fields_data['banner_display_method'] == 'random' ? 'checked=checked' : (!isset($fields_data['banner_display_method']) ? 'checked=checked' : '')) ?> value="random" />&nbsp;<label for="use_random_banner">Random Banner</label><br/>
                        <input type="radio" aria-required="true" name="banner_display_method" id="use_selected_banner" <?php echo (isset($fields_data['banner_display_method']) && $fields_data['banner_display_method'] == 'selected' ? 'checked=checked' : '') ?> value="selected" />&nbsp;<label for="use_selected_banner">Selected Banner</label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label class="ac-form-label" for="campaign_images">Campaign Images</label>
                        <div class="field_help" title="<?php echo CMAdChangerShared::$labels['campaign_images'] ?>"></div>
                    </td>
                    <td>
                        <div id="container">
                            <input type="button" value="Select files" id="pickfiles" class="pickfiles clear">
                            <div id="filelist" class="clear">
                                <?php
                                if( isset($fields_data['banners']) )
                                {
                                    foreach($fields_data['banners'] as $banner_index => $banner)
                                    {
                                        $clicks_rate = 0;
                                        $banner_filename = $banner['filename'];
                                        if( (int) $banner['banner_clicks_cnt'] > 0 )
                                        {
                                            $clicks_rate = round(((int) $banner['banner_clicks_cnt'] / (int) $banner['banner_impressions_cnt']) * 100);
                                        }

                                        //if(@file_get_contents(get_bloginfo('wpurl') . '/wp-content/uploads/'.CMAC_UPLOAD_PATH.''.$banner_filename)){
                                        if( file_exists(cmac_get_upload_dir() . $banner_filename) )
                                        {
                                            $filename = cmac_get_upload_url() . $banner_filename;
                                            $filename1 = cmac_get_upload_dir() . $banner_filename;
                                        }
                                        else
                                        {
                                            $filename = cmac_get_upload_url() . CMAC_TMP_UPLOAD_PATH . '' . $banner_filename;
                                            $filename1 = cmac_get_upload_dir() . CMAC_TMP_UPLOAD_PATH . $banner_filename;
                                        }

                                        // image info
                                        $image_size = getimagesize($filename1);
                                        $filesize = round(filesize($filename1) / 1024);
                                        $image_width = $image_size[0];
                                        $image_height = $image_size[1];
                                        $mime_splitted = explode('/', $image_size['mime']);
                                        $ext = $mime_splitted[1];
                                        $image_info = '<b>Dimensions:</b> ' . $image_width . 'x' . $image_height . "<br/>";
                                        $image_info .= '<b>Size:</b> ' . $filesize . ' kb' . "<br/>";
                                        $image_info .= '<b>Type:</b> ' . $ext;
                                        echo '<div class="plupload_image">
                                                    <img src="' . $filename . '" class="banner_image" title="' . $image_info . '" />
                                                    <input type="hidden" name="banner_filename[]" value="' . $banner_filename . '" />
                                                    <table class="banner_info" border=0>
                                                            <tr><td><label for="banner_title' . $banner_index . '">Name</label><div class="field_help" title="' . CMAdChangerShared::$labels['banner_title'] . '"></div></td><td><input type="text" name="banner_title[]" id="banner_title' . $banner_index . '" maxlength="150" value="' . (isset($banner['title']) ? $banner['title'] : '') . '" /></td></tr>
                                                            <tr><td><label for="banner_title_tag' . $banner_index . '">Banner Title</label><div class="field_help" title="' . CMAdChangerShared::$labels['banner_title_tag'] . '"></div></td><td><input type="text" name="banner_title_tag[]" id="banner_title_tag' . $banner_index . '" maxlength="50" value="' . (isset($banner['title_tag']) ? $banner['title_tag'] : '') . '" /></td></tr>
                                                            <tr><td><label for="banner_alt_tag' . $banner_index . '">Banner Alt</label><div class="field_help" title="' . CMAdChangerShared::$labels['banner_alt_tag'] . '"></div></td><td><input type="text" name="banner_alt_tag[]" id="banner_alt_tag' . $banner_index . '" maxlength="150" value="' . (isset($banner['alt_tag']) ? $banner['alt_tag'] : '') . '" /></td></tr>
                                                            <tr><td><label for="banner_link' . $banner_index . '">Target URL</label><div class="field_help" title="' . CMAdChangerShared::$labels['banner_link'] . '"></div></td><td><input type="text" name="banner_link[]" id="banner_link' . $banner_index . '" maxlength="150" value="' . (isset($banner['link']) ? $banner['link'] : '') . '" /></td></tr>
                                                            <tr><td><label for="banner_weight' . $banner_index . '">Weight</label><div class="field_help" title="' . CMAdChangerShared::$labels['banner_weight'] . '"></div></td><td><input type="text" name="banner_weight[]" id="banner_weight' . $banner_index . '" maxlength="4" value="' . (isset($banner['weight']) && is_numeric($banner['weight']) ? $banner['weight'] : '0') . '" class="num_field" /></td></tr>
                                                    </table>
                                                    <div class="ac_explanation clear">Click on image to select the banner</div>
                                                    <div class="clicks_and_impressions">
                                                            <div class="impressions">' . ($banner['banner_impressions_cnt'] ? $banner['banner_impressions_cnt'] : 0) . '</div>
                                                            <div class="clicks">' . ($banner['banner_clicks_cnt'] ? $banner['banner_clicks_cnt'] : 0) . '</div>
                                                            <div class="percent">' . $clicks_rate . '</div>
                                                    </div>
                                                    <img src="' . self::$cssPath . 'images/close.png' . '" class="delete_button" />
                                            </div>';
                                    }

                                    if( isset($fields_data['selected_banner_file']) && !empty($fields_data['selected_banner_file']) )
                                    {
                                        echo '<script type="text/javascript">
											jQuery(document).ready(function(){
												CM_AdsChanger.check_banner(jQuery(\'#filelist input[type="hidden"][value="' . $fields_data['selected_banner_file'] . '"]\').parent());
											})
										  </script>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <div class="selected_banner_details">
                            <label class="ac-form-label">Selected Image URL:</label>
                            <div id="selected_banner_url"></div>
                            <label class="ac-form-label" for="selected_image">Selected Image Name:</label>
                            <div id="selected_banner"></div>
                            <input type="hidden" name="selected_banner" value="" />
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="right">
            <input type="submit" value="<?php echo (isset($fields_data['campaign_id']) ? 'Save' : 'Add') ?>" name="submit" id="submit_button">
        </div>
    </form>
</div>