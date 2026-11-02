<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
the_post();
$games = khaiwal_games(); $slug = get_post_field( 'post_name', get_the_ID() );
$game = isset( $games[ $slug ] ) ? $games[ $slug ] : khaiwal_catalog_game( $slug );
if ( ! $game ) { $game = array( 'slug' => $slug, 'name' => get_the_title(), 'hindi' => get_the_title(), 'time' => '', 'results' => array() ); }
if ( ! isset( $games[ $slug ] ) ) {
    $game['name'] = get_the_title();
    $hi = get_post_meta( get_the_ID(), '_khaiwal_hindi', true );
    $time = get_post_meta( get_the_ID(), '_khaiwal_time', true );
    if ( $hi ) { $game['hindi'] = $hi; }
    if ( $time ) { $game['time'] = $time; }
    $game['post_id'] = get_the_ID();
}
$now = khaiwal_now(); $requested = isset( $_GET['month'] ) ? sanitize_text_field( wp_unslash( $_GET['month'] ) ) : $now->format( 'Y-m' );
if ( ! preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $requested ) ) { $requested = $now->format( 'Y-m' ); }
$month = DateTimeImmutable::createFromFormat( '!Y-m', $requested, new DateTimeZone( 'Asia/Kolkata' ) );
if ( ! $month ) { $month = $now->modify( 'first day of this month' ); }
$last_month = $month->modify( '-1 month' );
$options = array();
for ( $i = 0; $i < 6; $i++ ) { $option = $now->modify( 'first day of this month' )->modify( '-' . $i . ' month' ); $options[ $option->format( 'Y-m' ) ] = $option->format( 'F Y' ); }
foreach ( array_merge( array_keys( $game['results'] ), khaiwal_published_months( $slug ) ) as $key ) { $date = DateTimeImmutable::createFromFormat( '!Y-m', $key, new DateTimeZone( 'Asia/Kolkata' ) ); if ( $date ) { $options[ $key ] = $date->format( 'F Y' ); } }
if ( ! isset( $options[ $requested ] ) ) { $options[ $requested ] = $month->format( 'F Y' ); }
krsort( $options );
$current = khaiwal_result_on( $game, $now ); $previous = khaiwal_result_on( $game, $now->modify( '-1 day' ) );
// GET forms replace a URL's query string; preserve plain-permalink query vars.
$permalink_query = wp_parse_url( get_permalink(), PHP_URL_QUERY );
$permalink_args = array();
if ( $permalink_query ) { wp_parse_str( $permalink_query, $permalink_args ); }
?>
<main id="main">
    <section class="page-hero game-page-hero"><div class="container"><div class="breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo khaiwal_text( 'Home', 'होम' ); ?></a><span>/</span><a href="<?php echo esc_url( khaiwal_chart_url() ); ?>"><?php echo khaiwal_text( 'Charts', 'चार्ट' ); ?></a><span>/</span><?php echo khaiwal_text( $game['name'], $game['hindi'] ); ?></div><div class="game-hero-flex"><div><div class="eyebrow"><span class="eyebrow-dot"></span><?php echo khaiwal_text( 'GAME RESULTS & CHARTS', 'गेम रिज़ल्ट और चार्ट' ); ?></div><h1><?php echo khaiwal_text( $game['name'], $game['hindi'] ); ?><em>.</em></h1><p><?php echo khaiwal_icon( 'clock' ); ?> <?php echo $game['time'] ? esc_html( $game['time'] . ' IST' ) : khaiwal_text( 'Time not listed', 'समय उपलब्ध नहीं' ); ?> <span class="hero-pipe">/</span> <?php echo khaiwal_text( 'Results and monthly history', 'रिज़ल्ट और महीने का रिकॉर्ड' ); ?></p></div><div class="game-hero-result"><div><small><?php echo khaiwal_text( 'PREVIOUS', 'पिछला' ); ?></small><strong><?php echo esc_html( $previous !== '' ? $previous : '—' ); ?></strong></div><?php echo khaiwal_icon( 'arrow' ); ?><div><small><?php echo khaiwal_text( 'TODAY', 'आज' ); ?></small><strong class="gold"><?php echo esc_html( $current !== '' ? $current : '— —' ); ?></strong></div></div></div></div></section>
    <section class="section charts-section"><div class="container"><div class="chart-section-heading"><div><div class="eyebrow dark-eyebrow"><span class="section-number">01 /</span> <?php echo khaiwal_text( 'THE MONTHLY RECORD', 'महीने का रिकॉर्ड' ); ?></div><h2><?php echo khaiwal_text( 'Results, date by date', 'तारीख के अनुसार रिज़ल्ट' ); ?><span class="heading-dot">.</span></h2><p><?php echo khaiwal_text( 'Browse the selected month and the month before it.', 'चुने हुए महीने और उससे पिछले महीने का रिकॉर्ड देखें।' ); ?></p></div><form class="month-picker" method="get" action="<?php echo esc_url( get_permalink() ); ?>"><?php foreach ( $permalink_args as $key => $value ) { if ( $key !== 'month' && is_scalar( $value ) ) { echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">'; } } ?><label for="month"><?php echo khaiwal_text( 'SELECT MONTH', 'महीना चुनें' ); ?></label><select id="month" name="month"><?php foreach ( $options as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" data-month-label="<?php echo esc_attr( $key ); ?>" <?php selected( $requested, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><button type="submit"><?php echo khaiwal_text( 'View', 'देखें' ); ?> <?php echo khaiwal_icon( 'arrow' ); ?></button></form></div><div class="month-grid"><?php get_template_part( 'template-parts/month', 'table', array( 'game' => $game, 'month' => $month ) ); get_template_part( 'template-parts/month', 'table', array( 'game' => $game, 'month' => $last_month ) ); ?></div><div class="source-note"><?php echo khaiwal_icon( 'info' ); ?><p><?php if ( ! empty( $game['source_url'] ) ) : ?><?php echo khaiwal_text( 'Bundled historical snapshot:', 'संग्रहित पुराना डेटा:' ); ?> <a href="<?php echo esc_url( $game['source_url'] ); ?>" target="_blank" rel="noopener noreferrer">A1-Satta <?php echo esc_html( $game['name'] ); ?> chart</a>, <?php echo khaiwal_text( '2 November 2026. Published editor entries may override it.', '2 नवंबर 2026। संपादक द्वारा प्रकाशित डेटा इसे बदल सकता है।' ); ?> <?php else : ?><?php echo khaiwal_text( 'Results appear once published by an editor.', 'संपादक द्वारा प्रकाशित होने पर रिज़ल्ट दिखाई देंगे।' ); ?> <?php endif; ?><?php echo khaiwal_text( 'A dash means no result is available.', 'डैश का मतलब रिज़ल्ट उपलब्ध नहीं है।' ); ?></p></div><?php if ( trim( get_the_content() ) ) : ?><div class="prose game-content"><?php the_content(); ?></div><?php endif; ?><a class="back-link" href="<?php echo esc_url( khaiwal_chart_url() ); ?>"><?php echo khaiwal_icon( 'left' ); ?> <?php echo khaiwal_text( 'Back to all games', 'सभी गेम पर वापस जाएँ' ); ?></a></div></section>
</main>
<?php get_footer(); ?>
