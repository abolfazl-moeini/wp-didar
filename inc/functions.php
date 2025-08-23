<?php

function did_get_sku( $pid ) {
	if ( $sku = get_post_meta( $pid, "_sku", true ) ) {
		return $sku;
	}
	$sku = "SKU_$pid";
	update_post_meta( $pid, "_sku", $sku );

	return $sku;
	/*$product = wc_get_product($variation_id);

    if ($product && $product->is_type('variation')) {
        $parent_id = $product->get_parent_id();
        $parent_product = wc_get_product($parent_id);

        if ($parent_product) {
            $variations = $parent_product->get_available_variations();

            foreach ($variations as $variation) {
                if ($variation['variation_id'] === $variation_id) {
                    return $variation['sku'];
                }
            }
        }
    }

    return '';*/
}

function add_errorlog( $order_id, $path, $params, $output ) {
	global $wpdb;

	$path   = esc_sql( $path );
	$output = empty( $output ) ? [] : $output;
	$params = json_encode( $params, JSON_UNESCAPED_UNICODE );
	$output = json_encode( $output, JSON_UNESCAPED_UNICODE );

	/*$out = $wpdb->get_var("select id from {$wpdb->prefix}didar_error where path='$path' and params='$params' and date>(NOW() - INTERVAL 5 MINUTE)");
	if(!empty($out))
		return $out;*/

	return $wpdb->insert( "{$wpdb->prefix}didar_error", [
		'date'     => date( 'Y-m-d H:i:s' ),
		'order_id' => $order_id,
		'error'    => $output,
		'path'     => $path,
		'params'   => $params
	] );
}

function did_fix_price( $price ) {
	$opt = get_option( 'did_option', [] );

	$price_type = $opt['price_type'] ?? 0;

	if ( get_option( 'woocommerce_currency' ) == 'IRT' && 'IRR' == $price_type ) {

		return $price * 10;
	}
	if ( get_option( 'woocommerce_currency' ) == 'IRR' && 'IRT' == $price_type ) {

		return $price / 10;
	}

	return $price;
}


function did_get_variation_id_by_sku( $sku ) {
	global $wpdb;


	return $wpdb->get_var(
		$wpdb->prepare(
			"SELECT p.ID
            FROM {$wpdb->prefix}posts p
            INNER JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
            WHERE p.post_type = 'product_variation'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_sku'
            AND pm.meta_value = %s",
			$sku
		)
	);
}


/**
 * Get the mapped ID for a WooCommerce order line item.
 */

function didar_get_base_product_id_from_item( WC_Order_Item $item ): int {

	if ( ! $item instanceof WC_Order_Item_Product ) {

		return 0;
	}

	$product = $item->get_product();

	if ( $product instanceof WC_Product ) {
		// Categories live on the parent for variations.
		return $product->is_type( 'variation' ) ? (int) $product->get_parent_id() : (int) $product->get_id();
	}

	if ( $vid = $item->get_variation_id() ) {

		if ( $parent = (int) wp_get_post_parent_id( $vid ) ) {

			return $parent;
		}
	}

	return (int) $item->get_product_id();
}

/**
 * Try to get the Primary product_cat term ID from popular SEO plugins.
 * Order: Yoast helper → Yoast class/meta → Rank Math meta → 0
 */
function didar_get_primary_product_cat_id( int $product_id ): int {

	// Yoast helper
	if ( function_exists( 'yoast_get_primary_term_id' ) ) {

		if ( $primary_cat_id = (int) yoast_get_primary_term_id( 'product_cat', $product_id ) ) {

			return $primary_cat_id;
		}
	}
	// Yoast class
	if ( class_exists( 'WPSEO_Primary_Term' ) ) {
		$yoast = new WPSEO_Primary_Term( 'product_cat', $product_id );

		if ( $primary_cat_id = (int) $yoast->get_primary_term() ) {

			return $primary_cat_id;
		}
	}

	// Rank Math: product_cat meta key
	if ( $primary_cat_id = (int) get_post_meta( $product_id, 'rank_math_primary_product_cat', true ) ) {

		return $primary_cat_id;
	}

	if ( $primary_cat_id = (int) get_post_meta( $product_id, 'rank_math_primary_category', true ) ) {

		return $primary_cat_id;
	}

	return 0;
}

function didar_get_the_terms_with_args( int $id, string $taxonomy, array $args ) {

	if ( $terms = get_object_term_cache( $id, $taxonomy ) ) {

		return $terms;
	}

	return wp_get_object_terms( $id, $taxonomy, $args );
}

/**
 * Main function: get one MAPPED_ID for a WC_Order_Item.
 */
function didar_get_mapped_id_from_order_item( WC_Order_Item $item ): string {

	if ( ! $product_id = didar_get_base_product_id_from_item( $item ) ) {

		return '';
	}

	$options = get_option( 'did_option', [] );

	if ( empty( $options['category_map'] ) ) {

		return '';
	}

	if ( ! $the_category_id = didar_get_primary_product_cat_id( $product_id ) ) {

		$categories = didar_get_the_terms_with_args( $product_id, 'product_cat', [ 'number' => 1 ] );

		if ( $categories && ! is_wp_error( $categories ) ) {

			$the_category_id = $categories[0]->term_id;
		}
	}

	return $options['category_map'][ $the_category_id ] ?? '';
}
