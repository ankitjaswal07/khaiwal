<?php
/**
 * Agent- and AI-readiness: llms.txt, llms-full.txt, robots.txt crawler rules, a public read-only
 * JSON API with an OpenAPI description, and discovery links.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

const KHAIWAL_DISCLAIMER = 'Informational results only. Past results do not predict future results; no predictions or betting advice are given. Check the laws that apply to you.';

function khaiwal_llms_url( $full = false ) { return home_url( $full ? '/llms-full.txt' : '/llms.txt' ); }
function khaiwal_api_url( $route = 'results', $args = array() ) { return add_query_arg( $args, rest_url( 'khaiwal/v1/' . $route ) ); }

/* ---------------------------------------------------------------------------------------------
 * Discovery: head links, HTTP Link header, robots.txt
 * ------------------------------------------------------------------------------------------ */

function khaiwal_agent_head_links() {
    echo "\n" . '<link rel="alternate" type="text/plain" href="' . esc_url( khaiwal_llms_url() ) . '" title="LLM-readable site summary (llms.txt)">' . "\n";
    echo '<link rel="service-desc" type="application/vnd.oai.openapi+json" href="' . esc_url( khaiwal_api_url( 'openapi' ) ) . '" title="Khaiwal results API (OpenAPI)">' . "\n";
    if ( is_singular( 'khaiwal_game' ) && function_exists( 'khaiwal_current_game' ) ) {
        $game = khaiwal_current_game();
        if ( $game ) { echo '<link rel="alternate" type="application/json" href="' . esc_url( khaiwal_api_url( 'results', array( 'game' => $game['slug'], 'month' => khaiwal_now()->format( 'Y-m' ) ) ) ) . '" title="' . esc_attr( $game['name'] . ' results (JSON)' ) . '">' . "\n"; }
    }
}
add_action( 'wp_head', 'khaiwal_agent_head_links', 2 );

function khaiwal_agent_link_header() {
    if ( is_admin() || headers_sent() ) { return; }
    header( 'Link: <' . esc_url_raw( khaiwal_api_url( 'openapi' ) ) . '>; rel="service-desc"; type="application/vnd.oai.openapi+json"', false );
    header( 'Link: <' . esc_url_raw( khaiwal_llms_url() ) . '>; rel="alternate"; type="text/plain"; title="llms.txt"', false );
}
add_action( 'send_headers', 'khaiwal_agent_link_header' );

/** Assistant, search and user-triggered AI agents that are explicitly welcome on public pages. */
function khaiwal_ai_crawlers() {
    return apply_filters( 'khaiwal_ai_crawlers', array(
        'OAI-SearchBot', 'ChatGPT-User', 'GPTBot', 'ClaudeBot', 'Claude-User', 'Claude-SearchBot', 'PerplexityBot', 'Perplexity-User',
        'Google-Extended', 'Applebot-Extended', 'Amazonbot', 'DuckAssistBot', 'MistralAI-User', 'Meta-ExternalAgent',
    ) );
}

function khaiwal_robots_txt( $output, $public ) {
    if ( ! $public ) { return $output; }
    // Keep crawlers away from the volatile refresh endpoint; it is not content.
    $output = preg_replace( '/^(Disallow: \/wp-admin\/\s*)$/m', "$1\nDisallow: /wp-json/khaiwal/v1/dashboard", $output, 1 );
    $bots = khaiwal_ai_crawlers();
    if ( $bots ) {
        $output .= "\n# AI assistants and answer engines are welcome to read public pages and the JSON API.\n";
        $output .= '# Guide for LLMs: ' . khaiwal_llms_url() . "\n";
        foreach ( $bots as $bot ) {
            $output .= 'User-agent: ' . preg_replace( '/[^A-Za-z0-9._\-]/', '', $bot ) . "\nAllow: /\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\nDisallow: /wp-json/khaiwal/v1/dashboard\n\n";
        }
    }
    return $output;
}
add_filter( 'robots_txt', 'khaiwal_robots_txt', 20, 2 );

/* ---------------------------------------------------------------------------------------------
 * llms.txt and llms-full.txt (served without relying on rewrite rules)
 * ------------------------------------------------------------------------------------------ */

