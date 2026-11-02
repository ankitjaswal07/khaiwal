<?php
/** Khaiwal theme bootstrap. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'KHAIWAL_VERSION', '1.0.0' );
require_once get_template_directory() . '/inc/catalog.php';
require_once get_template_directory() . '/inc/admin.php';

function khaiwal_setup() {
    load_theme_textdomain( 'khaiwal', get_template_directory() . '/languages' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'custom-logo', array( 'height' => 56, 'width' => 200, 'flex-height' => true, 'flex-width' => true ) );
    register_nav_menus( array( 'primary' => __( 'Primary navigation', 'khaiwal' ) ) );
}
add_action( 'after_setup_theme', 'khaiwal_setup' );

function khaiwal_assets() {
    $uri = get_template_directory_uri();
    // Reuse conventional plugin handles where available instead of loading a second copy.
    $swiper_style = wp_style_is( 'swiper', 'registered' ) ? 'swiper' : 'khaiwal-swiper';
    $aos_style = wp_style_is( 'aos', 'registered' ) ? 'aos' : 'khaiwal-aos';
    if ( $swiper_style === 'khaiwal-swiper' ) { wp_register_style( $swiper_style, $uri . '/assets/vendor/swiper-bundle.min.css', array(), '11.2.10' ); }
    if ( $aos_style === 'khaiwal-aos' ) { wp_register_style( $aos_style, $uri . '/assets/vendor/aos.css', array(), '2.3.4' ); }
    wp_enqueue_style( $swiper_style );
    wp_enqueue_style( $aos_style );
    wp_enqueue_style( 'khaiwal-style', get_stylesheet_uri(), array(), KHAIWAL_VERSION );
    wp_enqueue_style( 'khaiwal-design', $uri . '/assets/css/theme.css', array( 'khaiwal-style', $swiper_style, $aos_style ), KHAIWAL_VERSION );

    $gsap = wp_script_is( 'gsap', 'registered' ) ? 'gsap' : 'khaiwal-gsap';
    $scroll = wp_script_is( 'scrolltrigger', 'registered' ) ? 'scrolltrigger' : ( wp_script_is( 'gsap-scrolltrigger', 'registered' ) ? 'gsap-scrolltrigger' : 'khaiwal-scrolltrigger' );
    $swiper = wp_script_is( 'swiper', 'registered' ) ? 'swiper' : 'khaiwal-swiper';
    $aos = wp_script_is( 'aos', 'registered' ) ? 'aos' : 'khaiwal-aos';
    if ( $gsap === 'khaiwal-gsap' ) { wp_register_script( $gsap, $uri . '/assets/vendor/gsap.min.js', array(), '3.13.0', true ); }
    if ( $scroll === 'khaiwal-scrolltrigger' ) { wp_register_script( $scroll, $uri . '/assets/vendor/ScrollTrigger.min.js', array( $gsap ), '3.13.0', true ); }
    if ( $swiper === 'khaiwal-swiper' ) { wp_register_script( $swiper, $uri . '/assets/vendor/swiper-bundle.min.js', array(), '11.2.10', true ); }
    if ( $aos === 'khaiwal-aos' ) { wp_register_script( $aos, $uri . '/assets/vendor/aos.js', array(), '2.3.4', true ); }
    wp_enqueue_script( 'khaiwal-app', $uri . '/assets/js/theme.js', array( $gsap, $scroll, $swiper, $aos ), KHAIWAL_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'khaiwal_assets', 20 );

/** Inline SVGs have fixed trusted paths, and inherit currentColor. */
function khaiwal_icon( $name, $class = '' ) {
    $paths = array(
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-up' => '<path d="M7 17 17 7M7 7h10v10"/>',
        'chevron' => '<path d="m9 18 6-6-6-6"/>',
        'left' => '<path d="m15 18-6-6 6-6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'chart' => '<path d="M4 19h16M6 15l4-4 3 3 5-7"/><path d="M15 7h3v3"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'spark' => '<path d="M12 3v18M3 12h18M5.6 5.6l12.8 12.8M18.4 5.6 5.6 18.4"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'shield' => '<path d="M12 3 4 6v5c0 5 3.5 8 8 10 4.5-2 8-5 8-10V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
        'book' => '<path d="M4 5.5c3-1 5.5-.5 8 1.5 2.5-2 5-2.5 8-1.5v13c-3-1-5.5-.5-8 1.5-2.5-2-5-2.5-8-1.5zM12 7v13"/>',
    );
    if ( ! isset( $paths[ $name ] ) ) { return ''; }
    return '<svg class="icon ' . esc_attr( $class ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[ $name ] . '</svg>';
}

/** Emit UI copy with an explicit Hindi equivalent for the lightweight switcher. */
function khaiwal_text( $en, $hi, $tag = 'span', $class = '' ) {
    $allowed = array( 'span', 'p', 'h1', 'h2', 'h3', 'strong', 'small' );
    $tag = in_array( $tag, $allowed, true ) ? $tag : 'span';
    return '<' . $tag . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . ' data-en="' . esc_attr( $en ) . '" data-hi="' . esc_attr( $hi ) . '">' . esc_html( $en ) . '</' . $tag . '>';
}

/** Respect explicitly assigned templates when an existing site already owns a slug. */
function khaiwal_page_url( $slug, $template ) {
    static $urls = array();
    if ( isset( $urls[ $template ] ) ) { return $urls[ $template ]; }
    $assigned = get_posts( array(
        'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => 1,
        'meta_key' => '_wp_page_template', 'meta_value' => $template, 'orderby' => 'ID', 'order' => 'ASC',
    ) );
    $page = $assigned ? $assigned[0] : get_page_by_path( $slug, OBJECT, 'page' );
    $urls[ $template ] = $page && $page->post_status === 'publish' ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
    return $urls[ $template ];
}
function khaiwal_chart_url() { return khaiwal_page_url( 'chart', 'page-chart.php' ); }
function khaiwal_contact_url() { return khaiwal_page_url( 'contact', 'page-contact.php' ); }
