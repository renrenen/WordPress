<?php
/*
 * @Author        : Qinver
 * @Url           : zibll.com
 * @Date          : 2020-10-28 22:46:22
 * @LastEditTime: 2021-01-22 22:13:15
 * @Email         : 770349780@qq.com
 * @Project       : Zibll子比主题
 * @Description   : 一款极其优雅的Wordpress主题
 * @Read me       : 感谢您使用子比主题，主题源码有详细的注释，支持二次开发。欢迎各位朋友与我相互交流。
 * @Remind        : 使用盗版主题会存在各种未知风险。支持正版，从我做起！
 */


/**
 * @description: 作者页-文章子TAB内容
 * @param {*}
 * @return {*}
 */
function zib_ajax_tab_author_posts_by()
{
    echo '<body style="display:none;"><main>';
    zib_author_ajax_posts();
    echo '</main></body>';
    exit;
}
add_action('wp_ajax_user_posts_by', 'zib_ajax_tab_author_posts_by');
add_action('wp_ajax_nopriv_user_posts_by', 'zib_ajax_tab_author_posts_by');

/**
 * @description: 作者页-TAB内容：评论
 * @param {*}
 * @return {*}
 */
function zib_ajax_tab_author_comment($user_id = '')
{
    $paged = isset($_GET['paged']) ? $_GET['paged'] : 1;
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : $user_id;
    if (!$user_id) return;

    $ice_perpage = 10;
    $comments_status = get_current_user_id() == $user_id ? 'all' : 'approve';

    $args = array(
        'user_id' => $user_id,
        'number' => 10,
        'status' => $comments_status,
        'offset' => ($paged - 1) * $ice_perpage
    );
    $comments = get_comments($args);
    $count_all = get_user_comment_count($user_id, $comments_status, false);

    // 开始输出
    echo '<body style="display:none;"><main>';
    echo '<div class="ajaxpager" id="author-tab-comment">';
    $html = '';
    if (!$comments) {
        echo zib_get_ajax_null('暂无评论内容');
    } else {
        foreach ($comments as $comment) {
            echo '<div class="ajax-item posts-item no_margin">';
            zib_comments_author_list($comment);
            echo '</div>';
        }
    }

    //输出下一页按钮按钮
    $ajax_url = home_url(add_query_arg(null, null));
    echo zibpay_get_ajax_next_paging($count_all, $paged, $ice_perpage, $ajax_url);

    echo '</div>';
    echo '</main></body>';
    exit;
}
add_action('wp_ajax_author_comment', 'zib_ajax_tab_author_comment');
add_action('wp_ajax_nopriv_author_comment', 'zib_ajax_tab_author_comment');


/**
 * @description: 作者页-TAB内容：关注和粉丝
 * @param {*}
 * @return {*}
 */
function zib_ajax_tab_author_follow($user_id = '')
{
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : $user_id;

    if (!$user_id) return;
    $follow = get_user_meta($user_id, 'follow-user', true);
    $followed = get_user_meta($user_id, 'followed-user', true);
    $follow_count = '0';
    $followed_count = '0';
    if ($follow) {
        $follow = maybe_unserialize($follow);
        $follow_count = count($follow);
    }

    if ($followed) {
        $followed = maybe_unserialize($followed);
        $followed_count = count($followed);
    }

    //开始输出
    echo '<body style="display:none;"><main>';

    echo '<div class="ajaxpager" id="author-tab-follow">';
    echo '<div class="ajax-item text-center">';

    echo '<ul class="list-inline splitters relative">';
    echo '<li class="active"><a data-toggle="tab" class="muted-color" href="#ajaxauthor-tab-follow">关注 ' . $follow_count . '</a></li>';
    echo '<li><a data-toggle="tab" class="muted-color" href="#ajaxauthor-tab-followed">粉丝 ' . $followed_count . '</a></li>';
    echo '</ul>';

    echo '<div class="tab-content box-body">';
    echo '<div class="tab-pane fade in active" id="ajaxauthor-tab-follow">';
    if ($follow) {
        foreach ($follow as $user_id) {
            zib_author_card($user_id);
        }
    } else {
        echo zib_get_null('暂无关注用户', '40', 'null-love.svg');
    }
    echo '</div>';

    echo '<div class="tab-pane fade" id="ajaxauthor-tab-followed">';
    if ($followed) {
        foreach ($followed as $user_id) {
            zib_author_card($user_id);
        }
    } else {
        echo zib_get_null('暂无粉丝', '40', 'null-love.svg');
    }
    echo '</div>';
    echo '<div class="ajax-pag hide"><div class="next-page ajax-next"><a href="#"></a></div></div>';

    echo '</div>';
    echo '</div>';
    echo '</main></body>';
    exit;
}
add_action('wp_ajax_author_follow', 'zib_ajax_tab_author_follow');
add_action('wp_ajax_nopriv_author_follow', 'zib_ajax_tab_author_follow');



