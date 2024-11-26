<?php

function get_the_category() {
    return [
        (object) [
            'term_id'=> ' mw-category-placeholder',
            'cat_name' => 'placeholder',
        ],
    ];
}

function get_category() {
    return '';
}
function get_category_link() {
    return '#';
}

function has_excerpt( $post = 0 ) {
    return false;
}

function edit_post_link() {
    echo '{{>EditBar}}';
}

function wp_attachment_is_image() {
    return false;
}

function get_adjacent_post() {
    return null;
}

function post_class( $class = 'post-19 post type-post status-publish format-standard hentry entry' ) {
    if ( is_array( $class ) ) {
        $class = implode( ' ', $class );
    }
    echoNewLine('class="' . $class . '"' );
}

function has_post_thumbnail() {
    return false;
}

function is_attachment() {
    return false;
}

function post_password_required() {
    return false;
}

function set_post_thumbnail_size() {}

function the_ID() {
    echo 'main-article';
}

function the_excerpt() {
    return '<!--todo excerpt -->';
}

function the_post_thumbnail() {
    return '<!--todo post-thumbnail -->';
}

function has_term( $t, $slug ) {
    switch ( $slug ) {
        case 'category':
            return true;
        default:
            return false;
    }
}
function get_the_terms() {
    return [];
}

function the_post() {
    global $renderMemory;
    // noop. Acts like a pop from array.
    $post = array_pop( $renderMemory['posts'] );
}

function  have_posts() {
    global $renderMemory;
    return count( $renderMemory['posts'] ) > 0;
}

function get_the_ID() {
    return 0;
}

function admin_url() {
    return '';
}

function get_post_mime_type() {
    return 'image/jpeg';
}

function get_post_format() {
    return 'standard';
}

class Tag {
    public $name = '<!-- TODO: tag name using domino inject template syntax around me-->';
    public $slug = 'mw-wordpress-category-cleanup';
    public $termId = 0;
    public $term_id = 0;
}

function get_tag_link( $id ) {
    return '#';
}

function get_the_tags() {
    return [
        new Tag()
    ];
}

function get_the_category_list() {
    return '{{>CategoryLinks}}';
}

function get_the_term_list() {
    return get_the_category_list();
}

function has_tag() {
    return true;
}

function get_the_tag_list() {
    return [];
}

function get_the_date() {
    return '<!-- placeholder:thedate -->';
}

function the_author_posts_link() {
    return '<a href="' . get_author_posts_url() . '">{{msg-history_short}}</a>';
}

function is_single() {
    return true;
}

function get_the_title() {
    $name = get_html_preferred_entry_point_name();
    switch ( $name ) {
        case 'singular':
        case 'index':
            return '{{{msg-sitetitle}}}';
        default:
            return '{{{html-title}}}';
    }
}

function single_post_title() {
    return '{{{html-title}}}';
}

function the_title( string $before = '', string $after = '', bool $echo = true ) {
    $t = $before . single_post_title() . $after;
    if ( $echo ) {
        echoNewLine($t);
    } else {
        return $t;
    }
}

function the_posts_pagination() {

}

function the_custom_header_markup() {
    return '<!-- TODO: the_custom_header_markup -->';
}

function comments_template() {
    echoNewLine('{{{html-after-content}}}' );
}

function the_tags( $label ) {
    echoNewLine($label . mw_the_category_plain() );
}

function mw_the_content() {
    return '<div id="siteNotice">{{{html-site-notice}}}</div>' .
    '<div id="content">{{{html-body-content}}}</div>' .
     '<div>{{{html-after-content}}}</div>';
}

function the_content() {
    // Alignwide for skins like `twentytwentyone`...?
    // Or not.. HelloElementor.
    echoNewLine(
        mw_the_content()
    );
}

function wp_get_attachment_image( $name ) {
    $html = '';
    if ( $name === 'custom_logo' ) {
        $html = '{{>Logo}}';
    }
    return '<!-- wp_get_attachment_image:' . $name . '-->' . $html;
}

function get_the_modified_time() {
    return '<!-- TODO: get_the_modified_time -->';
}

function get_the_time() {
    return '<!-- TODO: get_the_time -->';
}

function get_author_posts_url() {
    return '{{lastmodified-get_author_posts_url}}';
}

function get_the_author() {
    global $THEME_NAME;
    $key = $THEME_NAME . '-authors';
    __mediawiki_add_i18n_message( $key, 'Multiple authors' );
    return '{{msg-' . $key . '}}';
}

function get_avatar() {
    return '<!-- get_avatar -->';
}

function get_avatar_url() {
    return '{{#data-logos}}{{icon}}{{/data-logos}}';
}

function get_the_author_meta( $field = '', $user_id = false ) {
    switch ( $field ) {
        case 'description':
            return false; // disable author bio
        case 'display_name':
            return get_the_author();
        default:
         return '<!-- TODO: get_the_author_meta -->';
    }
}

