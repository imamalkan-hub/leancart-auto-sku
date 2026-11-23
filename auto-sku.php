<?php
/**
 * Plugin Name: LeanCart Auto SKU Generator
 * Plugin URI:  https://sleepclinicdirectory.com/
 * Description: When a new product or variation is saved and the SKU field is empty, generates a readable, unique SKU based on the product name.
 * Version:     1.0.0
 * Author:      LeanCart
 * Author URI:  https://leancart.dev
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: auto-sku
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LEANCART_AUTO_SKU_DEMO_SITE' ) ) {
	define( 'LEANCART_AUTO_SKU_DEMO_SITE', 'https://sleepclinicdirectory.com' );
}

require_once __DIR__ . '/includes/class-leancart-framework.php';

final class LeanCart_Auto_SKU {

	const SLUG = 'auto-sku';

	public static function init() {
		LeanCart_Framework::boot( array(
			'slug'    => self::SLUG,
			'name'    => 'LeanCart Auto SKU Generator',
			'version' => '1.0.0',
			'file'    => __FILE__,
			'site'    => LEANCART_AUTO_SKU_DEMO_SITE,
		) );

		// WooCommerce may load after these plugins; defer the check to runtime.
		add_action( 'admin_notices', array( __CLASS__, 'wc_notice' ) );

		load_plugin_textdomain( 'auto-sku', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		add_action( 'leancart_' . self::SLUG . '_settings_fields', array( __CLASS__, 'fields' ) );

		// WooCommerce may load after this plugin; hooks are registered once WC is ready.
		if ( LeanCart_Framework::is_wc() ) {
			self::hooks();
		} else {
			add_action( 'woocommerce_loaded', array( __CLASS__, 'hooks' ) );
		}
	}

	public static function wc_notice() {
		if ( LeanCart_Framework::is_wc() ) { return; }
		echo '<div class="notice notice-warning"><p><strong>' . esc_html( 'LeanCart Auto SKU Generator' ) . '</strong>: ';
		esc_html_e( 'This plugin requires WooCommerce to be active.', 'auto-sku' );
		echo '</p></div>';
	}

	public static function fields() {
		LeanCart_Framework::field_text( 'prefix', __('SKU prefix', 'auto-sku'), __('Can be left blank.', 'auto-sku'), 'text' );
	}


	public static function hooks() {
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'product' ), 20 );
		add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'variation' ), 20, 2 );
	}

	private static function make( $name, $id, $suffix = '' ) {
		$name = remove_accents( $name );
		$name = preg_replace( '/[^a-zA-Z0-9]+/', '-', strtolower( $name ) );
		$name = trim( $name, '-' );
		$parts = array_filter( array( LeanCart_Framework::setting( 'prefix' ), substr( $name, 0, 12 ), $id . $suffix ) );
		return strtoupper( implode( '-', $parts ) );
	}

	public static function product( $id ) {
		$product = wc_get_product( $id );
		if ( ! $product || $product->get_sku() ) { return; }
		$sku = self::make( $product->get_name(), $id );
		while ( wc_get_product_id_by_sku( $sku ) ) { $sku .= '-' . wp_rand( 10, 99 ); }
		$product->set_sku( $sku );
		$product->save();
	}

	public static function variation( $id, $loop ) {
		$v = wc_get_product( $id );
		if ( ! $v || $v->get_sku() ) { return; }
		$sku = self::make( $v->get_name(), $v->get_parent_id(), '-' . ( $loop + 1 ) );
		while ( wc_get_product_id_by_sku( $sku ) ) { $sku .= '-' . wp_rand( 10, 99 ); }
		$v->set_sku( $sku );
		$v->save();
	}


}

LeanCart_Auto_SKU::init();

