<?php

class didar_api {

	private static $order_id = null;

	private static function send_request( $router, $params = [] ) {

		$opt = get_option( 'did_option', [] );

		if ( empty( $opt['didar_api'] ) ) {

			return null;
		}

		$url  = "https://app.didar.me/api/$router?apikey={$opt['didar_api']}";
		$args = array(
			'method'    => 'POST',
			'headers'   => array( 'Content-Type' => 'application/json' ),
			//'body'        => json_encode( $params, JSON_UNESCAPED_UNICODE ),
			'sslverify' => false,
		);
		if ( ! empty( $params ) ) {
			$args['body'] = json_encode( $params, JSON_UNESCAPED_UNICODE );
		}

		$response = wp_remote_post( $url, $args );

		if ( ! is_wp_error( $response ) ) {
			$resp = wp_remote_retrieve_body( $response );
			$resp = json_decode( $resp );
			if ( ! isset( $resp->Response ) ) {
				add_errorlog( self::$order_id, $router, $params, $resp );
			}

			return $resp;

		} else {

			add_errorlog( self::$order_id, $router, $params, $response );
		}
	}


	public static function get_kariz_list(): array {

		if ( $request = self::send_request( 'pipeline/list/0', [] ) ) {

			return $request->Response;
		}

		return [];
	}

	public static function get_user_list(): array {

		if ( $request = self::send_request( 'User/List', [] ) ) {
			return $request->Response;
		}

		return [];
	}


	private static function get_price( $product_id ) {

		if ( $price = get_post_meta( $product_id, '_sale_price', true ) ) {

			return $price;
		}

		if ( $price = get_post_meta( $product_id, '_regular_price', true ) ) {

			return $price;
		}

		if ( $price = get_post_meta( $product_id, '_price', true ) ) {

			return $price;
		}
	}

	public static function has_user( $mobile ) {

		$user = self::send_request( 'contact/getbyphonenumber', [ 'MobilePhone' => $mobile ] )->Response;

		return empty( $user ) ? false : $user[0];
		/*$user = self::send_request('contact/personsearch',['Criteria'=>['LeadType'=>3,'Keywords'=>$user_name]])->Response;
        if($user->TotalCount==0)
        return false;
        else
        return $user->List[0];*/
	}


	public static function create_user( $args = [] ) {
		if ( $user = self::has_user( $args['MobilePhone'] ) ) {
			return $user;
		}
		$resp = self::send_request( 'contact/save', [ 'Contact' => $args ] );

		return $resp->Response ?? $resp;
	}

	public static function has_product( $pid ) {
		return self::send_request( 'product/search', [ 'Criteria' => [ 'Query' => urlencode( $pid ) ] ] );
	}

	public static function get_product_by_code( $id ) {
		global $wpdb;
		$sku = did_get_sku( $id );
		//$parent = $wpdb->get_var("select post_parent from $wpdb->posts join $wpdb->postmeta on ID=post_id and meta_key='_sku' and meta_value='$sku'");
		$parent  = $wpdb->get_var( $wpdb->prepare( "select post_parent from $wpdb->posts where ID=%d", $id ) );
		$idx     = empty( $parent ) ? $sku : did_get_sku( $parent );
		$product = self::send_request( 'product/getproductbycodes', [ 'Code' => [ $idx ] ] )->Response->Products[0];

		if ( empty( $product ) ) {
			return;
		}
		if ( empty( $parent ) ) {
			return $product;
		}
		foreach ( $product->Variants as $Variants ) {
			if ( $Variants->VariantCode == $sku ) {
				return $Variants;
			}
		}
		//return self::send_request('product/getproductbycodes',['Code'=>[$idx]])->Response->Products;
	}

