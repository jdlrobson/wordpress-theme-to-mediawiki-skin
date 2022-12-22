<?php
$renderMemory = [
    'theme-mod' => [],
    'theme-support' => [],
    'menus' => [],
    'posts' => [ 'Article' ],
    'hooks' => [],
];

const FOOTER_LIST_ITEM = '<li id="{{id}}" class="{{class}}">{{{html}}}</li>';

function has_nav_menu() {
    return true;
}

class WP_Widget {
    public function __construct() {
        $this->ID = 1;
    }
}

function  is_active_sidebar() {
    return true;
}

function mw_get_id( $location ) {
    return 'mw-' . $location;
}

function wp_nav_menu( $args = [] ) {
    global $renderMemory;
    $doEcho = isset( $args['echo'] ) ? $args['echo'] : true;
    $location = $args['theme_location'] ?? '';
    $menu = $args['theme_location'] ?
        get_menu_for_mediawiki($args['theme_location']) : '';

    $finalMenu = '<!-- wp_nav_menu: ' . $location . '-->';
    $containerClass = $args['container_class'] ?? '';
    $container = $args['container'] ?? true;
    $finalMenu .= $container  ? '<div class="' . $containerClass . ' mw-wp-menu">' : '';
    //$label = $renderMemory['menus'][$location] ?? '';
    //$finalMenu .= $label ?  "<h2>" . $label . "</h2>" : '';

    //<div class="menu-button-container">
		//	<button id="primary-mobile-menu" class="button" aria-controls="primary-menu-list" aria-expanded="false">
        //    <span class="dropdown-icon open">Menu				
        // <svg class="svg-icon" width="24" height="24" aria-hidden="true" role="img" focusable="false" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.5 6H19.5V7.5H4.5V6ZM4.5 12H19.5V13.5H4.5V12ZM19.5 18H4.5V19.5H19.5V18Z" fill="currentColor"></path></svg>				</span>
        //    <span class="dropdown-icon close">Close					
        // <svg class="svg-icon" width="24" height="24" aria-hidden="true" role="img" focusable="false" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 10.9394L5.53033 4.46973L4.46967 5.53039L10.9393 12.0001L4.46967 18.4697L5.53033 19.5304L12 13.0607L18.4697 19.5304L19.5303 18.4697L13.0607 12.0001L19.5303 5.53039L18.4697 4.46973L12 10.9394Z" fill="currentColor"></path></svg>				</span>
      //  </button><!-- #primary-mobile-menu -->
    //</div>
    
    $menuClass = $args['menu_class'] ?? '';
    // e.g. "<ul id="primary-menu-list" class="%2$s">%3$s</ul>"
    $ulTemplate = $args['items_wrap'] ?? '<ul id="' . $location . '-menu" class="menu %2$s">%3$s</ul>';

    $ul = str_replace(
        '%3$s', $menu,
        str_replace( '%2$s', $menuClass,
            str_replace( '%1$s', mw_get_id( $location ), $ulTemplate )
        ),
    );
    $finalMenu .= $ul;
    $finalMenu .= '<!--' . json_encode( $args ) . '-->';

    if ( $container ) {
        $finalMenu .= '</div>';
    }
    $argsStdClass = (object) $args;
    $finalMenu = apply_filters( 'wp_nav_menu', $finalMenu, $argsStdClass );

    if ( $doEcho ) {
        echoNewLine($finalMenu);
    } else {
        return $finalMenu;
    }
    // Displays a navigation menu.
    /*
    'theme_location'
    (string) Theme location to be used. Must be registered with register_nav_menu() in order to be selectable by the user.
    */
}

function mw_get_footer_places() {
    return '{{#data-footer.data-places.array-items}}'. FOOTER_LIST_ITEM . '{{/data-footer.data-places.array-items}}';
}

function get_edit_post_link() {
    return '<!-- todo: edit link -->';
}

function mw_categorywidget() {
    $c = <<<EOT
    <section class="widget widget_categories">
    <h2 class="widget-title">{{msg-categories}}</h2>
    EOT;
    $c .= mw_the_category();
    $c .= '</section>';
    return $c;
}

function echocategorywidget() {
    echo mw_categorywidget();
}

function echowidget($section) {
    echo '{{#'. $section . '}}';
    echo <<<EOT
    <!-- echowidget -->
    <section class="widget widget_{{id}} {{class}}" id="{{id}}">
    <h2 class="widget-title">{{label}}</h2>
    <ul>{{{html-items}}}</ul>
    {{{html-after-portal}}}
    </section>
    EOT;
    echo '{{/'. $section . '}}';
}


function dynamic_sidebar( $idOrName = 1 ) {
    // loads a sidebar registered via register_sidebar
    // TODO: needs to run action( 'widgets_init' );
    switch ( $idOrName ) {
        case 'sidebar-1':
            echowidget('data-portlets-sidebar.array-portlets-rest');
            break;
        case 'sidebar-2':
            echowidget('data-portlets.data-languages');
            break;
        case 'blog-sidebar':
        default:
            echo '<!-- TODO dynamic_sidebar: ' . $idOrName . '-->';
            break;
    }
    echo '<!-- dynamic_sidebar:' . $idOrName . '-->';
}

function mw_footer_license() {
    return '<!-- footer-license -->{{#data-footer}}' .
    '{{#data-info.array-items}}<span class="{{id}}">{{{html}}}</span>{{/data-info.array-items}}' .
    '{{/data-footer}}';
}

function get_menu_for_mediawiki( $key ) {
    $menu = '';
    switch ( $key ) {
        case 'mobile':
        case 'mobile_menu':
        case 'expanded':
            $menu = '{{#data-portlets-sidebar}}';
            $menu .= '{{#data-portlets-first}}{{{html-items}}}{{/data-portlets-first}}';
            $menu .= '{{#array-portlets-rest}}{{{html-items}}}{{/array-portlets-rest}}';
            $menu .= '{{/data-portlets-sidebar}}';
            break;
        case 'footer_menu':
        case 'footer':
            $menu .= '{{#data-footer.data-places.array-items}}'. FOOTER_LIST_ITEM . '{{/data-footer.data-places.array-items}}';
            $menu .= '{{#data-footer.data-icons.array-items}}'. FOOTER_LIST_ITEM . '{{/data-footer.data-icons.array-items}}';
            break;
        case 'menu-1':
        case 'main_menu':
        case 'primary':
            $menu = '{{#data-portlets-sidebar.data-portlets-first}}{{{html-items}}}{{/data-portlets-sidebar.data-portlets-first}}';
            break;
        case 'top-menu':
        case 'social-menu':
        case 'top-bar-nav':
        default:
            $menu = '<!-- TODO:get_menu_for_mediawiki: ' .  $key . '-->';
            break;
    }
    $menu .= '<!-- get_menu_for_mediawiki: ' .  $key . '-->';
    return $menu;
}

function register_nav_menu( string $location, string $description ) {
    global $renderMemory;
    $renderMemory['menus'][$location] = $description;
}

function register_nav_menus( $menus ) {
    foreach ( $menus as $location => $description ) {
        register_nav_menu( $location, $description );
    }
} 
