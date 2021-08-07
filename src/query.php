<?php

function get_query_var( string $var, mixed $default = '' ) {
    return $default;
}

function get_queried_object_id() {
    return -1;
}

function is_tax( $taxonomy = '', $term = '' ) {
    return false;
}
