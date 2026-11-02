<?php
/** Contact list: every contact-form message is stored and shown in wp-admin and on the Dashboard. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function khaiwal_contact_capability() { return apply_filters( 'khaiwal_contact_capability', 'edit_others_posts' ); }

function khaiwal_register_contact_type() {
    $cap = khaiwal_contact_capability();
    register_post_type( 'khaiwal_contact', array(
        'labels' => array( 'name' => __( 'Contacts', 'khaiwal' ), 'singular_name' => __( 'Contact', 'khaiwal' ), 'menu_name' => __( 'Contacts', 'khaiwal' ), 'all_items' => __( 'All contacts', 'khaiwal' ), 'edit_item' => __( 'Contact details', 'khaiwal' ), 'search_items' => __( 'Search contacts', 'khaiwal' ), 'not_found' => __( 'No contact requests yet.', 'khaiwal' ) ),
        'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'show_in_rest' => false, 'exclude_from_search' => true,
        'menu_icon' => 'dashicons-email-alt', 'menu_position' => 5, 'supports' => false,
        'capabilities' => array(
            'edit_posts' => $cap, 'edit_others_posts' => $cap, 'publish_posts' => $cap, 'read_private_posts' => $cap, 'delete_posts' => $cap,
            'delete_private_posts' => $cap, 'delete_published_posts' => $cap, 'delete_others_posts' => $cap, 'edit_private_posts' => $cap, 'edit_published_posts' => $cap,
            'create_posts' => 'do_not_allow',
        ),
        'map_meta_cap' => true,
    ) );
}
add_action( 'init', 'khaiwal_register_contact_type' );

/** Save one contact request. Returns the post ID or 0. */
function khaiwal_store_contact( $name, $email, $phone, $message ) {
    $id = wp_insert_post( array(
        'post_type' => 'khaiwal_contact', 'post_status' => 'publish', 'post_author' => 0,
        'post_title' => trim( $name . ' — ' . ( $email ? $email : $phone ) ),
    ), true );
    if ( is_wp_error( $id ) || ! $id ) { return 0; }
    update_post_meta( $id, '_khaiwal_name', $name );
    update_post_meta( $id, '_khaiwal_email', $email );
    update_post_meta( $id, '_khaiwal_phone', $phone );
    update_post_meta( $id, '_khaiwal_message', $message );
    delete_transient( 'khaiwal_contact_new_count' );
    return (int) $id;
}

