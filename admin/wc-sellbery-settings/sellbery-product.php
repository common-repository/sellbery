<?php
// WC Product Data
add_filter('woocommerce_product_data_tabs', function($tabs) {
	$tabs['sellbery_fields'] = [
		'label' => __('Sellbery Fields', 'wc_sellbery_fields'),
		'target' => 'sellbery_fields_data',
		'class' => ['show_if_simple','show_if_variable'],
		'priority' => 25
	];
	return $tabs;
});
add_action('woocommerce_product_data_panels', function() {
	global $post;
	$product = wc_get_product($post->ID);
	$bulets = ['sellbery_amazon_bullet_point1',"sellbery_amazon_bullet_point2","sellbery_amazon_bullet_point3","sellbery_amazon_bullet_point4","sellbery_amazon_bullet_point5"];
	
	require_once plugin_dir_path( dirname( __FILE__ ) ) . 'wc-sellbery-settings/sellbery-fields.php';

	?><div id="sellbery_fields_data" class="panel woocommerce_options_panel hidden"><?php
	foreach ($data as $key => $value) {
		$inherit = '';
		if (get_option($value) && get_option($value) != 'on') {
			$meta = get_post_meta($product->get_id(),get_option($value));
			if (empty($meta)) {
				$product->update_meta_data( $value, null);
				update_option($value."_old", "changed" );
			}else{
				$product->update_meta_data( $value, $meta[0]);
				update_option($value."_old", "changed" );
			}
			$inherit = "sellbery_hidden";
			$product->save();
		}else{
			if (get_option($value."_old") && get_option($value."_old") == 'changed') {
				$product->update_meta_data( $value, '');
				update_option($value."_old", "" );
			}
			$product->save();
		}
		if ($value == 'sellbery_universal_product_code') {
			woocommerce_wp_text_input([
				'id' => $value,
				'label' => __($key, 'wc_sellbery_labels'),
				'type' => 'number',
				'class' => "sellbery_inp short sell_number",
				'custom_attributes' => [
					'length'=> 12
				],
				'wrapper_class' => 'show_if_simple '. $inherit,
			]);
			
		}else if($value == 'sellbery_european_article_number' || $value == 'sellbery_international_standard_book' || 
 			$value == 'sellbery_international_standard_music'){
			woocommerce_wp_text_input([
				'id' => $value,
				'label' => __($key, 'wc_sellbery_labels'),
				'type' => 'number',
				'class' => "sellbery_inp short sell_number",
				'custom_attributes' => [
					'length'=> 13
				],
				'wrapper_class' => 'show_if_simple '. $inherit
			]);
		}else if($value == 'sellbery_japanese_article'){
			woocommerce_wp_text_input([
				'id' => $value,
				'label' => __($key, 'wc_sellbery_labels'),
				'type' => 'number',
				'class' => "sellbery_inp short sell_number",
				'custom_attributes' => [
					'length'=> 9
				],
				'wrapper_class' => 'show_if_simple '. $inherit,
			]);
		}else if($value == 'sellbery_pharmazentralnummer'){
			woocommerce_wp_text_input([
				'id' => $value,
				'label' => __($key, 'wc_sellbery_labels'),
				'wrapper_class' => 'show_if_simple '. $inherit,
				'class' => "sellbery_inp short sell_number",
				'custom_attributes' => [
					'length'=> 12,
					'length_min'=> 11,
					'x-type' => 'pzn'
				]
			]);
		}else if($value == 'sellbery_manufacturer' || $value == 'sellbery_brand'){
			woocommerce_wp_text_input([
				'id' => $value,
				'label' => __($key, 'wc_manufacturer_part_number'),
				'wrapper_class' => 'show_if_simple show_if_variable '. $inherit,
			]);
		}else if(in_array($value,$bulets)){
			woocommerce_wp_textarea_input([
				'id' => $value,
				'style'=> "height:60px",
				'label' => __($key, 'wc_manufacturer_part_number'),
				'wrapper_class' => 'show_if_simple show_if_variable '. $inherit
			]);
		}else{
			woocommerce_wp_text_input([
				'id' => $value,
				'label' => __($key, 'wc_sellbery_labels'),
				'wrapper_class' => 'show_if_simple ' . $inherit
			]);
		}
	}
	?><div class="clear btn_cont_val"><?php submit_button("Save","primary sellbery_validation","",false); ?></div>
	<style>
	.btn_cont_val{margin-bottom: 15px}
	.sellbery_hidden{display: none !important}
	.alert_error{color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;padding:5px 10px}
	</style>
	</div><?php
});

add_action('woocommerce_process_product_meta', function($post_id) {
	//$data comes from here
	require_once plugin_dir_path( dirname( __FILE__ ) ) . 'wc-sellbery-settings/sellbery-fields.php';
	$product = wc_get_product($post_id);
	foreach ($data as $key => $value) {
		$product->update_meta_data($value, sanitize_text_field($_POST[$value]));
	}
	$product->save();
});
