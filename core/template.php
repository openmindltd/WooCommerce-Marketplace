<?php
/**
 * Holds the marketplace template functions
 *
 * @package		Marketplace
 * @subpackage	Main
 * @author		Boris Glumpler
 * @copyright 	Copyright (c) 2010 - 2012, Themekraft
 * @link		https://shabushabu.eu
 * @license		http://www.opensource.org/licenses/gpl-3.0.php GPL License
 * @since 		Marketplace 0.9
 * @filesource
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Look for the templates in the proper places
 *
 * @since	Marketplace 0.9
 * @param	string	$found_template		The found template
 * @param	array 	$templates			All possible templates
 */
function mp_load_template_filter( $found_template, $templates ) {
    if( bp_is_current_component( 'earnings' ) || bp_is_current_component( 'settings' ) ) {
        foreach( (array)$templates as $template ) {
            if( file_exists( STYLESHEETPATH .'/'. $template ) )
                $filtered_templates[] = STYLESHEETPATH .'/'. $template;

            else
                $filtered_templates[] = marketplace()->plugin_dir .'templates/'. $template;
        }

        return apply_filters( 'mp_load_template_filter', $filtered_templates[0] );
    }
    else
        return $found_template;
}
add_filter( 'bp_located_template', 'mp_load_template_filter', 10, 2 );

/**
 * Load a template in the correct order
 *
 * @since	Marketplace 0.9
 * @param	string	$template_name		The template to load
 */
function mp_load_template( $template_name ) {
	if( file_exists( STYLESHEETPATH .'/marketplace/'. $template_name .'.php' ) )
		$located = STYLESHEETPATH .'/marketplace/'. $template_name .'.php';

	elseif( file_exists( TEMPLATEPATH .'/marketplace/'. $template_name .'.php' ) )
		$located = TEMPLATEPATH .'/marketplace/'. $template_name .'.php';

	else
		$located = marketplace()->plugin_dir .'templates/marketplace/'. $template_name .'.php';

	include( $located );
}

/**
 * Display the earnings for an author
 *
 * @since  	Marketplace 0.9
 */
function mp_earnings_template() {
	bp_core_load_template( apply_filters( 'marketplace_earnings_template', 'marketplace/earnings' ) );
}

/**
 * Display the user license keys
 *
 * @since  	Marketplace 0.9.1
 */
function mp_licenses_template() {
	if( ! bp_is_active( 'settings' ) )
		return false;

	add_action( 'bp_template_title', 	'mp_licenses_title' 	);
	add_action( 'bp_template_content', 	'mp_licenses_content' 	);

	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}


/**
 * Display the marketplace settings title
 *
 * @since  	Marketplace 0.9.1
 */
function mp_licenses_title() {
	echo '<h3>'. __( 'License Keys', 'marketplace' ) .'</h3>';
}

/**
 * Display the marketplace settings content
 *
 * @since  	Marketplace 0.9
 */