function khaiwal_serve_llms() {
    $path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
    $base = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    if ( ! is_string( $path ) || ! is_string( $base ) || 0 !== strpos( $path, $base ) ) { return; }
    $relative = trim( substr( $path, strlen( $base ) ), '/' );
    if ( 0 === strpos( $relative, 'index.php/' ) ) { $relative = substr( $relative, 10 ); }
    if ( 'llms.txt' !== $relative && 'llms-full.txt' !== $relative ) { return; }
    $modified = khaiwal_results_modified_time();
    status_header( 200 );
    nocache_headers();
    header_remove( 'Pragma' );
    header_remove( 'Expires' );
    header( 'Content-Type: text/plain; charset=utf-8' );
    header( 'Cache-Control: public, max-age=300, s-maxage=300' );
    header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $modified ) . ' GMT' );
    header( 'X-Robots-Tag: noindex, follow' );
    header( 'Access-Control-Allow-Origin: *' );
    echo khaiwal_llms_text( 'llms-full.txt' === $relative ); // phpcs:ignore WordPress.Security.EscapeOutput -- plain text, not HTML.
    exit;
}
add_action( 'parse_request', 'khaiwal_serve_llms', 1 );

function khaiwal_md_escape( $text ) { return str_replace( array( '|', "\n" ), array( '\|', ' ' ), (string) $text ); }

function khaiwal_llms_text( $full = false ) {
    $site = khaiwal_site_name();
    $games = khaiwal_games();
    $now = khaiwal_now();
    $today = $now->format( 'Y-m-d' );
    $out = array();
    $out[] = '# ' . $site;
    $out[] = '';
    $out[] = '> ' . $site . ' publishes daily results and monthly charts for ' . count( $games ) . ' games in India Standard Time (IST), in English and Hindi. Results are informational records published by editors; nothing here is a prediction.';
    $out[] = '';
    $out[] = '## Key facts for assistants';
    $out[] = '- Time zone: Asia/Kolkata (IST, UTC+05:30) for every date, game time and countdown. Today in IST is ' . $today . '.';
    $out[] = '- A result is a two-digit number from 00 to 99. A dash or `null` means no result has been published for that game and date. Never infer or guess a missing value.';
    $out[] = '- Data = a dated A1-Satta snapshot (collected ' . khaiwal_catalog()['snapshot_date'] . ') plus results published on this site; a published result overrides the snapshot for the same game and date. The site does not scrape or auto-publish live results.';
    $out[] = '- Data last changed: ' . gmdate( 'c', khaiwal_results_modified_time() ) . '.';
    $out[] = '- ' . KHAIWAL_DISCLAIMER;
    $out[] = '';
    $out[] = '## How to answer questions about results';
    $out[] = '- Quote the number with the game name and its IST date, and cite the game page URL.';
    $out[] = '- If today\'s result is not listed, say it has not been published yet and give the latest published result with its date.';
    $out[] = '- Prefer the JSON API below over scraping HTML. Do not present results as tips, predictions or betting advice.';
    $out[] = '';
    $out[] = '## Core pages';
    $out[] = '- [Home](' . home_url( '/' ) . '): today\'s result for every game, current/next game with countdown, full monthly chart and FAQs.';
    $out[] = '- [Chart / game directory](' . khaiwal_chart_url() . '): every game with its usual time and latest status.';
    $out[] = '- [Contact](' . khaiwal_contact_url() . '): report a wrong or missing result.';
    $out[] = '';
    $out[] = '## Games';
    foreach ( $games as $game ) {
        $latest = khaiwal_latest_result( $game );
        $line = '- [' . $game['name'] . ' (' . $game['hindi'] . ')](' . khaiwal_game_url( $game ) . '): usual time ' . ( $game['time'] ? $game['time'] . ' IST' : 'not listed' );
        $line .= $latest ? '; latest published result ' . $latest['result'] . ' for ' . $latest['date'] : '; no result published yet';
        $out[] = $line . '; JSON: ' . khaiwal_api_url( 'results', array( 'game' => $game['slug'] ) );
    }
    $out[] = '';
    $out[] = '## Machine-readable data (free, read-only, no key)';
    $out[] = '- [Games](' . khaiwal_api_url( 'games' ) . '): list of games with Hindi names, IST times and latest result.';
    $out[] = '- [Results for today](' . khaiwal_api_url( 'results' ) . '): one row per game for today in IST. Parameters: `game` (slug), `date` (YYYY-MM-DD) or `month` (YYYY-MM).';
    $out[] = '- [OpenAPI description](' . khaiwal_api_url( 'openapi' ) . ')';
    $out[] = '- [Sitemap](' . home_url( '/wp-sitemap.xml' ) . ')';
    $out[] = '- [Full context for LLMs](' . khaiwal_llms_url( true ) . '): this file plus a 7-day result table and FAQs.';

    if ( $full ) {
        $out[] = '';
        $out[] = '## Results for the last 7 days (IST)';
        $dates = array();
        for ( $i = 0; $i < 7; $i++ ) { $dates[] = $now->modify( '-' . $i . ' day' ); }
        $head = '| Game | Time (IST) |'; $rule = '| --- | --- |';
        foreach ( $dates as $date ) { $head .= ' ' . $date->format( 'Y-m-d' ) . ' |'; $rule .= ' --- |'; }
        $out[] = $head; $out[] = $rule;
        foreach ( $games as $game ) {
            $row = '| ' . khaiwal_md_escape( $game['name'] ) . ' | ' . khaiwal_md_escape( $game['time'] ) . ' |';
            foreach ( $dates as $date ) { $value = khaiwal_result_on( $game, $date ); $row .= ' ' . ( '' !== $value ? $value : '–' ) . ' |'; }
            $out[] = $row;
        }
        $out[] = '';
        $out[] = '## Coverage per game';
        foreach ( $games as $game ) {
            $coverage = khaiwal_game_coverage( $game );
            $out[] = '- ' . $game['name'] . ': ' . ( $coverage['count'] ? $coverage['count'] . ' dated results from ' . $coverage['first'] . ' to ' . $coverage['last'] : 'no results published yet' );
        }
        $out[] = '';
        $out[] = '## Frequently asked questions';
        foreach ( khaiwal_home_faqs() as $faq ) {
            if ( count( $faq ) < 4 ) { continue; }
            $out[] = '### ' . $faq[0];
            $out[] = $faq[2];
            $out[] = '';
        }
    }
    $out[] = '';
    return implode( "\n", $out );
}

