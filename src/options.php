<?php

class WP_Object_Cache {
 
    /**
     * Holds the cached objects.
     *
     * @since 2.0.0
     * @var array
     */
    private $cache = array();
 
    /**
     * The amount of times the cache data was already stored in the cache.
     *
     * @since 2.5.0
     * @var int
     */
    public $cache_hits = 0;
 
    /**
     * Amount of times the cache did not have the request in cache.
     *
     * @since 2.0.0
     * @var int
     */
    public $cache_misses = 0;
 
    /**
     * List of global cache groups.
     *
     * @since 3.0.0
     * @var array
     */
    protected $global_groups = array();
 
    /**
     * The blog prefix to prepend to keys in non-global groups.
     *
     * @since 3.5.0
     * @var string
     */
    private $blog_prefix;
 
    /**
     * Holds the value of is_multisite().
     *
     * @since 3.5.0
     * @var bool
     */
    private $multisite;
 
    /**
     * Sets up object properties; PHP 5 style constructor.
     *
     * @since 2.0.8
     */
    public function __construct() {
        $this->multisite   = is_multisite();
        $this->blog_prefix = $this->multisite ? get_current_blog_id() . ':' : '';
    }
 
    /**
     * Makes private properties readable for backward compatibility.
     *
     * @since 4.0.0
     *
     * @param string $name Property to get.
     * @return mixed Property.
     */
    public function __get( $name ) {
        return $this->$name;
    }
 
    /**
     * Makes private properties settable for backward compatibility.
     *
     * @since 4.0.0
     *
     * @param string $name  Property to set.
     * @param mixed  $value Property value.
     * @return mixed Newly-set property.
     */
    public function __set( $name, $value ) {
        return $this->$name = $value;
    }
 
    /**
     * Makes private properties checkable for backward compatibility.
     *
     * @since 4.0.0
     *
     * @param string $name Property to check if set.
     * @return bool Whether the property is set.
     */
    public function __isset( $name ) {
        return isset( $this->$name );
    }
 
    /**
     * Makes private properties un-settable for backward compatibility.
     *
     * @since 4.0.0
     *
     * @param string $name Property to unset.
     */
    public function __unset( $name ) {
        unset( $this->$name );
    }
 
