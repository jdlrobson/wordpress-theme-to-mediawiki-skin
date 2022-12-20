<?php

const THEME_PATH = 'themes/';
global $THEME_NAME;

global $post;

define( 'SCRIPT_DEBUG', true );

$THEME_NAME = $argv[1] ?? false;
if ( $argv[2] ?? false ) {
    error_reporting(0);
}

if ( !$THEME_NAME ) {
    echo('Please run `php convert.php "<theme-name>"' );
    exit();
}
//"hello-elementor";
const ABSPATH = '';

$PATH = dirname(__FILE__);

require_once('src/index.php');

function get_sidebar( $name = null, $args = [] ) {
    global $THEME_NAME;
    do_action( 'get_sidebar', $name, $args );
    require_once( dirname(__FILE__) . '/' . THEME_PATH . $THEME_NAME . '/sidebar.php' );
}

function get_parent_theme_file_uri() {
    $file = ltrim( $file, '/' );
 
    if ( empty( $file ) ) {
        $url = get_template_directory_uri();
    } else {
        $url = get_template_directory_uri() . '/' . $file;
    }
 
    /**
     * Filters the URL to a file in the parent theme.
     *
     * @since 4.7.0
     *
     * @param string $url  The file URL.
     * @param string $file The requested file to search for.
     */
    return apply_filters( 'parent_theme_file_uri', $url, $file );
}

function get_template_directory() {
    global $THEME_NAME;
    return dirname(__FILE__) . '/' . THEME_PATH . $THEME_NAME . '/';
}

// https://developer.wordpress.org/reference/functions/get_template_part/
function get_template_part( string $part, string $name = null, array $args = array() ) {
    global $THEME_NAME;
    $root = dirname(__FILE__) . '/' . THEME_PATH . $THEME_NAME . '/';
    $path1 = $root . $part . '.php';
    if ( $name ) {
        $path2 = $root . $part . '-' . $name . '.php';
        $path3 = $root . $part . '.php';
        if ( file_exists( $path2 ) ) {
            require_once( $path2 );
        } elseif ( file_exists( $path3 ) ) {
            require_once( $path3 );
        }
    } else {
        require_once( $path1 );
    }
}

function get_header() {
    global $THEME_NAME;
    return require_once(THEME_PATH . $THEME_NAME . "/header.php");
}

function get_footer() {
    global $THEME_NAME;
    return require_once(THEME_PATH . $THEME_NAME . "/footer.php");
}

function get_template_directory_uri() {
    global $THEME_NAME;
    return THEME_PATH . $THEME_NAME;
}

function get_theme_root() {
    return get_template_directory_uri();
}

function get_parent_theme_file_path( $path ) {
    return  get_theme_root() . $path;
}

require_once(THEME_PATH . $THEME_NAME ."/functions.php");

# Make it!

$post = new MockWPPost();
// add style.css if it hasn't been added already.
wp_enqueue_style( 'style.css', get_stylesheet_uri() );
ini_set( 'display_errors', 1 );
do_action( 'after_setup_theme', [] );
$content = mw_make_template( THEME_PATH . $THEME_NAME );

# Save it.
$outdir = "output/" . $THEME_NAME;
if ( !is_dir( $outdir ) ){
    mkdir($outdir);
}
file_put_contents($outdir . "/skin.mustache", $content );

# do js.
do_action('wp_enqueue_scripts', []);
global $skin_assets, $skin_js_inline;

if ( !file_exists( $outdir ) ) {
    mkdir( dirname( $outdir ), 0777, true );
}
foreach( $skin_assets as $path => $fullpath ) {
    // @todo update the path
    if ( strpos( $path, '../../' ) !== false ) {
        $path = str_replace( '../', '', $path );
    }
    $newpath = $outdir . '/resources/' . $path;
    if ( !file_exists( $newpath ) ) {
        mkdir( dirname( $newpath ), 0777, true );
    }
    copy( $fullpath, $newpath );
}

file_put_contents($outdir . "/skin.css", mw_make_css() );

$wp_enqueue_scripts = do_action( 'wp_enqueue_scripts', [] );

$bodyClass = apply_filters('body_class', [], '');
file_put_contents( $outdir . '/meta.json', json_encode( [
    'bodyClasses' => array_unique( $bodyClass ),
    'version' => mw_get_version_from_readme(),
] ) );
// TODO: IN Kadence inline JS needs to come first.
// oceanwp playing up.
// However in AStra it needs to come last... (flexibility is not defined)
$js = in_array( $THEME_NAME, [ 'astra', 'oceanwp' ] ) ?
    $skin_js . $skin_js_inline :
    $skin_js_inline . $skin_js;

file_put_contents($outdir . "/skin.js", $js );

global $skin_messages;
file_put_contents($outdir . '/en.json', json_encode( $skin_messages ) );
