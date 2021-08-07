<?php

function strip_shortcodes( $content ) {
    global $shortcode_tags;
 
    if ( false === strpos( $content, '[' ) ) {
        return $content;
    }
 
    if ( empty( $shortcode_tags ) || ! is_array( $shortcode_tags ) ) {
        return $content;
    }
 
    // Find all registered tag names in $content.
    preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches );
 
    $tags_to_remove = array_keys( $shortcode_tags );
 
    /**
     * Filters the list of shortcode tags to remove from the content.
     *
     * @since 4.7.0
     *
     * @param array  $tags_to_remove Array of shortcode tags to remove.
     * @param string $content        Content shortcodes are being removed from.
     */
    $tags_to_remove = apply_filters( 'strip_shortcodes_tagnames', $tags_to_remove, $content );
 
    $tagnames = array_intersect( $tags_to_remove, $matches[1] );
 
    if ( empty( $tagnames ) ) {
        return $content;
    }
 
    $content = do_shortcodes_in_html_tags( $content, true, $tagnames );
 
    $pattern = get_shortcode_regex( $tagnames );
    $content = preg_replace_callback( "/$pattern/", 'strip_shortcode_tag', $content );
 
    // Always restore square braces so we don't break things like <!--[if IE ]>.
    $content = unescape_invalid_shortcodes( $content );
 
    return $content;
}
