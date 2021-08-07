<?php

global $wp_query;

class WP_Query {
    public $max_num_pages = 1;
    public $post_count = 1;
    public function the_post() {
        global $post;
        return $post;
    }
    public function have_posts() {
        return true;
    }
    public function set() {}
    public function get() {}
    public function get_queried_object() {
        return [];
    }
}

$wp_query = new WP_Query();

function set_query_var( $var, $value ) {
    global $wp_query;
    $wp_query->set( $var, $value );
}

function get_queried_object() {
    global $wp_query;
    return $wp_query->get_queried_object();
}
