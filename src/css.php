<?php
global $skin_css, $skin_assets, $skin_css_inline;

$skin_css = '';
$skin_css_inline = <<<EOT
/*** INLINE CSS FOLLOWS ***/
EOT;
$skin_assets = [];

function get_stylesheet_directory() {
    return get_template_directory_uri();
}

function get_stylesheet_directory_uri() {
    return get_stylesheet_directory();
}

function get_stylesheet_uri() {
    return get_stylesheet_directory() . '/style.css';
}

function wp_register_style( string $handle, $src, $deps = array(), string|bool|null $ver = false, string $media = 'all' ) {
    wp_enqueue_style( $handle, $src, $ver );
}

function mw_fixup( $src, $disallowDirectoryAscension = false ) {
    $rules = [
        [ "/\.search-field/", "#searchInput" ],
        [ "/\.search-submit/", ".searchButton" ],
        // Too generic?
        [ "/\.menu-item > \.menu-link/", ".mw-list-item a" ],
        [ "/\.menu-link/", ".mw-list-item a" ],
        [ "/\.menu-item/", ".mw-list-item" ],
        [ "/figure/", ".thumb" ],
        //[ "/\.post-thumbnail/", ".thumb" ],
        [ "/\.wp-caption/", " .thumbcaption" ],
        [ "/\.alignleft/", " .tleft" ],
        [ "/\.alignright/", " .tright" ],
        
        // '(./ => relative url don't match ../
        [ "/\(\.\//", "(" ],
        // Rewrite '../' to ''
        [ "/\.\.\//", "" ],

        // twentynineteen fix
        [ "/\.mw-list-item-has-children/", ".menu-item-has-children" ],
        [ "/\.mw-list-item-link-return/", ".menu-item-link-return" ]

    ];

    if ( $disallowDirectoryAscension ) {
        $rules[] = [ "/([\'\"])\.\.\//", "$1" ];
        // these will be moved to resources folder.
    }
    return do_wp_to_mw_replacement(
        $rules,
        $src
    );
}

function wp_deregister_style( $handle ) {
    global $skin_css;
    $skin_css .= '/** Request to remove '.  $handle . ' but the converter doesn not support. */';
}

function mw_extract_assets( $src, $contents ) {
    global $skin_assets;
    $dir = dirname( $src );

    $matches = [];
    $debug = false;
    preg_match_all("/url\([\'\"]?(.*)\)/Ui", $contents, $matches );
    foreach( $matches as $match ) {
        foreach( $match as $file ) {
            if ( $file && strpos( $file, 'data:' ) === false ) {
                // path is relative to $src.
                $path = explode( '?', $file )[0];
                $path = explode( '#', $path )[0];
                if ( $path ) {
                    $cleanPath = preg_replace( "/url\(['\"]?/", '', $path );
                    $cleanPath = preg_replace(
                        "/\'?\)/",
                        '',
                        $cleanPath
                    );
                    if ( str_ends_with( $cleanPath, "'" ) ) {
                        $cleanPath = substr( $cleanPath, 0, strlen( $cleanPath ) - 1 );
                    }
                    if ( str_starts_with( $cleanPath, "'" ) ) {
                        $cleanPath = substr( $cleanPath, 1 );
                    }
                    $fullpath = preg_replace(
                        '/\/\.\//',
                        '/',
                        $dir . '/' . $cleanPath
                    );
                    // Can't go back from resources.
                    $cleanPath = preg_replace(
                        '/\.\.\//',
                        '',
                        $cleanPath
                    );
                    if ( file_exists( $fullpath ) ) {
                        echo 'saved asset ' . $cleanPath . "\n";
                        $skin_assets[$cleanPath] = $fullpath;
                    } else {
                        echo 'Unable to find asset inside ' .   $cleanPath . "\n";
                    }
                }
            }
        }
    }
}

$enqueuedSkinStyles = [];

function wp_style_add_data( $name, $type, $condition ) {
    global $enqueuedSkinStyles;
    // Skip any styles that are conditional e.g. IE8 or IE11.
    if ( $type === 'conditional' && $condition ) {
        $enqueuedSkinStyles[ $name ] = '';
    }
}

function wp_enqueue_style( string $handle, string $src = '', $deps = [], string|bool|null $ver = false, string $media = 'all' ) {
    global $enqueuedSkinStyles, $skin_assets, $skin_js;
    $skin_css = '';
    // Currently no way to set external dependencies.
    if ( substr( $src, 0, 2 ) === '//' ) {
        if ( strpos( $src, 'fonts.googleapis.com' ) !== false ) {
            $external = file_get_contents( 'https:' . $src );
            $skin_css = $external . $skin_css;
        } else {
            $skin_js .= <<<EOT
var node = document.createElement('link');
node.setAttribute('rel', 'stylesheet');
node.setAttribute('href', '$src');
document.head.appendChild(node);
EOT;
        }
        return;
    }
    $skin_css .= <<<EOT
/** style: $src */
EOT;
    if ( file_exists( $src ) ) {
        $contents = file_get_contents( $src );
        mw_extract_assets( $src, $contents );
        $skin_css .= $src ? mw_fixup( $contents, true ) : '';
    } else {
        $skin_css .= "/* File $src does not exist. */";
    }
    $enqueuedSkinStyles[ $handle ] = $skin_css;
}

function get_theme_mods() {
    $theme_slug = get_option( 'stylesheet' );
    $mods       = get_option( "theme_mods_$theme_slug" );
    if ( false === $mods ) {
        $theme_name = get_option( 'current_theme' );
        if ( false === $theme_name ) {
            $theme_name = wp_get_theme()->get( 'Name' );
        }
        $mods = get_option( "mods_$theme_name" ); // Deprecated location.
        if ( is_admin() && false !== $mods ) {
            update_option( "theme_mods_$theme_slug", $mods );
            delete_option( "mods_$theme_name" );
        }
    }
    return $mods;
}

function wp_add_inline_style( $handle, $data ) {
    global $skin_css_inline;
    $skin_css_inline .= <<<EOT

/** @todo: inline style $handle */

EOT;
    $skin_css_inline .=  mw_fixup($data);
}

function mw_make_css() {
    global $skin_css, $skin_css_inline, $enqueuedSkinStyles;
    $css = <<<EOT
/* The following rules will not be needed in future */
.mw-editsection {
	font-size: 0.85em;
	margin-left: 8px;
}
.printfooter { display: none; }
.thumbinner { width: auto !important; }
@media ( max-width: 800px ) {
	.mw-parser-output table {
		display: block; width: 100% !important; box-sizing: border-box;
		overflow-y: hidden;
		overflow-x: auto;
	}
}
.entry-content h2.mw-toc-heading,
h2#mw-toc-heading {
    font-size: 1em;
}
/* Some Wordpress themes don't declare */
@media screen {
    /* body because applies to @media screen and those styles apply last. */
    section h2:after,
    section h2:before {
        display: table-row;
    }
    .mw-editsection a {
        font-size: 0.6em;
        padding: 0 8px;
        text-decoration: none;
        font-weight: normal;
    }
}
/* If wordmark present hide the next element (heading) */
.mw-wordpress-wordmark + * {
    display: none;
}
/* Reset skins expecting last modified as text only. */
.skin-last-modified:after { content: none !important; }

/* disabled content-links because a.new is not scoped */
.mw-parser-output a.new {
    text-decoration: line-through wavy;
}

EOT;
    return implode( "\n", $enqueuedSkinStyles ) . $css .
        // inline styles come last as must override everything else
        $skin_css_inline;
}
