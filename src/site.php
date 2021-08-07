<?php

function is_search() {
    return false; // for now no special search treatment.
}

function is_home() {
    return true; // for now no special homepage treatment.
}

function is_archive() {
    return false; // not a mediawiki concept.
}

function body_class() {
    // MediaWiki skins do not need to worry about the body tag.
    return '';
}

function get_bloginfo( string $show = '', string $filter = 'raw' ) {
    switch( $show ) {
        case 'version':
            return '6.0.0';
        case 'name':
            // ‘name’ – Site title (set in Settings > General)
            return '{{msg-sitetitle}}';
        case 'description':
            // Site tagline (set in Settings > General)
            return '{{msg-tagline}}';
        /*
        ‘wpurl’ – The WordPress address (URL) (set in Settings > General)
        ‘url’ – The Site address (URL) (set in Settings > General)
        ‘admin_email’ – Admin email (set in Settings > General)
        ‘charset’ – The "Encoding for pages and feeds" (set in Settings > Reading)
        ‘version’ – The current WordPress version
        ‘html_type’ – The content-type (default: "text/html"). Themes and plugins can override the default value using the ‘pre_option_html_type’ filter
        ‘text_direction’ – The text direction determined by the site’s language. is_rtl() should be used instead
        ‘language’ – Language code for the current site
        ‘stylesheet_url’ – URL to the stylesheet for the active theme. An active child theme will take precedence over this value
        ‘stylesheet_directory’ – Directory path for the active theme. An active child theme will take precedence over this value
        ‘template_url’ / ‘template_directory’ – URL of the active theme’s directory. An active child theme will NOT take precedence over this value
        ‘pingback_url’ – The pingback XML-RPC file URL (xmlrpc.php)
        ‘atom_url’ – The Atom feed URL (/feed/atom)
        ‘rdf_url’ – The RDF/RSS 1.0 feed URL (/feed/rdf)
        ‘rss_url’ – The RSS 0.92 feed URL (/feed/rss)
        ‘rss2_url’ – The RSS 2.0 feed URL (/feed)
        ‘comments_atom_url’ – The comments Atom feed URL (/comments/feed)
        ‘comments_rss2_url’ – The comments RSS 2.0 feed URL (/comments/feed)
        */

        default:
            return '<!-- TODO: get_bloginfo -->';
    }
}

// https://developer.wordpress.org/reference/functions/bloginfo/
function bloginfo( string $show = '', string $filter = 'raw' ) {
    echo get_bloginfo( $show, $filter );
}

function is_404() {
    return false;
}

function is_author() {
    return false;
}

function get_the_archive_description() {
    return '<!-- TODO: get_the_archive_description -->';
}

function get_home_url( $blog_id = null, string $path = '', $scheme = null ) {
    return '{{link-mainpage}}';
}

function home_url( $path = '', $scheme = null ) {
    return get_home_url( null, $path, $scheme );
}

function get_the_archive_title() {
    return '<!-- archive title -->';
}

function is_page() {
    return true;
}

function is_front_page() {
    return false;
}

function is_admin() {
    return false;
}

function is_rtl() {
    return false;
}

function is_multisite() {
    return false;
}

function current_user_can() {
    return false;
}

function get_search_form() {
    echo '{{>WPSearch}}';
}

function has_header_image() {
    return false;
}
