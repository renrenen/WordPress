<?php
/*
 * @Author        : Qinver
 * @Url           : zibll.com
 * @Date          : 2020-09-29 13:18:50
 * @LastEditTime: 2021-05-26 16:54:18
 * @Email         : 770349780@qq.com
 * @Project       : Zibll子比主题
 * @Description   : 一款极其优雅的Wordpress主题
 * @Read me       : 感谢您使用子比主题，主题源码有详细的注释，支持二次开发。欢迎各位朋友与我相互交流。
 * @Remind        : 使用盗版主题会存在各种未知风险。支持正版，从我做起！
 */

//引入函数文件
require_once plugin_dir_path(__FILE__) . 'class/order-class.php';

foreach (array(
    'zibpay-ajax',
    'zibpay-download',
    'zibpay-user',
    'zibpay-vip',
    'zibpay-rebate',
    'ajax',
    'rebate-ajax',
    'zibpay-msg',
    'widget',
) as $php) {
    require_once plugin_dir_path(__FILE__) . 'functions/' . $php . '.php';
}

if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'functions/admin/admin.php';
}
/**挂钩到主题启动 */
function zibpay_creat_table_order()
{
    ZibPay::create_db();
}
add_action('admin_head', 'zibpay_creat_table_order');

/**
 * 创建后台管理菜单
 */
function add_settings_menu()
{
    add_menu_page('Zibll商城', 'Zibll商城', 'administrator',  'zibpay_page', 'zibpay_page', 'dashicons-cart');
    add_submenu_page('zibpay_page', '商品明细', '商品明细', 'administrator', 'zibpay_product_page', 'zibpay_product_page');
    add_submenu_page('zibpay_page', '订单明细', '订单明细', 'administrator', 'zibpay_order_page', 'zibpay_order_page');
    add_submenu_page('zibpay_page', '返佣明细', '佣金明细', 'administrator', 'zibpay_rebate_page', 'zibpay_rebate_page');
    if (_pz('pay_rebate_s')) {
        add_submenu_page('zibpay_page', '佣金提现', '佣金提现', 'administrator', 'zibpay_withdraw', 'zibpay_withdraw_page');
    }
    add_submenu_page('zibpay_page', '会员管理', '会员管理', 'administrator', 'users.php', '');
}
add_action('admin_menu', 'add_settings_menu');

function zibpay_page()
{
    require_once get_stylesheet_directory() . '/zibpay/page/index.php';
}
function zibpay_order_page()
{
    require_once get_stylesheet_directory() . '/zibpay/page/order.php';
}
function zibpay_product_page()
{
    require_once get_stylesheet_directory() . '/zibpay/page/product.php';
}
function zibpay_rebate_page()
{
    require_once get_stylesheet_directory() . '/zibpay/page/rebate.php';
}
function zibpay_withdraw_page()
{
    require_once get_stylesheet_directory() . '/zibpay/page/withdraw.php';
}

/**
 * 排队插入JS文件
 */
add_action('admin_enqueue_scripts', 'zibpay_setting_scripts');
function zibpay_setting_scripts()
{
    if (isset($_GET['page']) && stristr($_GET['page'], "zibpay")) {
        wp_enqueue_style('zibpay_page', get_template_directory_uri() . '/zibpay/assets/css/pay-page.css');
        wp_enqueue_script('highcharts', get_template_directory_uri() . '/zibpay/assets/js/highcharts.js', array('jquery'));
        wp_enqueue_script('westeros', get_template_directory_uri() . '/zibpay/assets/js/westeros.min.js', array('jquery', 'highcharts'));
        wp_enqueue_script('zibpay_page', get_template_directory_uri() . '/zibpay/assets/js/pay-page.js', array('jquery', 'jquery_form'));
    }
}

/**在文章页面插入产品购买模块 */
function zibpay_posts_pay_content()
{
    global $post;
    $pay_mate = get_post_meta($post->ID, 'posts_zibpay', true);

    if (empty($pay_mate['pay_type']) || $pay_mate['pay_type'] == 'no') return;

    // 查询是否已经购买
    $paid = zibpay_is_paid($post->ID);

    if ($paid) {
        //添加处理挂钩
        $html = apply_filters('zibpay_posts_paid_box', '', $pay_mate, $post->ID);
        $html = $html ? $html : zibpay_posts_paid_box($pay_mate, $paid, $post->ID);
        echo $html;
    } else {
        //添加处理挂钩
        $html = apply_filters('zibpay_posts_pay_box', '', $pay_mate, $post->ID);
        $html = $html ? $html : zibpay_posts_pay_box($pay_mate, $post->ID);
        echo $html;
    }
}

$pay_box_position = _pz('pay_box_position', 'top');
$positions = array(
    'box_top' => 'zib_single_before',
    'top' => 'zib_single_box_content_before',
    'bottom' => 'zib_posts_content_after',
    'box_bottom' => 'zib_single_after',
);
add_action($positions[$pay_box_position], 'zibpay_posts_pay_content', 1);


//获取购买的服务内容
function zibpay_get_service($class = 'inline-block mr10', $icon_class = 'c-red mr3')
{
    $_pz = _pz('pay_service');
    if (!$_pz || !is_array($_pz)) return;
    $html = '';
    foreach ($_pz as $service) {
        if (empty($service['value'])) {
            continue;
        }
        $icon = !empty($service['icon']) ? $service['icon'] : 'fa fa-check-circle-o';
        $value =  $service['value'];
        $html .= '<div class="' . $class . '"><i class="fa-fw ' . $icon . ' ' . $icon_class . '" aria-hidden="true"></i>' . $value . '</div>';
    }
    return $html;
}


//获取购买的演示按钮
function zibpay_get_demo_link($pay_mate = array(), $post_id = 0, $class = 'but c-yellow padding-lg btn-block em09')
{
    if (!$post_id) $post_id = get_the_ID();
    if (!$pay_mate) $pay_mate = get_post_meta($post_id, 'posts_zibpay', true);
    if ($pay_mate['pay_type'] != 2 || empty($pay_mate['demo_link']['url'])) return;


    $url = $pay_mate['demo_link']['url'];
    $text = !empty($pay_mate['demo_link']['text']) ? $pay_mate['demo_link']['text'] : '查看演示';
    $target = !empty($pay_mate['demo_link']['target']) ? ' target="_blank"' : '';

    $link = '<a' . $target . ' href="' . esc_url($url) . '" class="' . $class . '"><i class="fa fa-link fa-fw" aria-hidden="true"></i>' . $text . '</a>';

    return $link;
}

//获取已售数量 销售数量
function zibpay_get_sales_volume($pay_mate = array(), $post_id = 0)
{
    if (!$post_id) $post_id = get_the_ID();
    $cuont = get_post_meta($post_id, 'sales_volume', true);
    if ($cuont) return $cuont;
    if (!$pay_mate) $pay_mate = get_post_meta($post_id, 'posts_zibpay', true);
    $cuont = 0;
    global $wpdb;
    $cuont = $wpdb->get_var("SELECT COUNT(id) FROM $wpdb->zibpay_order where post_id=$post_id and status=1");
    $cuont = !empty((int)$pay_mate['pay_cuont']) ? (int)$pay_mate['pay_cuont'] + $cuont : $cuont;
    return $cuont > 0 ? $cuont : 0;
}

