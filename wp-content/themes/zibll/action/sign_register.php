<?php
/*
 * @Author        : Qinver
 * @Url           : zibll.com
 * @Date          : 2020-09-29 23:52:09
 * @LastEditTime: 2021-01-25 17:57:10
 * @Email         : 770349780@qq.com
 * @Project       : Zibll子比主题
 * @Description   : 一款极其优雅的Wordpress主题
 * @Read me       : 感谢您使用子比主题，主题源码有详细的注释，支持二次开发。欢迎各位朋友与我相互交流。
 * @Remind        : 使用盗版主题会存在各种未知风险。支持正版，从我做起！
 */


//免密登录
function zib_ajax_user_signin_nopas()
{
    if (is_user_logged_in()) {
        echo (json_encode(array('error' => 1, 'msg' => '你已经登录，请刷新页面')));
        exit;
    }

    $nopas_s = _pz('user_signin_nopas_s');
    if (!$nopas_s) {
        echo (json_encode(array('error' => 1, 'msg' => '暂未开启此功能，请使用帐号密码登录')));
        exit;
    }

    $nopas_type = _pz('user_signin_nopas_type');

    $captcha = zib_ajax_captcha_form_judgment($nopas_type);
    $captcha_type = $captcha['type'];
    $captcha_val = $captcha['to'];

    //执行人机验证
    zib_ajax_verification_form_judgment('img_yz_signin_captcha');

    //验证验证码
    zib_ajax_is_captcha($nopas_type);

    if ($captcha_type == 'email') {
        $user = get_user_by('email', $captcha_val);
        if (!$user) {
            echo (json_encode(array('error' => 1, 'msg' => '未找到此邮箱注册账户')));
            exit();
        }
    } elseif ($captcha_type == 'phone') {
        $user = zib_get_user_by('phone', $captcha_val);
        if (!$user) {
            echo (json_encode(array('error' => 1, 'msg' => '未找到此手机号注册账户')));
            exit();
        }
    }
    if (!$user) {
        echo (json_encode(array('error' => 1, 'msg' => '未找到您的用户信息')));
        exit();
    }

    //登录
    $remember = !empty($_POST['remember']) ? true : false;

    wp_set_current_user($user->ID, $user->user_login);
    wp_set_auth_cookie($user->ID, $remember);
    do_action('wp_login', $user->user_login, $user);

    $result = array('error' => 0, 'reload' => 1, 'msg' => '成功登录，页面跳转中');
    if (!empty($_REQUEST['redirect_to'])) {
        $result['goto'] = $_REQUEST['redirect_to'];
    }
    echo (json_encode($result));
    exit();
}
add_action('wp_ajax_user_signin_nopas', 'zib_ajax_user_signin_nopas');
add_action('wp_ajax_nopriv_user_signin_nopas', 'zib_ajax_user_signin_nopas');

/**用户注册 */
function zib_ajax_user_signup()
{
    if (is_user_logged_in()) {
        echo (json_encode(array('error' => 1, 'msg' => '你已经登录，请刷新页面')));
        exit;
    }

    //用户名判断
    zib_ajax_username_judgment('name');

    if (strlen($_POST['password2']) < 6) {
        echo (json_encode(array('error' => 1, 'msg' => '密码太短,至少6位')));
        exit();
    }

    $no_repas = _pz('user_signup_no_repas');
    $captch = _pz('user_signup_captch');

    if ((!$no_repas || !$captch) && $_POST['password2'] !== $_POST['repassword']) {
        echo (json_encode(array('error' => 1, 'msg' => '两次密码输入不一致')));
        exit();
    }

    //执行人机验证
    zib_ajax_verification_form_judgment('img_yz_signup_captcha');
    $captcha_type = '';
    $captcha_val = '';
    if ($captch) {
        //执行验证参数判断
        $_pz_captch_type = _pz('captch_type', 'email');

        $captcha = zib_ajax_captcha_form_judgment($_pz_captch_type);
        $captcha_type = $captcha['type'];
        $captcha_val = $captcha['to'];

        //执行验证码：验证判断
        zib_ajax_is_captcha($_pz_captch_type);
    }
    //新建用户
    $email = $captcha_type == 'email' ? $captcha_val : (!empty($_POST['email']) ? $_POST['email'] : '');

    $status = wp_create_user($_POST['name'], $_POST['password2'], $email);

    if (is_wp_error($status)) {
        if (!empty($err['existing_user_login'])) {
            echo (json_encode(array('error' => 1, 'wp_error' => json_encode($status), 'msg' => '用户名已存在，换一个试试')));
            exit();
        } else if (!empty($err['existing_user_email'])) {
            echo (json_encode(array('error' => 1, 'wp_error' => json_encode($status), 'msg' => '邮箱已存在，您可以尝试找回密码')));
            exit();
        }
        echo (json_encode(array('error' => 1, 'wp_error' => json_encode($status), 'msg' =>  $status->get_error_message())));
        exit();
    } elseif (!$status) {
        echo (json_encode(array('error' => 1, 'msg' => '系统出错，请稍候再试')));
        exit();
    }

    //登录
    $user = get_user_by('id', $status);
    wp_set_current_user($status, $user->user_login);
    wp_set_auth_cookie($status, true);
    do_action('wp_login', $user->user_login, $user);

    //保存用户手机号
    if ($captcha_type == 'phone') {
        update_user_meta($status, 'phone_number', $captcha_val);
    }

    $result = array('error' => 0, 'reload' => 1, 'msg' => '注册成功，欢迎您：' . $_POST['name']);
    //重定向返回页面
    if (!empty($_REQUEST['redirect_to'])) {
        $result['goto'] = $_REQUEST['redirect_to'];
    }
    echo (json_encode($result));
    exit();
}
add_action('wp_ajax_user_signup', 'zib_ajax_user_signup');
add_action('wp_ajax_nopriv_user_signup', 'zib_ajax_user_signup');


