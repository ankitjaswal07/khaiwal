<?php
/* Template Name: Info (About, Blog, FAQs, Policies) */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<main id="main">
<?php while ( have_posts() ) : the_post();
    list( $info_html, $info_toc ) = khaiwal_info_prepare( apply_filters( 'the_content', get_the_content() ) );
    $info_title = get_the_title();
?>
<section class="page-hero"><div class="container">
    <div class="breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo khaiwal_text( 'Home', 'होम' ); ?></a><span>/</span><?php echo esc_html( $info_title ); ?></div>
    <div class="eyebrow"><span class="eyebrow-dot"></span><?php echo khaiwal_text( 'EVERYTHING IN ONE PLACE', 'सब कुछ एक ही जगह' ); ?></div>
    <h1><?php echo esc_html( $info_title ); ?><em>.</em></h1>
    <p><?php echo khaiwal_text( 'Who we are, how this site works, answers to common questions, and our policies.', 'हम कौन हैं, यह साइट कैसे काम करती है, आम सवालों के जवाब और हमारी नीतियाँ।' ); ?></p>
</div></section>
<?php if ( $info_toc ) : ?>
<nav class="info-toc" aria-label="<?php esc_attr_e( 'On this page', 'khaiwal' ); ?>"><div class="container"><ul>
    <?php foreach ( $info_toc as $entry ) : ?><li><a href="#<?php echo esc_attr( $entry[0] ); ?>"><?php echo esc_html( $entry[1] ); ?></a></li><?php endforeach; ?>
</ul></div></nav>
<?php endif; ?>
<article class="section container prose info-content"><?php echo $info_html; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content output with ids added. ?><?php wp_link_pages(); ?></article>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>
