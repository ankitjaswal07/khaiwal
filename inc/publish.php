<?php
/**
 * Backend "Publish results": a full game table where an editor types results and
 * publishes them in one step. Results never carry an editor-entered title.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Capability needed to publish results (editors and above by default; same as the CSV importer). */
function khaiwal_publish_capability() {
    $capability = apply_filters( 'khaiwal_publish_capability', 'publish_posts' );
    return is_string( $capability ) && $capability ? $capability : 'publish_posts';
}

/** Admin URL of the Publish results page; WordPress sends logged-out visitors to log in first. */
function khaiwal_publish_url( $args = array() ) {
    return add_query_arg( array_merge( array( 'page' => 'khaiwal-publish' ), $args ), admin_url( 'admin.php' ) );
}

/* ---------------------------------------------------------------------------------------------
 * Automatic titles: nobody types a result title.
 * ------------------------------------------------------------------------------------------ */

function khaiwal_result_title( $name, $date ) {
    return trim( $name . ' – ' . $date );
}

/** Make sure a result always has a generated title, even when saved from the single-result editor. */
function khaiwal_force_result_title( $data, $postarr ) {
    if ( isset( $data['post_type'] ) && 'khaiwal_result' === $data['post_type'] && ( ! isset( $data['post_status'] ) || ! in_array( $data['post_status'], array( 'auto-draft', 'inherit' ), true ) ) ) {
        $slug = isset( $_POST['khaiwal_game'] ) && is_string( $_POST['khaiwal_game'] ) ? sanitize_key( wp_unslash( $_POST['khaiwal_game'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $date = isset( $_POST['khaiwal_date'] ) && is_string( $_POST['khaiwal_date'] ) ? sanitize_text_field( wp_unslash( $_POST['khaiwal_date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $games = khaiwal_games();
        if ( isset( $games[ $slug ] ) && khaiwal_valid_date( $date ) ) {
            $data['post_title'] = khaiwal_result_title( $games[ $slug ]['name'], $date );
        } elseif ( '' === trim( (string) $data['post_title'] ) ) {
            $data['post_title'] = __( 'Game result', 'khaiwal' );
        }
    }
    return $data;
}
add_filter( 'wp_insert_post_data', 'khaiwal_force_result_title', 10, 2 );

/** After meta is saved, rebuild the title from the stored game and date. */
function khaiwal_sync_result_title( $id ) {
    global $wpdb;
    $games = khaiwal_games();
    $slug = get_post_meta( $id, '_khaiwal_game', true );
    $date = get_post_meta( $id, '_khaiwal_date', true );
    if ( ! isset( $games[ $slug ] ) || ! khaiwal_valid_date( $date ) ) { return; }
    $title = khaiwal_result_title( $games[ $slug ]['name'], $date );
    if ( get_post_field( 'post_title', $id ) === $title ) { return; }
    $wpdb->update( $wpdb->posts, array( 'post_title' => $title ), array( 'ID' => $id ) ); // Direct write avoids re-triggering save_post.
    clean_post_cache( $id );
}

/* ---------------------------------------------------------------------------------------------
 * "Results changed" bookkeeping, used for Last-Modified headers, sitemap lastmod and caches.
 * ------------------------------------------------------------------------------------------ */

function khaiwal_touch_results() {
    static $queued = false;
    if ( $queued ) { return; }
    $queued = true;
    add_action( 'shutdown', function () {
        update_option( 'khaiwal_results_modified', time(), false );
        do_action( 'khaiwal_results_changed' );
    } );
}
function khaiwal_touch_results_for_post( $id ) { if ( 'khaiwal_result' === get_post_type( $id ) ) { khaiwal_touch_results(); } }
add_action( 'save_post_khaiwal_result', 'khaiwal_touch_results' );
add_action( 'trashed_post', 'khaiwal_touch_results_for_post' );
add_action( 'untrashed_post', 'khaiwal_touch_results_for_post' );
add_action( 'before_delete_post', 'khaiwal_touch_results_for_post' );

function khaiwal_results_modified_time() {
    $time = (int) get_option( 'khaiwal_results_modified', 0 );
    if ( $time ) { return $time; }
    $snapshot = strtotime( khaiwal_catalog()['snapshot_date'] . ' 12:00:00 Asia/Kolkata' );
    return $snapshot ? (int) $snapshot : time();
}

/* ---------------------------------------------------------------------------------------------
 * Menu
 * ------------------------------------------------------------------------------------------ */

function khaiwal_publish_menu() {
    $cap = khaiwal_publish_capability();
    add_menu_page( __( 'Publish results', 'khaiwal' ), __( 'Game results', 'khaiwal' ), $cap, 'khaiwal-publish', 'khaiwal_publish_screen', 'dashicons-calendar-alt', 4 );
    add_submenu_page( 'khaiwal-publish', __( 'Publish results', 'khaiwal' ), __( 'Publish results', 'khaiwal' ), $cap, 'khaiwal-publish', 'khaiwal_publish_screen' );
}
add_action( 'admin_menu', 'khaiwal_publish_menu', 5 );

/** WordPress does not add "Add New" for post types attached to a custom parent menu. */
function khaiwal_publish_menu_add_single() {
    add_submenu_page( 'khaiwal-publish', __( 'Publish a result', 'khaiwal' ), __( 'Add single result', 'khaiwal' ), 'edit_posts', 'post-new.php?post_type=khaiwal_result' );
}
add_action( 'admin_menu', 'khaiwal_publish_menu_add_single', 11 );

function khaiwal_publish_assets( $hook ) {
    if ( 'toplevel_page_khaiwal-publish' !== $hook ) { return; }
    $uri = get_template_directory_uri();
    wp_enqueue_style( 'khaiwal-admin-publish', $uri . '/assets/css/admin-publish.css', array(), KHAIWAL_VERSION );
    wp_enqueue_script( 'khaiwal-admin-publish', $uri . '/assets/js/admin-publish.js', array(), KHAIWAL_VERSION, true );
    wp_localize_script( 'khaiwal-admin-publish', 'khaiwalPublish', array(
        'confirmRemove' => __( 'Remove this published result? Results typed on this page that are not published yet will be discarded.', 'khaiwal' ),
        'ready' => __( '%d result(s) ready to publish', 'khaiwal' ),
        'none' => __( 'Nothing to publish yet', 'khaiwal' ),
        'leave' => __( 'You have results that are not published yet. Leave this page?', 'khaiwal' ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'khaiwal_publish_assets' );

/* ---------------------------------------------------------------------------------------------
 * Results list: show game / date / number instead of a title column.
 * ------------------------------------------------------------------------------------------ */

function khaiwal_result_columns( $columns ) {
    $new = array( 'cb' => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox">' );
    $new['khaiwal_game'] = __( 'Game', 'khaiwal' );
    $new['khaiwal_date'] = __( 'Date (IST)', 'khaiwal' );
    $new['khaiwal_number'] = __( 'Result', 'khaiwal' );
    $new['date'] = isset( $columns['date'] ) ? $columns['date'] : __( 'Published', 'khaiwal' );
    return $new;
}
add_filter( 'manage_khaiwal_result_posts_columns', 'khaiwal_result_columns' );

function khaiwal_result_column_content( $column, $id ) {
    $games = khaiwal_games();
    if ( 'khaiwal_game' === $column ) {
        $slug = get_post_meta( $id, '_khaiwal_game', true );
        $name = isset( $games[ $slug ] ) ? $games[ $slug ]['name'] : ( $slug ? $slug : __( '(no game)', 'khaiwal' ) );
        echo '<strong>' . ( current_user_can( 'edit_post', $id ) ? '<a class="row-title" href="' . esc_url( get_edit_post_link( $id ) ) . '">' . esc_html( $name ) . '</a>' : esc_html( $name ) ) . '</strong>';
    } elseif ( 'khaiwal_date' === $column ) {
        echo esc_html( get_post_meta( $id, '_khaiwal_date', true ) );
    } elseif ( 'khaiwal_number' === $column ) {
        echo '<code>' . esc_html( get_post_meta( $id, '_khaiwal_number', true ) ) . '</code>';
    }
}
add_action( 'manage_khaiwal_result_posts_custom_column', 'khaiwal_result_column_content', 10, 2 );
add_filter( 'list_table_primary_column', function ( $column, $screen ) { return 'edit-khaiwal_result' === $screen ? 'khaiwal_game' : $column; }, 10, 2 );

function khaiwal_result_default_order( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() || 'khaiwal_result' !== $query->get( 'post_type' ) || $query->get( 'orderby' ) ) { return; }
    $query->set( 'meta_key', '_khaiwal_date' );
    $query->set( 'orderby', 'meta_value' );
    $query->set( 'order', 'DESC' );
}
add_action( 'pre_get_posts', 'khaiwal_result_default_order' );

/* ---------------------------------------------------------------------------------------------
 * Data helpers
 * ------------------------------------------------------------------------------------------ */

/** Published editor entries between two dates (inclusive): [ date => [ slug => [ id, number ] ] ]. Latest edit wins. */
function khaiwal_published_map( $from, $to ) {
    $map = array();
    $ids = get_posts( array(
        'post_type' => 'khaiwal_result', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids',
        'orderby' => 'modified', 'order' => 'ASC', 'no_found_rows' => true,
        'meta_query' => array( array( 'key' => '_khaiwal_date', 'value' => array( $from, $to ), 'compare' => 'BETWEEN', 'type' => 'CHAR' ) ),
    ) );
    if ( $ids ) { update_meta_cache( 'post', $ids ); }
    foreach ( $ids as $id ) {
        $slug = get_post_meta( $id, '_khaiwal_game', true );
        $date = get_post_meta( $id, '_khaiwal_date', true );
        $number = get_post_meta( $id, '_khaiwal_number', true );
        if ( $slug && khaiwal_valid_date( $date ) && preg_match( '/^\d{2}$/', $number ) ) { $map[ $date ][ $slug ] = array( 'id' => (int) $id, 'number' => $number ); }
    }
    return $map;
}

/** The bundled snapshot value, ignoring editor-published entries. */
function khaiwal_snapshot_result( $game, $date ) {
    $month = substr( $date, 0, 7 );
    $index = (int) substr( $date, 8, 2 ) - 1;
    if ( isset( $game['results'][ $month ][ $index ] ) && preg_match( '/^\d{2}$/', (string) $game['results'][ $month ][ $index ] ) ) { return $game['results'][ $month ][ $index ]; }
    return '';
}

/** Create or update one published result. Returns published|updated|unchanged|forbidden|error. */
function khaiwal_publish_result( $slug, $date, $number, $games, $existing ) {
    $id = isset( $existing[ $date ][ $slug ] ) ? $existing[ $date ][ $slug ]['id'] : 0;
    if ( $id ) {
        if ( $existing[ $date ][ $slug ]['number'] === $number ) { return 'unchanged'; }
        if ( ! current_user_can( 'edit_post', $id ) ) { return 'forbidden'; }
        $result = wp_update_post( array( 'ID' => $id, 'post_title' => khaiwal_result_title( $games[ $slug ]['name'], $date ) ), true );
        if ( is_wp_error( $result ) ) { return 'error'; }
        update_post_meta( $id, '_khaiwal_number', $number );
        return 'updated';
    }
    $id = wp_insert_post( array( 'post_type' => 'khaiwal_result', 'post_status' => 'publish', 'post_title' => khaiwal_result_title( $games[ $slug ]['name'], $date ) ), true );
    if ( is_wp_error( $id ) || ! $id ) { return 'error'; }
    update_post_meta( $id, '_khaiwal_game', $slug );
    update_post_meta( $id, '_khaiwal_date', $date );
    update_post_meta( $id, '_khaiwal_number', $number );
    return 'published';
}

/* ---------------------------------------------------------------------------------------------
 * Saving
 * ------------------------------------------------------------------------------------------ */

function khaiwal_handle_publish() {
    if ( ! current_user_can( khaiwal_publish_capability() ) ) { wp_die( esc_html__( 'Not allowed.', 'khaiwal' ), '', array( 'response' => 403 ) ); }
    check_admin_referer( 'khaiwal_publish_results' );

    $view = isset( $_POST['view'] ) && 'month' === $_POST['view'] ? 'month' : 'day';
    $anchor = isset( $_POST['date'] ) && is_string( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
    $today = khaiwal_now()->format( 'Y-m-d' );
    if ( ! khaiwal_valid_date( $anchor ) ) { $anchor = $today; }
    $back = array( 'view' => $view, 'date' => $anchor );
    $games = khaiwal_games();
    $report = array( 'published' => 0, 'updated' => 0, 'unchanged' => 0, 'removed' => 0, 'invalid' => array(), 'forbidden' => 0, 'error' => 0 );
    $report_key = 'khaiwal_publish_report_' . get_current_user_id();

    // Remove a single published result (it is moved to the Trash).
    if ( ! empty( $_POST['khaiwal_unpublish'] ) && is_string( $_POST['khaiwal_unpublish'] ) ) {
        $parts = explode( '|', sanitize_text_field( wp_unslash( $_POST['khaiwal_unpublish'] ) ) );
        if ( 2 === count( $parts ) && khaiwal_valid_date( $parts[0] ) && isset( $games[ $parts[1] ] ) ) {
            $existing = khaiwal_published_map( $parts[0], $parts[0] );
            if ( isset( $existing[ $parts[0] ][ $parts[1] ] ) ) {
                $id = $existing[ $parts[0] ][ $parts[1] ]['id'];
                if ( current_user_can( 'delete_post', $id ) && wp_trash_post( $id ) ) { $report['removed']++; } else { $report['forbidden']++; }
            }
        }
        set_transient( $report_key, $report, 300 );
        wp_safe_redirect( khaiwal_publish_url( $back + array( 'khaiwal_done' => 1 ) ) );
        exit;
    }

    $entries = array();
    if ( isset( $_POST['results'] ) && is_array( $_POST['results'] ) ) {
        foreach ( wp_unslash( $_POST['results'] ) as $date => $row ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
            if ( ! is_array( $row ) ) { continue; }
            foreach ( $row as $slug => $value ) {
                if ( is_string( $value ) && '' !== trim( $value ) ) { $entries[] = array( (string) $date, sanitize_key( $slug ), trim( $value ) ); }
                if ( count( $entries ) > 1000 ) { wp_die( esc_html__( 'Too many entries in one request.', 'khaiwal' ), '', array( 'response' => 400 ) ); }
            }
        }
    }
    $valid = array(); $dates = array();
    foreach ( $entries as $entry ) {
        list( $date, $slug, $number ) = $entry;
        $label = ( isset( $games[ $slug ] ) ? $games[ $slug ]['name'] : $slug ) . ' · ' . $date . ' · ' . mb_substr( $number, 0, 10 );
        if ( ! isset( $games[ $slug ] ) || ! khaiwal_valid_date( $date ) || $date > $today || $date < '2000-01-01' || ! preg_match( '/^\d{2}$/', $number ) ) {
            $report['invalid'][] = $label;
            continue;
        }
        $valid[] = $entry; $dates[] = $date;
    }
    if ( $valid ) {
        $existing = khaiwal_published_map( min( $dates ), max( $dates ) );
        foreach ( $valid as $entry ) {
            $outcome = khaiwal_publish_result( $entry[1], $entry[0], $entry[2], $games, $existing );
            $report[ $outcome ]++;
        }
        if ( $report['published'] || $report['updated'] ) { do_action( 'khaiwal_results_published', $report ); }
    }
    set_transient( $report_key, $report, 300 );
    wp_safe_redirect( khaiwal_publish_url( $back + array( 'khaiwal_done' => 1 ) ) );
    exit;
}
add_action( 'admin_post_khaiwal_publish_results', 'khaiwal_handle_publish' );

/* ---------------------------------------------------------------------------------------------
 * Screen
 * ------------------------------------------------------------------------------------------ */

function khaiwal_publish_report_notice() {
    $key = 'khaiwal_publish_report_' . get_current_user_id();
    if ( empty( $_GET['khaiwal_done'] ) ) { return; } // phpcs:ignore WordPress.Security.NonceVerification
    $report = get_transient( $key );
    if ( ! $report ) { return; }
    delete_transient( $key );
    $done = (int) $report['published'] + (int) $report['updated'];
    $parts = array();
    if ( $report['published'] ) { $parts[] = sprintf( _n( '%d result published', '%d results published', $report['published'], 'khaiwal' ), $report['published'] ); }
    if ( $report['updated'] ) { $parts[] = sprintf( _n( '%d result updated', '%d results updated', $report['updated'], 'khaiwal' ), $report['updated'] ); }
    if ( $report['unchanged'] ) { $parts[] = sprintf( __( '%d unchanged', 'khaiwal' ), $report['unchanged'] ); }
    if ( $report['removed'] ) { $parts[] = __( 'Result removed', 'khaiwal' ); }
    if ( $parts ) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html( implode( ' · ', $parts ) ) . '.</strong>' . ( $done ? ' ' . esc_html__( 'They are live on the website now.', 'khaiwal' ) . ' <a href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View site', 'khaiwal' ) . '</a>' : '' ) . '</p></div>';
    }
    if ( ! $parts && empty( $report['invalid'] ) && empty( $report['forbidden'] ) && empty( $report['error'] ) ) {
        echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Nothing was entered, so nothing was published.', 'khaiwal' ) . '</p></div>';
    }
    if ( ! empty( $report['invalid'] ) ) {
        echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Skipped entries that are not exactly two digits (00–99), not in the game list, or dated in the future:', 'khaiwal' ) . '</strong> ' . esc_html( implode( '; ', array_slice( $report['invalid'], 0, 20 ) ) ) . '</p></div>';
    }
    if ( ! empty( $report['forbidden'] ) || ! empty( $report['error'] ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html( sprintf( __( '%d entries could not be saved (permissions or database error).', 'khaiwal' ), (int) $report['forbidden'] + (int) $report['error'] ) ) . '</p></div>';
    }
}

function khaiwal_publish_screen() {
    if ( ! current_user_can( khaiwal_publish_capability() ) ) { wp_die( esc_html__( 'You are not allowed to publish results.', 'khaiwal' ), '', array( 'response' => 403 ) ); }
    $tz = new DateTimeZone( 'Asia/Kolkata' );
    $now = khaiwal_now();
    $today = $now->format( 'Y-m-d' );
    $view = isset( $_GET['view'] ) && 'month' === $_GET['view'] ? 'month' : 'day'; // phpcs:ignore WordPress.Security.NonceVerification
    $requested = isset( $_GET['date'] ) && is_string( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : $today; // phpcs:ignore WordPress.Security.NonceVerification
    if ( ! khaiwal_valid_date( $requested ) || $requested < '2000-01-01' || $requested > '2100-12-31' ) { $requested = $today; }
    if ( $requested > $today ) { $requested = $today; }
    $anchor = DateTimeImmutable::createFromFormat( '!Y-m-d', $requested, $tz );
    $games = khaiwal_games(); // Same order as the public lineup.

    echo '<div class="wrap khaiwal-publish">';
    echo '<h1 class="wp-heading-inline">' . esc_html__( 'Publish results', 'khaiwal' ) . '</h1>';
    echo ' <a class="page-title-action" href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View site', 'khaiwal' ) . '</a> <a class="page-title-action" href="' . esc_url( admin_url( 'admin.php?page=khaiwal-import' ) ) . '">' . esc_html__( 'Import CSV', 'khaiwal' ) . '</a><hr class="wp-header-end">';
    khaiwal_publish_report_notice();
    if ( ! $games ) { echo '<div class="notice notice-warning"><p>' . esc_html__( 'No games are published yet. Publish Game posts first.', 'khaiwal' ) . '</p></div></div>'; return; }
    echo '<p class="description khaiwal-intro">' . esc_html__( "Type each game's two-digit result and press Publish: results go live immediately. No title is needed. All dates and times are India time (IST). Leave a box empty to skip that game.", 'khaiwal' ) . '</p>';

    // Navigation.
    $month_start = $anchor->modify( 'first day of this month' );
    if ( 'month' === $view ) {
        $prev = $month_start->modify( '-1 month' ); $next = $month_start->modify( '+1 month' );
        $title = $month_start->format( 'F Y' );
        $can_next = $next->format( 'Y-m-d' ) <= $today;
        $prev_date = $prev->format( 'Y-m-d' ); $next_date = $next->format( 'Y-m-d' );
    } else {
        $prev_date = $anchor->modify( '-1 day' )->format( 'Y-m-d' ); $next_date = $anchor->modify( '+1 day' )->format( 'Y-m-d' );
        $title = $anchor->format( 'l, j F Y' );
        $can_next = $next_date <= $today;
    }
    echo '<nav class="nav-tab-wrapper khaiwal-tabs" aria-label="' . esc_attr__( 'View', 'khaiwal' ) . '">';
    echo '<a class="nav-tab ' . ( 'day' === $view ? 'nav-tab-active' : '' ) . '" href="' . esc_url( khaiwal_publish_url( array( 'view' => 'day', 'date' => $requested ) ) ) . '">' . esc_html__( 'One day · all games', 'khaiwal' ) . '</a>';
    echo '<a class="nav-tab ' . ( 'month' === $view ? 'nav-tab-active' : '' ) . '" href="' . esc_url( khaiwal_publish_url( array( 'view' => 'month', 'date' => $requested ) ) ) . '">' . esc_html__( 'Whole month grid', 'khaiwal' ) . '</a></nav>';
    echo '<div class="khaiwal-datebar">';
    echo '<a class="button" href="' . esc_url( khaiwal_publish_url( array( 'view' => $view, 'date' => $prev_date ) ) ) . '">&larr; ' . esc_html( 'month' === $view ? __( 'Previous month', 'khaiwal' ) : __( 'Previous day', 'khaiwal' ) ) . '</a>';
    echo '<strong class="khaiwal-date-title">' . esc_html( $title ) . ( ( 'day' === $view && $requested === $today ) ? ' <span class="khaiwal-badge is-today">' . esc_html__( 'Today', 'khaiwal' ) . '</span>' : '' ) . '</strong>';
    if ( $can_next ) { echo '<a class="button" href="' . esc_url( khaiwal_publish_url( array( 'view' => $view, 'date' => $next_date ) ) ) . '">' . esc_html( 'month' === $view ? __( 'Next month', 'khaiwal' ) : __( 'Next day', 'khaiwal' ) ) . ' &rarr;</a>'; }
    echo '<a class="button" href="' . esc_url( khaiwal_publish_url( array( 'view' => $view, 'date' => $today ) ) ) . '">' . esc_html( 'month' === $view ? __( 'This month', 'khaiwal' ) : __( 'Today', 'khaiwal' ) ) . '</a>';
    echo '<form method="get" class="khaiwal-datepick" action="' . esc_url( admin_url( 'admin.php' ) ) . '"><input type="hidden" name="page" value="khaiwal-publish"><input type="hidden" name="view" value="' . esc_attr( $view ) . '"><label class="screen-reader-text" for="khaiwal-date-input">' . esc_html__( 'Choose a date', 'khaiwal' ) . '</label><input type="date" id="khaiwal-date-input" name="date" value="' . esc_attr( $requested ) . '" min="2000-01-01" max="' . esc_attr( $today ) . '"> <button class="button" type="submit">' . esc_html__( 'Go', 'khaiwal' ) . '</button></form>';
    echo '</div>';

    // Form.
    echo '<form method="post" id="khaiwal-publish-form" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" autocomplete="off">';
    wp_nonce_field( 'khaiwal_publish_results' );
    echo '<input type="hidden" name="action" value="khaiwal_publish_results"><input type="hidden" name="view" value="' . esc_attr( $view ) . '"><input type="hidden" name="date" value="' . esc_attr( $requested ) . '">';
    // The first submit button in the form is the one the Enter key triggers, so Publish comes first.
    echo '<div class="khaiwal-actionbar"><button type="submit" class="button button-primary button-hero" data-publish-button>' . esc_html__( 'Publish results', 'khaiwal' ) . '</button><span class="khaiwal-count" data-publish-count role="status" aria-live="polite">' . esc_html__( 'Nothing to publish yet', 'khaiwal' ) . '</span><span class="khaiwal-hint">' . esc_html__( 'Tip: Enter or two digits jumps to the next game · Ctrl/⌘ + Enter publishes.', 'khaiwal' ) . '</span></div>';

    if ( 'day' === $view ) { khaiwal_publish_day_table( $games, $requested, $today, $now ); } else { khaiwal_publish_month_table( $games, $month_start, $today ); }

    echo '<div class="khaiwal-actionbar khaiwal-actionbar-bottom"><button type="submit" class="button button-primary button-large" data-publish-button>' . esc_html__( 'Publish results', 'khaiwal' ) . '</button></div>';
    echo '</form>';
    echo '<p class="description">' . esc_html__( 'A published result replaces the archived snapshot for that game and date. Use "Remove" in the day view to take a published result back (it moves to the Trash).', 'khaiwal' ) . ' <a href="' . esc_url( admin_url( 'edit.php?post_type=khaiwal_result' ) ) . '">' . esc_html__( 'See all published results', 'khaiwal' ) . '</a></p>';
    echo '</div>';
}

function khaiwal_publish_day_table( $games, $date, $today, DateTimeImmutable $now ) {
    $published = khaiwal_published_map( $date, $date );
    $day = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, new DateTimeZone( 'Asia/Kolkata' ) );
    $previous_day = $day->modify( '-1 day' );
    echo '<table class="widefat striped khaiwal-table khaiwal-day-table"><caption class="screen-reader-text">' . esc_html( sprintf( __( 'Results for %s', 'khaiwal' ), $date ) ) . '</caption><thead><tr>';
    echo '<th scope="col" class="column-num">#</th><th scope="col">' . esc_html__( 'Game', 'khaiwal' ) . '</th><th scope="col">' . esc_html__( 'Time (IST)', 'khaiwal' ) . '</th><th scope="col">' . esc_html( sprintf( __( 'Previous (%s)', 'khaiwal' ), $previous_day->format( 'j M' ) ) ) . '</th><th scope="col" class="column-result">' . esc_html( sprintf( __( 'Result for %s', 'khaiwal' ), $day->format( 'j M' ) ) ) . '</th><th scope="col">' . esc_html__( 'Status', 'khaiwal' ) . '</th><th scope="col">' . esc_html__( 'Action', 'khaiwal' ) . '</th>';
    echo '</tr></thead><tbody>';
    $i = 0;
    foreach ( $games as $slug => $game ) {
        $i++;
        $override = isset( $published[ $date ][ $slug ] ) ? $published[ $date ][ $slug ]['number'] : '';
        $snapshot = khaiwal_snapshot_result( $game, $date );
        $previous = khaiwal_result_on( $game, $previous_day );
        $clock = khaiwal_game_clock( $game['time'] );
        $due = $date === $today && $clock && '' === $override && '' === $snapshot && ( $clock[0] * 60 + $clock[1] ) <= ( (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' ) );
        if ( '' !== $override ) { $status = '<span class="khaiwal-badge is-published">' . esc_html__( 'Published', 'khaiwal' ) . '</span>'; }
        elseif ( '' !== $snapshot ) { $status = '<span class="khaiwal-badge is-snapshot">' . esc_html__( 'Archive snapshot', 'khaiwal' ) . '</span>'; }
        elseif ( $due ) { $status = '<span class="khaiwal-badge is-due">' . esc_html__( 'Due now', 'khaiwal' ) . '</span>'; }
        else { $status = '<span class="khaiwal-badge">' . esc_html__( 'Awaiting', 'khaiwal' ) . '</span>'; }
        $id = 'khaiwal-result-' . $slug;
        echo '<tr class="' . ( $due ? 'is-due' : '' ) . '"><td class="column-num">' . esc_html( $i ) . '</td>';
        echo '<th scope="row" class="khaiwal-game-cell"><label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $game['name'] ) . '</strong><span lang="hi" class="khaiwal-hindi">' . esc_html( $game['hindi'] ) . '</span></label></th>';
        echo '<td>' . esc_html( $game['time'] ? $game['time'] : '—' ) . '</td>';
        echo '<td><code class="khaiwal-prev">' . esc_html( '' !== $previous ? $previous : '—' ) . '</code></td>';
        echo '<td class="column-result"><input class="khaiwal-result-input" type="text" id="' . esc_attr( $id ) . '" name="results[' . esc_attr( $date ) . '][' . esc_attr( $slug ) . ']" value="' . esc_attr( $override ) . '" data-original="' . esc_attr( $override ) . '" data-col="0" inputmode="numeric" pattern="[0-9]{2}" maxlength="2" placeholder="' . esc_attr( '' !== $snapshot ? $snapshot : '––' ) . '" aria-label="' . esc_attr( sprintf( __( '%1$s result for %2$s', 'khaiwal' ), $game['name'], $date ) ) . '"></td>';
        echo '<td>' . $status . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped literals above.
        echo '<td>' . ( '' !== $override ? '<button type="submit" class="button-link button-link-delete" name="khaiwal_unpublish" value="' . esc_attr( $date . '|' . $slug ) . '" data-confirm-remove>' . esc_html__( 'Remove', 'khaiwal' ) . '</button>' : '<span aria-hidden="true">—</span>' ) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}

function khaiwal_publish_month_table( $games, DateTimeImmutable $month, $today ) {
    $from = $month->format( 'Y-m-01' ); $to = $month->format( 'Y-m-t' );
    $published = khaiwal_published_map( $from, $to );
    echo '<div class="khaiwal-grid-scroll" tabindex="0" role="region" aria-label="' . esc_attr( sprintf( __( 'Results grid for %s', 'khaiwal' ), $month->format( 'F Y' ) ) ) . '">';
    echo '<table class="widefat striped khaiwal-table khaiwal-month-table"><thead><tr><th scope="col" class="khaiwal-sticky">' . esc_html__( 'Date', 'khaiwal' ) . '</th>';
    foreach ( $games as $game ) { echo '<th scope="col"><span>' . esc_html( $game['name'] ) . '</span><small>' . esc_html( $game['time'] ) . '</small></th>'; }
    echo '</tr></thead><tbody>';
    for ( $d = 1; $d <= (int) $month->format( 't' ); $d++ ) {
        $date = sprintf( '%s-%02d', $month->format( 'Y-m' ), $d );
        $future = $date > $today;
        echo '<tr class="' . ( $date === $today ? 'is-today' : '' ) . '"><th scope="row" class="khaiwal-sticky"><time datetime="' . esc_attr( $date ) . '">' . esc_html( DateTimeImmutable::createFromFormat( '!Y-m-d', $date )->format( 'D j' ) ) . '</time></th>';
        $col = 0;
        foreach ( $games as $slug => $game ) {
            $override = isset( $published[ $date ][ $slug ] ) ? $published[ $date ][ $slug ]['number'] : '';
            $snapshot = khaiwal_snapshot_result( $game, $date );
            echo '<td class="' . ( '' !== $override ? 'is-published' : '' ) . '">';
            if ( $future ) { echo '<span aria-hidden="true">·</span>'; }
            else { echo '<input class="khaiwal-result-input" type="text" name="results[' . esc_attr( $date ) . '][' . esc_attr( $slug ) . ']" value="' . esc_attr( $override ) . '" data-original="' . esc_attr( $override ) . '" data-col="' . esc_attr( $col ) . '" inputmode="numeric" pattern="[0-9]{2}" maxlength="2" placeholder="' . esc_attr( '' !== $snapshot ? $snapshot : '––' ) . '" aria-label="' . esc_attr( $game['name'] . ' ' . $date ) . '">'; }
            echo '</td>';
            $col++;
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
    echo '<p class="description">' . esc_html__( 'Greyed numbers are the archived snapshot; a number you type and publish replaces it. Published cells are highlighted.', 'khaiwal' ) . '</p>';
}

/** Quick Edit adds nothing for results (no title, no slug); keep the list tidy. */
function khaiwal_result_row_actions( $actions, $post ) {
    if ( 'khaiwal_result' === $post->post_type ) { unset( $actions['inline hide-if-no-js'] ); }
    return $actions;
}
add_filter( 'post_row_actions', 'khaiwal_result_row_actions', 10, 2 );
