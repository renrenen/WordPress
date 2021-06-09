<?php
$s = strip_tags($s);
//保存搜索历史
if (_new_strlen($s) >= 2) {
	$old_k = !empty($_COOKIE["history_search"]) ? json_decode(stripslashes($_COOKIE["history_search"])) : array();
	if (!is_array($old_k)) $old_k = array();
	if (in_array($s, $old_k)) {
		foreach ($old_k as $k=>$v) {
			if ($v == $s) {
				unset($old_k[$k]);
			}
		}
	}
	array_unshift($old_k, $s);
	$max_num = (int) _pz('search_popular_key_num', 20);
	$keywords = array_slice($old_k, 0, $max_num, true);
	setcookie('history_search', json_encode($old_k), time() + 3600 * 24 * 30, '/', '', false);
}
get_header();
?>
<?php if (function_exists('dynamic_sidebar')) {
	echo '<div class="container fluid-widget">';
	dynamic_sidebar('all_top_fluid');
	dynamic_sidebar('search_top_fluid');
	echo '</div>';
}

?>
<main class="container">
	<div class="content-wrap">
		<div class="content-layout">
			<?php if (function_exists('dynamic_sidebar')) {
				dynamic_sidebar('search_top_content');
			}
			?>
			<div class="ajaxpager">
				<div class="main-bg theme-box box-body radius8 main-shadow">
					<div class="title-h-left"><b>搜索精彩内容</b></div>
					<?php zib_get_search(); ?>
				</div>

				<?php
				if (!have_posts()) {
					echo '<div class="zib-widget">';
					echo zib_get_null('未找到相关结果','60', 'null-search.svg','','300');
					echo '</div>';
				} else {
					$tt = '全部内容';
					if ($s) {
						/**保存搜索关键词 */
						zib_update_search_keywords(esc_attr($s));
						$tt = '包含"<b class="focus-color search-key">' . esc_attr($s)  . '</b>"的全部内容';
					}
					if ($cat) {
						$cat_a = get_category($cat);
						if ($cat_a) {
							$tt = '在分类<b class="focus-color">"' . $cat_a->cat_name . '"</b>中' . $tt;
						}
					}
					echo '<div class="main-bg theme-box radius8 main-shadow overflow-hidden">';
					echo '<div class="box-body nobottom"><div class="title-h-left">' . $tt . '</div></div>';
					$args = array(
						'no_margin' => true,
						'is_card' => false,
					);
					zib_posts_list($args);
					zib_paging();
					echo '</div>';
				}
				echo '</div>';
				?>
				<?php if (function_exists('dynamic_sidebar')) {
					dynamic_sidebar('search_bottom_content');
				}
				?>
			</div>
		</div>
		<?php get_sidebar(); ?>
</main>
<?php if (function_exists('dynamic_sidebar')) {
	echo '<div class="container fluid-widget">';
	dynamic_sidebar('search_bottom_fluid');
	dynamic_sidebar('all_bottom_fluid');
	echo '</div>';
}
?>
<?php get_footer();
