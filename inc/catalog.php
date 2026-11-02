<?php
/** Game catalog, dated snapshots, and WordPress-managed overrides. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function khaiwal_catalog() {
    static $catalog = null;
    if ( null === $catalog ) {
        $json = file_get_contents( get_template_directory() . '/data/games.json' );
        $catalog = json_decode( $json, true );
        if ( ! is_array( $catalog ) ) { $catalog = array( 'snapshot_date' => '', 'games' => array() ); }
    }
    return $catalog;
}

/** Keep historical data available for old URLs without adding them to the active lineup. */
function khaiwal_catalog_game( $slug ) {
    $catalog = khaiwal_catalog();
    $all = array_merge( $catalog['games'], isset( $catalog['legacy_games'] ) ? $catalog['legacy_games'] : array() );
    foreach ( $all as $game ) { if ( $game['slug'] === $slug ) { return $game; } }
    return null;
}

function khaiwal_register_types() {
    register_post_type( 'khaiwal_game', array(
        'labels' => array( 'name' => __( 'Games', 'khaiwal' ), 'singular_name' => __( 'Game', 'khaiwal' ), 'add_new_item' => __( 'Add game', 'khaiwal' ) ),
        'public' => true, 'show_in_rest' => true, 'show_in_menu' => true,
        'menu_icon' => 'dashicons-chart-bar', 'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
        'rewrite' => array( 'slug' => 'games', 'with_front' => false ), 'has_archive' => false,
    ) );
    register_post_type( 'khaiwal_result', array(
        'labels' => array( 'name' => __( 'Game results', 'khaiwal' ), 'singular_name' => __( 'Game result', 'khaiwal' ), 'add_new' => __( 'Add single result', 'khaiwal' ), 'add_new_item' => __( 'Publish a result', 'khaiwal' ), 'all_items' => __( 'All results', 'khaiwal' ), 'edit_item' => __( 'Edit result', 'khaiwal' ) ),
        // Results never have an editor-entered title: it is generated from the game and date.
        // The menu lives under the Publish results page registered in inc/publish.php.
        'public' => false, 'show_ui' => true, 'show_in_menu' => 'khaiwal-publish', 'show_in_rest' => false,
        'supports' => false,
    ) );
}
add_action( 'init', 'khaiwal_register_types' );

/** Create missing game posts and required pages only; never replace existing content. */
function khaiwal_install_catalog() {
    khaiwal_register_types();
    foreach ( khaiwal_catalog()['games'] as $game ) {
        if ( get_page_by_path( $game['slug'], OBJECT, 'khaiwal_game' ) ) { continue; }
        $id = wp_insert_post( array(
            'post_type' => 'khaiwal_game', 'post_status' => 'publish', 'post_title' => $game['name'], 'post_name' => $game['slug'],
            'post_content' => '',
        ), true );
        if ( ! is_wp_error( $id ) ) {
            update_post_meta( $id, '_khaiwal_hindi', $game['hindi'] );
            update_post_meta( $id, '_khaiwal_time', $game['time'] );
        }
    }
    foreach ( array( 'chart' => 'Chart', 'contact' => 'Contact' ) as $slug => $title ) {
        // Avoid claiming an existing post URL as well as an existing page URL.
        if ( ! get_page_by_path( $slug, OBJECT, array( 'page', 'post' ) ) ) {
            wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug ) );
        }
    }
    // Seed a small English journal only on an otherwise untouched installation.
    $posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 2 ) );
    if ( empty( $posts ) || ( count( $posts ) === 1 && $posts[0]->post_name === 'hello-world' ) ) {
        $stories = array(
            array( 'How to read a result chart', 'A clear, simple guide to finding the numbers you need.', 'A result chart brings past published numbers together in one place. Choose a game and a month to see each available result by date. A dash means that no result is available, not that a number has been predicted. Always verify information with its original source.', 'story-charts.webp', 'रिज़ल्ट चार्ट कैसे पढ़ें', 'ज़रूरी नंबर आसानी से खोजने की एक सरल गाइड।' ),
            array( 'The games, all in one place', 'Get to know the game pages and find the results you follow.', 'Every game has its own page, with the current and previous month side by side. You can browse the complete list from the Chart page and use search to find a specific game. Results are provided for information only.', 'story-city.webp', 'सभी गेम, एक ही जगह', 'गेम पेज देखें और अपने पसंदीदा रिज़ल्ट खोजें।' ),
            array( 'A simpler way to stay in the know', 'A few helpful tips for keeping up with published results.', 'Start with the latest published results, then open a game to browse its history. Check the snapshot date and remember that results change only when an editor publishes new information. Past outcomes do not predict future outcomes.', 'hero-gold.webp', 'जानकारी पाने का आसान तरीका', 'प्रकाशित रिज़ल्ट देखने के लिए कुछ उपयोगी सुझाव।' ),
        );
        foreach ( $stories as $story ) {
            $id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => $story[0], 'post_excerpt' => $story[1], 'post_content' => '<p>' . esc_html( $story[2] ) . '</p>' ), true );
            if ( ! is_wp_error( $id ) ) {
                update_post_meta( $id, '_khaiwal_demo_image', $story[3] );
                update_post_meta( $id, '_khaiwal_demo_title_hi', $story[4] );
                update_post_meta( $id, '_khaiwal_demo_excerpt_hi', $story[5] );
            }
        }
    }
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'khaiwal_install_catalog' );

