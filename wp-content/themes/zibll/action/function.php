<?php
/*
 * @Author        : Qinver
 * @Url           : zibll.com
 * @Date          : 2020-09-29 13:18:37
 * @LastEditTime: 2021-05-24 20:41:46
 * @Email         : 770349780@qq.com
 * @Project       : Zibll子比主题
 * @Description   : 一款极其优雅的Wordpress主题
 * @Read me       : 感谢您使用子比主题，主题源码有详细的注释，支持二次开发。欢迎各位朋友与我相互交流。
 * @Remind        : 使用盗版主题会存在各种未知风险。支持正版，从我做起！
 */
$functions = array(
    'user',
    'author',
    'sign_register',
    'comment',
    'new_posts'
);

foreach ($functions as $function) {
    require_once plugin_dir_path(__FILE__) . $function . '.php';
}

/**设置验证码 */
function zib_get_captcha($counts = 6)
{
    $originalcode = '0,1,2,3,4,5,6,7,8,9';
    $originalcode = explode(',', $originalcode);
    $countdistrub = 10;
    $_dscode      = "";
    for ($j = 0; $j < $counts; $j++) {
        $dscode = $originalcode[rand(0, $countdistrub - 1)];
        $_dscode .= $dscode;
    }
    return strtolower($_dscode);
}

/**发送验证码 */
function zib_send_captcha($to, $type = 'email')
{
    @session_start();
    $code = zib_get_captcha(6);
    /**保存验证码到缓存 */
    $_SESSION['zib_captcha'] = $code;
    $_SESSION['zib_verification_to'] = $to;

    if (!empty($_SESSION['zib_captcha_time'])) {
        $time_x = strtotime(current_time('mysql')) - strtotime($_SESSION['zib_captcha_time']);
        if ($time_x < 60) {
            return array('error' => 1, 'ys' => 'danger', 'msg' => (60 - $time_x) . '秒后可重新发送');
        }
    }

    $_SESSION['zib_captcha_time'] = current_time('mysql');

    switch ($type) {
        case 'email':
            $result = false;
            $blog_name = get_bloginfo('name');
            if (is_email($to)) {
                $title = '[' . $blog_name . ']' . '收到验证码';
                $message = "您正在本站进行验证操作，如非您本人操作，请忽略此邮件。\r\n\r\n验证码30分钟内有效，如果超时请重新获取";
                $message .= "\r\n\r\n";
                $message .= '您的邮箱为：' . $to . "\r\n\r\n";
                $message .= '您的验证码为：';
                $message .= '<p style="font-size:34px;color:#3095f1;"><span style="border-bottom: 1px dashed #ccc; z-index: 1; position: static;">' . $code . '</span></p>';
                $result = @wp_mail($to, $title, $message);
            }
            if ($result) {
                return array('error' => 0, 'result' => true, 'msg' => '验证码已发送至您的邮箱');
            } else {
                return array('error' => 1, 'ys' => 'danger', 'msg' => '验证码发送失败');
            }
            break;
        case 'phone':
            $result = ZibSMS::send($to, $code);
            if (!empty($result['result'])) {
                $result['msg'] = '验证码短信已发送';
            }
            return $result;
            break;
    }
}


/**验证码效验 */
function zib_is_captcha($to, $code, $msg = '')
{
    @session_start();
    if (empty($_SESSION['zib_captcha']) || $_SESSION['zib_captcha'] != $code || empty($_SESSION['zib_verification_to']) || $_SESSION['zib_verification_to'] != $to) {
        return array('error' => 1, 'ys' => 'danger', 'msg' =>  $msg . '验证码错误');
    } else {
        if (!empty($_SESSION['zib_captcha_time'])) {
            $time_x = strtotime(current_time('mysql')) - strtotime($_SESSION['zib_captcha_time']);
            if ($time_x > 1800) {
                //30分钟有效
                return array('error' => 1, 'ys' => 'danger', 'msg' =>  $msg . '验证码已过期');
            }
        }
        return array('error' => 0, 'result' => true, 'msg' =>  $msg . '验证码效验成功');
    }
}

