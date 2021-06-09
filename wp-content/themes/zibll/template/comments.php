<?php
defined('ABSPATH') or die('无法直接加载此文件.');
$post_id = get_queried_object_id();
if (!comments_open($post_id) || _pz('close_comments')) return;

/*global $comment_ids; $comment_ids = array();
foreach ( $comments as $comment ) {
	if (get_comment_type() == "comment") {
		$comment_ids[get_comment_id()] = ++$comment_i;
	}
} */


$count_t = $post->comment_count;
$user_id = get_current_user_id();

$closeTimer = (strtotime(current_time('Y-m-d G:i:s')) - strtotime(get_the_time('Y-m-d G:i:s'))) / 86400;
?>
<div class="theme-box" id="comments">
	<div class="box-body notop">
		<div class="title-theme"><?php echo _pz('comment_title') ?>
			<?php echo $count_t ? '<small>共' . $count_t . '条</small>' : '<small>抢沙发</small>'; ?></div>
	</div>

	<div class="no_webshot main-bg theme-box box-body radius8 main-shadow">
		<?php if (get_option('comment_registration') && !is_user_logged_in()) { ?>
			<?php if (!zib_is_close_sign()) { ?>
				<div class="comment-signarea text-center box-body radius8">
					<h3 class="text-muted em12 theme-box muted-3-color">请登录后发表评论</h3>
					<p>
						<a href="javascript:;" class="signin-loader but c-blue padding-lg"><i class="fa fa-fw fa-sign-in mr10" aria-hidden="true"></i>登录</a>
						<a href="javascript:;" class="signup-loader ml10 but c-yellow padding-lg"><i data-class="icon mr10" data-viewbox="0 0 1024 1024" data-svg="signup" aria-hidden="true"></i>注册</a>
					</p>
					<?php zib_social_login(); ?>
				</div>
			<?php } ?>
		<?php } elseif (get_option('close_comments_for_old_posts') && $closeTimer > get_option('close_comments_days_old')) { ?>
			<div class="comment-signarea text-center box-body">
				<div class="text-muted em12 separator">文章评论已关闭</div>
			</div>
		<?php } else { ?>
			<div id="respond">
				<form action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" method="post" id="commentform">

					<ul class="list-inline">
						<li class="comt-title text-center">
							<?php

							if ($user_id) {
								global $current_user;
								echo '<div class="comt-avatar">' . zib_get_data_avatar($user_id) . '</div>';
								echo '<p class="text-ellipsis muted-2-color">' . zibpay_get_vip_icon(zib_get_user_vip_level($user_id)) . ' ' . $user_identity . '</p>';
							} else {
								echo '<div class="comt-avatar">' . zib_get_data_avatar($user_id) . '</div>';
								echo '<p class="" data-toggle-class="open" data-target="#comment-user-info" data-toggle="tooltip" title="填写用户信息">' . ($comment_author ? $comment_author : '昵称') . '</p>';
							}
							?>
						</li>
						<li class="comt-box">
							<textarea placeholder="<?php echo esc_attr(_pz('comment_text'))  ?>" class="form-control grin" name="comment" id="comment" cols="100%" rows="4" tabindex="1" onkeydown="if(event.ctrlKey&amp;&amp;event.keyCode==13){document.getElementById('submit').click();return false};"></textarea>
							<div class="comt-ctrl relative">
								<div class="comt-tips">
									<?php
									comment_id_fields();
									do_action('comment_form', $post->ID);
									?>
								</div>
								<div class="comt-tips-right pull-right">
									<a class="but c-red" id="cancel-comment-reply-link" href="javascript:;">取消</a>
									<button type="submit" class="but c-blue" name="submit" id="submit" tabindex="5"><?php echo _pz('comment_submit_text') ? _pz('comment_submit_text') : '提交评论' ?></button>
								</div>
								<div class="comt-tips-left">
									<?php
									if (!$user_id) {
										$require_name_email = get_option('require_name_email');
										$o_t = $require_name_email ? '(必填)' : '';
										echo '<span class="dropup relative" id="comment-user-info"' . ($require_name_email ? ' require_name_email="true"' : '') . '>';
										echo '<a class="but mr6" data-toggle-class="open" data-target="#comment-user-info" href="javascript:;"><i class="fa fa-fw fa-user"></i><span class="hide-sm">昵称</span></a>';
										echo '<div class="dropdown-menu box-body" style="width:250px;">';
										echo '<div class="mb20">';
										echo '<p>请填写用户信息：</p>';
										echo '<ul>';
										echo '<li class="line-form mb10">';
										echo '<input type="text" name="author" class="line-form-input" tabindex="1" value="' . esc_attr($comment_author) . '" placeholder="">';
										echo '<div class="scale-placeholder">昵称' . $o_t . '</div>';
										echo '<div class="abs-right muted-color"><i class="fa fa-fw fa-user"></i></div>';
										echo '<i class="line-form-line"></i>';
										echo '</li>';
										echo '<li class="line-form">';
										echo '<input type="text" name="email" class="line-form-input" tabindex="2" value="' . esc_attr($comment_author_email) . '" placeholder="">';
										echo '<div class="scale-placeholder">邮箱' . $o_t . '</div>';
										echo '<div class="abs-right muted-color"><i class="fa fa-fw fa-envelope-o"></i></div>';
										echo '<i class="line-form-line"></i>';
										echo '</li>';
										echo '</ul>';
										echo '</div>';
										zib_social_login();
										echo '</div>';
										echo '</span>';
									}
									if (_pz('comment_smilie')) {
										echo zib_get_input_expand_but('smilie');
									}
									if (_pz('comment_code')) {
										echo zib_get_input_expand_but('code');
									}
									if (_pz('comment_img')) {
										echo zib_get_input_expand_but('image', _pz("comment_upload_img", false));
									}
									?>
								</div>
							</div>
						</li>
					</ul>
				</form>
			</div>
		<?php } ?>
		<?php
		if (have_comments()) {
		?>
			<div id="postcomments">
				<ol class="commentlist list-unstyled">
					<?php
					wp_list_comments('type=comment&callback=zib_comments_list');
					?>
				</ol>
				<?php if (paginate_comments_links('type=array')) { ?>
					<div class="pagenav">
						<?php paginate_comments_links('prev_text=上一页&next_text=下一页'); ?>
					</div>
					<div class="comment post_ajax_loader" style="display: none;">
						<ul class="list-inline" style="display:flex;">
							<li>
								<div class="comt-avatar placeholder radius"></div>
							</li>
							<li class="comt-main">
								<div class="placeholder s1" style="width: 30%;"></div>
								<div class="placeholder k2 mb10"></div><i class="placeholder s1"></i><i class="placeholder s1 ml10"></i>
							</li>
						</ul>
						<ul class="list-inline" style="display:flex;">
							<li>
								<div class="comt-avatar placeholder radius"></div>
							</li>
							<li class="comt-main">
								<div class="placeholder s1" style="width: 30%;"></div>
								<div class="placeholder k2 mb10"></div><i class="placeholder s1"></i><i class="placeholder s1 ml10"></i>
							</li>
						</ul>
						<ul class="list-inline" style="display:flex;">
							<li>
								<div class="comt-avatar placeholder radius"></div>
							</li>
							<li class="comt-main">
								<div class="placeholder s1" style="width: 30%;"></div>
								<div class="placeholder k2 mb10"></div><i class="placeholder s1"></i><i class="placeholder s1 ml10"></i>
							</li>
						</ul>
					</div>
				<?php } ?>
			</div>
		<?php } ?>
	</div>
</div>