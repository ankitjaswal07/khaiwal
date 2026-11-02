<?php
/**
 * Front-end extras: notice-bar ticker, WhatsApp chat button, Customizer options,
 * and the editable single "Info" page (About, Blog, FAQs, Privacy, Disclaimer, Terms).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---------------------------------------------------------------------------------------------
 * Customizer
 * ------------------------------------------------------------------------------------------ */

function khaiwal_customize_register( WP_Customize_Manager $wp_customize ) {
    $wp_customize->add_section( 'khaiwal_options', array( 'title' => __( 'Khaiwal site options', 'khaiwal' ), 'priority' => 30 ) );
    $fields = array(
        'khaiwal_whatsapp_number' => array( __( 'WhatsApp number for the chat button (country code, digits only, e.g. 919876543210). Leave empty to hide the button.', 'khaiwal' ), 'text', 'khaiwal_sanitize_phone' ),
        'khaiwal_whatsapp_message' => array( __( 'Message pre-filled when someone taps the WhatsApp button', 'khaiwal' ), 'text', 'sanitize_text_field' ),
        'khaiwal_notice_text' => array( __( 'Extra message for the scrolling notice bar (optional)', 'khaiwal' ), 'textarea', 'sanitize_textarea_field' ),
        'khaiwal_about_title' => array( __( 'Homepage "About" section: heading (optional)', 'khaiwal' ), 'text', 'sanitize_text_field' ),
        'khaiwal_about_text' => array( __( 'Homepage "About" section: text (optional, plain text; blank lines make paragraphs)', 'khaiwal' ), 'textarea', 'sanitize_textarea_field' ),
    );
    foreach ( $fields as $id => $field ) {
        $wp_customize->add_setting( $id, array( 'default' => '', 'sanitize_callback' => $field[2], 'transport' => 'refresh' ) );
        $wp_customize->add_control( $id, array( 'label' => $field[0], 'section' => 'khaiwal_options', 'type' => $field[1] ) );
    }
}
add_action( 'customize_register', 'khaiwal_customize_register' );

function khaiwal_sanitize_phone( $value ) { return ltrim( preg_replace( '/\D+/', '', (string) $value ), '0' ); }

/* ---------------------------------------------------------------------------------------------
 * WhatsApp chat button
 * ------------------------------------------------------------------------------------------ */

function khaiwal_whatsapp_url() {
    $number = khaiwal_sanitize_phone( get_theme_mod( 'khaiwal_whatsapp_number', '' ) );
    if ( strlen( $number ) < 8 || strlen( $number ) > 15 ) { return ''; }
    $message = trim( (string) get_theme_mod( 'khaiwal_whatsapp_message', '' ) );
    if ( '' === $message ) { $message = sprintf( 'Hello %s, I have a question.', khaiwal_site_name() ); }
    return 'https://wa.me/' . $number . '?text=' . rawurlencode( $message );
}

function khaiwal_whatsapp_button() {
    $url = khaiwal_whatsapp_url();
    if ( ! $url ) { return; }
    echo '<a class="whatsapp-float" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'Chat on WhatsApp (opens in a new tab)', 'khaiwal' ) . '" data-aria-en="Chat on WhatsApp (opens in a new tab)" data-aria-hi="WhatsApp पर चैट करें (नई टैब में खुलेगा)">' . khaiwal_icon( 'whatsapp' ) . '<span class="whatsapp-label">' . khaiwal_text( 'Chat on WhatsApp', 'WhatsApp पर चैट करें' ) . '</span></a>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped helpers.
}
add_action( 'wp_footer', 'khaiwal_whatsapp_button', 5 );

/* ---------------------------------------------------------------------------------------------
 * Notice-bar ticker: next game, today's results, newest post, optional custom message
 * ------------------------------------------------------------------------------------------ */

