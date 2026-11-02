<?php
/**
 * SEO, answer-engine and structured-data layer.
 *
 * - Titles, descriptions, canonical, robots, Open Graph and Twitter cards for every page type.
 * - One JSON-LD graph per page (Organization, WebSite, WebPage, BreadcrumbList, FAQPage, Dataset, ItemList, BlogPosting).
 * - Answer-first content helpers used by the templates (quick answers and per-game FAQs).
 * - Sitemap freshness (lastmod follows published results).
 *
 * When a dedicated SEO plugin is active (Yoast, Rank Math, AIOSEO, SEOPress, SEO Framework) the theme
 * stops printing titles, meta tags and the generic graph, and only adds what plugins do not:
 * the per-game Dataset and the machine-readable discovery links.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---------------------------------------------------------------------------------------------
 * Environment
 * ------------------------------------------------------------------------------------------ */

function khaiwal_seo_plugin_active() {
    return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' ) || defined( 'SLIM_SEO_VERSION' );
}

/** Whether the theme prints titles, meta tags and the generic JSON-LD graph. */
function khaiwal_seo_enabled() {
    return (bool) apply_filters( 'khaiwal_seo_enabled', ! khaiwal_seo_plugin_active() );
}

function khaiwal_site_name() { return wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ); }

/** Trim to a snippet-friendly length without cutting a word. */
function khaiwal_trim( $text, $length = 158 ) {
    $text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $text ) ) );
    if ( mb_strlen( $text ) <= $length ) { return $text; }
    $cut = mb_substr( $text, 0, $length - 1 );
    $space = mb_strrpos( $cut, ' ' );
    if ( $space && $space > $length * 0.6 ) { $cut = mb_substr( $cut, 0, $space ); }
    return rtrim( $cut, " ,;:-–—" ) . '…';
}

function khaiwal_date_label( $date ) {
    $object = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, new DateTimeZone( 'Asia/Kolkata' ) );
    return $object ? $object->format( 'j M Y' ) : $date;
}

/* ---------------------------------------------------------------------------------------------
 * Game answer helpers (shared by templates, schema, llms.txt and the API)
 * ------------------------------------------------------------------------------------------ */

/** Most recent published result on or before today (IST): array( date, result ) or null. */
function khaiwal_latest_result( $game ) {
    static $cache = array();
    $slug = $game['slug'];
    if ( array_key_exists( $slug, $cache ) ) { return $cache[ $slug ]; }
    $cache[ $slug ] = null;
    $today = khaiwal_now()->format( 'Y-m-d' );
    $months = array_merge( isset( $game['results'] ) ? array_keys( $game['results'] ) : array(), khaiwal_published_months( $slug ) );
    $months = array_unique( array_filter( $months, function ( $month ) { return (bool) preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $month ); } ) );
    rsort( $months );
    foreach ( $months as $month ) {
        if ( $month > substr( $today, 0, 7 ) ) { continue; }
        $results = array_filter( khaiwal_results_for_month( $game, $month ), function ( $date ) use ( $today ) { return $date <= $today; }, ARRAY_FILTER_USE_KEY );
        if ( $results ) {
            krsort( $results );
            $date = (string) key( $results );
            $cache[ $slug ] = array( 'date' => $date, 'result' => (string) $results[ $date ] );
            break;
        }
    }
    return $cache[ $slug ];
}

/** Coverage of all known results for a game: first/last date and number of dated results. */
function khaiwal_game_coverage( $game ) {
    static $cache = array();
    $slug = $game['slug'];
    if ( isset( $cache[ $slug ] ) ) { return $cache[ $slug ]; }
    $months = array_merge( isset( $game['results'] ) ? array_keys( $game['results'] ) : array(), khaiwal_published_months( $slug ) );
    $months = array_unique( array_filter( $months, function ( $month ) { return (bool) preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $month ); } ) );
    sort( $months );
    $first = ''; $last = ''; $count = 0;
    foreach ( $months as $month ) {
        $results = khaiwal_results_for_month( $game, $month );
        if ( ! $results ) { continue; }
        ksort( $results );
        $count += count( $results );
        $keys = array_keys( $results );
        if ( '' === $first ) { $first = $keys[0]; }
        $last = end( $keys );
    }
    $cache[ $slug ] = array( 'first' => $first, 'last' => $last, 'count' => $count );
    return $cache[ $slug ];
}

/** Plain-language answer data for "what is the <game> result today?". */
function khaiwal_game_answer( $game ) {
    $now = khaiwal_now();
    $today = $now->format( 'Y-m-d' );
    $latest = khaiwal_latest_result( $game );
    return array(
        'today_date' => $today,
        'today' => khaiwal_result_on( $game, $now ),
        'previous' => khaiwal_result_on( $game, $now->modify( '-1 day' ) ),
        'latest' => $latest,
    );
}

