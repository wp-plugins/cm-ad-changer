<?php
/**
 * CM Ad Changer - client class
 *
 * @author CreativeMinds (http://ad-changer.cminds.com)
 * @copyright Copyright (c) 2013, CreativeMinds
 */
CMAC_Client::instance();

class CMAC_Client
{
    public static $calledClassName;
    protected static $instance = NULL;

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
    }

    /**
     * Banner output
     * @return String
     * @param Array   $args  Shortcode arguments
     */
    public static function banner_output($args)
    {
        CMAdChangerShared::cmac_log('CMAC_Client::banner_output()');

        if( is_numeric($args) )
        {
            $args['campaign_id'] = $args;
        }

        $args = shortcode_atts(array(
            'campaign_id'   => NULL,
            'linked_banner' => 1,
            'debug'         => 0,
            'wrapper'       => 0,
            'class'         => '',
            'target_blank'  => '1'
                ), $args, 'cm_ad_changer');


        if( !$args['campaign_id'] )
        {
            return 'Wrong campaign ID';
        }

        $params = array(
            'campaign_id' => $args['campaign_id']
        );

        $target = (isset($args['target_blank']) && !empty($args['target_blank'])) ? 'target="_blank"' : '';
        $filteredParams = apply_filters('cmac_get_banner_params', $params);
        $banner = CMAC_Data::cmac_get_banner($filteredParams);

        if( isset($args['debug']) && $args['debug'] == 1 )
        {
            echo cminds_format_list($banner, 'Ad changer debug Info:', 'acc_debug');
        }

        if( !isset($banner['error']) && isset($banner['image_id']) )
        {
            CMAdChangerShared::cmac_log('Banner received');
            $ret_html = '';

            if( isset($args['class']) && !empty($args['class']) )
            {
                $css_class = $args['class'];
            }
            else
            {
                $css_class = null;
            }

            if( isset($args['wrapper']) && $args['wrapper'] == '1' )
            {
                $ret_html .= '<div' . (!is_null($css_class) ? ' class="' . $css_class . '"' : '') . '>';
                $css_class = null; // not needed in other tags
            }

            $alt = (isset($banner['alt_tag']) && !empty($banner['alt_tag'])) ? ' alt="' . $banner['alt_tag'] . '"' : '';
            $title = (isset($banner['title_tag']) && !empty($banner['title_tag'])) ? ' title="' . $banner['title_tag'] . '"' : '';
            $img_html = '';

            if( isset($banner['banner_link']) && ($args['linked_banner'] == '1' || !isset($args['linked_banner'])) )
            {
                $bannerSizeHtml = '';
                $bannerSize = getimagesize($banner['image']);
                if( isset($bannerSize[3]) )
                {
                    $bannerSizeHtml = $bannerSize[3];
                }

                $dataHtml = '';
                $data['banner_id'] = $banner['image_id'];
                $data['campaign_id'] = $args['campaign_id'];

                foreach($data as $key => $value)
                {
                    if( !empty($value) )
                    {
                        $dataHtml .= ' data-' . $key . '="' . $value . '"';
                    }
                }

                $img_html .= '<img src="' . $banner['image'] . '"' . $alt . $title . $bannerSizeHtml . ' />';
                $ret_html .= '<a ' . $dataHtml . ' href="' . $banner['banner_link'] . '" '.$target.' class="acc_banner_link ' . (!is_null($css_class) ? $css_class : '') . '">' . $img_html . '</a>';
            }
            else
            {
                $img_html .= '<img src="' . $banner['image'] . '"' . $alt . $title . '' . (!is_null($css_class) ? ' class="' . $css_class . '"' : '') . ' />';
                $ret_html .= $img_html;
            }

            if( isset($args['wrapper']) && $args['wrapper'] == '1' )
            {
                $ret_html .= '</div>';
            }

            return $ret_html;
        }
        else
        {
            CMAdChangerShared::cmac_log('Error response from CMAC_API: ' . $banner['error']);
        }
    }

}