/** One-time, non-destructive upgrade for the selected 14-game lineup. */
function khaiwal_upgrade_lineup() {
    if ( get_option( 'khaiwal_lineup_version' ) === '2' ) { return; }
    $locked_at = (int) get_option( 'khaiwal_lineup_upgrade_lock', 0 );
    if ( $locked_at && time() - $locked_at > 300 ) { delete_option( 'khaiwal_lineup_upgrade_lock' ); }
    if ( ! add_option( 'khaiwal_lineup_upgrade_lock', time(), '', false ) ) { return; }
    $success = true;
    try {
        foreach ( khaiwal_catalog()['games'] as $game ) {
            $post = get_page_by_path( $game['slug'], OBJECT, 'khaiwal_game' );
            if ( ! $post ) {
                $id = wp_insert_post( array( 'post_type' => 'khaiwal_game', 'post_status' => 'publish', 'post_title' => $game['name'], 'post_name' => $game['slug'] ), true );
                if ( is_wp_error( $id ) ) { $success = false; continue; }
                update_post_meta( $id, '_khaiwal_hindi', $game['hindi'] );
                update_post_meta( $id, '_khaiwal_time', $game['time'] );
                continue;
            }
            // Update only the previous theme defaults; never overwrite editor customisations.
            $time = get_post_meta( $post->ID, '_khaiwal_time', true );
            $hi = get_post_meta( $post->ID, '_khaiwal_hindi', true );
            if ( '' === $time || ( isset( $game['legacy_default_time'] ) && $time === $game['legacy_default_time'] ) ) { update_post_meta( $post->ID, '_khaiwal_time', $game['time'] ); }
            if ( '' === $hi || ( isset( $game['legacy_default_hindi'] ) && $hi === $game['legacy_default_hindi'] ) ) { update_post_meta( $post->ID, '_khaiwal_hindi', $game['hindi'] ); }
            if ( isset( $game['legacy_default_name'] ) && $post->post_title === $game['legacy_default_name'] && $post->post_title !== $game['name'] ) {
                $updated = wp_update_post( array( 'ID' => $post->ID, 'post_title' => $game['name'] ), true );
                if ( is_wp_error( $updated ) ) { $success = false; }
            }
        }
        if ( $success ) { update_option( 'khaiwal_lineup_version', '2', false ); }
    } finally {
        delete_option( 'khaiwal_lineup_upgrade_lock' );
    }
}
add_action( 'init', 'khaiwal_upgrade_lineup', 20 );

/** The curated lineup uses published WordPress game posts as its source of truth. */
function khaiwal_games() {
    static $games = null;
    if ( null !== $games ) { return $games; }
    $games = array();
    foreach ( khaiwal_catalog()['games'] as $game ) { $games[ $game['slug'] ] = $game; }
    $posts = get_posts( array( 'post_type' => 'khaiwal_game', 'post_status' => 'publish', 'posts_per_page' => -1, 'post_name__in' => array_keys( $games ), 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true ) );
    foreach ( $posts as $post ) {
        $slug = $post->post_name;
        // Keep other existing game posts and their URLs intact, but out of the selected lineup.
        if ( ! isset( $games[ $slug ] ) ) { continue; }
        $games[ $slug ]['name'] = $post->post_title;
        $hi = get_post_meta( $post->ID, '_khaiwal_hindi', true );
        $time = get_post_meta( $post->ID, '_khaiwal_time', true );
        if ( $hi ) { $games[ $slug ]['hindi'] = $hi; }
        if ( $time ) { $games[ $slug ]['time'] = $time; }
        $games[ $slug ]['post_id'] = $post->ID;
    }
    // WordPress publication state is authoritative: never link to trashed or draft games.
    foreach ( $games as $slug => $game ) { if ( empty( $game['post_id'] ) ) { unset( $games[ $slug ] ); } }
    return $games;
}

