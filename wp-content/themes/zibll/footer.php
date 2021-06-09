<footer class="footer">
	<?php if (function_exists('dynamic_sidebar')) {
		dynamic_sidebar('all_footer');
	} ?>
	<div class="container-fluid container-footer">
		<?php do_action('zib_footer_conter'); ?>
	</div>
</footer>
<?php
wp_footer();
?>
</body>
</html>