function khaiwal_ticker_items() {
    static $html = null;
    if ( null !== $html ) { return $html; }
    $games = khaiwal_games();
    $now = khaiwal_now();
    $items = array();
    $state = khaiwal_schedule_state( $games, $now );
    if ( ! empty( $state['next'] ) ) {
        $next = $state['next'];
        $items[] = '<li class="tick tick-next"><a href="' . esc_url( khaiwal_game_url( $next['game'] ) ) . '">' . khaiwal_text( 'NEXT GAME', 'अगला गेम' ) . ' <b>' . esc_html( $next['game']['name'] ) . '</b> <span class="tick-time">' . esc_html( $next['game']['time'] . ' IST' ) . '</span></a></li>';
    }
    $items[] = '<li class="tick tick-label"><span>' . khaiwal_text( 'TODAY’S RESULTS', 'आज के रिज़ल्ट' ) . ' · <time data-ist-date datetime="' . esc_attr( $now->format( 'Y-m-d' ) ) . '">' . esc_html( $now->format( 'd M Y' ) ) . '</time></span></li>';
    foreach ( $games as $game ) {
        $value = khaiwal_result_on( $game, $now );
        $items[] = '<li class="tick"><a href="' . esc_url( khaiwal_game_url( $game ) ) . '"><b>' . khaiwal_text( $game['name'], $game['hindi'] ) . '</b> <span class="tick-time">' . esc_html( $game['time'] ) . '</span> <strong class="tick-result' . ( '' === $value ? ' is-waiting' : '' ) . '" data-ticker-slug="' . esc_attr( $game['slug'] ) . '">' . khaiwal_result_html( $value ) . '</strong></a></li>';
    }
    $latest = get_posts( array( 'post_type' => 'post', 'posts_per_page' => 1, 'no_found_rows' => true, 'ignore_sticky_posts' => true ) );
    if ( $latest ) { $items[] = '<li class="tick tick-post"><a href="' . esc_url( get_permalink( $latest[0] ) ) . '">' . khaiwal_text( 'LATEST POST', 'नई पोस्ट' ) . ' <b>' . esc_html( get_the_title( $latest[0] ) ) . '</b></a></li>'; }
    $custom = trim( (string) get_theme_mod( 'khaiwal_notice_text', '' ) );
    if ( '' !== $custom ) { $items[] = '<li class="tick tick-custom"><span>' . esc_html( $custom ) . '</span></li>'; }
    $items[] = '<li class="tick tick-note"><span>' . khaiwal_text( 'All times are IST. Information only; results are never predicted.', 'सभी समय IST में हैं। केवल जानकारी; रिज़ल्ट कभी अनुमानित नहीं होते।' ) . '</span></li>';
    $html = implode( '', $items ); // Each item is built from escaped values above.
    return $html;
}

/* ---------------------------------------------------------------------------------------------
 * Editable Info page (About, Blog, FAQs, Privacy Policy, Disclaimer, Terms & Conditions)
 * ------------------------------------------------------------------------------------------ */

function khaiwal_is_info_page() {
    return is_page() && ( is_page_template( 'page-info.php' ) || is_page( 'info' ) );
}

/** Give every H2 an id and collect them for the in-page menu. Returns array( html, toc ). */
function khaiwal_info_prepare( $html ) {
    $toc = array();
    $html = preg_replace_callback( '#<h2([^>]*)>(.*?)</h2>#is', function ( $m ) use ( &$toc ) {
        $attrs = $m[1];
        $label = trim( wp_strip_all_tags( $m[2] ) );
        if ( preg_match( '/\sid=("|\')([^"\']+)\1/', $attrs, $id_match ) ) { $id = $id_match[2]; }
        else { $id = sanitize_title( $label ); $attrs .= ' id="' . esc_attr( $id ) . '"'; }
        if ( '' !== $label ) { $toc[] = array( $id, $label ); }
        return '<h2' . $attrs . '>' . $m[2] . '</h2>';
    }, $html );
    return array( $html, $toc );
}

/** FAQ question/answer pairs: H3 questions under an H2 whose text mentions FAQ. */
function khaiwal_info_faq_items( $html ) {
    $items = array();
    $sections = preg_split( '#(?=<h2[\s>])#i', $html );
    foreach ( $sections as $section ) {
        if ( ! preg_match( '#^<h2[^>]*>(.*?)</h2>#is', $section, $heading ) || ! preg_match( '/faq|frequently asked|questions/i', wp_strip_all_tags( $heading[1] ) ) ) { continue; }
        $parts = preg_split( '#(<h3[^>]*>.*?</h3>)#is', $section, -1, PREG_SPLIT_DELIM_CAPTURE );
        for ( $i = 1; $i < count( $parts ) - 1; $i += 2 ) {
            $question = trim( wp_strip_all_tags( $parts[ $i ] ) );
            $answer = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $parts[ $i + 1 ] ) ) );
            if ( $question && $answer ) { $items[] = array( $question, '', $answer, '' ); }
        }
    }
    return $items;
}

