<?php
/** Top-bar account links: log in and jump straight to the result publishing table. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$publish_url = khaiwal_publish_url();
$can_publish = is_user_logged_in() && current_user_can( khaiwal_publish_capability() );
?>
<nav class="utility-account" aria-label="<?php esc_attr_e( 'Account', 'khaiwal' ); ?>" data-aria-en="Account" data-aria-hi="खाता">
    <?php if ( ! is_user_logged_in() ) : ?>
        <a class="utility-link" href="<?php echo esc_url( wp_login_url( $publish_url ) ); ?>" rel="nofollow"><?php echo khaiwal_icon( 'user' ); ?><?php echo khaiwal_text( 'Login', 'लॉगिन' ); ?></a>
        <a class="utility-link utility-link-publish" href="<?php echo esc_url( $publish_url ); ?>" rel="nofollow"><?php echo khaiwal_icon( 'publish' ); ?><?php echo khaiwal_text( 'Publish results', 'रिज़ल्ट प्रकाशित करें' ); ?></a>
    <?php else : ?>
        <?php if ( $can_publish ) : ?>
            <a class="utility-link utility-link-publish" href="<?php echo esc_url( $publish_url ); ?>" rel="nofollow"><?php echo khaiwal_icon( 'publish' ); ?><?php echo khaiwal_text( 'Publish results', 'रिज़ल्ट प्रकाशित करें' ); ?></a>
        <?php endif; ?>
        <a class="utility-link" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" rel="nofollow"><?php echo khaiwal_icon( 'user' ); ?><?php echo khaiwal_text( 'Log out', 'लॉग आउट' ); ?></a>
    <?php endif; ?>
</nav>
