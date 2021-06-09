<?php
/*
 * @Author        : Qinver
 * @Url           : zibll.com
 * @Date          : 2020-09-29 15:18:45
 * @LastEditTime: 2021-01-25 18:03:22
 * @Email         : 770349780@qq.com
 * @Project       : Zibll子比主题
 * @Description   : 一款极其优雅的Wordpress主题
 * @Read me       : 感谢您使用子比主题，主题源码有详细的注释，支持二次开发。欢迎各位朋友与我相互交流。
 * @Remind        : 使用盗版主题会存在各种未知风险。支持正版，从我做起！
 */
//设置收款码
function zib_ajax_user_set_rewards()
{

    $cuid = get_current_user_id();
    if (!$cuid) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '权限不足')));
        exit;
    }

    if (!wp_verify_nonce($_POST['upload_rewards_nonce'], 'upload_rewards')) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '安全验证失败，请稍候再试')));
        exit();
    }

    $weixin_lao_id = get_user_meta($cuid, 'rewards_wechat_image_id', true);
    $alipay_lao_id = get_user_meta($cuid, 'rewards_alipay_image_id', true);

    if (empty($_FILES['weixin']) && empty($_FILES['alipay']) && !$weixin_lao_id && !$alipay_lao_id) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '请选择收款二维码')));
        exit();
    }

    if (!empty($_FILES['weixin'])) {
        $weixin_img_id = zib_php_upload('weixin');
        if (!empty($weixin_img_id['error'])) {
            echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => $weixin_img_id['msg'])));
            exit();
        }
        if ($weixin_lao_id) {
            wp_delete_attachment($weixin_lao_id, true);
        }
        update_user_meta($cuid, 'rewards_wechat_image_id', $weixin_img_id);
    }

    if (!empty($_FILES['alipay'])) {
        $alipay_img_id = zib_php_upload('alipay');
        if (!empty($alipay_img_id['error'])) {
            echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => $alipay_img_id['msg'])));
            exit();
        }
        if ($alipay_lao_id) {
            wp_delete_attachment($alipay_lao_id, true);
        }
        update_user_meta($cuid, 'rewards_alipay_image_id', $alipay_img_id);
    }

    if (!empty($_POST['rewards_title'])) update_user_meta($cuid, 'rewards_title', esc_attr(trim($_POST['rewards_title'])));

    echo (json_encode(array('error' => 0, 'msg' => '设置成功', '_POST' => $_POST, '_FILES' => $_FILES)));

    exit();
}
add_action('wp_ajax_user_set_rewards', 'zib_ajax_user_set_rewards');

