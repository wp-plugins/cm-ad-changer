<?php
/**
 * CM Ad Changer
 *
 * @author CreativeMinds (http://ad-changer.cminds.com)
 * @copyright Copyright (c) 2013, CreativeMinds
 */
?>

<script>
    plugin_url = '<?php echo CMAC_PLUGIN_URL ?>';
</script>

<div class="acs-shortcode-reference clear">
    <p>To insert the ads into a page or post use following shortcode: [cm_ad_changer]. Here is the list of parameters: <a href="javascript:void(0)" onclick="jQuery(this).parent().next().slideToggle()">Show/Hide</a></p>
    <ul style="list-style-type: disc; margin-left: 20px; display: none;">
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
                        <label class="ac-form-label" for="acs_active" >Server Active</label>
                        <div class="field_help" title="<?php echo CMAdChangerShared::$labels['acs_active'] ?>"></div>
                    </td>
                    <td>
                        <input type="checkbox" name="acs_active" id="acs_active" value="1" <?php echo ($fields_data['acs_active'] == '1' ? 'checked=checked' : '') ?> />
                        <div style="clear:both;height:20px;"></div>
                    </td>
                </tr>
            </table>
            <table cellspacing=3 cellpadding=0 border=0 id="cutom_css_settings">
                <tr>
                    <td valign=top>
                        <label class="ac-form-label" for="acs_custom_css" >Custom CSS</label>
                        <div class="field_help" title="<?php echo CMAdChangerShared::$labels['acs_custom_css'] ?>"></div>
                    </td>
                    <td>
                        <textarea id="acs_custom_css" name="acs_custom_css" rows=7 value="<?php echo stripslashes($fields_data['acs_custom_css']) ?>"><?php echo stripslashes($fields_data['acs_custom_css']) ?></textarea>
                    </td>
                </tr>
            </table>

            <!-- Start Server information Module -->
            <div id="server-info">
                <div class='block'>
                    <h3>Server Information</h3>
                    <?php
                    $safe_mode = ini_get('safe_mode') ? ini_get('safe_mode') : 'Off';
                    $upload_max = ini_get('upload_max_filesize') ? ini_get('upload_max_filesize') : 'N/A';
                    $post_max = ini_get('post_max_size') ? ini_get('post_max_size') : 'N/A';
                    $memory_limit = ini_get('memory_limit') ? ini_get('memory_limit') : 'N/A';
                    $cURL = function_exists('curl_version') ? 'On' : 'Off';

                    $php_info = CMDM::parse_php_info();
                    ?>
                    <span class="description">This information is useful to check if plugin might have some incompabilities with you server.</span>
                    <table class="form-table server-info-table">
                        <tr>
                            <td>PHP Version</td>
                            <td><?php echo phpversion(); ?></td>
                            <td><?php if( version_compare(phpversion(), '5.3.0', '<') ): ?><strong>Recommended 5.3 or higher</strong><?php else: ?><span>OK</span><?php endif; ?></td>
                        </tr>
                        <tr>
                            <td>PHP Safe Mode</td>
                            <td><?php echo $safe_mode; ?></td>
                            <td><?php if( version_compare(phpversion(), '5.3.0', '<') ): ?><strong>Safe mode is deprecated</strong><?php else: ?><span>OK</span><?php endif; ?></td>
                        </tr>
                        <tr>
                            <td>PHP Max Upload Size</td>
                            <td><?php echo $upload_max; ?></td>
                            <td><?php if( CMDM_GroupDownloadPage::units2bytes($upload_max) < 1024 * 1024 * 5 ): ?>
                                    <strong>This value can be too lower to upload large files.</strong>
                                <?php else: ?><span>OK</span><?php endif; ?></td>
                        </tr>
                        <tr>
                            <td>PHP Max Post Size</td>
                            <td><?php echo $post_max; ?></td>
                            <td><?php if( CMDM_GroupDownloadPage::units2bytes($post_max) < 1024 * 1024 * 2 ): ?>
                                    <strong>This value can be too lower to upload large files.</strong>
                                <?php else: ?><span>OK</span><?php endif; ?></td>
                        </tr>
                        <tr>
                            <td>PHP Memory Limit</td>
                            <td><?php echo $memory_limit; ?></td>
                            <td><?php if( CMDM_GroupDownloadPage::units2bytes($memory_limit) < 1024 * 1024 * 128 ): ?>
                                    <strong>This value can be too lower to Wordpress with plugins work properly.</strong>
                                <?php else: ?><span>OK</span><?php endif; ?></td>
                        </tr>
                        <tr>
                            <td>PHP cURL</td>
                            <td><?php echo $cURL; ?></td>
                            <td><?php if( $cURL == 'Off' ): ?>
                                    <strong>cURL library is required to use the Social Login.</strong>
                                <?php else: ?><span>OK</span><?php endif; ?></td>
                        </tr>

                        <?php
                        if( isset($php_info['gd']) && is_array($php_info['gd']) )
                        {
                            foreach($php_info['gd'] as $key => $val)
                            {
                                if( !preg_match('/(WBMP|XBM|Freetype|T1Lib)/i', $key) && $key != 'Directive' && $key != 'gd.jpeg_ignore_warning' )
                                {
                                    echo '<tr>';
                                    echo '<td>' . $key . '</td>';
                                    if( stripos($key, 'support') === false )
                                    {
                                        echo '<td>' . $val . '</td>';
                                    }
                                    else
                                    {
                                        $val = true;
                                        echo '<td>enabled</td>';
                                    }

                                    echo '<td>';
                                    switch($key)
                                    {
                                        case 'GD Support':
                                            if( $val === true ) echo '<span>OK</span>';
                                            else echo '<strong>Required to display screenshots.</strong>';
                                            break;
                                    }
                                    echo '</td>';
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
</div>