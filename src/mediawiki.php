<?php
function mw_get_version_from_readme() {
    global $THEME_NAME;
    foreach ( [ 'readme.txt' ] as $filename ) {
        $readmepath = THEME_PATH . $THEME_NAME . '/' . $filename;
        $readme = file_get_contents($readmepath);
        if ( $readme ) {
            $matches = [];
            preg_match( '/Stable tag:\*?\*? (.*)/', $readme, $matches );
            $m = $matches[1] ?? null;
            if ( $m ) {
                return trim( $m );
            } else {
                return '<unknown>';
            }
        }
    }

    return '<unknown>';
}

// Map Wordpress thumbnail class to MediaWiki equivalent
function do_wp_to_mw_replacement( $replacements, $str ) {
    foreach ( $replacements as $replacement ) {
        $str = preg_replace( $replacement[0], $replacement[1], $str );
    }
    return $str;
}


