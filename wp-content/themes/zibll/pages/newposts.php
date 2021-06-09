<?php

/**
 * Template name: Zibll-写文章、投稿页面
 * Description:   用户前台发布文章的页面模板
 */

get_header();
$btn_txet = _pz('post_article_review_s') ? '发布' : '审核';
if (!is_user_logged_in()) {
    $btn_txet = '审核';
}
?>
<?php if (!_pz('post_article_s')) {
    get_template_part('template/content-404');
    get_footer();
    exit();
}
?>
<main role="main" class="container">
    <form>
        <?php
        $cuid = get_current_user_id();
        $draft_id = get_user_meta($cuid, 'posts_draft', true);
        $post_title = '';
        $post_content = '';
        $post_cat = '';
        $post_tags = '';
        $post_tag = '';
        $post_uptime = '';
        $view_btn = '';
        if ($draft_id) {
            $args = array(
                'include'          => array($draft_id),
                'post_status'     => 'draft'
            );
            $draft = get_posts($args);
            if (!empty($draft[0])) {
                $post_title = $draft[0]->post_title;
                $post_content = $draft[0]->post_content;
                $post_uptime = '<span class="badg">最后保存：' . $draft[0]->post_modified . '</span>';
                $post_cat = get_the_category($draft_id)[0]->term_id;
                $post_tags = get_the_tags($draft_id);

                if ($post_tags) {
                    $post_tags = array_column((array)$post_tags, 'name');
                    $post_tags = implode(', ', $post_tags);
                }
                $view_btn = (is_user_logged_in() && current_user_can('edit_post', $draft_id)) ? '<a target="_blank" href="' . get_permalink($draft_id) . '" class="but c-blue mr10"><i class="fa fa-file-text-o" aria-hidden="true"></i> 预览文章</a>' : '';
            } else {
                $draft_id = false;
                update_user_meta($cuid, 'posts_draft', false);
            }
        }
        ?>
        <div class="content-wrap newposts-wrap">
            <div class="content-layout">

                <div class="main-bg theme-box radius8 box-body main-shadow">
                    <div class="relative theme-box newposts-title">
                        <input type="text" class="line-form-input input-lg new-title" name="post_title" tabindex="1" value="<?php echo esc_attr($post_title) ?>" placeholder="请输入文章标题">
                        <i class="line-form-line"></i>
                    </div>
                    <?php
                    //  echo '<pre>'.json_encode($draft).'</pre>';
                    $content = $post_content;
                    $editor_id = 'post_content';
                    $settings = array(
                        'textarea_rows' => 20,
                        'editor_height' => 565,
                        'media_buttons' => false,
                        'default_editor' => 'tinymce',
                        'quicktags' => false,
                        'editor_css'    => '',
                        'tinymce'       => array(
                            'content_css' => ZIB_STYLESHEET_DIRECTORY_URI . '/css/tinymce.min.css',
                        ),
                        'teeny' => false,
                    );
                    if (_pz('post_article_img_s')) {
                        $settings['media_buttons'] = true;
                    }
                    if (!is_user_logged_in() && (_pz('post_article_limit', 'logged_in') == 'logged_in')) {
                        echo '<div class="text-center newposts-sign">';
                        if (zib_is_close_sign()) {
                            echo '<p class="muted-color box-body">注册登录已关闭，暂时无法发布文章</p>';
                        } else {
                            echo '<p class="muted-color box-body em12">请先登录！</p>';
                            echo '<p>';
                            echo '<a href="javascript:;" class="signin-loader but jb-blue padding-lg"><i class="fa fa-fw fa-sign-in mr10" aria-hidden="true"></i>登录</a>';
                            echo '<a href="javascript:;" class="signup-loader ml10 but jb-yellow padding-lg"><i data-class="icon mr10" data-viewbox="0 0 1024 1024" data-svg="signup" aria-hidden="true"></i>注册</a>';
                            echo '</p>';
                            zib_social_login();
                        }
                        echo '</div>';
                    } else {
                        wp_editor($content, $editor_id, $settings);
                    }

                    ?>
                    <?php echo '<div class="em09 mt10"><span class="view-btn">' . $view_btn . '</span><span class="modified-time">' . $post_uptime . '</span></div>'; ?>
                </div>
            </div>
        </div>

        <div class="sidebar show-sidebar">

            <?php if (is_user_logged_in()) {
            ?>
                <div class="main-bg theme-box radius8 main-shadow relative">

                    <?php $args = array(
                        'user_id' => $cuid,
                        'show_posts' => false,
                        'show_img_bg' => true,
                    );
                    zib_posts_avatar_box($args); ?>

                </div>
            <?php } ?>
            <div class="theme-box">
                <div class="main-bg theme-box radius8 main-shadow relative">
                    <div class="box-header">
                        <div class="title-theme">文章分类</div>
                    </div>

                    <div class="box-body">
                        <p class="muted-3-color em09">请选择文章分类</p>
                        <div class="form-select">
                            <select class="form-control" name="category" tabindex="5">
                                <?php
                                $cat_ids = _pz('post_article_cat', array());

                                $cats = get_categories(array(
                                    'orderby' => 'include',
                                    'include' => $cat_ids,
                                    'hide_empty' => false
                                ));

                                if ($cats) {
                                    foreach ($cats as $cat) {
                                        echo '<option value="' . $cat->term_id . '" ' . selected($cat->term_id, $post_cat, false) . '>' . $cat->name . '</option>';
                                    }
                                } else {
                                    echo '<option value="1" selected="selected">' . get_category(1)->name . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                    </div>
                    <div class="box-header">
                        <div class="title-theme">文章标签</div>
                    </div>
                    <div class="box-body">
                        <p class="muted-3-color em09">填写文章的标签，每个标签用逗号隔开</p>
                        <textarea class="form-control" rows="3" name="tags" placeholder="输入文章标签" tabindex="6"><?php echo $post_tags; ?></textarea>
                    </div>
                </div>
            </div>
            <div class="zib-widget">
                <div class="box-body">
                    <div class="text-center">
                        <p class="separator muted-3-color theme-box">Are you ready</p>
                        <?php
                        if ($draft_id) {
                            echo '<input type="hidden" name="posts_id" value="' . $draft_id . '">';
                        }
                        if (is_user_logged_in()) {
                            echo '<p class="em09 muted-3-color theme-box">如果您的文章还未完全写作完成，请先保存草稿，文章提交' . $btn_txet . '之后不可再修改！</p>';
                            echo '<botton type="button" action="posts_draft" name="submit" class="but jb-green mr6 new-posts-submit"><i class="fa fa-fw fa-dot-circle-o"></i>保存草稿</botton>';
                        } elseif (_pz('post_article_limit', 'logged_in') == 'logged_in') {
                            if (zib_is_close_sign()) {
                                echo '<p class="em09 muted-3-color theme-box">注册登录已关闭，暂时无法发布文章</p>';
                            } else {
                                echo '<p class="em09 muted-3-color theme-box">请登陆后发布文章</p>';
                            }
                        } else {
                            echo '<p class="em09 muted-3-color theme-box">您当前未登录，不能保存草稿，文章提交' . $btn_txet . '之后不可再修改！</p>';
                        }
                        if (_pz('post_article_limit', 'logged_in') != 'logged_in' || is_user_logged_in()) {
                            echo '<botton type="button" action="posts_save" name="submit" class="ml10 but jb-blue ml6 new-posts-submit"><i class="fa fa-fw fa-check-square-o"></i>提交' . $btn_txet . '</botton>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </form>
</main>
<?php get_footer();