    /**
     * Adds data to the cache if it doesn't already exist.
     *
     * @since 2.0.0
     *
     * @uses WP_Object_Cache::_exists() Checks to see if the cache already has data.
     * @uses WP_Object_Cache::set()     Sets the data after the checking the cache
     *                                  contents existence.
     *
     * @param int|string $key    What to call the contents in the cache.
     * @param mixed      $data   The contents to store in the cache.
     * @param string     $group  Optional. Where to group the cache contents. Default 'default'.
     * @param int        $expire Optional. When to expire the cache contents. Default 0 (no expiration).
     * @return bool True on success, false if cache key and group already exist.
     */
    public function add( $key, $data, $group = 'default', $expire = 0 ) {
        if ( wp_suspend_cache_addition() ) {
            return false;
        }
 
        if ( empty( $group ) ) {
            $group = 'default';
        }
 
        $id = $key;
        if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) ) {
            $id = $this->blog_prefix . $key;
        }
 
        if ( $this->_exists( $id, $group ) ) {
            return false;
        }
 
        return $this->set( $key, $data, $group, (int) $expire );
    }
 
    /**
     * Sets the list of global cache groups.
     *
     * @since 3.0.0
     *
     * @param string|string[] $groups List of groups that are global.
     */
    public function add_global_groups( $groups ) {
        $groups = (array) $groups;
 
        $groups              = array_fill_keys( $groups, true );
        $this->global_groups = array_merge( $this->global_groups, $groups );
    }
 
    /**
     * Decrements numeric cache item's value.
     *
     * @since 3.3.0
     *
     * @param int|string $key    The cache key to decrement.
     * @param int        $offset Optional. The amount by which to decrement the item's value. Default 1.
     * @param string     $group  Optional. The group the key is in. Default 'default'.
     * @return int|false The item's new value on success, false on failure.
     */
    public function decr( $key, $offset = 1, $group = 'default' ) {
        if ( empty( $group ) ) {
            $group = 'default';
        }
 
        if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) ) {
            $key = $this->blog_prefix . $key;
        }
 
        if ( ! $this->_exists( $key, $group ) ) {
            return false;
        }
 
        if ( ! is_numeric( $this->cache[ $group ][ $key ] ) ) {
            $this->cache[ $group ][ $key ] = 0;
        }
 
        $offset = (int) $offset;
 
        $this->cache[ $group ][ $key ] -= $offset;
 
        if ( $this->cache[ $group ][ $key ] < 0 ) {
            $this->cache[ $group ][ $key ] = 0;
        }
 
        return $this->cache[ $group ][ $key ];
    }
 
    /**
     * Removes the contents of the cache key in the group.
     *
     * If the cache key does not exist in the group, then nothing will happen.
     *
     * @since 2.0.0
     *
     * @param int|string $key        What the contents in the cache are called.
     * @param string     $group      Optional. Where the cache contents are grouped. Default 'default'.
     * @param bool       $deprecated Optional. Unused. Default false.
     * @return bool False if the contents weren't deleted and true on success.
     */
    public function delete( $key, $group = 'default', $deprecated = false ) {
        if ( empty( $group ) ) {
            $group = 'default';
        }
 
        if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) ) {
            $key = $this->blog_prefix . $key;
        }
 
        if ( ! $this->_exists( $key, $group ) ) {
            return false;
        }
 
        unset( $this->cache[ $group ][ $key ] );
        return true;
    }
 
    /**
     * Clears the object cache of all data.
     *
     * @since 2.0.0
     *
     * @return true Always returns true.
     */
    public function flush() {
        $this->cache = array();
 
        return true;
    }
 
    /**
     * Retrieves the cache contents, if it exists.
     *
     * The contents will be first attempted to be retrieved by searching by the
     * key in the cache group. If the cache is hit (success) then the contents
     * are returned.
     *
     * On failure, the number of cache misses will be incremented.
     *
     * @since 2.0.0
     *
     * @param int|string $key   The key under which the cache contents are stored.
     * @param string     $group Optional. Where the cache contents are grouped. Default 'default'.
     * @param bool       $force Optional. Unused. Whether to force an update of the local cache
     *                          from the persistent cache. Default false.
     * @param bool       $found Optional. Whether the key was found in the cache (passed by reference).
     *                          Disambiguates a return of false, a storable value. Default null.
     * @return mixed|false The cache contents on success, false on failure to retrieve contents.
     */
    public function get( $key, $group = 'default', $force = false, &$found = null ) {
        if ( empty( $group ) ) {
            $group = 'default';
        }
 
        if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) ) {
            $key = $this->blog_prefix . $key;
        }
 
        if ( $this->_exists( $key, $group ) ) {
            $found             = true;
            $this->cache_hits += 1;
            if ( is_object( $this->cache[ $group ][ $key ] ) ) {
                return clone $this->cache[ $group ][ $key ];
            } else {
                return $this->cache[ $group ][ $key ];
            }
        }
 
        $found               = false;
        $this->cache_misses += 1;
        return false;
    }
 
    /**
     * Retrieves multiple values from the cache in one call.
     *
     * @since 5.5.0
     *
     * @param array  $keys  Array of keys under which the cache contents are stored.
     * @param string $group Optional. Where the cache contents are grouped. Default 'default'.
     * @param bool   $force Optional. Whether to force an update of the local cache
     *                      from the persistent cache. Default false.
     * @return array Array of values organized into groups.
     */
    public function get_multiple( $keys, $group = 'default', $force = false ) {
        $values = array();
 
        foreach ( $keys as $key ) {
            $values[ $key ] = $this->get( $key, $group, $force );
        }
 
        return $values;
    }
 
    /**
     * Increments numeric cache item's value.
     *
     * @since 3.3.0
     *
     * @param int|string $key    The cache key to increment
     * @param int        $offset Optional. The amount by which to increment the item's value. Default 1.
     * @param string     $group  Optional. The group the key is in. Default 'default'.
     * @return int|false The item's new value on success, false on failure.
     */
    public function incr( $key, $offset = 1, $group = 'default' ) {
        if ( empty( $group ) ) {
            $group = 'default';
        }
 
        if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) ) {
            $key = $this->blog_prefix . $key;
        }
 
        if ( ! $this->_exists( $key, $group ) ) {
            return false;
        }
 
        if ( ! is_numeric( $this->cache[ $group ][ $key ] ) ) {
            $this->cache[ $group ][ $key ] = 0;
        }
 
        $offset = (int) $offset;
 
        $this->cache[ $group ][ $key ] += $offset;
 
        if ( $this->cache[ $group ][ $key ] < 0 ) {
            $this->cache[ $group ][ $key ] = 0;
        }
 
        return $this->cache[ $group ][ $key ];
    }
 
    /**
     * Replaces the contents in the cache, if contents already exist.
     *
     * @since 2.0.0
     *
     * @see WP_Object_Cache::set()
     *
     * @param int|string $key    What to call the contents in the cache.
     * @param mixed      $data   The contents to store in the cache.
     * @param string     $group  Optional. Where to group the cache contents. Default 'default'.
     * @param int        $expire Optional. When to expire the cache contents. Default 0 (no expiration).
     * @return bool False if not exists, true if contents were replaced.
     */
    public function replace( $key, $data, $group = 'default', $expire = 0 ) {
        if ( empty( $group ) ) {
            $group = 'default';
        }
 
        $id = $key;
        if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) ) {
            $id = $this->blog_prefix . $key;
        }
 
        if ( ! $this->_exists( $id, $group ) ) {
            return false;
        }
 
        return $this->set( $key, $data, $group, (int) $expire );
    }
 
    /**
     * Resets cache keys.
     *
     * @since 3.0.0
     *
     * @deprecated 3.5.0 Use switch_to_blog()
     * @see switch_to_blog()
     */
    public function reset() {
        _deprecated_function( __FUNCTION__, '3.5.0', 'switch_to_blog()' );
 
        // Clear out non-global caches since the blog ID has changed.
        foreach ( array_keys( $this->cache ) as $group ) {
            if ( ! isset( $this->global_groups[ $group ] ) ) {
                unset( $this->cache[ $group ] );
            }
        }
    }
 
    /**
     * Sets the data contents into the cache.
     *
     * The cache contents are grouped by the $group parameter followed by the
     * $key. This allows for duplicate IDs in unique groups. Therefore, naming of
     * the group should be used with care and should follow normal function
     * naming guidelines outside of core WordPress usage.
     *
     * The $expire parameter is not used, because the cache will automatically
     * expire for each time a page is accessed and PHP finishes. The method is
     * more for cache plugins which use files.
     *
     * @since 2.0.0
     *
     * @param int|string $key    What to call the contents in the cache.
     * @param mixed      $data   The contents to store in the cache.
     * @param string     $group  Optional. Where to group the cache contents. Default 'default'.
     * @param int        $expire Not Used.
     * @return true Always returns true.
     */
    public function set( $key, $data, $group = 'default', $expire = 0 ) {
        if ( empty( $group ) ) {
            $group = 'default';
        }
 
        if ( $this->multisite && ! isset( $this->global_groups[ $group ] ) ) {
            $key = $this->blog_prefix . $key;
        }
 
        if ( is_object( $data ) ) {
            $data = clone $data;
        }
 
        $this->cache[ $group ][ $key ] = $data;
        return true;
    }
 
    /**
     * Echoes the stats of the caching.
     *
     * Gives the cache hits, and cache misses. Also prints every cached group,
     * key and the data.
     *
     * @since 2.0.0
     */
    public function stats() {
        echo '<p>';
        echo "<strong>Cache Hits:</strong> {$this->cache_hits}<br />";
        echo "<strong>Cache Misses:</strong> {$this->cache_misses}<br />";
        echo '</p>';
        echo '<ul>';
        foreach ( $this->cache as $group => $cache ) {
            echo '<li><strong>Group:</strong> ' . esc_html( $group ) . ' - ( ' . number_format( strlen( serialize( $cache ) ) / KB_IN_BYTES, 2 ) . 'k )</li>';
        }
        echo '</ul>';
    }
 
    /**
     * Switches the internal blog ID.
     *
     * This changes the blog ID used to create keys in blog specific groups.
     *
     * @since 3.5.0
     *
     * @param int $blog_id Blog ID.
     */
    public function switch_to_blog( $blog_id ) {
        $blog_id           = (int) $blog_id;
        $this->blog_prefix = $this->multisite ? $blog_id . ':' : '';
    }
 
    /**
     * Serves as a utility function to determine whether a key exists in the cache.
     *
     * @since 3.4.0
     *
     * @param int|string $key   Cache key to check for existence.
     * @param string     $group Cache group for the key existence check.
     * @return bool Whether the key exists in the cache for the given group.
     */
    protected function _exists( $key, $group ) {
        return isset( $this->cache[ $group ] ) && ( isset( $this->cache[ $group ][ $key ] ) || array_key_exists( $key, $this->cache[ $group ] ) );
    }
}