/** Bilingual sentence array( en, hi ) answering the result question for a game. */
function khaiwal_game_answer_sentence( $game ) {
    $a = khaiwal_game_answer( $game );
    $name = $game['name']; $hindi = $game['hindi'];
    $today = khaiwal_date_label( $a['today_date'] );
    if ( '' !== $a['today'] ) {
        return array(
            sprintf( 'The %1$s result for %2$s (IST) is %3$s.', $name, $today, $a['today'] ),
            sprintf( '%1$s का %2$s (IST) का रिज़ल्ट %3$s है।', $hindi, $today, $a['today'] )
        );
    }
    if ( $a['latest'] ) {
        $when = khaiwal_date_label( $a['latest']['date'] );
        return array(
            sprintf( 'The %1$s result for %2$s has not been published yet. The latest published result is %3$s, for %4$s.', $name, $today, $a['latest']['result'], $when ),
            sprintf( '%1$s का %2$s का रिज़ल्ट अभी प्रकाशित नहीं हुआ है। नवीनतम प्रकाशित रिज़ल्ट %4$s का %3$s है।', $hindi, $today, $a['latest']['result'], $when )
        );
    }
    return array(
        sprintf( 'No %1$s result has been published yet.', $name ),
        sprintf( '%1$s का कोई रिज़ल्ट अभी प्रकाशित नहीं हुआ है।', $hindi )
    );
}

/** FAQ items for a game page: array of array( q_en, q_hi, a_en, a_hi ). Visible on the page and in FAQPage JSON-LD. */
function khaiwal_game_faqs( $game ) {
    $name = $game['name']; $hindi = $game['hindi'];
    $sentence = khaiwal_game_answer_sentence( $game );
    $time_en = $game['time'] ? sprintf( '%1$s is scheduled for %2$s India Standard Time (IST). Scheduled times are informational and do not guarantee when a result is published.', $name, $game['time'] ) : sprintf( 'A usual time for %s is not listed. Results appear once they are published.', $name );
    $time_hi = $game['time'] ? sprintf( '%1$s का निर्धारित समय भारतीय मानक समय (IST) में %2$s है। समय केवल जानकारी के लिए है और रिज़ल्ट प्रकाशित होने की गारंटी नहीं है।', $hindi, $game['time'] ) : sprintf( '%s का सामान्य समय सूचीबद्ध नहीं है। रिज़ल्ट प्रकाशित होने पर दिखेगा।', $hindi );
    $api = rest_url( 'khaiwal/v1/results' );
    return apply_filters( 'khaiwal_game_faqs', array(
        array( sprintf( 'What is the %s result today?', $name ), sprintf( '%s का आज का रिज़ल्ट क्या है?', $hindi ), $sentence[0], $sentence[1] ),
        array( sprintf( 'What time is the %s result?', $name ), sprintf( '%s का रिज़ल्ट कितने बजे आता है?', $hindi ), $time_en, $time_hi ),
        array( sprintf( 'Where can I see the %s monthly chart?', $name ), sprintf( '%s का मासिक चार्ट कहाँ देखें?', $hindi ), sprintf( 'This page lists %s results date by date for the selected month and the month before it. Use the month selector for earlier months. A dash means no result is available for that date.', $name ), sprintf( 'इस पेज पर %s के रिज़ल्ट चुने हुए महीने और उससे पिछले महीने की तारीख के अनुसार दिए गए हैं। पुराने महीनों के लिए महीना चुनें। डैश का मतलब उस तारीख का रिज़ल्ट उपलब्ध नहीं है।', $hindi ) ),
        array( sprintf( 'Can past %s results predict the next result?', $name ), sprintf( 'क्या %s के पुराने रिज़ल्ट से अगला रिज़ल्ट पता चल सकता है?', $hindi ), 'No. Past results are historical records only. They cannot reliably predict or guarantee any future result, and this site does not offer predictions.', 'नहीं। पुराने रिज़ल्ट केवल ऐतिहासिक रिकॉर्ड हैं। वे भविष्य के किसी रिज़ल्ट की विश्वसनीय भविष्यवाणी या गारंटी नहीं देते, और यह साइट कोई भविष्यवाणी नहीं देती।' ),
        array( sprintf( 'Is there a machine-readable %s result feed?', $name ), sprintf( 'क्या %s के रिज़ल्ट मशीन-पठनीय रूप में मिलते हैं?', $hindi ), sprintf( 'Yes. A free, read-only JSON endpoint returns published results: %s?game=%s. It is documented in the OpenAPI description linked from llms.txt.', $api, $game['slug'] ), sprintf( 'हाँ। प्रकाशित रिज़ल्ट के लिए मुफ़्त, केवल-पढ़ने वाला JSON एंडपॉइंट उपलब्ध है: %s?game=%s।', $api, $game['slug'] ) ),
    ), $game );
}

