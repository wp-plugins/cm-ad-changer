<?php

class CMAdChangerFrontend
{
    public static $calledClassName;
    protected static $instance = NULL;
    protected static $cssPath = NULL;
    protected static $jsPath = NULL;
    protected static $viewsPath = NULL;

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
        if( empty(self::$calledClassName) )
        {
            self::$calledClassName = __CLASS__;
        }

        self::$cssPath = CMAC_PLUGIN_URL . 'frontend/assets/css/';
        self::$jsPath = CMAC_PLUGIN_URL . 'frontend/assets/js/';
        self::$viewsPath = CMAC_PLUGIN_DIR . 'frontend/views/';

        add_action('wp_enqueue_scripts', array(self::$calledClassName, 'cmac_enqueue_head_check'), 1);

        add_action('wp_enqueue_scripts', array(self::$calledClassName, 'cmac_enqueue_js'));
        add_action('wp_print_styles', array(self::$calledClassName, 'cmac_enqueue_css'));
    }

    /**
     * Add tooltip stylesheet & javascript to page first
     */
    public static function cmac_enqueue_js()
    {
        if( defined('CMAC_HEAD_ENQUEUED') )
        {
            wp_enqueue_script('cm-ad-changer-scripts', self::$jsPath . 'scripts.js', array('jquery'));

            $scriptData['ajaxurl'] = admin_url('admin-ajax.php');
            wp_localize_script('cm-ad-changer-scripts', 'cmac_data', $scriptData);

            do_action('cmac_enqueue_js');
        }
    }

    /**
     * Outputs the frontend CSS
     */
    public static function cmac_enqueue_css()
    {
        /*
         * It's WP 3.3+ function
         */
        if( function_exists('wp_add_inline_style') && defined('CMAC_HEAD_ENQUEUED') )
        {
            wp_enqueue_style('cm-ad-changer-frontend', self::$cssPath . 'styles.css');

            $custom_style = '';
            $custom_css = get_option('acs_custom_css', '');

            if( !empty($custom_css) )
            {
                $custom_style = "\n<!--ACC Custom CSS-->\n";
                $custom_style .= $custom_css;
                $custom_style .= "\n<!--ACC Custom CSS: END-->\n";
            }

            wp_add_inline_style('cm-ad-changer-frontend', $custom_style);

            CMAdChangerShared::cmac_log('Inline styles added');
        }

        do_action('cmac_enqueue_css');
    }

    /**
     * Function checks if the custom CSS and Ad Changer Scripts should be enqueued
     */
    public static function cmac_enqueue_head_check()
    {
        if( defined('DOING_AJAX') && DOING_AJAX )
        {
            return;
        }

        $addCustomCssConditionsBase = array(
            'widgetDisplayed' => ACWidget::$widget_displayed
        );

        $addCustomCssConditions = apply_filters('cmac_enqueue_head_conditions', $addCustomCssConditionsBase);

        foreach($addCustomCssConditions as $key => $value)
        {
            if( $value == TRUE )
            {
                define('CMAC_HEAD_ENQUEUED', '1');
                break;
            }
        }

        if( !defined('CMAC_HEAD_ENQUEUED') )
        {
            while(have_posts()): the_post();
                $the_content = get_the_content();
                if( has_shortcode($the_content, 'cm_ad_changer') )
                {
                    define('CMAC_HEAD_ENQUEUED', '1');
                }
            endwhile;
        }
    }

}