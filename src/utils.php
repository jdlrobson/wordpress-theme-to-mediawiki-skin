<?php

function language_attributes() {
    return "{{{html-user-language-attributes}}}";
}

function esc_html_x( $str ) {
    return $str;
}

// https://developer.wordpress.org/reference/functions/esc_attr/
function esc_attr( $text ) {
    // Mustache will do our escaping.
    return $text ?? '';
}

function esc_url_raw( string $url, $protocols = null ) {
    // Mustache will do our escaping.
    return esc_url( $url, $protocols, 'db' );
}

// Display translated text.
function _e( string $text, string $domain = 'default' ) {
    return $text . '<!-- todo: translate -->';
}

function esc_attr_e( $str ) {
    return $str;
}
function esc_attr__( $str ) {
    return $str;
}

function esc_html( $str ) {
    return $str;
}

function esc_html_e( $str ) {
    echo htmlspecialchars( $str );
}

function esc_html__( $str ) {
    return $str;
}

function esc_url( $url, $protocols = null, $_context = 'display' ) {
    return $url;
}

function untrailingslashit( $string ) {
    return rtrim( $string, '/\\' );
}

function trailingslashit( $string ) {
    return untrailingslashit( $string ) . '/';
}

// Retrieve translated string with gettext context.
function _x(  string $text, string $context, string $domain = 'default' ) {
    return $text;
}

function _ex( string $text, string $context, string $domain = 'default' ) {
    return $text;
}

function tag_escape( $tag_name ) {
    $safe_tag = strtolower( preg_replace( '/[^a-zA-Z0-9_:]/', '', $tag_name ) );
    /**
     * Filters a string cleaned and escaped for output as an HTML tag.
     *
     * @since 2.8.0
     *
     * @param string $safe_tag The tag name after it has been escaped.
     * @param string $tag_name The text before it was escaped.
     */
    return apply_filters( 'tag_escape', $safe_tag, $tag_name );
}

function echoNewLine($str) {
    echo($str);
    echo("\r\n");
}