/** Homepage FAQs (visible in the FAQ section and exported as FAQPage JSON-LD). */
function khaiwal_home_faqs() {
    $games = khaiwal_games();
    $names = array(); $hindi = array();
    foreach ( $games as $game ) { $names[] = $game['name'] . ( $game['time'] ? ' (' . $game['time'] . ')' : '' ); $hindi[] = $game['hindi']; }
    $list_en = $names ? implode( ', ', $names ) : 'No games are listed yet';
    $list_hi = $hindi ? implode( ', ', $hindi ) : 'अभी कोई गेम सूचीबद्ध नहीं है';
    return apply_filters( 'khaiwal_home_faqs', array(
        array( 'How are results updated?', 'रिज़ल्ट कैसे अपडेट होते हैं?', 'Results appear when an editor publishes them from the Publish results page in WordPress or imports a CSV. The bundled A1-Satta history is a dated snapshot, not an automatic live feed.', 'संपादक द्वारा वर्डप्रेस के रिज़ल्ट प्रकाशित करें पेज से प्रकाशित करने या CSV आयात करने पर रिज़ल्ट दिखते हैं। A1-Satta का पुराना डेटा एक दिनांकित संग्रह है, लाइव फ़ीड नहीं।' ),
        array( 'How do I use the result chart?', 'रिज़ल्ट चार्ट कैसे देखें?', 'Choose a game, month and year in the full chart, then select View results. You can also choose All games to compare them by date.', 'पूरे चार्ट में गेम, महीना और साल चुनें, फिर रिज़ल्ट देखें पर क्लिक करें। तारीख के अनुसार तुलना के लिए सभी गेम भी चुन सकते हैं।' ),
        array( 'What does a dash mean?', 'डैश का क्या मतलब है?', 'A dash means a verified result is not available for that game and date. It is not a predicted number.', 'डैश का मतलब है कि उस गेम और तारीख का सत्यापित रिज़ल्ट उपलब्ध नहीं है। यह कोई अनुमानित नंबर नहीं है।' ),
        array( 'How are the current and next games chosen?', 'मौजूदा और अगला गेम कैसे चुना जाता है?', 'They follow the game times listed in India Standard Time. Scheduled times are informational and do not guarantee when a result will be published.', 'ये भारत के मानक समय के अनुसार सूचीबद्ध गेम समय पर आधारित हैं। समय केवल जानकारी के लिए है और रिज़ल्ट प्रकाशित होने की गारंटी नहीं है।' ),
        array( 'Can I view previous months?', 'क्या मैं पिछले महीनों के रिज़ल्ट देख सकता हूँ?', 'Yes. Select a month and year in the chart, or open a game page. Only available or editor-published records are shown.', 'हाँ। चार्ट में महीना और साल चुनें या किसी गेम का पेज खोलें। केवल उपलब्ध या संपादक द्वारा प्रकाशित रिकॉर्ड दिखते हैं।' ),
        array( 'Can past results predict future results?', 'क्या पुराने रिज़ल्ट से भविष्य का पता चल सकता है?', 'No. Past results are historical records and cannot reliably predict or guarantee future outcomes.', 'नहीं। पुराने रिज़ल्ट केवल ऐतिहासिक रिकॉर्ड हैं और वे भविष्य के परिणाम की विश्वसनीय भविष्यवाणी या गारंटी नहीं देते।' ),
        array( 'Which games are listed and at what time?', 'कौन-से गेम सूचीबद्ध हैं और उनका समय क्या है?', 'The lineup is: ' . $list_en . '. All times are India Standard Time (IST) and are informational.', 'सूची: ' . $list_hi . '। सभी समय भारतीय मानक समय (IST) में हैं और केवल जानकारी के लिए हैं।' ),
        array( 'Which time zone does the site use?', 'साइट कौन-सा समय क्षेत्र इस्तेमाल करती है?', 'Every date, game time and countdown uses Asia/Kolkata (IST, UTC+05:30), regardless of where you are. The 1:00 AM Disawar game uses its actual IST calendar date.', 'हर तारीख, गेम समय और काउंटडाउन Asia/Kolkata (IST, UTC+05:30) में है, चाहे आप कहीं भी हों। रात 1:00 बजे का दिसावर गेम अपनी असली IST तारीख इस्तेमाल करता है।' ),
        array( 'Is there an API or machine-readable data?', 'क्या API या मशीन-पठनीय डेटा उपलब्ध है?', 'Yes. A free read-only JSON API returns games and published results (/wp-json/khaiwal/v1/games and /wp-json/khaiwal/v1/results), described by an OpenAPI document. A plain-text summary for AI assistants is available at /llms.txt.', 'हाँ। मुफ़्त, केवल-पढ़ने वाला JSON API गेम और प्रकाशित रिज़ल्ट देता है (/wp-json/khaiwal/v1/games और /wp-json/khaiwal/v1/results), जिसका OpenAPI विवरण उपलब्ध है। AI सहायकों के लिए सादा-टेक्स्ट सार /llms.txt पर है।' ),
    ) );
}

/* ---------------------------------------------------------------------------------------------
 * Page context
 * ------------------------------------------------------------------------------------------ */

/** home | chart | game | contact | post | page | search | 404 | archive | other */
function khaiwal_page_kind() {
    if ( is_404() ) { return '404'; }
    if ( is_search() ) { return 'search'; }
    if ( is_front_page() || ( is_home() && ! is_paged() ) ) { return 'home'; }
    if ( is_singular( 'khaiwal_game' ) ) { return 'game'; }
    if ( is_page_template( 'page-chart.php' ) || ( is_page() && is_page( 'chart' ) ) ) { return 'chart'; }
    if ( is_page_template( 'page-contact.php' ) || ( is_page() && is_page( 'contact' ) ) ) { return 'contact'; }
    if ( is_singular( 'post' ) ) { return 'post'; }
    if ( is_page() ) { return 'page'; }
    if ( is_archive() || is_home() ) { return 'archive'; }
    return 'other';
}

