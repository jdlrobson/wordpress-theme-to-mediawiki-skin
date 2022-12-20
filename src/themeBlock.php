<?php

class WP_Block_Pattern_Categories_Registry {
    public function is_registered() {
        return true;
    }
    public static function get_instance() {
        return new WP_Block_Pattern_Categories_Registry();
    }
}

$patterns = [];

function register_block_pattern( $name, $args ) {
    global $patterns;
    $patterns[$name] = $args;
}

function get_pattern( $name ) {
    global $patterns;
    return $patterns[$name] ?? null;
}

function mw_get_modern_template_pattern( $theme_path, $pattern ) {
    $pattern = get_pattern( $pattern['slug'] );
    return $pattern ?
        process_modern_wordpress_template( $theme_path, $pattern['content'] ) :
        '';
}

function mw_get_modern_template_part( $theme_path, $part ) {
    $partpath = $theme_path . '/parts/' . $part . '.html';
    if ( file_exists( $partpath ) ) {
        $c = file_get_contents( $partpath );
        return process_modern_wordpress_template( 
            $theme_path, $c
        );  
    } else {
        return '<!-- mw-wp-error:Could not find part ' . $part . '-->';
    }
}

function toCSS( $styles ) {
    return '';
}
function mw_attrs(&$args, $className ) {
    $align = $args['align'] ?? 'none';
    $className .= ' align' . $align;
    $className .= ' ' . ( $args['className'] ?? '' );
    $s = toCSS( $args['style'] ?? [] );
    unset( $args['align'] );
    unset( $args['className'] );
    return 'data-style="' . '" style="' . $s . '" class="' . $className . '"';
}

function register_block_style( $name, $css ) {
    // @todo
}

function _processMatches( $theme_path, $contents, $matches ) {
    foreach( $matches[1] as $key => $fn ) {
        $original = $matches[0][$key];
        $args = json_decode( $matches[2][$key], true );
        $replacement = '<!-- TODO:' . $fn . '-->' . $original;
        switch ( $fn ) {
            case 'template-part':
                $tagName = $args['tagName'] ?? 'div';
                $replacement = '<' . $tagName . '>';
                $replacement .= mw_get_modern_template_part( $theme_path, $args['slug'] );
                $replacement .= '</' . $tagName . '>';
                break;
            case 'post-title':
                $level = $args['level'] ?? 1;
                $replacement = '<h' . (string)$level . ' ' . mw_attrs($args, 'entry-title') . '>';
                $replacement .= get_the_title();
                $replacement .= '</h' .(string) $level . '>';
                break;
            case 'heading':
            case 'site-title':
                $replacement = '<div class="wp-block-group"><h1 ' . mw_attrs($args, 'entry-title') . '>{{{html-title-heading}}}</h1></div>';
                // @todo process CSS?
                break;
            case 'site-logo':
                $replacement = get_custom_logo();
                break;
            case 'post-content':
                $replacement = mw_the_content();
                break;
            case 'post-comments':
                $replacement = mw_the_category();
                break;
            case 'site-tagline':
                $replacement = get_bloginfo( 'description' );
                break;
            case 'gallery':
            case 'image':
            case 'social-link':
            case 'social-links':
            case 'buttons':
            case 'button':
            case 'paragraph':
            case 'group':
            case 'spacer':
            case 'separator':
            case 'page-list':
            case 'post-featured-image':
                break;
            case 'pattern':
                $replacement = mw_get_modern_template_pattern( $theme_path, $args );
                break;
            case 'navigation':
                $replacement = wp_nav_menu( [
                    'echo' => false,
                    'theme_location' => 'primary',
                ] );
                break;
            default:
                var_dump( 'exception inside _processMatches');
                var_dump( $fn . '!!');
                die;
                break;
            break;
        }
        // todo:theme.json ==> CSS
        // https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-json/
        //https://developer.wordpress.org/block-editor/how-to-guides/themes/create-block-theme/#theme-setup
        $contents = str_replace( $original, $replacement . '<!--done' . json_encode( $args ) . '-->', $contents );
    }
    return $contents;
}

function process_modern_wordpress_template( $theme_path, $contents ) {
    $matches = [];
    // do self closing tags
    preg_match_all('/\<\!\-\- wp:([^ ]*) (.*)\/-->/', $contents, $matches, PREG_PATTERN_ORDER );
    $contents = _processMatches( $theme_path, $contents, $matches );

    // do remaining tags.
    $matches = [];
    preg_match_all('/\<\!\-\- wp:([^ ]*) (.*)-->/', $contents, $matches, PREG_PATTERN_ORDER );
    $contents = _processMatches( $theme_path, $contents, $matches );

    return $contents;
}

function get_block_css_color( $selector, $data ) {
    $css = $selector . ' {';
        foreach ( $data as $key => $value ) {
            switch ( $key ) {
                case 'text':
                    $css .= 'color: ' . $value . ';';
                    break;
                case 'background':
                    if ( strpos( $value, ';' ) === 0 ) {
                        $css .= substr( $value, 1 ) . ';';
                    } else {
                        $css .= 'background-color:' . $value . ';';
                    }
                    break;
                default:
                    $css .= '/* TODO:color ' . $key . '*/';
                    break;
            }
        }
    $css .= '}';
    return $css;
}

