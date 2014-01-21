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