global $wp_object_cache;

$wp_object_cache = new WP_Object_Cache();

function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
    global $wp_object_cache;
 
    return $wp_object_cache->get( $key, $group, $force, $found );
}

function add_option( $option, $value = '', $deprecated = '', $autoload = 'yes' ) {
    global $wpdb;
 
    if ( ! empty( $deprecated ) ) {
        _deprecated_argument( __FUNCTION__, '2.3.0' );
    }
 
    $option = trim( $option );
    if ( empty( $option ) ) {
        return false;
    }
 
    /*
     * Until a proper _deprecated_option() function can be introduced,
     * redirect requests to deprecated keys to the new, correct ones.
     */
    $deprecated_keys = array(
        'blacklist_keys'    => 'disallowed_keys',
        'comment_whitelist' => 'comment_previously_approved',
    );
 
    if ( ! wp_installing() && isset( $deprecated_keys[ $option ] ) ) {
        _deprecated_argument(
            __FUNCTION__,
            '5.5.0',
            sprintf(
                /* translators: 1: Deprecated option key, 2: New option key. */
                __( 'The "%1$s" option key has been renamed to "%2$s".' ),
                $option,
                $deprecated_keys[ $option ]
            )
        );
        return add_option( $deprecated_keys[ $option ], $value, $deprecated, $autoload );
    }
 
    wp_protect_special_option( $option );
 
    if ( is_object( $value ) ) {
        $value = clone $value;
    }
 
    $value = sanitize_option( $option, $value );
 
    // Make sure the option doesn't already exist.
    // We can check the 'notoptions' cache before we ask for a DB query.
    $notoptions = wp_cache_get( 'notoptions', 'options' );
 
    if ( ! is_array( $notoptions ) || ! isset( $notoptions[ $option ] ) ) {
        /** This filter is documented in wp-includes/option.php */
        if ( apply_filters( "default_option_{$option}", false, $option, false ) !== get_option( $option ) ) {
            return false;
        }
    }
 
    $serialized_value = maybe_serialize( $value );
    $autoload         = ( 'no' === $autoload || false === $autoload ) ? 'no' : 'yes';
 
    /**
     * Fires before an option is added.
     *
     * @since 2.9.0
     *
     * @param string $option Name of the option to add.
     * @param mixed  $value  Value of the option.
     */
    do_action( 'add_option', $option, $value );
 
    $result = $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option, $serialized_value, $autoload ) );
    if ( ! $result ) {
        return false;
    }
 
    if ( ! wp_installing() ) {
        if ( 'yes' === $autoload ) {
            $alloptions            = wp_load_alloptions( true );
            $alloptions[ $option ] = $serialized_value;
            wp_cache_set( 'alloptions', $alloptions, 'options' );
        } else {
            wp_cache_set( $option, $serialized_value, 'options' );
        }
    }
 
    // This option exists now.
    $notoptions = wp_cache_get( 'notoptions', 'options' ); // Yes, again... we need it to be fresh.
 
    if ( is_array( $notoptions ) && isset( $notoptions[ $option ] ) ) {
        unset( $notoptions[ $option ] );
        wp_cache_set( 'notoptions', $notoptions, 'options' );
    }
 
    /**
     * Fires after a specific option has been added.
     *
     * The dynamic portion of the hook name, `$option`, refers to the option name.
     *
     * @since 2.5.0 As "add_option_{$name}"
     * @since 3.0.0
     *
     * @param string $option Name of the option to add.
     * @param mixed  $value  Value of the option.
     */
    do_action( "add_option_{$option}", $option, $value );
 
    /**
     * Fires after an option has been added.
     *
     * @since 2.9.0
     *
     * @param string $option Name of the added option.
     * @param mixed  $value  Value of the option.
     */
    do_action( 'added_option', $option, $value );
 
    return true;
}

