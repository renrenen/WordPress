<?php

/**
 * Template name: Zibll-登录/注册/找回密码
 * Description:   找回密码页面
 */

$redirect_to = !empty($_GET['redirect_to']) ? $_GET['redirect_to'] : home_url();
$tab = !empty($_GET['tab']) ? $_GET['tab'] : '';
$interim = isset($_REQUEST['interim-login']);

//如果已经登录则退回
$user_bind_tab = '';
if (is_user_logged_in()) {
    //兼容后台临时登录
    if ($interim && is_super_admin()) {
        echo '<!DOCTYPE HTML><html><body class="login interim-login-success"><div style="padding: 60px 0;text-align: center;color: #1a88fd;">登录成功</div></body></html>';
        exit;
    } elseif ($tab != 'bind') {
        wp_safe_redirect($redirect_to);
        exit;
    }
    if ($tab == 'bind') {
        $bind_type = zib_get_user_bind_type();
        $user_bind_tab = zib_get_user_bind_tab($bind_type);
        if (!$user_bind_tab) {
            wp_safe_redirect($redirect_to);
            exit;
        }
    }
} elseif ($tab == 'bind') {
    //如果是绑定，但是未登录则返回登录页
    wp_safe_redirect(add_query_arg('tab', 'signin'));
    exit;
}

$background_html = '';
$background = _pz('user_sign_page_option', 0, 'background');
if ($background) {
    $background_array = explode(',', $background);
    $rand = array_rand($background_array, 1);
    $background_url = wp_get_attachment_url($background_array[$rand]);
    $background_html = '<div class="fixed lazyload" data-bg="' . $background_url . '" style=\'background-repeat: no-repeat;background-size: cover;background-position: center;\'></div>';
}

$card_position = _pz('user_sign_page_option', 'right', 'card_position');

$card_col_class = ' col-md-offset-6';
if ($card_position == 'left') $card_col_class = '';
if ($card_position == 'center') $card_col_class = ' col-md-offset-3';

?>

<!DOCTYPE HTML>
<html <?php echo 'lang="' . esc_attr(get_bloginfo('language')) . '"'; ?>>

<head>
    <meta charset="UTF-8">
    <link rel="dns-prefetch" href="//apps.bdimg.com">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=0.0">
    <meta http-equiv="Cache-Control" content="no-transform" />
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <meta name="robots" content="noindex,nofollow">
    <?php wp_head(); ?>
    <style>
        .page-template-user-sign {
            min-height: 500px;
        }

        .sign-page {
            min-height: 500px;
            padding-top: 70px;
        }

        .sign-row {
            height: 100%;
        }

        .sign-page .sign {
            width: 350px;
        }

        .sign-col {
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>

<body <?php body_class(_bodyclass()); ?>>
    <?php
    echo $background_html;
    echo qj_dh_nr();
    if (_pz('user_sign_page_option', 0, 'show_header') && !$interim) {
        zib_header();
    }
    ?>
    <main role="main" class="container sign-page absolute">
        <div class="row sign-row gutters-5">
            <div class="col-md-6<?php echo $card_col_class; ?> sign-col">
                <div style="padding:20px 0 60px 0">
                    <div class="sign zib-widget blur-bg relative">
                        <?php
                        if (_pz('user_sign_page_option', 0, 'show_logo')) {
                            echo zib_get_sign_logo(home_url());
                        }
                        if ($user_bind_tab) {
                            $bind_html = '';
                            $bind_html .= '<div class="box-body">';
                            $bind_text = _pz('user_bind_option', '', 'mandatory_bind_text');
                            $bind_html .= $bind_text ? '<div class="mb20 em09">' . $bind_text . '</div>' : '';
                            $bind_html .= $user_bind_tab;
                            $bind_html .= '</div>';
                            $bind_html .= '<div class="box-body notop"><a type="button" class="but c-red padding-lg btn-block " href="' . wp_logout_url(home_url()) . '">' . zib_svg('signout') . ' 退出登录</a></div>';
                            echo $bind_html;
                        } else {
                            zib_user_signtab_content($tab, true);
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <div class="notyn"></div>
    <?php
    $footer = _pz('user_sign_page_option', 0, 'footer');
    if ($footer && !$interim) {
        echo '<div class="text-center blur-bg fixed px12 opacity8" style="top: auto; height: auto;padding: 10px; ">' . $footer . '</div>';
    }
    wp_footer();
    ?>
</body>

</html>

<?php
