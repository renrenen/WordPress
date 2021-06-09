<?php
/*
 * @Author        : Qinver
 * @Url           : zibll.com
 * @Date          : 2020-09-29 13:18:50
 * @LastEditTime: 2021-05-01 14:33:33
 * @Email         : 770349780@qq.com
 * @Project       : Zibll子比主题
 * @Description   : 一款极其优雅的Wordpress主题|社交帐号登录
 * @Read me       : 感谢您使用子比主题，主题源码有详细的注释，支持二次开发。欢迎各位朋友与我相互交流。
 * @Remind        : 使用盗版主题会存在各种未知风险。支持正版，从我做起！
 */

/**
 * @description: 获取用户配置
 * @param {*}
 * @return {*}
 */
function get_oauth_config($type = 'qq')
{
    $defaults = array(
        'appid' => '',
        'appkey' => '',
        'backurl' => esc_url(home_url('/oauth/' . $type . '/callback')),
        'agent' => false,
        'appkrivatekey' => '',
    );
    return wp_parse_args((array) _pz('oauth_' . $type . '_option'), $defaults);
}

/**
 * 处理返回数据，更新用户资料
 */
function zib_oauth_update_user($args)
{
    /** 需求数据明细 */
    $defaults = array(
        'type'   => '',
        'openid' => '',
        'name' => '',
        'avatar' => '',
        'description' => '',
        'getUserInfo' => array(),
    );

    $args = wp_parse_args((array) $args, $defaults);

    // 初始化信息
    $openid_meta_key = 'oauth_' . $args['type'] . '_openid';
    $openid = $args['openid'];
    $return_data = array(
        'redirect_url' => '',
        'msg' => '',
        'error' => true,
    );;

    global $wpdb, $current_user;

    // 查询该openid是否已存在
    $user_exist = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key=%s AND meta_value=%s", $openid_meta_key, $openid));

    // 查询已登录用户
    $current_user_id = get_current_user_id();

    //如果已经登录，且该openid已经存在
    if ($current_user_id && isset($user_exist) && $current_user_id != $user_exist) {
        $return_data['msg'] = '绑定失败，可能之前已有其他账号绑定，请先登录并解绑。';
        return $return_data;
    }

    if (isset($user_exist) && (int) $user_exist > 0) {
        // 该开放平台账号已连接过WP系统，再次使用它直接登录
        $user_exist = (int) $user_exist;

        //登录
        $user = get_user_by('id', $user_exist);
        wp_set_current_user($user_exist);
        wp_set_auth_cookie($user_exist, true);
        do_action('wp_login', $user->user_login, $user);

        $return_data['redirect_url'] = get_author_posts_url($user_exist);  //重定向链接到用户中心
        $return_data['error'] = false;
    } elseif ($current_user_id) {
        // 已经登录，但openid未占用，则绑定，更新用户字段
        // 更新用户mate
        $args['user_id'] = $current_user_id;
        //绑定用户不更新以下数据
        $args['name'] = '';
        $args['description'] = '';

        zib_oauth_update_user_meta($args);
        // 准备返回数据
        $return_data['redirect_url'] = get_author_posts_url($current_user_id);  //重定向链接到用户中心
        $return_data['error'] = false;
    } else {
        // 既未登录且openid未占用，则新建用户并绑定
        $login_name = "user" . mt_rand(1000, 9999) . mt_rand(1000, 9999);
        $user_pass  = wp_create_nonce(rand(10, 1000));

        $user_id = wp_create_user($login_name, $user_pass);
        if (is_wp_error($user_id)) {
            //新建用户出错
            $return_data['msg'] = $user_id->get_error_message();
        } else {
            //新建用户成功
            update_user_meta($user_id, 'oauth_new', $args['type']);
            /**标记为系统新建用户 */
            //更新用户mate
            $args['user_id'] = $user_id;
            zib_oauth_update_user_meta($args);
            $return_data['redirect_url'] = get_author_posts_url($user_id);  //重定向链接到用户中心

            //登录
            $user = get_user_by('id', $user_id);
            wp_set_current_user($user_id, $user->user_login);
            wp_set_auth_cookie($user_id, true);
            do_action('wp_login', $user->user_login, $user);
            // 准备返回数据
            $return_data['redirect_url'] = get_author_posts_url($user_id);  //重定向链接到用户中心
            $return_data['error'] = false;
        }
    }
    return $return_data;
}

function zib_oauth_update_user_meta($args)
{
    /** 需求数据明细 */
    $defaults = array(
        'user_id' => '',
        /**用户id */
        'type'   => '',
        'openid' => '',
        'name' => '',
        'avatar' => '',
        'description' => '',
        'getUserInfo' => array(),
    );
    $args = wp_parse_args((array) $args, $defaults);

    update_user_meta($args['user_id'], 'oauth_' . $args['type'] . '_openid', $args['openid']);
    update_user_meta($args['user_id'], 'oauth_' . $args['type'] . '_getUserInfo', $args['getUserInfo']);
    $custom_avatar = get_user_meta($args['user_id'], 'custom_avatar', true);
    if (!empty($args['avatar']) && !$custom_avatar) {
        update_user_meta($args['user_id'], 'custom_avatar', $args['avatar']);
    }
    if (!empty($args['name'])) {
        $user_datas = array(
            'ID' => $args['user_id'],
            'display_name' => $args['name'],
            'nickname'     => $args['name'],
        );
        wp_update_user($user_datas);
    }
    if (!empty($args['description'])) {
        update_user_meta($args['user_id'], 'description', $args['description']);
    }
}

//  [zib_oauth_page_rewrite_rules OAuth登录处理页路由(/oauth)]
function zib_oauth_page_rewrite_rules($wp_rewrite)
{
    if ($ps = get_option('permalink_structure')) {
        $new_rules['oauth/([A-Za-z]+)$']          = 'index.php?oauth=$matches[1]';
        $new_rules['oauth/([A-Za-z]+)/callback$'] = 'index.php?oauth=$matches[1]&oauth_callback=1';
        $wp_rewrite->rules                        = $new_rules + $wp_rewrite->rules;
    }
}
add_action('generate_rewrite_rules', 'zib_oauth_page_rewrite_rules');

function zib_add_oauth_page_query_vars($public_query_vars)
{
    if (!is_admin()) {
        $public_query_vars[] = 'oauth';          // 添加参数白名单oauth，代表是各种OAuth登录处理页
        $public_query_vars[] = 'oauth_callback';
    }
    return $public_query_vars;
}
add_filter('query_vars', 'zib_add_oauth_page_query_vars');

function zib_oauth_page_template()
{
    $oauth          = strtolower(get_query_var('oauth')); //转换为小写
    $oauth_callback = get_query_var('oauth_callback');
    if ($oauth) {
        if (in_array($oauth, array('gitee', 'giteeagent', 'alipay', 'alipayagent', 'baidu', 'baiduagent', 'qq', 'qqagent', 'weixin', 'weixinagent', 'weibo', 'weiboagent', 'github', 'githubagent'))) :
            global $wp_query;
            $wp_query->is_home = false;
            $wp_query->is_page = true; //将该模板改为页面属性，而非首页
            $template          = $oauth_callback ? TEMPLATEPATH . '/oauth/' . $oauth . '/callback.php' : TEMPLATEPATH . '/oauth/' . $oauth . '/login.php';
            load_template($template);
            exit;
        else :
            // 非法路由处理
            unset($oauth);
            return;
        endif;
    }
}
add_action('template_redirect', 'zib_oauth_page_template', 5);