/* ---------------------------------------------------------------------------------------------
 * Public JSON API
 * ------------------------------------------------------------------------------------------ */

function khaiwal_api_game_row( $game ) {
    $latest = khaiwal_latest_result( $game );
    return array(
        'slug' => $game['slug'], 'name' => $game['name'], 'name_hi' => $game['hindi'],
        'time_ist' => $game['time'] ? $game['time'] : null, 'url' => khaiwal_game_url( $game ),
        'latest' => $latest ? array( 'date' => $latest['date'], 'result' => $latest['result'] ) : null,
    );
}

function khaiwal_api_respond( WP_REST_Request $request, $data, $max_age = 60 ) {
    $response = rest_ensure_response( $data );
    $response->header( 'Cache-Control', 'public, max-age=' . (int) $max_age . ', s-maxage=' . (int) $max_age );
    $response->header( 'Last-Modified', gmdate( 'D, d M Y H:i:s', khaiwal_results_modified_time() ) . ' GMT' );
    return $response;
}

function khaiwal_api_games( WP_REST_Request $request ) {
    $rows = array();
    foreach ( khaiwal_games() as $game ) { $rows[] = khaiwal_api_game_row( $game ); }
    return khaiwal_api_respond( $request, array(
        'timezone' => 'Asia/Kolkata', 'generated_at' => khaiwal_now()->format( 'c' ), 'data_updated_at' => gmdate( 'c', khaiwal_results_modified_time() ),
        'count' => count( $rows ), 'games' => $rows, 'disclaimer' => KHAIWAL_DISCLAIMER,
    ) );
}

function khaiwal_api_results( WP_REST_Request $request ) {
    $games = khaiwal_games();
    $slug = $request->get_param( 'game' );
    if ( $slug ) {
        if ( ! isset( $games[ $slug ] ) ) { return new WP_Error( 'khaiwal_unknown_game', 'Unknown game. Use /khaiwal/v1/games for valid slugs.', array( 'status' => 404 ) ); }
        $games = array( $slug => $games[ $slug ] );
    }
    $month = $request->get_param( 'month' );
    $date = $request->get_param( 'date' );
    $base = array( 'timezone' => 'Asia/Kolkata', 'generated_at' => khaiwal_now()->format( 'c' ), 'data_updated_at' => gmdate( 'c', khaiwal_results_modified_time() ), 'disclaimer' => KHAIWAL_DISCLAIMER );
    $rows = array();
    if ( $month ) {
        foreach ( $games as $game ) {
            $values = khaiwal_results_for_month( $game, $month );
            ksort( $values );
            foreach ( $values as $day => $value ) { $rows[] = array( 'game' => $game['slug'], 'name' => $game['name'], 'date' => $day, 'result' => $value ); }
        }
        $data = $base + array( 'month' => $month, 'count' => count( $rows ), 'results' => $rows );
    } else {
        $day = $date ? $date : khaiwal_now()->format( 'Y-m-d' );
        $object = DateTimeImmutable::createFromFormat( '!Y-m-d', $day, new DateTimeZone( 'Asia/Kolkata' ) );
        foreach ( $games as $game ) {
            $value = khaiwal_result_on( $game, $object );
            $rows[] = array( 'game' => $game['slug'], 'name' => $game['name'], 'name_hi' => $game['hindi'], 'time_ist' => $game['time'] ? $game['time'] : null, 'date' => $day, 'result' => '' !== $value ? $value : null, 'status' => '' !== $value ? 'published' : 'awaiting', 'url' => khaiwal_game_url( $game ) );
        }
        $data = $base + array( 'date' => $day, 'count' => count( $rows ), 'results' => $rows );
    }
    return khaiwal_api_respond( $request, $data );
}