//获取推广返利促销标签
function zibpay_get_rebate_discount_tag($pay_mate = array(), $post_id = 0)
{
    if (!_pz('pay_rebate_s')) return;

    if (!$post_id) $post_id = get_the_ID();
    if (!$pay_mate) $pay_mate = get_post_meta($post_id, 'posts_zibpay', true);

    // 推荐返佣、让利功能
    $referrer_id = zibpay_get_referrer_id();
    $discount_tag = '';
    if ($referrer_id) {
        //查询到推荐人
        //返利规则
        $rebate_ratio = zibpay_get_user_rebate_rule($referrer_id);
        if (
            !empty($pay_mate['pay_rebate_discount'])
            && $rebate_ratio['type']
            && is_array($rebate_ratio['type'])
            && (in_array('all', $rebate_ratio['type']) || in_array($pay_mate['pay_type'], $rebate_ratio['type']))
        ) {
            // 设置标签文案
            $referrer_data = get_userdata($referrer_id);
            $discount_tag = _pz('pay_rebate_text_discount');
            $discount_tag =  str_replace('%discount%', $pay_mate['pay_rebate_discount'], $discount_tag);
            $discount_tag =  str_replace('%referrer_name%', $referrer_data->display_name, $discount_tag);
        };
    }

    return $discount_tag;
}


//免费内容-需要登录才能查看
function zibpay_posts_free_logged_show_box($pay_mate, $post_id = '')
{
    if (!$pay_mate) {
        if (!$post_id) $post_id = get_the_ID();
        $pay_mate = get_post_meta($post_id, 'posts_zibpay', true);
    }

    if (empty($pay_mate['pay_type']) || $pay_mate['pay_type'] == 'no') return;

    $order_type_name = zibpay_get_pay_type_name($pay_mate['pay_type'], true);
    $order_type_name = str_replace("付费", "免费", $order_type_name);

    $cuont = '';
    $cuont_volume = zibpay_get_sales_volume($pay_mate, $post_id);
    if (_pz('pay_show_paycount', true) && $cuont_volume) {
        $cuont = '<badge class="img-badge hot jb-blue px12">已售 ' . $cuont_volume . '</badge>';
    }
    $mark = _pz('pay_mark', '￥');
    $mark = '<span class="pay-mark">' . $mark . '</span>';

    //会员价格
    $vip_price = zibpay_get_posts_vip_price($pay_mate);

    //标题
    $pay_title = zibpay_get_post_pay_title($pay_mate, $post_id);
    //价格
    $price = zibpay_get_show_price($pay_mate, $post_id, 'c-red');

    //更多内容
    $pay_details = !empty($pay_mate['pay_details']) ? '<div class="pay-details">' . $pay_mate['pay_details'] . '</div>' : '';

    //商品属性
    $attribute = zibpay_get_product_attributes($pay_mate, $post_id);

    //演示地址
    $demo_link = zibpay_get_demo_link($pay_mate, $post_id);
    //服务内容
    $service = zibpay_get_service('inline-block ml10');
    $service = $service ? '<div class="px12 muted-2-color mt10 text-right">' . $service . '</div>' : '';
    //付费类型
    $order_type_name = '<div class="pay-tag abs-center">' . $order_type_name . '</div>';

    //左侧图片
    $product_graphic = '';
    $post_thumbnail = '';
    if ($pay_mate['pay_type'] != 5) {
        if ($pay_mate['pay_type'] == 6) {
            $video_pic = !empty($pay_mate['video_pic']) ? '<img class="fit-cover lazyload" src="' . zib_get_lazy_thumb() . '" data-src="' . esc_attr($pay_mate['video_pic']) . '" alt="付费视频-' . esc_attr($pay_title) . '">' : zib_post_thumbnail();
            $post_thumbnail = $video_pic;
            $post_thumbnail .= '<div class="absolute graphic-mask" style="opacity: 0.2;"></div>';
            $post_thumbnail .= '<div class="abs-center text-center"><i class="fa fa-play-circle-o fa-4x opacity8" aria-hidden="true"></i></div>';
        } else {
            $post_thumbnail = zib_post_thumbnail();
        }

        $product_graphic = '<div class="flex0 relative mr20 hide-sm pay-thumb"><div class="graphic">';
        $product_graphic .= $post_thumbnail;
        $product_graphic .= '<div class="abs-center text-center left-bottom">';
        $product_graphic .= $demo_link ? '<div class="">' . $demo_link . '</div>' : '';
        $product_graphic .= '</div>';
        $product_graphic .= '</div></div>';
    } else {
        $product_graphic = zibpay_get_posts_pay_gallery_box($pay_mate);
        $product_graphic = str_replace("请付费后", "请登录后", $product_graphic);
    }

    //登录按钮
    if (zib_is_close_sign()) {  //是否开启登录功能
        //简介
        $pay_doc = !empty($pay_mate['pay_doc']) ? $pay_mate['pay_doc'] : '';
        $button = '<div class=""><span class="badg padding-lg btn-block c-red em09"><i class="fa fa-info-circle mr10"></i>登录功能已关闭，暂时无法查看</span></div>';
    } else {
        $pay_doc = !empty($pay_mate['pay_doc']) ? $pay_mate['pay_doc'] : '此内容为' . str_replace("付费", "免费", zibpay_get_pay_type_name($pay_mate['pay_type'])) . '，请登录后查看';
        $button = '<div class=""><a href="javascript:;" class="but signin-loader padding-lg btn-block jb-blue"><i class="fa fa-sign-in"></i> 登录查看</a></div>';
    }

    //左侧图片结束
    $order_type_class = 'order-type-' . $pay_mate['pay_type'];
    $html = '<div class="zib-widget pay-box  ' . $order_type_class . '" id="posts-pay">';
    $html .= '<div class="flex pay-flexbox">';
    $html .= $product_graphic;
    $html .= '<div class="flex1 flex xx jsb">';
    $html .= '<dt class="text-ellipsis pay-title"' . ($cuont ? 'style="padding-right: 50px;"' : '') . '>' . $pay_title . '</dt>';
    $html .= '<div class="mt6 em09 muted-2-color">' . $pay_doc . '</div>';
    $html .= '<div class="price-box hide-sm">' . $price . '</div>';
    $html .= '<div class="text-right mt10">' . $button . '</div>';

    $html .= '';
    $html .= '</div>';
    $html .= '</div>';
    $html .= $service;
    $html .= $demo_link ? '<div class="mt10 visible-xs-block">' . $demo_link . '</div>' : '';;
    $html .= $attribute;
    $html .= $pay_details;
    $html .= $order_type_name;
    $html .= $cuont;
    $html .= '</div>';

    return $html;
}


