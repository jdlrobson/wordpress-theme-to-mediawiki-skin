<?php

global $skin_js, $skin_js_inline_bottom, $skin_js_inline_top;

$skin_js = '';
$skin_js_inline_bottom = '';
$skin_js_inline_top = '';
global $skin_js_read_files;
$skin_js_read_files = [];

function mw_get_js() {
    global $skin_js_inline_top, $skin_js_inline_bottom, $skin_js;
    return $skin_js_inline_top . $skin_js . $skin_js_inline_bottom;
}

function wp_add_inline_script( $handle, $data ) {
    global $skin_js_inline_bottom;
    $skin_js_inline_bottom .= $data;
}

function wp_localize_script( string $handle, string $object_name, array $l10n ) {
    global $skin_js_inline_top;
    $skin_js_inline_top .= 'window["' . $object_name . '"]' . ' = ' . json_encode( $l10n ) . ';';
}

function mw_wp_get_script( $path ) {
    global $skin_js_read_files;
    if ( $path === '' || substr( $path, 0, 2 ) === '//' ) {
        return '';
    }
    // Don't read files twice
    $read = $skin_js_read_files[$path] ?? false;
    if ( file_exists( $path ) && !$read ) {
        $skin_js_read_files[$path] = true;
        return do_wp_to_mw_replacement(
            [
                [
                    '/var twentytwenty = twentytwenty \|\| \{\}\;/',
                    'var twentytwenty = twentytwenty || {}; window.twentytwenty = twentytwenty;'
                ],
                //[ "/\.focus\(/", ".on('click'," ], // breaks twentytwenty
                // some themes written against older jquery version
                [ "/\.load\(/", ".on('load'," ],
                // rewrite click() to trigger('click') to make next substitution safe.
                // Needed by twentytwenty skin.
                [ "/\.click\( *\)/", ".triggerEvent('click')" ],
                [ "/\.click\(/", ".on('click'," ],
               // [ "/\.focus\(/", ".on('focus'," ],
                [ "/\.size\(\)/", ".length"]
            ],
            '(function() {' . file_get_contents( $path ). '}());'
        );
    } else {
        return '';
    }
}

function wp_register_script( $name, $path, $dependencies ) {
    global $skin_js;
    $skin_js .= mw_wp_get_script( $path );
}

function wp_enqueue_script( $name, $path = '' ) {
    global $skin_js, $skin_js_read_files;
    $skin_js .= mw_wp_get_script( $path );
}