//修改用户资料
function zib_ajax_user_edit_datas()
{
    $cuid = get_current_user_id();
    if (!$cuid) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '权限不足')));
        exit;
    }
    //用户名判断
    zib_ajax_username_judgment('name', true);
    $_POST['name'] = trim($_POST['name']);

    if (empty($_POST['desc'])) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '请填写个人签名')));
        exit();
    }

    if (_new_strlen($_POST['desc']) < 4) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '签名过短，不能低于4个字符')));
        exit();
    }
    if (_new_strlen($_POST['desc']) > 60) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '签名过长，不能超过60个字符')));
        exit();
    }

    if ($_POST['url'] && (!zib_is_url($_POST['url']))) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '网址格式错误')));
        exit();
    }

    if ($_POST['url'] && !$_POST['url_name']) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '请输入个人网站名称')));
        exit();
    }

    if ($_POST['url_name'] && !$_POST['url']) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '请输入个人网站链接')));
        exit();
    }

    if ($_POST['address'] && _new_strlen($_POST['address']) > 50) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '居住地格式错误')));
        exit();
    }

    if ($_POST['weibo'] && (!zib_is_url($_POST['weibo']) || _new_strlen($_POST['weibo']) > 50)) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '微博格式错误')));
        exit();
    }

    if ($_POST['github'] && (!zib_is_url($_POST['github']) || _new_strlen($_POST['github']) > 50)) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => 'GitHub格式错误')));
        exit();
    }

    if ($_POST['qq'] && !preg_match("/^[1-9]\d{4,16}$/", $_POST['qq'])) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => 'QQ格式错误')));
        exit();
    }

    if ($_POST['weixin'] && _new_strlen($_POST['weixin']) > 20) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '微信字数过长，限制在30字内')));
        exit();
    }

    if ($_POST['desc']) update_user_meta($cuid, 'description', trim($_POST['desc']));
    if ($_POST['qq']) update_user_meta($cuid, 'qq', trim($_POST['qq']));
    if ($_POST['weixin']) update_user_meta($cuid, 'weixin', trim($_POST['weixin']));
    if ($_POST['weibo']) update_user_meta($cuid, 'weibo', trim($_POST['weibo']));
    if ($_POST['github']) update_user_meta($cuid, 'github', trim($_POST['github']));
    if ($_POST['url_name']) update_user_meta($cuid, 'url_name', trim($_POST['url_name']));
    if ($_POST['gender']) update_user_meta($cuid, 'gender', trim($_POST['gender']));
    if ($_POST['address']) update_user_meta($cuid, 'address', trim($_POST['address']));
    if ($_POST['privacy']) update_user_meta($cuid, 'privacy', trim($_POST['privacy']));

    $datas = array('ID' => $cuid);
    if ($_POST['url']) $datas['user_url'] = trim($_POST['url']);
    if ($_POST['name']) {
        $datas['display_name'] = trim($_POST['name']);
        $datas['nickname'] = trim($_POST['name']);
    };

    $status = wp_update_user($datas);

    if (is_wp_error($status)) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => $status->get_error_message())));
        exit();
    }

    echo (json_encode(array('error' => 0, 'ys' => '', 'msg' => '用户资料修改成功')));
    exit();
}
add_action('wp_ajax_user_edit_datas', 'zib_ajax_user_edit_datas');


//修改用户头像
function zib_ajax_user_upload_avatar()
{
    $cuid = get_current_user_id();
    if (!$cuid) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '权限不足')));
        exit;
    }
    if (!wp_verify_nonce($_POST['upload_avatar_nonce'], 'upload_avatar')) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '安全验证失败，请稍候再试')));
        exit();
    }

    if (empty($_FILES['file'])) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '请选择图像')));
        exit();
    }

    $img_id = zib_php_upload();
    if (!empty($img_id['error'])) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => $img_id['msg'])));
        exit();
    }
    $image_url = wp_get_attachment_image_src($img_id, 'thumbnail');
    $lao_id = get_user_meta($cuid, 'custom_avatar_id', true);
    if ($lao_id) {
        wp_delete_attachment($lao_id, true);
    }
    update_user_meta($cuid, 'custom_avatar_id', $img_id);
    update_user_meta($cuid, 'custom_avatar', $image_url[0]);
    //删除缓存
    wp_cache_delete($cuid, 'user_avatar');
    echo (json_encode(array('error' => 0, 'msg' => '头像修改成功', 'img_id' => $img_id, 'img_url' => $image_url[0])));
    exit();
}
add_action('wp_ajax_user_upload_avatar', 'zib_ajax_user_upload_avatar');


//修改用户封面图修
function zib_ajax_user_upload_cover()
{
    $cuid = get_current_user_id();
    if (!$cuid) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '权限不足')));
        exit;
    }
    if (!wp_verify_nonce($_POST['upload_cover_nonce'], 'upload_cover')) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '安全验证失败，请稍候再试')));
        exit();
    }

    if (empty($_FILES['file'])) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '请选择图像')));
        exit();
    }

    $img_id = zib_php_upload();
    if (!empty($img_id['error'])) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => $img_id['msg'])));
        exit();
    }
    $image_url = wp_get_attachment_image_src($img_id, 'full');
    $lao_id = get_user_meta($cuid, 'cover_image_id', true);

    if ($lao_id) {
        wp_delete_attachment($lao_id, true);
    }

    update_user_meta($cuid, 'cover_image_id', $img_id);
    update_user_meta($cuid, 'cover_image', $image_url[0]);

    echo (json_encode(array('error' => 0, 'msg' => '封面图修改成功', 'img_id' => $img_id, 'img_url' => $image_url[0])));
    exit();
}
add_action('wp_ajax_user_upload_cover', 'zib_ajax_user_upload_cover');


