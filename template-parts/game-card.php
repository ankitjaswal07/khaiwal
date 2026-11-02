<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$game = $args['game'];
$today = khaiwal_now();
$previous = $today->modify( '-1 day' );
$current_value = khaiwal_result_on( $game, $today );
$previous_value = khaiwal_result_on( $game, $previous );
$words = preg_split( '/\s+/', $game['name'] );
$initials = strtoupper( substr( $words[0], 0, 1 ) . ( isset( $words[1] ) ? substr( $words[1], 0, 1 ) : substr( $words[0], 1, 1 ) ) );
?>
<article class="game-card" data-game-card data-name="<?php echo esc_attr( strtolower( $game['name'] ) . ' ' . $game['hindi'] ); ?>" data-popular="<?php echo ! empty( $game['popular'] ) ? 'yes' : 'no'; ?>" data-status="<?php echo $current_value !== '' ? 'declared' : 'awaiting'; ?>">
    <div class="game-card-top">
        <span class="game-monogram" aria-hidden="true"><?php echo esc_html( $initials ); ?></span>
        <div class="game-title"><h3><?php echo khaiwal_text( $game['name'], $game['hindi'] ); ?></h3><p><?php echo khaiwal_icon( 'clock' ); ?> <?php echo esc_html( $game['time'] ? $game['time'] . ' IST' : '—' ); ?></p></div>
        <a class="card-arrow" href="<?php echo esc_url( khaiwal_game_url( $game ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'View %s chart', 'khaiwal' ), $game['name'] ) ); ?>" data-aria-en="<?php echo esc_attr( 'View ' . $game['name'] . ' chart' ); ?>" data-aria-hi="<?php echo esc_attr( $game['hindi'] . ' का चार्ट देखें' ); ?>"><?php echo khaiwal_icon( 'arrow-up' ); ?></a>
    </div>
    <div class="game-results">
        <div><span class="result-label"><?php echo khaiwal_text( 'PREVIOUS', 'पिछला' ); ?></span><strong class="previous-number"><?php echo esc_html( $previous_value !== '' ? $previous_value : '—' ); ?></strong></div>
        <span class="result-separator"><?php echo khaiwal_icon( 'arrow' ); ?></span>
        <div><span class="result-label"><?php echo khaiwal_text( 'TODAY', 'आज' ); ?></span><strong class="current-number <?php echo $current_value === '' ? 'is-waiting' : ''; ?>"><?php echo esc_html( $current_value !== '' ? $current_value : '— —' ); ?></strong></div>
    </div>
    <div class="game-card-bottom"><span class="result-status <?php echo $current_value !== '' ? 'is-declared' : ''; ?>"><i></i><?php echo $current_value !== '' ? khaiwal_text( 'Result declared', 'रिज़ल्ट घोषित' ) : khaiwal_text( 'Awaiting result', 'रिज़ल्ट की प्रतीक्षा' ); ?></span><a href="<?php echo esc_url( khaiwal_game_url( $game ) ); ?>"><?php echo khaiwal_text( 'View chart', 'चार्ट देखें' ); ?> <?php echo khaiwal_icon( 'chevron' ); ?></a></div>
</article>
