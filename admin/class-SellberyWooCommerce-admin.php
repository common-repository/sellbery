<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Sellbery_WooCommerce
 * @subpackage Sellbery_WooCommerce/admin
 */

 // Lots of help, borrowed code from: https://github.com/rayman813/smashing-custom-fields/blob/master/smashing-fields-approach-1/smashing-fields.php


class Sellbery_WooCommerce_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		//Add Sellbery Fields to products
		add_action('admin_init', array($this, 'setup_wc_product_fields'));
		//Add Sellbery Fields to Integration
		add_filter( 'woocommerce_get_sections_integration' , array($this,'sellbery_attributes_tab') );
		add_action('admin_init', array($this, 'if_wc_is_active'));
		add_filter( 'woocommerce_integrations',array($this,'add_integration') );
		add_filter( 'plugin_action_links', array($this, 'wpse_25030_settings_plugin_link'), 10, 2 );
        add_filter('plugin_row_meta',array($this,'fun_hide_view_details'),10,4);


            // add action deleted product
        add_action( 'trashed_post', array($this, 'action_trashed_post'));
            // restore product
        add_action( 'untrash_post', array($this, 'action_untrash_post'));
            // get DB result deleted products
        add_action('rest_api_init', array($this, 'api_get_deleted_product'));
    }


    function api_get_deleted_product () {

        register_rest_route('wc/v3' , '/products/deleted', array(
            'methods' => 'GET',
            'callback' => array($this,'my_awesome_func'),
            'permission_callback' => '__return_true',
            'id' => ''
        ));
    }


    protected function set_error( $error ) {
        // Reset user.
        $this->user = null;

        $this->error = $error;
    }

    private function get_user_data_by_consumer_key( $consumer_key ) {
        global $wpdb;

        $consumer_key = wc_api_hash( $consumer_key  );
        $user         = $wpdb->get_row(
            $wpdb->prepare(
                "
		SELECT key_id, user_id, permissions, consumer_key, consumer_secret, nonces
		FROM {$wpdb->prefix}woocommerce_api_keys
		WHERE consumer_key = %s
	",
                $consumer_key
            )
        );

        return $user;
    }

    private function perform_basic_authentication() {
        $this->auth_method = 'basic_auth';
        $consumer_key      = '';
        $consumer_secret   = '';
        // If the $_GET parameters are present, use those first.
        if ( ! empty( $_GET['consumer_key'] ) && ! empty( $_GET['consumer_secret'] ) ) { // WPCS: CSRF ok.
            $consumer_key    = sanitize_text_field($_GET['consumer_key']); // WPCS: CSRF ok, sanitization ok.
            $consumer_secret = sanitize_text_field($_GET['consumer_secret']); // WPCS: CSRF ok, sanitization ok.

        }

        // If the above is not present, we will do full basic auth.
        if ( ! $consumer_key && ! empty( $_SERVER['PHP_AUTH_USER'] ) && ! empty( $_SERVER['PHP_AUTH_PW'] ) ) {
            $consumer_key    = sanitize_text_field($_SERVER['PHP_AUTH_USER']); // WPCS: CSRF ok, sanitization ok.
            $consumer_secret = sanitize_text_field($_SERVER['PHP_AUTH_PW']); // WPCS: CSRF ok, sanitization ok.
        }

        // Stop if don't have any key.
        if ( ! $consumer_key || ! $consumer_secret ) {
            return false;
        }

        // Get user data.
        $this->user = $this->get_user_data_by_consumer_key( $consumer_key );
        if ( empty( $this->user ) ) {
            return false;
        }

        // Validate user secret.
        if ( ! hash_equals( $this->user->consumer_secret, $consumer_secret ) ) { // @codingStandardsIgnoreLine
            $this->set_error( new WP_Error( 'woocommerce_rest_authentication_error', __( 'Consumer secret is invalid.', 'woocommerce' ), array( 'status' => 401 ) ) );

            return false;
        }

        return $this->user->user_id;
    }



        // get api call result (obj)
    function my_awesome_func($request){

        $result_auth = $this->perform_basic_authentication();

        if ($result_auth) {
            global $wpdb;

            $items_per_page = 10;
            $page = $_GET['page'] ?? 1;
            $page = intval($page);
            $offset = ( $page * $items_per_page ) - $items_per_page ?? null;
            $all_table_data = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sellbery_deleted");
            $del_products_total = count($all_table_data);
            $current_url = $page ?? 1;
            $api_result = $wpdb->get_results( 
            	$wpdb->prepare("SELECT * FROM {$wpdb->prefix}sellbery_deleted LIMIT %d OFFSET %d",$items_per_page,$offset), OBJECT);
            if (!empty($prev_page_url)) {
                return ['product_data' => $api_result,
                    'Total' => $del_products_total,
                    'PerPage' => $items_per_page,
                    'Page' => $current_url,
                ];
            }else {
                return ['product_data' => $api_result,
                    'Total' => $del_products_total,
                    'PerPage' => $items_per_page,
                    'Page' => $current_url,
                ];
            }
        }else {
            echo 'You dont have a permission to see this page';
        }

    }

    // get deleted product
    function action_trashed_post($post_id) {
        $product = wc_get_product( $post_id );
        $product_sku = $product->get_sku();
        $delete_date = date('Y-m-d');

        global $wpdb;

        $tblname = "{$wpdb->prefix}sellbery_deleted";

            // Check to see if the table exists already, if not, then create it
        if($wpdb->get_var( "show tables like $tblname" ) != $tblname)
        {
            $sql = 'CREATE TABLE $tblname(
        id INTEGER NOT NULL AUTO_INCREMENT,
        product_id INTEGER(255),
        sku VARCHAR(255),
        delete_date VARCHAR(255),
        PRIMARY KEY (id))';

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

        }
        if($wpdb->get_var( "show tables like $tblname" )) {
            $wpdb->insert($wp_track_table, array(
                "product_id" => $post_id,
                "sku" => $product_sku,
                "delete_date" => $delete_date,
            ));
        }
    }

    function action_untrash_post( $post_id ) {

        global $wpdb;
        $tblname = "{$wpdb->prefix}sellbery_deleted";

        if($wpdb->get_var( "show tables like $tblname" )) {
            $wpdb->delete(
                $tblname, // table to delete from
                array(
                    'product_id' => $post_id // value in column to target for deletion
                )
            );
        }
    }



    /**
     * Sellbery Plugin URI Label
     */
    public function fun_hide_view_details($plugin_meta, $plugin_file, $plugin_data, $status)
    {
        if($plugin_file == 'SellberyWooCommerce-master/SellberyWooCommerce.php'){
            $name = $plugin_data["PluginURI"];
            $plugin_meta[2] =  '<a href="https://monosnap.com/file/1Zd1neel5uKlv58Qmv3xR3woN5MpJC/?tab=plugin-information&amp;plugin=akismet&amp;TB_iframe=true&amp;width=772&amp;height=889" class="thickbox open-plugin-details-modal" aria-label="More information about Akismet Anti-Spam" data-title="Akismet Anti-Spam">View details</a>';
        }
        return $plugin_meta;
    }


	/**
	 * Sellbery action links
	 */
	public function wpse_25030_settings_plugin_link( $links, $file ) 
	{

	    if ( $file == 'SellberyWooCommerce-master/SellberyWooCommerce.php' )
	    {
	        /*
	         * Insert at the end View details
	         */
	         $links[] = '<a href="/wp-admin/admin.php?page=wc-settings&tab=integration&section=sellbery_attributes">'.__('Settings','sellbery').'</a>';
	         $links[] = '<a href="https://help.sellbery.com?utm_source=partner&utm_medium=woocommerce-plugin">'.__('Support','sellbery').'</a>';
	    }
	    return $links;
	}

	/**
	 * Add Sellbery Fields to Integration
	 */
	public function sellbery_attributes_tab( $settings_tab ){
		$settings_tab['sellbery_attributes'] = __( 'Sellbery Attributes' );
		$settings_tab = array_merge(["sellbery_attributes"=>$settings_tab["sellbery_attributes"]], $settings_tab);
	    return $settings_tab;
	}
	public function if_wc_is_active(){
		if(is_plugin_active('woocommerce/woocommerce.php')){
			if(class_exists('WC_Integration')){
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/wc-sellbery-settings/wc-sellbery-integration.php';
				new WC_Integration_Sellbery_Integration();
			}
		}
	}
	public function add_integration($integrations){
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/wc-sellbery-settings/wc-sellbery-integration.php';
		$integrations[] = 'WC_Integration_Sellbery_Integration';
		return $integrations;
	}

	/**
	 * Add Sellbery Fields to products
	 */
	public function setup_wc_product_fields(){
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/wc-sellbery-settings/sellbery-product.php';
	}

	/**
	 * Add the menu items to the admin menu
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {

		// Main Menu Item
	  	add_menu_page(
			'Sellbery for WooCommerce',
			'Sellbery for WooCommerce',
			'manage_options',
			'custom-plugin',
			array($this, 'display_custom_plugin_admin_page_two'),
			'dashicons-store',
			1);
		// Sub Menu Item Two
		add_submenu_page(
			'custom-plugin',
			'Connect with WC',
			'Connect with WC',
			'manage_options',
			'custom-plugin',
			array($this, 'display_custom_plugin_admin_page_two')
		);
		add_submenu_page(
			'custom-plugin',
			'Our table',
			'Our table',
			'manage_options',
			'custom-plugin/settings-page-table',
			array($this, 'display_custom_plugin_admin_page_table')
		);
	}

	/**
	 * Setup sections in the settings
	 *
	 * @since    1.0.0
	 */
	public function setup_sections() {
		add_settings_section( 'section_one', 'Section One', array($this, 'section_callback'), 'SellberyWooCommerce-options' );
		add_settings_section( 'section_two', 'Section Two', array($this, 'section_callback'), 'SellberyWooCommerce-options' );
	}

	/**
	 * Callback for each section
	 *
	 * @since    1.0.0
	 */
	public function section_callback( $arguments ) {
		switch( $arguments['id'] ){
			case 'section_one':
				echo '<p>This is settings for section one, you can put some more information here if needed.</p>';
				break;
			case 'section_two':
				echo '<p>Section two! More information on this section can go here.</p>';
				break;
		}
	}

	/**
	 * Admin Notice
	 * 
	 * This displays the notice in the admin page for the user
	 *
	 * @since    1.0.0
	 */
	public function admin_notice($message) { ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo($message); ?></p>
		</div><?php
	}

	/**
	 * This handles setting up the rewrite rules for Past Sales
	 *
	 * @since    1.0.0
	 */
	public function setup_rewrites() {
		//
		$url_slug = 'custom-plugin';
		// Lets setup our rewrite rules
		add_rewrite_rule( $url_slug . '/?$', 'index.php?custom_plugin=index', 'top' );
		add_rewrite_rule( $url_slug . '/page/([0-9]{1,})/?$', 'index.php?custom_plugin=items&custom_plugin_paged=$matches[1]', 'top' );
		add_rewrite_rule( $url_slug . '/([a-zA-Z0-9\-]{1,})/?$', 'index.php?custom_plugin=detail&custom_plugin_vehicle=$matches[1]', 'top' );


		// Lets flush rewrite rules on activation
		flush_rewrite_rules();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Sellbery_WooCommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Sellbery_WooCommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/SellberyWooCommerce-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Sellbery_WooCommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Sellbery_WooCommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/SellberyWooCommerce-admin.js', array( 'jquery' ), $this->version, false );

	}

}
