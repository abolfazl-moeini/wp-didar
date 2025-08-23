<?php
/*$ver = didar_api::get_custom_fields();
var_dump($ver);*/
global $wpdb;
$paging = '';
$rowsa  = $wpdb->get_results( "select * from {$wpdb->prefix}didar_error order by id desc" );

//echo count($rowsa);


$hpos_table   = $wpdb->prefix . 'wc_orders';
$hpos_enabled = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $hpos_table ) ) === $hpos_table;

if ( $hpos_enabled ) {
    $rows = $wpdb->get_results( "
        SELECT e.* 
        FROM {$wpdb->prefix}didar_error e
        INNER JOIN {$wpdb->prefix}wc_orders o ON e.order_id = o.id
		LEFT JOIN {$wpdb->prefix}wc_order_meta om ON o.id = om.order_id AND om.meta_key = 'didar_id'
		WHERE om.meta_id IS NULL
		ORDER BY e.id DESC;
    " );
} else {
    $rows = $wpdb->get_results( "
		SELECT e.* 
		FROM {$wpdb->prefix}didar_error e
		INNER JOIN {$wpdb->prefix}posts p ON e.order_id = p.ID
		LEFT JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'didar_id'
		WHERE p.post_type = 'shop_order'
		AND pm.meta_id IS NULL
		ORDER BY e.id DESC;
    " );
}


//echo count($rows);
?>
<div class="wrap">
    <h2><?php esc_attr_e( 'Error log', 'didar' ); ?></h2>
    <style>
        table tr td pre {
            max-height: 250px; /* Set maximum height */
            overflow-y: auto; /* Enable vertical scrolling */
            max-width: 300px;
            line-height: 1.5;
        }
    </style>
    <form method="post" enctype="multipart/form-data">
        <table class="widefat striped" style="max-width:100%;">
            <thead>
            <tr>
                <th><?php esc_attr_e( 'Order ID', 'didar' ); ?></th>
                <th><?php esc_attr_e( 'Error date', 'didar' ); ?></th>
                <th><?php esc_attr_e( 'Error request', 'didar' ); ?></th>
                <th><?php esc_attr_e( 'Params', 'didar' ); ?></th>
                <th><?php esc_attr_e( 'Response', 'didar' ); ?></th>
            </tr>
            </thead>
            <body>
            <?php
            if ( ! empty( $rows ) ) {
                $url    = admin_url();
                $paging = did_paging( $rows, 20 );
                foreach ( $rows as $row ) {
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo admin_url( "post.php?post=" . $row->order_id . "&action=edit" ); ?>"><?php echo $row->order_id; ?></a>
                        </td>
                        <td>
                            <bdi><?php echo $row->date; ?>
                                <bdi>
                        </td>
                        <td>
                            <bdi><?php echo $row->date; ?>
                                <bdi>
                        </td>
                        <td><?php echo $row->path; ?></td>
                        <td>
                        <pre dir="ltr"><?php print_r( json_decode( $row->params, true ) ); ?><pre>
                        </td>
                        <td>
                        <pre dir="ltr"><?php print_r( json_decode( $row->error ) ); ?><pre>
                        </td>
                    </tr>
                    <?php
                }
            }
            ?>
            </body>
        </table>
        <?php echo $paging; ?>
    </form>
</div>
