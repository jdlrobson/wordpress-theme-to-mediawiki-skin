<?php

require_once( 'shortcodes.php' );

function wp_strip_all_tags( $string, $remove_breaks = false ) {
    $string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
    $string = strip_tags( $string );
 
    if ( $remove_breaks ) {
        $string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
    }
 
    return trim( $string );
}

function _autop_newline_preservation_helper( $matches ) {
    return str_replace( "\n", '<WPPreserveNewline />', $matches[0] );
}

function balanceTags( $text, $force = false ) {  // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
    if ( $force || (int) get_option( 'use_balanceTags' ) === 1 ) {
        return force_balance_tags( $text );
    } else {
        return $text;
    }
}

function wp_trim_words( $text, $num_words = 55, $more = null ) {
    if ( null === $more ) {
        $more = __( '&hellip;' );
    }
 
    $original_text = $text;
    $text          = wp_strip_all_tags( $text );
    $num_words     = (int) $num_words;
 
    /*
     * translators: If your word count is based on single characters (e.g. East Asian characters),
     * enter 'characters_excluding_spaces' or 'characters_including_spaces'. Otherwise, enter 'words'.
     * Do not translate into your own language.
     */
    if ( strpos( _x( 'words', 'Word count type. Do not translate!' ), 'characters' ) === 0 && preg_match( '/^utf\-?8$/i', get_option( 'blog_charset' ) ) ) {
        $text = trim( preg_replace( "/[\n\r\t ]+/", ' ', $text ), ' ' );
        preg_match_all( '/./u', $text, $words_array );
        $words_array = array_slice( $words_array[0], 0, $num_words + 1 );
        $sep         = '';
    } else {
        $words_array = preg_split( "/[\n\r\t ]+/", $text, $num_words + 1, PREG_SPLIT_NO_EMPTY );
        $sep         = ' ';
    }
 
    if ( count( $words_array ) > $num_words ) {
        array_pop( $words_array );
        $text = implode( $sep, $words_array );
        $text = $text . $more;
    } else {
        $text = implode( $sep, $words_array );
    }
 
    /**
     * Filters the text content after words have been trimmed.
     *
     * @since 3.3.0
     *
     * @param string $text          The trimmed text.
     * @param int    $num_words     The number of words to trim the text to. Default 55.
     * @param string $more          An optional string to append to the end of the trimmed text, e.g. &hellip;.
     * @param string $original_text The text before it was trimmed.
     */
    return apply_filters( 'wp_trim_words', $text, $num_words, $more, $original_text );
}