//修改用户密码
function zib_ajax_user_change_password()
{
    $user_id = get_current_user_id();
    if (!$user_id) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '登录状态异常，请刷新后重试')));
        exit;
    }
    if (empty($_POST['password']) || empty($_POST['password2'])) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '密码不能为空')));
        exit();
    }

    if (strlen($_POST['password']) < 6) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '密码至少6位')));
        exit();
    }

    if ($_POST['password'] !== $_POST['password2']) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '两次密码输入不一致')));
        exit();
    }

    //人机验证
    zib_ajax_verification_form_judgment('img_yz_change_password');


    $oauth_new = get_user_meta($user_id, 'oauth_new', true);
    if (!$oauth_new) {
        global $wp_hasher, $current_user;
        require_once(ABSPATH . WPINC . '/class-phpass.php');

        $wp_hasher = new PasswordHash(8, TRUE);
        if (empty($_POST['passwordold'])) {
            echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '请输入原密码')));
            exit();
        }

        if ($_POST['passwordold'] == $_POST['password']) {
            echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '新密码和原密码不能相同')));
            exit();
        }

        if (!$wp_hasher->CheckPassword($_POST['passwordold'], $current_user->user_pass)) {
            echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '原密码错误')));
            exit();
        }
    }

    $status = wp_update_user(
        array(
            'ID' => $user_id,
            'user_pass' => $_POST['password']
        )
    );

    if (is_wp_error($status)) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => $status->get_error_message())));
        exit();
    }
    delete_user_meta($user_id, 'oauth_new');
    $msg = $oauth_new ? '密码设置成功' : '修改成功，下次请使用新密码登录';
    echo (json_encode(array('error' => 0, 'msg' => $msg)));
    exit();
}
add_action('wp_ajax_user_change_password', 'zib_ajax_user_change_password');


//用户解除第三方账户绑定
function zib_ajax_user_oauth_untying()
{
    if (empty($_POST['user_id']) || empty($_POST['type'])) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '参数传入错误')));
        exit();
    }
    $cuid = get_current_user_id();
    if (!$cuid || $cuid != $_POST['user_id']) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '权限不足')));
        exit;
    }

    delete_user_meta($_POST['user_id'], 'oauth_' . $_POST['type'] . '_openid');
    delete_user_meta($_POST['user_id'], 'oauth_' . $_POST['type'] . '_getUserInfo');

    echo (json_encode(array('error' => 0, 'ys' => '', 'msg' => '已解除绑定')));

    exit();
}
add_action('wp_ajax_user_oauth_untying', 'zib_ajax_user_oauth_untying');



///AJAX用户绑定邮箱发送验证码
function zib_ajax_bind_email_captcha()
{
    $user = wp_get_current_user();
    $cuid = (isset($user->ID) ? (int) $user->ID : 0);

    $email = $user->user_email;

    if (!$cuid) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '登录状态异常，请刷新后重试')));
        exit;
    }

    if (empty($_POST['email'])) {
        echo (json_encode(array('error' => 1, 'msg' => '请输入邮箱帐号')));
        exit();
    }

    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        echo (json_encode(array('error' => 1, 'msg' => '邮箱格式错误')));
        exit();
    }

    if ($email == $_POST['email']) {
        echo (json_encode(array('error' => 1, 'msg' => '修改邮箱不能与现邮箱相同')));
        exit();
    }

    //执行人机验证
    zib_ajax_verification_form_judgment('img_yz_bind_email_captcha');

    if (email_exists($_POST['email'])) {
        echo (json_encode(array('error' => 1, 'msg' => '该邮箱已有绑定帐号')));
        exit();
    }

    zib_ajax_send_captcha('email', $_POST['email'], false);
    exit();
}
add_action('wp_ajax_bind_email_captcha', 'zib_ajax_bind_email_captcha');