function khaiwal_contact_new_count() {
    $cached = get_transient( 'khaiwal_contact_new_count' );
    if ( false !== $cached ) { return (int) $cached; }
    $query = new WP_Query( array( 'post_type' => 'khaiwal_contact', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_khaiwal_handled', 'compare' => 'NOT EXISTS' ) ) ) );
    set_transient( 'khaiwal_contact_new_count', (int) $query->found_posts, HOUR_IN_SECONDS );
    return (int) $query->found_posts;
}

function khaiwal_contact_phone_link( $phone ) {
    $digits = preg_replace( '/\D+/', '', (string) $phone );
    if ( ! $digits ) { return ''; }
    return '<a href="' . esc_url( 'tel:+' . $digits ) . '">' . esc_html( $phone ) . '</a> · <a href="' . esc_url( 'https://wa.me/' . $digits ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'WhatsApp', 'khaiwal' ) . '</a>';
}

/* Menu bubble with the number of unhandled requests. */
function khaiwal_contact_menu_bubble() {
    global $menu;
    if ( ! current_user_can( khaiwal_contact_capability() ) || ! is_array( $menu ) ) { return; }
    $count = khaiwal_contact_new_count();
    if ( ! $count ) { return; }
    foreach ( $menu as $key => $item ) {
        if ( isset( $item[2] ) && 'edit.php?post_type=khaiwal_contact' === $item[2] ) { $menu[ $key ][0] .= ' <span class="awaiting-mod">' . (int) $count . '</span>'; break; } // phpcs:ignore WordPress.WP.GlobalVariablesOverride
    }
}
add_action( 'admin_menu', 'khaiwal_contact_menu_bubble', 99 );

/* List table. */
function khaiwal_contact_columns( $columns ) {
    return array(
        'cb' => $columns['cb'], 'khaiwal_name' => __( 'Name', 'khaiwal' ), 'khaiwal_contact' => __( 'Email / phone', 'khaiwal' ),
        'khaiwal_message' => __( 'Message', 'khaiwal' ), 'khaiwal_status' => __( 'Status', 'khaiwal' ), 'date' => __( 'Received', 'khaiwal' ),
    );
}
add_filter( 'manage_khaiwal_contact_posts_columns', 'khaiwal_contact_columns' );

function khaiwal_contact_column_content( $column, $id ) {
    if ( 'khaiwal_name' === $column ) {
        echo '<strong><a class="row-title" href="' . esc_url( get_edit_post_link( $id ) ) . '">' . esc_html( get_post_meta( $id, '_khaiwal_name', true ) ) . '</a></strong>';
    } elseif ( 'khaiwal_contact' === $column ) {
        $email = get_post_meta( $id, '_khaiwal_email', true ); $phone = get_post_meta( $id, '_khaiwal_phone', true );
        if ( $email ) { echo '<a href="' . esc_url( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a><br>'; }
        echo khaiwal_contact_phone_link( $phone ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper.
    } elseif ( 'khaiwal_message' === $column ) {
        echo esc_html( khaiwal_trim( get_post_meta( $id, '_khaiwal_message', true ), 140 ) );
    } elseif ( 'khaiwal_status' === $column ) {
        echo get_post_meta( $id, '_khaiwal_handled', true ) ? '<span style="color:#1d6b2f;font-weight:600">' . esc_html__( 'Handled', 'khaiwal' ) . '</span>' : '<span style="color:#b32d2e;font-weight:600">' . esc_html__( 'New', 'khaiwal' ) . '</span>';
    }
}
add_action( 'manage_khaiwal_contact_posts_custom_column', 'khaiwal_contact_column_content', 10, 2 );
add_filter( 'list_table_primary_column', function ( $column, $screen ) { return 'edit-khaiwal_contact' === $screen ? 'khaiwal_name' : $column; }, 10, 2 );

function khaiwal_contact_row_actions( $actions, $post ) {
    if ( 'khaiwal_contact' !== $post->post_type ) { return $actions; }
    unset( $actions['inline hide-if-no-js'] );
    $handled = (bool) get_post_meta( $post->ID, '_khaiwal_handled', true );
    $url = wp_nonce_url( add_query_arg( array( 'action' => 'khaiwal_toggle_contact', 'contact' => $post->ID ), admin_url( 'admin-post.php' ) ), 'khaiwal_toggle_contact_' . $post->ID );
    $actions['khaiwal_toggle'] = '<a href="' . esc_url( $url ) . '">' . esc_html( $handled ? __( 'Mark as new', 'khaiwal' ) : __( 'Mark as handled', 'khaiwal' ) ) . '</a>';
    return $actions;
}
add_filter( 'post_row_actions', 'khaiwal_contact_row_actions', 10, 2 );

function khaiwal_toggle_contact() {
    $id = isset( $_GET['contact'] ) ? absint( $_GET['contact'] ) : 0;
    check_admin_referer( 'khaiwal_toggle_contact_' . $id );
    if ( ! $id || 'khaiwal_contact' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) { wp_die( esc_html__( 'Not allowed.', 'khaiwal' ), '', array( 'response' => 403 ) ); }
    if ( get_post_meta( $id, '_khaiwal_handled', true ) ) { delete_post_meta( $id, '_khaiwal_handled' ); } else { update_post_meta( $id, '_khaiwal_handled', '1' ); }
    delete_transient( 'khaiwal_contact_new_count' );
    wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=khaiwal_contact' ) );
    exit;
}
add_action( 'admin_post_khaiwal_toggle_contact', 'khaiwal_toggle_contact' );

/* "Export CSV" button on the list screen. */
function khaiwal_contact_export_button( $which ) {
    if ( 'top' !== $which || ! isset( $_GET['post_type'] ) || 'khaiwal_contact' !== $_GET['post_type'] || ! current_user_can( khaiwal_contact_capability() ) ) { return; } // phpcs:ignore WordPress.Security.NonceVerification
    echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=khaiwal_export_contacts' ), 'khaiwal_export_contacts' ) ) . '">' . esc_html__( 'Export CSV', 'khaiwal' ) . '</a>';
}
add_action( 'manage_posts_extra_tablenav', 'khaiwal_contact_export_button' );

function khaiwal_export_contacts() {
    if ( ! current_user_can( khaiwal_contact_capability() ) ) { wp_die( esc_html__( 'Not allowed.', 'khaiwal' ), '', array( 'response' => 403 ) ); }
    check_admin_referer( 'khaiwal_export_contacts' );
    $ids = get_posts( array( 'post_type' => 'khaiwal_contact', 'post_status' => 'publish', 'posts_per_page' => 5000, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true ) );
    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="khaiwal-contacts-' . gmdate( 'Y-m-d' ) . '.csv"' );
    $out = fopen( 'php://output', 'w' );
    fwrite( $out, "\xEF\xBB\xBF" );
    fputcsv( $out, array( 'received', 'name', 'email', 'phone', 'message', 'status' ) );
    $safe = function ( $value ) { $value = (string) $value; return ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@' ), true ) ) ? "'" . $value : $value; }; // Spreadsheet formula-injection guard.
    foreach ( $ids as $id ) {
        fputcsv( $out, array( get_post_time( 'Y-m-d H:i', false, $id ), $safe( get_post_meta( $id, '_khaiwal_name', true ) ), $safe( get_post_meta( $id, '_khaiwal_email', true ) ), $safe( get_post_meta( $id, '_khaiwal_phone', true ) ), $safe( get_post_meta( $id, '_khaiwal_message', true ) ), get_post_meta( $id, '_khaiwal_handled', true ) ? 'handled' : 'new' ) );
    }
    fclose( $out );
    exit;
}
add_action( 'admin_post_khaiwal_export_contacts', 'khaiwal_export_contacts' );

/* Edit screen: read-only details plus a handled checkbox. */
function khaiwal_contact_meta_box_register() {
    add_meta_box( 'khaiwal_contact_details', __( 'Contact details', 'khaiwal' ), 'khaiwal_contact_meta_box', 'khaiwal_contact', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'khaiwal_contact_meta_box_register' );

function khaiwal_contact_meta_box( $post ) {
    wp_nonce_field( 'khaiwal_contact_details', 'khaiwal_contact_nonce' );
    $email = get_post_meta( $post->ID, '_khaiwal_email', true ); $phone = get_post_meta( $post->ID, '_khaiwal_phone', true );
    echo '<table class="form-table" role="presentation"><tbody>';
    echo '<tr><th>' . esc_html__( 'Name', 'khaiwal' ) . '</th><td>' . esc_html( get_post_meta( $post->ID, '_khaiwal_name', true ) ) . '</td></tr>';
    echo '<tr><th>' . esc_html__( 'Email', 'khaiwal' ) . '</th><td>' . ( $email ? '<a href="' . esc_url( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a>' : '—' ) . '</td></tr>';
    echo '<tr><th>' . esc_html__( 'Phone / WhatsApp', 'khaiwal' ) . '</th><td>' . ( $phone ? khaiwal_contact_phone_link( $phone ) : '—' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
    echo '<tr><th>' . esc_html__( 'Received', 'khaiwal' ) . '</th><td>' . esc_html( get_the_date( 'j M Y, g:i a', $post ) ) . '</td></tr>';
    echo '<tr><th>' . esc_html__( 'Message', 'khaiwal' ) . '</th><td>' . nl2br( esc_html( get_post_meta( $post->ID, '_khaiwal_message', true ) ) ) . '</td></tr>';
    echo '<tr><th>' . esc_html__( 'Status', 'khaiwal' ) . '</th><td><label><input type="checkbox" name="khaiwal_handled" value="1" ' . checked( (bool) get_post_meta( $post->ID, '_khaiwal_handled', true ), true, false ) . '> ' . esc_html__( 'Handled', 'khaiwal' ) . '</label></td></tr>';
    echo '</tbody></table>';
}

function khaiwal_contact_save( $id ) {
    if ( ! isset( $_POST['khaiwal_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['khaiwal_contact_nonce'] ) ), 'khaiwal_contact_details' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) { return; }
    if ( ! empty( $_POST['khaiwal_handled'] ) ) { update_post_meta( $id, '_khaiwal_handled', '1' ); } else { delete_post_meta( $id, '_khaiwal_handled' ); }
    delete_transient( 'khaiwal_contact_new_count' );
}
add_action( 'save_post_khaiwal_contact', 'khaiwal_contact_save' );
add_action( 'trashed_post', function ( $id ) { if ( 'khaiwal_contact' === get_post_type( $id ) ) { delete_transient( 'khaiwal_contact_new_count' ); } } );

/* Dashboard widget. */
function khaiwal_contact_dashboard_widget() {
    if ( ! current_user_can( khaiwal_contact_capability() ) ) { return; }
    wp_add_dashboard_widget( 'khaiwal_contacts_widget', __( 'Contact requests', 'khaiwal' ), 'khaiwal_contact_dashboard_render' );
}
add_action( 'wp_dashboard_setup', 'khaiwal_contact_dashboard_widget' );

/** Show the contact list first, above the default WordPress widgets (users can still drag it). */
function khaiwal_contact_dashboard_first() {
    global $wp_meta_boxes;
    if ( empty( $wp_meta_boxes['dashboard']['normal']['core']['khaiwal_contacts_widget'] ) ) { return; }
    $widget = $wp_meta_boxes['dashboard']['normal']['core']['khaiwal_contacts_widget'];
    unset( $wp_meta_boxes['dashboard']['normal']['core']['khaiwal_contacts_widget'] );
    $wp_meta_boxes['dashboard']['normal']['core'] = array( 'khaiwal_contacts_widget' => $widget ) + $wp_meta_boxes['dashboard']['normal']['core']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
}
add_action( 'wp_dashboard_setup', 'khaiwal_contact_dashboard_first', 99 );

function khaiwal_contact_dashboard_render() {
    $ids = get_posts( array( 'post_type' => 'khaiwal_contact', 'post_status' => 'publish', 'posts_per_page' => 6, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true ) );
    $new = khaiwal_contact_new_count();
    echo '<p><strong>' . esc_html( sprintf( _n( '%d new request', '%d new requests', $new, 'khaiwal' ), $new ) ) . '</strong></p>';
    if ( ! $ids ) { echo '<p>' . esc_html__( 'Nobody has used the contact form yet.', 'khaiwal' ) . '</p>'; }
    else {
        echo '<ul style="margin:0">';
        foreach ( $ids as $id ) {
            $email = get_post_meta( $id, '_khaiwal_email', true ); $phone = get_post_meta( $id, '_khaiwal_phone', true );
            echo '<li style="padding:8px 0;border-bottom:1px solid #f0f0f1"><strong><a href="' . esc_url( get_edit_post_link( $id ) ) . '">' . esc_html( get_post_meta( $id, '_khaiwal_name', true ) ) . '</a></strong> ' . ( get_post_meta( $id, '_khaiwal_handled', true ) ? '' : '<span style="background:#d63638;color:#fff;border-radius:9px;padding:1px 7px;font-size:11px">' . esc_html__( 'New', 'khaiwal' ) . '</span>' ) . '<br>';
            if ( $email ) { echo '<a href="' . esc_url( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a> '; }
            echo khaiwal_contact_phone_link( $phone ); // phpcs:ignore WordPress.Security.EscapeOutput
            echo '<br><span style="color:#50575e">' . esc_html( khaiwal_trim( get_post_meta( $id, '_khaiwal_message', true ), 110 ) ) . '</span> <em style="color:#8c8f94">' . esc_html( human_time_diff( get_post_time( 'U', true, $id ) ) . ' ' . __( 'ago', 'khaiwal' ) ) . '</em></li>';
        }
        echo '</ul>';
    }
    echo '<p style="margin-bottom:0"><a class="button button-primary" href="' . esc_url( admin_url( 'edit.php?post_type=khaiwal_contact' ) ) . '">' . esc_html__( 'View all contacts', 'khaiwal' ) . '</a> <a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=khaiwal_export_contacts' ), 'khaiwal_export_contacts' ) ) . '">' . esc_html__( 'Export CSV', 'khaiwal' ) . '</a></p>';
}
