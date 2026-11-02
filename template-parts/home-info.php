<?php
/** Homepage "Good to know" cards linking into the single Info page. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$info = khaiwal_info_url();
$cards = array(
    array( 'shield', 'privacy-policy', 'Privacy policy', 'गोपनीयता नीति', 'What we store, and what we never collect.', 'हम क्या रखते हैं और क्या कभी नहीं लेते।' ),
    array( 'info', 'disclaimer', 'Disclaimer', 'अस्वीकरण', 'Information only: no predictions, tips or wagers.', 'सिर्फ़ जानकारी: कोई अनुमान, टिप या दांव नहीं।' ),
    array( 'check', 'terms-and-conditions', 'Terms & conditions', 'नियम और शर्तें', 'The simple rules for using this website.', 'इस वेबसाइट के उपयोग के आसान नियम।' ),
    array( 'search', 'faqs', 'FAQs', 'अक्सर पूछे सवाल', 'Quick answers about WAIT, dashes and timings.', 'WAIT, डैश और समय पर झटपट जवाब।' ),
    array( 'book', 'blog', 'Blog', 'ब्लॉग', 'Guides on reading charts and results.', 'चार्ट और रिज़ल्ट पढ़ने की गाइड।' ),
);
?>
<section class="section info-home" id="information">
    <div class="container">
        <div class="section-heading" data-reveal><div><div class="eyebrow dark-eyebrow"><span class="section-number">07 /</span> <?php echo khaiwal_text( 'INFORMATION', 'जानकारी' ); ?></div><h2><?php echo khaiwal_text( 'Information & policies', 'जानकारी और नीतियाँ' ); ?><span class="heading-dot">.</span></h2><p><?php echo khaiwal_text( 'Everything about how this site works, in one editable page.', 'यह साइट कैसे काम करती है, सब कुछ एक ही पेज पर।' ); ?></p></div></div>
        <div class="info-cards" data-reveal>
            <?php foreach ( $cards as $card ) : ?>
            <a class="info-card" href="<?php echo esc_url( $info . '#' . $card[1] ); ?>"><span class="feature-icon"><?php echo khaiwal_icon( $card[0] ); ?></span><strong><?php echo khaiwal_text( $card[2], $card[3] ); ?></strong><p><?php echo khaiwal_text( $card[4], $card[5] ); ?></p><span class="info-card-more"><?php echo khaiwal_text( 'Read', 'पढ़ें' ); ?> <?php echo khaiwal_icon( 'arrow' ); ?></span></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
