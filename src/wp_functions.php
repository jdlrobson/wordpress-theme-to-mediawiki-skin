<?php
global $wp_version;
$wp_version = '6.0.0';

function wp_style_add_data() {}
function wp_script_add_data() {}
function wp_register_script() {}
function wp_get_script_polyfill() {}
function wp_parse_str( $string, &$array ) {
    parse_str( $string, $array );
 
    /**
     * Filters the array of variables derived from a parsed string.
     *
     * @since 2.3.0
     *
     * @param array $array The array populated with variables.
     */
    $array = apply_filters( 'wp_parse_str', $array );
}

function wp_parse_args( string|array|object $args, array $defaults = array() ) {
    if ( is_object( $args ) ) {
        $parsed_args = get_object_vars( $args );
    } elseif ( is_array( $args ) ) {
        $parsed_args =& $args;
    } else {
        wp_parse_str( $args, $parsed_args );
    }
 
    if ( is_array( $defaults ) && $defaults ) {
        return array_merge( $defaults, $parsed_args );
    }
    return $parsed_args;
}

function wp_is_mobile() {
    return false;
}

function wp_installing() {
    return false;
}

function wp_protect_special_option() {}

function wp_create_nonce() {
    return '';
}

function wp_head() {
    // MediaWiki skins do not need to worry about the head.
    return '';
}
function wp_body_open() {}
function wp_footer() {}

function mw_the_category() {
    global $THEME_NAME;
    __mediawiki_add_i18n_message( $THEME_NAME . '-no-categories', 'Uncategorized' );
    return '{{#data-portlets}}' .
    '{{>CategoryPortlet}}' .
    '{{/data-portlets}}';
}

const PLACEHOLDER_CATEGORIES = '<!-- placeholder:categories -->';

// The formatted output of a list of pages.
function wp_link_pages( $args = '' ) {
    echo PLACEHOLDER_CATEGORIES;
}

function wp_login_url( $redirect = '', $force_reauth = false ) {
    return '/w/index.php?title=Special:Userlogin';
}
