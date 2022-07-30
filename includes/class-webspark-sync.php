<?php
/**
 * Sync with API
 *
 * @package wpsync-webspark
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Webspark_Sync' ) ) {
	/**
	 * Webspark Sync Class
	 */
	class Webspark_Sync {
		/**
		 * Enable cron
		 *
		 * @return void
		 */
		public static function enable_cron_action() {
			if ( ! wp_next_scheduled( 'wpsync_cron_action' ) ) {
				wp_schedule_event( time(), 'hourly', 'wpsync_cron_action' );
			}
			if ( ! wp_next_scheduled( 'wpsync_create_products_queue' ) ) {
				wp_schedule_event( strtotime( '+1 minute' ), 'every_minute', 'wpsync_create_products_queue' );
			}
		}
		/**
		 * Disable cron
		 *
		 * @return void
		 */
		public static function disable_cron_action() {
			wp_clear_scheduled_hook( 'wpsync_cron_action' );
			wp_clear_scheduled_hook( 'wpsync_create_products_queue' );
		}

		/**
		 * Admin notice if WC plugin is not enabled
		 *
		 * @return void
		 */
		public static function admin_notice_wc_error() {
			?>
		<div class="notice notice-error is-dismissible">
			<p>Sync Error. Woocommerce not enabled</p>
		</div>
			<?php
		}

		/**
		 * Sync data with API
		 *
		 * @return bool
		 */
		public static function sync_action() {
			$sync_enabled = get_option( 'wpsync_enable_sync' );
			if ( ! $sync_enabled ) {
				return false;
			}

			if ( ! class_exists( 'Woocommerce' ) ) {
				add_action( 'admin_notices', array( __CLASS__, 'admin_notice_wc_error' ) );
				return false;
			}

			// Get remote data.
			$args        = array(
				'timeout' => 20,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
			);
			$remote_data = wp_remote_get( WEBSPARK_PRODUCTS_URL, $args );
			// Error in request.
			if ( is_wp_error( $remote_data ) ) {
				write_log( 'Webspark Sync: ' . $remote_data->get_error_message );
				return false;
			}
			// Getting response body.
			$body = wp_remote_retrieve_body( $remote_data );
			// Decode to assoc array.
			$data = json_decode( $body, true );

			if ( ! is_array( $data ) ) {
				write_log( 'Webspark Sync: data decode failed' );
			}

			// IF error is true break operation.
			if ( true === $data['error'] ) {
				return false;
			}

			if ( is_array( $data['data'] ) && ! empty( $data['data'] ) ) {
				self::delete_unused_products( $data['data'] );
				$chunked_data = array_chunk( $data['data'], WEBSPARK_PRODUCTS_QUEUE );
				update_option( 'wpsync_products_queue', $chunked_data );
			} else {
				return false;
			}
			return true;
		}

		/**
		 * Create products action
		 *
		 * @return void
		 */
		public static function create_products() {
			$data = get_option( 'wpsync_products_queue' );
			if ( is_array( $data ) ) {
				foreach ( $data as $key => $value ) {
					$items = $value;
					unset( $data[ $key ] );
					update_option( 'wpsync_products_queue', $data );
					foreach ( $items as $item ) {
						self::create_product( $item );
					}
					break;
				}
			}
		}

		/**
		 * Create single product
		 *
		 * @param array $item Item: sku, name, description, price, picture, in_stock.
		 * @return int
		 */
		public static function create_product( $item ) {
			$exists_product_id = wc_get_product_id_by_sku( $item['sku'] );
			if ( $exists_product_id ) {
				$product = new WC_Product_Simple( $exists_product_id );
			} else {
				$product = new WC_Product_Simple();
			}
			$product->set_sku( $item['sku'] );
			$product->set_name( $item['name'] );
			$product->set_description( $item['description'] );
			$product->set_regular_price( $item['price'] );
			$product->set_stock_quantity( $item['in_stock'] );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			$product->set_stock_status( 'in_stock' );
			$image_id = self::get_remote_image( $item['picture'] );
			if ( $image_id ) {
				$product->set_image_id( $image_id );
			}
			$product_id = $product->save();

			return $product_id;
		}

		/**
		 * Add product image
		 *
		 * @param string $image_url Image URL.
		 * @return int
		 */
		public static function get_remote_image( $image_url ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';

			// From URL to get redirected URL.
			$url = $image_url;
			$ch  = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

			// Return follow location true.
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
			curl_exec( $ch );

			// Getinfo or redirected URL from effective URL.
			$redirected_url = curl_getinfo( $ch, CURLINFO_EFFECTIVE_URL );

			$image_id = media_sideload_image( $redirected_url, 0, '', 'id' );

			if ( is_wp_error( $image_id ) ) {
				write_log( 'Webspark Sync: ' . $image_id->get_error_message() );
				return false;
			} else {
				return $image_id;
			}
		}

		/**
		 * Delete unused products
		 *
		 * @param array $data Data from API.
		 * @return void
		 */
		public static function delete_unused_products( $data ) {
			$products_in_catalog = array();

			foreach ( $data as $item ) {
				$exists_product_id = wc_get_product_id_by_sku( $item['sku'] );
				if ( $exists_product_id ) {
					$products_in_catalog[] = $exists_product_id;
				}
			}

			$args = array(
				'exclude' => $products_in_catalog,
				'return'  => 'ids',
			);

			$products = wc_get_products( $args );

			if ( ! empty( $products ) ) {
				foreach ( $products as $id ) {
					wp_delete_post( $id );
				}
			}

		}
	}
}