/**文章已经付费模块 */
function zibpay_posts_paid_box($pay_mate, $paid, $post_id = '')
{

    if (empty($pay_mate['pay_type']) || $pay_mate['pay_type'] == 'no') return;

    //判断免费资源且需要登录
    if ($paid['paid_type'] == 'free' && _pz('pay_free_logged_show') && !is_user_logged_in()) return zibpay_posts_free_logged_show_box($pay_mate, $post_id);

    //标题
    $pay_title = zibpay_get_post_pay_title($pay_mate, $post_id);

    //简介
    $pay_doc = !empty($pay_mate['pay_doc']) ? $pay_mate['pay_doc'] : '';

    //销售数量
    $cuont = '';
    $cuont_volume = zibpay_get_sales_volume($pay_mate, $post_id);
    if (_pz('pay_show_paycount', true) && $cuont_volume) {
        $cuont = '<badge class="img-badge hot jb-blue px12">已售 ' . $cuont_volume . '</badge>';
    }

    //更多内容
    $pay_details = !empty($pay_mate['pay_details']) ? '<div class="pay-details">' . $pay_mate['pay_details'] . '</div>' : '';

    //额外隐藏内容
    $pay_extra_hide = !empty($pay_mate['pay_extra_hide']) ? '<div class="pay-details">' . $pay_mate['pay_extra_hide'] . '</div>' : '';

    //商品属性
    $attribute = zibpay_get_product_attributes($pay_mate, $post_id);

    //演示地址
    $demo_link = zibpay_get_demo_link($pay_mate, $post_id);
    //服务内容
    $service = zibpay_get_service('inline-block ml10');
    $service = $service ? '<div class="px12 muted-2-color mt10 text-right">' . $service . '</div>' : '';
    //订单类型
    $order_type_name = zibpay_get_pay_type_name($pay_mate['pay_type'], true);

    //查看原因
    $paid_type = $paid['paid_type'];
    $paid_name = zibpay_get_paid_type_name($paid_type);

    //订单类型
    $pay_type = $pay_mate['pay_type'];
    $order_type_class = 'order-type-' . $pay_mate['pay_type'];

    $paid_box = '';
    $header_box = '';
    $down_box = '';

    switch ($pay_type) {
            // 根据支付接口循环进行支付流程
        case 1:
            break;
        case 2:
            //付费阅读 //付费下载

            //商品属性
            $attribute = zibpay_get_product_attributes($pay_mate, $post_id);

            if (_pz('pay_type_option', 0, 'down_alone_page')) {  //判断是否开启独立下载页面
                $demo_link = zibpay_get_demo_link($pay_mate, $post_id, 'but jb-yellow padding-lg btn-block');
                $down_link = '<a target="_blank" href="' . add_query_arg('post', $post_id, zib_get_template_page_url('pages/download.php'))  . '" class="but jb-blue padding-lg btn-block"><i class="fa fa-download fa-fw" aria-hidden="true"></i>资源下载</a>';
                $down_box  = '<div class="mt10">' . $down_link . '</div>';
                if ($demo_link) {  //判断是否有演示地址
                    $down_box = '<div class="but-group paid-down-group mt10">';
                    $down_box .= $demo_link;
                    $down_box .= $down_link;
                    $down_box .= '</div>';
                }
            } else {
                $down_buts = zibpay_get_post_down_buts($pay_mate, $paid_type, $post_id);
                $down_box = '<div class="hidden-box show"><div class="hidden-text"><i class="fa fa-download mr6" aria-hidden="true"></i>资源下载</div>' . $down_buts . '</div>';
                $down_box .= zibpay_get_demo_link($pay_mate, $post_id);
            }

            $down_box .= $attribute;
            break;

        case 5:
            //付费图库
            $gallery = '';
            $slide = '';
            $pay_gallery = $pay_mate['pay_gallery'];
            if ($pay_gallery) {
                $gallery_ids = explode(',', $pay_gallery);
                $all_count = count($gallery_ids);
                $i = 1;
                $attachment = '';
                foreach ((array)$gallery_ids as $id) {
                    $attachment = zib_get_attachment_image_src($id, _pz('thumb_postfirstimg_size'))[0];
                    $slide .= zibpay_get_posts_pay_gallery_box_image_slide($id, $i, $all_count, $pay_title, $attachment);
                    $i++;
                }
                $gallery .= '<div class="swiper-container swiper-scroll">';
                $gallery .= '<div class="swiper-wrapper">';
                $gallery .= $slide;
                $gallery .= '</div>';
                $gallery .= '<div class="swiper-button-prev"></div><div class="swiper-button-next"></div>';
                $gallery .= '</div>';

                $header_box = '<div class="relative-h paid-gallery">';
                $header_box .= '<div class="absolute blur-10 opacity3"><img class="fit-cover lazyload" src="' . zib_get_lazy_thumb() . '" data-src="' . esc_attr($attachment) . '" alt="付费图片-' . esc_attr($pay_title) . '"></div>';
                $header_box .= '<div style="margin-top: -20px;" class="relative mb6"><span class="badg b-theme badg-sm"> 共' . $all_count . '张图片 </span></div>';
                $header_box .= $gallery;
                $header_box .= '</div>';
            } else {
                $header_box = '<div class="b-red text-center" style="padding: 30px 10px;"><i class="fa fa-fw fa-info-circle mr10"></i>暂无图片内容，' . (is_super_admin() ? '请在后台添加' : '请与管理员联系') . '</div>';
            }

            break;
        case 6:
            //付费视频
            $video_url =  $pay_mate['video_url'];
            $video_pic =  $pay_mate['video_pic'];

            if ($video_url) {
                $header_box = zib_get_dplayer($video_url, $video_pic);
            } else {
                $header_box = '<div class="b-red text-center" style="padding: 30px 10px;"><i class="fa fa-fw fa-info-circle mr10"></i>暂无视频内容，' . (is_super_admin() ? '请在后台添加' : '请与管理员联系') . '</div>';
            }

            break;
    }


    //已支付模块
    if ($paid_type == 'free') {
        //免费
        $paid_box = '';
        $order_type_name = str_replace("付费", "免费", $order_type_name);
    } elseif ($paid_type == 'paid') {
        //已经购买
        $mark = _pz('pay_mark', '￥');
        $mark = '<span class="pay-mark">' . $mark . '</span>';
        $paid_info = '<div class="flex jsb"><span>订单号</span><span>' . zibpay_get_order_num_link($paid['order_num']) . '</span></div>';
        $paid_info .= '<div class="flex jsb"><span>支付时间</span><span>' . $paid['pay_time'] . '</span></div>';
        $paid_info .= '<div class="flex jsb"><span>支付金额</span><span>' .  $mark . round($pay_mate['pay_price'], 2) . '</span></div>';

        $paid_box .= '<div class="flex ac jb-green padding-10 em09">';
        $paid_box .= '<div class="text-center flex1"><div class="mb6"><i class="fa fa-shopping-bag fa-2x" aria-hidden="true"></i></div><b class="em12">' . $paid_name . '</b></div>';
        $paid_box .= '<div class="em09 paid-info flex1">' . $paid_info . '</div>';
        $paid_box .= '</div>';
    } elseif (stristr($paid_type, 'vip')) {
        //会员免费
        $paid_box .= '<div class="flex jsb ac payvip-icon box-body vipbg-v' . $paid['vip_level'] . '">';
        $paid_box .=  zibpay_get_show_price($pay_mate, $post_id);
        $paid_box .= '<div class="flex0"><b class="em12">' . zibpay_get_vip_icon($paid['vip_level'], 'mr10 em12') . $paid_name . '</b></div>';
        $paid_box .= '</div>';
    }

    //构建内容
    $html = '<div class="pay-box zib-widget paid-box ' . $order_type_class . '" id="posts-pay">';
    $html .= $header_box;
    $html .= $paid_box;

    $html .= '<div class="box-body relative">';
    $html .= $cuont;

    $html .= '<div><span class="badg c-red hollow badg-sm mr6">' . $order_type_name . '</span><b' . ($cuont ? ' style="padding-right: 50px;"' : '') . '>' . $pay_title . '</b></div>';
    $html .= $pay_doc ? '<div class="mt10">' . $pay_doc . '</div>' : '';
    $html .= $down_box;
    $html .= $pay_details;
    $html .= $pay_extra_hide;
    $html .= $service;
    $html .= '</div>';
    $html .= '</div>';
    return $html;
}



