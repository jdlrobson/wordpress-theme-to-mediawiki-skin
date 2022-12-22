<?php

function load_template( $_template_file, $require_once = true, $args = array() ) {
    if ( $require_once ) {
        require_once $_template_file;
    } else {
        require $_template_file;
    }
}

// https://developer.wordpress.org/reference/functions/get_template_part/
function get_template_part( string $part, string $name = null, array $args = array() ) {
    global $THEME_NAME;
    $root = dirname(__FILE__) . '/../' . THEME_PATH . $THEME_NAME . '/';
    $path1 = $root . $part . '.php';
    if ( $name ) {
        $path2 = $root . $part . '-' . $name . '.php';
        $path3 = $root . $part . '.php';
        if ( file_exists( $path2 ) ) {
            require_once( $path2 );
        } elseif ( file_exists( $path3 ) ) {
            require_once( $path3 );
        }
    } else {
        if ( file_exists( $path1 ) ) {
          require_once( $path1 );
        }
    }
}

require_once( 'themeBlock.php' );
function mw_make_template( $theme_path ) {
    $htmlthemeentrypoint = $theme_path . "/templates/page.html";
    $res = make_wp_block_template( $theme_path );
    if ( $res !== false ) {
        return $res;
    }
    // make the legacy template
    $wpindexentrypoint = $theme_path . "/index.php";
    $wppageentrypoint = $theme_path . "/single.php";

    if ( file_exists( $wppageentrypoint ) ) {
        //require_once( $wpindexentrypoint );
        ob_start();
        require_once( $wppageentrypoint );
    } else {
        ob_start();
        require_once( $wpindexentrypoint );
    }
    $content = ob_get_contents();
    ob_end_clean();
    $content = str_replace( '<body >', '<body>', $content );
    preg_match("/<body.*\/body>/s", $content, $matches);
    
    if ( $matches[0] ?? false ) {

        $content = $matches[0];
    }

    # grab the bit inside body tag
    $content = preg_replace( "/<body[^>]*>/i", "", $content);
    $content = preg_replace( "/<\/body *>/i", "", $content);

    /*if ( strpos( $content, 'data-footer.data-places.array-items' ) === false ) {
        $content = preg_replace(
            '/Powered by/',
            '<ul style="display:inline">' . mw_get_footer_places() . '</ul> Powered by',
            $content
        );
    }*/

    ob_start();
    do_action( 'wp_footer' );
    // Add the footer to the contents if applicable.
    $content .= ob_get_contents();
    ob_end_clean();

    return mw_finalize_the_template( $content );
}

function mw_finalize_the_template( $content ) {
    // Make sure an edit link is available.
    if ( strpos( $content, '{{>EditBar}}') === false ) {
        $adminBar = '{{>AdminBarWithEdit}}';
    } else {
        $adminBar = '{{>AdminBar}}';
    }

    $license = mw_footer_license();

    $icons = '<!-- footer-icons -->{{#data-footer}}' .
        '<p>{{#data-icons.array-items}}{{{html}}}{{/data-icons.array-items}}</p>' .
        '{{/data-footer}}';
    if ( strpos( $content, 'data-footer.data-places.array-items' ) === false ) {
        $content = str_replace(
            '<a href="https://wordpress.org/">',
            '{{>CompactFooter}} <a href="https://wordpress.org/">',
            $content
        );
        $content = str_replace(
            'Copyright &copy;',
            '{{>CompactFooter}}  Copyright &copy;',
            $content
        );
    } elseif ( strpos( $content, '#data-footer.data-info.array-items') === false) {

    }
    
    if ( strpos( $content, 'dashicons' ) !== false ) {
        $content .= '<link rel="stylesheet"' .
            'href="https://developer.wordpress.org/wp-includes/css/dashicons.min.css?ver=6.0-alpha-52573">';
    }

    //$content = preg_replace( "/\>&copy;/", ">{{>CompactFooter}} &copy;", $content );

    return $adminBar . $content;
}