	public static function create_product( $item ) {

		global $wpdb;
		$id      = empty( $item->get_variation_id() ) ? $item->get_product_id() : $item->get_variation_id();
		$product = self::get_product_by_code( $id );
		if ( ! empty( $product ) ) {
			update_post_meta( $id, 'didar_id', $product->Id );

			return empty( $product->ProductId ) ? [ $product->Id, null ] : [ $product->ProductId, $product->Id ];
		}

		$title = get_the_title( $item->get_product_id() );
		$args  = [
			'Title'             => $title,
			'Code'              => did_get_sku( $item->get_product_id() ),//$item->get_product_id(),
			'Unit'              => 'IRR',
			'Description'       => '',
			'TitleForInvoice'   => $title,
			'ProductCategoryId' => '313180cb-29c4-45ec-918c-aecc3229c3af',
			'Variants'          => []
		];

		if ( empty( $item->get_variation_id() ) ) {

			$args['UnitPrice'] = did_fix_price( $item->get_subtotal() );
		}


		if ( $didar = get_post_meta( $item->get_product_id(), 'didar_id', true ) ) {

			$args['Id'] = $didar;
		}

		$variations = $wpdb->get_results( $wpdb->prepare( "select * from $wpdb->posts where post_type='product_variation' and post_status='publish' and post_parent= %d",
			$item->get_product_id() ) );

		if ( ! empty( $variations ) ) {

			foreach ( $variations as $i => $variation ) {
				$price = did_fix_price( self::get_price( $variation->ID ) );

				$args['Variants'][ $i ] = [
					'UnitPrice'       => $price,
					'Title'           => $variation->post_title,
					'VariantCode'     => did_get_sku( $variation->ID ),
					'TitleForInvoice' => $variation->post_title,
					'IsDefault'       => false
				];

				if ( $didid = get_post_meta( $variation->ID, 'didar_id', true ) ) {

					$args['Variants'][ $i ]['Id']        = $didid;
					$args['Variants'][ $i ]['ProductId'] = $didar;
				}
			}
		}

		$product = self::send_request( 'product/save', [ 'Product' => $args ] );

		if ( ! isset( $product->Response ) ) {

			return $product;
		}

		$product = $product->Response;

		if ( isset( $product->Id ) ) {

			update_post_meta( $item->get_product_id(), 'didar_id', $product->Id );
		}

		if ( ! empty( $product->Variants ) ) {

			foreach ( $product->Variants as $variant ) {

				update_post_meta( did_get_variation_id_by_sku( $variant->VariantCode ), 'didar_id', $variant->Id );
				//update_post_meta($variant->VariantCode, 'didar_id',$variant->Id);
			}
		}
		$var_id = empty( $item->get_variation_id() ) ? null : get_post_meta( $item->get_variation_id(), 'didar_id',
			true );

		return [ get_post_meta( $item->get_product_id(), 'didar_id', true ), $var_id ];
	}

	public static function get_category() {

		return self::send_request( 'product/categories', [] )->Response;
	}

	public static function create_order( $args = [] ) {

		return self::send_request( 'deal/save', $args );
	}

