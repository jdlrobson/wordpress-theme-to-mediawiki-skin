<?php

function is_child_theme() {
    return false;
}

function is_tag() {
    return false;
}

function add_editor_style(){}

function current_theme_supports( $feature, ...$args ) {
    global $_wp_theme_features;
 
    if ( 'custom-header-uploads' === $feature ) {
        return current_theme_supports( 'custom-header', 'uploads' );
    }
 
    if ( ! isset( $_wp_theme_features[ $feature ] ) ) {
        return false;
    }
 
    // If no args passed then no extra checks need be performed.
    if ( ! $args ) {
        return true;
    }
 
    switch ( $feature ) {
        case 'post-thumbnails':
            /*
             * post-thumbnails can be registered for only certain content/post types
             * by passing an array of types to add_theme_support().
             * If no array was passed, then any type is accepted.
             */
            if ( true === $_wp_theme_features[ $feature ] ) {  // Registered for all types.
                return true;
            }
            $content_type = $args[0];
            return in_array( $content_type, $_wp_theme_features[ $feature ][0], true );
 
        case 'html5':
        case 'post-formats':
            /*
             * Specific post formats can be registered by passing an array of types
             * to add_theme_support().
             *
             * Specific areas of HTML5 support *must* be passed via an array to add_theme_support().
             */
            $type = $args[0];
            return in_array( $type, $_wp_theme_features[ $feature ][0], true );
 
        case 'custom-logo':
        case 'custom-header':
        case 'custom-background':
            // Specific capabilities can be registered by passing an array to add_theme_support().
            return ( isset( $_wp_theme_features[ $feature ][0][ $args[0] ] ) && $_wp_theme_features[ $feature ][0][ $args[0] ] );
    }
 
    /**
     * Filters whether the current theme supports a specific feature.
     *
     * The dynamic portion of the hook name, `$feature`, refers to the specific
     * theme feature. See add_theme_support() for the list of possible values.
     *
     * @since 3.4.0
     *
     * @param bool   $supports Whether the current theme supports the given feature. Default true.
     * @param array  $args     Array of arguments for the feature.
     * @param string $feature  The theme feature.
     */
    return apply_filters( "current_theme_supports-{$feature}", true, $args, $_wp_theme_features[ $feature ] ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
}

function display_header_text() {
    if ( ! current_theme_supports( 'custom-header', 'header-text' ) ) {
        return false;
    }
 
    $text_color = get_theme_mod( 'header_textcolor', get_theme_support( 'custom-header', 'default-text-color' ) );
    return 'blank' !== $text_color;
}

function get_theme_file_uri( $file = '' ) {
    $file = ltrim( $file, '/' );
 
    if ( empty( $file ) ) {
        $url = get_stylesheet_directory_uri();
    } elseif ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
        $url = get_stylesheet_directory_uri() . '/' . $file;
    } else {
        $url = get_template_directory_uri() . '/' . $file;
    }
 
    /**
     * Filters the URL to a file in the theme.
     *
     * @since 4.7.0
     *
     * @param string $url  The file URL.
     * @param string $file The requested file to search for.
     */
    return apply_filters( 'theme_file_uri', $url, $file );
}

function get_theme_mod( $name, $default = false ) {
    $mods = get_theme_mods();

    // OVERRIDEs
    // disable excerpts
    if ( $name === 'ocean_blog_entry_excerpt_length' ) {
        return '500';
    } elseif ( $name === 'custom_logo' ) {
        return 'custom_logo';
    }
 
    if ( isset( $mods[ $name ] ) ) {
        /**
         * Filters the theme modification, or 'theme_mod', value.
         *
         * The dynamic portion of the hook name, `$name`, refers to the key name
         * of the modification array. For example, 'header_textcolor', 'header_image',
         * and so on depending on the theme options.
         *
         * @since 2.2.0
         *
         * @param string $current_mod The value of the current theme modification.
         */
        return apply_filters( "theme_mod_{$name}", $mods[ $name ] );
    }
 
    if ( is_string( $default ) ) {
        // Only run the replacement if an sprintf() string format pattern was found.
        if ( preg_match( '#(?<!%)%(?:\d+\$?)?s#', $default ) ) {
            // Remove a single trailing percent sign.
            $default = preg_replace( '#(?<!%)%$#', '', $default );
            $default = sprintf( $default, get_template_directory_uri(), get_stylesheet_directory_uri() );
        }
    }

    /** This filter is documented in wp-includes/theme.php */
    return apply_filters( "theme_mod_{$name}", $default );
}

