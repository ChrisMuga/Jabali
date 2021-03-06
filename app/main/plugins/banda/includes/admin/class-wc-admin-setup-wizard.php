<?php
/**
 * Setup Wizard Class
 *
 * Takes new users through some basic steps to setup their store.
 *
 * @author      Jabali
 * @category    Admin
 * @package     Banda/Admin
 * @version     2.6.0
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Setup_Wizard class.
 */
class WC_Admin_Setup_Wizard {

	/** @var string Currenct Step */
	private $step   = '';

	/** @var array Steps for the setup wizard */
	private $steps  = array();

	/** @var array Tweets user can optionally send after install */
	private $tweets = array(
		'Someone give me woo-t, I just set up a new store with #Jabali and @Banda!',
		'Someone give me high five, I just set up a new store with #Jabali and @Banda!'
	);

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		if ( apply_filters( 'banda_enable_setup_wizard', true ) && current_user_can( 'manage_banda' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menus' ) );
			add_action( 'admin_init', array( $this, 'setup_wizard' ) );
		}
	}

	/**
	 * Add admin menus/screens.
	 */
	public function admin_menus() {
		add_dashboard_page( '', '', 'manage_options', 'wc-setup', '' );
	}

	/**
	 * Show the setup wizard.
	 */
	public function setup_wizard() {
		if ( empty( $_GET['page'] ) || 'wc-setup' !== $_GET['page'] ) {
			return;
		}
		$this->steps = array(
			'introduction' => array(
				'name'    =>  __( 'Introduction', 'banda' ),
				'view'    => array( $this, 'wc_setup_introduction' ),
				'handler' => ''
			),
			'pages' => array(
				'name'    =>  __( 'Page Setup', 'banda' ),
				'view'    => array( $this, 'wc_setup_pages' ),
				'handler' => array( $this, 'wc_setup_pages_save' )
			),
			'locale' => array(
				'name'    =>  __( 'Location', 'banda' ),
				'view'    => array( $this, 'wc_setup_locale' ),
				'handler' => array( $this, 'wc_setup_locale_save' )
			),
			'shipping_taxes' => array(
				'name'    =>  __( 'Tax &amp; Deliveries', 'banda' ),
				'view'    => array( $this, 'wc_setup_shipping_taxes' ),
				'handler' => array( $this, 'wc_setup_shipping_taxes_save' ),
			),
			'payments' => array(
				'name'    =>  __( 'Payments', 'banda' ),
				'view'    => array( $this, 'wc_setup_payments' ),
				'handler' => array( $this, 'wc_setup_payments_save' ),
			),
			'next_steps' => array(
				'name'    =>  __( 'Ready!', 'banda' ),
				'view'    => array( $this, 'wc_setup_ready' ),
				'handler' => ''
			)
		);
		$this->step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );
		$suffix     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'select2', WC()->plugin_url() . '/assets/js/select2/select2' . $suffix . '.js', array( 'jquery' ), '3.5.2' );
		wp_register_script( 'wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select' . $suffix . '.js', array( 'jquery', 'select2' ), WC_VERSION );
		wp_localize_script( 'wc-enhanced-select', 'wc_enhanced_select_params', array(
			'i18n_matches_1'            => _x( 'One result is available, press enter to select it.', 'enhanced select', 'banda' ),
			'i18n_matches_n'            => _x( '%qty% results are available, use up and down arrow keys to navigate.', 'enhanced select', 'banda' ),
			'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'banda' ),
			'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'banda' ),
			'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'banda' ),
			'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'banda' ),
			'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'banda' ),
			'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'banda' ),
			'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'banda' ),
			'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'banda' ),
			'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'banda' ),
			'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'banda' ),
			'ajax_url'                  => admin_url( 'admin-ajax.php' ),
			'search_products_nonce'     => wp_create_nonce( 'search-products' ),
			'search_customers_nonce'    => wp_create_nonce( 'search-customers' )
		) );
		wp_enqueue_style( 'banda_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
		wp_enqueue_style( 'wc-setup', WC()->plugin_url() . '/assets/css/wc-setup.css', array( 'dashicons', 'install' ), WC_VERSION );

		wp_register_script( 'wc-setup', WC()->plugin_url() . '/assets/js/admin/wc-setup.min.js', array( 'jquery', 'wc-enhanced-select', 'jquery-blockui' ), WC_VERSION );
		wp_localize_script( 'wc-setup', 'wc_setup_params', array(
			'locale_info' => json_encode( include( WC()->plugin_path() . '/i18n/locale-info.php' ) )
		) );

		if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
			call_user_func( $this->steps[ $this->step ]['handler'] );
		}

		ob_start();
		$this->setup_wizard_header();
		$this->setup_wizard_steps();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		exit;
	}

	public function get_next_step_link() {
		$keys = array_keys( $this->steps );
		return add_query_arg( 'step', $keys[ array_search( $this->step, array_keys( $this->steps ) ) + 1 ] );
	}

	/**
	 * Setup Wizard Header.
	 */
	public function setup_wizard_header() {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title><?php _e( 'Banda &rsaquo; Setup Wizard', 'banda' ); ?></title>
			<?php wp_print_scripts( 'wc-setup' ); ?>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_head' ); ?>
		</head>
		<body class="wc-setup wp-core-ui">
			<h1 id="wc-logo"><a href="http://mtaandao.co.ke/"><img src="<?php echo WC()->plugin_url(); ?>/assets/images/banda_logo.png" alt="Banda" /></a></h1>
		<?php
	}

	/**
	 * Setup Wizard Footer.
	 */
	public function setup_wizard_footer() {
		?>
			<?php if ( 'next_steps' === $this->step ) : ?>
				<a class="wc-return-to-dashboard" href="<?php echo esc_url( admin_url() ); ?>"><?php _e( 'Return to the Jabali Dashboard', 'banda' ); ?></a>
			<?php endif; ?>
			</body>
		</html>
		<?php
	}

	/**
	 * Output the steps.
	 */
	public function setup_wizard_steps() {
		$ouput_steps = $this->steps;
		array_shift( $ouput_steps );
		?>
		<ol class="wc-setup-steps">
			<?php foreach ( $ouput_steps as $step_key => $step ) : ?>
				<li class="<?php
					if ( $step_key === $this->step ) {
						echo 'active';
					} elseif ( array_search( $this->step, array_keys( $this->steps ) ) > array_search( $step_key, array_keys( $this->steps ) ) ) {
						echo 'done';
					}
				?>"><?php echo esc_html( $step['name'] ); ?></li>
			<?php endforeach; ?>
		</ol>
		<?php
	}

	/**
	 * Output the content for the current step.
	 */
	public function setup_wizard_content() {
		echo '<div class="wc-setup-content">';
		call_user_func( $this->steps[ $this->step ]['view'] );
		echo '</div>';
	}

	/**
	 * Introduction step.
	 */
	public function wc_setup_introduction() {
		?>
		<h1><?php _e( 'Welcome to Banda Ecommerce!', 'banda' ); ?></h1>
		<p><?php _e( 'Banda helps you set up and deploy your online store in a few easy steps and receive payments conveniently.</strong>', 'banda' ); ?></p>
		<p><?php _e( 'If you wish to set up Banda later, you can skip and return to the Jabali dashboard!', 'banda' ); ?></p>
		<p class="wc-setup-actions step">
			<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button-primary button button-large button-next"><?php _e( 'Let\'s Roll!', 'banda' ); ?></a>
			<a href="<?php echo esc_url( admin_url() ); ?>" class="button button-large"><?php _e( 'Later, man.', 'banda' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Page setup.
	 */
	public function wc_setup_pages() {
		?>
		<center><h1><?php _e( 'Page Setup', 'banda' ); ?></h1></center>
		<form method="post">
			<p><?php printf( __( 'The following essential %spages%s will be created automatically if they do not already exist:', 'banda' ), '<a href="' . esc_url( admin_url( 'edit.php?post_type=page' ) ) . '" target="_blank">', '</a>' ); ?></p>
			<table class="wc-setup-pages" cellspacing="0">
				<thead>
					<tr>
						<th class="page-name"><?php _e( 'Page Name', 'banda' ); ?></th>
						<th class="page-description"><?php _e( 'Description', 'banda' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="page-name"><?php echo _x( 'Showcase', 'Page title', 'banda' ); ?></td>
						<td><?php _e( 'The showcase page will display your products.', 'banda' ); ?></td>
					</tr>
					<tr>
						<td class="page-name"><?php echo _x( 'Cart', 'Page title', 'banda' ); ?></td>
						<td><?php _e( 'The cart page will be where the customers go to view their cart and begin checkout.', 'banda' ); ?></td>
					</tr>
					<tr>
						<td class="page-name"><?php echo _x( 'Checkout', 'Page title', 'banda' ); ?></td>
						<td>
							<?php _e( 'The checkout page will be where the customers go to pay for their items.', 'banda' ); ?>
						</td>
					</tr>
					<tr>
						<td class="page-name"><?php echo _x( 'Pay', 'Page title', 'banda' ); ?></td>
						<td>
							<?php _e( 'The payment page will be where the customers go to process payment for their items.', 'banda' ); ?>
						</td>
					</tr>
					<tr>
						<td class="page-name"><?php echo _x( 'My Account', 'Page title', 'banda' ); ?></td>
						<td>
							<?php _e( 'Registered customers will be able to manage their account details and view past orders on this page.', 'banda' ); ?>
						</td>
					</tr>
					<tr>
						<td class="page-name"><?php echo _x( 'Terms & Conditions', 'Page title', 'banda' ); ?></td>
						<td>
							<?php _e( 'You will edit this to show terms and conditions for your online store.', 'banda' ); ?>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'banda' ); ?>" name="save_step" />
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php _e( 'Skip this step', 'banda' ); ?></a>
				<?php wp_nonce_field( 'wc-setup' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Save Page Settings.
	 */
	public function wc_setup_pages_save() {
		check_admin_referer( 'wc-setup' );

		WC_Install::create_pages();
		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Locale settings.
	 */
	public function wc_setup_locale() {
		$user_location  = WC_Geolocation::geolocate_ip();
		$country        = ! empty( $user_location['country'] ) ? $user_location['country'] : 'KE';
		$state          = ! empty( $user_location['state'] ) ? $user_location['state'] : '*';
		$state          = 'KE' === $country && '*' === $state ? '1' : $state;

		// Defaults
		$currency       = get_option( 'banda_currency', 'KSH' );
		$currency_pos   = get_option( 'banda_currency_pos', 'left' );
		$decimal_sep    = get_option( 'banda_price_decimal_sep', '.' );
		$num_decimals   = get_option( 'banda_price_num_decimals', '2' );
		$thousand_sep   = get_option( 'banda_price_thousand_sep', ',' );
		$dimension_unit = get_option( 'banda_dimension_unit', 'cm' );
		$weight_unit    = get_option( 'banda_weight_unit', 'kg' );
		?>
		<center><h1><?php _e( 'Your Location', 'banda' ); ?></h1></center>
		<form method="post">
			<table class="form-table">
				<tr>
					<th scope="row"><label for="store_location"><?php _e( 'Where is your store based?', 'banda' ); ?></label></th>
					<td>
					<select id="store_location" name="store_location" style="width:100%;" required data-placeholder="<?php esc_attr_e( 'Choose a country&hellip;', 'banda' ); ?>" class="wc-enhanced-select">
							<?php WC()->countries->country_dropdown_options( $country, $state ); ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="currency_code"><?php _e( 'Which currency will your store use?', 'banda' ); ?></label></th>
					<td>
						<select id="currency_code" name="currency_code" style="width:100%;" data-placeholder="<?php esc_attr_e( 'Choose a currency&hellip;', 'banda' ); ?>" class="wc-enhanced-select">
							<option value=""><?php _e( 'Choose a currency&hellip;', 'banda' ); ?></option>
							<?php
							foreach ( get_banda_currencies() as $code => $name ) {
								echo '<option value="' . esc_attr( $code ) . '" ' . selected( $currency, $code, false ) . '>' . esc_html( $name . ' (' . get_banda_currency_symbol( $code ) . ')' ) . '</option>';
							}
							?>
						</select>
						<span class="description"><?php printf( __( 'If your currency is not listed you can %sadd it later%s.', 'banda' ), '<a href="https://mtaandao.co.ke/docs/banda/document/add-a-custom-currency-symbol/" target="_blank">', '</a>' ); ?></span>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="currency_pos"><?php _e( 'Currency Position', 'banda' ); ?></label></th>
					<td>
						<select id="currency_pos" name="currency_pos" class="wc-enhanced-select">
							<option value="left" <?php selected( $currency_pos, 'left' ); ?>><?php echo __( 'Left', 'banda' ); ?></option>
							<option value="right" <?php selected( $currency_pos, 'right' ); ?>><?php echo __( 'Right', 'banda' ); ?></option>
							<option value="left_space" <?php selected( $currency_pos, 'left_space' ); ?>><?php echo __( 'Left with space', 'banda' ); ?></option>
							<option value="right_space" <?php selected( $currency_pos, 'right_space' ); ?>><?php echo __( 'Right with space', 'banda' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="thousand_sep"><?php _e( 'Thousand Separator', 'banda' ); ?></label></th>
					<td>
						<input type="text" id="thousand_sep" name="thousand_sep" size="2" value="<?php echo esc_attr( $thousand_sep ) ; ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="decimal_sep"><?php _e( 'Decimal Separator', 'banda' ); ?></label></th>
					<td>
						<input type="text" id="decimal_sep" name="decimal_sep" size="2" value="<?php echo esc_attr( $decimal_sep ) ; ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="num_decimals"><?php _e( 'Number of Decimals', 'banda' ); ?></label></th>
					<td>
						<input type="text" id="num_decimals" name="num_decimals" size="2" value="<?php echo esc_attr( $num_decimals ) ; ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="weight_unit"><?php _e( 'Which unit should be used for product weights?', 'banda' ); ?></label></th>
					<td>
						<select id="weight_unit" name="weight_unit" class="wc-enhanced-select">
							<option value="kg" <?php selected( $weight_unit, 'kg' ); ?>><?php echo __( 'kg', 'banda' ); ?></option>
							<option value="g" <?php selected( $weight_unit, 'g' ); ?>><?php echo __( 'g', 'banda' ); ?></option>
							<option value="lbs" <?php selected( $weight_unit, 'lbs' ); ?>><?php echo __( 'lbs', 'banda' ); ?></option>
							<option value="oz" <?php selected( $weight_unit, 'oz' ); ?>><?php echo __( 'oz', 'banda' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dimension_unit"><?php _e( 'Which unit should be used for product dimensions?', 'banda' ); ?></label></th>
					<td>
						<select id="dimension_unit" name="dimension_unit" class="wc-enhanced-select">
							<option value="m" <?php selected( $dimension_unit, 'm' ); ?>><?php echo __( 'm', 'banda' ); ?></option>
							<option value="cm" <?php selected( $dimension_unit, 'cm' ); ?>><?php echo __( 'cm', 'banda' ); ?></option>
							<option value="mm" <?php selected( $dimension_unit, 'mm' ); ?>><?php echo __( 'mm', 'banda' ); ?></option>
							<option value="in" <?php selected( $dimension_unit, 'in' ); ?>><?php echo __( 'in', 'banda' ); ?></option>
							<option value="yd" <?php selected( $dimension_unit, 'yd' ); ?>><?php echo __( 'yd', 'banda' ); ?></option>
						</select>
					</td>
				</tr>
			</table>
			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'banda' ); ?>" name="save_step" />
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php _e( 'Skip this step', 'banda' ); ?></a>
				<?php wp_nonce_field( 'wc-setup' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Save Locale Settings.
	 */
	public function wc_setup_locale_save() {
		check_admin_referer( 'wc-setup' );

		$store_location = sanitize_text_field( $_POST['store_location'] );
		$currency_code  = sanitize_text_field( $_POST['currency_code'] );
		$currency_pos   = sanitize_text_field( $_POST['currency_pos'] );
		$decimal_sep    = sanitize_text_field( $_POST['decimal_sep'] );
		$num_decimals   = sanitize_text_field( $_POST['num_decimals'] );
		$thousand_sep   = sanitize_text_field( $_POST['thousand_sep'] );
		$weight_unit    = sanitize_text_field( $_POST['weight_unit'] );
		$dimension_unit = sanitize_text_field( $_POST['dimension_unit'] );

		update_option( 'banda_default_country', $store_location );
		update_option( 'banda_currency', $currency_code );
		update_option( 'banda_currency_pos', $currency_pos );
		update_option( 'banda_price_decimal_sep', $decimal_sep );
		update_option( 'banda_price_num_decimals', $num_decimals );
		update_option( 'banda_price_thousand_sep', $thousand_sep );
		update_option( 'banda_weight_unit', $weight_unit );
		update_option( 'banda_dimension_unit', $dimension_unit );

		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Shipping and taxes.
	 */
	public function wc_setup_shipping_taxes() {
		?>
		<center><h1><?php _e( 'Deliveries &amp; Tax Setup', 'banda' ); ?></h1></center>
		<form method="post">
			<p><?php _e( 'If you will be charging sales tax, or delivering physical goods to customers, you can enable these below. This is optional and can be changed later.', 'banda' ); ?></p>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="banda_calc_shipping"><?php _e( 'Will you be delivering products?', 'banda' ); ?></label></th>
					<td>
						<input type="checkbox" id="banda_calc_shipping" <?php checked( get_option( 'banda_ship_to_countries', '' ) !== 'disabled', true ); ?> name="banda_calc_shipping" class="input-checkbox" value="1" />
						<label for="banda_calc_shipping"><?php _e( 'Yes, I will be delivering physical goods to customers', 'banda' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="banda_calc_taxes"><?php _e( 'Will you be charging sales tax?', 'banda' ); ?></label></th>
					<td>
						<input type="checkbox" <?php checked( get_option( 'banda_calc_taxes', 'no' ), 'yes' ); ?> id="banda_calc_taxes" name="banda_calc_taxes" class="input-checkbox" value="1" />
						<label for="banda_calc_taxes"><?php _e( 'Yes, I will be charging sales tax', 'banda' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="banda_prices_include_tax"><?php _e( 'How will you enter product prices?', 'banda' ); ?></label></th>
					<td>
						<label><input type="radio" <?php checked( get_option( 'banda_prices_include_tax', 'no' ), 'yes' ); ?> id="banda_prices_include_tax" name="banda_prices_include_tax" class="input-radio" value="yes" /> <?php _e( 'I will enter prices inclusive of tax', 'banda' ); ?></label><br/>
						<label><input type="radio" <?php checked( get_option( 'banda_prices_include_tax', 'no' ), 'no' ); ?> id="banda_prices_include_tax" name="banda_prices_include_tax" class="input-radio" value="no" /> <?php _e( 'I will enter prices exclusive of tax', 'banda' ); ?></label>
					</td>
				</tr>
				<?php
					$locale_info = include( WC()->plugin_path() . '/i18n/locale-info.php' );
					$tax_rates   = array();
					$country     = WC()->countries->get_base_country();
					$state       = WC()->countries->get_base_state();

					if ( isset( $locale_info[ $country ] ) ) {
						if ( isset( $locale_info[ $country ]['tax_rates'][ $state ] ) ) {
							$tax_rates = $locale_info[ $country ]['tax_rates'][ $state ];
						} elseif ( isset( $locale_info[ $country ]['tax_rates'][''] ) ) {
							$tax_rates = $locale_info[ $country ]['tax_rates'][''];
						}
						if ( isset( $locale_info[ $country ]['tax_rates']['*'] ) ) {
							$tax_rates = array_merge( $locale_info[ $country ]['tax_rates']['*'], $tax_rates );
						}
					}
					if ( $tax_rates ) {
						?>
						<tr class="tax-rates">
							<td colspan="2">
								<p><?php printf( __( 'The following tax rates will be imported automatically for you. You can read more about taxes in %1$sour documentation%2$s.', 'banda' ), '<a href="https://mtaandao.co.ke/docs/banda/document/setting-up-taxes-in-banda/" target="_blank">', '</a>' ); ?></p>
								<div class="importing-tax-rates">
									<table class="tax-rates">
										<thead>
											<tr>
												<th><?php _e( 'Country', 'banda' ); ?></th>
												<th><?php _e( 'State', 'banda' ); ?></th>
												<th><?php _e( 'Rate (%)', 'banda' ); ?></th>
												<th><?php _e( 'Name', 'banda' ); ?></th>
											</tr>
										</thead>
										<tbody>
											<?php
												foreach ( $tax_rates as $rate ) {
													?>
													<tr>
														<td class="readonly"><?php echo esc_attr( $rate['country'] ); ?></td>
														<td class="readonly"><?php echo esc_attr( $rate['state'] ? $rate['state'] : '*' ); ?></td>
														<td class="readonly"><?php echo esc_attr( $rate['rate'] ); ?></td>
														<td class="readonly"><?php echo esc_attr( $rate['name'] ); ?></td>
													</tr>
													<?php
												}
											?>
										</tbody>
									</table>
								</div>
								<p class="description"><?php printf( __( 'You may need to add/edit rates based on your products or business location which can be done from the %1$stax settings%2$s screen. If in doubt, speak to an accountant.', 'banda' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=tax' ) . '" target="_blank">', '</a>' ); ?></p>
							</td>
						</tr>
						<?php
					}
				?>
			</table>
			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'banda' ); ?>" name="save_step" />
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php _e( 'Skip this step', 'banda' ); ?></a>
				<?php wp_nonce_field( 'wc-setup' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Save shipping and tax options.
	 */
	public function wc_setup_shipping_taxes_save() {
		check_admin_referer( 'wc-setup' );

		$enable_shipping = isset( $_POST['banda_calc_shipping'] );
		$enable_taxes    = isset( $_POST['banda_calc_taxes'] );

		if ( $enable_shipping ) {
			update_option( 'banda_ship_to_countries', '' );
			WC_Admin_Notices::add_notice( 'no_shipping_methods' );
		} else {
			update_option( 'banda_ship_to_countries', 'disabled' );
		}

		update_option( 'banda_calc_taxes', $enable_taxes ? 'yes' : 'no' );
		update_option( 'banda_prices_include_tax', sanitize_text_field( $_POST['banda_prices_include_tax'] ) );

		if ( $enable_taxes ) {
			$locale_info = include( WC()->plugin_path() . '/i18n/locale-info.php' );
			$tax_rates   = array();
			$country     = WC()->countries->get_base_country();
			$state       = WC()->countries->get_base_state();

			if ( isset( $locale_info[ $country ] ) ) {
				if ( isset( $locale_info[ $country ]['tax_rates'][ $state ] ) ) {
					$tax_rates = $locale_info[ $country ]['tax_rates'][ $state ];
				} elseif ( isset( $locale_info[ $country ]['tax_rates'][''] ) ) {
					$tax_rates = $locale_info[ $country ]['tax_rates'][''];
				}
				if ( isset( $locale_info[ $country ]['tax_rates']['*'] ) ) {
					$tax_rates = array_merge( $locale_info[ $country ]['tax_rates']['*'], $tax_rates );
				}
			}
			if ( $tax_rates ) {
				$loop = 0;
				foreach ( $tax_rates as $rate ) {
					$tax_rate = array(
						'tax_rate_country'  => $rate['country'],
						'tax_rate_state'    => $rate['state'],
						'tax_rate'          => $rate['rate'],
						'tax_rate_name'     => $rate['name'],
						'tax_rate_priority' => isset( $rate['priority'] ) ? absint( $rate['priority'] ) : 1,
						'tax_rate_compound' => 0,
						'tax_rate_shipping' => $rate['shipping'] ? 1 : 0,
						'tax_rate_order'    => $loop ++,
						'tax_rate_class'    => ''
					);
					WC_Tax::_insert_tax_rate( $tax_rate );
				}
			}
		}

		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Simple array of gateways to show in wizard.
	 * @return array
	 */
	protected function get_wizard_payment_gateways() {
		$gateways = array(
			'paypal-braintree' => array(
				'name'        => __( 'PayPal by Braintree', 'banda' ),
				'image'       => WC()->plugin_url() . '/assets/images/paypal-braintree.png',
				'description' => sprintf( __( 'Safe and secure payments using credit cards or your customer\'s PayPal account. %sLearn more about PayPal%s.', 'banda' ), '<a href="https://jabali.github.io/plugins/banda-gateway-paypal-powered-by-braintree/" target="_blank">', '</a>' ),
				'class'       => 'featured featured-row-last',
				'repo-slug'   => 'banda-gateway-paypal-powered-by-braintree',
			),
			'paypal-ec' => array(
				'name'        => __( 'PayPal Express Checkout', 'banda' ),
				'image'       => WC()->plugin_url() . '/assets/images/paypal.png',
				'description' => sprintf( __( 'Safe and secure payments using credit cards or your customer\'s PayPal account. %sLearn more about PayPal%s.', 'banda' ), '<a href="https://jabali.github.io/plugins/banda-gateway-paypal-express-checkout/" target="_blank">', '</a>' ),
				'class'       => 'featured featured-row-last',
				'repo-slug'   => 'banda-gateway-paypal-express-checkout',
			),
			'stripe' => array(
				'name'        => __( 'JamboPay', 'banda' ),
				'image'       => WC()->plugin_url() . '/assets/images/stripe.png',
				'description' => sprintf( __( 'A modern and robust way to accept credit card payments on your store. %sLearn more about JamboPay%s.', 'banda' ), '<a href="https://jambopay.com/" target="_blank">', '</a>' ),
				'class'       => 'featured featured-row-last',
			),
			'mobile' => array(
				'name'        => __( 'Mobile Payments', 'banda' ),
				'image'       => WC()->plugin_url() . '/assets/images/mpesa.png',
				'description' => sprintf( __( 'Pay using your mobile phone via MPesa, Airtel Money and more. %sLearn more about Lipa Na MPesa%s.', 'banda' ), '<a href="https://safaricom.co.ke/lipa-na-mpesa/" target="_blank">', '</a>' ),
				'class'       => 'featured featured-row-first',
				//'repo-slug'   => 'banda-gateway-mobile',
			),
			'pesapal' => array(
				'name'        => __( 'Pesapal Checkout', 'banda' ),
				'image'       => WC()->plugin_url() . '/assets/images/paypal.png',
				'description' => sprintf( __( 'Safe and secure payments using credit cards or your customer\'s Pesapal account. %sLearn more about Pesapal%s.', 'banda' ), '<a href="https://jabali.github.io/plugins/banda-gateway-paypal-express-checkout/" target="_blank">', '</a>' ),
				'class'       => 'featured featured-row-last',
				//'repo-slug'   => 'banda-gateway-pesapal',
			),
			'paypal' => array(
				'name'        => __( 'PayPal Standard', 'banda' ),
				'description' => __( 'Accept payments via PayPal using account balance or credit card.', 'banda' ),
				'image'       => WC()->plugin_url() . '/assets/images/mpesa.png',
				'class'       => '',
				'settings'    => array(
					'email' => array(
						'label'       => __( 'PayPal email address', 'banda' ),
						'type'        => 'email',
						'value'       => get_option( 'admin_email' ),
						'placeholder' => __( 'PayPal email address', 'banda' ),
					),
				),
			),
			'cheque' => array(
				'name'        => _x( 'Check Payments', 'Check payment method', 'banda' ),
				'description' => __( 'A simple offline gateway that lets you accept a check as method of payment.', 'banda' ),
				'image'       => '',
				'class'       => '',
			),
			'bacs' => array(
				'name'        => __( 'Bank Transfer (BACS) Payments', 'banda' ),
				'description' => __( 'A simple offline gateway that lets you accept BACS payment.', 'banda' ),
				'image'       => '',
				'class'       => '',
			),
			'cod' => array(
				'name'        => __( 'Cash on Delivery', 'banda' ),
				'description' => __( 'A simple offline gateway that lets you accept cash on delivery.', 'banda' ),
				'image'       => '',
				'class'       => '',
			)
		);

		$country = WC()->countries->get_base_country();

		if ( 'US' === $country ) {
			unset( $gateways['paypal-ec'] );
		} else {
			unset( $gateways['paypal-braintree'] );
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			unset( $gateways['paypal-braintree'] );
			unset( $gateways['paypal-ec'] );
			unset( $gateways['stripe'] );
		}

		return $gateways;
	}

	/**
	 * Payments Step.
	 */
	public function wc_setup_payments() {
		$gateways = $this->get_wizard_payment_gateways();
		?>
		<h1><?php _e( 'Payments', 'banda' ); ?></h1>
		<form method="post" class="wc-wizard-payment-gateway-form">
			<p><?php printf( __( 'Banda can accept both online and offline payments. %2$sAdditional payment methods%3$s can be installed later and managed from the %1$scheckout settings%3$s screen.', 'banda' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '" target="_blank">', '<a href="' . admin_url( 'admin.php?page=wc-addons&view=payment-gateways' ) . '" target="_blank">', '</a>' ); ?></p>

			<ul class="wc-wizard-payment-gateways">
				<?php foreach ( $gateways as $gateway_id => $gateway ) : ?>
					<li class="wc-wizard-gateway wc-wizard-gateway-<?php echo esc_attr( $gateway_id ); ?> <?php echo esc_attr( $gateway['class'] ); ?>">
						<div class="wc-wizard-gateway-enable">
							<input type="checkbox" name="wc-wizard-gateway-<?php echo esc_attr( $gateway_id ); ?>-enabled" class="input-checkbox" value="yes" />
							<label>
								<?php if ( $gateway['image'] ) : ?>
									<img src="<?php echo esc_attr( $gateway['image'] ); ?>" alt="<?php echo esc_attr( $gateway['name'] ); ?>" />
								<?php else : ?>
									<?php echo esc_html( $gateway['name'] ); ?>
								<?php endif; ?>
							</label>
						</div>
						<div class="wc-wizard-gateway-description">
							<?php echo wp_kses_post( wpautop( $gateway['description'] ) ); ?>
						</div>
						<?php if ( ! empty( $gateway['settings'] ) ) : ?>
							<table class="form-table wc-wizard-gateway-settings">
								<?php foreach ( $gateway['settings'] as $setting_id => $setting ) : ?>
									<tr>
										<th scope="row"><label for="<?php echo esc_attr( $gateway_id ); ?>_<?php echo esc_attr( $setting_id ); ?>"><?php echo esc_html( $setting['label'] ); ?>:</label></th>
										<td>
											<input
												type="<?php echo esc_attr( $setting['type'] ); ?>"
												id="<?php echo esc_attr( $gateway_id ); ?>_<?php echo esc_attr( $setting_id ); ?>"
												name="<?php echo esc_attr( $gateway_id ); ?>_<?php echo esc_attr( $setting_id ); ?>"
												class="input-text"
												value="<?php echo esc_attr( $setting['value'] ); ?>"
												placeholder="<?php echo esc_attr( $setting['placeholder'] ); ?>"
												/>
										</td>
									</tr>
								<?php endforeach; ?>
							</table>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="wc-setup-actions step">
				<input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Continue', 'banda' ); ?>" name="save_step" />
				<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php _e( 'Skip this step', 'banda' ); ?></a>
				<?php wp_nonce_field( 'wc-setup' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Payments Step save.
	 */
	public function wc_setup_payments_save() {
		check_admin_referer( 'wc-setup' );

		$gateways = $this->get_wizard_payment_gateways();

		foreach ( $gateways as $gateway_id => $gateway ) {
			// If repo-slug is defined, download and install plugin from .org.
			if ( ! empty( $gateway['repo-slug'] ) && ! empty( $_POST[ 'wc-wizard-gateway-' . $gateway_id . '-enabled' ] ) ) {
				wp_schedule_single_event( time() + 10, 'banda_plugin_background_installer', array( $gateway_id, $gateway ) );
			}

			$settings_key        = 'banda_' . $gateway_id . '_settings';
			$settings            = array_filter( (array) get_option( $settings_key, array() ) );
			$settings['enabled'] = ! empty( $_POST[ 'wc-wizard-gateway-' . $gateway_id . '-enabled' ] ) ? 'yes' : 'no';

			if ( ! empty( $gateway['settings'] ) ) {
				foreach ( $gateway['settings'] as $setting_id => $setting ) {
					$settings[ $setting_id ] = wc_clean( $_POST[ $gateway_id . '_' . $setting_id ] );
				}
			}

			update_option( $settings_key, $settings );
		}

		wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}

	/**
	 * Actions on the final step.
	 */
	private function wc_setup_ready_actions() {
		WC_Admin_Notices::remove_notice( 'install' );

		if ( isset( $_GET['wc_tracker_optin'] ) && isset( $_GET['wc_tracker_nonce'] ) && wp_verify_nonce( $_GET['wc_tracker_nonce'], 'wc_tracker_optin' ) ) {
			update_option( 'banda_allow_tracking', 'yes' );
			WC_Tracker::send_tracking_data( true );

		} elseif ( isset( $_GET['wc_tracker_optout'] ) && isset( $_GET['wc_tracker_nonce'] ) && wp_verify_nonce( $_GET['wc_tracker_nonce'], 'wc_tracker_optout' ) ) {
			update_option( 'banda_allow_tracking', 'no' );
		}
	}

	/**
	 * Final step.
	 */
	public function wc_setup_ready() {
		$this->wc_setup_ready_actions();
		shuffle( $this->tweets );
		?>
		<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://mtaandao.co.ke/" data-text="<?php echo esc_attr( $this->tweets[0] ); ?>" data-via="Banda" data-size="large">Tweet</a>
		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>

		<h1><?php _e( 'Your Store is Ready!', 'banda' ); ?></h1>

		<div class="wc-setup-next-steps">
			<div class="wc-setup-next-steps-first">
				<h2><?php _e( 'Next Steps', 'banda' ); ?></h2>
				<ul>
					<li class="setup-product"><a class="button button-primary button-large" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product&tutorial=true' ) ); ?>"><?php _e( 'Create your first product!', 'banda' ); ?></a></li>
				</ul>
			</div>
			<div class="wc-setup-next-steps-last">
				<h2><?php _e( 'Learn More', 'banda' ); ?></h2>
				<ul>
					<li class="learn-more"><a href="http://mtaandao.co.ke/plugins/banda"><?php _e( 'Learn more about selling with Banda.', 'banda' ); ?></a></li>
				</ul>
			</div>
		</div>
		<?php
	}
}

new WC_Admin_Setup_Wizard();