function get_block_css_typography( $selector, $blockData ) {
    $css = $selector . '{';
        foreach ( $blockData as $key => $data ) {
            switch ( $key ) {
                case 'fontSize':
                    $css .= "font-size: $data;";
                    break;
                case 'fontFamily':
                    $css .= "font-family: $data;";
                    break;
                case 'fontWeight':
                    $css .= "font-weight: $data;";
                    break;
                case 'lineHeight':
                    $css .= "line-height: $data;";
                    break;
                case 'textTransform':
                    $css .= "text-transform: $data;";
                    break;
                default:
                    var_dump( $key );
                    var_dump( $data );
                    var_dump( 'get_block_css_typography default' );
                    die;
            }
        }
        return $css . '}';
}

function get_block_box_model_rule( $property, $data ) {
    $top = $data['top'];
    $bottom = $data['bottom'];
    $left = $data['left'];
    $right = $data['right'];
    return "$property: $top $right $bottom $left;";
}

function get_block_css_spacing( $selector, $blockData ) {
    $css = $selector . '{';
    foreach ( $blockData as $key => $data ) {
        switch ( $key ) {
            case 'blockGap':
                $css .= '--wp--style--block-gap: $data;';
                break;
            case 'padding':
                $css .= get_block_box_model_rule( 'padding', $data );
                break;
            case 'margin':
                $css .= get_block_box_model_rule( 'margin', $data );
                break;
            default:
                var_dump( $key );
                var_dump( $data );
                var_dump( 'get_block_css_spacing default' );
                die;
        }
    }
    return $css . '}';
}

function get_block_css_elements( $selector, $blockData ) {
    $css = '';
    foreach ( $blockData as $key => $data ) {
        switch ( $key ) {
            case 'link':
                $css .= get_block_css( 'a', $data );
                break;
            case 'h6':
            case 'h5':
            case 'h4':
            case 'h3':
            case 'h2':
            case 'h1':
                $css .= get_block_css( $key, $data );
                break;
            default:
                var_dump( $key );
                var_dump( $data );
                var_dump( 'get_block_css elements' );
                die;
        }
    }
    return $css;
}

function get_block_css_border( $selector, $blockData ) {
    $css = $selector . ' {';
    foreach ( $blockData as $key => $data ) {
        switch ( $key ) {
            case 'color':
                $css .= "border-color: $data;";
            case 'style':
                $css .= "border-style: $data;";
            case 'width':
                $css .= "border-width: $data;";
            case 'radius':
                $css .= "border-radius: $data;";
                break;
            default:
                var_dump( $data );
                die;
        }
    }
    return $css . '}';
}

function get_block_css( $selector, $themeData ) {
    $css = '';
    foreach ( $themeData as $key => $data ) {
        switch ( $key ) {
            case 'color':
                $css .= get_block_css_color( $selector, $data );
                wp_add_inline_style( 'theme.json:color', $css );
                break;
            case 'spacing':
                $css .= get_block_css_spacing( $selector, $data );
                break;
            case 'typography':
                $css .= get_block_css_typography( $selector, $data );
                break;
            case 'elements':
                $css .= get_block_css_elements( $selector, $data );
                break;
            case 'border':
                $css .= get_block_css_border( $selector, $data );
                break;
            case 'blocks':
                foreach ( $data as $blockKey => $blockData ) {
                    switch ( $blockKey ) {
                        case 'core/button':
                            $css .= get_block_css( '.wp-block-button__link', $blockData );
                            break;
                        default:
                            break;
                    }
                }
                break;
            default:
                var_dump( 'get_block_css default' );
                var_dump( $key );die;
                break;
        }
    }
    return $css;
}

function make_wp_block_template( $theme_path ) {
    $blockTemplateThemes = [
        $theme_path . "/templates/page.html",
        $theme_path . "/templates/index.html"
    ];
    foreach( $blockTemplateThemes as $htmlthemeentrypoint ) {
        if ( file_exists( $htmlthemeentrypoint ) ) {
            $functions_path = $theme_path . "/functions.php";
            if ( file_exists( $functions_path ) ) {
                require_once( $functions_path );
                do_action( 'init', [] );
            }


            $theme = json_decode(
                file_get_contents( $theme_path . '/theme.json' ),
                true
            );
            $css = file_get_contents( 'src/block-theme.css' );
            $css .= get_block_css( 'body', $theme['styles'] );
            $css .= get_block_css( 'body', $theme['styles'] );
            wp_add_inline_style( 'theme.json:color', $css );
            return mw_finalize_the_template(
                '<div class="is-root-container">' .
                process_modern_wordpress_template(  $theme_path, file_get_contents( $htmlthemeentrypoint ) ) .
                '</div>'
            );
        }
    }
    return false;
}