//用户绑定邮箱
function zib_ajax_user_bind_email()
{
    $user = wp_get_current_user();
    $cuid = (isset($user->ID) ? (int) $user->ID : 0);

    $email = $user->user_email;

    if (!$cuid) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '登录状态异常，请刷新后重试')));
        exit;
    }

    //执行用户隐私协议是否同意
    zib_ajax_agree_agreement_judgment();
    //执行邮箱格式判断
    $captcha = zib_ajax_captcha_form_judgment('email');
    $captcha_val = $captcha['to'];

    if ($email == $_POST['email']) {
        echo (json_encode(array('error' => 1, 'msg' => '修改邮箱不能与现邮箱相同')));
        exit();
    }

    //执行人机验证
    zib_ajax_verification_form_judgment('img_yz_bind_email_captcha');

    //判断是否需要验证码验证
    if (_pz('user_bind_option', true, 'email_set_captch')) {
        //执行验证码：验证判断
        zib_ajax_is_captcha('email');
    }

    if (email_exists($captcha_val)) {
        echo (json_encode(array('error' => 1, 'msg' => '该邮箱已有绑定帐号')));
        exit();
    }

    $status = wp_update_user(
        array(
            'ID' => $cuid,
            'user_email' => $captcha_val
        )
    );

    if (is_wp_error($status)) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', $status->get_error_message())));
        exit();
    }

    $msg = $email ? '邮箱修改成功' : '邮箱绑定成功';
    echo (json_encode(array('error' => 0, 'msg' => $msg)));
    exit();
}
add_action('wp_ajax_user_bind_email', 'zib_ajax_user_bind_email');

///AJAX用户绑定邮箱发送验证码
function zib_ajax_bind_phone_captcha()
{
    $user = wp_get_current_user();
    $cuid = (isset($user->ID) ? (int) $user->ID : 0);

    if (!$cuid) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '登录状态异常，请刷新后重试')));
        exit;
    }

    //执行手机号格式判断
    $captcha = zib_ajax_captcha_form_judgment('phone');
    $phone = get_user_meta($cuid, 'phone_number', true);

    if ($phone == $_POST['phone']) {
        echo (json_encode(array('error' => 1, 'msg' => '手机号不能与现手机号相同')));
        exit();
    }

    //执行人机验证
    zib_ajax_verification_form_judgment('img_yz_bind_phone_captcha');

    if (zib_get_user_by('phone', $_POST['phone'])) {
        echo (json_encode(array('error' => 1, 'msg' => '该手机号已被绑定')));
        exit();
    }

    zib_ajax_send_captcha('phone', $_POST['phone'], false);
    exit();
}
add_action('wp_ajax_bind_phone_captcha', 'zib_ajax_bind_phone_captcha');


//用户绑定手机号
function zib_ajax_user_bind_phone()
{
    $user = wp_get_current_user();
    $cuid = (isset($user->ID) ? (int) $user->ID : 0);
    $email = $user->user_email;

    if (!$cuid) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '登录状态异常，请刷新后重试')));
        exit;
    }

    //执行用户隐私协议是否同意
    zib_ajax_agree_agreement_judgment();
    //执行手机号格式判断
    $captcha = zib_ajax_captcha_form_judgment('phone');
    $captcha_val = $captcha['to'];

    $phone = get_user_meta($cuid, 'phone_number', true);

    if ($phone == $captcha_val) {
        echo (json_encode(array('error' => 1, 'msg' => '手机号不能与现手机号相同')));
        exit();
    }

    //执行人机验证
    zib_ajax_verification_form_judgment('img_yz_bind_phone_captcha');

    update_user_meta($cuid, 'phone_number', $captcha_val);

    $msg = $phone ? '手机号修改成功' : '手机号绑定成功';
    echo (json_encode(array('error' => 0, 'msg' => $msg)));
    exit();
}
add_action('wp_ajax_user_bind_phone', 'zib_ajax_user_bind_phone');

//用户账户功能设置模态框
function zib_ajax_user_set_modal()
{

    $user_data = wp_get_current_user();
    $cuid = (isset($user_data->ID) ? (int) $user_data->ID : 0);
    if (!$cuid) {
        echo '<div class="text-center c-red" style="padding: 80px 0; ">登录状态异常，请刷新后重试</div>';
        exit;
    }

    $html = '<button class="close" data-dismiss="modal">' . zib_svg('close', '0 0 1024 1024', 'ic-close') . '</button>';
    $tab = !empty($_REQUEST['tab']) ? $_REQUEST['tab'] : 'email';
    $html .= zib_get_user_center_bind_tab($tab, $cuid);
    echo '<div class="modal-body">' . $html . '</div>';
    exit();
}
add_action('wp_ajax_user_set_modal', 'zib_ajax_user_set_modal');
