<?php
/**
 * Plugin Name: University of Michigan: News
 * Plugin URI: https://github.com/umdigital/umich-news/
 * Description: Display umich news related content
 * Version: 1.2
 * Author: U-M: Digital
 * Author URI: https://vpcomm.umich.edu
 */

class UmichNews
{
    static public $pluginPath;

    static private $_baseRemoteUrls = array(
        'in-the-news' => 'https://tools.vpcomm.umich.edu/apis/in-the-news/'
    );
    static private $_cacheTimeout = 1;

    static public function init()
    {
        self::$pluginPath    = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
        self::$_cacheTimeout = 60 * 60 * (self::$_cacheTimeout >= 1 ? self::$_cacheTimeout : 1);

        // UPDATER SETUP
        if( !class_exists( 'WP_GitHub_Updater' ) ) {
            include_once self::$pluginPath .'includes'. DIRECTORY_SEPARATOR .'updater.php';
        }
        if( isset( $_GET['force-check'] ) && $_GET['force-check'] && !defined( 'WP_GITHUB_FORCE_UPDATE' ) ) {
            define( 'WP_GITHUB_FORCE_UPDATE', true );
        }
        if( is_admin() ) {
            new WP_GitHub_Updater(array(
                // this is the slug of your plugin
                'slug' => plugin_basename(__FILE__),
                // this is the name of the folder your plugin lives in
                'proper_folder_name' => dirname( plugin_basename( __FILE__ ) ),
                // the github API url of your github repo
                'api_url' => 'https://api.github.com/repos/umdigital/umich-news',
                // the github raw url of your github repo
                'raw_url' => 'https://raw.githubusercontent.com/umdigital/umich-news/master',
                // the github url of your github repo
                'github_url' => 'https://github.com/umdigital/umich-news',
                 // the zip url of the github repo
                'zip_url' => 'https://github.com/umdigital/umich-news/zipball/master',
                // wether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
                'sslverify' => true,
                // which version of WordPress does your plugin require?
                'requires' => '4.0',
                // which version of WordPress is your plugin tested up to?
                'tested' => '4.9.8',
                // which file to use as the readme for the version number
                'readme' => 'README.md',
                // Access private repositories by authorizing under Appearance > Github Updates when this example plugin is installed
                'access_token' => '',
            ));
        }

        // ADD EDITOR BLOCKS
        add_action( 'init', function(){
            if( function_exists( 'register_block_type' ) ) {
                foreach( glob( __DIR__ .'/blocks/*',  GLOB_ONLYDIR ) as $block ) {
                    if( is_file( "{$block}/block.php" ) ) {
                        include_once "{$block}/block.php";
                    }
                }
            }
        });

        add_action( 'wp_enqueue_scripts', function(){
            wp_enqueue_style( 'umich-news', plugins_url('assets/umich-news.css', __FILE__ ) );
        });

        add_shortcode( 'umichnews', function( $atts ){
            $atts = shortcode_atts(array(
                'type'     => '',
                'limit'    => '3',
                'template' => 'shortcode',
                'pagevar'  => '',
            ), $atts );

            switch( $atts['type'] ) {
                case 'in-the-news':
                    break;

                default:
                    return false;
                    break;
            }

            $startNum = null;
            if( $atts['pagevar'] && isset( $_GET[ $atts['pagevar'] ] ) ) {
                $startNum = (($_GET[ $atts['pagevar'] ] - 1) * $atts['limit']) + 1;
            }

            // locate template
            $tpl = implode( DIRECTORY_SEPARATOR, array( self::$pluginPath, 'templates', $atts['type'] .'--shortcode.tpl' ) );
            $tpl = locate_template( array( 'umich-news/'. $atts['type'] .'--'. $atts['template'] .'.tpl' ), false ) ?: $tpl;

            // GET DATA
            $newsRes = self::getApi(array(
                'type'  => $atts['type'],
                'limit' => $atts['limit'],
                'start' => $startNum
            ));

            if( $newsRes ) {
                // show results
                ob_start();
                include( $tpl );
                return ob_get_clean();
            }

            return false;
        });

        // 10% chance of cleanup
        if( mt_rand( 1, 10 ) == 3 ) {
            $expires = 60 * 60 * 24 * 7; // 7 days

            $wpUpload  = wp_upload_dir();
            $cachePath = implode( DIRECTORY_SEPARATOR, array(
                $wpUpload['basedir'],
                'umich-news-cache',
                '*'
            ));

            foreach( glob( $cachePath ) as $file ) {
                if( (filemtime( $file ) + $expires) < time() ) {
                    unlink( $file );
                }
            }
        }
    }

    static public function getApi( $params )
    {
        $params = array_merge(array(
            'type'  => '',
            'limit' => 3,
            'start' => null,
            'date'  => null
        ), $params );

        if( !$params['type'] || !isset( self::$_baseRemoteUrls[ $params['type'] ] ) ) {
            return false;
        }

        // generate api request url
        $url = self::$_baseRemoteUrls[ $params['type'] ];
        $parts = parse_url( $url );
        $parts['query'] = isset( $parts['query'] ) ? $parts['query'] : '';
        parse_str( $parts['query'], $parts['query'] );

        unset( $params['type'] );
        foreach( $params as $key => $val ) {
            if( !is_null( $val ) ) {
                $parts['query'][ $key ] = $val;
            }
        }

        $parts['query'] = http_build_query( $parts['query'] );
        $parts['query'] = $parts['query'] ? '?'. $parts['query'] : '';
        $url = "{$parts['scheme']}://{$parts['host']}{$parts['path']}{$parts['query']}";

        // determine local cache path
        $wpUpload = wp_upload_dir();
        $cachePath = implode( DIRECTORY_SEPARATOR, array(
            $wpUpload['basedir'],
            'umich-news-cache',
            md5( $url ) .'.cache'
        ));

        if( !file_exists( $cachePath ) || ((@filemtime( $cachePath ) + self::$_cacheTimeout) < time()) ) {
            @touch( $cachePath );

            // get live results
            $stream = stream_context_create(array(
                'http' => array(
                    'timeout' => 1
                )
            ));
            if( $json = file_get_contents( $url, false, $stream ) ) {
                if( $res = @json_decode( $json ) ) {
                    if( property_exists( $res, 'results' ) ) {
                        // CACHE RESULTS
                        wp_mkdir_p( dirname( $cachePath ) );

                        @file_put_contents( $cachePath, $json );
                    }
                }
            }
        }

        if( $newsRes = @json_decode( file_get_contents( $cachePath ) ) ) {
            return $newsRes;
        }

        return false;
    }
}

UmichNews::init();
