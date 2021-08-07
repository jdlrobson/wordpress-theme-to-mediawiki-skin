<?php
global $wp_filter, $wp_current_filter;

$wp_filter = [];
$wp_current_filter = [];

// https://developer.wordpress.org/reference/functions/apply_filters/
function apply_filters( $hook_name, $value ) {
    global $wp_filter, $wp_current_filter;
 
    $args = func_get_args();
 
    // Do 'all' actions first.
    if ( isset( $wp_filter['all'] ) ) {
        $wp_current_filter[] = $hook_name;
        _wp_call_all_hook( $args );
    }
 
    if ( ! isset( $wp_filter[ $hook_name ] ) ) {
        if ( isset( $wp_filter['all'] ) ) {
            array_pop( $wp_current_filter );
        }
 
        return $value;
    }
 
    if ( ! isset( $wp_filter['all'] ) ) {
        $wp_current_filter[] = $hook_name;
    }
 
    // Don't pass the tag name to WP_Hook.
    array_shift( $args );
 
    $filtered = $wp_filter[ $hook_name ]->apply_filters( $value, $args );
 
    array_pop( $wp_current_filter );
 
    return $filtered;
}

function wp_filter_object_list( $list, $args = array(), $operator = 'and', $field = false ) {
    if ( ! is_array( $list ) ) {
        return array();
    }
 
    $util = new WP_List_Util( $list );
 
    $util->filter( $args, $operator );
 
    if ( $field ) {
        $util->pluck( $field );
    }
 
    return $util->get_output();
}

// https://developer.wordpress.org/reference/functions/add_filter/
function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
    global $wp_filter;
 
    if ( ! isset( $wp_filter[ $hook_name ] ) ) {
        $wp_filter[ $hook_name ] = new WP_Hook();
    }
 
    $wp_filter[ $hook_name ]->add_filter( $hook_name, $callback, $priority, $accepted_args );
 
    return true;
}

// https://developer.wordpress.org/reference/functions/remove_filter/
function remove_filter(  string $hook_name, callable $callback, int $priority = 10 ) {

}