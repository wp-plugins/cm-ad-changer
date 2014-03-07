<?php

/**
 * CM Ad Changer
 *
 * @author CreativeMinds (http://ad-changer.cminds.com)
 * @copyright Copyright (c) 2013, CreativeMinds
 */
class AC_Client
{

    /**
     * Banner output
     * @return String
     * @param Array   $args  Shortcode arguments
     */
    public function banner_output($args)
    {
        cmac_log('AC_Client::banner_output()');
        if( is_array($args) ) $campaign_id = $args['campaign_id'];
        elseif( is_numeric($args) ) $campaign_id = $args;
        else
        {
            return 'Wrong campaign ID';
        }

        $server_url = get_bloginfo('wpurl');
        $url = $server_url . '/?acs_action=get_banner&campaign_id=' . $campaign_id;

//		$data = self::curl_request($url,'http_referer='.get_bloginfo('wpurl'));
        $banner = AC_Requests::get_banner(get_bloginfo('wpurl'), // http referer
                                                       $campaign_id  // campaign id
        );

//		$banner = json_decode($data,true);

        if( isset($args['debug']) ) echo ac_format_list($banner, 'Ad changer debug Info:', 'acc_debug');

        if( !isset($banner['error']) && isset($banner['banner_id']) )
        {
            cmac_log('Banner received');
            $ret_html = '';

            /*
              if(get_option('acs_div_wrapper','0')=='1')
              $ret_html .= '<div class="'.get_option('acs_class_name','').'"';

              if(trim(get_option('acs_class_name',''))!==''&&trim(get_option('acs_custom_css',''))!='')
              $ret_html .= ' style="'.get_option('acs_custom_css').'"';

              $ret_html .= ">\n";
             */

            // Add custom css before banner
            $custom_css = get_option('acc_custom_css', '');
            if( !empty($custom_css) )
            {
                $ret_html .= "\n<!--ACC Custom CSS-->\n";
                $ret_html .= "<style>\n";
                $ret_html .= $custom_css;
                $ret_html .= "\n</style>";
                $ret_html .= "\n<!--ACC Custom CSS: END-->\n";
            }

            if( isset($args['class']) && !empty($args['class']) ) $css_class = $args['class'];
            else $css_class = null;

            if( isset($args['wrapper']) && $args['wrapper'] == '1' )
            {
                $ret_html .= '<div' . (!is_null($css_class) ? ' class="' . $css_class . '"' : '') . '>';
                $css_class = null; // not needed in other tags
            }


            $alt = (isset($banner['alt_tag']) && !empty($banner['alt_tag'])) ? ' alt="' . $banner['alt_tag'] . '"' : '';
            $title = (isset($banner['title_tag']) && !empty($banner['title_tag'])) ? ' title="' . $banner['title_tag'] . '"' : '';


            if( isset($banner['banner_link']) && ($args['linked_banner'] == '1' || !isset($args['linked_banner'])) )
            {
                $img_html .= '<img src="' . $banner['image'] . '"' . $alt . $title . ' />';
                $ret_html .= '<a href="' . $banner['banner_link'] . '" target="_blank" class="acc_banner_link ' . (!is_null($css_class) ? $css_class : '') . '">' . $img_html . '</a>';
            }
            else
            {
                $img_html .= '<img src="' . $banner['image'] . '"' . $alt . $title . '' . (!is_null($css_class) ? ' class="' . $css_class . '"' : '') . ' />';
                $ret_html .= $img_html;
            }

            /*
              if(get_option('acs_div_wrapper','0')=='1')
              $ret_html .= '</div>';
             */
            if( isset($args['wrapper']) && $args['wrapper'] == '1' ) $ret_html .= '</div>';

            $ret_html .= self::get_script($campaign_id, $banner['banner_id']);

            return $ret_html;
        }
        else cmac_log('Error response from AC_Request: ' . $banner['error']);
    }

    /**
     * Banner script
     * @return String
     * @param Int   $campaign_id  Campaign ID
     * @param Int   $banner_id Banner ID
     */
    function get_script($campaign_id, $banner_id)
    {
        $script = '
	<script>
	</script>';

        return $script;
    }

    /*
      private function curl_request($url=null,$post=''){

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_REFERER, get_bloginfo('wpurl'));
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($ch, CURLOPT_TIMEOUT, 60);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

      $data = curl_exec($ch);

      curl_close($ch);

      return $data;
      }
     */
}