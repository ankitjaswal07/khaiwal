<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php echo khaiwal_text( 'Skip to content', 'मुख्य सामग्री पर जाएँ' ); ?></a>
<header class="site-header">
    <div class="utility-bar">
        <div class="container utility-inner">
            <span class="utility-message"><span class="status-dot"></span><?php echo khaiwal_text( 'YOUR EVERYDAY RESULTS DESTINATION', 'आपके रोज़ाना रिज़ल्ट की जगह' ); ?></span>
            <div class="utility-right">
                <?php get_template_part( 'template-parts/utility', 'account' ); ?>
                <span class="utility-divider"></span>
                <span class="utility-clock"><?php echo khaiwal_icon( 'clock' ); ?> <time data-ist-clock><?php echo esc_html( khaiwal_now()->format( 'D, M j, Y  •  g:i A' ) ); ?></time> <span>IST</span></span>
                <span class="utility-divider"></span>
                <div class="language-switch" role="group" aria-label="Language / भाषा">
                    <button type="button" data-language="en" aria-pressed="true" lang="en">English</button><span>/</span><button type="button" data-language="hi" aria-pressed="false" lang="hi">हिंदी</button>
                </div>
            </div>
        </div>
    </div>
    <div class="main-nav-wrap">
        <div class="container main-nav">
            <a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) . ' home' ); ?>" data-aria-en="<?php echo esc_attr( get_bloginfo( 'name' ) . ' home' ); ?>" data-aria-hi="<?php echo esc_attr( get_bloginfo( 'name' ) . ' होम' ); ?>">
                <?php if ( has_custom_logo() ) { echo wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'full', false, array( 'class' => 'brand-custom-logo', 'alt' => '' ) ); } else { ?><span class="brand-mark" aria-hidden="true"><span>K</span><i></i></span><?php } ?>
                <span class="brand-type"><strong><?php bloginfo( 'name' ); ?><b>.</b></strong><small><?php echo khaiwal_text( 'RESULTS & CHARTS', 'रिज़ल्ट और चार्ट' ); ?></small></span>
            </a>
            <nav class="primary-nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'khaiwal' ); ?>" data-aria-en="Primary navigation" data-aria-hi="मुख्य नेविगेशन">
                <?php if ( has_nav_menu( 'primary' ) ) : ?>
                    <?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'primary-menu', 'depth' => 1 ) ); ?>
                <?php else : ?>
                    <ul class="primary-menu">
                        <li><a class="nav-pill <?php echo is_front_page() ? 'is-active' : ''; ?>" <?php echo is_front_page() ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo khaiwal_text( 'Home', 'होम' ); ?></a></li>
                        <li><a class="nav-pill <?php echo ( is_page( 'chart' ) || is_page_template( 'page-chart.php' ) || is_singular( 'khaiwal_game' ) ) ? 'is-active' : ''; ?>" <?php echo ( is_page( 'chart' ) || is_page_template( 'page-chart.php' ) ) ? 'aria-current="page"' : ( is_singular( 'khaiwal_game' ) ? 'aria-current="location"' : '' ); ?> href="<?php echo esc_url( khaiwal_chart_url() ); ?>"><?php echo khaiwal_text( 'Chart', 'चार्ट' ); ?></a></li>
                        <li><a class="nav-pill <?php echo ( is_page( 'contact' ) || is_page_template( 'page-contact.php' ) ) ? 'is-active' : ''; ?>" <?php echo ( is_page( 'contact' ) || is_page_template( 'page-contact.php' ) ) ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url( khaiwal_contact_url() ); ?>"><?php echo khaiwal_text( 'Contact', 'संपर्क' ); ?></a></li>

                        <li><a class="nav-pill <?php echo khaiwal_is_info_page() ? 'is-active' : ''; ?>" <?php echo khaiwal_is_info_page() ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url( khaiwal_info_url() ); ?>"><?php echo khaiwal_text( 'Info', 'जानकारी' ); ?></a></li>
                    </ul>
                <?php endif; ?>
            </nav>
            <a class="nav-chart-link" href="<?php echo esc_url( khaiwal_chart_url() ); ?>"><?php echo khaiwal_icon( 'chart' ); ?> <span><?php echo khaiwal_text( 'Explore charts', 'चार्ट देखें' ); ?></span> <?php echo khaiwal_icon( 'arrow-up' ); ?></a>
        </div>
    </div>
    <div class="notice-bar">
        <div class="container notice-inner">
            <span class="notice-label"><span class="notice-spark"><?php echo khaiwal_icon( 'spark' ); ?></span> <?php echo khaiwal_text( 'LIVE UPDATES', 'लाइव अपडेट' ); ?></span>
            <div class="notice-marquee" data-marquee data-dashboard-url="<?php echo esc_url( rest_url( 'khaiwal/v1/dashboard' ) ); ?>" role="region" aria-label="<?php esc_attr_e( 'Latest results and updates', 'khaiwal' ); ?>" data-aria-en="Latest results and updates" data-aria-hi="ताज़ा रिज़ल्ट और अपडेट" tabindex="0">
                <ul class="marquee-list" data-marquee-list><?php echo khaiwal_ticker_items(); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts. ?></ul>
            </div>
            <button type="button" class="marquee-toggle" data-marquee-toggle aria-pressed="false" aria-label="<?php esc_attr_e( 'Pause scrolling updates', 'khaiwal' ); ?>" data-label-pause="<?php esc_attr_e( 'Pause scrolling updates', 'khaiwal' ); ?>" data-label-play="<?php esc_attr_e( 'Resume scrolling updates', 'khaiwal' ); ?>" hidden><?php echo khaiwal_icon( 'pause', 'icon-pause' ); ?><?php echo khaiwal_icon( 'play', 'icon-play' ); ?></button>
            <a class="notice-explore" href="<?php echo esc_url( khaiwal_chart_url() ); ?>"><?php echo khaiwal_text( 'EXPLORE', 'देखें' ); ?> <?php echo khaiwal_icon( 'arrow' ); ?></a>
        </div>
    </div>
</header>
