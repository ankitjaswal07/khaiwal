<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$faqs = khaiwal_home_faqs();
?>
<section id="faqs" class="section faq-section">
    <div class="container faq-layout">
        <div class="faq-intro" data-reveal><div class="eyebrow dark-eyebrow"><span class="section-number">06 /</span> <?php echo khaiwal_text( 'GOOD TO KNOW', 'ज़रूरी जानकारी' ); ?></div><h2><?php echo khaiwal_text( 'Questions?', 'कोई सवाल?' ); ?><br><?php echo khaiwal_text( 'We have answers', 'जवाब यहाँ हैं' ); ?><span class="heading-dot">.</span></h2><p><?php echo khaiwal_text( 'A little more clarity about the results, the charts and how it all works.', 'रिज़ल्ट, चार्ट और इनके काम करने के तरीके के बारे में अधिक जानकारी।' ); ?></p><a class="text-link" href="<?php echo esc_url( khaiwal_contact_url() ); ?>"><?php echo khaiwal_text( 'Still have a question? Get in touch', 'और सवाल हैं? संपर्क करें' ); ?> <span><?php echo khaiwal_icon( 'arrow' ); ?></span></a></div>
        <div class="faq-list" data-reveal><?php foreach ( $faqs as $index => $faq ) : if ( count( $faq ) < 4 ) { continue; } ?><details class="faq-item" <?php echo $index === 0 ? 'open' : ''; ?>><summary><span><?php echo khaiwal_text( $faq[0], $faq[1] ); ?></span><span class="faq-toggle" aria-hidden="true"></span></summary><div class="faq-answer"><p><?php echo khaiwal_text( $faq[2], $faq[3] ); ?></p></div></details><?php endforeach; ?><p class="faq-more"><a href="<?php echo esc_url( khaiwal_info_url() . '#faqs' ); ?>"><?php echo khaiwal_text( 'More questions, privacy policy and terms', 'और सवाल, गोपनीयता नीति और नियम' ); ?> <?php echo khaiwal_icon( 'arrow' ); ?></a></p></div>
    </div>
</section>
