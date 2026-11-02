<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$dashboard = $args['dashboard'];
$current = $dashboard['current']; $recent = $dashboard['recent']; $next = $dashboard['next'];
$seconds = $next ? max( 0, $next['timestamp'] - $dashboard['server_ts'] ) : 0;
$countdown = $next ? sprintf( '%02d:%02d:%02d', floor( $seconds / 3600 ), floor( ( $seconds % 3600 ) / 60 ), $seconds % 60 ) : '—';
?>
<div class="hero-dashboard" role="region" aria-label="Game schedule and results" data-aria-en="Game schedule and results" data-aria-hi="गेम समय और रिज़ल्ट" data-dashboard data-dashboard-url="<?php echo esc_url( rest_url( 'khaiwal/v1/dashboard' ) ); ?>" data-server-ts="<?php echo esc_attr( $dashboard['server_ts'] ); ?>" data-next-ts="<?php echo esc_attr( $next ? $next['timestamp'] : 0 ); ?>">
    <div class="dashboard-topline"><span><i></i><?php echo khaiwal_text( 'THE GAME LINEUP', 'गेम की सूची' ); ?></span><span><?php echo khaiwal_text( 'INDIA TIME', 'भारत समय' ); ?> / IST</span></div>
    <div class="dashboard-current" aria-live="polite" aria-atomic="true">
        <div class="dashboard-row"><span class="dashboard-label"><?php echo khaiwal_text( 'Current game', 'मौजूदा गेम' ); ?></span><span class="dashboard-status <?php echo $current && $current['result'] !== '' ? 'is-published' : ''; ?>" data-dashboard-status><?php echo $current && $current['result'] !== '' ? khaiwal_text( 'Result published', 'रिज़ल्ट प्रकाशित' ) : khaiwal_text( 'Awaiting result', 'रिज़ल्ट की प्रतीक्षा' ); ?></span></div>
        <div class="dashboard-current-main">
            <div class="dashboard-game-info">
                <a class="dashboard-game-name" data-dashboard-current-link href="<?php echo esc_url( $current ? $current['url'] : khaiwal_chart_url() ); ?>"><span data-dashboard-current-name><?php echo esc_html( $current ? $current['name'] : 'No scheduled game' ); ?></span> <small lang="hi" data-dashboard-current-hi><?php echo esc_html( $current ? '/ ' . $current['hindi'] : '' ); ?></small></a>
                <div class="dashboard-game-time"><?php echo khaiwal_icon( 'clock' ); ?> <span data-dashboard-current-time><?php echo esc_html( $current ? $current['time'] . ' IST' : '—' ); ?></span><i></i><time data-dashboard-current-date datetime="<?php echo esc_attr( $current ? $current['date'] : '' ); ?>"><?php echo esc_html( $current ? date_i18n( 'j M', strtotime( $current['date'] . ' 12:00:00' ) ) : '—' ); ?></time></div>
            </div>
            <div class="dashboard-number"><small><?php echo khaiwal_text( 'Result', 'रिज़ल्ट' ); ?></small><strong data-dashboard-current-result><?php echo esc_html( $current && $current['result'] !== '' ? $current['result'] : '—' ); ?></strong></div>
        </div>
    </div>
    <div class="dashboard-lower">
        <a class="dashboard-mini dashboard-recent" data-dashboard-recent-link href="<?php echo esc_url( $recent ? $recent['url'] : khaiwal_chart_url() ); ?>">
            <span class="dashboard-label"><?php echo khaiwal_text( 'Recent result', 'पिछला रिज़ल्ट' ); ?></span>
            <span class="dashboard-mini-row"><span class="dashboard-mini-name"><strong data-dashboard-recent-name><?php echo esc_html( $recent ? $recent['name'] : 'No recent result' ); ?></strong><small lang="hi" data-dashboard-recent-hi><?php echo esc_html( $recent ? $recent['hindi'] : '' ); ?></small></span><b data-dashboard-recent-result><?php echo esc_html( $recent ? $recent['result'] : '—' ); ?></b></span>
            <time data-dashboard-recent-date datetime="<?php echo esc_attr( $recent ? $recent['date'] : '' ); ?>"><?php echo esc_html( $recent ? date_i18n( 'j M', strtotime( $recent['date'] . ' 12:00:00' ) ) : '—' ); ?></time>
        </a>
        <a class="dashboard-mini dashboard-next" data-dashboard-next-link href="<?php echo esc_url( $next ? $next['url'] : khaiwal_chart_url() ); ?>">
            <span class="dashboard-label"><?php echo khaiwal_text( 'Next game', 'अगला गेम' ); ?></span>
            <span class="dashboard-mini-name"><strong data-dashboard-next-name><?php echo esc_html( $next ? $next['name'] : 'No scheduled game' ); ?></strong><small lang="hi" data-dashboard-next-hi><?php echo esc_html( $next ? $next['hindi'] : '' ); ?></small></span>
            <span class="dashboard-next-time"><span data-dashboard-next-time><?php echo esc_html( $next ? $next['time'] . ' IST' : '—' ); ?></span><span class="dashboard-countdown"><span><?php echo khaiwal_text( 'Time to game', 'बाकी समय' ); ?></span> <b data-dashboard-countdown><?php echo esc_html( $countdown ); ?></b></span></span>
        </a>
    </div>
    <noscript><div class="dashboard-noscript">Reload this page to update results and the countdown.</div></noscript>
    <div class="dashboard-bottom"><span data-dashboard-refresh-note role="status"><?php echo khaiwal_text( 'Updates appear when published', 'प्रकाशित होने पर अपडेट दिखेंगे' ); ?></span><a href="#full-chart"><?php echo khaiwal_text( 'Full chart', 'पूरा चार्ट' ); ?> <?php echo khaiwal_icon( 'arrow' ); ?></a></div>
</div>
