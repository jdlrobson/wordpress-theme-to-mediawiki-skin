<?php

global $skin_messages;

$skin_messages = [];

function __mediawiki_get_i18n_key( $domain, $text ) {
    global $skin_messages;
    foreach( $skin_messages as $key => $otherText ) {
        if ( $text === $otherText ) {
            return $key;
        }
    }
    $id = (string)count( array_keys( $skin_messages ) );
    $key = $domain . '-' . $id;
    $skin_messages[ $key ] =  $text;
    return $key;
}

function __mediawiki_add_i18n_message( $key, $text ) {
    global $skin_messages;
    $skin_messages[ $key ] =  $text;
}

//  Retrieve the translation of $text.
function __( string $text, string $domain = 'default' ) {
    global $THEME_NAME;
    $matches = [];
    // some messages might rely on substitutions e.g. Astra footer.
    $res = preg_match( '/\[.*\]/', $text, $matches );
    if (
        strpos( $text, '%s' ) !== false ||
        strpos( $text, 'http' ) === 0 ||
        count( $matches ) > 0
    ) {
        return $text;
    }
    $key = __mediawiki_get_i18n_key( $domain, preg_replace( '/%s/', '', html_entity_decode( $text ) ) );
    return '{{msg-' . $key . '}}';
}

function load_theme_textdomain() {}

function esc_attr_x( $str ) {
    return html_entity_decode( $str );
}

function date_i18n() {
    return 'todo last modified date';
}
