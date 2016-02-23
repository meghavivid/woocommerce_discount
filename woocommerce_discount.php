<?php
/*
Plugin Name: Woo Commerce Discount Extension
Plugin URI: http://localhost/wordpress_test/
Description: Woo Commerce Discount Extension
Version: 3.0.0
Author: Automattic
Author URI: http://localhost/wordpress_test/
License: 
Text Domain: woo_discount
*/

/* woo_discount : Add Discount field in cart and checkout page */ 
add_action( 'woocommerce_cart_calculate_fees','woocommerce_custom_surcharge' ,9);
function woocommerce_custom_surcharge() {
	if(is_user_logged_in()) {
			global $current_user;
			$apply_woo_discount= get_user_meta( $current_user->ID, 'apply_woo_discount', true);	
			if($apply_woo_discount == "yes") 
			{				
				global $woocommerce;
				if ( is_admin() && ! defined( 'DOING_AJAX' ) )
					return;

				$settings_Arr = json_decode(get_option("woo-discount-settings"));
				$woo_discount = $settings_Arr->woo_discount;
				$discount_point = 0;
				global $wpdb;
				
				$results_points_user = $wpdb->get_row(
					$wpdb->prepare("select * from ".$wpdb->prefix . "woo_discount_users where user_id=%d",$current_user->ID)
				);

				if($results_points_user != "")
					$discount_point = $results_points_user->discount_point;
				else
					$discount_point = $woo_discount;
				$percentage_discount = $settings_Arr->woo_discount_per;
				$pcnt = ($woocommerce->cart->cart_contents_total * $percentage_discount) / 100; 
				if($percentage_discount != "" || $percentage_discount != 0 ) {
					if(	$discount_point >= $pcnt)
					{					
						$surcharge = ($woocommerce->cart->cart_contents_total * $percentage_discount) / 100; //( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) * $percentage;	
						$woocommerce->cart->add_fee( 'Discount', number_format((float)$surcharge, 2, '.', ''), true, '' );
					}
					else if($discount_point > 0)
					{
						
						$surcharge = number_format((float)$discount_point, 2, '.', ''); //( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) * $percentage;	
						$woocommerce->cart->add_fee( 'Discount', number_format((float)$surcharge, 2, '.', ''), true, '' );
					
						/*_e("You do not have sufficient discount point");*/
					}
					else 
					{
						/*_e("You do not have sufficient discount point");*/
					}
				}
			}
	}
}
/* woo_discount : End Add Discount field in cart and checkout page */ 