function is_serialized( $data, $strict = true ) {
    // If it isn't a string, it isn't serialized.
    if ( ! is_string( $data ) ) {
        return false;
    }
    $data = trim( $data );
    if ( 'N;' === $data ) {
        return true;
    }
    if ( strlen( $data ) < 4 ) {
        return false;
    }
    if ( ':' !== $data[1] ) {
        return false;
    }
    if ( $strict ) {
        $lastc = substr( $data, -1 );
        if ( ';' !== $lastc && '}' !== $lastc ) {
            return false;
        }
    } else {
        $semicolon = strpos( $data, ';' );
        $brace     = strpos( $data, '}' );
        // Either ; or } must exist.
        if ( false === $semicolon && false === $brace ) {
            return false;
        }
        // But neither must be in the first X characters.
        if ( false !== $semicolon && $semicolon < 3 ) {
            return false;
        }
        if ( false !== $brace && $brace < 4 ) {
            return false;
        }
    }
    $token = $data[0];
    switch ( $token ) {
        case 's':
            if ( $strict ) {
                if ( '"' !== substr( $data, -2, 1 ) ) {
                    return false;
                }
            } elseif ( false === strpos( $data, '"' ) ) {
                return false;
            }
            // Or else fall through.
        case 'a':
        case 'O':
            return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
        case 'b':
        case 'i':
        case 'd':
            $end = $strict ? '$' : '';
            return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$end/", $data );
    }
    return false;
}

