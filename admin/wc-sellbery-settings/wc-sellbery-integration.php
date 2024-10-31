<?php
class WC_Integration_Sellbery_Integration extends WC_Integration {
	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		global $woocommerce;
		$this->id                 = 'sellbery_attributes';
		// Load the settings.
		$this->init_settings();
		// Define user set variables.
		$this->api_key          = $this->get_option( 'api_key' );
		$this->debug            = $this->get_option( 'debug' );
		// Actions.
		add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
	}
	public function admin_options(){

		//$data comes from here
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'wc-sellbery-settings/sellbery-fields.php';

		$args = array(
       		'post_type'      => 'product',
        	'posts_per_page' => 1,
    	);
    	$product = new WP_Query( $args );


    	if (isset($product->post->ID)) {
            $res = get_post_meta($product->post->ID);
        }

        if (isset($product->post->post_title)) {
            $res['_product_title'] = [$product->post->post_title];
        }
        if (isset($product->post->post_excerpt)) {
            $res['_product_description'] = [$product->post->post_excerpt];
        }
    	$product_attributes = "";
        if (isset($res)) {
            foreach ($res as $k => $value) {
                if (strpos($k, 'sellbery') !== false) {
                    unset($res[$k]);
                }
            }
            unset($res['_edit_lock']);
            unset($res['_edit_last']);
            unset($res['_product_version']);

            if (!empty($res['_product_attributes'][0])) {
                $product_attributes = unserialize($res['_product_attributes'][0]);
                unset($res['_product_attributes']);
                foreach ($product_attributes as $key) {
                    $res[$key['name']] = $key['value'];
                }
            }
        }

		if (!empty($_POST)) {
			foreach ($data as $key => $value) {
				$value = sanitize_text_field($value);
				if (isset($_POST[$value.'_dropdown'])) {
					update_option($value, sanitize_text_field($_POST[$value.'_dropdown']) );
				}else if(isset($_POST[$value.'_checkbox'])){
					update_option($value, sanitize_text_field($_POST[$value.'_checkbox']) );
				}
			}
		}
		?>
			<style>.sellbery_attributes_table select{width: 96%}</style>
			<div class='sellbery_attributes_table'>
                <div class="connect-woo-btn-block" style="margin-bottom: 15px;">
                    <label style="cursor: default"><strong>Connect WooCommerce</strong></label> </br>
                    <a href="<?php echo esc_url(self::build_authorization_url()); ?>" name="test_button"
                       class="button-primary woocommerce-save-button"> <?php esc_html_e('Connect', 'woocommerce'); ?></a>
                </div>
				<table>
					<tr>
						<th>Sellbery fields</th>
						<th>WooCommerce Fields</th>
						<th>Disable</th>
					</tr>
					<?php
					foreach($data as $key => $value){ ?>
						<tr>
							<td><span><?php echo $key ?></span></td>
							<td><select <?php if (!get_option($value) || get_option($value) == "on") {echo 'disabled';} ?>
							  id="<?php echo $value.'_dropdown'; ?>" name="<?php echo $value.'_dropdown'; ?>">
							<?php foreach($res as $key1 => $value1){ 
									$key_r = str_replace(['_','-'], " ", $key1);
									if ($value != $key1) {
								?>
								<option <?php if (get_option($value) != "on" && get_option($value) == $key1 ) {echo 'selected';} ?>  
								value="<?php echo $key1 ?>"><?php echo ucwords(trim($key_r)) ?></option>
								<?php } }
							?>	
							</select></td>
							<td><label for="<?php echo $value.'_checkbox'; ?>"></label> <input name='<?php echo $value.'_checkbox'; ?>' id='<?php echo $value.'checkbox'; ?>' type="checkbox" <?php if (!get_option($value) || get_option($value) == "on") {
								echo 'checked';
							} ?>> Add the <?php echo $key; ?> manually on the product page</td>
						</tr>
					<?php } ?>
				</table>
			</div>
	<?php 
	}

    public static function build_authorization_url() {
        global $current_user;

        if ($current_user->first_name != '') {
            $fname =  $current_user->first_name;
        }else if($current_user->user_login != ''){
            $fname =  $current_user->user_login;
        }else{
            $fname = "no_fullName";
        }


        $store_url = get_site_url();
        $endpoint = '/wc-auth/v1/authorize';
        $params = [
            'app_name' => 'Sellbery',
            'scope' => 'read_write',
            'user_id' => wp_generate_uuid4(),
            'return_url' => 'https://app.sellbery.com/signup?email='.$current_user->user_email."&full_name=".$fname.'&store_url='.$store_url,
            'callback_url' => 'https://app.sellbery.com/rts/http/auth'
        ];
        $query_string = http_build_query( $params );

        return $store_url . $endpoint . '?' . $query_string;

    }
}