/* woo_discount : Change cart total */ 
add_action('woocommerce_calculate_totals', 'calculate_totals', 9, 1);
function calculate_totals( $totals){
	
	if(is_user_logged_in()) {
		global $current_user;
		$apply_woo_discount= get_user_meta( $current_user->ID, 'apply_woo_discount', true);	
		if($apply_woo_discount == "yes") 
		{
			$settings_Arr = json_decode(get_option("woo-discount-settings"));
			$woo_discount = $settings_Arr->woo_discount;
			$discount_point = 0;
			global $wpdb;
			global $current_user;
			$results_points_user = $wpdb->get_row(
				$wpdb->prepare("select * from ".$wpdb->prefix . "woo_discount_users where user_id=%d",$current_user->ID)
			);
			if($results_points_user != "")
				$discount_point = $results_points_user->discount_point;
			else
				$discount_point = $woo_discount;
			$percentage_discount = $settings_Arr->woo_discount_per;
			
			if(is_cart())
			{
				_e("Remaining Points ".$discount_point,"woo_discount");
			}
			else 
			{
				add_action( 'woocommerce_before_checkout_form', 'amm_remaining_points', 11 , 1);
			}
			
			if($percentage_discount != "" || $percentage_discount != 0 ) {
					$pcnt = ($totals->subtotal * $percentage_discount) / 100; 
					$fee_Arr = array();
					for($fee_cnt=0; $fee_cnt < count($totals->fees); $fee_cnt++)
					{
						$fee_Arr[$totals->fees[$fee_cnt]->name] = $totals->fees[$fee_cnt]->amount;
					}
					if(	$discount_point >= $pcnt)
					{	
				
						if(array_key_exists('Discount',$fee_Arr))
								$totals->cart_contents_total =  number_format((float)$totals->subtotal, 2, '.', '') - number_format((float)$fee_Arr['Discount'], 2, '.', '') - number_format((float)$fee_Arr['Discount'], 2, '.', ''); 											
					}					
					else if($discount_point > 0)
					{
						$surcharge =number_format((float)$discount_point, 2, '.', ''); //( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) * $percentage;	
					
						$fee_Arr['Discount'] = number_format((float)$surcharge, 2, '.', ''); 
						for($fee_cnt=0; $fee_cnt < count($totals->fees); $fee_cnt++)
						{
							if( $totals->fees[$fee_cnt]->name == "Discount")
							$totals->fees[$fee_cnt]->amount = $fee_Arr['Discount'] ;
						
						}
						$totals->cart_contents_total =  $totals->subtotal - number_format((float)$surcharge, 2, '.', '') - number_format((float)$surcharge, 2, '.', '')	; 
						
					}
					else 
					{
						if(is_cart())
						{
							_e("<br />You do not have sufficient discount point","woo_discount");
						}
						else 
						{
							add_action( 'woocommerce_before_checkout_form', 'amm_add_checkout_notice', 11 );

						}
					}	
			}
		}
	}
}
function amm_remaining_points() {
	global $current_user;
	global $wpdb;
	$settings_Arr = json_decode(get_option("woo-discount-settings"));
	$woo_discount = $settings_Arr->woo_discount;
	$results_points_user = $wpdb->get_row(
		$wpdb->prepare("select * from ".$wpdb->prefix . "woo_discount_users where user_id=%d",$current_user->ID)
	);
	
	if($results_points_user != "")
		$discount_point = $results_points_user->discount_point;
	else
		$discount_point = $woo_discount;
    wc_print_notice( __( "Remaining Points $discount_point", 'woocommerce' ), 'notice' );
}
		
function amm_add_checkout_notice() {
    wc_print_notice( __( 'You do not have sufficient discount point', 'woocommerce' ), 'notice' );
}
/* woo_discount : End Change cart total */ 

/* woo_discount : Show my cred option on checkout page  */ 
add_action( 'after_setup_theme', 'customize_woo_mycred_gateway', 999 );
function customize_woo_mycred_gateway() {
	// Remove the gateway hook
	remove_filter( 'woocommerce_available_payment_gateways', 'mycred_woo_available_gateways' );
	// Add our own gateway hook
	add_filter( 'woocommerce_available_payment_gateways', 'mycred_always_show_payment_option_in_woo' );
	function mycred_always_show_payment_option_in_woo( $gateways )
	{
	return $gateways;
	}
}
/* woo_discount : Show my cred option on checkout page  */ 