class WP_Label {
    public $singular_name = '';
}
class WP_Post_Type {
    public $name = '';
    public $labels = '';
    public $has_archive = false;
    public function __construct() {
        $this->labels = new WP_Label();
    }
}

function is_post_type_archive() {
    return false;
}

// https://developer.wordpress.org/reference/functions/wp_get_post_terms/
function wp_get_post_terms() {
    return [];
}

// https://developer.wordpress.org/reference/functions/wp_reset_postdata/
function wp_reset_postdata() {

}

function get_post_types( $args = array(), $output = 'names', $operator = 'and' ) {
    global $wp_post_types;
 
    $field = ( 'names' === $output ) ? 'name' : false;
 
    return wp_filter_object_list( $wp_post_types, $args, $operator, $field );
}

function get_post_type_object( string $post_type ) {
    // (WP_Post_Type|null) WP_Post_Type object if it exists, null otherwise.
    return new WP_Post_Type();
}
function get_post_class( $class = '', $post_id = null ) {
    return [$class,  'mw-post'];
}

// f there are no shortcode tags defined, then the content will be returned without any filtering. 
function do_shortcode( string $content, bool $ignore_html = false ) {
    return $content;
}

function the_post_navigation( $args = array() ) {
    echo get_the_post_navigation( $args );
}

function get_the_post_navigation() {
    return '<!-- placeholder:get_the_post_navigation -->';
    //<nav class="navigation"><div class="nav-links"><small>' . mw_footer_license() . '</small></div>';
}

function post_type_supports() {
    return false;
}

function is_page_template( $template = '' ) {
    return false;
}

function  get_next_post( bool $in_same_term = false,  $excluded_terms = '', string $taxonomy = 'category' ) {
    return null;
}

function get_previous_post( bool $in_same_term = false, $excluded_terms = '', string $taxonomy = 'category' ) {
    return null;
}

function get_comments_number() {
    return 0;
}

function get_the_posts_pagination() {
    return get_the_post_navigation();
}

global $post;
$post = new stdClass();
$post->ID = 0;
$post->post_type = 'article';
$post->post_author = get_the_author();
$post->post_content = '{{{html-body-content}}}';


function last_modified() {
    echoNewLine(get_the_modified_date());
}

function the_posts_navigation() {
    echoNewLine('<!-- TODO: the_posts_navigation -->');
}

function the_postxxx() {
    echoNewLine('<article class="article">');
	echoNewLine('<header class="entry-header">');
	echoNewLine('<h2 class="entry-title">');
    echoNewLine('{{{html-title}}}');
    echoNewLine('</h2>');
    echoNewLine('<div class="entry-meta">');
	echoNewLine('<span class="posted-on">');
    last_modified();
    echoNewLine('</span>			</div><!-- .entry-meta -->');
	echoNewLine('</header><!-- .entry-header -->');
    echoNewLine('<div class="entry-content">');
	the_content();
	echoNewLine('</div><!-- .entry-content -->');
    echoNewLine('<footer class="entry-footer">');
    echoNewLine('<span class="cat-links">');
    the_tags('');
    echoNewLine('</span>');
    echoNewLine('</footer><!-- .entry-footer -->');
    echoNewLine('</article>');
}

function get_the_modified_date() {
    return '';
}

function get_post_type() {
    return 'post';
}

class mwpost
{
    public function __construct() {
       $this->post_parent= null;
    }
}

function get_post( $post = null, $output = null, $filter = 'raw' ) {
    $post = new mwpost();
    return $post;
}

function get_metadata( $meta_type, $object_id, $meta_key = '', $single = false ) {
    return '';
}
function get_post_meta( $post_id, $key = '', $single = false ) {
    return get_metadata( 'post', $post_id, $key, $single );
}

function is_paged() {
    return false;
}

function get_permalink() {
    # canonical-url for > 1.39, #t-permalink for older skins
    return '{{canonical-url}}#t-permalink';
}

function get_the_permalink() {
    return get_permalink();
}

function the_permalink() {
    echo get_the_permalink();
}
/// https://developer.wordpress.org/reference/functions/is_singular/
function is_singular( $post_types = '' ) {
    switch ( $post_types ) {
        case 'oceanwp_library':
        case 'elementor_library':
        case 'page':
        case 'download':
            return false;
        default:
            return true;
    }
}

function is_sticky() {
    return false;
}

function is_multi_author() {
    return true;
}

class MockWPPost {
    public $post_author = '';
    public function __construct() {
        $this->ID = 1;
        $this->post_type = 'page';
    }
}

function get_post_format_slugs() {
    return ['standard'];
}

function is_customize_preview() {
    return false;
}

function comments_open() {}

function pings_open() {
    return false;
}

function taxonomy_exists() {
    return true;
}

function mw_the_category_plain() {
    global $THEME_NAME;
    __mediawiki_add_i18n_message( $THEME_NAME . '-no-categories', 'Uncategorized' );
    return '{{>CategoryPlain}}';
}

function the_category() {
    echo mw_the_category_plain();
}