/** [khaiwal_blog per_page="9"]: the newest journal posts, paginated with ?bpage=. */
function khaiwal_blog_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'per_page' => 9 ), $atts, 'khaiwal_blog' );
    $per_page = max( 1, min( 24, (int) $atts['per_page'] ) );
    $paged = isset( $_GET['bpage'] ) ? max( 1, absint( $_GET['bpage'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
    $query = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => $per_page, 'paged' => $paged, 'ignore_sticky_posts' => true ) );
    ob_start();
    echo '<div class="info-blog" id="blog-list">';
    if ( $query->have_posts() ) {
        echo '<div class="post-list">';
        while ( $query->have_posts() ) {
            $query->the_post();
            echo '<article class="post-list-item"><div><div class="story-meta"><time datetime="' . esc_attr( get_the_date( 'c' ) ) . '">' . esc_html( get_the_date() ) . '</time></div><h3><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3><p>' . esc_html( khaiwal_trim( get_the_excerpt(), 180 ) ) . '</p></div><a class="round-link" href="' . esc_url( get_permalink() ) . '" aria-label="' . esc_attr( sprintf( __( 'Read %s', 'khaiwal' ), get_the_title() ) ) . '">' . khaiwal_icon( 'arrow-up' ) . '</a></article>'; // phpcs:ignore WordPress.Security.EscapeOutput
        }
        echo '</div>';
        if ( $query->max_num_pages > 1 ) {
            echo '<nav class="info-pagination" aria-label="' . esc_attr__( 'Blog pages', 'khaiwal' ) . '">';
            if ( $paged > 1 ) { echo '<a class="button button-outline-dark" href="' . esc_url( add_query_arg( 'bpage', $paged - 1, get_permalink() ) . '#blog-list' ) . '">&larr; ' . esc_html__( 'Newer posts', 'khaiwal' ) . '</a>'; }
            if ( $paged < $query->max_num_pages ) { echo '<a class="button button-outline-dark" href="' . esc_url( add_query_arg( 'bpage', $paged + 1, get_permalink() ) . '#blog-list' ) . '">' . esc_html__( 'Older posts', 'khaiwal' ) . ' &rarr;</a>'; }
            echo '</nav>';
        }
    } else {
        echo '<p>' . esc_html__( 'No posts yet. Add posts in WordPress and they will appear here.', 'khaiwal' ) . '</p>';
    }
    echo '</div>';
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode( 'khaiwal_blog', 'khaiwal_blog_shortcode' );

/** [khaiwal_game field="time"]: live game details inside editable game text (name, hindi, time, latest, latest_date, today). */
function khaiwal_game_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'field' => 'name', 'game' => '' ), $atts, 'khaiwal_game' );
    $game = null;
    if ( $atts['game'] ) { $games = khaiwal_games(); $game = isset( $games[ sanitize_key( $atts['game'] ) ] ) ? $games[ sanitize_key( $atts['game'] ) ] : null; }
    elseif ( is_singular( 'khaiwal_game' ) ) { $game = khaiwal_current_game(); }
    if ( ! $game ) { return ''; }
    switch ( $atts['field'] ) {
        case 'hindi': return esc_html( $game['hindi'] );
        case 'time': return esc_html( $game['time'] ? $game['time'] . ' IST' : __( 'its usual time', 'khaiwal' ) );
        case 'latest': $latest = khaiwal_latest_result( $game ); return esc_html( $latest ? $latest['result'] : '—' );
        case 'latest_full': $latest = khaiwal_latest_result( $game ); return $latest ? esc_html( sprintf( '%1$s (for %2$s)', $latest['result'], khaiwal_date_label( $latest['date'] ) ) ) : esc_html__( 'not available yet, because nothing has been published', 'khaiwal' );
        case 'latest_date': $latest = khaiwal_latest_result( $game ); return esc_html( $latest ? khaiwal_date_label( $latest['date'] ) : '—' );
        case 'today': return esc_html( khaiwal_result_on( $game, khaiwal_now() ) ?: '—' );
        default: return esc_html( $game['name'] );
    }
}
add_shortcode( 'khaiwal_game', 'khaiwal_game_shortcode' );

/** Games and the Info page keep their own editor content; keep it readable on the front end. */
function khaiwal_body_classes( $classes ) {
    if ( khaiwal_is_info_page() ) { $classes[] = 'is-info-page'; }
    return $classes;
}
add_filter( 'body_class', 'khaiwal_body_classes' );
