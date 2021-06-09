<?php

/*
 *                        _oo0oo_
 *                       o8888888o
 *                       88" . "88
 *                       (| -_- |)
 *                       0\  =  /0
 *                     ___/`---'\___
 *                   .' \\|     |// '.
 *                  / \\|||  :  |||// \
 *                 / _||||| -:- |||||- \
 *                |   | \\\  - /// |   |
 *                | \_|  ''\---/''  |_/ |
 *                \  .-\__  '-'  ___/-. /
 *              ___'. .'  /--.--\  `. .'___
 *           ."" '<  `.___\_<|>_/___.' >' "".
 *          | | :  `- \`.;`\ _ /`;.`/ - ` : | |
 *          \  \ `_.   \_ __\ /__ _/   .-` /  /
 *      =====`-.____`.___ \_____/___.-`___.-'=====
 *                        `=---='
 *
 *
 *      ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 *
 *            佛祖保佑       永不宕机     永无BUG
 *
 */


/*
 * @Author        : Qinver
 * @Url           : zibll.com
 * @Date          : 2020-11-11 11:35:21
 * @LastEditTime: 2020-12-23 22:31:32
 * @Email         : 770349780@qq.com
 * @Project       : Zibll子比主题
 * @Description   : 一款极其优雅的Wordpress主题
 * @Read me       : 感谢您使用子比主题，主题源码有详细的注释，支持二次开发。欢迎各位朋友与我相互交流。
 * @Remind        : 使用盗版主题会存在各种未知风险。支持正版，从我做起！
 */

//定义常量
define('ZIB_STYLESHEET_DIRECTORY_URI', get_stylesheet_directory_uri());
define('ZIB_UPDATE_FILE', get_theme_file_path('/inc/options/zib-update.php'));

//载入文件
$require_once = array(
	'vendor/autoload.php',
	'inc/class/class.php',
	'inc/codestar-framework/codestar-framework.php',
	'inc/options/options.php',
	'inc/functions/functions.php',
	'inc/widgets/widget-index.php',
	'oauth/oauth.php',
	'zibpay/functions.php',
	'action/function.php',
	'inc/csf-framework/classes/zib-csf.class.php',
);

foreach ($require_once as $require) {
	require_once get_theme_file_path('/' . $require);
}


//codestar演示
//require_once get_theme_file_path('/inc/codestar-framework/samples/admin-options.php');

/**
 * @description: 根据页面模板获取页面链接
 * @param {*} $template
 * @return {*}
 */
function zib_get_template_page_url($template)
{
	$cache = wp_cache_get($template, 'page_url', true);
	if ($cache) return $cache;
	$templates = array(
		'pages/newposts.php'   => array('发布文章', 'newposts'),
		'pages/user-sign.php' => array('登录/注册/找回密码', 'user-sign'),
		'pages/download.php' => array('资源下载', 'download'),
	);
	$pages_args = array(
		'meta_key' => '_wp_page_template',
		'meta_value' => $template
	);
	$pages = get_pages($pages_args);
	$page_id = 0;
	if (!empty($pages[0]->ID)) {
		$page_id = $pages[0]->ID;
	} elseif (!empty($templates[$template][0])) {
		$one_page = array(
			'post_title'  => $templates[$template][0],
			'post_name'   => $templates[$template][1],
			'post_status' => 'publish',
			'post_type'   => 'page',
			'post_author' => 1,
		);

		$page_id = wp_insert_post($one_page);
		update_post_meta($page_id, '_wp_page_template', $template);
	}
	if ($page_id) {
		$url = get_permalink($page_id);
		wp_cache_set($template, $url, 'page_url');
		return $url;
	} else {
		return false;
	}
}
