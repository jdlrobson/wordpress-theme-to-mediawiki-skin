<?php

global $wp_query;

class WP_Query {
    private $values = [];
    public $max_num_pages = 1;
    public $post_count = 1;
    public function the_post() {
        global $post;
        return $post;
    }
    public function have_posts() {
        return false;
    }
    public function set( $name, $value ) {
        $this->values[$name] = $value;
    }
    public function get( $name) {
        return $this->values[ $name ] ?? null;
    }
    public function get_queried_object() {
        return [];
    }
}
