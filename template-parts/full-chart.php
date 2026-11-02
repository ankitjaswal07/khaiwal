<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$games = $args['games']; $now = $args['now'];
$game_filter = isset( $_GET['chart_game'] ) && is_string( $_GET['chart_game'] ) ? sanitize_key( wp_unslash( $_GET['chart_game'] ) ) : 'all';
if ( $game_filter !== 'all' && ! isset( $games[ $game_filter ] ) ) { $game_filter = 'all'; }
$month_number = isset( $_GET['chart_month'] ) && is_scalar( $_GET['chart_month'] ) ? absint( $_GET['chart_month'] ) : (int) $now->format( 'n' );
$year_number = isset( $_GET['chart_year'] ) && is_scalar( $_GET['chart_year'] ) ? absint( $_GET['chart_year'] ) : (int) $now->format( 'Y' );
if ( $month_number < 1 || $month_number > 12 ) { $month_number = (int) $now->format( 'n' ); }
if ( $year_number < 2000 || $year_number > 2100 ) { $year_number = (int) $now->format( 'Y' ); }
$month_key = sprintf( '%04d-%02d', $year_number, $month_number );
$month = DateTimeImmutable::createFromFormat( '!Y-m', $month_key, new DateTimeZone( 'Asia/Kolkata' ) );
$shown_games = $game_filter === 'all' ? $games : array( $game_filter => $games[ $game_filter ] );
$values = array(); foreach ( $shown_games as $game ) { $values[ $game['slug'] ] = khaiwal_results_for_month( $game, $month_key ); }
$prev = $month->modify( '-1 month' ); $next = $month->modify( '+1 month' );
$chart_url = function ( $date ) use ( $game_filter ) {
    return add_query_arg( array( 'chart_game' => $game_filter, 'chart_month' => $date->format( 'n' ), 'chart_year' => $date->format( 'Y' ) ), home_url( '/' ) ) . '#full-chart';
};
?>
<section id="full-chart" class="section full-chart-section" data-chart-month="<?php echo esc_attr( $month_key ); ?>">
    <div class="container">
        <div class="section-heading full-chart-heading" data-reveal>
            <div><div class="eyebrow dark-eyebrow"><span class="section-number">02 /</span> <?php echo khaiwal_text( 'THE COMPLETE RECORD', 'पूरा रिकॉर्ड' ); ?></div><h2><?php echo khaiwal_text( 'The full result chart', 'पूरा रिज़ल्ट चार्ट' ); ?><span class="heading-dot">.</span></h2><p><?php echo khaiwal_text( 'Every game, every date. Find a result with the filters below.', 'हर गेम, हर तारीख। नीचे फ़िल्टर से रिज़ल्ट खोजें।' ); ?></p></div>
            <a class="chart-heading-link" href="<?php echo esc_url( khaiwal_chart_url() ); ?>"><?php echo khaiwal_text( 'Explore game pages', 'गेम पेज देखें' ); ?> <?php echo khaiwal_icon( 'arrow-up' ); ?></a>
        </div>
        <form class="full-chart-filters" method="get" action="<?php echo esc_url( home_url( '/#full-chart' ) ); ?>">
            <label for="chart-game"><span><?php echo khaiwal_text( 'GAME', 'गेम' ); ?></span><select id="chart-game" name="chart_game"><option value="all" data-en="All games" data-hi="सभी गेम" <?php selected( $game_filter, 'all' ); ?>>All games</option><?php foreach ( $games as $game ) : ?><option value="<?php echo esc_attr( $game['slug'] ); ?>" data-en="<?php echo esc_attr( $game['name'] ); ?>" data-hi="<?php echo esc_attr( $game['hindi'] ); ?>" <?php selected( $game_filter, $game['slug'] ); ?>><?php echo esc_html( $game['name'] ); ?></option><?php endforeach; ?></select></label>
            <label for="chart-month"><span><?php echo khaiwal_text( 'MONTH', 'महीना' ); ?></span><select id="chart-month" name="chart_month"><?php for ( $number = 1; $number <= 12; $number++ ) : $option_date = $month->setDate( $year_number, $number, 1 ); ?><option value="<?php echo esc_attr( $number ); ?>" data-month-option="<?php echo esc_attr( $option_date->format( 'Y-m' ) ); ?>" <?php selected( $month_number, $number ); ?>><?php echo esc_html( $option_date->format( 'F' ) ); ?></option><?php endfor; ?></select></label>
            <label for="chart-year"><span><?php echo khaiwal_text( 'YEAR', 'साल' ); ?></span><input id="chart-year" type="number" name="chart_year" min="2000" max="2100" inputmode="numeric" value="<?php echo esc_attr( $year_number ); ?>" required></label>
            <button class="chart-filter-submit" type="submit"><?php echo khaiwal_text( 'View results', 'रिज़ल्ट देखें' ); ?> <?php echo khaiwal_icon( 'arrow' ); ?></button>
        </form>
        <div class="full-chart-card" data-reveal>
            <div class="full-chart-card-head"><div><small><?php echo khaiwal_text( 'MONTHLY RESULTS', 'महीने के रिज़ल्ट' ); ?></small><h3 data-month-label="<?php echo esc_attr( $month_key ); ?>"><?php echo esc_html( $month->format( 'F Y' ) ); ?></h3></div><div class="full-chart-navigation"><?php if ( (int) $prev->format( 'Y' ) >= 2000 ) : ?><a href="<?php echo esc_url( $chart_url( $prev ) ); ?>" aria-label="<?php esc_attr_e( 'Previous month', 'khaiwal' ); ?>" data-aria-en="Previous month" data-aria-hi="पिछला महीना"><?php echo khaiwal_icon( 'left' ); ?></a><?php endif; ?><?php if ( (int) $next->format( 'Y' ) <= 2100 ) : ?><a href="<?php echo esc_url( $chart_url( $next ) ); ?>" aria-label="<?php esc_attr_e( 'Next month', 'khaiwal' ); ?>" data-aria-en="Next month" data-aria-hi="अगला महीना"><?php echo khaiwal_icon( 'chevron' ); ?></a><?php endif; ?></div></div>
            <div class="full-chart-scroll" role="region" tabindex="0" aria-labelledby="full-chart-title" aria-describedby="full-chart-help">
                <table class="full-result-table"><caption id="full-chart-title" class="screen-reader-text"><span data-month-label="<?php echo esc_attr( $month_key ); ?>"><?php echo esc_html( $month->format( 'F Y' ) ); ?></span> <?php echo khaiwal_text( 'game results', 'गेम रिज़ल्ट' ); ?></caption>
                    <thead><tr><th scope="col"><?php echo khaiwal_text( 'DATE', 'तारीख' ); ?></th><?php foreach ( $shown_games as $game ) : ?><th scope="col"><a href="<?php echo esc_url( khaiwal_game_url( $game ) ); ?>"><?php echo khaiwal_text( $game['name'], $game['hindi'] ); ?></a><small><?php echo esc_html( $game['time'] ? $game['time'] . ' IST' : '—' ); ?></small></th><?php endforeach; ?></tr></thead>
                    <tbody><?php for ( $day = 1; $day <= (int) $month->format( 't' ); $day++ ) : $date = sprintf( '%s-%02d', $month_key, $day ); ?><tr class="<?php echo $date === $now->format( 'Y-m-d' ) ? 'is-today' : ''; ?>"><th scope="row"><time datetime="<?php echo esc_attr( $date ); ?>" data-result-date="<?php echo esc_attr( $date ); ?>"><?php echo esc_html( sprintf( '%02d %s', $day, $month->format( 'M' ) ) ); ?></time></th><?php foreach ( $shown_games as $game ) : $value = isset( $values[ $game['slug'] ][ $date ] ) ? $values[ $game['slug'] ][ $date ] : ''; ?><td data-chart-cell data-chart-date="<?php echo esc_attr( $date ); ?>" data-game-slug="<?php echo esc_attr( $game['slug'] ); ?>" class="<?php echo $value !== '' ? 'has-result' : 'is-unavailable'; ?>"><?php echo khaiwal_result_html( $value, $date === $now->format( 'Y-m-d' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td><?php endforeach; ?></tr><?php endfor; ?></tbody>
                </table>
            </div>
            <div id="full-chart-help" class="full-chart-help"><span><?php echo khaiwal_icon( 'info' ); ?> <?php echo khaiwal_text( 'Scroll to see every date and game.', 'हर तारीख और गेम देखने के लिए स्क्रॉल करें।' ); ?></span><span><?php echo khaiwal_text( 'A dash means no result is available.', 'डैश का मतलब रिज़ल्ट उपलब्ध नहीं है।' ); ?></span></div>
        </div>
        <p class="full-chart-source"><?php echo khaiwal_text( 'Historical values include an A1-Satta snapshot from 2 November 2026. Editor-published results override it.', 'पुराने नंबरों में 2 नवंबर 2026 तक A1-Satta का संग्रह शामिल है। संपादक द्वारा प्रकाशित रिज़ल्ट इसे बदलते हैं।' ); ?> <a href="https://a1-satta.com/" target="_blank" rel="noopener noreferrer"><?php echo khaiwal_text( 'View source', 'स्रोत देखें' ); ?> <?php echo khaiwal_icon( 'arrow-up' ); ?></a></p>
    </div>
</section>
