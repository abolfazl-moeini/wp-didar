<div class="wrap">
    <?php
    global $didar;
    $didar = didar_api::get_custom_fields();
    ?>
    <?php
    $url = admin_url( 'admin.php?page=custom_fields' );
    ?>
    <style>
        .tab {
            text-align: center;
        }

        .tab li {
            display: inline;
            padding: 5px 10px;
        }

        .active {
            color: orange;
        }

        .dashicons {
            margin-top: 6px;
        }

        .del {
            cursor: pointer;
            color: red;
        }
    </style>
    <ul class="tab">
        <li><a href="<?php echo $url ?>&tab=contact"
               class="<?php echo $_GET['tab'] == 'contact' ? 'active' : ''; ?>"><?php _e( 'Contact custom field',
                        'didar' ); ?></a></li>
        <li><a href="<?php echo $url ?>&tab=deal"
               class="<?php echo $_GET['tab'] == 'deal' ? 'active' : ''; ?>"><?php _e( 'Deal custom field',
                        'didar' ); ?></a></li>
    </ul>
    <div class="contact"><?php
        if ( $_GET['tab'] == 'contact' ) {
            include_once( 'custom_fields_contact.php' );
        }
        ?></div>
    <div class="deal"><?php
        if ( $_GET['tab'] != 'contact' ) {
            include_once( 'custom_fields_deal.php' );
        }
        ?></div>
</div>
<script>
    jQuery(document).ready(function ($) {
        $('.add').click(function () {
            ++i;
            str = `
		<tr>
			<td>
				<select name="custom[${i}][didar]" required>
					<option value=""><?php _e( 'Select...', 'didar' ); ?></option>
					<?php
            if ( ! empty( $didar ) ) {
                foreach ( $didar->Response as $field ) {
                    echo "<option value='$field->Key'>$field->Title</option>\n";
                }
            }
            ?>
				</select>
			</td>
			<td>
				<select name="custom[${i}][wp]" required>
					<option value=""><?php _e( 'Select...', 'didar' ); ?></option>
					${option}
				</select>
			</td>
			<td>
				<span class="dashicons dashicons-table-row-delete del"></span>
			</td>
		</tr>`;
            $('table tbody').append(str);
        });

        $('body').on('click', '.del', function () {
            if (confirm('<?php _e( 'Do you want delete this custom field?', 'didar' ); ?>'))
                $(this).closest('tr').remove();
        });
    });
</script>
