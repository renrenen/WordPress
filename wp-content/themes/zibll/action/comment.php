<?php
/*
 * @Author        : Qinver
 * @Url           : zibll.com
 * @Date          : 2020-09-29 13:18:36
 * @LastEditTime: 2021-01-19 20:32:42
 * @Email         : 770349780@qq.com
 * @Project       : Zibll子比主题
 * @Description   : 一款极其优雅的Wordpress主题
 * @Read me       : 感谢您使用子比主题，主题源码有详细的注释，支持二次开发。欢迎各位朋友与我相互交流。
 * @Remind        : 使用盗版主题会存在各种未知风险。支持正版，从我做起！
 */

function zib_ajax_get_comment()
{
    if (empty($_POST['comment_id'])) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '数据传入出错')));
        exit();
    }
    $comment_id = absint($_POST['comment_id']);
    $comment = get_comment($comment_id);
    if (!$comment) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '查找数据出错')));
        exit();
    }
    $current_user_id = get_current_user_id();
    if (!_pz('user_edit_comment', 'true') || !$current_user_id  || ($comment->user_id != $current_user_id && !is_super_admin())) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '编辑权限不足')));
        exit();
    }
    if ('trash' == $comment->comment_approved) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '评论已删除')));
        exit();
    }
    $comment->msg = '内容已获取，请编辑评论';
    echo (json_encode($comment));
    exit();
}
add_action('wp_ajax_get_comment', 'zib_ajax_get_comment');
add_action('wp_ajax_nopriv_get_comment', 'zib_ajax_get_comment');

function zib_ajax_trash_comment()
{
    if (empty($_POST['comment_id'])) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '数据传入出错')));
        exit();
    }
    $comment_id = absint($_POST['comment_id']);
    $comment = get_comment($comment_id);
    if (!$comment) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '查找数据出错')));
        exit();
    }

    $current_user_id = get_current_user_id();
    if (!_pz('user_edit_comment', 'true') || !$current_user_id  || ($comment->user_id != $current_user_id && !is_super_admin())) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '编辑权限不足')));
        exit();
    }
    if ('trash' == $comment->comment_approved) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '评论已删除')));
        exit();
    }
    if (wp_trash_comment($comment)) {
        echo (json_encode(array('msg' => '评论已删除')));
    } else {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '操作失败')));
    }
    exit;
}
add_action('wp_ajax_trash_comment', 'zib_ajax_trash_comment');
add_action('wp_ajax_nopriv_trash_comment', 'zib_ajax_trash_comment');

function zib_ajax_submit_comment()
{
    $edit_id  = !empty($_POST['edit_comment_ID']) ? absint($_POST['edit_comment_ID']) : false;
    if (empty($_POST['comment']) || _new_strlen($_POST['comment']) < 2) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '请输入内容')));
        exit();
    }
    $current = get_current_user_id();
    if ($edit_id) {
        $comment = get_comment($edit_id);
        if (!$comment) {
            echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '查找数据出错')));
            exit();
        }
        if (!_pz('user_edit_comment', 'true') || !$current || ($comment->user_id != $current && !is_super_admin())) {
            echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '编辑权限不足')));
            exit();
        }
        if ($comment->comment_content == $_POST['comment']) {
            echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '内容未修改')));
            exit;
        }
        $update_comment = wp_update_comment([
            'comment_ID' => $edit_id,
            'comment_content' => $_POST['comment'],
        ]);

        if (!$update_comment) {
            echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '评论修改失败')));
            exit;
        }

        echo (json_encode(array('error' => 0, 'html' => zib_comment_filters($_POST['comment']), 'msg' => '评论已修改')));
        exit;
    }
    $comment = wp_handle_comment_submission(wp_unslash($_POST));
    if (is_wp_error($comment)) {
        $data = $comment->get_error_data();
        if (!empty($data)) {
            echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => $comment->get_error_message())));
            exit;
        } else {
            echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '评论提交失败')));
            exit;
        }
    }
    if (!$current) {
        do_action('set_comment_cookies', $comment, wp_get_current_user());
    }

    echo (json_encode(array('error' => 0, 'html' => zib_get_comments_list($comment, false, false), 'msg' => '评论已提交')));
    exit;
}
add_action('wp_ajax_submit_comment', 'zib_ajax_submit_comment');
add_action('wp_ajax_nopriv_submit_comment', 'zib_ajax_submit_comment');