function set_theme_mod( $name, $value ) {
    $mods      = get_theme_mods();
    $old_value = isset( $mods[ $name ] ) ? $mods[ $name ] : false;
 
    /**
     * Filters the theme modification, or 'theme_mod', value on save.
     *
     * The dynamic portion of the hook name, `$name`, refers to the key name
     * of the modification array. For example, 'header_textcolor', 'header_image',
     * and so on depending on the theme options.
     *
     * @since 3.9.0
     *
     * @param string $value     The new value of the theme modification.
     * @param string $old_value The current value of the theme modification.
     */
    $mods[ $name ] = apply_filters( "pre_set_theme_mod_{$name}", $value, $old_value );
 
    $theme = get_option( 'stylesheet' );
 
    return update_option( "theme_mods_$theme", $mods );
}

function add_theme_support( $feature, ...$args ) {
    global $_wp_theme_features;
 
    if ( ! $args ) {
        $args = true;
    }
 
    switch ( $feature ) {
        case 'post-thumbnails':
            // All post types are already supported.
            if ( true === get_theme_support( 'post-thumbnails' ) ) {
                return;
            }
 
            /*
             * Merge post types with any that already declared their support
             * for post thumbnails.
             */
            if ( isset( $args[0] ) && is_array( $args[0] ) && isset( $_wp_theme_features['post-thumbnails'] ) ) {
                $args[0] = array_unique( array_merge( $_wp_theme_features['post-thumbnails'][0], $args[0] ) );
            }
 
            break;
 
        case 'post-formats':
            if ( isset( $args[0] ) && is_array( $args[0] ) ) {
                $post_formats = get_post_format_slugs();
                unset( $post_formats['standard'] );
 
                $args[0] = array_intersect( $args[0], array_keys( $post_formats ) );
            } else {
                _doing_it_wrong( "add_theme_support( 'post-formats' )", __( 'You need to pass an array of post formats.' ), '5.6.0' );
                return false;
            }
            break;
 
        case 'html5':
            // You can't just pass 'html5', you need to pass an array of types.
            if ( empty( $args[0] ) ) {
                // Build an array of types for back-compat.
                $args = array( 0 => array( 'comment-list', 'comment-form', 'search-form' ) );
            } elseif ( ! isset( $args[0] ) || ! is_array( $args[0] ) ) {
                _doing_it_wrong( "add_theme_support( 'html5' )", __( 'You need to pass an array of types.' ), '3.6.1' );
                return false;
            }
 
            // Calling 'html5' again merges, rather than overwrites.
            if ( isset( $_wp_theme_features['html5'] ) ) {
                $args[0] = array_merge( $_wp_theme_features['html5'][0], $args[0] );
            }
            break;
 
        case 'custom_logo':
        case 'custom-logo':
            if ( true === $args ) {
                $args = array( 0 => array() );
            }
            $defaults = array(
                'width'                => null,
                'height'               => null,
                'flex-width'           => false,
                'flex-height'          => false,
                'header-text'          => '',
                'unlink-homepage-logo' => false,
            );
            $args[0]  = wp_parse_args( array_intersect_key( $args[0], $defaults ), $defaults );
 
            // Allow full flexibility if no size is specified.
            if ( is_null( $args[0]['width'] ) && is_null( $args[0]['height'] ) ) {
                $args[0]['flex-width']  = true;
                $args[0]['flex-height'] = true;
            }
            break;
 
        case 'custom-header-uploads':
            return add_theme_support( 'custom-header', array( 'uploads' => true ) );
 
        case 'custom-header':
            if ( true === $args ) {
                $args = array( 0 => array() );
            }
 
            $defaults = array(
                'default-image'          => '',
                'random-default'         => false,
                'width'                  => 0,
                'height'                 => 0,
                'flex-height'            => false,
                'flex-width'             => false,
                'default-text-color'     => '',
                'header-text'            => true,
                'uploads'                => true,
                'wp-head-callback'       => '',
                'admin-head-callback'    => '',
                'admin-preview-callback' => '',
                'video'                  => false,
                'video-active-callback'  => 'is_front_page',
            );
 
            $jit = isset( $args[0]['__jit'] );
            unset( $args[0]['__jit'] );
 
            // Merge in data from previous add_theme_support() calls.
            // The first value registered wins. (A child theme is set up first.)
            if ( isset( $_wp_theme_features['custom-header'] ) ) {
                $args[0] = wp_parse_args( $_wp_theme_features['custom-header'][0], $args[0] );
            }
 
            // Load in the defaults at the end, as we need to insure first one wins.
            // This will cause all constants to be defined, as each arg will then be set to the default.
            if ( $jit ) {
                $args[0] = wp_parse_args( $args[0], $defaults );
            }
 
            /*
             * If a constant was defined, use that value. Otherwise, define the constant to ensure
             * the constant is always accurate (and is not defined later,  overriding our value).
             * As stated above, the first value wins.
             * Once we get to wp_loaded (just-in-time), define any constants we haven't already.
             * Constants are lame. Don't reference them. This is just for backward compatibility.
             */
 
            if ( defined( 'NO_HEADER_TEXT' ) ) {
                $args[0]['header-text'] = ! NO_HEADER_TEXT;
            } elseif ( isset( $args[0]['header-text'] ) ) {
                define( 'NO_HEADER_TEXT', empty( $args[0]['header-text'] ) );
            }
 
            if ( defined( 'HEADER_IMAGE_WIDTH' ) ) {
                $args[0]['width'] = (int) HEADER_IMAGE_WIDTH;
            } elseif ( isset( $args[0]['width'] ) ) {
                define( 'HEADER_IMAGE_WIDTH', (int) $args[0]['width'] );
            }
 
            if ( defined( 'HEADER_IMAGE_HEIGHT' ) ) {
                $args[0]['height'] = (int) HEADER_IMAGE_HEIGHT;
            } elseif ( isset( $args[0]['height'] ) ) {
                define( 'HEADER_IMAGE_HEIGHT', (int) $args[0]['height'] );
            }
 
            if ( defined( 'HEADER_TEXTCOLOR' ) ) {
                $args[0]['default-text-color'] = HEADER_TEXTCOLOR;
            } elseif ( isset( $args[0]['default-text-color'] ) ) {
                define( 'HEADER_TEXTCOLOR', $args[0]['default-text-color'] );
            }
 
            if ( defined( 'HEADER_IMAGE' ) ) {
                $args[0]['default-image'] = HEADER_IMAGE;
            } elseif ( isset( $args[0]['default-image'] ) ) {
                define( 'HEADER_IMAGE', $args[0]['default-image'] );
            }
 
            if ( $jit && ! empty( $args[0]['default-image'] ) ) {
                $args[0]['random-default'] = false;
            }
 
            // If headers are supported, and we still don't have a defined width or height,
            // we have implicit flex sizes.
            if ( $jit ) {
                if ( empty( $args[0]['width'] ) && empty( $args[0]['flex-width'] ) ) {
                    $args[0]['flex-width'] = true;
                }
                if ( empty( $args[0]['height'] ) && empty( $args[0]['flex-height'] ) ) {
                    $args[0]['flex-height'] = true;
                }
            }
 
            break;
 
        case 'custom-background':
            if ( true === $args ) {
                $args = array( 0 => array() );
            }
 
            $defaults = array(
                'default-image'          => '',
                'default-preset'         => 'default',
                'default-position-x'     => 'left',
                'default-position-y'     => 'top',
                'default-size'           => 'auto',
                'default-repeat'         => 'repeat',
                'default-attachment'     => 'scroll',
                'default-color'          => '',
                'wp-head-callback'       => '_custom_background_cb',
                'admin-head-callback'    => '',
                'admin-preview-callback' => '',
            );
 
            $jit = isset( $args[0]['__jit'] );
            unset( $args[0]['__jit'] );
 
            // Merge in data from previous add_theme_support() calls. The first value registered wins.
            if ( isset( $_wp_theme_features['custom-background'] ) ) {
                $args[0] = wp_parse_args( $_wp_theme_features['custom-background'][0], $args[0] );
            }
 
            if ( $jit ) {
                $args[0] = wp_parse_args( $args[0], $defaults );
            }
 
            if ( defined( 'BACKGROUND_COLOR' ) ) {
                $args[0]['default-color'] = BACKGROUND_COLOR;
            } elseif ( isset( $args[0]['default-color'] ) || $jit ) {
                define( 'BACKGROUND_COLOR', $args[0]['default-color'] );
            }
 
            if ( defined( 'BACKGROUND_IMAGE' ) ) {
                $args[0]['default-image'] = BACKGROUND_IMAGE;
            } elseif ( isset( $args[0]['default-image'] ) || $jit ) {
                define( 'BACKGROUND_IMAGE', $args[0]['default-image'] );
            }
 
            break;
 
        // Ensure that 'title-tag' is accessible in the admin.
        case 'title-tag':
            // Can be called in functions.php but must happen before wp_loaded, i.e. not in header.php.
            if ( did_action( 'wp_loaded' ) ) {
                _doing_it_wrong(
                    "add_theme_support( 'title-tag' )",
                    sprintf(
                        /* translators: 1: title-tag, 2: wp_loaded */
                        __( 'Theme support for %1$s should be registered before the %2$s hook.' ),
                        '<code>title-tag</code>',
                        '<code>wp_loaded</code>'
                    ),
                    '4.1.0'
                );
 
                return false;
            }
    }
 
    $_wp_theme_features[ $feature ] = $args;
}

