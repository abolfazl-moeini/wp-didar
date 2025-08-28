<div class="wrap">
    <h1><?php esc_attr_e( 'Didar', 'didar' ); ?></h1>
    <?php
    //$ver = didar_api::get_custom_fields();
    //var_dump($ver);
    //if(isset($_POST['save'])){
    if ( isset( $_POST['save'] ) && wp_verify_nonce( $_POST['didar_settings_nonce'], 'didar_settings' ) ) {

        $_POST['status']['wc-completed'] = 1;

        /**
         * @FIX update filtered values
         */
        update_option( 'did_option', $_POST );

        echo '<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible"><p><strong>' . esc_html__( 'Settings saved',
                        'didar' ) . '</strong></p></div>';
    }
    $opt         = get_option( 'did_option', [] );
    $status      = $opt['status'] ?? [];
    $type        = $opt['send_type'] ?? 1;
    $soid        = $opt['soid'] ?? '';
    $nonce       = wp_create_nonce( 'didar_settings' );
    $sku         = $opt['sku'] ?? 0;
    $same_person = $opt['same_person'] ?? 0;
    $price_type  = $opt['price_type'] ?? 'IRT';

    $woo_status = [
        //'draft'=>__('Draft', 'didar'),
            'wc-completed'  => __( 'Completed', 'didar' ),
            'wc-processing' => __( 'Processing', 'didar' ),
            'wc-cancelled'  => __( 'Cancelled', 'didar' ),
            'wc-refunded'   => __( 'Refunded', 'didar' ),
            'wc-pending'    => __( 'Pending', 'didar' ),
            'wc-on-hold'    => __( 'On Hold', 'didar' ),
            'wc-failed'     => __( 'Failed', 'didar' )
    ];
    ?>
    <form method="POST">
        <input type="hidden" name="didar_settings_nonce" value="<?php echo esc_attr( $nonce ); ?>">
        <table class="wp-list-table posts form-table" role="presentation">
            <thead>
            <tr>
                <th colspan="2"><?php esc_attr_e( 'didar WooCommerce plugin settings', 'didar' ); ?></th>
            </tr>
            </thead>
            <tr>
                <td><?php esc_attr_e( 'Didar API key', 'didar' ); ?></td>
                <td><input type="text" name="didar_api" dir="ltr"
                           value="<?php echo isset( $opt['didar_api'] ) ? esc_attr( $opt['didar_api'] ) : ''; ?>"
                           size="55"/></td>
            </tr>
            <tr>
                <td><?php esc_attr_e( 'Send Type', 'didar' ); ?></td>
                <td>
                    <input type="radio" name="send_type" value="auto" <?php echo checked( true, $type !== 'auto') ?>/>
                    <label><?php esc_attr_e( 'Auto', 'didar' ); ?></label><br/>
                    <input type="radio" name="send_type" value="manual" <?php echo checked( true, $type !== 'auto' ) ?>/>
                    <label><?php esc_attr_e( 'Manual', 'didar' ); ?></label><br/>
                </td>
            </tr>
            <tr>
                <td><?php esc_attr_e( 'Mapping WooCommerce status to meeting status', 'didar' ); ?></td>
                <td>
                    <table>
                        <?php
                        foreach ( $woo_status as $key => $title ) {
                            $checked  = ( isset( $status[ $key ] ) || $key == 'completed' ) ? 'checked="checked"' : '';
                            $selected = empty( $status[ $key ] ) ? ( $key == 'completed' ? 1 : '' ) : $status[ $key ];

                            echo '
						<tr>
							<th><input type="checkbox" class="status" data-id="' . $key . '" ' . ( $key == 'completed' ? ' disabled="disabled"' : '' ) . $checked . '/> ' . $title . '</th>
							<td>
								<select name="status[' . $key . ']" class="' . $key . '" ' . ( ( empty( $checked ) or $key == 'completed' ) ? 'disabled="disabled"' : '' ) . '>
									<option value="0" ' . ( $selected == 0 ? 'selected="selected"' : '' ) . '>' . __( 'Failed',
                                            'didar' ) . '</option>
									<option value="1" ' . ( $selected == 1 ? 'selected="selected"' : '' ) . '>' . __( 'Success',
                                            'didar' ) . '</option>
									<option value="2" ' . ( $selected == 2 ? 'selected="selected"' : '' ) . '>' . __( 'Current',
                                            'didar' ) . '</option>
								</select>
							</td>
						</tr>';
                        }
                        ?>
                    </table>
                </td>
            </tr>
            <tr>
                <td><?php esc_attr_e( 'Choose Kariz', 'didar' ); ?></td>
                <td>
                    <select id="parentSelect" name="parent_kariz">
                        <option value=""><?php esc_attr_e( 'Choose...', 'didar' ); ?></option>
                    </select>

                    <select id="childSelect" name="kariz"></select>
                    <!--select name="kar iz">