/** The game array for the current game page (mirrors single-khaiwal_game.php). */
function khaiwal_current_game() {
    static $game = false;
    if ( false !== $game ) { return $game; }
    $game = null;
    if ( ! is_singular( 'khaiwal_game' ) ) { return $game; }
    $id = get_queried_object_id();
    $slug = get_post_field( 'post_name', $id );
    $games = khaiwal_games();
    $game = isset( $games[ $slug ] ) ? $games[ $slug ] : khaiwal_catalog_game( $slug );
    if ( ! $game ) { $game = array( 'slug' => $slug, 'name' => get_the_title( $id ), 'hindi' => get_the_title( $id ), 'time' => '', 'results' => array() ); }
    if ( ! isset( $games[ $slug ] ) ) {
        $game['name'] = get_the_title( $id );
        $hi = get_post_meta( $id, '_khaiwal_hindi', true ); $time = get_post_meta( $id, '_khaiwal_time', true );
        if ( $hi ) { $game['hindi'] = $hi; }
        if ( $time ) { $game['time'] = $time; }
        $game['post_id'] = $id;
    }
    return $game;
}

function khaiwal_default_og_image() {
    return array( 'url' => get_template_directory_uri() . '/assets/images/og-default.jpg', 'width' => 1200, 'height' => 630, 'alt' => khaiwal_site_name() . ' – game results and charts' );
}

function khaiwal_page_image( $kind ) {
    if ( is_singular() && has_post_thumbnail() ) {
        $id = get_post_thumbnail_id();
        $src = wp_get_attachment_image_src( $id, 'large' );
        if ( $src ) { return array( 'url' => $src[0], 'width' => (int) $src[1], 'height' => (int) $src[2], 'alt' => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ); }
    }
    return khaiwal_default_og_image();
}

/** Everything the head and JSON-LD need, computed once per request. */
function khaiwal_seo_context() {
    static $context = null;
    if ( null !== $context ) { return $context; }
    $kind = khaiwal_page_kind();
    $site = khaiwal_site_name();
    $games = khaiwal_games();
    $count = count( $games );
    $c = array( 'kind' => $kind, 'title' => '', 'description' => '', 'url' => '', 'type' => 'website', 'noindex' => false, 'game' => null, 'modified' => khaiwal_results_modified_time() );

    switch ( $kind ) {
        case 'home':
            $first = array_slice( array_column( $games, 'name' ), 0, 3 );
            $c['title'] = sprintf( '%s: Today\'s Game Results, Charts & Timings (IST)', $site );
            $c['description'] = $count ? khaiwal_trim( sprintf( 'Today\'s results for %1$d games in IST — %2$s and more — with game times, monthly charts and a free results API.', $count, implode( ', ', $first ) ) ) : khaiwal_trim( get_bloginfo( 'description' ) );
            $c['url'] = home_url( '/' );
            break;
        case 'chart':
            $c['title'] = sprintf( 'Game Results Chart: All %1$d Games by Date | %2$s', $count, $site );
            $c['description'] = khaiwal_trim( sprintf( 'Browse results and monthly charts for all %1$d games, with each game\'s usual time in IST. Open a game for its date-by-date record.', $count ) );
            $c['url'] = get_permalink( get_queried_object_id() );
            break;
        case 'game':
            $game = khaiwal_current_game();
            $c['game'] = $game;
            $latest = khaiwal_latest_result( $game );
            $c['title'] = sprintf( '%1$s Result Today, Chart & Time (%2$s) | %3$s', $game['name'], $game['hindi'], $site );
            $excerpt = has_excerpt( get_queried_object_id() ) ? get_the_excerpt( get_queried_object_id() ) : '';
            $c['description'] = $excerpt ? khaiwal_trim( $excerpt ) : khaiwal_trim( sprintf(
                '%1$s (%2$s) result today%3$s and full monthly chart.%4$s',
                $game['name'], $game['hindi'], $game['time'] ? ', game time ' . $game['time'] . ' IST' : '',
                $latest ? sprintf( ' Latest published result: %1$s for %2$s.', $latest['result'], khaiwal_date_label( $latest['date'] ) ) : ''
            ) );
            $c['url'] = get_permalink( get_queried_object_id() );
            $c['modified'] = max( $c['modified'], (int) get_post_modified_time( 'U', true, get_queried_object_id() ) );
            break;
        case 'contact':
            $c['title'] = sprintf( 'Contact %s: Questions, Corrections & Feedback', $site );
            $c['description'] = khaiwal_trim( sprintf( 'Contact the %s team about a result, a chart or the website. Send a message and we will reply by email.', $site ) );
            $c['url'] = get_permalink( get_queried_object_id() );
            break;
        case 'post':
        case 'page':
            $id = get_queried_object_id();
            $c['title'] = get_the_title( $id ) . ' | ' . $site;
            $excerpt = has_excerpt( $id ) ? get_the_excerpt( $id ) : wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $id ) ) );
            $c['description'] = khaiwal_trim( $excerpt ? $excerpt : get_bloginfo( 'description' ) );
            $c['url'] = get_permalink( $id );
            $c['type'] = 'post' === $kind ? 'article' : 'website';
            $c['modified'] = (int) get_post_modified_time( 'U', true, $id );
            break;
        case 'search':
            $c['noindex'] = true;
            $c['title'] = sprintf( 'Search: %s | %s', get_search_query(), $site );
            $c['description'] = khaiwal_trim( get_bloginfo( 'description' ) );
            $c['url'] = get_search_link();
            break;
        case '404':
            $c['noindex'] = true;
            $c['title'] = sprintf( 'Page not found | %s', $site );
            $c['description'] = khaiwal_trim( get_bloginfo( 'description' ) );
            break;
        default:
            $c['description'] = khaiwal_trim( get_bloginfo( 'description' ) );
            $c['url'] = '';
    }
    $c['image'] = khaiwal_page_image( $kind );
    $context = apply_filters( 'khaiwal_seo_context', $c );
    return $context;
}