function maybe_serialize( $data ) {
    if ( is_array( $data ) || is_object( $data ) ) {
        return serialize( $data );
    }
 
    /*
     * Double serialization is required for backward compatibility.
     * See https://core.trac.wordpress.org/ticket/12930
     * Also the world will end. See WP 3.6.1.
     */
    if ( is_serialized( $data, false ) ) {
        return serialize( $data );
    }
 
    return $data;
}

function sanitize_option( $option, $value ) {
    global $wpdb;
 
    $original_value = $value;
    $error          = '';
 
    switch ( $option ) {
        case 'admin_email':
        case 'new_admin_email':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                $value = sanitize_email( $value );
                if ( ! is_email( $value ) ) {
                    $error = __( 'The email address entered did not appear to be a valid email address. Please enter a valid email address.' );
                }
            }
            break;
 
        case 'thumbnail_size_w':
        case 'thumbnail_size_h':
        case 'medium_size_w':
        case 'medium_size_h':
        case 'medium_large_size_w':
        case 'medium_large_size_h':
        case 'large_size_w':
        case 'large_size_h':
        case 'mailserver_port':
        case 'comment_max_links':
        case 'page_on_front':
        case 'page_for_posts':
        case 'rss_excerpt_length':
        case 'default_category':
        case 'default_email_category':
        case 'default_link_category':
        case 'close_comments_days_old':
        case 'comments_per_page':
        case 'thread_comments_depth':
        case 'users_can_register':
        case 'start_of_week':
        case 'site_icon':
            $value = absint( $value );
            break;
 
        case 'posts_per_page':
        case 'posts_per_rss':
            $value = (int) $value;
            if ( empty( $value ) ) {
                $value = 1;
            }
            if ( $value < -1 ) {
                $value = abs( $value );
            }
            break;
 
        case 'default_ping_status':
        case 'default_comment_status':
            // Options that if not there have 0 value but need to be something like "closed".
            if ( '0' == $value || '' === $value ) {
                $value = 'closed';
            }
            break;
 
        case 'blogdescription':
        case 'blogname':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( $value !== $original_value ) {
                $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', wp_encode_emoji( $original_value ) );
            }
 
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                $value = esc_html( $value );
            }
            break;
 
        case 'blog_charset':
            $value = preg_replace( '/[^a-zA-Z0-9_-]/', '', $value ); // Strips slashes.
            break;
 
        case 'blog_public':
            // This is the value if the settings checkbox is not checked on POST. Don't rely on this.
            if ( null === $value ) {
                $value = 1;
            } else {
                $value = (int) $value;
            }
            break;
 
        case 'date_format':
        case 'time_format':
        case 'mailserver_url':
        case 'mailserver_login':
        case 'mailserver_pass':
        case 'upload_path':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                $value = strip_tags( $value );
                $value = wp_kses_data( $value );
            }
            break;
 
        case 'ping_sites':
            $value = explode( "\n", $value );
            $value = array_filter( array_map( 'trim', $value ) );
            $value = array_filter( array_map( 'esc_url_raw', $value ) );
            $value = implode( "\n", $value );
            break;
 
        case 'gmt_offset':
            $value = preg_replace( '/[^0-9:.-]/', '', $value ); // Strips slashes.
            break;
 
        case 'siteurl':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                if ( preg_match( '#http(s?)://(.+)#i', $value ) ) {
                    $value = esc_url_raw( $value );
                } else {
                    $error = __( 'The WordPress address you entered did not appear to be a valid URL. Please enter a valid URL.' );
                }
            }
            break;
 
        case 'home':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                if ( preg_match( '#http(s?)://(.+)#i', $value ) ) {
                    $value = esc_url_raw( $value );
                } else {
                    $error = __( 'The Site address you entered did not appear to be a valid URL. Please enter a valid URL.' );
                }
            }
            break;
 
        case 'WPLANG':
            $allowed = get_available_languages();
            if ( ! is_multisite() && defined( 'WPLANG' ) && '' !== WPLANG && 'en_US' !== WPLANG ) {
                $allowed[] = WPLANG;
            }
            if ( ! in_array( $value, $allowed, true ) && ! empty( $value ) ) {
                $value = get_option( $option );
            }
            break;
 
        case 'illegal_names':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                if ( ! is_array( $value ) ) {
                    $value = explode( ' ', $value );
                }
 
                $value = array_values( array_filter( array_map( 'trim', $value ) ) );
 
                if ( ! $value ) {
                    $value = '';
                }
            }
            break;
 
        case 'limited_email_domains':
        case 'banned_email_domains':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                if ( ! is_array( $value ) ) {
                    $value = explode( "\n", $value );
                }
 
                $domains = array_values( array_filter( array_map( 'trim', $value ) ) );
                $value   = array();
 
                foreach ( $domains as $domain ) {
                    if ( ! preg_match( '/(--|\.\.)/', $domain ) && preg_match( '|^([a-zA-Z0-9-\.])+$|', $domain ) ) {
                        $value[] = $domain;
                    }
                }
                if ( ! $value ) {
                    $value = '';
                }
            }
            break;
 
        case 'timezone_string':
            $allowed_zones = timezone_identifiers_list();
            if ( ! in_array( $value, $allowed_zones, true ) && ! empty( $value ) ) {
                $error = __( 'The timezone you have entered is not valid. Please select a valid timezone.' );
            }
            break;
 
        case 'permalink_structure':
        case 'category_base':
        case 'tag_base':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                $value = esc_url_raw( $value );
                $value = str_replace( 'http://', '', $value );
            }
 
            if ( 'permalink_structure' === $option && '' !== $value && ! preg_match( '/%[^\/%]+%/', $value ) ) {
                $error = sprintf(
                    /* translators: %s: Documentation URL. */
                    __( 'A structure tag is required when using custom permalinks. <a href="%s">Learn more</a>' ),
                    __( 'https://wordpress.org/support/article/using-permalinks/#choosing-your-permalink-structure' )
                );
            }
            break;
 
        case 'default_role':
            if ( ! get_role( $value ) && get_role( 'subscriber' ) ) {
                $value = 'subscriber';
            }
            break;
 
        case 'moderation_keys':
        case 'disallowed_keys':
            $value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );
            if ( is_wp_error( $value ) ) {
                $error = $value->get_error_message();
            } else {
                $value = explode( "\n", $value );
                $value = array_filter( array_map( 'trim', $value ) );
                $value = array_unique( $value );
                $value = implode( "\n", $value );
            }
            break;
    }
 
    if ( ! empty( $error ) ) {
        $value = get_option( $option );
        if ( function_exists( 'add_settings_error' ) ) {
            add_settings_error( $option, "invalid_{$option}", $error );
        }
    }
 
    /**
     * Filters an option value following sanitization.
     *
     * @since 2.3.0
     * @since 4.3.0 Added the `$original_value` parameter.
     *
     * @param string $value          The sanitized option value.
     * @param string $option         The option name.
     * @param string $original_value The original value passed to the function.
     */
    return apply_filters( "sanitize_option_{$option}", $value, $option, $original_value );
}