//文章购买模块
function zibpay_posts_pay_box($pay_mate = array(), $post_id = 0)
{
    if (!$pay_mate) {
        if (!$post_id) $post_id = get_the_ID();
        $pay_mate = get_post_meta($post_id, 'posts_zibpay', true);
    }

    if (empty($pay_mate['pay_type']) || $pay_mate['pay_type'] == 'no') return;

    $order_type_name = zibpay_get_pay_type_name($pay_mate['pay_type'], true);

    $cuont = '';
    $cuont_volume = zibpay_get_sales_volume($pay_mate, $post_id);
    if (_pz('pay_show_paycount', true) && $cuont_volume) {
        $cuont = '<badge class="img-badge hot jb-blue px12">已售 ' . $cuont_volume . '</badge>';
    }

    //会员价格
    $vip_price = zibpay_get_posts_vip_price($pay_mate);

    //标题
    $pay_title = zibpay_get_post_pay_title($pay_mate, $post_id);
    //价格
    $price = zibpay_get_show_price($pay_mate, $post_id, 'c-red');

    //简介
    $pay_doc = !empty($pay_mate['pay_doc']) ? $pay_mate['pay_doc'] : '此内容为' . zibpay_get_pay_type_name($pay_mate['pay_type']) . '，请付费后查看';

    //更多内容
    $pay_details = !empty($pay_mate['pay_details']) ? '<div class="pay-details">' . $pay_mate['pay_details'] . '</div>' : '';

    //商品属性
    $attribute = zibpay_get_product_attributes($pay_mate, $post_id);
    //购买按钮
    $pay_button = zibpay_get_pay_form_but($pay_mate, $post_id);
    //演示地址
    $demo_link = zibpay_get_demo_link($pay_mate, $post_id);
    //服务内容
    $service = zibpay_get_service('inline-block ml10');
    $service = $service ? '<div class="px12 muted-2-color mt10 text-right">' . $service . '</div>' : '';
    //付费类型
    $order_type_name = '<div class="pay-tag abs-center">' . $order_type_name . '</div>';

    //推广让利
    $discount_tag = zibpay_get_rebate_discount_tag();

    //左侧图片
    $product_graphic = '';
    $post_thumbnail = '';
    if ($pay_mate['pay_type'] != 5) {
        if ($pay_mate['pay_type'] == 6) {
            $video_pic = !empty($pay_mate['video_pic']) ? '<img class="fit-cover lazyload" src="' . zib_get_lazy_thumb() . '" data-src="' . esc_attr($pay_mate['video_pic']) . '" alt="付费视频-' . esc_attr($pay_title) . '">' : zib_post_thumbnail();
            $post_thumbnail = $video_pic;
            $post_thumbnail .= '<div class="absolute graphic-mask" style="opacity: 0.2;"></div>';
            $post_thumbnail .= '<div class="abs-center text-center"><i class="fa fa-play-circle-o fa-4x opacity8" aria-hidden="true"></i></div>';
        } else {
            $post_thumbnail = zib_post_thumbnail();
        }

        $product_graphic = '<div class="flex0 relative mr20 hide-sm pay-thumb"><div class="graphic">';
        $product_graphic .= $post_thumbnail;
        $product_graphic .= '<div class="abs-center text-center left-bottom">';
        $product_graphic .= $demo_link ? '<div class="">' . $demo_link . '</div>' : '';
        $product_graphic .= $discount_tag ? '<div class="padding-6 jb-red px12">' . $discount_tag . '</div>' : '';;
        $product_graphic .= '</div>';
        $product_graphic .= '</div></div>';
    } else {
        $product_graphic = zibpay_get_posts_pay_gallery_box($pay_mate);
    }

    //左侧图片结束
    $order_type_class = 'order-type-' . $pay_mate['pay_type'];
    $html = '<div class="zib-widget pay-box  ' . $order_type_class . '" id="posts-pay">';
    $html .= '<div class="flex pay-flexbox">';
    $html .= $product_graphic;
    $html .= '<div class="flex1 flex xx jsb">';
    $html .= '<dt class="text-ellipsis pay-title"' . ($cuont ? 'style="padding-right: 50px;"' : '') . '>' . $pay_title . '</dt>';
    $html .= '<div class="mt6 em09 muted-2-color">' . $pay_doc . '</div>';

    $html .= '<div class="price-box">' . $price . '</div>';
    $html .= $discount_tag ? '<div class="visible-xs-block badg c-red px12 mb6">' . $discount_tag . '</div>' : '';;
    $html .=  $vip_price ? '<div>' . $vip_price . '</div>' : '';
    $html .= '<div class="text-right mt10">' . $pay_button . '</div>';

    $html .= '';
    $html .= '</div>';
    $html .= '</div>';
    $html .= $service;
    $html .= $demo_link ? '<div class="mt10 visible-xs-block">' . $demo_link . '</div>' : '';;
    $html .= $attribute;
    $html .= $pay_details;
    $html .= $order_type_name;
    $html .= $cuont;
    $html .= '</div>';

    return $html;
}

function zibpay_get_posts_pay_gallery_box_image_slide($id, $i, $all_count, $pay_title = '', $attachment = '')
{

    $attachment = $attachment ? $attachment : zib_get_attachment_image_src($id, _pz('thumb_postfirstimg_size'))[0];
    $attachment_full = zib_get_attachment_image_src($id, 'full')[0];
    $slide = '<div class="swiper-slide mr10" style="width: 150px;">';
    $slide .= '<a data-imgbox="payimg" href="' . esc_url($attachment_full) . '">';
    $slide .= '<div class="graphic" style="padding-bottom: 100%!important;">';
    $slide .= '<img class="fit-cover lazyload" src="' . zib_get_lazy_thumb() . '" data-src="' . esc_attr($attachment) . '" data-full-url="' . esc_attr($attachment_full) . '" alt="付费图片-' . esc_attr($pay_title) . '">';
    $slide .= '<div class="abs-center right-top"><badge class="b-black opacity8 mr6 mt6">' . $i . '/' . $all_count . '</badge></div>';
    $slide .= '</div>';
    $slide .= '</a>';
    $slide .= '</div>';

    return $slide;
}