/* ---------------------------------------------------------------------------------------------
 * Head output
 * ------------------------------------------------------------------------------------------ */

function khaiwal_seo_document_title( $title ) {
    if ( ! khaiwal_seo_enabled() ) { return $title; }
    $c = khaiwal_seo_context();
    return in_array( $c['kind'], array( 'home', 'chart', 'game', 'contact' ), true ) && $c['title'] ? esc_html( $c['title'] ) : $title;
}
add_filter( 'pre_get_document_title', 'khaiwal_seo_document_title', 20 );

/** Rich-snippet friendly robots directives; search and 404 pages stay out of the index. */
function khaiwal_seo_robots( $robots ) {
    if ( ! khaiwal_seo_enabled() ) { return $robots; }
    $c = khaiwal_seo_context();
    $robots['max-snippet'] = '-1';
    $robots['max-video-preview'] = '-1';
    $robots['max-image-preview'] = 'large';
    if ( $c['noindex'] ) { $robots['noindex'] = true; $robots['follow'] = true; }
    return $robots;
}
add_filter( 'wp_robots', 'khaiwal_seo_robots', 20 );

function khaiwal_seo_head() {
    if ( ! khaiwal_seo_enabled() ) { return; }
    $c = khaiwal_seo_context();
    remove_action( 'wp_head', 'rel_canonical' ); // The theme prints one canonical for every page type below.
    $tags = array();
    if ( $c['description'] ) { $tags[] = '<meta name="description" content="' . esc_attr( $c['description'] ) . '">'; }
    if ( $c['url'] && ! $c['noindex'] ) { $tags[] = '<link rel="canonical" href="' . esc_url( $c['url'] ) . '">'; }
    $tags[] = '<meta name="theme-color" content="#ffd800">';
    if ( ! $c['noindex'] && 'other' !== $c['kind'] ) {
        $og_title = in_array( $c['kind'], array( 'home', 'chart', 'game', 'contact' ), true ) ? $c['title'] : wp_get_document_title();
        $tags[] = '<meta property="og:locale" content="en_IN">';
        $tags[] = '<meta property="og:locale:alternate" content="hi_IN">';
        $tags[] = '<meta property="og:site_name" content="' . esc_attr( khaiwal_site_name() ) . '">';
        $tags[] = '<meta property="og:type" content="' . esc_attr( $c['type'] ) . '">';
        $tags[] = '<meta property="og:title" content="' . esc_attr( $og_title ) . '">';
        if ( $c['description'] ) { $tags[] = '<meta property="og:description" content="' . esc_attr( $c['description'] ) . '">'; }
        if ( $c['url'] ) { $tags[] = '<meta property="og:url" content="' . esc_url( $c['url'] ) . '">'; }
        $image = $c['image'];
        $tags[] = '<meta property="og:image" content="' . esc_url( $image['url'] ) . '">';
        if ( ! empty( $image['width'] ) ) { $tags[] = '<meta property="og:image:width" content="' . (int) $image['width'] . '"><meta property="og:image:height" content="' . (int) $image['height'] . '">'; }
        if ( ! empty( $image['alt'] ) ) { $tags[] = '<meta property="og:image:alt" content="' . esc_attr( $image['alt'] ) . '">'; }
        if ( 'article' === $c['type'] ) {
            $tags[] = '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c', get_queried_object_id() ) ) . '">';
            $tags[] = '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c', get_queried_object_id() ) ) . '">';
        }
        if ( in_array( $c['kind'], array( 'home', 'chart', 'game' ), true ) ) { $tags[] = '<meta property="og:updated_time" content="' . esc_attr( gmdate( 'c', $c['modified'] ) ) . '">'; }
        $tags[] = '<meta name="twitter:card" content="summary_large_image">';
        $tags[] = '<meta name="twitter:title" content="' . esc_attr( $og_title ) . '">';
        if ( $c['description'] ) { $tags[] = '<meta name="twitter:description" content="' . esc_attr( $c['description'] ) . '">'; }
        $tags[] = '<meta name="twitter:image" content="' . esc_url( $image['url'] ) . '">';
        if ( ! empty( $image['alt'] ) ) { $tags[] = '<meta name="twitter:image:alt" content="' . esc_attr( $image['alt'] ) . '">'; }
    }
    echo "\n<!-- Khaiwal SEO -->\n" . implode( "\n", $tags ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- every tag is escaped above.
}
add_action( 'wp_head', 'khaiwal_seo_head', 1 );

/* ---------------------------------------------------------------------------------------------
 * JSON-LD
 * ------------------------------------------------------------------------------------------ */

function khaiwal_schema_json( $data ) {
    return wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP );
}

