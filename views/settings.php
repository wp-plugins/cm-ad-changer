<?php
/**
 * CM Ad Changer 
 *
 * @author CreativeMinds (http://ad-changer.cminds.com)
 * @copyright Copyright (c) 2013, CreativeMinds
 */
?>

<script>
	plugin_url='<?php echo plugins_url('', __FILE__)?>';
</script>
<div class="wrap ad_changer ac_settings">
	<h2><?php echo $plugin_data['Name']; ?> : Settings</h2>
<?php
	ac_top_menu();
	if(isset($errors)&&!empty($errors)){
?>
	<ul class="ac_error clear">
<?php
		foreach($errors as $error)
			echo '<li>'.$error.'</li>';
?>		
	</ul>
<?php		
	}
	if($success)
		echo '<div class="ac_success clear">'.$success.'</div>';		
?>			
	<div class="acs-shortcode-reference clear">
		<p>To insert the ads into a page or post use following shortcode: [cm_ad_changer]. Here is the list of parameters: <a href="javascript:void(0)" onclick="jQuery(this).parent().next().slideToggle()">Show/Hide</a></p>
		<ul style="list-style-type: disc; margin-left: 20px;">
			<li>
				<strong>campaign_id</strong> - ID of a campaign (required)
			</li>
			<li>
				<strong>linked_banner</strong> - Banner is a linked image or just image. Can be 1 or 0 (default: 1)
			</li>
			<li>
				<strong>debug</strong> - Show the debug info. Can be 1 or 0 (default: 0)
			</li>				
			<li>
				<strong>wrapper</strong> - Wrapper On or Off. Wraps banner with  a div tag. Can be 1 or 0 (default: 0)
			</li>						
			<li>
				<strong>class</strong> - Banner (div) class name
			</li>				
		</ul>
	</div>
	<div class="ac-edit-form clear">
		<form id="acs_settings_form" method="post">
		<input type="hidden" name="action" value="acs_settings" />
		<div id="settings_fields" class="clear">
			<ul>
				<li><a href="#general_settings_fields">General Settings</a></li>
				<li><a href="#cutom_css_settings">Custom CSS</a></li>
				<li><a href="#server-info">Server Information</a></li>
			</ul>

			<table cellspacing=3 cellpadding=0 border=0 id="general_settings_fields">
				<tr>
					<td>
						<label class="ac-form-label" for="acs_active" >Server Active</label><div class="field_help" title="<?php echo $label_descriptions['acs_active']?>"></div>
					</td>
					<td>
						<input type="checkbox" name="acs_active" id="acs_active" value="1" <?php echo ($fields_data['acs_active']=='1'?'checked=checked':'')?> />
			 <div style="clear:both;height:20px;"></div>		
					</td>
				</tr>
				<tr>
					<td>
						<label class="ac-form-label" for="acs_max_campaigns_no" >Max Number of Campaigns</label><div class="field_help" title="<?php echo $label_descriptions['acs_max_campaigns_no']?>"></div>
					</td>
					<td>
						<input type="text" name="acs_max_campaigns_no" id="acs_max_campaigns_no" value="<?php echo $fields_data['acs_max_campaigns_no']?>" size=1 />
					</td>
				</tr>					
			</table>	
		<table cellspacing=3 cellpadding=0 border=0 id="cutom_css_settings">
			<tr>
				<td valign=top>
					<label class="ac-form-label" for="acs_custom_css" >Custom CSS</label><div class="field_help" title="<?php echo $label_descriptions['acs_custom_css']?>"></div>
				</td>
				<td>
					<textarea id="acs_custom_css" name="acs_custom_css" rows=7 value="<?php echo stripslashes($fields_data['acs_custom_css'])?>"><?php echo stripslashes($fields_data['acs_custom_css'])?></textarea>
				</td>
			</tr>							
		</table>
		
<!-- Start Server information Module -->
			<div id="server-info">
				<div class='block'>
                <h3>Server Information</h3>
<?php 
		
	if (ini_get('safe_mode'))
		$safe_mode = 'On';
	else
		$safe_mode = 'Off';

	if (!($upload_max = ini_get('upload_max_filesize')))
		$upload_max = 'N/A';

	if (!($post_max = ini_get('post_max_size')))
		$post_max = 'N/A';

	if (!($memory_limit = ini_get('memory_limit')))
		$memory_limit = 'N/A';

	if (!($cURL = function_exists('curl_version')))
		$cURL = 'Off';
	else
		$cURL = 'On';


		$php_info = parse_php_info();
?>
				<span class="description" style="">
				CM Ad Changer is a mix of a JavaScript application and a parsing engine. 
				This information is useful to check if the CM Ad Changer might have some incompatibilities with your server. Make sure GD support is enabled.
				</span>
				<table class="form-table">
				<tr>
					<td>PHP Version</td><td><?php echo phpversion(); ?></td>
				</tr>
				<tr>
					<td>PHP Safe Mode</td><td><?php echo $safe_mode; ?> (Should be Off)</td>
				</tr>
				<tr>
					<td>PHP Max Upload Size</td><td><?php echo $upload_max; ?></td>
				</tr>
				<tr>
					<td>PHP Max Post Size</td><td><?php echo $post_max; ?></td>
				</tr>
				<tr>
					<td>PHP Memory Limit</td><td><?php echo $memory_limit; ?></td>
				</tr>
				<tr>
					<td>PHP cURL</td><td><?php echo $cURL; ?> (Should be On)</td>
				</tr>

				<?php
					if (isset($php_info['gd']) && is_array($php_info['gd']))
					{
						foreach ($php_info['gd'] as $key => $val) {
							if (!preg_match('/(WBMP|XBM|Freetype|T1Lib)/i', $key) && $key != 'Directive' && $key != 'gd.jpeg_ignore_warning') {
								echo '<tr>';
								echo '<td>'.$key.'</td>';
								if (stripos($key, 'support') === false) {
									echo '<td>'.$val.'</td>';
								}
								else {
									echo '<td>enabled</td>';
								}
								echo '</tr>';
							}
						}
					}
				?>
				</table>
				</div>
			</div>		
	</div>
		<input type="submit" value="Store Settings" id="submit_button">		
		</form>
<script>
jQuery(document).ready(function(){
	jQuery('#settings_fields').tabs();	
	jQuery('#acs_max_campaigns_no').spinner({
		max: 50,
		min: 0
	});			

	jQuery('#acs_div_wrapper').click(function(){

		if(jQuery(this).attr('checked')=='checked'){
			jQuery('.custom_style').css('display','inline');
		}
		else
			jQuery('.custom_style').hide();
	})

	if(jQuery('#acs_div_wrapper').attr('checked')=='checked')
		jQuery('.custom_style').css('display','inline');	
		
	jQuery('.field_help').tooltip({
		show: {
			effect: "slideDown",
			delay: 100
		},
		position: {
			my: "left top",
			at: "right top"
		}		
	})		
})
</script>
	</div>
</div>
