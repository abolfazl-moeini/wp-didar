<?php
/*
Plugin Name: Didar
Plugin URI: https://didar.me/
Description: Connect your Woocommerce website to Didar CRM
Version: 1.1
Author: Didar
Author URI: https://didar.me/
Text Domain: didar
Domain Path: /lang
*/


use Automattic\WooCommerce\Utilities\OrderUtil;

function did_is_HPOS_enabled(): bool {

    $callback = [ OrderUtil::class, 'custom_orders_table_usage_is_enabled' ];

    return is_callable( $callback ) && $callback();
}

define( 'DID_PATH', dirname( __file__ ) . '/' );

include_once( DID_PATH . 'inc/functions.php' );
include_once( DID_PATH . 'inc/didar_api.php' );
include_once( DID_PATH . 'inc/hooks.php' );
include_once( DID_PATH . 'inc/paging.php' );

new did_hooks();

function did_load_textdomain() {

    load_plugin_textdomain( 'didar', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
}

add_action( 'plugins_loaded', 'did_load_textdomain' );

function did_enqueue_admin_css_for_wc_orders() {

    //$screen = get_current_screen();
    //if ($screen && $screen->id === 'woocommerce_page_wc-orders') {
    wp_enqueue_style( 'didar-admin-css', plugins_url( 'assets/css/admin.css', __FILE__ ) );
    //}
}

add_action( 'admin_enqueue_scripts', 'did_enqueue_admin_css_for_wc_orders' );

add_action( 'admin_enqueue_scripts', 'did_plugin_enqueue_scripts' );

function did_plugin_enqueue_scripts() {

    wp_enqueue_style( 'did-plugin-style', plugins_url( 'css/style.css', __FILE__ ), [], '1.0' );

    wp_add_inline_style( 'did-plugin-style', '#toplevel_page_did_managment img {width: 20px;height: auto;}' );
}

add_action( 'add_meta_boxes', 'did_custom_order_meta_box' );
function did_custom_order_meta_box() {

    add_meta_box(
            'did-order-meta-box',
            esc_html__( 'Didar', 'didar' ),
            'did_custom_order_meta_box_callback',
            wc_get_page_screen_id( 'shop-order' ),
            'side',
            'core'
    );
}

function did_custom_order_meta_box_callback( $post ) {

    $order = wc_get_order( $post->ID );
    $opt   = get_option( 'did_option', [] );
    ?>
    <div class="order_data_column">
        <?php
        //var_dump($opt['status']);
        if ( isset( $opt['status'][ 'wc-' . $order->get_status() ] ) ) { ?>
            <div class="didar_status">
                <?php
                if ( $code = get_post_meta( $order->get_id(), 'didar_id', true ) ) {
                    echo "<span class='sent'>" . esc_attr( 'Sent', 'didar' ) . "</span>
			<div>" . esc_attr( 'Registered code',
                                    'didar' ) . ":<kbd class='dcode'>" . esc_attr( get_post_meta( $order->get_id(),
                                    'didar_id', true ) ) . "</kbd></div>";
                } else {
                    echo "<span class='waiting'>" . esc_attr( 'Waiting', 'didar' ) . "</span>";
                }
                ?>
            </div>
            <div class="save_order_didar_wrapper">
                <input type="checkbox" name="didar" value="1"/> <label><?php esc_attr_e( 'Send to Didar',
                            'didar' ); ?></label>
            </div>
            <?php
        } else {
            echo esc_html__( 'The current invoice is not ready to be sent.', 'didar' );
        }
        ?>
    </div>
    <?php
}

add_action( 'woocommerce_process_shop_order_meta', 'did_save_admin_order', 10, 2 );

