<?php
/**
 * Plugin Name: Woocommerce Post.ch Tracking
 * Plugin URI: https://1daywebsite.ch
 * Description: Schweizer Post Sendungsverfolgungscode auf Bestellbestätigung anbringen. Exklusiv für Kunden von 1daywebsite.ch
 * Version: 1.0.0
 * Author: AFB
 * Author URI: https://1daywebsite.ch
 * Tested up to: 5.3
 * WC requires at least: 2.6
 * WC tested up to: 4.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    if ( ! class_exists( 'AFBPosttracking' ) ) :
	class AFBPosttracking {

	    public function __construct(){
			add_action( 'admin_init', array($this, 'afbpost_register_plugin_settings' ));
			add_action( 'save_post',  array($this, 'afbpost_save_tracking' ), 1, 1 );
			add_action( 'woocommerce_view_order', array($this, 'afbpost_action_woocommerce_view_order'), 10, 1 ); 
			add_action( 'woocommerce_email_order_meta', array($this, 'afbpost_action_woocommerce_email_order_meta'), 10, 4 ); 
			// Set Plugin Path
			$this->pluginPath = dirname(__FILE__);
	    }

	    public function afbpost_register_plugin_settings() {
			//register our settings
			add_meta_box( 'afbpost_order_packaging', __('Post.ch Sendungsverfolgung (Tracking)','woocommerce'), array($this,'afbpost_order_packaging'), 'shop_order', 'side', 'high' );
	    }

	    // add tracking input boxes to the admin order page
	    public function afbpost_order_packaging(){
			global $post;

			$afbpost_tracking_code = get_post_meta( $post->ID, '_afbpost_tracking_code', true ) ? get_post_meta( $post->ID, '_afbpost_tracking_code', true ) : '';
			echo '<input type="hidden" name="afbpost_order_tracking_nonce" value="' . wp_create_nonce() . '">
			<p style="border-bottom:solid 1px #eee;padding-bottom:13px;">
			<label for="afbpost_tracking_code">' . __('Sendungsnummer (Tracking Code):','woocommerce') . '</label>
			<input type="text" style="width:250px;" name="afbpost_tracking_code" placeholder="' . $afbpost_tracking_code . '" value="' . $afbpost_tracking_code . '">
			</p><button type="submit" class="button save_order button-primary" name="save" value="Update">' . __('Aktualisieren','woocommerce') . '</button>';
	    }

	    public function afbpost_save_tracking( $post_id ) {
			// We need to verify this with the proper authorization (security stuff).

			// Check if our nonce is set.
			if ( ! isset( $_POST[ 'afbpost_order_tracking_nonce' ] ) ) {
			    return $post_id;
			}
			$nonce = $_REQUEST[ 'afbpost_order_tracking_nonce' ];

			//Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $nonce ) ) {
			    return $post_id;
			}

			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			    return $post_id;
			}

			// Check the user's permissions.
			if ( 'page' == $_POST[ 'post_type' ] ) {
			    if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			    }
			} else {

			    if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			    }
			}

			$order = wc_get_order( $post_id );
			if($order){
			    // --- Its safe for us to save the data ! --- //
			    $afbpost_tracking_code   = sanitize_text_field($_POST['afbpost_tracking_code']);
			    $order->update_meta_data( '_afbpost_tracking_code', $afbpost_tracking_code );
			    $order->save();
			}
	    }

	    // Add tracking info to myaccount order page
	    public function afbpost_action_woocommerce_view_order($orderid) { 
			$order = wc_get_order( $orderid );
			$afbpost_tracking_code = $order->get_meta( '_afbpost_tracking_code');
			if(!empty(trim($afbpost_tracking_code))) {
				$afbpost_myaccount_link = "www.post.ch/swisspost-tracking?formattedParcelCodes={$afbpost_tracking_code}";
			    echo '<p>' . __('Ihre post.ch Sendungsnummer (Tracking Code) lautet','woocommerce') . ': <b>' . $afbpost_tracking_code.'</b><br>' . 
				__('Sie könnten die Sendung direkt an dieser Adresse verfolgen','woocommerce') . ':<br><a href="'. $afbpost_myaccount_link .'" class="btn" target="_blank">' . __('Sendung Verfolgen','woocommerce') . '</a></p>';
			}
	    }

	    // Add tracking info to order complete email
	    public function afbpost_action_woocommerce_email_order_meta( $order, $sent_to_admin, $plain_text, $email ) { 
			if($order->get_status()=="completed"){
			    $afbpost_tracking_code = $order->get_meta( '_afbpost_tracking_code');
				$afbpost_order_complete_link = "www.post.ch/swisspost-tracking?formattedParcelCodes={$afbpost_tracking_code}";
			    if(!empty(trim($afbpost_tracking_code))) {
					echo '<p>' . __('Ihre Bestellung wurde abgeschickt. Die post.ch Sendungsnummer (Tracking Code) lautet','woocommerce') . ': <b>' . $afbpost_tracking_code.'</b></p><p>' . __('Sie könnten die Sendung direkt an dieser Adresse verfolgen','woocommerce') . ':<br><a href="'. $afbpost_order_complete_link .'" target="_blank">' . __('Sendung Verfolgen','woocommerce') . '</a></p>';
			    }
			}
	    }

	}

    $AFBPosttracking = new AFBPosttracking();
    endif;
}
