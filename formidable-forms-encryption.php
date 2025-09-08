<?php
/*
Plugin Name: Formidable Forms Encryption
Plugin URI: https://abuyasmeen.com
Description: Encryption of Formidable Form Fields, Requires ACF
Version: 1.1.2
Author: Jeremy Varnham
*/
/* Adapted from
 * @source : https://victorfont.com/encrypt-decrypt-formidable-form-fields/
 */

function ff_encrypt( $data ) {
    $key = FORMIDABLE_SALT;
    $method = FORMIDABLE_METHOD;
    $options = FORMIDABLE_OPTIONS;
    $iv = FORMIDABLE_IV;

    return openssl_encrypt( $data, $method, $key, $options, $iv );
}

function ff_decrypt( $encrypted ) {
    $key = FORMIDABLE_SALT;
    $method = FORMIDABLE_METHOD;
    $options = FORMIDABLE_OPTIONS;
    $iv = FORMIDABLE_IV;

    return openssl_decrypt( $encrypted, $method, $key, $options, $iv );
}

/*
 * Encrypt chosen fields
 * Requires Advanced Custom Fields (ACF) plugin 
 */
function ff_encrypt_field_acf( $values ) { 
	$form_ids_array = ff_acf_get_options(); // get values from ACF Options page

	foreach ( $form_ids_array as $form_id => $field_ids ) {
		if ( $values['form_id'] == $form_id ) { 
			foreach( $field_ids as $field_id ) { 
			    $current_value = $values['item_meta'][$field_id];
			    $encrypted_value = ff_encrypt( $current_value ); 
			    $values['item_meta'][$field_id] = $encrypted_value;
			}
		}
	}
	return $values;
}
add_filter('frm_pre_create_entry', 'ff_encrypt_field_acf');
add_filter('frm_pre_update_entry', 'ff_encrypt_field_acf');

/*
 * Decrypt chosen fields in backend Edit mode
 * Requires Advanced Custom Fields (ACF) plugin 
 */
function ff_decrypt_field_acf( $values, $field, $args ){	
	$form_ids_array = ff_acf_get_options(); // get values from ACF Options page
	$field_ids = ff_acf_array_flatten( $form_ids_array );	
	
	if ( in_array( $field->id, $field_ids, false ) ) {
        $encrypted = $values['value'];
        $values['value'] = ff_decrypt( $encrypted );
	}
    return $values;
}
add_filter('frm_setup_edit_fields_vars', 'ff_decrypt_field_acf', 20, 3);

/*
 * Decrypt fields in frontend 
 * usage [x decrypt=1]
 */
function ff_decrypt_field_for_view( $string, $tag, $atts, $field ) {
    if( isset( $atts['decrypt'] ) ) {
        $string = ff_decrypt( $string );
    }
    return $string;
}
add_filter('frmpro_fields_replace_shortcodes', 'ff_decrypt_field_for_view', 10, 4);


/* 
 * GET ACF Values from Options Page
 */
function ff_acf_get_options() {
	if( have_rows('forms', 'option') ) {
		while( have_rows('forms', 'option') ) {
			the_row(); 
	    	$form_id = get_sub_field('fform_id');
	    	$field_ids_string = get_sub_field('ffield_ids');
	    	$field_ids_string = str_replace(", ", ",", $field_ids_string); // remove extra spaces between commas and values
	    	$field_ids[$form_id] = array_map('intval', explode( ",", $field_ids_string ) );
	    }
	} 
	return $field_ids;
}

/* 
 * Merges all field ids into one array
 * Helper function for decrypt
 */
function ff_acf_array_flatten( $array ) {
	if ( !is_array( $array ) ) {
		return array();
	}
	$result = array();
	foreach ( $array as $key => $value ) {
		if ( is_array( $value ) ) {
			$result = array_merge( $result, ff_acf_array_flatten( $value ) );
		}
		else {
			$result[$key] = $value;
		}
	} 
	return $result;
}

/* ***************** OPTIONS PAGE USING ACF ***************** */

/*
 * Creates Encryption option page
 * under Formidable menu
 */
function formidable_encryption_acf_init() {
	if( function_exists('acf_add_options_sub_page') ) {
		
		$child = acf_add_options_sub_page(array(
			'page_title' 	=> 'Formidable Encryption',
			'menu_title'	=> 'Encryption',
			'menu_slug' 	=> 'formidable-encryption',
			'parent_slug'	=> 'formidable',
			'capability'	=> 'manage_options',
			'redirect'		=> false
		));
	}
}
add_action('acf/init', 'formidable_encryption_acf_init');

/*
 * Defines ACF fields
 */
if( function_exists('acf_add_local_field_group') ) {
	acf_add_local_field_group(array(
		'key' => 'group_formidable_encryption',
		'title' => 'Formidable Encryption',
		'fields' => array(
			array(
				'key' => 'field_forms_repeater',
				'label' => 'Forms',
				'name' => 'forms',
				'type' => 'repeater',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'collapsed' => '',
				'min' => 0,
				'max' => 0,
				'layout' => 'table',
				'button_label' => 'Add',
				'sub_fields' => array(
					array(
						'key' => 'field_form_id',
						'label' => 'Form ID',
						'name' => 'fform_id',
						'type' => 'number',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'min' => '',
						'max' => '',
						'step' => '',
					),
					array(
						'key' => 'field_field_ids',
						'label' => 'Form Field IDs',
						'name' => 'ffield_ids',
						'type' => 'text',
						'instructions' => 'Comma separated list of fields to be encrypted on the selected form',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_encrypt_yesno',
						'label' => 'Encrypt',
						'name' => 'encrypt',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '10',
							'class' => '',
							'id' => '',
						),
						'message' => '',
						'default_value' => 1,
						'ui' => 1,
						'ui_on_text' => '',
						'ui_off_text' => '',
					),
					array(
						'key' => 'field_decrypt_yesno',
						'label' => 'Decrypt',
						'name' => 'decrypt',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '10',
							'class' => '',
							'id' => '',
						),
						'message' => '',
						'default_value' => 1,
						'ui' => 1,
						'ui_on_text' => '',
						'ui_off_text' => '',
					),
				),
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'options_page',
					'operator' => '==',
					'value' => 'formidable-encryption',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => true,
		'description' => '',
		'show_in_rest' => 0,
	));
	
}