//构建付费图片盒子
function zibpay_get_posts_pay_gallery_box($pay_mate = array(), $post_id = 0)
{
    if (!$post_id) $post_id = get_the_ID();
    if (!$pay_mate) $pay_mate = get_post_meta($post_id, 'posts_zibpay', true);
    if ($pay_mate['pay_type'] != 5) return;

    $gallery = '';
    $slide = '';
    //推广让利
    $discount_tag = zibpay_get_rebate_discount_tag();
    //标题
    $pay_title = zibpay_get_post_pay_title($pay_mate, $post_id);

    $post_thumbnail = zib_post_thumbnail();

    if (!empty($pay_mate['pay_gallery'])) {
        $gallery_ids = explode(',', $pay_mate['pay_gallery']);
        $all_count = count($gallery_ids);

        $show = (int)$pay_mate['pay_gallery_show'];
        if ($show >= 1 && $all_count > $show) {
            $i = 1;
            $attachment = '';
            foreach ((array)$gallery_ids as $id) {
                if ($i > $show) {
                    break;
                }
                $attachment = zib_get_attachment_image_src($id, _pz('thumb_postfirstimg_size'))[0];
                $slide .= zibpay_get_posts_pay_gallery_box_image_slide($id, $i, $all_count, $pay_title, $attachment);
                $i++;
            }
            if ($i <= $all_count) {
                $slide .= '<div class="swiper-slide mr10" style="width: 150px;">';
                $slide .= '<div class="graphic" style="padding-bottom: 100%!important;">';
                $slide .= '<img class="fit-cover lazyload blur-10" src="' . zib_get_lazy_thumb() . '" data-src="' . esc_attr($attachment) . '" alt="付费图片-' . esc_attr($pay_title) . '">';
                $slide .= '<div class="absolute graphic-mask" style="opacity: 0.3;"></div>';
                $slide .= '<div class="abs-center text-center">请付费后查看</br>剩余' . ($all_count - $i + 1) . '张图片</div>';
                $slide .= '</div>';
                $slide .= '</div>';
            }

            $gallery = '';
            $gallery .= '<div class="swiper-container swiper-scroll">';
            $gallery .= '<div class="swiper-wrapper">';
            $gallery .= $slide;
            $gallery .= '</div>';
            $gallery .= '<div class="swiper-button-prev"></div><div class="swiper-button-next"></div>';
            $gallery .= '</div>';

            $product_graphic = '<div class="relative-h pay-gallery mr20 radius8 padding-10 flex ac">';
            $product_graphic .= $gallery;
            $product_graphic .= $discount_tag ? '<div class="padding-6 jb-red px12 abs-center text-center left-bottom hidden-xs">' . $discount_tag . '</div>' : '';;

            $product_graphic .= '</div>';
        } else {
            $post_thumbnail = zib_post_thumbnail();
            $product_graphic = '<div class="flex0 relative mr20 hide-sm pay-thumb"><div class="graphic">';
            $product_graphic .= '<div class="blur-10 absolute">' . $post_thumbnail . '</div>';
            $product_graphic .= '<div class="absolute graphic-mask" style="opacity: 0.3;"></div>';
            $product_graphic .= '<div class="abs-center text-center">共' . $all_count . '张图片</br>请付费后查看</div>';
            $product_graphic .= $discount_tag ? '<div class="padding-6 jb-red px12 abs-center text-center left-bottom">' . $discount_tag . '</div>' : '';;
            $product_graphic .= '</div></div>';
        }
    } else {
        $product_graphic = '<div class="flex0 relative mr20 hide-sm pay-thumb"><div class="graphic">';
        $product_graphic .= '<img class="fit-cover" src="' . zib_get_lazy_thumb() . '">';
        $product_graphic .= '<div class="absolute graphic-mask" style="opacity: 0.6;"></div>';
        $product_graphic .= '<div class="abs-center text-center">' . (is_super_admin() ? '暂无图片，请在后台添加付费图片' : '暂无可查看图片，请与站长联系') . '</div>';
        $product_graphic .= $discount_tag ? '<div class="padding-6 jb-red px12 abs-center text-center left-bottom">' . $discount_tag . '</div>' : '';;
        $product_graphic .= '</div></div>';
    }

    return $product_graphic;
}


//获取商品属性
function zibpay_get_product_attributes($pay_mate = array(), $post_id = 0, $class = 'flex jsb', $separator = '')
{
    if (!$post_id) $post_id = get_the_ID();
    if (!$pay_mate) $pay_mate = get_post_meta($post_id, 'posts_zibpay', true);
    if ($pay_mate['pay_type'] != 2 || empty($pay_mate['attributes'])) return;

    $attr_html = '';
    foreach ((array)$pay_mate['attributes'] as $attr) {
        if (!empty($attr['key']) && !empty($attr['value'])) {
            $attr_html .= '<div class="' . $class . '">';
            $attr_html .= '<span class="attr-key">' . $attr['key'] . '</span>' . $separator;
            $attr_html .= '<span class="attr-value">' . $attr['value'] . '</span>';
            $attr_html .= '</div>';
        }
    }

    $html = '<div class="pay-attr mt10">';
    $html .= $attr_html;
    $html .= '</div>';
    return $html;
}


//获取标题
function zibpay_get_post_pay_title($pay_mate = array(), $post_id = 0)
{
    if (!$pay_mate) {
        if (!$post_id) $post_id = get_the_ID();
        $pay_mate = get_post_meta($post_id, 'posts_zibpay', true);
    }

    $pay_title = !empty($pay_mate['pay_title']) ? $pay_mate['pay_title'] : get_the_title() . get_the_subtitle(false);
    return $pay_title;
}

//获取普通用户价格模块
function zibpay_get_show_price($pay_mate = array(), $post_id = 0, $class = 'px13')
{
    if (!$pay_mate) {
        if (!$post_id) $post_id = get_the_ID();
        $pay_mate = get_post_meta($post_id, 'posts_zibpay', true);
    }
    $mark = _pz('pay_mark', '￥');
    $mark = '<span class="pay-mark">' . $mark . '</span>';

    //价格
    $original_price = !empty($pay_mate['pay_original_price']) ? '<span class="original-price">' . $mark . round($pay_mate['pay_original_price'], 2) . '</span>' : '';
    //促销标签
    $promotion_tag = !empty($pay_mate['promotion_tag']) && !empty($pay_mate['pay_original_price']) ? '<badge>' . $pay_mate['promotion_tag'] . '</badge><br/>' : '';
    $original_price = $promotion_tag ? '<div class="inline-block ml10 text-left">' . $promotion_tag . $original_price . '</div>' : $original_price;
    $price = '<div class="' . $class . '"><b class="em3x">' . $mark . round($pay_mate['pay_price'], 2) . '</b>' . $original_price . '</div>';
    //限制购买
    $pay_limit = !empty($pay_mate['pay_limit']) ? (int)$pay_mate['pay_limit'] : '0';
    if ($pay_limit > 0  && (_pz('pay_user_vip_1_s', true) || _pz('pay_user_vip_2_s', true))) {
        $title = array(
            '1' => _pz('pay_user_vip_1_name') . '及以上会员可购买',
            '2' => '仅' . _pz('pay_user_vip_2_name') . '可购买',
        );
        $vip_icon = zib_svg('vip_' . $pay_limit, '0 0 1024 1024', 'mr3');

        $price = '<div class="' . $class . ' padding-h10"><b data-toggle="tooltip" title="' . $title[$pay_limit] . '" class="badg radius jb-vip' . $pay_limit . '" style="padding: 5px 20px;">' . $vip_icon . '会员专属资源</b></div>';
    }
    return  $price;
}

//获取带有form的购买按钮
function zibpay_get_pay_form_but($pay_mate = array(), $post_id = 0, $class = 'pay-button')
{
    if (!$post_id) $post_id = get_the_ID();
    if (!$pay_mate) $pay_mate = get_post_meta($post_id, 'posts_zibpay', true);

    $pay_button = '';
    $remind = '';

    //购买权限
    $user_id = get_current_user_id();
    $pay_limit = !empty($pay_mate['pay_limit']) ? (int)$pay_mate['pay_limit'] : '0';
    if (!is_user_logged_in()) {
        if (!_pz('pay_no_logged_in', true) || $pay_limit != '0') {
            if (zib_is_close_sign()) {
                $pay_button = '<span class="badg px12 c-yellow">登录功能已关闭，暂时无法购买</span>';
            } else {
                $pay_button = '<a href="javascript:;" class="but jb-blue signin-loader padding-lg"><i class="fa fa-sign-in mr10" aria-hidden="true"></i>登录购买</a>';
            }
        } else {
            $remind =  '<div class="pay-extra-hide px12 mt6" style="font-size:12px;">' . _pz('pay_no_logged_remind') . '</div>';
        }
    }

    //购买权限
    if ($pay_limit > 0  && (_pz('pay_user_vip_1_s', true) || _pz('pay_user_vip_2_s', true))) {
        $vip_icon = zib_svg('vip_' . $pay_limit, '0 0 1024 1024', 'mr3');;

        //开始限制购买权限
        $user_vip_level = zib_get_user_vip_level($user_id);

        if (!$user_vip_level) {
            $pay_vip_text = '开通会员';
        } else if ($user_vip_level < $pay_limit) {
            $pay_vip_text = '升级' . _pz('pay_user_vip_' . $pay_limit . '_name');
        }

        if (!$user_vip_level || $user_vip_level < $pay_limit) {
            $pay_button = '<div class="badg c-yellow em09 mb6"><i class="fa fa-fw fa-info-circle fa-fw mr6" aria-hidden="true"></i>您暂无购买权限，请先' . $pay_vip_text . '</div>';
            $pay_button .= '<a href="javascript:;" vip-level="' . $pay_limit . '" class="but btn-block jb-vip' . $pay_limit . ' pay-vip padding-lg">' . $vip_icon . $pay_vip_text . '</a>';
        }
    }
    if (!$pay_button) $pay_button = zibpay_get_initiate_pay_button();
    $order_type_name = zibpay_get_pay_type_name($pay_mate['pay_type']);
    $order_name = get_bloginfo('name') . '-' . $order_type_name;
    $html = '';
    $html .= '<form class="pay-form">';
    $html .= '<div class="' . $class . '">' . $pay_button . '</div>';
    $html .= $remind;
    $html .= '<input type="hidden" name="post_id" value="' . $post_id . '">';
    $html .= '<input type="hidden" name="order_name" value="' . $order_name . '">';
    $html .= '<input type="hidden" name="order_type" value="' . $pay_mate['pay_type'] . '">';
    $html .= '<input type="hidden" name="action" value="initiate_pay">';
    $html .= '</form>';
    return $html;
}