function did_save_admin_order( $order_id, $order ) {

    if ( ! isset( $_POST['didar'] ) || ! is_admin() ) {

        return;
    }

    $didar = didar_api::save_order( $order_id );

    if ( isset( $didar->Message ) || isset( $didar->Error ) ) {

        update_post_meta( $order_id, 'didar_msg',
                ( isset( $didar->Message ) ? ucfirst( wp_strip_all_tags( $didar->Message ) ) . " [" . $didar->Code . "]" : ucfirst( wp_strip_all_tags( $didar->Error ) ) ) );
    } else {

        update_post_meta( $order_id, 'didar_id', $didar->Id );
    }

}


/**
 * schedular task
 */
// Schedule the function to run hourly
add_action( 'didar_send_all_order_cron', 'didar_send_all_order_function' );
function didar_send_all_order_function() {
    global $wpdb;
    $opt = get_option( 'did_option', [] );

    if ( ! empty( $opt['send_type'] ) ) {

        $from   = $opt['order_start'] ?? 0;
        $to     = $opt['order_count'] ?? 20;
        $status = implode( "','", $opt['status'] );

        if ( did_is_HPOS_enabled() ) {

            $rows = $wpdb->get_results( $wpdb->prepare( "
		select * from {$wpdb->prefix}wc_orders o 
		where o.type='shop_order' and o.status in(%s) 
		and o.id> %d and NOT EXISTS(select post_id from $wpdb->postmeta where post_id=o.id and meta_key='didar_id' and meta_value<>'') 
		order by id limit %s",
                    $status, $from, $to ), ARRAY_A );

            if ( ! empty( $rows ) ) {

                foreach ( $rows as $row ) {

                    $didar = didar_api::save_order( $row['id'] );
                    if ( isset( $didar->Message ) || isset( $didar->Error ) ) {

                        update_post_meta( $row['id'], 'didar_msg',
                                ( $didar->Message ?? $didar->Error ) );
                    }
                    if ( isset( $didar->Id ) ) {

                        update_post_meta( $row['id'], 'didar_id', $didar->Id );
                    }
                }
            }

        } else {

            $rows = $wpdb->get_results( $wpdb->prepare( "
		select * from {$wpdb->prefix}posts o 
		where o.post_type='shop_order' and o.post_status in(%s) 
		and o.ID> %d and NOT EXISTS(select post_id from $wpdb->postmeta where post_id=o.ID and meta_key='didar_id' and meta_value<>'') 
		order by ID limit %d",
                    $status, $from, $to ), ARRAY_A );

            if ( ! empty( $rows ) ) {

                foreach ( $rows as $row ) {

                    $didar = didar_api::save_order( $row['ID'] );
                    /*if(isset($didar->Message)or isset($didar->Error)){
                        update_post_meta($row['ID'],'didar_msg',(isset($didar->Message)?$didar->Message:$didar->Error));
                    }*/
                    if ( isset( $didar->Id ) ) {
                        update_post_meta( $row['ID'], 'didar_id', $didar->Id );
                    }

                }
            }

        }


    }
}


// Schedule the cron event on plugin activation
register_activation_hook( __FILE__, 'didar_send_all_order_schedule_cron' );
function didar_send_all_order_schedule_cron() {
    global $wpdb;

    if ( ! wp_next_scheduled( 'didar_send_all_order_cron' ) ) {
        wp_schedule_event( time(), 'hourly', 'didar_send_all_order_cron' );
    }

    $sql = "CREATE TABLE {$wpdb->prefix}didar_error (
        id INT NOT NULL AUTO_INCREMENT,
        order_id INT NOT NULL,
        date datetime NOT NULL,
        error text NOT NULL,
        path varchar(30) NOT NULL,
        params text NOT NULL,
        PRIMARY KEY (id)
    ) DEFAULT CHARACTER SET utf8;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// Remove the cron event on plugin deactivation
register_deactivation_hook( __FILE__, 'didar_send_all_order_remove_cron' );

function didar_send_all_order_remove_cron() {

    wp_clear_scheduled_hook( 'didar_send_all_order_cron' );
}
