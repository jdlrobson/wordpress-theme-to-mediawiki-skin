<?php

$wp_query = new WP_Query();

function set_query_var( $var, $value ) {
    global $wp_query;
    $wp_query->set( $var, $value );
}

function get_queried_object() {
    global $wp_query;
    return $wp_query->get_queried_object();
}


function get_query_var( string $var, mixed $default = '' ) {
    global $wp_query;
    return $wp_query->get( $var );
}

function get_queried_object_id() {
    return -1;
}

function is_tax( $taxonomy = '', $term = '' ) {
    return false;
}