/**
 * @description: AJAX验证码判断，判断错误直接退出ajax
 * @param {*} $to
 * @param {*} $code
 * @return {*}
 */
function zib_ajax_is_captcha($to_name = 'email', $code_name = 'captch')
{
    $type_name = '';
    if ($to_name == 'email') {
        $type_name = '邮箱';
    } elseif ($to_name == 'phone') {
        $type_name = '短信';
    }
    if (empty($_REQUEST[$code_name])) {
        echo (json_encode(array('error' => 1, 'msg' => '请输入' . $type_name . '验证码')));
        exit();
    }
    if (empty($_REQUEST[$to_name])) {
        echo (json_encode(array('error' => 1, 'msg' => '缺少验证参数')));
        exit();
    }
    $is_captcha = zib_is_captcha($_REQUEST[$to_name], $_REQUEST[$code_name]);
    if ($is_captcha['error']) {
        echo json_encode($is_captcha);
        exit();
    }

    return true;
}

/**前端AJAX发送验证码 */
function zib_ajax_send_captcha($captcha_type, $to, $judgment = true)
{
    if ($judgment) {
        $captcha = zib_ajax_captcha_form_judgment($captcha_type, $to);
        $captcha_type = $captcha['type'];
        $to = $captcha['to'];
    }
    $send = zib_send_captcha($to, $captcha_type);

    if (empty($send['error']) && empty($send['msg'])) {
        $send['msg'] = '验证码已发送';
    }
    echo json_encode($send);
    exit;
}

/**
 * @description: ajax人机验证判断
 * @param {*} $captcha_type
 * @param {*} $input
 * @return {*}
 */
function zib_ajax_verification_form_judgment($id)
{
    $type =  _pz('user_verification_type', 'slider');
    if ($type == 'image') {
        if (empty($_REQUEST['canvas_yz']) || strlen($_REQUEST['canvas_yz']) < 4) {
            echo (json_encode(array('error' => 1, 'msg' => '请输入图形验证码')));
            exit();
        }
        if (empty($_REQUEST['canvas_code'][$id])) {
            echo (json_encode(array('error' => 1, 'msg' => '环境异常，请刷新后重试')));
            exit();
        }
        $vcode = strtolower(implode('', $_REQUEST['canvas_code'][$id]));
        if ($vcode != strtolower($_REQUEST['canvas_yz'])) {
            echo (json_encode(array('error' => 1, 'msg' => '图形验证码错误')));
            exit();
        }
    } elseif ($type == 'slider') {
        if (empty($_REQUEST['slidercaptcha']['spliced']) || empty($_REQUEST['slidercaptcha']['verified'])) {
            echo (json_encode(array('error' => 1, 'msg' => '人机验证失败')));
            exit();
        }
    }
    return true;
}

/**
 * @description: 执行用户隐私协议勾选检测 同意协议
 * @param {*} $id
 * @return {*}
 */
function zib_ajax_agree_agreement_judgment($name = 'user_agreement')
{
    if (zib_get_agreement_input() && empty($_REQUEST[$name])) {
        echo (json_encode(array('error' => 1, 'msg' => '请先阅读并同意用户协议')));
        exit();
    }
}

/**
 * @description: AJAX验证方式判断，已做了AJAX返回
 * @param {*} $captcha_type
 * @param {*} $input
 * @return 错误直接退出，正确返回 $captcha_type 验证方式
 */
