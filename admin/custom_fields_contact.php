<?php
global $wpdb, $didar;
?>
<h2><?php _e( 'Contact custom fields', 'didar' ); ?></h2>
<?php
if ( isset( $_POST['save'] ) ) {
    update_option( 'didar_field_contact', $_POST['custom'] );
    echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Contace custom field saved success.',
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
        $i         = 0;
        $user_meta = $wpdb->get_col( "select distinct meta_key from $wpdb->usermeta" );
        if ( $fields = get_option( 'didar_field_contact', [] ) ) {
            foreach ( $fields as $i => $field ) {
                $option = $fdidar = '';
                foreach ( $user_meta as $meta ) {
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
                            class="dashicons dashicons-saved"> </span> <?php _e( 'Save Custom Fields', 'didar' ); ?>
                </button>
            </td>
        </tr>
        </tfoot>
    </table>
</form>
<script>
    var i =<?php echo $i; ?>;
    var option = `<?php
    if ( ! empty( $user_meta ) ) {
        foreach ( $user_meta as $meta ) {
            echo "<option value='$meta'>$meta</option>\n";
        }
    }
    ?>`;
</script>