const WP_DEBUG = false;

function get_template() {
    /**
     * Filters the name of the current theme.
     *
     * @since 1.5.0
     *
     * @param string $template Current theme's directory name.
     */
    return apply_filters( 'template', get_option( 'template' ) );
}

function get_theme_file_path( $file = '' ) {
    $file = ltrim( $file, '/' );
 
    if ( empty( $file ) ) {
        $path = get_stylesheet_directory();
    } elseif ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
        $path = get_stylesheet_directory() . '/' . $file;
    } else {
        $path = get_template_directory() . '/' . $file;
    }
 
    /**
     * Filters the path to a file in the theme.
     *
     * @since 4.7.0
     *
     * @param string $path The file path.
     * @param string $file The requested file to search for.
     */
    return apply_filters( 'theme_file_path', $path, $file );
}

function wp_get_theme() {
    return new Config();
}

global $_wp_default_headers;
$_wp_default_headers = [];
function register_default_headers( $headers ) {
    global $_wp_default_headers;
 
    $_wp_default_headers = array_merge( (array) $_wp_default_headers, (array) $headers );
}

function get_theme_support( $feature, ...$args ) {
    global $_wp_theme_features;
    if ( ! isset( $_wp_theme_features[ $feature ] ) ) {
        return false;
    }
 
    if ( ! $args ) {
        return $_wp_theme_features[ $feature ];
    }
 
    switch ( $feature ) {
        case 'custom-logo':
            return true;
        case 'custom-header':
        case 'custom-background':
            if ( isset( $_wp_theme_features[ $feature ][0][ $args[0] ] ) ) {
                return $_wp_theme_features[ $feature ][0][ $args[0] ];
            }
            return false;
 
        default:
            return $_wp_theme_features[ $feature ];
    }
}

function get_header_textcolor() {
    return get_theme_mod( 'header_textcolor', get_theme_support( 'custom-header', 'default-text-color' ) );
}
