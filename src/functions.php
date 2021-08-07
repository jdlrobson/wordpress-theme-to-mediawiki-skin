<?php

function __return_false() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
    return false;
}

function absint( $maybeint ) {
    return abs( (int) $maybeint );
}

class WP_List_Util {
    /**
     * The input array.
     *
     * @since 4.7.0
     * @var array
     */
    private $input = array();
 
    /**
     * The output array.
     *
     * @since 4.7.0
     * @var array
     */
    private $output = array();
 
    /**
     * Temporary arguments for sorting.
     *
     * @since 4.7.0
     * @var array
     */
    private $orderby = array();
 
    /**
     * Constructor.
     *
     * Sets the input array.
     *
     * @since 4.7.0
     *
     * @param array $input Array to perform operations on.
     */
    public function __construct( $input ) {
        $this->output = $input;
        $this->input  = $input;
    }
 
    /**
     * Returns the original input array.
     *
     * @since 4.7.0
     *
     * @return array The input array.
     */
    public function get_input() {
        return $this->input;
    }
 
    /**
     * Returns the output array.
     *
     * @since 4.7.0
     *
     * @return array The output array.
     */
    public function get_output() {
        return $this->output;
    }
 
    /**
     * Filters the list, based on a set of key => value arguments.
     *
     * Retrieves the objects from the list that match the given arguments.
     * Key represents property name, and value represents property value.
     *
     * If an object has more properties than those specified in arguments,
     * that will not disqualify it. When using the 'AND' operator,
     * any missing properties will disqualify it.
     *
     * @since 4.7.0
     *
     * @param array  $args     Optional. An array of key => value arguments to match
     *                         against each object. Default empty array.
     * @param string $operator Optional. The logical operation to perform. 'AND' means
     *                         all elements from the array must match. 'OR' means only
     *                         one element needs to match. 'NOT' means no elements may
     *                         match. Default 'AND'.
     * @return array Array of found values.
     */
    public function filter( $args = array(), $operator = 'AND' ) {
        if ( empty( $args ) ) {
            return $this->output;
        }
 
        $operator = strtoupper( $operator );
 
        if ( ! in_array( $operator, array( 'AND', 'OR', 'NOT' ), true ) ) {
            return array();
        }
 
        $count    = count( $args );
        $filtered = array();
 
        foreach ( $this->output as $key => $obj ) {
            $matched = 0;
 
            foreach ( $args as $m_key => $m_value ) {
                if ( is_array( $obj ) ) {
                    // Treat object as an array.
                    if ( array_key_exists( $m_key, $obj ) && ( $m_value == $obj[ $m_key ] ) ) {
                        $matched++;
                    }
                } elseif ( is_object( $obj ) ) {
                    // Treat object as an object.
                    if ( isset( $obj->{$m_key} ) && ( $m_value == $obj->{$m_key} ) ) {
                        $matched++;
                    }
                }
            }
 
            if ( ( 'AND' === $operator && $matched === $count )
                || ( 'OR' === $operator && $matched > 0 )
                || ( 'NOT' === $operator && 0 === $matched )
            ) {
                $filtered[ $key ] = $obj;
            }
        }
 
        $this->output = $filtered;
 
        return $this->output;
    }
 
