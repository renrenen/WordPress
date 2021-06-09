<?php
/*
 * @Author        : Qinver
 * @Url           : zibll.com
 * @Date          : 2020-09-29 13:18:36
 * @LastEditTime: 2020-12-20 19:21:39
 * @Email         : 770349780@qq.com
 * @Project       : Zibll子比主题
 * @Description   : 一款极其优雅的Wordpress主题
 * @Read me       : 感谢您使用子比主题，主题源码有详细的注释，支持二次开发。欢迎各位朋友与我相互交流。
 * @Remind        : 使用盗版主题会存在各种未知风险。支持正版，从我做起！
 */

function zib_ajax_new_posts()
{

    $cuid = get_current_user_id();

    if (!_pz('post_article_s')) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '不允许发布文章')));
        exit();
    } elseif (_pz('post_article_limit', 'logged_in') == 'logged_in') {
        if (!$cuid) {
            echo (json_encode(array('error' => 1, 'ys' => 'warning', 'singin' => true, 'msg' => '请先登录！')));
            exit;
        }
    }

    $title   =  !empty($_POST['post_title']) ? $_POST['post_title'] : false;
    $content =  !empty($_POST['post_content']) ? $_POST['post_content'] : false;
    $cat = !empty($_POST['category']) ? $_POST['category'] : false;
    $action = !empty($_POST['action']) ? $_POST['action'] : false;
    $draft_id = '';
    if ($cuid) {
        $draft_id = get_user_meta($cuid, 'posts_draft', true);
    }

    $posts_id = !empty($_POST['posts_id']) ? $_POST['posts_id'] : ($draft_id ? $draft_id : 0);

    if (empty($title)) {
        echo (json_encode(array('error' => 1, 'ys' => 'warning', 'msg' => '请填写文章标题')));
        exit();
    }
    if (empty($content)) {
        echo (json_encode(array('error' => 1, 'ys' => 'warning', 'msg' => '还未填写任何内容')));
        exit();
    }

    if ($action == 'posts_save') {
        if (_new_strlen($title) > 30) {
            echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '标题太长了，不能超过30个字')));
            exit();
        }
        if (_new_strlen($title) < 5) {
            echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '标题太短！')));
            exit();
        }
        if (_new_strlen($content) < 10) {
            echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '文章内容过少')));
            exit();
        }
        if (empty($cat)) {
            echo (json_encode(array('error' => 1, 'ys' => 'warning', 'msg' => '请选择文章分类')));
            exit();
        }
    }

    if (!$cuid && _pz('post_article_limit') == 'all') {
        if (_new_strlen($content) < 10) {
            echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '文章内容过少')));
            exit();
        }
        if (empty($_POST['user_name'])) {
            echo (json_encode(array('error' => 1, 'msg' => '请输入昵称')));
            exit();
        }
        $cuid = _pz('post_article_limit', 1);
        $lx = !empty($_POST['contact_details']) ? ',联系：' . $_POST['contact_details'] : '';
        $title = $title . '[投稿-姓名：' . $_POST['user_name'] . $lx . ']';
    }

    $cat = array();
    $cat[] = !empty($_POST['category']) ? $_POST['category'] : false;
    $tags = preg_split("/,|，|\s|\n/", $_POST['tags']);

    $postarr = array(
        'post_title'   => $title,
        'post_author'  => $cuid,
        'post_status'   => 'draft',
        'ID'            => $posts_id,
        'post_content' => $content,
        'post_category' => $cat,
        'tags_input'    => $tags,
        'comment_status' => 'open',
    );
    if (_pz('post_article_review_s') && is_user_logged_in()) {
        $postarr['post_status'] = 'publish';
    }
    if ($action == 'posts.draft') {
        $postarr['post_status'] = 'draft';
    }
    $in_id = wp_insert_post($postarr, 1);
    if (is_wp_error($in_id)) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => $in_id->get_error_message())));
        exit();
    }
    if (!$in_id) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '投稿失败，请稍后再试')));
        exit();
    }
    $url = '';
    if (is_user_logged_in() && current_user_can('edit_post', $in_id)) {
        $url = get_permalink($in_id);
    }

    if ($action == 'posts_draft') {
        update_user_meta($cuid, 'posts_draft', $in_id);
        echo (json_encode(array('error' => 0, 'posts_id' => $in_id, 'url' => $url, 'tags' => $tags, 'time' => current_time('mysql'), 'posts_url' => get_permalink($in_id),   'msg' => '草稿保存成功')));
        exit();
    }
    if (get_current_user_id()) {
        $open_url = get_author_posts_url(get_current_user_id());
    } else {
        $open_url = home_url();
    }
    if ($action == 'posts_save') {
        update_user_meta($cuid, 'posts_draft', false);
        if (_pz('post_article_review_s')) {
            echo (json_encode(array('error' => 0, 'posts_id' => $in_id, 'url' => $url,  'ok' => 1, 'open_url' => $open_url, 'msg' => '文章已发布')));
        } else {
            echo (json_encode(array('error' => 0, 'posts_id' => $in_id, 'url' => $url,  'ok' => 1, 'open_url' => $open_url, 'msg' => '投稿成功，等待审核中...')));
        }
        exit();
    }

    echo (json_encode($_POST));
    exit;
}
add_action('wp_ajax_posts_save', 'zib_ajax_new_posts');
add_action('wp_ajax_nopriv_posts_save', 'zib_ajax_new_posts');
add_action('wp_ajax_posts_draft', 'zib_ajax_new_posts');
add_action('wp_ajax_nopriv_posts_draft', 'zib_ajax_new_posts');