function zib_ajax_captcha_form_judgment($captcha_type = 'email', $input = '')
{

    $captcha_type = $captcha_type ? $captcha_type : (!empty($_REQUEST['captcha_type']) ? $_REQUEST['captcha_type'] : '');
    $input = $input ? $input : (!empty($_REQUEST[$captcha_type]) ? $_REQUEST[$captcha_type] : '');
    $input = esc_sql(trim($input));
    if (!$captcha_type) {
        echo (json_encode(array('error' => 1, 'msg' => '参数传入错误')));
        exit();
    }

    if ($captcha_type == 'email') {
        if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
            echo (json_encode(array('error' => 1, 'msg' => '邮箱格式错误')));
            exit();
        }
    } elseif ($captcha_type == 'phone') {
        if (!ZibSMS::is_phonenumber($input)) {
            echo (json_encode(array('error' => 1, 'phone' => $input, 'msg' => '手机号码格式有误')));
            exit();
        }
    } else {
        if (!$input) {
            echo (json_encode(array('error' => 1, 'msg' => '请输入邮箱或手机号')));
            exit();
        }
        if (ZibSMS::is_phonenumber($input)) {
            $captcha_type = 'phone';
        } elseif (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $captcha_type = 'email';
        } else {
            echo (json_encode(array('error' => 1, 'msg' => '手机号或邮箱格式错误')));
            exit();
        }
    }
    return array('type' => $captcha_type, 'to' => $input);
}

/**前端AJAX链接提交 */
function zib_ajax_frontend_links_submit()
{

    if (isset($_COOKIE['zib_links_submit_time'])) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '操作过于频繁，请稍候再试')));
        exit();
    }
    if (empty($_POST['link_name'])) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '请填写链接名称')));
        exit();
    }
    if (empty($_POST['link_url'])) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '请填写链接地址')));
        exit();
    }
    /**准备数据 */
    $linkdata = array(
        'link_name'   => esc_attr($_POST['link_name']),
        'link_url'    => esc_url($_POST['link_url']),
        'link_description' => !empty($_POST['link_description']) ? esc_attr($_POST['link_description']) : '',
        'link_image' => !empty($_POST['link_image']) ? esc_attr($_POST['link_image']) : '',
        'link_visible' => 'N'
    );
    /**添加链接 */
    $links_id = wp_insert_link($linkdata);
    if (is_wp_error($links_id)) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => $links_id->get_error_message())));
        exit();
    }
    //设置浏览器缓存限制提交的间隔时间
    $expire = time() + 30;
    setcookie('zib_links_submit_time', time(), $expire, '/', '', false);

    echo (json_encode(array('msg' => '提交成功，等待管理员处理')));
    /**添加执行挂钩 */
    do_action('zib_ajax_frontend_links_submit_success', $_POST);
    exit();
}
add_action('wp_ajax_frontend_links_submit', 'zib_ajax_frontend_links_submit');
add_action('wp_ajax_nopriv_frontend_links_submit', 'zib_ajax_frontend_links_submit');


//上传图像
function zib_php_upload($file = 'file')
{
    if (empty($_FILES)) {
        return array('error' => 1, '_FILES' => '', 'msg' => '图片信息错误，请重新选择图片');
    }

    if ($_FILES) {
        require_once(ABSPATH . "wp-admin" . '/includes/image.php');
        require_once(ABSPATH . "wp-admin" . '/includes/file.php');
        require_once(ABSPATH . "wp-admin" . '/includes/media.php');
        $attach_id = media_handle_upload($file, 0);
        if (is_wp_error($attach_id)) {
            return array('error' => 1, '_FILES' => $_FILES, 'msg' => $attach_id->get_error_message());
        } else {
            return $attach_id;
        }
    }
}

/**评论上传图片 */
function zib_ajax_user_upload_image()
{
    //必须登录
    $cuid = get_current_user_id();
    if (!$cuid) {
        echo (json_encode(array('error' => 1, 'error_id' => 'nologged', 'ys' => 'danger', 'msg' => '请先登录')));
        exit;
    }
    if (!wp_verify_nonce($_POST['upload_image_nonce'], 'upload_image')) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => '安全验证失败，请稍候再试')));
        exit();
    }

    //开始上传
    $img_id = zib_php_upload();
    if (!empty($img_id['error'])) {
        echo (json_encode(array('error' => 1, 'ys' => 'danger', 'msg' => $img_id['msg'])));
        exit();
    }

    $size = !empty($_REQUEST['size']) ? $_REQUEST['size'] : 'large';
    $img_url = wp_get_attachment_image_src($img_id, $size)[0];

    echo (json_encode(array('error' => '', 'ys' => '', 'msg' => '图片已上传', 'img_url' => $img_url)));
    exit();
}
add_action('wp_ajax_user_upload_image', 'zib_ajax_user_upload_image');
add_action('wp_ajax_nopriv_user_upload_image', 'zib_ajax_user_upload_image');