	public static function save_order( $order_id ) {

		self::$order_id = $order_id;

		$opt   = get_option( 'did_option', [] );
		$order = new WC_Order( $order_id );
		$ostat = $order->get_status();

		if ( empty( $opt['status'][ 'wc-' . $ostat ] ) ) {
			return false;
		}

		$args = [
			'Type'           => 'Person',
			'FirstName'      => $order->get_billing_first_name(),
			'LastName'       => $order->get_billing_last_name(),
			'DisplayName'    => $order->get_formatted_billing_full_name(),
			'MobilePhone'    => $order->get_billing_phone(),
			'Email'          => $order->get_billing_email(),
			'IsCustomer'     => true,
			//'CustomerCode' => $order->get_customer_id(),
			'ZipCode'        => $order->get_billing_postcode(),
			'CityTitle'      => $order->get_billing_city(),
			'ProvinceTitle'  => $order->get_billing_state(),
			'CompanyName'    => $order->get_billing_company(),
			'VisibilityType' => 'All',
			'Addresses'      => [
				'KeyValues' => [
					[
						'Key'   => "آدرس",
						'Value' => preg_replace( "/<[^>]+>/is", " ", $order->get_formatted_billing_address() ),
					],
				]
			],
			/**
			 * @FIX undefined
			 */
			'Fields'         => $fields,
		];

		if ( $cfield = get_option( 'didar_field_contact', [] ) ) {

			$fields = [];
			$uid    = get_post_meta( $order_id, '_customer_user', true );
			foreach ( $cfield as $field ) {
				$fields[ $field['didar'] ] = get_user_meta( $uid, $field['wp'], true );
			}
			$args['Fields'] = $fields;
		}

		$user = self::create_user( $args );

		if ( ! isset( $user->Id ) ) {
			return $user;
		}

		$product = [];
		$items   = $order->get_items();

		foreach ( $items as $item ) {

			$did_prod = self::create_product( $item );
			if ( is_object( $did_prod ) ) {
				return $did_prod;
			}

			$price = intval( $item->get_subtotal() / $item->get_quantity() );

			$price = did_fix_price( $price );

			$product[] = [
				'ProductId'   => $did_prod[0],
				'Quantity'    => $item->get_quantity(),
				'UnitPrice'   => $price,
				'Discount'    => $item->get_subtotal_tax(),
				'ProductCode' => ( empty( $item->get_variation_id() ) ? did_get_sku( $item->get_product_id() ) : did_get_sku( $item->get_variation_id() ) ),
				//'ProductCode' => (empty($item->get_variation_id())?$item->get_product_id():$item->get_variation_id()),
				'VariantId'   => $did_prod[1]
			];
		}
		$tax    = empty( $order->get_total_tax() ) ? 0 : ( $order->get_total_tax() / $order->get_total() * 100 );
		$tax    = did_fix_price( $tax );
		$status = empty( $opt['status'][ $ostat ] ) ? 0 : $opt['status'][ $ostat ];
		/*switch($order->get_status()){
			case 'completed':$status=1;break;
			case 'cancelled':
			case 'refunded':
			case 'failed':$status=2;break;
			default:$status=0;break;
		}*/
		$price = did_fix_price( $order->get_total() );
		$soid  = ( isset( $opt['soid'] ) && $opt['soid'] === 'on' ) ? " $order_id" : '';
		$owner = '';
		if ( $opt['same_person'] == 1 ) {
			$owner = get_user_meta( $order->get_customer_id(), 'didar_deal_person', true );
		}

		if ( strlen( $owner ) < 2 ) {
			$owner = $opt['user'][ array_rand( $opt['user'] ) ];
			update_user_meta( $order->get_customer_id(), 'didar_deal_person', $opt['user'][ $owner ] );
		}


		$args = [
			'PersonId'        => $user->Id,
			'ContactId'       => $user->Id,
			'Status'          => $status,
			'Title'           => 'معامله ' . $order->get_formatted_billing_full_name() . $soid,
			'Price'           => $price,
			'Code'            => $order_id,
			'TaxPercent'      => $tax,
			//'RegisterDate'  => $order->get_date_created(),
			'IsPaid'          => true,
			'IsWon'           => $status == 1,
			'OwnerId'         => $owner,//$opt['user'],
			'PipelineStageId' => ( $opt['kariz'] ?? '' ),
		];

		if ( $cfield = get_option( 'didar_field_deal', [] ) ) {

			$fields = [];
			foreach ( $cfield as $field ) {
				$fields[ $field['didar'] ] = get_post_meta( $order_id, $field['wp'], true );
			}
			$args['Fields'] = $fields;
		}


		//echo(json_encode(['Deal'=>$args,'DealItems'=>$product]));die;
		$out = self::create_order( [ 'Deal' => $args, 'DealItems' => $product ] );

		//var_dump(['Deal'=>$args,'DealItems'=>$product]);
		//var_dump($out);
		return $out->Response ?? $out;
	}

	public static function get_custom_fields() {

		return self::send_request( 'customfield/GetCustomfieldList', [] );
	}
}