function update_option( $option, $value, $autoload = null ) {
    global $wpdb;
 
    $option = trim( $option );
    if ( empty( $option ) ) {
        return false;
    }
 
    /*
     * Until a proper _deprecated_option() function can be introduced,
     * redirect requests to deprecated keys to the new, correct ones.
     */
    $deprecated_keys = array(
        'blacklist_keys'    => 'disallowed_keys',
        'comment_whitelist' => 'comment_previously_approved',
    );
 
    if ( ! wp_installing() && isset( $deprecated_keys[ $option ] ) ) {
        _deprecated_argument(
            __FUNCTION__,
            '5.5.0',
            sprintf(
                /* translators: 1: Deprecated option key, 2: New option key. */
                __( 'The "%1$s" option key has been renamed to "%2$s".' ),
                $option,
                $deprecated_keys[ $option ]
            )
        );
        return update_option( $deprecated_keys[ $option ], $value, $autoload );
    }
 
    wp_protect_special_option( $option );
 
    if ( is_object( $value ) ) {
        $value = clone $value;
    }
 
    $value     = sanitize_option( $option, $value );
    $old_value = get_option( $option );
 
    /**
     * Filters a specific option before its value is (maybe) serialized and updated.
     *
     * The dynamic portion of the hook name, `$option`, refers to the option name.
     *
     * @since 2.6.0
     * @since 4.4.0 The `$option` parameter was added.
     *
     * @param mixed  $value     The new, unserialized option value.
     * @param mixed  $old_value The old option value.
     * @param string $option    Option name.
     */
    $value = apply_filters( "pre_update_option_{$option}", $value, $old_value, $option );
 
    /**
     * Filters an option before its value is (maybe) serialized and updated.
     *
     * @since 3.9.0
     *
     * @param mixed  $value     The new, unserialized option value.
     * @param string $option    Name of the option.
     * @param mixed  $old_value The old option value.
     */
    $value = apply_filters( 'pre_update_option', $value, $option, $old_value );
 
    /*
     * If the new and old values are the same, no need to update.
     *
     * Unserialized values will be adequate in most cases. If the unserialized
     * data differs, the (maybe) serialized data is checked to avoid
     * unnecessary database calls for otherwise identical object instances.
     *
     * See https://core.trac.wordpress.org/ticket/38903
     */
    if ( $value === $old_value || maybe_serialize( $value ) === maybe_serialize( $old_value ) ) {
        return false;
    }
 
    /** This filter is documented in wp-includes/option.php */
    if ( apply_filters( "default_option_{$option}", false, $option, false ) === $old_value ) {
        // Default setting for new options is 'yes'.
        if ( null === $autoload ) {
            $autoload = 'yes';
        }
 
        return add_option( $option, $value, '', $autoload );
    }
 
    $serialized_value = maybe_serialize( $value );
 
    /**
     * Fires immediately before an option value is updated.
     *
     * @since 2.9.0
     *
     * @param string $option    Name of the option to update.
     * @param mixed  $old_value The old option value.
     * @param mixed  $value     The new option value.
     */
    do_action( 'update_option', $option, $old_value, $value );
 
    $update_args = array(
        'option_value' => $serialized_value,
    );
 
    if ( null !== $autoload ) {
        $update_args['autoload'] = ( 'no' === $autoload || false === $autoload ) ? 'no' : 'yes';
    }
 
    $result = $wpdb->update( $wpdb->options, $update_args, array( 'option_name' => $option ) );
    if ( ! $result ) {
        return false;
    }
 
    $notoptions = wp_cache_get( 'notoptions', 'options' );
 
    if ( is_array( $notoptions ) && isset( $notoptions[ $option ] ) ) {
        unset( $notoptions[ $option ] );
        wp_cache_set( 'notoptions', $notoptions, 'options' );
    }
 
    if ( ! wp_installing() ) {
        $alloptions = wp_load_alloptions( true );
        if ( isset( $alloptions[ $option ] ) ) {
            $alloptions[ $option ] = $serialized_value;
            wp_cache_set( 'alloptions', $alloptions, 'options' );
        } else {
            wp_cache_set( $option, $serialized_value, 'options' );
        }
    }
 
    /**
     * Fires after the value of a specific option has been successfully updated.
     *
     * The dynamic portion of the hook name, `$option`, refers to the option name.
     *
     * @since 2.0.1
     * @since 4.4.0 The `$option` parameter was added.
     *
     * @param mixed  $old_value The old option value.
     * @param mixed  $value     The new option value.
     * @param string $option    Option name.
     */
    do_action( "update_option_{$option}", $old_value, $value, $option );
 
    /**
     * Fires after the value of an option has been successfully updated.
     *
     * @since 2.9.0
     *
     * @param string $option    Name of the updated option.
     * @param mixed  $old_value The old option value.
     * @param mixed  $value     The new option value.
     */
    do_action( 'updated_option', $option, $old_value, $value );
 
    return true;
}

// https://developer.wordpress.org/reference/functions/get_option/
function get_option( $option, $default = false ) {
    switch ( $option ) {
        case 'theme_mods_stylesheet':
            return [
                'display_excerpt_or_full_post' => 'page',
            ];
        case 'stylesheet':
            return 'stylesheet'; 
        case 'home':
            return '{{msg-sitetitle}}';
        case 'siteurl':
            return '{{link-mainpage}}';
        default:
            return $default;
    }
}