function force_balance_tags( $text ) {
    $tagstack  = array();
    $stacksize = 0;
    $tagqueue  = '';
    $newtext   = '';
    // Known single-entity/self-closing tags.
    $single_tags = array( 'area', 'base', 'basefont', 'br', 'col', 'command', 'embed', 'frame', 'hr', 'img', 'input', 'isindex', 'link', 'meta', 'param', 'source' );
    // Tags that can be immediately nested within themselves.
    $nestable_tags = array( 'blockquote', 'div', 'object', 'q', 'span' );
 
    // WP bug fix for comments - in case you REALLY meant to type '< !--'.
    $text = str_replace( '< !--', '<    !--', $text );
    // WP bug fix for LOVE <3 (and other situations with '<' before a number).
    $text = preg_replace( '#<([0-9]{1})#', '&lt;$1', $text );
 
    /**
     * Matches supported tags.
     *
     * To get the pattern as a string without the comments paste into a PHP
     * REPL like `php -a`.
     *
     * @see https://html.spec.whatwg.org/#elements-2
     * @see https://w3c.github.io/webcomponents/spec/custom/#valid-custom-element-name
     *
     * @example
     * ~# php -a
     * php > $s = [paste copied contents of expression below including parentheses];
     * php > echo $s;
     */
    $tag_pattern = (
        '#<' . // Start with an opening bracket.
        '(/?)' . // Group 1 - If it's a closing tag it'll have a leading slash.
        '(' . // Group 2 - Tag name.
            // Custom element tags have more lenient rules than HTML tag names.
            '(?:[a-z](?:[a-z0-9._]*)-(?:[a-z0-9._-]+)+)' .
                '|' .
            // Traditional tag rules approximate HTML tag names.
            '(?:[\w:]+)' .
        ')' .
        '(?:' .
            // We either immediately close the tag with its '>' and have nothing here.
            '\s*' .
            '(/?)' . // Group 3 - "attributes" for empty tag.
                '|' .
            // Or we must start with space characters to separate the tag name from the attributes (or whitespace).
            '(\s+)' . // Group 4 - Pre-attribute whitespace.
            '([^>]*)' . // Group 5 - Attributes.
        ')' .
        '>#' // End with a closing bracket.
    );
 
    while ( preg_match( $tag_pattern, $text, $regex ) ) {
        $full_match        = $regex[0];
        $has_leading_slash = ! empty( $regex[1] );
        $tag_name          = $regex[2];
        $tag               = strtolower( $tag_name );
        $is_single_tag     = in_array( $tag, $single_tags, true );
        $pre_attribute_ws  = isset( $regex[4] ) ? $regex[4] : '';
        $attributes        = trim( isset( $regex[5] ) ? $regex[5] : $regex[3] );
        $has_self_closer   = '/' === substr( $attributes, -1 );
 
        $newtext .= $tagqueue;
 
        $i = strpos( $text, $full_match );
        $l = strlen( $full_match );
 
        // Clear the shifter.
        $tagqueue = '';
        if ( $has_leading_slash ) { // End tag.
            // If too many closing tags.
            if ( $stacksize <= 0 ) {
                $tag = '';
                // Or close to be safe $tag = '/' . $tag.
 
                // If stacktop value = tag close value, then pop.
            } elseif ( $tagstack[ $stacksize - 1 ] === $tag ) { // Found closing tag.
                $tag = '</' . $tag . '>'; // Close tag.
                array_pop( $tagstack );
                $stacksize--;
            } else { // Closing tag not at top, search for it.
                for ( $j = $stacksize - 1; $j >= 0; $j-- ) {
                    if ( $tagstack[ $j ] === $tag ) {
                        // Add tag to tagqueue.
                        for ( $k = $stacksize - 1; $k >= $j; $k-- ) {
                            $tagqueue .= '</' . array_pop( $tagstack ) . '>';
                            $stacksize--;
                        }
                        break;
                    }
                }
                $tag = '';
            }
        } else { // Begin tag.
            if ( $has_self_closer ) { // If it presents itself as a self-closing tag...
                // ...but it isn't a known single-entity self-closing tag, then don't let it be treated as such
                // and immediately close it with a closing tag (the tag will encapsulate no text as a result).
                if ( ! $is_single_tag ) {
                    $attributes = trim( substr( $attributes, 0, -1 ) ) . "></$tag";
                }
            } elseif ( $is_single_tag ) { // Else if it's a known single-entity tag but it doesn't close itself, do so.
                $pre_attribute_ws = ' ';
                $attributes      .= '/';
            } else { // It's not a single-entity tag.
                // If the top of the stack is the same as the tag we want to push, close previous tag.
                if ( $stacksize > 0 && ! in_array( $tag, $nestable_tags, true ) && $tagstack[ $stacksize - 1 ] === $tag ) {
                    $tagqueue = '</' . array_pop( $tagstack ) . '>';
                    $stacksize--;
                }
                $stacksize = array_push( $tagstack, $tag );
            }
 
            // Attributes.
            if ( $has_self_closer && $is_single_tag ) {
                // We need some space - avoid <br/> and prefer <br />.
                $pre_attribute_ws = ' ';
            }
 
            $tag = '<' . $tag . $pre_attribute_ws . $attributes . '>';
            // If already queuing a close tag, then put this tag on too.
            if ( ! empty( $tagqueue ) ) {
                $tagqueue .= $tag;
                $tag       = '';
            }
        }
        $newtext .= substr( $text, 0, $i ) . $tag;
        $text     = substr( $text, $i + $l );
    }
 
    // Clear tag queue.
    $newtext .= $tagqueue;
 
    // Add remaining text.
    $newtext .= $text;
 
    while ( $x = array_pop( $tagstack ) ) {
        $newtext .= '</' . $x . '>'; // Add remaining tags to close.
    }
 
    // WP fix for the bug with HTML comments.
    $newtext = str_replace( '< !--', '<!--', $newtext );
    $newtext = str_replace( '<    !--', '< !--', $newtext );
 
    return $newtext;
}

