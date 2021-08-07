<?php
class Config {
    public function get() {
        return '1';
    }

    public function __get( $arg ) {
        return '1';
    }
}