/**用户登录 */
function zib_ajax_user_signin()
{

    if (is_user_logged_in()) {
        echo (json_encode(array('error' => 1, 'msg' => '你已经登录，请刷新页面')));
        exit;
    }
    //用户名判断
    zib_ajax_username_judgment('username', true);

    if (empty($_POST['password'])) {
        echo (json_encode(array('error' => 1, 'msg' => '请输入密码')));
        exit();
    }

    //执行人机验证
    zib_ajax_verification_form_judgment('img_yz_signin');

    if (filter_var($_POST['username'], FILTER_VALIDATE_EMAIL)) {
        $user_data = get_user_by('email', $_POST['username']);
        if (empty($user_data)) {
            echo (json_encode(array('error' => 1, 'msg' => '未找到此邮箱注册账户')));
            exit();
        }
    } elseif (_pz('user_signin_phone_s') && ZibSMS::is_phonenumber($_POST['username'])) {
        $user_data = zib_get_user_by('phone', $_POST['username']);
        if (empty($user_data)) {
            echo (json_encode(array('error' => 1, 'msg' => '未找到此手机号注册账户')));
            exit();
        }
    } else {
        $user_data = get_user_by('login', $_POST['username']);
        if (empty($user_data)) {
            echo (json_encode(array('error' => 1, 'msg' => '未找到此用户名注册账户')));
            exit();
        }
    }

    $username = $user_data->user_login;

    $remember = !empty($_POST['remember']) ? true : false;
    $login_data = array(
        'user_login' => $username,
        'user_password' => $_POST['password'],
        'remember' => $remember
    );

    $user_verify = wp_signon($login_data);

    if (is_wp_error($user_verify)) {
        echo (json_encode(array('error' => 1, 'msg' => '帐号或密码错误')));
        exit();
    }

    $result = array('error' => 0, 'reload' => 1, 'msg' => '成功登录，页面跳转中');
    if (!empty($_REQUEST['redirect_to'])) {
        $result['goto'] = $_REQUEST['redirect_to'];
    }
    echo (json_encode($result));
    exit();
}
add_action('wp_ajax_user_signin', 'zib_ajax_user_signin');
add_action('wp_ajax_nopriv_user_signin', 'zib_ajax_user_signin');


///AJAX注册发送验证码
function zib_ajax_signup_captcha()
{

    //判断不能重复
    $captcha = zib_ajax_captcha_form_judgment(_pz('captch_type', 'email'));

    $captcha_type = $captcha['type'];
    $to = $captcha['to'];
    //执行人机验证
    zib_ajax_verification_form_judgment('img_yz_signup_captcha');

    if ($captcha_type == 'email' && email_exists($to)) {
        echo (json_encode(array('error' => 1, 'msg' => '该邮箱已注册，请登录')));
        exit();
    }
    if ($captcha_type == 'phone' && zib_get_user_by('phone', $to)) {
        echo (json_encode(array('error' => 1, 'msg' => '该手机号已注册，请登录')));
        exit();
    }

    zib_ajax_send_captcha($captcha_type, $to, false);
    exit();
}
add_action('wp_ajax_signup_captcha', 'zib_ajax_signup_captcha');
add_action('wp_ajax_nopriv_signup_captcha', 'zib_ajax_signup_captcha');


