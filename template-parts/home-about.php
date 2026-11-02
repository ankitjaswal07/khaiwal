<?php
/** Homepage "About" section. Heading and text can be changed in Appearance > Customize > Khaiwal site options. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$title = trim( (string) get_theme_mod( 'khaiwal_about_title', '' ) );
$text = trim( (string) get_theme_mod( 'khaiwal_about_text', '' ) );
$site = khaiwal_site_name();
$info = khaiwal_info_url();
?>
<section class="section about-home" id="about">
    <div class="container about-home-inner" data-reveal>
        <div class="about-home-copy">
            <div class="eyebrow dark-eyebrow"><span class="section-number">04 /</span> <?php echo khaiwal_text( 'ABOUT US', 'हमारे बारे में' ); ?></div>
            <?php if ( '' !== $title ) : ?><h2><?php echo esc_html( $title ); ?><span class="heading-dot">.</span></h2><?php else : ?><h2><?php echo khaiwal_text( 'About ' . $site, $site . ' के बारे में' ); ?><span class="heading-dot">.</span></h2><?php endif; ?>
            <?php if ( '' !== $text ) : foreach ( preg_split( '/\R{2,}/', $text ) as $para ) { if ( '' !== trim( $para ) ) { echo '<p>' . nl2br( esc_html( trim( $para ) ) ) . '</p>'; } } else : ?>
            <p><?php echo khaiwal_text( $site . ' is an independent information website that keeps a clear, dated record of published game results for every game on the daily lineup.', $site . ' एक स्वतंत्र जानकारी वाली वेबसाइट है, जो रोज़ की सूची के हर गेम के प्रकाशित रिज़ल्ट का साफ़, तारीख-वार रिकॉर्ड रखती है।' ); ?></p>
            <p><?php echo khaiwal_text( 'Results are entered by our editors in India Standard Time and left as WAIT until they are announced. We never predict numbers and we do not take bets or payments.', 'रिज़ल्ट हमारे संपादक भारतीय मानक समय में दर्ज करते हैं और घोषित होने तक WAIT दिखता है। हम कभी नंबर का अनुमान नहीं लगाते और न ही कोई दांव या भुगतान लेते हैं।' ); ?></p>
            <?php endif; ?>
            <a class="button button-dark" href="<?php echo esc_url( $info . '#about-us' ); ?>"><?php echo khaiwal_text( 'Read more about us', 'हमारे बारे में और पढ़ें' ); ?> <span class="button-icon"><?php echo khaiwal_icon( 'arrow' ); ?></span></a>
        </div>
        <ul class="about-facts">
            <li><strong><?php echo (int) count( khaiwal_games() ); ?></strong><span><?php echo khaiwal_text( 'games on the daily lineup', 'रोज़ की सूची में गेम' ); ?></span></li>
            <li><strong>IST</strong><span><?php echo khaiwal_text( 'every date and time', 'हर तारीख और समय' ); ?></span></li>
            <li><strong>2</strong><span><?php echo khaiwal_text( 'months of charts per game', 'हर गेम के दो महीने के चार्ट' ); ?></span></li>
            <li><strong>JSON</strong><span><?php echo khaiwal_text( 'free data for tools and assistants', 'टूल और असिस्टेंट के लिए मुफ़्त डेटा' ); ?></span></li>
        </ul>
    </div>
</section>
