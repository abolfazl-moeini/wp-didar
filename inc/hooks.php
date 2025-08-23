<?php

class did_hooks {

    public function __construct() {
        add_action( 'init', [ $this, 'init' ] );
        /**
         * admin menu
         */
        add_action( 'admin_menu', [ $this, 'admin_menu' ] );
        /**
         * add didar column to woocomerce order table
         */
        add_filter( 'manage_woocommerce_page_wc-orders_columns', [ $this, 'add_custom_shop_order_column' ] );
        add_action( 'manage_woocommerce_page_wc-orders_custom_column', [ $this, 'shop_order_column_meta_field_value' ],
                10, 2 );

        add_filter( 'manage_edit-shop_order_columns', [ $this, 'add_custom_shop_order_column' ] );
        add_action( 'manage_shop_order_posts_custom_column', [ $this, 'shop_order_column_meta_field_value' ], 10, 2 );

        /**
         * ajax hook save order to didar
         */
        add_action( 'wp_ajax_didar_send_order', [ $this, 'didar_send_order' ] );
        add_action( 'wp_ajax_didar_send_all_order', [ $this, 'didar_send_all_order' ] );
        add_action( 'admin_footer', [ $this, 'didar_bulk_action_mark' ] );

    }

    public function init(): void {

        if ( isset( $_GET['page'] ) && $_GET['page'] === 'custom_fields' && is_admin() ) {

            $url = admin_url( 'admin.php?page=custom_fields' );

            if ( ! isset( $_GET['tab'] ) && ! in_array( $_GET['tab'], [ 'contact', 'deal' ] ) ) {

                wp_redirect( "$url&tab=contact" );
                exit;
            }
        }
    }

    public function admin_menu(): void {

        add_menu_page( esc_html__( 'Didar CRM', 'didar' )
                , esc_html__( 'Didar CRM', 'didar' )
                , 'administrator'
                , 'did_managment'
                , [ $this, 'did_managment' ]
                , plugins_url( 'assets/images/logo.png', __DIR__ )
        );

        add_submenu_page( 'did_managment'
                , esc_html__( 'Send to Didar', 'didar' )
                , esc_html__( 'Send to Didar', 'didar' )
                , 'administrator'
                , 'did_send_ajax'
                , [ $this, 'did_send_ajax' ]
                , plugins_url( 'assets/images/logo.png', __DIR__ )
        );

        add_submenu_page( 'did_managment', esc_html__( 'Reset service', 'didar' ),
                esc_html__( 'Reset service', 'didar' ), 'administrator', 'did_reset_service',
                [ $this, 'did_reset_service' ] );

        add_submenu_page( 'did_managment', esc_html__( 'Custom fields', 'didar' ),
                esc_html__( 'Custom fields', 'didar' ), 'administrator', 'custom_fields',
                [ $this, 'did_custom_fields' ] );

        add_submenu_page( 'did_managment', esc_html__( 'Error log', 'didar' ), esc_html__( 'Error log', 'didar' ),
                'administrator', 'did_errorlog', [ $this, 'did_errorlog' ] );
    }


    public static function didar_hourly_send(): void {
        global $wpdb;

        //did_hooks::didar_send_all_order();
        $opt  = get_option( 'did_option', [] );
        $from = $opt['order_start'] ?? 0;

        if ( isset( $opt['send_type'] ) && $opt['send_type'] == 2 ) {
            return;
        }

        $status = implode( "','", array_keys( $opt['status'] ) );
        $rows   = $wpdb->get_results( $wpdb->prepare( "select id from {$wpdb->prefix}wc_orders wo where wo.status in(%s) 
        and not exists(select post_id from $wpdb->postmeta where post_id=wo.id and meta_key='didar_id' and meta_value<>'') order by id limit %d",
                $status, $from ) );

        if ( empty( $rows ) ) {
            return;
        }
        foreach ( $rows as $row ) {
            $didar = didar_api::save_order( $row->id );
            if ( $didar === false ) {
                continue;
            }
            if ( isset( $didar->Error ) || isset( $didar->Message ) ) {

                update_post_meta( $row->id, 'didar_msg',
                        ( $didar->Message ?? $didar->Error ) );
            } else {
                update_post_meta( $row->id, 'didar_id', $didar->Id );
            }
        }
    }

    public function did_managment(): void {
        include_once( DID_PATH . 'admin/config.php' );
    }

    public function did_send_ajax(): void {
        include_once( DID_PATH . 'admin/send_ajax.php' );
    }

    public function did_errorlog(): void {
        include_once( DID_PATH . 'admin/error_log.php' );
    }

    public function did_reset_service(): void {
        include_once( DID_PATH . 'admin/reset.php' );
    }

    public function did_custom_fields(): void {
        include_once( DID_PATH . 'admin/custom_fields.php' );
    }

    public function add_custom_shop_order_column( $columns ): array {

        $columns['didar_status'] = esc_html__( 'Didar Status', 'didar' );

        return $columns;
    }

    public function shop_order_column_meta_field_value( $column, $order ) {

        $soid = is_numeric( $order ) ? $order : $order->get_id();

        if ( $column === 'didar_status' ) {

            if ( $oid = get_post_meta( $soid, 'didar_id', true ) ) {

                echo '<span class="sent">' . esc_html__( 'Sent', 'didar' ) . '</span>';
            } else {
                echo '<span class="waiting">' . esc_html__( 'Waiting', 'didar' ) . '</span>';
            }
        }
    }


