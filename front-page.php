<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
$games = khaiwal_games();
$now = khaiwal_now();
$dashboard = khaiwal_dashboard_data( $games, $now );
$home_games = $games;
$archive_date = khaiwal_catalog()['snapshot_date'];
?>
<main id="main">
    <section class="hero">
        <div class="hero-image" style="background-image:url('<?php echo esc_url( get_template_directory_uri() . '/assets/images/hero-gold.webp' ); ?>')" aria-hidden="true"></div>
        <div class="hero-overlay"></div>
        <div class="container hero-inner">
            <div class="hero-copy">
                <div class="eyebrow hero-eyebrow"><span class="eyebrow-dot"></span><?php echo khaiwal_text( 'THE RESULTS EDIT', 'रिज़ल्ट की दुनिया' ); ?><span class="eyebrow-line"></span><span>INDIA / IST</span></div>
                <h1 class="hero-heading"><span data-en="The numbers you" data-hi="आपके पसंदीदा">The numbers you</span><br><span data-en="care about," data-hi="रिज़ल्ट,">care about,</span><br><em data-en="all in one place." data-hi="अब एक ही जगह।">all in one place.</em></h1>
                <p class="hero-description"><?php echo khaiwal_text( 'A simpler way to stay in the know. Explore today’s results, game times in IST and monthly charts for all the games you follow.', 'जानकारी पाने का एक आसान तरीका। अपने सभी पसंदीदा गेम के आज के रिज़ल्ट, IST समय और मासिक चार्ट देखें।' ); ?></p>
                <div class="hero-actions"><a class="button button-yellow" href="#results"><?php echo khaiwal_text( 'Explore results', 'रिज़ल्ट देखें' ); ?> <span class="button-icon"><?php echo khaiwal_icon( 'arrow' ); ?></span></a><a class="button button-outline" href="<?php echo esc_url( khaiwal_chart_url() ); ?>"><?php echo khaiwal_text( 'View all charts', 'सभी चार्ट देखें' ); ?> <?php echo khaiwal_icon( 'arrow-up' ); ?></a></div>
                <div class="hero-proof"><span class="proof-icon"><?php echo khaiwal_icon( 'check' ); ?></span><span><?php echo khaiwal_text( count( $games ) . ' games', count( $games ) . ' गेम' ); ?></span><i></i><span><?php echo khaiwal_text( 'Two-month charts', 'दो महीने के चार्ट' ); ?></span><i></i><span><?php echo khaiwal_text( 'Easy to explore', 'देखने में आसान' ); ?></span></div>
            </div>
            <?php get_template_part( 'template-parts/hero', 'dashboard', array( 'dashboard' => $dashboard ) ); ?>
            <div class="hero-side-label" aria-hidden="true">THE DAILY RESULT JOURNAL <span><?php echo khaiwal_icon( 'spark' ); ?></span> 01 — <?php echo esc_html( count( $games ) ); ?></div>
        </div>
        <div class="hero-bottom-line"><div class="container"><span><?php echo khaiwal_text( 'A LITTLE MORE CLARITY, EVERY DAY', 'हर दिन, थोड़ी और स्पष्टता' ); ?></span><span><?php echo khaiwal_text( 'SCROLL TO EXPLORE ↓', 'नीचे स्क्रॉल करें ↓' ); ?></span></div></div>
    </section>

    <section id="results" class="section results-section">
        <div class="container">
            <div class="section-heading results-heading" data-reveal>
                <div><div class="eyebrow dark-eyebrow"><span class="section-number">01 /</span> <?php echo khaiwal_text( 'THE DAILY LINEUP', 'रोज़ाना रिज़ल्ट' ); ?></div><h2><?php echo khaiwal_text( 'Results at a glance', 'रिज़ल्ट एक नज़र में' ); ?><span class="heading-dot">.</span></h2><p><?php echo khaiwal_text( 'The games you follow, right where you need them.', 'आपके पसंदीदा गेम, अब आपकी नज़र में।' ); ?></p></div>
                <div class="date-card"><?php echo khaiwal_icon( 'calendar' ); ?><div><small><?php echo khaiwal_text( 'TODAY IN INDIA', 'आज की तारीख' ); ?></small><strong data-ist-date><?php echo esc_html( $now->format( 'd M Y' ) ); ?></strong></div><span class="date-live-dot"></span></div>
            </div>
            <div class="result-strip" data-reveal><div><span class="strip-badge"><span></span><?php echo khaiwal_text( 'THE LINEUP', 'रिज़ल्ट सूची' ); ?></span><strong><?php echo khaiwal_text( 'Today’s game results', 'आज के गेम रिज़ल्ट' ); ?></strong></div><a href="<?php echo esc_url( khaiwal_chart_url() ); ?>"><?php echo khaiwal_text( 'View all games', 'सभी गेम देखें' ); ?> <?php echo khaiwal_icon( 'arrow-up' ); ?></a></div>
            <div class="game-grid home-game-grid" data-reveal>
                <?php foreach ( $home_games as $game ) { get_template_part( 'template-parts/game', 'card', array( 'game' => $game ) ); } ?>
            </div>
            <?php if ( ! $games ) : ?><p class="empty-search"><?php echo khaiwal_text( 'No games have been published yet.', 'अभी कोई गेम प्रकाशित नहीं हुआ है।' ); ?></p><?php endif; ?>
            <?php $summary = khaiwal_home_summary( $games, $now ); ?>
            <section class="answer-block" aria-labelledby="results-summary-title" data-reveal>
                <h2 id="results-summary-title"><?php echo khaiwal_text( 'Today’s game results in short', 'आज के गेम रिज़ल्ट संक्षेप में' ); ?></h2>
                <p><?php echo khaiwal_text( $summary[0], $summary[1] ); ?></p>
                <p class="answer-meta"><?php echo khaiwal_text( 'All times are India Standard Time (IST) and informational. Results are published by editors; nothing is predicted.', 'सभी समय भारतीय मानक समय (IST) में हैं और केवल जानकारी के लिए हैं। रिज़ल्ट संपादकों द्वारा प्रकाशित होते हैं; कुछ भी अनुमानित नहीं है।' ); ?> <a href="<?php echo esc_url( khaiwal_api_url( 'results' ) ); ?>"><?php echo khaiwal_text( 'Today’s results as JSON', 'आज के रिज़ल्ट JSON में' ); ?></a> · <a href="<?php echo esc_url( khaiwal_llms_url() ); ?>">llms.txt</a></p>
            </section>
            <div class="results-footer"><p><?php echo khaiwal_icon( 'info' ); ?> <?php echo khaiwal_text( 'Archived source snapshot:', 'स्रोत का संग्रह:' ); ?> <strong><?php echo esc_html( date_i18n( 'j M Y', strtotime( $archive_date . ' 12:00:00' ) ) ); ?></strong> · <?php echo khaiwal_text( 'New results appear when published.', 'नए रिज़ल्ट प्रकाशित होने पर दिखाई देंगे।' ); ?></p><a class="text-link" href="<?php echo esc_url( khaiwal_chart_url() ); ?>"><?php echo khaiwal_text( 'See all ' . count( $games ) . ' games', 'सभी ' . count( $games ) . ' गेम देखें' ); ?> <span><?php echo khaiwal_icon( 'arrow' ); ?></span></a></div>
        </div>
    </section>

    <?php get_template_part( 'template-parts/full', 'chart', array( 'games' => $games, 'now' => $now ) ); ?>

    <section class="discover-section">
        <div class="container discover-inner" data-reveal>
            <div class="discover-copy"><div class="eyebrow"><span class="section-number">03 /</span> <?php echo khaiwal_text( 'A BETTER WAY TO BROWSE', 'देखने का बेहतर तरीका' ); ?></div><h2><?php echo khaiwal_text( 'A little less searching.', 'कम खोजें।' ); ?><br><em><?php echo khaiwal_text( 'A lot more knowing.', 'ज़्यादा जानें।' ); ?></em></h2><p><?php echo khaiwal_text( 'From today’s numbers to the previous month’s charts, everything is organised so you can find what matters.', 'आज के नंबर से पिछले महीने के चार्ट तक, सब कुछ आसानी से खोजने के लिए व्यवस्थित है।' ); ?></p><a class="button button-yellow" href="<?php echo esc_url( khaiwal_chart_url() ); ?>"><?php echo khaiwal_text( 'Explore the charts', 'चार्ट देखें' ); ?> <span class="button-icon"><?php echo khaiwal_icon( 'arrow' ); ?></span></a></div>
            <div class="discover-features">
                <div><span class="feature-icon"><?php echo khaiwal_icon( 'grid' ); ?></span><div><strong><?php echo khaiwal_text( 'All your games', 'आपके सभी गेम' ); ?></strong><p><?php echo khaiwal_text( 'A complete lineup in one place.', 'सभी गेम की सूची एक जगह।' ); ?></p></div><b>01</b></div>
                <div><span class="feature-icon"><?php echo khaiwal_icon( 'chart' ); ?></span><div><strong><?php echo khaiwal_text( 'Charts made simple', 'आसान चार्ट' ); ?></strong><p><?php echo khaiwal_text( 'Current and previous month at a glance.', 'मौजूदा और पिछले महीने के चार्ट।' ); ?></p></div><b>02</b></div>
                <div><span class="feature-icon"><?php echo khaiwal_icon( 'book' ); ?></span><div><strong><?php echo khaiwal_text( 'Stories worth reading', 'पढ़ने लायक कहानियाँ' ); ?></strong><p><?php echo khaiwal_text( 'Helpful guides from our journal.', 'हमारे जर्नल की उपयोगी गाइड।' ); ?></p></div><b>03</b></div>
            </div>
        </div>
    </section>

    <section class="section journal-section" id="journal">
        <div class="container">
            <div class="section-heading journal-heading" data-reveal><div><div class="eyebrow dark-eyebrow"><span class="section-number">04 /</span> <?php echo khaiwal_text( 'THE KHAIWAL JOURNAL', 'खाईवाल जर्नल' ); ?></div><h2><?php echo khaiwal_text( 'A little something to read', 'कुछ पढ़ने के लिए' ); ?><span class="heading-dot">.</span></h2><p><?php echo khaiwal_text( 'Fresh perspectives, helpful guides and more.', 'नई जानकारी, उपयोगी गाइड और बहुत कुछ।' ); ?></p></div><div class="slider-controls"><button type="button" class="slider-prev" aria-label="<?php esc_attr_e( 'Previous story', 'khaiwal' ); ?>" data-aria-en="Previous story" data-aria-hi="पिछली कहानी"><?php echo khaiwal_icon( 'left' ); ?></button><button type="button" class="slider-next" aria-label="<?php esc_attr_e( 'Next story', 'khaiwal' ); ?>" data-aria-en="Next story" data-aria-hi="अगली कहानी"><?php echo khaiwal_icon( 'arrow' ); ?></button></div></div>
            <?php $stories = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 6, 'ignore_sticky_posts' => true, 'no_found_rows' => true ) ); ?>
            <?php if ( $stories->have_posts() ) : ?>
            <div class="swiper journal-swiper" data-reveal><div class="swiper-wrapper">
                <?php while ( $stories->have_posts() ) : $stories->the_post(); $demo_image = get_post_meta( get_the_ID(), '_khaiwal_demo_image', true ); $title_hi = get_post_meta( get_the_ID(), '_khaiwal_demo_title_hi', true ); $excerpt_hi = get_post_meta( get_the_ID(), '_khaiwal_demo_excerpt_hi', true ); ?>
                <article class="swiper-slide story-card"><a class="story-image" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( 'Read ' . get_the_title() ); ?>" data-aria-en="<?php echo esc_attr( 'Read ' . get_the_title() ); ?>" data-aria-hi="<?php echo esc_attr( ( $title_hi ? $title_hi : get_the_title() ) . ' पढ़ें' ); ?>"><?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); } else { ?><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/' . ( $demo_image ? basename( $demo_image ) : 'story-charts.webp' ) ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy"><?php } ?><span class="story-arrow"><?php echo khaiwal_icon( 'arrow-up' ); ?></span></a><div class="story-meta"><span><?php echo khaiwal_text( 'THE JOURNAL', 'जर्नल' ); ?></span><i></i><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></time></div><h3><a href="<?php the_permalink(); ?>"><?php echo $title_hi ? khaiwal_text( get_the_title(), $title_hi ) : esc_html( get_the_title() ); ?></a></h3><p><?php $excerpt = get_the_excerpt(); echo $excerpt_hi ? khaiwal_text( $excerpt, $excerpt_hi ) : esc_html( $excerpt ); ?></p><a class="story-read" href="<?php the_permalink(); ?>"><?php echo khaiwal_text( 'READ STORY', 'पूरी कहानी' ); ?> <?php echo khaiwal_icon( 'arrow' ); ?></a></article>
                <?php endwhile; wp_reset_postdata(); ?>
            </div><div class="swiper-pagination journal-pagination"></div></div>
            <?php else : ?><p><?php echo khaiwal_text( 'New stories will appear here when published.', 'नई कहानियाँ प्रकाशित होने पर यहाँ दिखाई देंगी।' ); ?></p><?php endif; ?>
        </div>
    </section>
    <?php get_template_part( 'template-parts/faqs' ); ?>
    <?php if ( is_page() && have_posts() ) : while ( have_posts() ) : the_post(); if ( trim( get_the_content() ) ) : ?><section class="section editor-content"><div class="container prose"><?php the_content(); ?></div></section><?php endif; endwhile; endif; ?>
    <section class="bottom-cta"><div class="container bottom-cta-inner"><div><span class="eyebrow"><?php echo khaiwal_text( 'THE RESULTS ARE IN', 'रिज़ल्ट देखें' ); ?></span><h2><?php echo khaiwal_text( 'Ready to explore?', 'देखने के लिए तैयार हैं?' ); ?></h2><p><?php echo khaiwal_text( 'All your charts. One simple place.', 'आपके सभी चार्ट। एक आसान जगह।' ); ?></p></div><a class="button button-dark" href="<?php echo esc_url( khaiwal_chart_url() ); ?>"><?php echo khaiwal_text( 'Browse all charts', 'सभी चार्ट देखें' ); ?> <span class="button-icon"><?php echo khaiwal_icon( 'arrow' ); ?></span></a><span class="cta-star" aria-hidden="true"><?php echo khaiwal_icon( 'spark' ); ?></span></div></section>
</main>
<?php get_footer(); ?>
