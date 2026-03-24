<?php
// JetEngine - JetEngine Cross-sells & Upsells macros
// Deze snippet voegt JetEngine macros toe voor WooCommerce, waarmee je eenvoudig cross-sells en upsells van een product kunt ophalen en gebruiken in listings.

add_action( 'jet-engine/register-macros', function(){ 

	if ( ! function_exists( 'WC' ) ) {
		return;
	}
  
	class WC_Crosssells extends \Jet_Engine_Base_Macros {

		public function macros_tag() {
			return 'wc_crosssells';
		}

		public function macros_name() {
			return 'WC Product Cross-sells';
		}

		public function macros_args() {
			return array();
		}

		public function macros_callback( $args = array() ) {
	
			$object = $this->get_macros_object();
			
			if ( ! is_a( $object, 'WC_Product' ) && ! is_a( $object, 'WP_Post' ) ) {
				return;
			}
			
			$object_id = jet_engine()->listings->data->get_current_object_id( $object );
			
			$product = wc_get_product( $object_id );
			
			if ( ! $product ) {
				return;
			}
			
			$cross_sell_products = $product->get_cross_sell_ids();

			return implode( ',', $cross_sell_products );
			
		}
		
	}

	new WC_Crosssells();
	
	class WC_Upsells extends \Jet_Engine_Base_Macros {

		public function macros_tag() {
			return 'wc_upsells';
		}

		/**
		 * Macros name in UI
		 */
		public function macros_name() {
			return 'WC Product Upsells';
		}

		public function macros_args() {
			return array();
		}

		public function macros_callback( $args = array() ) {
	
			$object = $this->get_macros_object();
			
			if ( ! is_a( $object, 'WC_Product' ) && ! is_a( $object, 'WP_Post' ) ) {
				return;
			}
			
			$object_id = jet_engine()->listings->data->get_current_object_id( $object );
			
			$product = wc_get_product( $object_id );
			
			if ( ! $product ) {
				return;
			}
			
			$upsells_products = $product->get_upsell_ids();

			return implode( ',', $upsells_products );
			
		}
		
	}

	new WC_Upsells();
	
} );
