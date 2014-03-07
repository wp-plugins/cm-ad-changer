<?php

/**
 * CM Ad Changer
 *
 * @author CreativeMinds (http://ad-changer.cminds.com)
 * @copyright Copyright (c) 2013, CreativeMinds
 */
class ACWidget extends WP_Widget
{
    var $shortcode_name = 'cm_ad_changer';
    static $widget_displayed = false;

    /**
     * The widget constructor. Specifies the classname and description, instantiates the widget,
     * loads localization files, and includes necessary scripts and styles.
     *
     * @version 1.0
     * @since 1.0
     */
    function ACWidget()
    {
        $widget_opts = array(
            'title'         => 'Ad Changer Client',
            'campaign_id'   => '0',
            'linked_banner' => '1'
        );
        $this->WP_Widget('ACWidget', 'Ad Changer', $widget_opts);
    }

    function widget($args, $instance)
    {
        extract($args);
        echo '<div id="ACWidget"';

        if( isset($instance['css_class']) && !empty($instance['css_class']) && (!isset($instance['wrapper']) || $instance['wrapper'] == '0') ) echo ' class="' . $instance['css_class'] . '"';

        echo '>';

        if( !empty($instance['title']) )
        {
            if( isset($instance['title_css_class']) && !empty($instance['title_css_class']) )
            {
                if( strpos($before_title, 'class="') !== false ) $before_title = str_replace('class="', 'class="' . $instance['title_css_class'] . ' ', $before_title);
                else $before_title = str_replace('>', 'class="' . $instance['title_css_class'] . '">', $before_title);
            }
            echo $before_title . (isset($instance['title']) && !empty($instance['title']) ? $instance['title'] : '') . $after_title;
        }

        if( isset($instance['campaign_id']) )
        {
            $shortcode = '[cm_ad_changer campaign_id=' . $instance['campaign_id'];
            if( isset($instance['linked_banner']) ) $shortcode .= ' linked_banner=' . $instance['linked_banner'];


            if( isset($instance['css_class']) && !empty($instance['css_class']) ) $shortcode .= ' class="' . $instance['css_class'] . '"';

            if( isset($instance['wrapper']) ) $shortcode .= ' wrapper=' . $instance['wrapper'];

            $shortcode .=']';
            echo do_shortcode($shortcode);
        }
        echo '</div>';
        self::$widget_displayed = true;
    }

    function update($new_instance, $old_instance)
    {
        $instance = array();

        if( !is_null($new_instance['title']) )
        {
            $instance['title'] = $new_instance['title'];
        }
        if( !is_null($new_instance['campaign_id']) )
        {
            $instance['campaign_id'] = $new_instance['campaign_id'];
        }


        if( !is_null($new_instance['css_class']) )
        {
            $instance['css_class'] = $new_instance['css_class'];
        }

        if( !is_null($new_instance['title_css_class']) )
        {
            $instance['title_css_class'] = $new_instance['title_css_class'];
        }

        $instance['wrapper'] = isset($new_instance['wrapper']) ? '1' : '0';

        $instance['linked_banner'] = isset($new_instance['linked_banner']) ? '1' : '0';
        return $instance;
    }

    /**
     * Generates the administration form for the widget.
     * @param  Array $instance The array of keys and values for the widget.
     *
     * @version 1.0
     * @since 1.0
     */
    function form($instance)
    {
        if( isset($instance['title']) )
        {
            $title = $instance['title'];
        }
        else
        {
            $title = 'Ad Changer Client';
        }
        ?>
        <table cellpadding=2>
            <?php
            wp_nonce_field(plugin_basename(__FILE__), 'ac_noncename');
            echo '<tr><td><label for="' . $this->get_field_id('title') . '">Title</label></td>';
            echo '<td><input type="text" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" value="' . (isset($instance['title']) ? $instance['title'] : '') . '" size=15  /></td></tr>';

            echo '<tr><td><label for="' . $this->get_field_id('campaign_id') . '">Campaign ID</label></td>';
            echo '<td><input type="text" id="' . $this->get_field_id('campaign_id') . '" name="' . $this->get_field_name('campaign_id') . '" value="' . (isset($instance['campaign_id']) ? $instance['campaign_id'] : '') . '" size=4  /></td></tr>';

            echo '<tr><td width="80px"><label for="' . $this->get_field_id('linked_banner') . '">Linked Banner</label></td>';
            echo '<td><input type="checkbox" id="' . $this->get_field_id('linked_banner') . '" name="' . $this->get_field_name('linked_banner') . '" ' . (isset($instance['linked_banner']) && $instance['linked_banner'] == '0' ? '' : 'checked=checked') . ' value="1"  /></td></tr>';

            echo '<tr><td><label for="' . $this->get_field_id('css_class') . '">Class</label></td>';
            echo '<td><input type="text" id="' . $this->get_field_id('css_class') . '" name="' . $this->get_field_name('css_class') . '" value="' . (isset($instance['css_class']) ? $instance['css_class'] : '') . '" size=15  /></td></tr>';

            echo '<tr><td><label for="' . $this->get_field_id('title_css_class') . '">Title Class</label></td>';
            echo '<td><input type="text" id="' . $this->get_field_id('title_css_class') . '" name="' . $this->get_field_name('title_css_class') . '" value="' . (isset($instance['title_css_class']) ? $instance['title_css_class'] : '') . '" size=15  /></td></tr>';

            echo '<tr><td><label for="' . $this->get_field_id('wrapper') . '">Wrapper</label></td>';
            echo '<td><input type="checkbox" id="' . $this->get_field_id('wrapper') . '" name="' . $this->get_field_name('wrapper') . '" ' . (isset($instance['wrapper']) && $instance['wrapper'] == '1' ? 'checked=checked' : '') . ' value="1"  /></td></tr>';
            ?>
        </table>
        <?php
    }

}

// registering widget
function cm_acc_register_widget()
{
    register_widget('ACWidget');
}

add_action('widgets_init', 'cm_acc_register_widget');
