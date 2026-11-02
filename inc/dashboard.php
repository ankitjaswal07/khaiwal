<?php
/** Schedule-driven hero and lightweight, same-origin result refresh. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Parse the editor's usual India-time schedule without accepting ambiguous dates. */
function khaiwal_game_clock( $clock ) {
    if ( ! is_string( $clock ) || ! preg_match( '/^\s*(0?[1-9]|1[0-2]):([0-5][0-9])\s*(AM|PM)\s*$/i', $clock, $matches ) ) { return null; }
    $hour = (int) $matches[1] % 12;
    if ( strtoupper( $matches[3] ) === 'PM' ) { $hour += 12; }
    return array( $hour, (int) $matches[2] );
}

/**
 * Current is the latest scheduled game; recent is the latest earlier published
 * result; next is the next scheduled game. Dates follow India time at midnight.
 */
function khaiwal_schedule_state( $games, DateTimeImmutable $now ) {
    $now = $now->setTimezone( new DateTimeZone( 'Asia/Kolkata' ) );
    $day = $now->setTime( 0, 0 );
    $events = array();
    for ( $offset = -7; $offset <= 1; $offset++ ) {
        $date = $day->modify( ( $offset >= 0 ? '+' : '' ) . $offset . ' days' );
        $order = 0;
        foreach ( $games as $game ) {
            $clock = khaiwal_game_clock( $game['time'] );
            if ( $clock ) { $events[] = array( 'game' => $game, 'date' => $date->setTime( $clock[0], $clock[1] ), 'order' => $order ); }
            $order++;
        }
    }
    usort( $events, function ( $a, $b ) {
        $compare = $a['date']->getTimestamp() <=> $b['date']->getTimestamp();
        return $compare ?: ( $a['order'] <=> $b['order'] );
    } );
    $current_index = -1;
    foreach ( $events as $index => $event ) {
        if ( $event['date']->getTimestamp() <= $now->getTimestamp() ) { $current_index = $index; } else { break; }
    }
    $current = $current_index >= 0 ? $events[ $current_index ] : null;
    $next = isset( $events[ $current_index + 1 ] ) ? $events[ $current_index + 1 ] : null;
    $recent = null;
    for ( $index = $current_index - 1; $index >= 0; $index-- ) {
        $value = khaiwal_result_on( $events[ $index ]['game'], $events[ $index ]['date'] );
        if ( $value !== '' ) { $recent = $events[ $index ]; $recent['result'] = $value; break; }
    }
    if ( ! $recent && $current ) { $recent = khaiwal_recent_before( $games, $current['date'] ); }
    if ( $current ) { $current['result'] = khaiwal_result_on( $current['game'], $current['date'] ); }
    return array( 'current' => $current, 'recent' => $recent, 'next' => $next );
}

/** Find an older published result when the previous week has no entries. */
function khaiwal_recent_before( $games, DateTimeImmutable $cutoff ) {
    if ( ! $games ) { return null; }
    global $wpdb;
    $slugs = array_keys( $games );
    $placeholders = implode( ',', array_fill( 0, count( $slugs ), '%s' ) );
    $sql = "SELECT DISTINCT SUBSTR(result_date.meta_value, 1, 7)
        FROM {$wpdb->posts} AS result
        INNER JOIN {$wpdb->postmeta} AS result_game ON result_game.post_id = result.ID AND result_game.meta_key = '_khaiwal_game'
        INNER JOIN {$wpdb->postmeta} AS result_date ON result_date.post_id = result.ID AND result_date.meta_key = '_khaiwal_date'
        WHERE result.post_type = 'khaiwal_result' AND result.post_status = 'publish' AND result_game.meta_value IN ($placeholders)";
    $months = $wpdb->get_col( $wpdb->prepare( $sql, $slugs ) );
    foreach ( $games as $game ) { $months = array_merge( $months, array_keys( $game['results'] ) ); }
    $months = array_unique( array_filter( $months, function ( $month ) use ( $cutoff ) {
        return preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $month ) && $month <= $cutoff->format( 'Y-m' );
    } ) );
    rsort( $months );
    foreach ( $months as $month ) {
        $best = null;
        foreach ( $games as $game ) {
            $clock = khaiwal_game_clock( $game['time'] );
            if ( ! $clock ) { continue; }
            foreach ( khaiwal_results_for_month( $game, $month ) as $date => $number ) {
                if ( ! khaiwal_valid_date( $date ) ) { continue; }
                $when = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, new DateTimeZone( 'Asia/Kolkata' ) )->setTime( $clock[0], $clock[1] );
                if ( $when->getTimestamp() < $cutoff->getTimestamp() && ( ! $best || $when->getTimestamp() > $best['date']->getTimestamp() ) ) {
                    $best = array( 'game' => $game, 'date' => $when, 'result' => $number );
                }
            }
        }
        if ( $best ) { return $best; }
    }
    return null;
}

function khaiwal_dashboard_event( $event ) {
    if ( ! $event ) { return null; }
    $game = $event['game'];
    return array(
        'slug' => $game['slug'], 'name' => $game['name'], 'hindi' => $game['hindi'],
        'time' => $game['time'], 'url' => khaiwal_game_url( $game ),
        'date' => $event['date']->format( 'Y-m-d' ), 'timestamp' => $event['date']->getTimestamp(),
        'result' => isset( $event['result'] ) ? $event['result'] : '',
    );
}

function khaiwal_dashboard_data( $games = null, $now = null ) {
    if ( null === $games ) { $games = khaiwal_games(); }
    if ( null === $now ) { $now = khaiwal_now(); }
    $now = $now->setTimezone( new DateTimeZone( 'Asia/Kolkata' ) );
    $state = khaiwal_schedule_state( $games, $now );
    $cards = array();
    foreach ( $games as $game ) {
        $cards[ $game['slug'] ] = array(
            'today' => khaiwal_result_on( $game, $now ),
            'previous' => khaiwal_result_on( $game, $now->modify( '-1 day' ) ),
        );
    }
    return array(
        'server_ts' => $now->getTimestamp(), 'today' => $now->format( 'Y-m-d' ),
        'current' => khaiwal_dashboard_event( $state['current'] ),
        'recent' => khaiwal_dashboard_event( $state['recent'] ),
        'next' => khaiwal_dashboard_event( $state['next'] ), 'cards' => $cards,
    );
}

function khaiwal_register_dashboard_route() {
    register_rest_route( 'khaiwal/v1', '/dashboard', array(
        'methods' => WP_REST_Server::READABLE, 'permission_callback' => '__return_true',
        'args' => array( 'month' => array(
            'required' => false,
            'validate_callback' => function ( $value ) { return is_string( $value ) && preg_match( '/^(20[0-9]{2}|2100)-(0[1-9]|1[0-2])$/', $value ); },
        ) ),
        'callback' => function ( WP_REST_Request $request ) {
            $games = khaiwal_games();
            $data = khaiwal_dashboard_data( $games );
            $month = $request->get_param( 'month' );
            if ( $month ) {
                $data['chart'] = array();
                foreach ( $games as $game ) { $data['chart'][ $game['slug'] ] = khaiwal_results_for_month( $game, $month ); }
            }
            $response = rest_ensure_response( $data );
            $response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
            $response->header( 'X-Robots-Tag', 'noindex' );
            return $response;
        },
    ) );
}
add_action( 'rest_api_init', 'khaiwal_register_dashboard_route' );