//获取付费类型的名称
function zibpay_get_pay_type_name($pay_type, $show_icon = false)
{
    $name = array(
        '1' => '付费阅读',
        '2' => '付费资源',
        '3' => '产品购买',
        '4' => '购买会员',
        '5' => '付费图片',
        '6' => '付费视频',
        '7' => '自动售卡',
    );
    if ($show_icon) {
        return zibpay_get_pay_type_icon($pay_type, 'mr3') . $name[$pay_type];
    } else {
        return $name[$pay_type];
    }
}

//获取付费类型的图标
function zibpay_get_pay_type_icon($pay_type, $class = '', $tip = false)
{
    $class = $class ? ' ' . $class : '';
    $icons = array(
        '1' => '<i class="fa fa-book' . $class . '"></i>',
        '2' => '<i class="fa fa-download' . $class . '"></i>',
        '3' => '<i class="fa fa-shopping-cart' . $class . '"></i>',
        '4' => '<i class="fa fa-diamond' . $class . '"></i>',
        '5' => '<i class="fa fa-file-image-o' . $class . '"></i>',
        '6' => '<i class="fa fa-play-circle' . $class . '"></i>',
        '7' => '<i class="fa fa-credit-card' . $class . '"></i>',
    );
    if ($tip) {
        return '<span title="' . zibpay_get_pay_type_name($pay_type) . '" data-toggle="tooltip">' . $icons[$pay_type] . '<span>';
    } else {
        return $icons[$pay_type];
    }
}

/**获取支付按钮html */
function zibpay_get_initiate_pay_button($text = '立即购买', $wechat_text = '微信购买', $alipay_text = '支付宝购买')
{
    $pay_wechat_sdk = _pz('pay_wechat_sdk_options');
    $pay_alipay_sdk = _pz('pay_alipay_sdk_options');

    $pay_button_args = array();
    $pay_dropdown_args = array();
    if ($pay_wechat_sdk && $pay_wechat_sdk != 'null') {
        $pay_button_args[] = '<button class="but jb-green initiate-pay" pay_type="wechat"><i class="fa fa-weixin" aria-hidden="true"></i>' . $wechat_text . '</button>';
        $pay_dropdown_args[] = '<li><a href="javascript:;" class="initiate-pay" pay_type="wechat"><i class="fa fa-weixin ml6" aria-hidden="true"></i>' . $wechat_text . '</a></li>';
    }
    if ($pay_alipay_sdk && $pay_alipay_sdk != 'null') {
        $pay_button_args[] = '<button class="but jb-blue initiate-pay" pay_type="alipay">' . zib_svg('alipay') . $alipay_text . '</button>';
        $pay_dropdown_args[] = '<li><a href="javascript:;" class="initiate-pay" pay_type="alipay">' . zib_svg('alipay') . $alipay_text . '</a></li>';
    }

    if (!$pay_button_args) {
        $pay_button = '<span class="badg px12 c-yellow-2">暂时无法购买，请与站长联系</span>';
        if (is_super_admin()) {
            $pay_button = '<a href="' . zib_get_admin_csf_url('商城付费') . '" class="but c-red mr6">请配置收款方式及收款接口</a>';
        }
    } elseif (wp_is_mobile() || _pz('pay_show_allbut')) {
        $pay_button = implode('', $pay_button_args);
        if (count($pay_button_args) > 1) {
            $pay_button = '<span class="but-group">' . $pay_button . '</span>';
        } else {
            $pay_button = '<div class="pay-button-block">' . $pay_button . '</div>';
        }
    } else {
        $payment = zibpay_get_default_payment();

        if (count($pay_button_args) > 1) {
            $drop = implode('', $pay_dropdown_args);

            $drop = '<ul class="dropdown-menu hover-show-con">' . $drop . '</ul>';
            $pay_button = '<div class="btn-group dropup hover-show pay-button-block">';
            $pay_button .= '<button type="button" class="but jb-red initiate-pay" pay_type="' . $payment . '"><i class="fa fa-angle-right" aria-hidden="true"></i>' . $text . '</button>';
            $pay_button .= '';
            $pay_button .= $drop;
            $pay_button .= '</div>';
        } else {
            $pay_button = '<button class="but jb-red initiate-pay" pay_type="' . $payment . '"><i class="fa fa-angle-right" aria-hidden="true"></i>' . $text . '</button>';
        }
    }
    return $pay_button;
}

/**获取默认支付方式 */
function zibpay_get_default_payment()
{
    $payment = _pz('default_payment', 'wechat');
    $pay_wechat_sdk = _pz('pay_wechat_sdk_options');
    $pay_alipay_sdk = _pz('pay_alipay_sdk_options');
    if ($payment == 'wechat' && (!$pay_wechat_sdk || $pay_wechat_sdk == 'null')) $payment = 'alipay';
    if ($payment == 'alipay' && (!$pay_alipay_sdk || $pay_alipay_sdk == 'null')) $payment = 'wechat';

    return $payment;
}


function zibpay_get_posts_vip_price($pay_mate, $hide = 0)
{

    if (zib_is_close_sign()) return;
    $mark = _pz('pay_mark', '￥');
    $mark = '<span class="em09">' . $mark . '</span>';
    $user_id = get_current_user_id();
    $action_class = $user_id ? '' : ' signin-loader';
    $vip_level = $user_id ? zib_get_user_vip_level($user_id) : false;

    $vip_price_con = array();
    $price = isset($pay_mate['pay_price']) ? round($pay_mate['pay_price'], 2) : 0;

    for ($vi = 1; $vi <= 2; $vi++) {
        if (!_pz('pay_user_vip_' . $vi . '_s', true) || $hide == $vi) {
            continue;
        }
        $vip_price = !empty($pay_mate['vip_' . $vi . '_price']) ? round($pay_mate['vip_' . $vi . '_price'], 2) : 0;
        //会员价格与正常价格取最小值
        $vip_price = $vip_price < $price ? $vip_price : $price;

        $vip_price = $vip_price ? '<span class="px12">' . $mark . '</span>' . $vip_price : '免费';
        $vip_price = '<span class="em12">' . $vip_price . '</span>';
        $vip_icon = zib_svg('vip_' . $vi, '0 0 1024 1024', 'mr3') . _pz('pay_user_vip_' . $vi . '_name');

        //action_class
        if ($user_id && (!$vip_level || $vip_level < $vi)) {
            $action_class = ' pay-vip';
        }

        if ($action_class) {
            $vip_price_con[] = '<span href="javascript:;" class="but vip-price ' . $action_class . '" vip-level="' . $vi . '" data-toggle="tooltip" title="开通' . _pz('pay_user_vip_' . $vi . '_name') . '">' . $vip_icon  . $vip_price . '</span>';
        } else {
            $vip_price_con[] = '<span class="but vip-price" vip-level="' . $vi . '">' . $vip_icon  . $vip_price . '</span>';
        }
    }
    $vip_price_html = implode('', $vip_price_con);
    if (count($vip_price_con) > 1) {
        $vip_price_html = '<span class="but-group">' . $vip_price_html . '</span>';
    }

    return $vip_price_html;
}