/**
 * @description: AJAX空白内容
 * @param {*}
 * @return {*}
 */
function zib_get_ajax_null($text = '暂无内容', $margin = '60', $img = 'null.svg')
{
    $html = zib_get_null($text, $margin, $img, 'ajax-item ');
    $html .= '<div class="ajax-pag hide"><div class="next-page ajax-next"><a href="#"></a></div></div>';
    return $html;
}


/**
 * @description: 空白内容
 * @param {*}
 * @return {*}
 */
function zib_get_null($text = '暂无内容', $margin = '60', $img = 'null.svg', $class = '', $width = '240')
{
    $html = '<div class="text-center ' . $class . '" style="margin:' . $margin . 'px 0;"><img style="width:' . $width . 'px;opacity: .7;" src="' . ZIB_STYLESHEET_DIRECTORY_URI . '/img/' . $img . '"><p style="margin-top:' . $margin . 'px;" class="em09 muted-3-color separator">' . $text . '</p></div>';
    return $html;
}


/**
 * @description: 空白内容
 * @param {*}
 * @return {*}
 */
function zib_get_ajax_error_html($args = array())
{

    $defaults = array(
        'error' => 1,
        'class' => '',
        'ys' => 'danger',
        'margin' => '50',
        'id' => '',
        'msg' => '内容获取出错！',
    );
    $args = wp_parse_args((array) $args, $defaults);

    $id = $args['id'] ? ' id="' . $args['id'] . '"' : '';
    $con = '错误：' . $args['msg'] . '，错误代码：' . $args['error'];

    $con = '<div class="ajax-item text-center text-' . $args['ys'] . ' ' . $args['class'] . '" style="padding:' . $args['margin'] . 'px 0;">' . $con . '</div>';
    $con .= '<div class="ajax-pag hide"><div class="next-page ajax-next"><a href="#"></a></div></div>';
    $html = '<div class="ajaxpager"' . $id . '>' . $con . '</div>';
    $html = '<body><main>' . $html . '</main></body>';

    return $html;
}


function zib_is_repetition_username($name)
{
    $db_name = false;
    if ($name) {
        global $wpdb;
        $db_name = $wpdb->get_var("SELECT id FROM $wpdb->users WHERE `user_nicename`='" . $name . "' OR `display_name`='" . $name . "' ");
    }
    return $db_name;
}


/**
 * @description: 用户名AJAX验证|错误直接结束ajax
 * @param {*} $name
 * @return {*}
 */
function zib_ajax_username_judgment($name, $simple = false)
{
    $user_name = !empty($_REQUEST[$name]) ? trim($_REQUEST[$name]) : '';
    if (!$user_name) {
        echo (json_encode(array('error' => 1, 'msg' => '请输入用户名')));
        exit();
    }
    if (_new_strlen($user_name) < 2) {
        echo (json_encode(array('error' => 1, 'msg' => '用户名太短')));
        exit();
    }
    if (_new_strlen($user_name) > 10) {
        echo (json_encode(array('error' => 1, 'msg' => '用户名太长')));
        exit();
    }
    if (!$simple) {
        if (is_disable_username($user_name)) {
            echo (json_encode(array('error' => 1, 'msg' => '昵称含保留或非法字符')));
            exit();
        }
        //重复昵称判断
        if (_pz('no_repetition_name', true)) {
            if (zib_is_repetition_username($user_name)) {
                echo (json_encode(array('error' => 1, 'msg' => '昵称已存在，请换一个试试')));
                exit();
            }
        }
    }

    return true;
}