/* woo_discount :  Create tables */ 
register_activation_hook( __FILE__, 'woodiscount_install_tabels' );
function woodiscount_install_tabels()
{
	
	global $wpdb;
	$table_name = $wpdb->prefix . "woo_discount_users_details";
	$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
	 `woo_discount_id` int(11) NOT NULL AUTO_INCREMENT,
	  `user_id` int(11) NOT NULL,
	  `order_id` int(11) NOT NULL,
	  `discount_point` decimal(10,2) NOT NULL,
	  PRIMARY KEY (`woo_discount_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1 ;";
		
		
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	$table_name = $wpdb->prefix . "woo_discount_users";
	$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
	 `woo_discount_user_id` int(11) NOT NULL AUTO_INCREMENT,
	  `user_id` int(11) NOT NULL,
	  `discount_point` decimal(10,2) NOT NULL,
	  PRIMARY KEY (`woo_discount_user_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1 ;";
	dbDelta($sql);
	$option_value = get_option("woo-discount-settings");
	if($option_value != "")
	{
		$settings_Arr = json_decode($option_value);
		if(isset($settings_Arr->settings_Arr) && $settings_Arr->settings_Arr != "")
		{
			$settings_Arr = array();
			$settings_Arr["woo_discount"] = 100;
			$settings_Arr["woo_discount_per"] = 20;
			update_option("woo-discount-settings",json_encode($settings_Arr));
		}
	}
	else {		
		$settings_Arr = array();
		$settings_Arr["woo_discount"] = 100;
		$settings_Arr["woo_discount_per"] = 20;
		update_option("woo-discount-settings",json_encode($settings_Arr));
	}
}
/* woo_discount : End Create tables */

/* woo_discount : Drop tables */
register_deactivation_hook(__FILE__, 'woo_discount_tables_uninstall_action');
function woo_discount_tables_uninstall_action () 
{
	global $wpdb;	
	$table_name = $wpdb->prefix . "woo_discount_users_details";
	$wpdb->query("delete from $table_name");
	
	$table_name = $wpdb->prefix . "woo_discount_users";
	$wpdb->query("delete from $table_name");
}
/* woo_discount : End Drop tables */

/* woo_discount : Add page in menu */
add_action( 'admin_menu', 'woodiscount_plugin_page' );
function woodiscount_plugin_page(){
	add_menu_page( 'Woo Discount', 'Woo Discount', 'manage_options', 'woodiscount-settings', 'woodiscount_settings_function' ); 
}
function woodiscount_settings_function()
{
	if ( !class_exists( 'WooCommerce' ) ) {
		_e("Woocommerce plugin is require to run this extension","woo_discount");
	}
	if(isset($_REQUEST["woo_discount_config"]))
	{
		if($_REQUEST['woo_discount'] > 0) {
			$settings_Arr = array();
			$settings_Arr["woo_discount"] = $_REQUEST['woo_discount'];
			$settings_Arr["woo_discount_per"] = $_REQUEST['woo_discount_per'];
			update_option("woo-discount-settings",json_encode($settings_Arr));
		}
	}
	?>
	<style>
	.config_divs {
		margin: 10px;
	}
	.config_divs label {
		display: block;
		font-weight: bold;
	} 
	</style>
	<div class="woo_notify">
		<?php 
		if(isset($_REQUEST["woo_discount_config"]) && $_REQUEST['woo_discount'] == "" || (isset($_REQUEST["woo_discount_config"]) && $_REQUEST['woo_discount'] < 0))
		{
			_e("Discount Point can not be less than zero","woo_discount");
		}
		if(isset($_REQUEST["woo_discount_config"]) && $_REQUEST['woo_discount_per'] == "" || (isset($_REQUEST["woo_discount_config"]) && $_REQUEST['woo_discount_per'] < 0))
		{
			_e("Discount percentage can not be less than zero","woo_discount");
		}
		$settings_Arr = json_decode(get_option("woo-discount-settings"));
		$woo_discount = $settings_Arr->woo_discount;
		$woo_discount_per = $settings_Arr->woo_discount_per;
		?>
	</div>
	<form method="post" action="" class="configform">
		<h2>Woo Discount Configuration Settings</h2>
		<div class="config_divs">
			<label><?php _e("Discount Point","woo_discount"); ?></label>				
			<input type="text" name="woo_discount" value="<?php echo $woo_discount; ?>" />
		</div>	
		<div class="config_divs">
			<label><?php _e("Discount %","woo_discount"); ?></label>				
			<input type="text" name="woo_discount_per" value="<?php echo $woo_discount_per; ?>" />
		</div>	
		<input type="submit" value="Save" class="button button-primary button-large" name="woo_discount_config"/>
	</form>
	<?php
}
/* woo_discount : End Add page in menu */

/* woo_discount : On order complete make entry in table and update user discount point */
function woo_discount_order_status_completed( $order_id ) {
	if(is_user_logged_in()) {
		global $current_user;
		$order = new WC_Order( $order_id);
		$apply_woo_discount= get_user_meta( $order->user_id, 'apply_woo_discount', true);	
		if($apply_woo_discount == "yes") 
		{
			$settings_Arr = json_decode(get_option("woo-discount-settings"));
			$woo_discount = $settings_Arr->woo_discount;
			$discount_point = 0;
			global $wpdb;
			global $current_user;
			$results_points_user = $wpdb->get_row(
				$wpdb->prepare("select * from ".$wpdb->prefix . "woo_discount_users where user_id=%d", $order->user_id)
			);
			
			if($results_points_user != "")
				$discount_point = $results_points_user->discount_point;
			else
			{
				$discount_point = $woo_discount;
				$wpdb->insert(
						$wpdb->prefix."woo_discount_users",
						array(
							"user_id"=>$order->user_id,					
							"discount_point"=>number_format((float)$discount_point , 2, '.', '')
						),
						array(
							"%d",
							"%f"
						)
						
					);
			}
			$percentage_discount = $settings_Arr->woo_discount_per;
			
			$order_total =  $order->order_total;

			$order_Res = $order->get_order_item_totals();
			$order_total  = $order->get_subtotal();
			$pcnt = (number_format((float)$order_total , 2, '.', '') * $percentage_discount) / 100; 
					
			if(	$discount_point >= $pcnt)
			{
				
				$check_duplicate_entry = $wpdb->get_row(
					$wpdb->prepare("select * from ".$wpdb->prefix . "woo_discount_users_details where user_id=%d and order_id=%d",$order->user_id,$order_id)
				);
				if($check_duplicate_entry == "" ) {
						$wpdb->insert(
							$wpdb->prefix."woo_discount_users_details",
							array(
								"user_id"=>$order->user_id,
								"order_id"=>$order_id,
								"discount_point"=>number_format((float)$pcnt , 2, '.', '')
								
							),
							array(
								"%d",
								"%d",
								"%f"
							)
							
						);
						$wpdb->update(
							$wpdb->prefix."woo_discount_users",
							array(
											
								"discount_point"=>(number_format((float)$discount_point , 2, '.', '')  - number_format((float)$pcnt , 2, '.', '') ) 
								//'test'=> $discount_point."111====>".$pcnt."=====>".json_encode($settings_Arr). "=====".json_encode($order_Res)."===".(number_format((float)$order_total , 2, '.', '')  ."- ".number_format((float)$pcnt , 2, '.', '') )
							),
							array("user_id"=>$order->user_id),
							array(
								"%f"
							),
							array(
								"%d"
							)
							
						);
				}
			}
			else if($discount_point > 0)
			{
				$check_duplicate_entry = $wpdb->get_row(
					$wpdb->prepare("select * from ".$wpdb->prefix . "woo_discount_users_details where user_id=%d and order_id=%d",$order->user_id,$order_id)
				);
				if($check_duplicate_entry == "" ) {
						$wpdb->insert(
							$wpdb->prefix."woo_discount_users_details",
							array(
								"user_id"=>$order->user_id,
								"order_id"=>$order_id,
								"discount_point"=>number_format((float)$discount_point , 2, '.', '')
								
							),
							array(
								"%d",
								"%d",
								"%f"
							)
							
						);
						$wpdb->update(
							$wpdb->prefix."woo_discount_users",
							array(
											
								"discount_point"=>(number_format((float)$discount_point , 2, '.', '')  - number_format((float)$discount_point , 2, '.', '') ) 
								//'test'=> $discount_point."111====>".$pcnt."=====>".json_encode($settings_Arr). "=====".json_encode($order_Res)."===".(number_format((float)$order_total , 2, '.', '')  ."- ".number_format((float)$pcnt , 2, '.', '') )
							),
							array("user_id"=>$order->user_id),
							array(
								"%f"
							),
							array(
								"%d"
							)
							
						);
				}
			}
		}
	}
}
add_action("woocommerce_checkout_order_processed", "woo_discount_order_status_completed");
//add_action( 'woocommerce_order_status_completed', 'woo_discount_order_status_completed' );
/* woo_discount : End On order complete make entry in table and update user discount point */

function woo_discount_approve_function($user_id)
{
	if($user_id != "")
	{
		global $current_user;
		update_user_meta($user_id,"apply_woo_discount","yes");
	}	
}
add_action("woo_discount_approve","woo_discount_approve_function",10,1);


?>