/**前端登录AJAX发送验证码 */
function zib_ajax_signin_captcha()
{

    //判断不能重复
    $captcha = zib_ajax_captcha_form_judgment(_pz('user_signin_nopas_type', 'email'));
    $captcha_type = $captcha['type'];
    $to = $captcha['to'];
    //执行人机验证
    zib_ajax_verification_form_judgment('img_yz_signin_captcha');

    if ($captcha_type == 'email' && !email_exists($to)) {
        echo (json_encode(array('error' => 1, 'msg' => '该邮箱尚未注册或绑定帐号')));
        exit();
    }
    if ($captcha_type == 'phone' && !zib_get_user_by('phone', $to)) {
        echo (json_encode(array('error' => 1, 'msg' => '该手机号尚未注册或绑定帐号')));
        exit();
    }

    zib_ajax_send_captcha($captcha_type, $to, false);
    exit();
}
add_action('wp_ajax_signin_captcha', 'zib_ajax_signin_captcha');
add_action('wp_ajax_nopriv_signin_captcha', 'zib_ajax_signin_captcha');


//找回密码发送验证码
function zib_ajax_resetpassword_captcha($captcha_type = '', $send = '')
{

    $captcha = zib_ajax_captcha_form_judgment(_pz('user_repas_captch_type', 'email'));
    $captcha_type = $captcha['type'];
    $to = $captcha['to'];

    //人机验证
    zib_ajax_verification_form_judgment('img_yz_resetpassword_captcha');
    if ($captcha_type == 'email') {
        $user_id = email_exists($to);
        if (!$user_id) {
            echo (json_encode(array('error' => 1, 'msg' => '该邮箱尚未注册或绑定帐号')));
            exit();
        }
    }
    if ($captcha_type == 'phone') {
        $user_data = zib_get_user_by('phone', $to);
        if (!$user_data) {
            echo (json_encode(array('error' => 1, 'msg' => '该手机号尚未注册或绑定帐号')));
            exit();
        }
        $user_id = $user_data->ID;
    }
    $allow = apply_filters('allow_password_reset', true, $user_data->ID);
    if (!$allow) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '此用户不允许重置密码')));
        exit();
    }
    zib_ajax_send_captcha($captcha_type, $to, false);
    exit();
}
add_action('wp_ajax_resetpassword_captcha', 'zib_ajax_resetpassword_captcha');
add_action('wp_ajax_nopriv_resetpassword_captcha', 'zib_ajax_resetpassword_captcha');


//找回密码
function zib_ajax_reset_password($captcha_type = '', $send = '')
{

    if (empty($_POST['repassword']) || empty($_POST['password2'])) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '密码不能为空')));
        exit();
    }

    if (strlen($_POST['repassword']) < 6) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '密码至少6位')));
        exit();
    }

    if ($_POST['repassword'] !== $_POST['password2']) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '两次密码输入不一致')));
        exit();
    }

    $nopas_type = _pz('user_repas_captch_type', 'email');

    //获取输入内容
    $captcha = zib_ajax_captcha_form_judgment($nopas_type);
    $captcha_type = $captcha['type'];
    $captcha_val = $captcha['to'];

    //人机验证
    zib_ajax_verification_form_judgment('img_yz_resetpassword_captcha');

    //验证验证码
    zib_ajax_is_captcha($nopas_type);

    if ($captcha_type == 'email') {
        $user_data = get_user_by('email', $captcha_val);
    }
    if ($captcha_type == 'phone') {
        $user_data = zib_get_user_by('phone', $captcha_val);
    }

    if (!$user_data) {
        echo (json_encode(array('error' => 1, 'msg' => '未查询到您的帐号信息')));
        exit();
    }
    //修改密码
    $status = wp_update_user(
        array(
            'ID' => $user_data->ID,
            'user_pass' => $_POST['password2']
        )
    );

    if (is_wp_error($status)) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', $status->get_error_message())));
        exit();
    }

    if (!is_user_logged_in()) {
        wp_set_current_user($user_data->ID, $user_data->user_login);
        wp_set_auth_cookie($user_data->ID, true);
        do_action('wp_login', $user_data->user_login, $user_data);
    }

    echo (json_encode(array('error' => 0, 'reload' => 1, 'goto' => esc_url(home_url()), 'msg' => '密码重设成功！请牢记新密码')));
    exit;
}
add_action('wp_ajax_reset_password', 'zib_ajax_reset_password');
add_action('wp_ajax_nopriv_reset_password', 'zib_ajax_reset_password');