/** Keep previous game URLs working without listing them in the active lineup or core sitemap. */
function khaiwal_sitemap_game_args( $args, $post_type ) {
    if ( $post_type === 'khaiwal_game' ) { $args['post_name__in'] = array_column( khaiwal_catalog()['games'], 'slug' ); }
    return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'khaiwal_sitemap_game_args', 10, 2 );

function khaiwal_legacy_game_robots( $robots ) {
    if ( is_singular( 'khaiwal_game' ) ) {
        $slug = get_post_field( 'post_name', get_queried_object_id() );
        if ( ! in_array( $slug, array_column( khaiwal_catalog()['games'], 'slug' ), true ) ) { $robots['noindex'] = true; }
    }
    return $robots;
}
add_filter( 'wp_robots', 'khaiwal_legacy_game_robots' );

function khaiwal_game_url( $game ) {
    return ! empty( $game['post_id'] ) ? get_permalink( $game['post_id'] ) : home_url( '/games/' . $game['slug'] . '/' );
}

function khaiwal_now() { return new DateTimeImmutable( 'now', new DateTimeZone( 'Asia/Kolkata' ) ); }

function khaiwal_valid_date( $date ) {
    if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m ) ) { return false; }
    return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
}

/** Load published entries for a month in bulk; they override the bundled snapshot. */
function khaiwal_month_overrides( $month ) {
    static $cache = array();
    if ( isset( $cache[ $month ] ) ) { return $cache[ $month ]; }
    $cache[ $month ] = array();
    if ( ! preg_match( '/^\d{4}-\d{2}$/', $month ) ) { return $cache[ $month ]; }
    $ids = get_posts( array(
        'post_type' => 'khaiwal_result', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids',
        'orderby' => 'modified', 'order' => 'ASC', 'no_found_rows' => true,
        'meta_query' => array( array( 'key' => '_khaiwal_date', 'value' => array( $month . '-01', $month . '-31' ), 'compare' => 'BETWEEN', 'type' => 'CHAR' ) ),
    ) );
    if ( $ids ) { update_meta_cache( 'post', $ids ); }
    foreach ( $ids as $id ) {
        $slug = get_post_meta( $id, '_khaiwal_game', true );
        $date = get_post_meta( $id, '_khaiwal_date', true );
        $value = get_post_meta( $id, '_khaiwal_number', true );
        if ( $slug && khaiwal_valid_date( $date ) && preg_match( '/^\d{2}$/', $value ) ) { $cache[ $month ][ $slug ][ $date ] = $value; }
    }
    return $cache[ $month ];
}

/** Include every editor-published month in the game page's selector. */
function khaiwal_published_months( $slug ) {
    global $wpdb;
    $months = $wpdb->get_col( $wpdb->prepare(
        "SELECT DISTINCT SUBSTR(result_date.meta_value, 1, 7)
         FROM {$wpdb->posts} AS result
         INNER JOIN {$wpdb->postmeta} AS result_game ON result_game.post_id = result.ID AND result_game.meta_key = '_khaiwal_game'
         INNER JOIN {$wpdb->postmeta} AS result_date ON result_date.post_id = result.ID AND result_date.meta_key = '_khaiwal_date'
         WHERE result.post_type = 'khaiwal_result' AND result.post_status = 'publish' AND result_game.meta_value = %s",
        $slug
    ) );
    return array_values( array_filter( $months, function ( $month ) { return (bool) preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $month ); } ) );
}

function khaiwal_results_for_month( $game, $month ) {
    $out = array();
    if ( ! empty( $game['results'][ $month ] ) ) {
        foreach ( $game['results'][ $month ] as $index => $value ) {
            if ( preg_match( '/^\d{2}$/', (string) $value ) ) { $out[ sprintf( '%s-%02d', $month, $index + 1 ) ] = $value; }
        }
    }
    $overrides = khaiwal_month_overrides( $month );
    if ( isset( $overrides[ $game['slug'] ] ) ) { $out = array_merge( $out, $overrides[ $game['slug'] ] ); }
    return $out;
}

function khaiwal_result_on( $game, DateTimeImmutable $date ) {
    $all = khaiwal_results_for_month( $game, $date->format( 'Y-m' ) );
    return isset( $all[ $date->format( 'Y-m-d' ) ] ) ? $all[ $date->format( 'Y-m-d' ) ] : '';
}