function khaiwal_schema_faq( $faqs, $url ) {
    $items = array();
    foreach ( $faqs as $faq ) {
        if ( count( $faq ) < 4 ) { continue; }
        $items[] = array( '@type' => 'Question', 'name' => $faq[0], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $faq[2] ) );
    }
    return $items ? array( '@type' => 'FAQPage', '@id' => $url . '#faq', 'url' => $url, 'inLanguage' => 'en-IN', 'mainEntity' => $items ) : null;
}

function khaiwal_schema_graph() {
    $c = khaiwal_seo_context();
    $kind = $c['kind'];
    if ( $c['noindex'] || 'other' === $kind || 'archive' === $kind ) { return array(); }
    $home = home_url( '/' );
    $org_id = $home . '#organization'; $site_id = $home . '#website';
    $url = $c['url'] ? $c['url'] : $home;
    $page_id = $url . '#webpage';
    $site = khaiwal_site_name();
    $games = khaiwal_games();
    $graph = array();

    $org = array( '@type' => 'Organization', '@id' => $org_id, 'name' => $site, 'url' => $home, 'areaServed' => array( '@type' => 'Country', 'name' => 'India' ) );
    $logo = has_custom_logo() ? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' ) : get_site_icon_url( 512 );
    if ( $logo ) { $org['logo'] = array( '@type' => 'ImageObject', 'url' => $logo ); }
    $graph[] = $org;
    $graph[] = array( '@type' => 'WebSite', '@id' => $site_id, 'url' => $home, 'name' => $site, 'description' => khaiwal_trim( get_bloginfo( 'description' ) ), 'inLanguage' => array( 'en-IN', 'hi-IN' ), 'publisher' => array( '@id' => $org_id ) );

    $types = array( 'home' => 'WebPage', 'chart' => 'CollectionPage', 'game' => 'WebPage', 'contact' => 'ContactPage', 'post' => 'WebPage', 'page' => 'WebPage' );
    $page = array( '@type' => $types[ $kind ], '@id' => $page_id, 'url' => $url, 'name' => in_array( $kind, array( 'post', 'page' ), true ) ? get_the_title( get_queried_object_id() ) : $c['title'], 'description' => $c['description'], 'isPartOf' => array( '@id' => $site_id ), 'inLanguage' => 'en-IN', 'primaryImageOfPage' => array( '@type' => 'ImageObject', 'url' => $c['image']['url'], 'width' => $c['image']['width'], 'height' => $c['image']['height'] ) );
    if ( in_array( $kind, array( 'home', 'chart', 'game' ), true ) ) {
        $page['dateModified'] = gmdate( 'c', $c['modified'] );
        $page['speakable'] = array( '@type' => 'SpeakableSpecification', 'cssSelector' => array( '.answer-block', 'h1' ) );
    }

    // Breadcrumbs.
    $crumbs = array();
    if ( 'home' !== $kind ) {
        $crumbs[] = array( $site, $home );
        if ( 'game' === $kind ) { $crumbs[] = array( 'Charts', khaiwal_chart_url() ); $crumbs[] = array( $c['game']['name'], $url ); }
        elseif ( 'post' === $kind ) { $crumbs[] = array( 'Journal', home_url( '/#journal' ) ); $crumbs[] = array( get_the_title( get_queried_object_id() ), $url ); }
        else { $crumbs[] = array( 'chart' === $kind ? 'Charts' : get_the_title( get_queried_object_id() ), $url ); }
        $list = array();
        foreach ( $crumbs as $i => $crumb ) { $list[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb[0], 'item' => $crumb[1] ); }
        $page['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
    }

    if ( 'home' === $kind || 'chart' === $kind ) {
        $items = array(); $position = 1;
        foreach ( $games as $game ) {
            $items[] = array( '@type' => 'ListItem', 'position' => $position++, 'name' => $game['name'] . ' result (' . $game['hindi'] . ')', 'url' => khaiwal_game_url( $game ) );
        }
        $page['mainEntity'] = array( '@type' => 'ItemList', '@id' => $url . '#games', 'name' => 'Games with daily results and monthly charts', 'numberOfItems' => count( $items ), 'itemListOrder' => 'https://schema.org/ItemListUnordered', 'itemListElement' => $items );
    }

    if ( 'post' === $kind ) {
        $id = get_queried_object_id();
        $page = array_merge( $page, array( '@type' => 'WebPage' ) );
        $article = array( '@type' => 'BlogPosting', '@id' => $url . '#article', 'mainEntityOfPage' => array( '@id' => $page_id ), 'headline' => khaiwal_trim( get_the_title( $id ), 110 ), 'description' => $c['description'], 'datePublished' => get_the_date( 'c', $id ), 'dateModified' => get_the_modified_date( 'c', $id ), 'inLanguage' => 'en-IN', 'image' => array( $c['image']['url'] ), 'author' => array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $id ) ) ), 'publisher' => array( '@id' => $org_id ) );
        $graph[] = $article;
    }

    $graph[] = $page;
    if ( isset( $list ) ) { $graph[] = array( '@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $list ); }

    if ( 'home' === $kind ) {
        $faq = khaiwal_schema_faq( khaiwal_home_faqs(), $url );
        if ( $faq ) { $graph[] = $faq; }
    }
    return apply_filters( 'khaiwal_schema_graph', $graph, $c );
}

/** Dataset for a game's published results (kept even when an SEO plugin owns the generic graph). */
function khaiwal_schema_game_dataset( $game, $url ) {
    $coverage = khaiwal_game_coverage( $game );
    $latest = khaiwal_latest_result( $game );
    $site = khaiwal_site_name();
    $dataset = array(
        '@type' => 'Dataset', '@id' => $url . '#dataset', 'url' => $url,
        'name' => $game['name'] . ' daily results (IST)', 'alternateName' => $game['hindi'],
        'description' => sprintf( 'Date-by-date %1$s (%2$s) results published by %3$s. Each record is a two-digit number (00–99) for a calendar date in India Standard Time. A missing date means no result is available; no values are predicted.', $game['name'], $game['hindi'], $site ),
        'keywords' => array( $game['name'] . ' result', $game['hindi'] . ' रिज़ल्ट', $game['name'] . ' chart', 'daily results', 'IST' ),
        'inLanguage' => array( 'en-IN', 'hi-IN' ), 'isAccessibleForFree' => true,
        'creator' => array( '@id' => home_url( '/' ) . '#organization' ),
        'variableMeasured' => array( '@type' => 'PropertyValue', 'name' => 'Result number', 'description' => 'Two-digit result from 00 to 99', 'minValue' => 0, 'maxValue' => 99 ),
        'spatialCoverage' => 'India',
        'dateModified' => gmdate( 'c', khaiwal_results_modified_time() ),
    );
    if ( $coverage['first'] && $coverage['last'] ) { $dataset['temporalCoverage'] = $coverage['first'] . '/' . $coverage['last']; }
    $month = $latest ? substr( $latest['date'], 0, 7 ) : khaiwal_now()->format( 'Y-m' );
    $dataset['distribution'] = array( '@type' => 'DataDownload', 'encodingFormat' => 'application/json', 'contentUrl' => add_query_arg( array( 'game' => $game['slug'], 'month' => $month ), rest_url( 'khaiwal/v1/results' ) ) );
    return $dataset;
}

function khaiwal_seo_jsonld() {
    $kind = khaiwal_page_kind();
    $graph = array();
    if ( khaiwal_seo_enabled() ) { $graph = khaiwal_schema_graph(); }
    if ( 'game' === $kind ) {
        $game = khaiwal_current_game();
        $url = get_permalink( get_queried_object_id() );
        $dataset = khaiwal_schema_game_dataset( $game, $url );
        $faq = khaiwal_schema_faq( khaiwal_game_faqs( $game ), $url );
        if ( khaiwal_seo_enabled() ) {
            foreach ( $graph as $i => $node ) { if ( isset( $node['@id'] ) && $node['@id'] === $url . '#webpage' ) { $graph[ $i ]['mainEntity'] = array( '@id' => $dataset['@id'] ); $graph[ $i ]['about'] = array( '@id' => $dataset['@id'] ); } }
            $graph[] = $dataset;
            if ( $faq ) { $graph[] = $faq; }
        } else {
            $graph = array( $dataset );
        }
    }
    if ( ! $graph ) { return; }
    echo "\n<script type=\"application/ld+json\" id=\"khaiwal-jsonld\">" . khaiwal_schema_json( array( '@context' => 'https://schema.org', '@graph' => array_values( $graph ) ) ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON-encoded with HEX_TAG/HEX_AMP.
}
add_action( 'wp_head', 'khaiwal_seo_jsonld', 20 );

/* ---------------------------------------------------------------------------------------------
 * Sitemap freshness: lastmod follows the newest published result.
 * ------------------------------------------------------------------------------------------ */

function khaiwal_game_lastmods() {
    static $mods = null;
    if ( null !== $mods ) { return $mods; }
    global $wpdb;
    $mods = array();
    $rows = $wpdb->get_results( "SELECT game.meta_value AS slug, MAX(result.post_modified_gmt) AS modified
        FROM {$wpdb->posts} AS result
        INNER JOIN {$wpdb->postmeta} AS game ON game.post_id = result.ID AND game.meta_key = '_khaiwal_game'
        WHERE result.post_type = 'khaiwal_result' AND result.post_status = 'publish'
        GROUP BY game.meta_value" );
    foreach ( (array) $rows as $row ) { $mods[ $row->slug ] = (int) strtotime( $row->modified . ' UTC' ); }
    return $mods;
}

function khaiwal_chart_page_id() {
    static $id = null;
    if ( null === $id ) { $id = (int) url_to_postid( khaiwal_chart_url() ); }
    return $id;
}

function khaiwal_sitemap_entry( $entry, $post ) {
    if ( 'khaiwal_game' === $post->post_type ) {
        $mods = khaiwal_game_lastmods();
        $time = max( (int) strtotime( $post->post_modified_gmt . ' UTC' ), isset( $mods[ $post->post_name ] ) ? $mods[ $post->post_name ] : 0 );
        if ( $time ) { $entry['lastmod'] = gmdate( 'c', $time ); }
    } elseif ( 'page' === $post->post_type && (int) $post->ID === khaiwal_chart_page_id() ) {
        $entry['lastmod'] = gmdate( 'c', max( khaiwal_results_modified_time(), (int) strtotime( $post->post_modified_gmt . ' UTC' ) ) );
    }
    return $entry;
}
add_filter( 'wp_sitemaps_posts_entry', 'khaiwal_sitemap_entry', 10, 2 );

function khaiwal_sitemap_home_entry( $entry ) {
    $entry['lastmod'] = gmdate( 'c', khaiwal_results_modified_time() );
    return $entry;
}
add_filter( 'wp_sitemaps_posts_show_on_front_entry', 'khaiwal_sitemap_home_entry' );

/** The user sitemap lists authors, which adds nothing for a results site. */
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) { return 'users' === $name ? false : $provider; }, 10, 2 );

/* ---------------------------------------------------------------------------------------------
 * Visible answer-first summaries (also what assistants quote)
 * ------------------------------------------------------------------------------------------ */

/** Bilingual one-paragraph summary of today's results for all games. Returns array( en, hi ). */
function khaiwal_home_summary( $games, DateTimeImmutable $now ) {
    $declared_en = array(); $declared_hi = array(); $waiting_en = array(); $waiting_hi = array();
    foreach ( $games as $game ) {
        $value = khaiwal_result_on( $game, $now );
        if ( '' !== $value ) { $declared_en[] = $game['name'] . ' ' . $value; $declared_hi[] = $game['hindi'] . ' ' . $value; }
        else { $waiting_en[] = $game['name']; $waiting_hi[] = $game['hindi']; }
    }
    $date = $now->format( 'j M Y' );
    $en = sprintf( 'Game results for %s (IST): ', $date ) . ( $declared_en ? implode( ', ', $declared_en ) . '.' : 'none published yet.' ) . ( $waiting_en ? ' Awaiting: ' . implode( ', ', $waiting_en ) . '.' : '' );
    $hi = sprintf( '%s (IST) के गेम रिज़ल्ट: ', $date ) . ( $declared_hi ? implode( ', ', $declared_hi ) . '।' : 'अभी कोई प्रकाशित नहीं हुआ।' ) . ( $waiting_hi ? ' प्रतीक्षा में: ' . implode( ', ', $waiting_hi ) . '।' : '' );
    return array( $en, $hi );
}

/** Short bilingual intro for the chart/directory page. Returns array( en, hi ). */
function khaiwal_chart_summary( $games ) {
    $count = count( $games );
    $times_en = array(); $times_hi = array();
    foreach ( $games as $game ) { if ( $game['time'] ) { $times_en[] = $game['name'] . ' ' . $game['time']; $times_hi[] = $game['hindi'] . ' ' . $game['time']; } }
    return array(
        sprintf( '%1$d games are listed with their usual result times in IST: %2$s. Open any game for its date-by-date monthly chart. A dash means no result is available; nothing is predicted.', $count, implode( ', ', $times_en ) ),
        sprintf( '%1$d गेम उनके सामान्य IST रिज़ल्ट समय के साथ सूचीबद्ध हैं: %2$s। किसी भी गेम का तारीख के अनुसार मासिक चार्ट देखने के लिए उसे खोलें। डैश का मतलब रिज़ल्ट उपलब्ध नहीं है; कुछ भी अनुमानित नहीं है।', $count, implode( ', ', $times_hi ) )
    );
}

/** About paragraph for a game page. Returns array( en, hi ). */
function khaiwal_game_about( $game ) {
    $coverage = khaiwal_game_coverage( $game );
    $count = count( khaiwal_games() );
    $time_en = $game['time'] ? sprintf( 'Its usual result time is %s IST.', $game['time'] ) : 'A usual result time is not listed.';
    $time_hi = $game['time'] ? sprintf( 'इसका सामान्य रिज़ल्ट समय %s IST है।', $game['time'] ) : 'सामान्य रिज़ल्ट समय सूचीबद्ध नहीं है।';
    $cov_en = $coverage['count'] ? sprintf( ' %1$d dated results from %2$s to %3$s are recorded.', $coverage['count'], khaiwal_date_label( $coverage['first'] ), khaiwal_date_label( $coverage['last'] ) ) : ' No dated results are recorded yet.';
    $cov_hi = $coverage['count'] ? sprintf( ' %2$s से %3$s तक के %1$d तारीख-वार रिज़ल्ट दर्ज हैं।', $coverage['count'], khaiwal_date_label( $coverage['first'] ), khaiwal_date_label( $coverage['last'] ) ) : ' अभी कोई तारीख-वार रिज़ल्ट दर्ज नहीं है।';
    return array(
        sprintf( '%1$s (%2$s) is one of the %3$d games listed on %4$s. %5$s This page shows the selected month and the month before it, built from a dated archive snapshot and results published by our editors.%6$s Missing dates appear as dashes; no value is ever predicted.', $game['name'], $game['hindi'], $count, khaiwal_site_name(), $time_en, $cov_en ),
        sprintf( '%1$s (%2$s) %4$s पर सूचीबद्ध %3$d गेम में से एक है। %5$s इस पेज पर चुना हुआ महीना और उससे पिछला महीना दिखता है, जो दिनांकित संग्रह और हमारे संपादकों द्वारा प्रकाशित रिज़ल्ट से बना है।%6$s छूटी हुई तारीखें डैश के रूप में दिखती हैं; कोई मान अनुमानित नहीं होता।', $game['name'], $game['hindi'], $count, khaiwal_site_name(), $time_hi, $cov_hi )
    );
}