    /**
     * Plucks a certain field out of each object in the list.
     *
     * This has the same functionality and prototype of
     * array_column() (PHP 5.5) but also supports objects.
     *
     * @since 4.7.0
     *
     * @param int|string $field     Field from the object to place instead of the entire object
     * @param int|string $index_key Optional. Field from the object to use as keys for the new array.
     *                              Default null.
     * @return array Array of found values. If `$index_key` is set, an array of found values with keys
     *               corresponding to `$index_key`. If `$index_key` is null, array keys from the original
     *               `$list` will be preserved in the results.
     */
    public function pluck( $field, $index_key = null ) {
        $newlist = array();
 
        if ( ! $index_key ) {
            /*
             * This is simple. Could at some point wrap array_column()
             * if we knew we had an array of arrays.
             */
            foreach ( $this->output as $key => $value ) {
                if ( is_object( $value ) ) {
                    $newlist[ $key ] = $value->$field;
                } else {
                    $newlist[ $key ] = $value[ $field ];
                }
            }
 
            $this->output = $newlist;
 
            return $this->output;
        }
 
        /*
         * When index_key is not set for a particular item, push the value
         * to the end of the stack. This is how array_column() behaves.
         */
        foreach ( $this->output as $value ) {
            if ( is_object( $value ) ) {
                if ( isset( $value->$index_key ) ) {
                    $newlist[ $value->$index_key ] = $value->$field;
                } else {
                    $newlist[] = $value->$field;
                }
            } else {
                if ( isset( $value[ $index_key ] ) ) {
                    $newlist[ $value[ $index_key ] ] = $value[ $field ];
                } else {
                    $newlist[] = $value[ $field ];
                }
            }
        }
 
        $this->output = $newlist;
 
        return $this->output;
    }
 
    /**
     * Sorts the list, based on one or more orderby arguments.
     *
     * @since 4.7.0
     *
     * @param string|array $orderby       Optional. Either the field name to order by or an array
     *                                    of multiple orderby fields as $orderby => $order.
     * @param string       $order         Optional. Either 'ASC' or 'DESC'. Only used if $orderby
     *                                    is a string.
     * @param bool         $preserve_keys Optional. Whether to preserve keys. Default false.
     * @return array The sorted array.
     */
    public function sort( $orderby = array(), $order = 'ASC', $preserve_keys = false ) {
        if ( empty( $orderby ) ) {
            return $this->output;
        }
 
        if ( is_string( $orderby ) ) {
            $orderby = array( $orderby => $order );
        }
 
        foreach ( $orderby as $field => $direction ) {
            $orderby[ $field ] = 'DESC' === strtoupper( $direction ) ? 'DESC' : 'ASC';
        }
 
        $this->orderby = $orderby;
 
        if ( $preserve_keys ) {
            uasort( $this->output, array( $this, 'sort_callback' ) );
        } else {
            usort( $this->output, array( $this, 'sort_callback' ) );
        }
 
        $this->orderby = array();
 
        return $this->output;
    }
 
    /**
     * Callback to sort the list by specific fields.
     *
     * @since 4.7.0
     *
     * @see WP_List_Util::sort()
     *
     * @param object|array $a One object to compare.
     * @param object|array $b The other object to compare.
     * @return int 0 if both objects equal. -1 if second object should come first, 1 otherwise.
     */
    private function sort_callback( $a, $b ) {
        if ( empty( $this->orderby ) ) {
            return 0;
        }
 
        $a = (array) $a;
        $b = (array) $b;
 
        foreach ( $this->orderby as $field => $direction ) {
            if ( ! isset( $a[ $field ] ) || ! isset( $b[ $field ] ) ) {
                continue;
            }
 
            if ( $a[ $field ] == $b[ $field ] ) {
                continue;
            }
 
            $results = 'DESC' === $direction ? array( 1, -1 ) : array( -1, 1 );
 
            if ( is_numeric( $a[ $field ] ) && is_numeric( $b[ $field ] ) ) {
                return ( $a[ $field ] < $b[ $field ] ) ? $results[0] : $results[1];
            }
 
            return 0 > strcmp( $a[ $field ], $b[ $field ] ) ? $results[0] : $results[1];
        }
 
        return 0;
    }
}

function wp_list_pluck( $list, $field, $index_key = null ) {
    $util = new WP_List_Util( $list );
 
    return $util->pluck( $field, $index_key );
}