/**扫码付款模态框 */
function zibpay_qrcon_pay_modal($args = array())
{
    $defaults = array(
        'class' => '',
        'payment' => 'wechat',
        'order_price' => '<i class="loading px12"></i>',
        'order_name' => '<i class="placeholder s1" style=" height: 18px; width: 60%; "></i>',
        'user_vip' => zib_get_user_vip_level(),
    );
    $args = wp_parse_args((array) $args, $defaults);

    $class = 'pay-payment ' . $args['payment'];
    $class .= ' ' . $args['class'];
    $pay_wechat_sdk = _pz('pay_wechat_sdk_options');
    $pay_alipay_sdk = _pz('pay_alipay_sdk_options');
    $pay_switch_button = '';
    if ($pay_alipay_sdk && $pay_alipay_sdk != 'null') {
        $pay_switch_button .= '<button class="but c-blue btn-block hollow t-alipay initiate-pay-switch" pay_type="alipay">切换支付宝付款</button>';
    }
    if ($pay_wechat_sdk && $pay_wechat_sdk != 'null') {
        $pay_switch_button .= '<button class="but c-green btn-block hollow t-wechat initiate-pay-switch" pay_type="wechat">切换微信付款</button>';
    }

    $alipay_sys = get_template_directory_uri() . '/zibpay/assets/img/alipay-sys.png';
    $wechat_sys = get_template_directory_uri() . '/zibpay/assets/img/wechat-sys.png';

    $qrcode_defaults = get_template_directory_uri() . '/zibpay/assets/img/pay-qrcode.png';

    $vip_tag = $args['user_vip'] ? '<span data-toggle="tooltip" title="' . _pz('pay_user_vip_' . $args['user_vip'] . '_name') . '" class="mr6">' . zibpay_get_vip_icon($args['user_vip']) . '</span>' : '';

    $con = '<div class="modal fade" id="modal_pay" tabindex="-1" role="dialog" aria-hidden="false">
        <div class="modal-dialog" role="document">
            <div class="' . $class . '">
                <div class="modal-body modal-pay-body">
                    <div class="row-5 hide-sm">
                        <img class="pay-sys lazyload t-wechat" data-src="' . $alipay_sys . '">
                        <img class="pay-sys lazyload t-alipay" data-src="' . $wechat_sys . '">
                    </div>
                    <div class="row-5">
                    <div class="pay-qrcon">
                        <div class="qrcon">
                            <div class="pay-logo-header theme-box"><span class="pay-logo"></span><span class="pay-logo-name t-wechat">支付宝</span><span class="pay-logo-name t-alipay">微信支付</span></div>
                            <div class="pay-title em09 muted-2-color">' . $args['order_name'] . '</div>
                            <div>' . $vip_tag . '<span class="em09">￥</span><span class="pay-price em12">' . $args['order_price'] . '</span></div>
                            <div class="pay-qrcode">
                                <img src="' . $qrcode_defaults . '">
                            </div>
                        </div>
                    <div class="pay-switch">' . $pay_switch_button . '</div>
                    <div class="pay-notice"><div class="notice load">正在生成订单，请稍候</div></div>
                    </div>
				</div>
                </div>
            </div>
        </div>
    </div>';
    return $con;
}


/**前台载入js文件 */
function zibpay_load_scripts()
{
    // wp_enqueue_script('zibpay', get_template_directory_uri() . '/zibpay/assets/js/pay.min.js', array('jquery'), THEME_VERSION, true);
    wp_localize_script('zibpay', 'zibpay_ajax_url', admin_url("admin-ajax.php"));
}
//add_action('wp_enqueue_scripts', 'zibpay_load_scripts');

/**后台生成二维码图片 */
function zibpay_get_Qrcode($url)
{
    //引入phpqrcode类库
    require_once plugin_dir_path(__FILE__) . 'class/qrcode.class.php';
    $errorCorrectionLevel = 'L'; //容错级别
    $matrixPointSize      = 6; //生成图片大小
    ob_start();
    QRcode::png($url, false, $errorCorrectionLevel, $matrixPointSize, 2);
    $data = ob_get_contents();
    ob_end_clean();

    $imageString = base64_encode($data);
    header("content-type:application/json; charset=utf-8");
    return 'data:image/jpeg;base64,' . $imageString;
}

/**获取支付参数函数 */
function zibpay_get_payconfig($type)
{
    $defaults = array();
    $defaults['xunhupay'] = array(
        'wechat_appid'     => '',
        'wechat_appsecret' => '',
        'alipay_appid'     => '',
        'alipay_appsecret' => '',
    );
    $defaults['official_wechat'] =  array(
        'merchantid'     => '',
        'appid'     => '',
        'key' => '',
        'jsapi' => '',
        'h5' => '',
    );
    $defaults['official_alipay'] =  array(
        'appid'     => '',
        'privatekey' => '',
        'publickey' => '',
        'pid'     => '',
        'md5key'     => '',
        'webappid'     => '',
        'webprivatekey'     => '',
        'h5' => '',
    );
    $defaults['codepay'] =  array(
        'id'     => '',
        'key' => '',
        'token' => '',
    );
    $defaults['payjs'] =  array(
        'mchid'     => '',
        'key' => '',
    );
    $defaults['xhpay'] =  array(
        'mchid'     => '',
        'key' => '',
    );
    $defaults['epay'] =  array(
        'apiurl'     => '',
        'partner' => '',
        'key' => '',
        'qrcode' => true,
    );
    return wp_parse_args((array)_pz($type), $defaults[$type]);;
}

/**根据订单号获取链接 */
function zibpay_get_order_num_link($order_num, $class = '')
{
    $href = '';
    $user_id = get_current_user_id();
    if ($user_id) {
        $href = get_author_posts_url($user_id) . '?page=pay';
    }
    $a = '<a target="_blank" href="' . $href . '" class="' . $class . '">' . $order_num . '</a>';
    if ($href) {
        return $a;
    } else {
        return '<span class="' . $class . '">' . $order_num . '</span>';
    }
}

/**判断是否在微信APP内 */
function zibpay_is_wechat_app()
{
    return strripos($_SERVER['HTTP_USER_AGENT'], 'micromessenger');
}

/**查看权限转文字 */
function zibpay_get_paid_type_name($paid_type)
{
    $paid_name = array(
        'free' => '免费内容',
        'paid' => '已购买',
        'vip1_free' => _pz('pay_user_vip_1_name') . '免费',
        'vip2_free' => _pz('pay_user_vip_2_name') . '免费',
    );

    return $paid_name[$paid_type];
}

