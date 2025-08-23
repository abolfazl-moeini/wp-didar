<div class="wrap">
	<h1><?php esc_attr_e('Send to Didar', 'didar'); ?></h1>
	<p><?php esc_attr_e('Send all', 'didar'); ?> <strong><?php esc_attr_e('Completed invoices', 'didar'); ?></strong> <?php esc_attr_e('to Didar', 'didar'); ?></p>
	<?php
	global $wpdb;
	$opt    = get_option( 'did_option', [] );
	$status = implode("','", array_keys($opt['status']) );
	$from   = isset($opt['order_start'])?$opt['order_start']:0;

	if( isHPOSenabled() ){
		$cnt = $wpdb->get_var( $wpdb->prepare("
	select count(ID) from {$wpdb->prefix}wc_orders o 
	where o.type='shop_order' and o.status in(%s)
	and o.id>%d and NOT EXISTS(select post_id from $wpdb->postmeta where post_id=o.id and meta_key='didar_id' and meta_value<>'')", $status, $from ) );
	}else{
		$cnt = $wpdb->get_var( $wpdb->prepare("
	select count(ID) from {$wpdb->prefix}posts o 
	where o.post_type='shop_order' and o.post_status in(%s)
	and o.id>%d and NOT EXISTS(select post_id from $wpdb->postmeta where post_id=o.ID and meta_key='didar_id' and meta_value<>'')", $status, $from  ) );
	}



	$cnt = empty($cnt)?0:$cnt;
	?>
	<input type="button" class="button send_ajax" value="<?php esc_attr_e('Start', 'didar'); ?>"/>
	<p><span class="cnt">0</span> از <?php echo absint($cnt); ?> <?php esc_attr_e('invoices has been sent', 'didar'); ?></p>
	<div class="progress"><div></div></div>
</div>

<style>
	.progress {
		background: #b9b9b92e;
		width: 100%;
		margin: 0 5px;
		height: 15px;
		border-radius: 10px;
		direction: ltr
	}
	.progress div{
		background: #10d22f;
		width: 0%;
		height: 15px;
		border-radius: 10px;
		transition: width 0.5s ease;
		background: linear-gradient(to right, #00800021 0%, #00800066 25%, #0080008f 50%, #008000c4 75%, green 100%);
	}
</style>
<script>
	var stop = false;
	var pg = width = 0;
	var step = 100 / <?php echo absint($cnt); ?>;
	function send_request(oid=<?php echo absint($from) ?>){

		if(!stop || width>=100)
			return;
		jQuery.ajax({
			url:ajaxurl,
			type:'POST',
			data:{action:'didar_send_all_order',oid:oid},
		}).done(function(data){
			/*jQuery('table tbody').append('<tr><td>'+data[1]+'</td></tr>');
			if(data[0]==2){
				stop = false;
				jQuery('send_ajax').val('توقف');
				return;
			}
			if(data[0]==1){
				pg++;
				jQuery(".cnt").text(pg);
				width += step;
				jQuery('.progress div').css('width', width + '%');
				send_request();
			}*/
			pg++;
			jQuery(".cnt").text(pg);
			width += step;
			jQuery('.progress div').css('width', width + '%');
			send_request(data[2]);
			if(width==100){
				jQuery('.send_ajax').val('<?php esc_attr_e("Start", "didar"); ?>');
				stop = false;
			}
		});
	}
	jQuery('.send_ajax').click(function(){
		stop = !stop;
		jQuery(this).val(stop?'<?php esc_attr_e("Stop", "didar"); ?>':'<?php esc_attr_e("Start", "didar"); ?>');
		send_request();
	});
</script>