<option value="">انتخاب</option>
<?php
                    /*if($kariz = didar_api::get_kariz_list()){
                                            $selected = isset($opt['kariz'])?$opt['kariz']:'';
                                            foreach($kariz as $kar){
                                                foreach($kar->Stages as $stage){
                                                    echo "<option value='$stage->Id' ".($selected==$stage->Id?"selected='selected'":'').">$kar->Title-$stage->Title</option>";
                                                }

                                            }
                                        }*/
                    ?>
</select-->
                </td>
            </tr>
            <tr>
                <td><?php esc_attr_e( 'Responsible selection', 'didar' ); ?></td>
                <td>
                    <ul>
                        <?php
                        if ( $users = didar_api::get_user_list() ) {
                            $selected = isset( $opt['user'] ) ? ( is_array( $opt['user'] ) ? $opt['user'] : [ $opt['user'] ] ) : [];
                            foreach ( $users as $user ) {
                                echo "
								<li>
								<input type='checkbox' name='user[]' value='" . esc_attr( $user->UserId ) . "' " . ( in_array( $user->UserId,
                                                $selected ) ? "checked='checked'" : '' ) . " /> " . esc_attr( $user->DisplayName ) . "
								</li>";
                            }
                        }
                        ?>
                    </ul>
                </td>
            </tr>
            <tr>
                <td><?php esc_attr_e( 'Send WooCommerce order ID', 'didar' ); ?></td>
                <td>
                    <input type="checkbox" name="soid" <?php checked( $soid, "on" ); ?>/>
                    <small><?php esc_attr_e( 'Display the WooCommerce order ID in the didar deal name',
                                'didar' ); ?></small>
                </td>
            </tr>
            <tr>
                <td><?php esc_attr_e( 'Number of submissions from the web service', 'didar' ); ?></td>
                <td>
                    <input type="number" name="order_count"
                           value="<?php echo isset( $opt['order_count'] ) ? esc_attr( $opt['order_count'] ) : 20; ?>"
                           class="form-control"/>
                    <small><?php esc_attr_e( 'How many invoices should be sent each time the web service is sent',
                                'didar' ); ?></small>
                </td>
            </tr>
            <!--tr>
				<td><?php esc_attr_e( 'Start sending from the invoice', 'didar' ); ?></td>
				<td>
					<input type="number" name="order_start" value="<?php echo isset( $opt['order_start'] ) ? esc_attr( $opt['order_start'] ) : 0; ?>" class="form-control" />
					<small><?php esc_attr_e( 'Start sending invoice from invoice with ID', 'didar' ); ?></small>
				</td>
			</tr-->
            <tr>
                <td><?php esc_attr_e( 'Person responder of transaction', 'didar' ); ?></td>
                <td>
                    <input type="radio" name="same_person" value="1" <?php checked( $same_person, 1 ); ?>/>
                    <label><?php esc_attr_e( 'Yes', 'didar' ); ?></label><br>
                    <input type="radio" name="same_person" value="0" <?php checked( $same_person, 0 ); ?>/>
                    <label><?php esc_attr_e( 'No', 'didar' ); ?></label>
                    <p><?php esc_attr_e( 'The person responsible for the transaction should be the person responsible',
                                'didar' ); ?></p>
                </td>
            </tr>
            <tr>
                <td><?php esc_attr_e( 'Didar price type', 'didar' ); ?></td>
                <td>
                    <input type="radio" name="price_type" value="IRR" <?php checked( $price_type, 'IRR' ); ?>/>
                    <label><?php esc_attr_e( 'Rial', 'didar' ); ?></label><br>
                    <input type="radio" name="price_type" value="IRT" <?php checked( $price_type, 'IRT' ); ?>/>
                    <label><?php esc_attr_e( 'Tooman', 'didar' ); ?></label>
                </td>
            </tr>
            <tr>
                <td><?php esc_attr_e( 'Generate SKU for product', 'didar' ); ?></td>
                <td>
                    <input type="radio" name="sku" value="1" <?php checked( $sku, 1 ); ?>/>
                    <label><?php esc_attr_e( 'Yes', 'didar' ); ?></label><br>
                    <input type="radio" name="sku" value="0" <?php checked( $sku, 0 ); ?>/>
                    <label><?php esc_attr_e( 'No', 'didar' ); ?></label>
                </td>
            </tr>
        </table>
        <?php

        $didar_product_cats = get_terms( [
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
        ] );
        ?>

        <hr style="margin:24px 0" />
        <h3><?php _e( 'WooCommerce Product Category Mapping', 'didar' ); ?></h3>
        <p><?php _e( 'Map each WooCommerce product category to one of didar product categories.', 'didar' ); ?></p>

        <table class="widefat striped">
            <thead>
            <tr>
                <th style="width:40%"><?php _e( 'Category', 'didar' ); ?></th>
                <th><?php _e( 'Mapped Category', 'didar' ); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if ( ! empty( $didar_product_cats ) && ! is_wp_error( $didar_product_cats )  ) : ?>
                <?php foreach ( $didar_product_cats as $cat ) :
                    $selected = $opt['category_map'][ $cat->term_id ] ?? '';
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html( $cat->name ); ?></strong>
                            <br>(ID: <?php echo (int) $cat->term_id; ?>)
                        </td>
                        <td>
                            <select name="category_map[<?php echo (int) $cat->term_id; ?>]" style="min-width:220px;">
                                <option value=""> - </option>
                                <?php foreach ( didar_api::get_category() as $val => $label ) : ?>
                                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $selected,
                                            $val ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="2"><?php _e( 'No product categories found.', 'didar' ); ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="submit">

            <input type="submit" name="save" value="<?php esc_attr_e( 'Save Settings', 'didar' ); ?>"
                   class="button-primary save_btn"/>
        </div>
    </form>
