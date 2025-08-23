<h2><?php _e( 'Deals custom fields', 'didar' ); ?></h2>
<?php
if ( isset( $_POST['save'] ) ) {
    update_option( 'didar_field_deal', $_POST['custom'] );
    echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Deal custom field saved success.',
                    'didar' ) . '</p></div>';
}
?>
<form method="post">
    <table class="widefat striped">
        <thead>
        <tr>
            <td colspan="3">
                <button class="button add" type="button"><span
                            class="dashicons dashicons-plus"></span> </span> <?php _e( 'Add', 'didar' ); ?></button>
            </td>
        </tr>
        </thead>
        <tbody>
        <?php
        global $wpdb, $didar;
        $i          = 0;
        $order_meta = $wpdb->get_col( "select distinct meta_key from $wpdb->postmeta join $wpdb->posts on ID=post_id and post_type='shop_order'" );
        if ( $fields = get_option( 'didar_field_deal', [] ) ) {
            foreach ( $fields as $i => $field ) {
                $option = $fdidar = '';
                foreach ( $order_meta as $meta ) {
                    $option .= "<option value='$meta' " . ( $meta == $field['wp'] ? 'selected="selected"' : '' ) . ">$meta</option>\n";
                }

                foreach ( $didar->Response as $item ) {
                    $fdidar .= "<option value='$item->Key' " . ( $field['didar'] == $item->Key ? 'selected="selected"' : '' ) . ">$item->Title</option>\n";
                }
                echo "
					<tr>
						<td>
							<select name='custom[$i][didar]' required>
								<option value=''>" . __( 'Select...', 'didar' ) . "</option>
								$fdidar
							</select>
						</td>
						<td>
							<select name='custom[$i][wp]' required>
								<option value=''>" . __( 'Select...', 'didar' ) . "</option>
								$option
							</select>
						</td>
						<td><span class=\"dashicons dashicons-table-row-delete del\"></span></td>
					<tr>";
            }
        }
        ?>
        </tbody>
        <tfoot>
        <tr>
            <td colspan="3">
                <button class="button-primary" name="save" type="submit"><span
                            class="dashicons dashicons-saved"> </span> <?php _e( 'save custom fields', 'didar' ); ?>
                </button>
            </td>
        </tr>
        </tfoot>
    </table>
</form>
<script>
    var i =<?php echo $i; ?>;
    var option = `<?php
    if ( ! empty( $order_meta ) ) {
        foreach ( $order_meta as $meta ) {
            echo "<option value='$meta'>$meta</option>\n";
        }
    }
    ?>`;
</script>