    public function didar_send_order(): void {

        if ( empty( $_POST['oid'] ) ) {

            return;
        }

        if ( $code = get_post_meta( $_POST['oid'], 'didar_id', true ) ) {
            wp_send_json( [ 0, esc_html__( 'This invoice has already been registered.', 'didar' ) ] );
        }
        $didar = didar_api::save_order( $_POST['oid'] );

        if ( $didar === false ) {

            return;
        }

        if ( isset( $didar->Error ) || isset( $didar->Message ) ) {
            update_post_meta( $_POST['oid'], 'didar_msg',
                    ( $didar->Message ?? $didar->Error ) );
            wp_send_json( [ 0, ( $didar->Message ?? $didar->Error ) ] );
        }

        if ( ! is_object( $didar ) ) {

            wp_send_json( [ 0, $didar ] );
        }

        update_post_meta( $_POST['oid'], 'didar_id', $didar->Id );
        wp_send_json( [ 1, esc_html__( 'The invoice was registered successfully.', 'didar' ) ] );
    }

    public function didar_send_all_order(): void {
        global $wpdb;

        $opt    = get_option( 'did_option', [] );
        $status = implode( "','", array_keys( $opt['status'] ) );
        $oid    = empty( $_POST['oid'] ) ? 0 : $_POST['oid'];

        if ( isHPOSenabled() ) {

            $row = $wpdb->get_row( $wpdb->prepare( "
		select * from {$wpdb->prefix}wc_orders o 
		where o.type='shop_order' and o.status in(%s) 
		and o.id>%d and NOT EXISTS(select post_id from $wpdb->postmeta where post_id=o.id and meta_key='didar_id' and meta_value<>'') 
		order by id limit 1", $status, $oid ) );

            if ( empty( $row ) ) {
                wp_send_json( [ 2, esc_html__( 'Nothing found.', 'didar' ) ] );
            }
            $didar = didar_api::save_order( $row->id );

            if ( $didar === false ) {
                return;
            }

            if ( isset( $didar->Message ) || isset( $didar->Error ) ) {
                update_post_meta( $row->id, 'didar_msg',
                        ( $didar->Message ?? $didar->Error ) );
                wp_send_json( [
                        0,
                        ( $didar->Message ?? $didar->Error ) . "-$row->id",
                        $row->id
                ] );
            }

            if ( isset( $didar->Id ) ) {

                update_post_meta( $row->id, 'didar_id', $didar->Id );
                wp_send_json( [
                        1,
                        __( 'The invoice was registered successfully', 'didar' ) . '-' . $row->id,
                        $row->id
                ] );
            }

            wp_send_json( [ 0, $didar . "-$row->id", $row->id ] );

        } else {

            $row = $wpdb->get_row( $wpdb->prepare( "
		select * from {$wpdb->prefix}posts o 
		where o.post_type='shop_order' and o.post_status in(%s) 
		and o.ID>%d and NOT EXISTS(select post_id from $wpdb->postmeta where post_id=o.ID and meta_key='didar_id' and meta_value<>'') 
		order by ID limit 1", $status, $oid ) );

            if ( empty( $row ) ) {
                wp_send_json( [ 2, esc_html__( 'Nothing found.', 'didar' ) ] );
            }

            $didar = didar_api::save_order( $row->ID );

            if ( $didar === false ) {
                return;
            }

            if ( isset( $didar->Message ) || isset( $didar->Error ) ) {
                update_post_meta( $row->ID, 'didar_msg',
                        ( $didar->Message ?? $didar->Error ) );
                wp_send_json( [
                        0,
                        ( $didar->Message ?? $didar->Error ) . "-$row->ID",
                        $row->ID
                ] );
            }

            if ( isset( $didar->Id ) ) {
                update_post_meta( $row->ID, 'didar_id', $didar->Id );
                wp_send_json( [
                        1,
                        esc_html__( 'The invoice was registered successfully', 'didar' ) . '-' . $row->id,
                        $row->ID
                ] );
            }

            wp_send_json( [ 0, $didar . "-$row->ID", $row->ID ] );
        }
    }

    public function didar_bulk_action_mark() {

    if ( isset( $_GET['page'] ) || $_GET['page'] === 'wc-orders' )
        ?>
        <script type="text/javascript">
            jQuery('.send_order_didar').click(function () {
                jQuery('.msg').hide();
                var td = jQuery(this).closest('td');
                td.find(".save_order_didar_wrapper").addClass('wait');
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'didar_send_order',
                        oid: jQuery(this).data('id')
                    },
                }).done(function (data) {
                    td.find(".save_order_didar_wrapper").removeClass('wait');
                    //alert(data[1]);
                    td.find('.msg').text(data[1]).show();
                    if (data[0] == 0)
                        td.prev('td').html('<span class="waiting"><?php esc_attr_e( "Waiting", "didar" ) ?></span>');
                    else
                        td.prev('td').html('<span class="sent"><?php esc_attr_e( "Sent", "didar" ) ?></span>');
                });
            });
        </script>
        <?php
    }
}
