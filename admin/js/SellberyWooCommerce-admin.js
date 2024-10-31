(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	
	 // On Page Load
	$(function() {

		if ($(".sellbery_fields_options").length > 0) {
			$("#sellbery_fields_data input").keypress(function(e){
				if($(this).attr('length')){
				 let length = $(this).attr('length');
					if($(this).hasClass('sell_number')){
						if ($(this).attr('x-type')) {
							if (!$.isNumeric(e.key) && $(this).val().length > 3) {
								e.preventDefault();
							}
						}else{
							if (!$.isNumeric(e.key)) {
								e.preventDefault();
							}
						}
					}
					if($(this).val().toString().length >= length ){
						e.preventDefault();
					}
					if($(this).val().toString().length >= (length-1) ){
						wp_custom_errors(true,$(this))
					}
				}
			});
			$("#sellbery_fields_data input").keyup(function(e){
				if($(this).attr('x-type')){
					let str = $(this).val();
					let digits = $(this).val().substring(4, 12);
					let letters = $(this).val().substring(0, 3);
					let letter1 = $(this).val().substring(0, 1);
					let letter2 = $(this).val().substring(1, 2);
					let letter3 = $(this).val().substring(2, 3);
					let reg = /^[a-zA-Z]+$/;
					let check = true;
					if(!reg.test(letters)){
						check = false;
					}else{
						if (letter1.toUpperCase() != "P") {
							check = false;
						}
						if (letter2.toUpperCase() != "" && letter2.toUpperCase() != "Z") {
							check = false;
						}
						if (letter3.toUpperCase() != "" && letter3.toUpperCase() != "N") {
							check = false;
						}
					}
					if(str[3] && str[3] != '-'){
						check = false;
					}
					if(digits != '' && !$.isNumeric(digits)){
						check = false;
					}
					if(str == '' || str[str.length-1] == ''){
						check = true;
					}
					wp_custom_errors(check,$(this));
				}
			});
			$("#publish").click(function(){
				$(".sellbery_validation").click();
			});
			$(".sellbery_validation").click(function(){
				let bool = true;
				$(".sellbery_inp").each(function(){
					if ($(this).attr("length") != $(this).val().toString().length && $(this).val() != '' && $(this).val() != '0') {
						bool = wp_custom_errors(false,$(this));
					}
					if ($(this).attr("x-type")) {
						let digits = $(this).val().substring(4, 12);
						if (digits.length < 7 && $(this).val() != '') {
							bool = wp_custom_errors(false,$(this));
						}
					}
				});
				if (bool == false) {
					$("#post").submit((e) => e.preventDefault());
				}else{
					$("#post").unbind("submit");
				}
			});

			function wp_custom_errors(error,_this){
				let html_error = $("<div class='alert_error clear'><strong>Error!</strong> Incorrect Value</div>");
				if(error == false){
					if(_this.parent().find('.alert_error').length == 0){
						_this.parent().append(html_error);
						_this.css("border-color","red");
					}
					$("#post").submit((e) => e.preventDefault());
					return false;
				}else{
					$("#post").unbind("submit");
					_this.parent().find('.alert_error').remove();
					_this.css("border-color","#7e8993");
					return true;
				}
			}
		}


		if($(".sellbery_attributes_table").length > 0){
			$(".sellbery_attributes_table input").change(function(){
				if($(this)[0].checked){
					$(this).parent().parent().find('select').attr("disabled","disabled");
				}else{
					$(this).parent().parent().find('select').removeAttr("disabled");
				}
			});
		}

	});
})( jQuery );