/**
 * @description: 作者页-TAB内容：用户资料
 * @param {*}
 * @return {*}
 */
function zib_ajax_tab_author_data()
{
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';

    if (!$user_id) return;

    echo '<body style="display:none;"><main>';
    echo '<div class="ajaxpager" id="author-tab-data">';

    echo '<div class="ajax-item box-body notop">';

    zib_author_con_datas($user_id);

    echo '</div>';
    echo '<div class="ajax-pag hide"><div class="next-page ajax-next"><a href="#"></a></div></div>';
    echo '</div>';

    echo '</main></body>';
    exit;
}
add_action('wp_ajax_author_data', 'zib_ajax_tab_author_data');
add_action('wp_ajax_nopriv_author_data', 'zib_ajax_tab_author_data');





/**
 * @description: 作者页-TAB内容：用户个人私有 个人中心
 * @param {*}
 * @return {*}
 */
function zib_ajax_tab_author_user()
{
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';

    if (!$user_id) return;

    echo '<body style="display:none;"><main>';
    echo '<div class="ajaxpager" id="author-tab-user">';

    echo '<div class="ajax-item box-body notop">';

    zib_author_con_user($user_id);

    echo '</div>';
    echo '<div class="ajax-pag hide"><div class="next-page ajax-next"><a href="#"></a></div></div>';
    echo '</div>';

    echo '</main></body>';
    exit;
}
add_action('wp_ajax_author_user', 'zib_ajax_tab_author_user');


/**
 * @description: AJAX获取用户文章的参数
 * @param {*}
 * @return {*}
 */
function zib_author_ajax_posts($type = '')
{

    //获取$_GET参数仪
    $paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : 1;
    $user_id = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0;
    $orderby = isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : '';
    $post_status = isset($_REQUEST['post_status']) ? $_REQUEST['post_status'] : '';
    $type = $type ? $type : (isset($_REQUEST['type']) ? $_REQUEST['type'] : '');

    if (!$user_id) return;

    //判断分页
    $count_all = (int) count_user_posts($user_id);
    $ice_perpage = (int) get_option('posts_per_page');

    $args = array(
        'no_margin' => true,
        'no_author' => false,
    );

    $post_args = array(
        'ignore_sticky_posts' => 1,
        'order' => 'DESC',
        'author' => $user_id,
        'paged' => $paged,
    );
    if ($orderby !== 'views') {
        $post_args['orderby'] = $orderby;
    } else {
        $post_args['orderby'] = 'meta_value_num';
        $post_args['meta_query'] = array(
            array(
                'key' => 'views',
                'order' => 'DESC'
            )
        );
    }

    $ajax_id = '';

    if ($orderby) {
        $ajax_id = 'user_posts_by_' . $orderby;
    }
    if ($post_status) {
        $ajax_id = 'user_posts_by_' . $post_status;
    }
    if ($type == 'favorite') {
        $ajax_id = 'author-tab-favorite';
    }
    $ajax_id = $ajax_id ? ' id="' . $ajax_id . '"' : '';
    //输出开始
    echo '<div class="posts-row ajaxpager"' . $ajax_id . '>';

    //收藏文章
    if ($type == 'favorite') {
        $favorite_ids = get_user_meta($user_id, 'favorite-posts', true);
        if ($favorite_ids) {
            $favorite_ids = (array)maybe_unserialize($favorite_ids);
        } else {
            echo zib_get_ajax_null('暂无收藏内容', 60, 'null-2.svg');
        }

        $post_args['orderby'] = 'data';
        $post_args['author'] = 0;
        $post_args['paged'] = 0;
        $post_args['post__in'] = $favorite_ids;
        $count_all = 0;
        $count_all = @count($favorite_ids);
        if ($count_all > $ice_perpage) {
            $favorite_ids = array_chunk($favorite_ids, $ice_perpage);
            $post__in = isset($favorite_ids[$paged - 1]) ? $favorite_ids[$paged - 1] : 0;
            $post_args['post__in'] = $post__in;
        }
        if (!isset($favorite_ids[$paged - 1])) {
            // 如果没有文章则结束
            echo '</div>';
            return;
        }
    }

    if ($post_status == 'draft') {
        $post_args['post_status'] = 'draft';
        $count_all = zib_get_user_post_count($user_id, 'draft');
    }
    if ($post_status == 'trash') {
        $post_args['post_status'] = 'trash';
        $count_all = zib_get_user_post_count($user_id, 'trash');
    }
    $the_query = new WP_Query($post_args);

    //主内容输出
    zib_posts_list($args, $the_query);

    //输出下一页按钮按钮
    $ajax_url = home_url(add_query_arg(null, null));
    echo zibpay_get_ajax_next_paging($count_all, $paged, $ice_perpage, $ajax_url);
    //输出结束
    echo '</div>';
}
