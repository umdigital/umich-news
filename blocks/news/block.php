<?php

class UmichNews_Block_News
{
    static private $_prefix = 'umichnews-news';
    static private $_block  = 'news';

    static public function init()
    {
        $script       = null;
        $styles       = null;
        $editorStyles = null;
        $editorScript = null;

        // FRONT & BACK END JS
        if( file_exists( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'script.js' ) ) {
            $script = self::$_prefix .'--'. self::$_block .'-js';

            wp_register_script(
                $script,
                plugins_url( '/script.js', __FILE__ ),
                array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-api' ),
                filemtime( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'script.js' )
            );
        }

        // FRONT & BACKEND STYLES
        if( file_exists( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'styles.css' ) ) {
            $style = self::$_prefix .'--'. self::$_block .'-css';

            wp_register_style(
                $style,
                plugins_url( '/styles.css', __FILE__ ),
                array(),
                filemtime( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'styles.css' )
            );
        }

        // BACKEND STYLES
        if( file_exists( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'editor.css' ) ) {
            $editorStyles = self::$_prefix .'--'. self::$_block .'-ed-css';

            wp_register_style(
                $editorStyles,
                plugins_url( '/editor.css', __FILE__ ),
                array(),
                filemtime( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'editor.css' )
            );
        }

        $editorScript = self::$_prefix .'--'. self::$_block .'-ed-js';
        wp_register_script(
            $editorScript,
            plugins_url( '/editor.js', __FILE__ ),
            array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-api' ),
            filemtime( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'editor.js' )
        );

        register_block_type( __DIR__, array(
            'script'          => $script,
            'style'           => $style,
            'editor_style'    => $editorStyles,
            'editor_script'   => $editorScript,
            'render_callback' => function( $instance, $content ){
                $instance = array_merge(array(
                    'type'      => 'in-the-news',
                    'limit'     => 3,
                    'showDate'  => true,
                    'paginate'  => false,
                    'pagevar'   => null,
                    'template'  => 'default',
                    'className' => 'is-style-basic'
                ), $instance );

                $classes = array();
                $classes[] = 'wp-block-umichnews-news';
                $classes[] = $instance['className'];
                $classes[] = $instance['type'];

                $instance['className'] = implode( ' ', $classes );

                $startNum = null;
                if( $instance['paginate'] && $instance['pagevar'] && isset( $_GET[ $instance['pagevar'] ] ) ) {
                    $startNum = (($_GET[ $instance['pagevar'] ] - 1) * $instance['limit']) + 1;
                }

                $newsRes = UmichNews::getApi(array(
                    'type'  => $instance['type'],
                    'limit' => $instance['limit'],
                    'start' => $startNum,
                ));

                // locate template
                $tpl = implode( DIRECTORY_SEPARATOR, array( UmichNews::$pluginPath, 'templates', 'block--'. $instance['type'] .'--default.tpl' ) );
                $tpl = locate_template( array( 'umich-news/block--'. $instance['type'] .'--'. $instance['template'] .'.tpl' ), false ) ?: $tpl;

                ob_start();
                include( $tpl );
                return ob_get_clean();
            })
        );
    }
}
UmichNews_Block_News::init();
