<?php

global $skin_js;

$skin_js = '';
$skin_js_inline = '';
global $skin_js_read_files;
$skin_js_read_files = [];


function wp_add_inline_script( $handle, $data ) {
    global $skin_js, $skin_js_inline;
    $skin_js_inline .= $data;
}

function wp_localize_script( string $handle, string $object_name, array $l10n ) {
    wp_add_inline_script(
        $handle,
        'window["' . $object_name . '"]' . ' = ' . json_encode( $l10n ) . ';'
    );
}

function  wp_enqueue_script( $name, $path = '' ) {

    global $skin_js, $skin_js_read_files;
    if ( $path === '' || substr( $path, 0, 2 ) === '//' ) {
        return;
    }
    // Don't read files twice
    $read = $skin_js_read_files[$path] ?? false;
    if ( file_exists( $path ) && !$read ) {
        $skin_js_read_files[$path] = true;
        $skin_js .= do_wp_to_mw_replacement(
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
    }
}