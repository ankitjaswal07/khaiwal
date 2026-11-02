<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( post_password_required() ) { return; }
?>
<section id="comments" class="comments-area">
<?php if ( have_comments() ) : ?>
    <h2><?php printf( esc_html( _n( '%s comment', '%s comments', get_comments_number(), 'khaiwal' ) ), esc_html( number_format_i18n( get_comments_number() ) ) ); ?></h2>
    <ol class="comment-list"><?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 40 ) ); ?></ol>
    <?php the_comments_pagination(); ?>
<?php endif; ?>
<?php if ( comments_open() ) { comment_form(); } elseif ( get_comments_number() ) { echo '<p>' . esc_html__( 'Comments are closed.', 'khaiwal' ) . '</p>'; } ?>
</section>
