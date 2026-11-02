<?php
/** Editorial tools for game details and published results. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function khaiwal_add_meta_boxes() {
    add_meta_box( 'khaiwal_game_details', __( 'Game details', 'khaiwal' ), 'khaiwal_game_meta_box', 'khaiwal_game', 'side' );
    add_meta_box( 'khaiwal_result_details', __( 'Result details', 'khaiwal' ), 'khaiwal_result_meta_box', 'khaiwal_result', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'khaiwal_add_meta_boxes' );

function khaiwal_game_meta_box( $post ) {
    wp_nonce_field( 'khaiwal_game_details', 'khaiwal_game_nonce' );
    $time = get_post_meta( $post->ID, '_khaiwal_time', true );
    $hi = get_post_meta( $post->ID, '_khaiwal_hindi', true );
    echo '<p><label for="khaiwal_time">' . esc_html__( 'Usual result time (IST)', 'khaiwal' ) . '</label><br><input class="widefat" id="khaiwal_time" name="khaiwal_time" value="' . esc_attr( $time ) . '" placeholder="3:15 PM"></p>';
    echo '<p><label for="khaiwal_hindi">' . esc_html__( 'Hindi game name', 'khaiwal' ) . '</label><br><input class="widefat" id="khaiwal_hindi" name="khaiwal_hindi" value="' . esc_attr( $hi ) . '"></p>';
}

function khaiwal_result_meta_box( $post ) {
    wp_nonce_field( 'khaiwal_result_details', 'khaiwal_result_nonce' );
    $selected = get_post_meta( $post->ID, '_khaiwal_game', true );
    $date = get_post_meta( $post->ID, '_khaiwal_date', true );
    $number = get_post_meta( $post->ID, '_khaiwal_number', true );
    echo '<p>' . esc_html__( 'Publish this post to display the result. A published result overrides the archived snapshot for the same game and date.', 'khaiwal' ) . '</p>';
    echo '<p><label for="khaiwal_game"><strong>' . esc_html__( 'Game', 'khaiwal' ) . '</strong></label><br><select id="khaiwal_game" name="khaiwal_game" required><option value="">' . esc_html__( 'Select a game', 'khaiwal' ) . '</option>';
    foreach ( khaiwal_games() as $game ) { echo '<option value="' . esc_attr( $game['slug'] ) . '" ' . selected( $selected, $game['slug'], false ) . '>' . esc_html( $game['name'] ) . '</option>'; }
    echo '</select></p>';
    echo '<p><label for="khaiwal_date"><strong>' . esc_html__( 'Result date (IST)', 'khaiwal' ) . '</strong></label><br><input type="date" id="khaiwal_date" name="khaiwal_date" required value="' . esc_attr( $date ) . '"></p>';
    echo '<p><label for="khaiwal_number"><strong>' . esc_html__( 'Result number (00–99)', 'khaiwal' ) . '</strong></label><br><input id="khaiwal_number" name="khaiwal_number" inputmode="numeric" pattern="[0-9]{2}" maxlength="2" required value="' . esc_attr( $number ) . '"><br><small>' . esc_html__( 'Keep leading zeroes, for example 07.', 'khaiwal' ) . '</small></p>';
}

function khaiwal_save_game( $id ) {
    if ( ! isset( $_POST['khaiwal_game_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['khaiwal_game_nonce'] ) ), 'khaiwal_game_details' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) { return; }
    update_post_meta( $id, '_khaiwal_time', isset( $_POST['khaiwal_time'] ) ? sanitize_text_field( wp_unslash( $_POST['khaiwal_time'] ) ) : '' );
    update_post_meta( $id, '_khaiwal_hindi', isset( $_POST['khaiwal_hindi'] ) ? sanitize_text_field( wp_unslash( $_POST['khaiwal_hindi'] ) ) : '' );
}
add_action( 'save_post_khaiwal_game', 'khaiwal_save_game' );

function khaiwal_save_result( $id ) {
    if ( ! isset( $_POST['khaiwal_result_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['khaiwal_result_nonce'] ) ), 'khaiwal_result_details' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) { return; }
    $slug = isset( $_POST['khaiwal_game'] ) ? sanitize_key( wp_unslash( $_POST['khaiwal_game'] ) ) : '';
    $date = isset( $_POST['khaiwal_date'] ) ? sanitize_text_field( wp_unslash( $_POST['khaiwal_date'] ) ) : '';
    $number = isset( $_POST['khaiwal_number'] ) ? sanitize_text_field( wp_unslash( $_POST['khaiwal_number'] ) ) : '';
    $games = khaiwal_games();
    if ( ! isset( $games[ $slug ] ) || ! khaiwal_valid_date( $date ) || ! preg_match( '/^\d{2}$/', $number ) ) {
        set_transient( 'khaiwal_result_error_' . get_current_user_id(), 1, 60 );
        return;
    }
    update_post_meta( $id, '_khaiwal_game', $slug );
    update_post_meta( $id, '_khaiwal_date', $date );
    update_post_meta( $id, '_khaiwal_number', $number );
}
add_action( 'save_post_khaiwal_result', 'khaiwal_save_result' );

function khaiwal_result_error_notice() {
    $key = 'khaiwal_result_error_' . get_current_user_id();
    if ( get_transient( $key ) ) {
        delete_transient( $key );
        echo '<div class="notice notice-error"><p>' . esc_html__( 'Result details were not saved. Select a valid game and date and enter exactly two digits.', 'khaiwal' ) . '</p></div>';
    }
}
add_action( 'admin_notices', 'khaiwal_result_error_notice' );

function khaiwal_import_menu() {
    add_submenu_page( 'edit.php?post_type=khaiwal_result', __( 'Import results', 'khaiwal' ), __( 'Import CSV', 'khaiwal' ), 'publish_posts', 'khaiwal-import', 'khaiwal_import_screen' );
}
add_action( 'admin_menu', 'khaiwal_import_menu' );

function khaiwal_import_screen() {
    if ( ! current_user_can( 'publish_posts' ) ) { return; }
    echo '<div class="wrap"><h1>' . esc_html__( 'Import game results', 'khaiwal' ) . '</h1>';
    if ( isset( $_GET['imported'] ) ) {
        printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html( sprintf( __( 'Imported or updated %1$d rows. Skipped %2$d rows (invalid or not editable).', 'khaiwal' ), absint( $_GET['imported'] ), isset( $_GET['skipped'] ) ? absint( $_GET['skipped'] ) : 0 ) ) );
    }
    echo '<p>' . esc_html__( 'Upload a CSV with the headers game_slug,date,result. Dates must be YYYY-MM-DD and results must be two digits. Existing published entries for the same game and date are updated.', 'khaiwal' ) . '</p>';
    echo '<p><code>game_slug,date,result<br>delhi-bazar,2026-11-01,30<br>madhupuri,2026-11-01,09</code></p>';
    echo '<p>' . esc_html__( 'Game slugs are the last part of each game URL. The file is processed locally and is not retained.', 'khaiwal' ) . '</p>';
    echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    wp_nonce_field( 'khaiwal_import' );
    echo '<input type="hidden" name="action" value="khaiwal_import_results"><input type="file" name="results_csv" accept=".csv,text/csv" required> ';
    submit_button( __( 'Import results', 'khaiwal' ), 'primary', 'submit', false );
    echo '</form></div>';
}

function khaiwal_import_results() {
    if ( ! current_user_can( 'publish_posts' ) ) { wp_die( esc_html__( 'Not allowed.', 'khaiwal' ), '', array( 'response' => 403 ) ); }
    check_admin_referer( 'khaiwal_import' );
    if ( empty( $_FILES['results_csv']['tmp_name'] ) || ! is_string( $_FILES['results_csv']['tmp_name'] ) || ! isset( $_FILES['results_csv']['name'], $_FILES['results_csv']['size'] ) || ! is_string( $_FILES['results_csv']['name'] ) || ! is_numeric( $_FILES['results_csv']['size'] ) || ! is_uploaded_file( $_FILES['results_csv']['tmp_name'] ) || ! empty( $_FILES['results_csv']['error'] ) || $_FILES['results_csv']['size'] > 2 * MB_IN_BYTES || strtolower( pathinfo( sanitize_file_name( $_FILES['results_csv']['name'] ), PATHINFO_EXTENSION ) ) !== 'csv' ) {
        wp_die( esc_html__( 'Please upload a CSV file smaller than 2 MB.', 'khaiwal' ), '', array( 'response' => 400 ) );
    }
    $file = fopen( $_FILES['results_csv']['tmp_name'], 'r' );
    if ( ! $file ) { wp_die( esc_html__( 'Could not read the CSV.', 'khaiwal' ), '', array( 'response' => 400 ) ); }
    $header = fgetcsv( $file );
    if ( ! is_array( $header ) ) { fclose( $file ); wp_die( esc_html__( 'The CSV is empty.', 'khaiwal' ), '', array( 'response' => 400 ) ); }
    $header = array_map( function( $value ) { return strtolower( trim( preg_replace( '/^\xEF\xBB\xBF/', '', $value ) ) ); }, $header );
    $cols = array_flip( $header );
    if ( ! isset( $cols['game_slug'], $cols['date'], $cols['result'] ) ) { fclose( $file ); wp_die( esc_html__( 'CSV must contain game_slug,date,result headers.', 'khaiwal' ), '', array( 'response' => 400 ) ); }
    // Reject oversize row counts before inserting anything: do not silently drop a file's tail.
    $rows = array();
    while ( ( $row = fgetcsv( $file ) ) !== false ) {
        if ( count( $rows ) >= 5000 ) { fclose( $file ); wp_die( esc_html__( 'The CSV contains more than 5,000 rows. Split it into smaller files; no rows were imported.', 'khaiwal' ), '', array( 'response' => 400 ) ); }
        $rows[] = $row;
    }
    fclose( $file );
    $games = khaiwal_games(); $imported = 0; $skipped = 0;
    foreach ( $rows as $row ) {
        $slug = sanitize_key( isset( $row[ $cols['game_slug'] ] ) ? trim( $row[ $cols['game_slug'] ] ) : '' );
        $date = isset( $row[ $cols['date'] ] ) ? trim( $row[ $cols['date'] ] ) : '';
        $number = isset( $row[ $cols['result'] ] ) ? trim( $row[ $cols['result'] ] ) : '';
        if ( ! isset( $games[ $slug ] ) || ! khaiwal_valid_date( $date ) || ! preg_match( '/^\d{2}$/', $number ) ) { $skipped++; continue; }
        $existing = get_posts( array( 'post_type' => 'khaiwal_result', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_query' => array(
            array( 'key' => '_khaiwal_game', 'value' => $slug ), array( 'key' => '_khaiwal_date', 'value' => $date ),
        ) ) );
        $data = array( 'post_type' => 'khaiwal_result', 'post_status' => 'publish', 'post_title' => $games[ $slug ]['name'] . ' – ' . $date );
        if ( $existing ) { if ( ! current_user_can( 'edit_post', $existing[0] ) ) { $skipped++; continue; } $data['ID'] = $existing[0]; }
        $id = wp_insert_post( $data, true );
        if ( is_wp_error( $id ) ) { $skipped++; continue; }
        update_post_meta( $id, '_khaiwal_game', $slug );
        update_post_meta( $id, '_khaiwal_date', $date );
        update_post_meta( $id, '_khaiwal_number', $number );
        $imported++;
    }
    wp_safe_redirect( add_query_arg( array( 'post_type' => 'khaiwal_result', 'page' => 'khaiwal-import', 'imported' => $imported, 'skipped' => $skipped ), admin_url( 'edit.php' ) ) );
    exit;
}
add_action( 'admin_post_khaiwal_import_results', 'khaiwal_import_results' );

/** Contact form is intentionally simple; page content can instead use any WP form plugin. */
function khaiwal_send_message() {
    $return = khaiwal_contact_url();
    // Reject array-shaped fields before passing untrusted input to string functions.
    foreach ( array( 'khaiwal_contact_nonce', 'name', 'email', 'message', 'website' ) as $field ) {
        if ( isset( $_POST[ $field ] ) && ! is_string( $_POST[ $field ] ) ) { wp_safe_redirect( add_query_arg( 'message', 'error', $return ) ); exit; }
    }
    if ( ! isset( $_POST['khaiwal_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['khaiwal_contact_nonce'] ) ), 'khaiwal_contact' ) ) { wp_safe_redirect( add_query_arg( 'message', 'error', $return ) ); exit; }
    $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
    $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $body = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
    $honeypot = isset( $_POST['website'] ) ? trim( wp_unslash( $_POST['website'] ) ) : '';
    if ( $honeypot || ! $name || strlen( $name ) > 400 || ! is_email( $email ) || strlen( $email ) > 200 || ! $body || strlen( $body ) > 20000 ) { wp_safe_redirect( add_query_arg( 'message', 'error', $return ) ); exit; }
    $address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
    $rate_key = 'khaiwal_contact_' . md5( $address );
    if ( get_transient( $rate_key ) ) { wp_safe_redirect( add_query_arg( 'message', 'error', $return ) ); exit; }
    set_transient( $rate_key, 1, MINUTE_IN_SECONDS );
    $sent = wp_mail( get_option( 'admin_email' ), 'Website enquiry from ' . $name, $body . "\n\nFrom: " . $name . ' <' . $email . '>', array( 'Reply-To: ' . $email ) );
    wp_safe_redirect( add_query_arg( 'message', $sent ? 'sent' : 'error', $return ) ); exit;
}
add_action( 'admin_post_nopriv_khaiwal_contact', 'khaiwal_send_message' );
add_action( 'admin_post_khaiwal_contact', 'khaiwal_send_message' );