</div>
<script>
    var kariz = <?php $kariz = didar_api::get_kariz_list(); echo json_encode( $kariz, JSON_UNESCAPED_UNICODE ); ?>;
    (function ($) {
        var parentSelect = $("#parentSelect");
        var childSelect = $("#childSelect");
        var selectedParentValue = "<?php echo esc_attr( $opt['parent_kariz'] ?? '' ); ?>";
        var selectedChildValue = "<?php echo esc_attr( $opt['kariz'] ?? '' ); ?>";


        kariz.forEach(function (item) {
            var option = $("<option>").text(item.Title).val(item.Id);
            parentSelect.append(option);
        });

        parentSelect.on("change", function () {
            var selectedParentId = parentSelect.val();
            childSelect.empty();
            var selectedParent = kariz.find(function (item) {
                return item.Id === selectedParentId;
            });

            if (selectedParent && selectedParent.Stages) {
                selectedParent.Stages.forEach(function (stage) {
                    var option = $("<option>").text(stage.Title).val(stage.Id);
                    childSelect.append(option);
                });
            }
        });

        parentSelect.val(selectedParentValue).trigger("change");
        childSelect.val(selectedChildValue);

    })(jQuery);
    jQuery('.status').click(function () {
        tr = jQuery(this).closest('tr');
        if (jQuery(this).is(':checked')) {
            tr.find('select').removeAttr('disabled');
            tr.find('select option:selected').removeAttr('selected');
        } else {
            tr.find('select').attr('disabled', 'disabled');
        }
    });
</script>