/**判断查查看权限 */
function zibpay_is_paid($post_id, $user_id = '', $product_id = '')
{
    // 准备判断参数
    if (!$post_id) return false;
    if (!$user_id) $user_id = get_current_user_id();
    $posts_pay = get_post_meta($post_id, 'posts_zibpay', true);
    if (empty($posts_pay['pay_price'])) {
        $pay_order = array('paid_type' => 'free');
        return $pay_order;
    }

    $vip_level = zib_get_user_vip_level($user_id);
    if ($vip_level && empty($posts_pay['vip_' . $vip_level . '_price'])) {
        $pay_order = array('paid_type' => 'vip' . $vip_level . '_free', 'vip_level' => $vip_level);
        return $pay_order;
    }

    global $wpdb;

    if ($user_id) {
        // 如果已经登录，根据用户id查找数据库订单
        $pay_order = $wpdb->get_row("SELECT * FROM $wpdb->zibpay_order where user_id=$user_id and post_id=$post_id and status=1");
        if ($pay_order) {
            $pay_order = (array) $pay_order;
            $pay_order['paid_type'] = 'paid';
            return $pay_order;
        }
    }
    // 如果未登录，
    //根据浏览器Cookie查找
    if (isset($_COOKIE['zibpay_' . $post_id])) {
        $pay_order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->zibpay_order} WHERE order_num = %s and post_id=%d and status=1", $_COOKIE['zibpay_' . $post_id], $post_id));

        if ($pay_order) {
            $pay_order = (array) $pay_order;
            $pay_order['paid_type'] = 'paid';
            return $pay_order;
        }
    } else {
        //根据IP地址查找


    }
    return false;
}

/**判断是否已经存在订单 */
function zibpay_is_order_exists($post_id, $user_id = '', $product_id = '')
{
    // 准备判断参数
    if (!$user_id) $user_id = get_current_user_id();
    global $wpdb;

    if ($user_id) {
        // 如果已经登录，根据用户id查找数据库订单
        $pay_order = $wpdb->get_row("SELECT * FROM $wpdb->zibpay_order where user_id=$user_id and post_id=$post_id");
        if ($pay_order) return $pay_order;
    }
    // 如果未登录，
    //根据浏览器Cookie查找
    if (isset($_COOKIE['zibpay_' . $post_id])) {
        $pay_order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->zibpay_order} WHERE order_num = %s and post_id=%d", $_COOKIE['zibpay_' . $post_id], $post_id));

        if ($pay_order) return $pay_order;
    } else {
        //根据IP地址查找
    }
    return false;
}


/**创建编辑器短代码 */
//添加隐藏内容，付费可见
function zibpay_to_show($atts, $content = null)
{

    $a = '#posts-pay';
    $_hide = '<div class="hidden-box"><a class="hidden-text" href="javascript:(scrollTo(\'' . $a . '\',-120));"><i class="fa fa-exclamation-circle"></i>&nbsp;&nbsp;此处内容已隐藏，请付费后查看</a></div>';
    global $post;

    $pay_mate = get_post_meta($post->ID, 'posts_zibpay', true);

    $paid = zibpay_is_paid($post->ID);
    /**如果未设置付费阅读功能，则直接显示 */
    if (empty($pay_mate['pay_type']) || $pay_mate['pay_type'] != '1') return  $content;
    /**
     * 判断逻辑
     * 1. 管理登录
     * 2. 已经付费
     * 3. 必须设置了付费阅读
     */
    if (is_super_admin()) {
        return '<div class="hidden-box show"><div class="hidden-text">本文隐藏内容 - 管理员可见</div>' . do_shortcode($content) . '</div>';
    } elseif ($paid) {
        $paid_name = zibpay_get_paid_type_name($paid['paid_type']);
        return '<div class="hidden-box show"><div class="hidden-text">本文隐藏内容 - ' . $paid_name . '</div>' . do_shortcode($content) . '</div>';
    } else {
        return  $_hide;
    }
}
add_shortcode('payshow', 'zibpay_to_show');


/**
 *页码加载
 */
function zibpay_admin_pagenavi($total_count, $number_per_page = 15)
{
    $current_page = isset($_GET['paged']) ? $_GET['paged'] : 1;

    if (isset($_GET['paged'])) {
        unset($_GET['paged']);
    }

    $total_pages    = ceil($total_count / $number_per_page);

    $first_page_url    = add_query_arg('paged', 1);
    $last_page_url    = add_query_arg('paged', $total_pages);

    if ($current_page > 1 && $current_page < $total_pages) {
        $prev_page        = $current_page - 1;
        $prev_page_url    = add_query_arg('paged', $prev_page);

        $next_page        = $current_page + 1;
        $next_page_url    = add_query_arg('paged', $next_page);
    } elseif ($current_page == 1) {
        $prev_page_url    = '#';
        $first_page_url    = '#';
        if ($total_pages > 1) {
            $next_page        = $current_page + 1;
            $next_page_url    = add_query_arg('paged', $next_page);
        } else {
            $next_page_url    = '#';
            $last_page_url    = '#';
        }
    } elseif ($current_page == $total_pages) {
        $prev_page        = $current_page - 1;
        $prev_page_url    = add_query_arg('paged', $prev_page);
        $next_page_url    = '#';
        $last_page_url    = '#';
    }
?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num">每页 <?php echo $number_per_page; ?> 共 <?php echo $total_count; ?></span>
            <span class="pagination-links">
                <a class="first-page button <?php if ($current_page == 1) echo 'disabled'; ?>" title="前往第一页" href="<?php echo $first_page_url; ?>">«</a>
                <a class="prev-page button <?php if ($current_page == 1) echo 'disabled'; ?>" title="前往上一页" href="<?php echo $prev_page_url; ?>">‹</a>
                <span class="paging-input">第 <?php echo $current_page; ?> 页，共 <span class="total-pages"><?php echo $total_pages; ?></span> 页</span>
                <a class="next-page button <?php if ($current_page == $total_pages) echo 'disabled'; ?>" title="前往下一页" href="<?php echo $next_page_url; ?>">›</a>
                <a class="last-page button <?php if ($current_page == $total_pages) echo 'disabled'; ?>" title="前往最后一页" href="<?php echo $last_page_url; ?>">»</a>
            </span>
        </div>
        <br class="clear">
    </div>
<?php
}


/**
 * @description: 给页面底部添加空白支付模态框
 * @param {*}
 * @return {*}
 */
add_action('wp_footer', 'zibpay_show_pay_modal');
function zibpay_show_pay_modal()
{

    $payment = zibpay_get_default_payment();
    $pay_moda_args = array(
        'payment' => $payment,
    );

    echo zibpay_qrcon_pay_modal($pay_moda_args);
}


//支付成之后，更新商品销量meta
function zibpay_update_posts_meta($pay_order)
{
    /**根据订单号查询订单 */
    $pay_order = (array)$pay_order;
    $post_id = $pay_order['post_id'];

    if ($post_id) {
        $pay_mate = get_post_meta($post_id, 'posts_zibpay', true);
        $cuont = 0;
        global $wpdb;
        $cuont = $wpdb->get_var("SELECT COUNT(id) FROM $wpdb->zibpay_order where post_id=$post_id and status=1");
        $cuont = !empty((int)$pay_mate['pay_cuont']) ? (int)$pay_mate['pay_cuont'] + $cuont : $cuont;
        $cuont = $cuont > 0 ? $cuont : 0;
        update_post_meta($post_id, 'sales_volume', $cuont);
    }
}
add_action('payment_order_success', 'zibpay_update_posts_meta');