function mp_licenses_content() {
	global $wpdb;

	$keys = $wpdb->get_results( $wpdb->prepare( "
		SELECT wsl.*
		FROM {$wpdb->prefix}woocommerce_software_licences wsl
		RIGHT JOIN {$wpdb->postmeta} pm
		ON pm.post_id = wsl.order_id
		WHERE pm.meta_key = '_customer_user'
		AND pm.meta_value = %s
	", bp_displayed_user_id() ) );

	?>
	<table class="license-keys-table" id="license-keys-table">
		<thead>
			<tr>
				<th class="product"><?php _e( 'Product', 'marketplace' ) ?></th>
				<th class="api-key"><?php _e( 'API Key', 'marketplace' ) ?></th>
				<th class="activation-email"><?php _e( 'Activation Email', 'marketplace' ) ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		if( count( $keys ) > 0 ) :
			foreach( $keys as $key ) :
				$product = get_post( $key->software_product_id );
				?>
				<tr>
					<td class="product"><a href="<?php echo get_permalink( $product ) ?>"><?php echo esc_html( $product->post_title ) ?></a></td>
					<td class="api-key"><?php echo esc_html( $key->licence_key ) ?></td>
					<td class="activation-email"><?php echo esc_html( $key->activation_email ) ?></td>
				</tr>
				<?php
			endforeach;
		else :
			?>
			<tr>
				<td colspan="2"><?php _e( 'No API keys yet.', 'marketplace' ) ?></td>
			</tr>
			<?php
		endif;
		?>
		</tbody>
	</table>
	<?php
}

/**
 * Display the marketplace settings for an author
 *
 * @since  	Marketplace 0.9
 */
function mp_settings_template() {
	global $bp_settings_updated;

	if( ! bp_is_active( 'settings' ) )
		return false;

	$bp_settings_updated = false;

	if( isset( $_POST['submit'] ) )	{
		check_admin_referer( 'mp_marketplace_settings' );

		if( isset( $_POST['paypal-email'] ) && is_email( $_POST['paypal-email'] ) ) :
			$email = wp_filter_kses( $_POST['paypal-email'] );

			update_user_meta( bp_displayed_user_id(), 'mp_paypal_address', $email );
		endif;

		if( isset( $_POST['bank-details'] ) ) :
			$details = array();

			foreach( $_POST['bank-details'] as $key => $detail ) :
				$details[$key] = wp_filter_kses( $detail );
			endforeach;

			update_user_meta( bp_displayed_user_id(), 'mp_bank_details', $details );
		endif;

		$bp_settings_updated = true;
	}

	add_action( 'bp_template_title', 	'mp_settings_title' 	);
	add_action( 'bp_template_content', 	'mp_settings_content' 	);

	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

/**
 * Display the marketplace settings title
 *
 * @since  	Marketplace 0.9
 */
function mp_settings_title() {
	echo '<h3>'. __( 'Marketplace Settings', 'marketplace' ) .'</h3>';
}

/**
 * Display the marketplace settings content
 *
 * @since  	Marketplace 0.9
 */
function mp_settings_content() {
	global $bp_settings_updated;

	$user_notice 	= get_option( 'mp_user_notice' );
	$paypal_address = get_user_meta( bp_displayed_user_id(), 'mp_paypal_address', true );
	$bank_details	= get_user_meta( bp_displayed_user_id(), 'mp_bank_details',   true );

	if ( $bp_settings_updated ) { ?>
		<div id="message" class="updated fade">
			<p><?php _e( 'Changes Saved.', 'marketplace' ) ?></p>
		</div>
	<?php } ?>

	<div id="mp-user-notice">
		<?php echo wpautop( $user_notice ); ?>
	</div>

	<form action="<?php echo bp_loggedin_user_domain() . bp_get_settings_slug() . '/marketplace/' ?>" method="post" id="settings-form" class="standard-form">
		<?php wp_nonce_field( 'mp_marketplace_settings' ); ?>

        <p>
        	<label for="paypal-email"><?php _e( 'PayPal Address', 'events' ) ?></label>
            <input type="text" name="paypal-email" id="paypal-email" value="<?php echo esc_attr( $paypal_address ) ?>" /><br />
            <small><?php _e( 'Enter your PayPal email address to receive your commission.', 'marketplace' ) ?></small>
        </p>

        <p>
        	<label for="bank_details_bank_name"><?php _e( 'Bank Details', 'events' ) ?></label>
            <input type="text" name="bank-details[bank_name]" id="bank_details_bank_name" value="<?php echo esc_attr( $bank_details['bank_name'] ) ?>" /><br />
            <small><?php _e( 'Bank Name', 'marketplace' ) ?></small>
        </p>

        <p>
            <input type="text" name="bank-details[bank_number]" id="bank_details_bank_number" value="<?php echo esc_attr( $bank_details['bank_number'] ) ?>" /><br />
            <small><?php _e( 'Bank Number', 'marketplace' ) ?></small>
        </p>

        <p>
            <input type="text" name="bank-details[account_number]" id="bank_details_account_number" value="<?php echo esc_attr( $bank_details['account_number'] ) ?>" /><br />
            <small><?php _e( 'Account Number', 'marketplace' ) ?></small>
        </p>

        <p>
            <input type="text" name="bank-details[swift]" id="bank_details_swift" value="<?php echo esc_attr( $bank_details['swift'] ) ?>" /><br />
            <small><?php _e( 'BIC/Swift-Code', 'marketplace' ) ?></small>
        </p>

        <p>
            <input type="text" name="bank-details[iban]" id="bank_details_iban" value="<?php echo esc_attr( $bank_details['iban'] ) ?>" /><br />
            <small><?php _e( 'IBAN', 'marketplace' ) ?></small>
        </p>

		<div class="submit">
			<p><input type="submit" name="submit" value="<?php _e( 'Save Changes', 'marketplace' ) ?>" id="submit" class="auto"/></p>
		</div>
	</form>
	<?php
}

/**
 * Check if the passed user already has at least 1 published product
 *
 * @since  	Marketplace 0.9.1
 */
function mp_has_products( $user_id = 0 ) {
	if( empty( $user_id ) )
		$user_id = bp_displayed_user_id();

	$products = get_posts( array(
		'post_type' 	=> 'product',
		'numberposts' 	=> 1,
		'author'		=> $user_id
	) );

	if( count( $products ) > 0 )
		return true;

	return false;
}

/* End of file template.php */
/* Location: ./core/template.php */