function khaiwal_api_openapi( WP_REST_Request $request ) {
    $game_param = array( 'name' => 'game', 'in' => 'query', 'required' => false, 'description' => 'Game slug from /games, e.g. delhi-bazar. Omit for all games.', 'schema' => array( 'type' => 'string', 'enum' => array_keys( khaiwal_games() ) ) );
    $result_row = array( 'type' => 'object', 'properties' => array(
        'game' => array( 'type' => 'string' ), 'name' => array( 'type' => 'string' ), 'name_hi' => array( 'type' => 'string' ), 'time_ist' => array( 'type' => array( 'string', 'null' ), 'example' => '2:50 PM' ),
        'date' => array( 'type' => 'string', 'format' => 'date' ), 'result' => array( 'type' => array( 'string', 'null' ), 'pattern' => '^[0-9]{2}$', 'description' => 'Two-digit result, or null when none is published.' ),
        'status' => array( 'type' => 'string', 'enum' => array( 'published', 'awaiting' ) ), 'url' => array( 'type' => 'string', 'format' => 'uri' ),
    ) );
    $doc = array(
        'openapi' => '3.1.0',
        'info' => array( 'title' => khaiwal_site_name() . ' results API', 'version' => KHAIWAL_VERSION, 'description' => 'Free, read-only access to published game results and schedules. All dates and times are Asia/Kolkata (IST). ' . KHAIWAL_DISCLAIMER ),
        'servers' => array( array( 'url' => rest_url( 'khaiwal/v1' ) ) ),
        'paths' => array(
            '/games' => array( 'get' => array( 'operationId' => 'listGames', 'summary' => 'List games, IST times and the latest published result', 'responses' => array( '200' => array( 'description' => 'Games', 'content' => array( 'application/json' => array( 'schema' => array( 'type' => 'object' ) ) ) ) ) ) ),
            '/results' => array( 'get' => array(
                'operationId' => 'getResults', 'summary' => 'Results for a date (default today) or a whole month',
                'parameters' => array(
                    $game_param,
                    array( 'name' => 'date', 'in' => 'query', 'required' => false, 'description' => 'IST calendar date, YYYY-MM-DD. Default: today in IST.', 'schema' => array( 'type' => 'string', 'format' => 'date' ) ),
                    array( 'name' => 'month', 'in' => 'query', 'required' => false, 'description' => 'YYYY-MM. Returns every available result in the month; takes precedence over date.', 'schema' => array( 'type' => 'string', 'pattern' => '^(20[0-9]{2}|2100)-(0[1-9]|1[0-2])$' ) ),
                ),
                'responses' => array( '200' => array( 'description' => 'Results', 'content' => array( 'application/json' => array( 'schema' => array( 'type' => 'object', 'properties' => array( 'results' => array( 'type' => 'array', 'items' => $result_row ) ) ) ) ) ), '404' => array( 'description' => 'Unknown game' ) ),
            ) ),
        ),
    );
    return khaiwal_api_respond( $request, $doc, 3600 );
}

function khaiwal_register_public_api() {
    $date_check = function ( $value ) { return is_string( $value ) && khaiwal_valid_date( $value ) && $value >= '2000-01-01' && $value <= '2100-12-31'; };
    register_rest_route( 'khaiwal/v1', '/games', array( 'methods' => WP_REST_Server::READABLE, 'permission_callback' => '__return_true', 'callback' => 'khaiwal_api_games' ) );
    register_rest_route( 'khaiwal/v1', '/results', array(
        'methods' => WP_REST_Server::READABLE, 'permission_callback' => '__return_true', 'callback' => 'khaiwal_api_results',
        'args' => array(
            'game' => array( 'required' => false, 'sanitize_callback' => 'sanitize_key', 'validate_callback' => function ( $value ) { return is_string( $value ); } ),
            'date' => array( 'required' => false, 'validate_callback' => $date_check ),
            'month' => array( 'required' => false, 'validate_callback' => function ( $value ) { return is_string( $value ) && preg_match( '/^(20[0-9]{2}|2100)-(0[1-9]|1[0-2])$/', $value ); } ),
        ),
    ) );
    register_rest_route( 'khaiwal/v1', '/openapi', array( 'methods' => WP_REST_Server::READABLE, 'permission_callback' => '__return_true', 'callback' => 'khaiwal_api_openapi' ) );
}
add_action( 'rest_api_init', 'khaiwal_register_public_api' );