function wpautop( string $pee, bool $br = true ) {
    $pre_tags = array();
 
    if ( trim( $pee ) === '' ) {
        return '';
    }
 
    // Just to make things a little easier, pad the end.
    $pee = $pee . "\n";
 
    /*
     * Pre tags shouldn't be touched by autop.
     * Replace pre tags with placeholders and bring them back after autop.
     */
    if ( strpos( $pee, '<pre' ) !== false ) {
        $pee_parts = explode( '</pre>', $pee );
        $last_pee  = array_pop( $pee_parts );
        $pee       = '';
        $i         = 0;
 
        foreach ( $pee_parts as $pee_part ) {
            $start = strpos( $pee_part, '<pre' );
 
            // Malformed HTML?
            if ( false === $start ) {
                $pee .= $pee_part;
                continue;
            }
 
            $name              = "<pre wp-pre-tag-$i></pre>";
            $pre_tags[ $name ] = substr( $pee_part, $start ) . '</pre>';
 
            $pee .= substr( $pee_part, 0, $start ) . $name;
            $i++;
        }
 
        $pee .= $last_pee;
    }
    // Change multiple <br>'s into two line breaks, which will turn into paragraphs.
    $pee = preg_replace( '|<br\s*/?>\s*<br\s*/?>|', "\n\n", $pee );
 
    $allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|address|math|style|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';
 
    // Add a double line break above block-level opening tags.
    $pee = preg_replace( '!(<' . $allblocks . '[\s/>])!', "\n\n$1", $pee );
 
    // Add a double line break below block-level closing tags.
    $pee = preg_replace( '!(</' . $allblocks . '>)!', "$1\n\n", $pee );
 
    // Add a double line break after hr tags, which are self closing.
    $pee = preg_replace( '!(<hr\s*?/?>)!', "$1\n\n", $pee );
 
    // Standardize newline characters to "\n".
    $pee = str_replace( array( "\r\n", "\r" ), "\n", $pee );
 
    // Find newlines in all elements and add placeholders.
    $pee = wp_replace_in_html_tags( $pee, array( "\n" => ' <!-- wpnl --> ' ) );
 
    // Collapse line breaks before and after <option> elements so they don't get autop'd.
    if ( strpos( $pee, '<option' ) !== false ) {
        $pee = preg_replace( '|\s*<option|', '<option', $pee );
        $pee = preg_replace( '|</option>\s*|', '</option>', $pee );
    }
 
    /*
     * Collapse line breaks inside <object> elements, before <param> and <embed> elements
     * so they don't get autop'd.
     */
    if ( strpos( $pee, '</object>' ) !== false ) {
        $pee = preg_replace( '|(<object[^>]*>)\s*|', '$1', $pee );
        $pee = preg_replace( '|\s*</object>|', '</object>', $pee );
        $pee = preg_replace( '%\s*(</?(?:param|embed)[^>]*>)\s*%', '$1', $pee );
    }
 
    /*
     * Collapse line breaks inside <audio> and <video> elements,
     * before and after <source> and <track> elements.
     */
    if ( strpos( $pee, '<source' ) !== false || strpos( $pee, '<track' ) !== false ) {
        $pee = preg_replace( '%([<\[](?:audio|video)[^>\]]*[>\]])\s*%', '$1', $pee );
        $pee = preg_replace( '%\s*([<\[]/(?:audio|video)[>\]])%', '$1', $pee );
        $pee = preg_replace( '%\s*(<(?:source|track)[^>]*>)\s*%', '$1', $pee );
    }
 
    // Collapse line breaks before and after <figcaption> elements.
    if ( strpos( $pee, '<figcaption' ) !== false ) {
        $pee = preg_replace( '|\s*(<figcaption[^>]*>)|', '$1', $pee );
        $pee = preg_replace( '|</figcaption>\s*|', '</figcaption>', $pee );
    }
 
    // Remove more than two contiguous line breaks.
    $pee = preg_replace( "/\n\n+/", "\n\n", $pee );
 
    // Split up the contents into an array of strings, separated by double line breaks.
    $pees = preg_split( '/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY );
 
    // Reset $pee prior to rebuilding.
    $pee = '';
 
    // Rebuild the content as a string, wrapping every bit with a <p>.
    foreach ( $pees as $tinkle ) {
        $pee .= '<p>' . trim( $tinkle, "\n" ) . "</p>\n";
    }
 
    // Under certain strange conditions it could create a P of entirely whitespace.
    $pee = preg_replace( '|<p>\s*</p>|', '', $pee );
 
    // Add a closing <p> inside <div>, <address>, or <form> tag if missing.
    $pee = preg_replace( '!<p>([^<]+)</(div|address|form)>!', '<p>$1</p></$2>', $pee );
 
    // If an opening or closing block element tag is wrapped in a <p>, unwrap it.
    $pee = preg_replace( '!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', '$1', $pee );
 
    // In some cases <li> may get wrapped in <p>, fix them.
    $pee = preg_replace( '|<p>(<li.+?)</p>|', '$1', $pee );
 
    // If a <blockquote> is wrapped with a <p>, move it inside the <blockquote>.
    $pee = preg_replace( '|<p><blockquote([^>]*)>|i', '<blockquote$1><p>', $pee );
    $pee = str_replace( '</blockquote></p>', '</p></blockquote>', $pee );
 
    // If an opening or closing block element tag is preceded by an opening <p> tag, remove it.
    $pee = preg_replace( '!<p>\s*(</?' . $allblocks . '[^>]*>)!', '$1', $pee );
 
    // If an opening or closing block element tag is followed by a closing <p> tag, remove it.
    $pee = preg_replace( '!(</?' . $allblocks . '[^>]*>)\s*</p>!', '$1', $pee );
 
    // Optionally insert line breaks.
    if ( $br ) {
        // Replace newlines that shouldn't be touched with a placeholder.
        $pee = preg_replace_callback( '/<(script|style|svg).*?<\/\\1>/s', '_autop_newline_preservation_helper', $pee );
 
        // Normalize <br>
        $pee = str_replace( array( '<br>', '<br/>' ), '<br />', $pee );
 
        // Replace any new line characters that aren't preceded by a <br /> with a <br />.
        $pee = preg_replace( '|(?<!<br />)\s*\n|', "<br />\n", $pee );
 
        // Replace newline placeholders with newlines.
        $pee = str_replace( '<WPPreserveNewline />', "\n", $pee );
    }
 
    // If a <br /> tag is after an opening or closing block tag, remove it.
    $pee = preg_replace( '!(</?' . $allblocks . '[^>]*>)\s*<br />!', '$1', $pee );
 
    // If a <br /> tag is before a subset of opening or closing block tags, remove it.
    $pee = preg_replace( '!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee );
    $pee = preg_replace( "|\n</p>$|", '</p>', $pee );
 
    // Replace placeholder <pre> tags with their original content.
    if ( ! empty( $pre_tags ) ) {
        $pee = str_replace( array_keys( $pre_tags ), array_values( $pre_tags ), $pee );
    }
 
    // Restore newlines in all elements.
    if ( false !== strpos( $pee, '<!-- wpnl -->' ) ) {
        $pee = str_replace( array( ' <!-- wpnl --> ', '<!-- wpnl -->' ), "\n", $pee );
    }
 
    return $pee;
}

function wp_replace_in_html_tags( string $haystack, array $replace_pairs ) {
    // Find all elements.
    $textarr = wp_html_split( $haystack );
    $changed = false;

    // Optimize when searching for one item.
    if ( 1 === count( $replace_pairs ) ) {
        // Extract $needle and $replace.
        foreach ( $replace_pairs as $needle => $replace ) {
        }

        // Loop through delimiters (elements) only.
        for ( $i = 1, $c = count( $textarr ); $i < $c; $i += 2 ) {
            if ( false !== strpos( $textarr[ $i ], $needle ) ) {
                $textarr[ $i ] = str_replace( $needle, $replace, $textarr[ $i ] );
                $changed       = true;
            }
        }
    } else {
        // Extract all $needles.
        $needles = array_keys( $replace_pairs );

        // Loop through delimiters (elements) only.
        for ( $i = 1, $c = count( $textarr ); $i < $c; $i += 2 ) {
            foreach ( $needles as $needle ) {
                if ( false !== strpos( $textarr[ $i ], $needle ) ) {
                    $textarr[ $i ] = strtr( $textarr[ $i ], $replace_pairs );
                    $changed       = true;
                    // After one strtr() break out of the foreach loop and look at next element.
                    break;
                }
            }
        }
    }

    if ( $changed ) {
        $haystack = implode( $textarr );
    }

    return $haystack;
}

function wp_html_split( string $input ) {
    return preg_split( get_html_split_regex(), $input, -1, PREG_SPLIT_DELIM_CAPTURE );
}

function get_html_split_regex() {
    static $regex;
 
    if ( ! isset( $regex ) ) {
        // phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound -- don't remove regex indentation
        $comments =
            '!'             // Start of comment, after the <.
            . '(?:'         // Unroll the loop: Consume everything until --> is found.
            .     '-(?!->)' // Dash not followed by end of comment.
            .     '[^\-]*+' // Consume non-dashes.
            . ')*+'         // Loop possessively.
            . '(?:-->)?';   // End of comment. If not found, match all input.
 
        $cdata =
            '!\[CDATA\['    // Start of comment, after the <.
            . '[^\]]*+'     // Consume non-].
            . '(?:'         // Unroll the loop: Consume everything until ]]> is found.
            .     '](?!]>)' // One ] not followed by end of comment.
            .     '[^\]]*+' // Consume non-].
            . ')*+'         // Loop possessively.
            . '(?:]]>)?';   // End of comment. If not found, match all input.
 
        $escaped =
            '(?='             // Is the element escaped?
            .    '!--'
            . '|'
            .    '!\[CDATA\['
            . ')'
            . '(?(?=!-)'      // If yes, which type?
            .     $comments
            . '|'
            .     $cdata
            . ')';
 
        $regex =
            '/('                // Capture the entire match.
            .     '<'           // Find start of element.
            .     '(?'          // Conditional expression follows.
            .         $escaped  // Find end of escaped element.
            .     '|'           // ...else...
            .         '[^>]*>?' // Find end of normal element.
            .     ')'
            . ')/';
        // phpcs:enable
    }
 
    return $regex;
}

function sanitize_hex_color_no_hash( $color ) {
    $color = ltrim( $color, '#' );
 
    if ( '' === $color ) {
        return '';
    }
 
    return sanitize_hex_color( '#' . $color ) ? $color : null;
}

function sanitize_html_class( $str ) {
    return $str;
}

function sanitize_text_field( $str ) {
    return $str;
}

function sanitize_hex_color( string $color ) {
    if ( '' === $color ) {
        return '';
    }
 
    // 3 or 6 hex digits, or the empty string.
    if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
        return $color;
    }
}

function map_deep( $value, $callback ) {
    if ( is_array( $value ) ) {
        foreach ( $value as $index => $item ) {
            $value[ $index ] = map_deep( $item, $callback );
        }
    } elseif ( is_object( $value ) ) {
        $object_vars = get_object_vars( $value );
        foreach ( $object_vars as $property_name => $property_value ) {
            $value->$property_name = map_deep( $property_value, $callback );
        }
    } else {
        $value = call_user_func( $callback, $value );
    }
 
    return $value;
}

function urlencode_deep( $value ) {
    return map_deep( $value, 'urlencode' );
}
