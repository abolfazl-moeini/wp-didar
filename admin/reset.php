<div class="wrap">
	<h2>ریست وبسرویس</h2>
	<p><?php _e('You can rest didar webservice on this section with 2 type','didar'); ?></p>
	<p><?php _e('1.Reset webservice and start send order info from begin order if you leave input empty or  zero','didar'); ?></p>
	<p><?php _e('2.Reset webservice and start send order from order id you enter in input text','didar'); ?></p>
<?php
global $wpdb;	
if(isset($_POST['reset'])){
	if(is_numeric($_POST['order_id']))
		$wpdb->query("delete from {$wpdb->postmeta} where meta_key in('didar_id','didar_msg') and post_id>='{$_POST['order_id']}'");
	else
		$wpdb->query("delete from {$wpdb->postmeta} where meta_key in('didar_id','didar_msg')");
	echo '<div class="notice notice-success is-dismissible"><p>'.__('didar webservice reset successfull.','didar').'</p></div>';
}
$id = $wpdb->get_var("select ifnull(post_id,0) from $wpdb->postmeta where meta_key='didar_id' order by post_id desc limit 1");
?>
	<style>
		fieldset{
			padding:10px 10px 12px 10px;
			border:1px solid silver;
			border-radius:4px;
		}
	</style>
	<form method="post">
		<fieldset>
			<legend>ریست وبسرویس</legend>
			<label><?php _e('Start send order from ID','didar'); ?></label>
			<input type="number" name="order_id" dir="ltr" size="7" value="<?php echo $id; ?>" placeholder="order ID"> 
			<button type="submit" name="reset" class="button" onclick="confirm('<?php _e('Are you sure reset webservice?','didar'); ?>')"><?php _e('Reset','didar'); ?></button>
		</fieldset>
	</form>
</div>