<?php
// https://developer.wordpress.org/reference/functions/do_action/
function do_action( $hook_name, ...$arg ) {
    echo '<!-- do_action:start ' . $hook_name . '-->';
    global $wp_filter, $wp_actions, $wp_current_filter;
 
    if ( ! isset( $wp_actions[ $hook_name ] ) ) {
        $wp_actions[ $hook_name ] = 1;
    } else {
        ++$wp_actions[ $hook_name ];
    }
 
    // Do 'all' actions first.
    if ( isset( $wp_filter['all'] ) ) {
        $wp_current_filter[] = $hook_name;
        $all_args            = func_get_args(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
        _wp_call_all_hook( $all_args );
    }
 
    if ( ! isset( $wp_filter[ $hook_name ] ) ) {
        if ( isset( $wp_filter['all'] ) ) {
            array_pop( $wp_current_filter );
        }
 
        echo '<!-- do_action:end ' . $hook_name . '-->';
        return;
    }
 
    if ( ! isset( $wp_filter['all'] ) ) {
        $wp_current_filter[] = $hook_name;
    }
 
    if ( empty( $arg ) ) {
        $arg[] = '';
    } elseif ( is_array( $arg[0] ) && 1 === count( $arg[0] ) && isset( $arg[0][0] ) && is_object( $arg[0][0] ) ) {
        // Backward compatibility for PHP4-style passing of `array( &$this )` as action `$arg`.
        $arg[0] = $arg[0][0];
    }
 
    $wp_filter[ $hook_name ]->do_action( $arg );
 
    array_pop( $wp_current_filter );
    echo '<!-- do_action:end ' . $hook_name . '-->';
}

// https://developer.wordpress.org/reference/functions/add_action/
function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
    return add_filter( $hook_name, $callback, $priority, $accepted_args );
}