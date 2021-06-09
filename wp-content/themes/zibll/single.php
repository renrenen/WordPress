<?php
/*
 * @Author: Qinver
 * @Url: zibll.com
 * @Date: 2020-12-12 15:58:40
 * @LastEditTime: 2021-04-07 13:05:49
 */
wp_reset_postdata();
if (zib_is_docs_mode()) {
    get_template_part('template/single-dosc');
    return;
}
get_header();
?>
<?php if (function_exists('dynamic_sidebar')) {
    echo '<div class="container fluid-widget">';
    dynamic_sidebar('all_top_fluid');
    dynamic_sidebar('single_top_fluid');
    echo '</div>';
}
?>
<main role="main" class="container">
    <div class="content-wrap">
        <div class="content-layout">
            <?php
            //头部小工具
            if (function_exists('dynamic_sidebar')) {
                dynamic_sidebar('single_top_content');
            }
            //主内容
            zib_single();

            //评论模块
            comments_template('/template/comments.php', true);

            //底部小工具
            if (function_exists('dynamic_sidebar')) {
                dynamic_sidebar('single_bottom_content');
            }
            ?>
        </div>
    </div>
    <?php get_sidebar(); ?>
</main>
<?php if (function_exists('dynamic_sidebar')) {
    echo '<div class="container fluid-widget">';
    dynamic_sidebar('single_bottom_fluid');
    dynamic_sidebar('all_bottom_fluid');
    echo '</div>';
}
?>
<?php zib_rewards_modal(get_the_author_meta('ID')); ?>
<?php get_footer();
