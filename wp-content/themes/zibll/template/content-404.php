<section>
	<div class="f404">
		<img src="<?php echo ZIB_STYLESHEET_DIRECTORY_URI ?>/img/404.svg">
		<p class="muted-2-color box-body separator" style="margin:50px 0;">未找到相关内容</p>
	</div>
	<div class="theme-box box-body">
		<?php
		$args = array(
			'class' => '',
			'show_keywords' => true,
			'show_history' => true,
			'keywords_title' => _pz('search_popular_title', '热门搜索'),
			'placeholder' => _pz('search_placeholder', '开启精彩搜索'),
			'show_input_cat' => true,
			'show_more_cat' => true,
			'show_posts' => true,
		);
		zib_get_search($args);
		?>
	</div>
</section>