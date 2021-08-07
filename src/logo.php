<?php

function get_custom_logo() {
    return '{{>Logo}}';
}

function the_custom_logo( int $blog_id = 0 ) {
    echo( get_custom_logo() );
}

function has_custom_logo() {
    return true;
}