function wp_allowed_protocols() {
    static $protocols = array();
 
    if ( empty( $protocols ) ) {
        $protocols = array( 'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'irc6', 'ircs', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'sms', 'svn', 'tel', 'fax', 'xmpp', 'webcal', 'urn' );
    }
 
    if ( ! did_action( 'wp_loaded' ) ) {
        /**
         * Filters the list of protocols allowed in HTML attributes.
         *
         * @since 3.0.0
         *
         * @param string[] $protocols Array of allowed protocols e.g. 'http', 'ftp', 'tel', and more.
         */
        $protocols = array_unique( (array) apply_filters( 'kses_allowed_protocols', $protocols ) );
    }
 
    return $protocols;
}

function _http_build_query( $data, $prefix = null, $sep = null, $key = '', $urlencode = true ) {
    $ret = array();
 
    foreach ( (array) $data as $k => $v ) {
        if ( $urlencode ) {
            $k = urlencode( $k );
        }
        if ( is_int( $k ) && null != $prefix ) {
            $k = $prefix . $k;
        }
        if ( ! empty( $key ) ) {
            $k = $key . '%5B' . $k . '%5D';
        }
        if ( null === $v ) {
            continue;
        } elseif ( false === $v ) {
            $v = '0';
        }
 
        if ( is_array( $v ) || is_object( $v ) ) {
            array_push( $ret, _http_build_query( $v, '', $sep, $k, $urlencode ) );
        } elseif ( $urlencode ) {
            array_push( $ret, $k . '=' . urlencode( $v ) );
        } else {
            array_push( $ret, $k . '=' . $v );
        }
    }
 
    if ( null === $sep ) {
        $sep = ini_get( 'arg_separator.output' );
    }
 
    return implode( $sep, $ret );
}

function build_query( $data ) {
    return _http_build_query( $data, null, '&', '', false );
}

function add_query_arg( $args ) {
    if ( is_array( $args[0] ) ) {
        if ( count( $args ) < 2 || false === $args[1] ) {
            $uri = $_SERVER['REQUEST_URI'];
        } else {
            $uri = $args[1];
        }
    } else {
        if ( count( $args ) < 3 || false === $args[2] ) {
            $uri = $_SERVER['REQUEST_URI'];
        } else {
            $uri = $args[2];
        }
    }
 
    $frag = strstr( $uri, '#' );
    if ( $frag ) {
        $uri = substr( $uri, 0, -strlen( $frag ) );
    } else {
        $frag = '';
    }
 
    if ( 0 === stripos( $uri, 'http://' ) ) {
        $protocol = 'http://';
        $uri      = substr( $uri, 7 );
    } elseif ( 0 === stripos( $uri, 'https://' ) ) {
        $protocol = 'https://';
        $uri      = substr( $uri, 8 );
    } else {
        $protocol = '';
    }
 
    if ( strpos( $uri, '?' ) !== false ) {
        list( $base, $query ) = explode( '?', $uri, 2 );
        $base                .= '?';
    } elseif ( $protocol || strpos( $uri, '=' ) === false ) {
        $base  = $uri . '?';
        $query = '';
    } else {
        $base  = '';
        $query = $uri;
    }
 
    wp_parse_str( $query, $qs );
    $qs = urlencode_deep( $qs ); // This re-URL-encodes things that were already in the query string.
    if ( is_array( $args[0] ) ) {
        foreach ( $args[0] as $k => $v ) {
            $qs[ $k ] = $v;
        }
    } else {
        $qs[ $args[0] ] = $args[1];
    }
 
    foreach ( $qs as $k => $v ) {
        if ( false === $v ) {
            unset( $qs[ $k ] );
        }
    }
 
    $ret = build_query( $qs );
    $ret = trim( $ret, '?' );
    $ret = preg_replace( '#=(&|$)#', '$1', $ret );
    $ret = $protocol . $base . $ret . $frag;
    $ret = rtrim( $ret, '?' );
    return $ret;
}

function wp_json_encode( $data, $options = 0, $depth = 512 ) {
    $json = json_encode( $data, $options, $depth );
 
    // If json_encode() was successful, no need to do more sanity checking.
    if ( false !== $json ) {
        return $json;
    }
 
    try {
        $data = _wp_json_sanity_check( $data, $depth );
    } catch ( Exception $e ) {
        return false;
    }
 
    return json_encode( $data, $options, $depth );
}
