<?php
/**
 * Default content, seeded once and never over existing work:
 *  - the editable Info page (About, Blog, FAQs, Privacy Policy, Disclaimer, Terms & Conditions)
 *  - 300-400 words of informational text for every game that has an empty editor.
 * Bump KHAIWAL_CONTENT_VERSION to run the routine again for newly added defaults.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'KHAIWAL_CONTENT_VERSION', '1' );

function khaiwal_info_default_content() {
    $file = get_template_directory() . '/data/info-page.html';
    $html = is_readable( $file ) ? (string) file_get_contents( $file ) : '';
    return strtr( $html, array(
        '{{site}}' => esc_html( khaiwal_site_name() ),
        '{{contact_url}}' => esc_url( khaiwal_contact_url() ),
    ) );
}

function khaiwal_game_default_content() {
    $file = get_template_directory() . '/data/game-content.json';
    $data = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array();
    return is_array( $data ) ? $data : array();
}

function khaiwal_seed_content() {
    if ( get_option( 'khaiwal_content_version' ) === KHAIWAL_CONTENT_VERSION ) { return; }
    $locked_at = (int) get_option( 'khaiwal_content_seed_lock', 0 );
    if ( $locked_at && time() - $locked_at > 300 ) { delete_option( 'khaiwal_content_seed_lock' ); }
    if ( ! add_option( 'khaiwal_content_seed_lock', time(), '', false ) ) { return; }
    $success = true;
    kses_remove_filters(); // Trusted theme content: keep block markup and shortcodes intact even on a visitor's request.
    try {
        // The Info page: reuse any page already using the template or the /info/ address; otherwise create it.
        $existing = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'posts_per_page' => 1, 'meta_key' => '_wp_page_template', 'meta_value' => 'page-info.php', 'fields' => 'ids' ) );
        if ( ! $existing && ! get_page_by_path( 'info', OBJECT, array( 'page', 'post' ) ) ) {
            $id = wp_insert_post( array(
                'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'info',
                'post_title' => __( 'About, Blog, FAQs, Policies', 'khaiwal' ),
                'post_content' => khaiwal_info_default_content(),
                'meta_input' => array( '_wp_page_template' => 'page-info.php' ),
            ), true );
            if ( is_wp_error( $id ) ) { $success = false; }
        }
        // Game text: only into editors that are still empty.
        $texts = khaiwal_game_default_content();
        foreach ( $texts as $slug => $html ) {
            $post = get_page_by_path( sanitize_title( $slug ), OBJECT, 'khaiwal_game' );
            if ( ! $post ) { $success = false; continue; }
            if ( '' !== trim( (string) $post->post_content ) ) { continue; }
            $updated = wp_update_post( array( 'ID' => $post->ID, 'post_content' => wp_slash( $html ) ), true );
            if ( is_wp_error( $updated ) ) { $success = false; }
        }
        if ( $success ) { update_option( 'khaiwal_content_version', KHAIWAL_CONTENT_VERSION, false ); }
    } finally {
        delete_option( 'khaiwal_content_seed_lock' );
        kses_init();
    }
    flush_rewrite_rules( false );
}
add_action( 'init', 'khaiwal_seed_content', 40 );
