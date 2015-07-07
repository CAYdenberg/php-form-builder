<?php

namespace PhpFormBuilder;

class PhpFormBuilder extends BaseClass {

	// Stores all form inputs
	private $inputs = array();

	// Does this form have a submit value?
	private $has_submit = false;

	/**
	 * Constructor function to set form action and attributes
	 *
	 * @param string $action
	 * @param bool   $args
	 *
	 */
	function __construct( $args = false ) {

		// Default form attributes
		$this->defaults = array(
			'action'       => '.',
			'method'       => 'post',
			'enctype'      => 'application/x-www-form-urlencoded',
			'class'        => array(),
			'id'           => '',
			'markup'       => 'html',
			'novalidate'   => false,
			'add_nonce'    => false,
			'add_honeypot' => true,
			'form_element' => true,
			'add_submit'   => true
		);
		$this->create_settings( $args );

	}

	/**
	 * Add an input field to the form for outputting later
	 *
	 * @param string $label
	 * @param string $args
	 * @param string $slug
	 *
	 * @return object/Input
	 */
	function add_input( $label, $args = array(), $slug = '' ) {
		//if the slug is left blank, the Input object will build one
		$input = new Input( $label, $args, $slug );
		return $this->inputs[$input->get_slug()] = $input;
	}

	/**
	 * Add multiple inputs to the input queue
	 * Each input should be formatted as an array of [label, args, slug]
	 *
	 * @param $arr
	 *
	 * @return bool
	 */
	public function add_inputs( $arr ) {

		foreach ( $arr as $field ) {
			$this->add_input(
				$field[0],
				isset( $field[1] ) ? $field[1] : [],
				isset( $field[2] ) ? $field[2] : ''
			);
		}

		return true;
	}

	/**
	* Delete all inputs from the form.
	* Useful to suppress output if validation and all subsequent tasks are
	* succesful.
	*
	*/
	public function delete_inputs() {
		$this->inputs = array();
	}

	/**
	* Set or overwrite for each Input in the form.
	* NOTE: must be called after the inputs are set
	*
	* @param setting_key string
	* @param setting_value string
	* @param exclude array - exclude inputs by slug
	*
	*/
	public function set_for_each_input( $setting_key, $setting_value, $exclude = array() ) {
		foreach ( $this->inputs as $input_key => $input ) {
			if ( in_array($input_key, $exclude) ) continue;
			$input->set( $setting_key, $setting_value );
		}
	}

	/**
	 * Add a setting (such as a class) for each Input in the form.
	 * NOTE: must be called after the inputs are set
	 *
	 * @param setting_key string
	 * @param setting_value string
	 * @param exclude array - exclude inputs by slug
	 *
	 */
	public function add_setting_for_each_input( $setting_key, $setting_value, $exclude = array() ) {
		foreach ( $this->inputs as $input_key => $input ) {
			if ( in_array($input_key, $exclude) ) continue;
			$input->add_setting( $setting_key, $setting_value );
		}
	}

	/**
	 * Build the HTML for the form based on the input queue
	 *
	 * @param bool $echo Should the HTML be echoed or returned?
	 *
	 * @return string
	 */
	function build_form( $echo = true ) {

		$output = '';

		if ( $this->settings['form_element'] ) {
			$output .= '<form method="' . $this->settings['method'] . '"';

			if ( ! empty( $this->settings['enctype'] ) ) {
				$output .= ' enctype="' . $this->settings['enctype'] . '"';
			}

			if ( ! empty( $this->settings['action'] ) ) {
				$output .= ' action="' . $this->settings['action'] . '"';
			}

			if ( ! empty( $this->settings['id'] ) ) {
				$output .= ' id="' . $this->settings['id'] . '"';
			}

			if ( count( $this->settings['class'] ) > 0 ) {
				$output .= $this->output_classes( $this->settings['class'] );
			}

			if ( $this->settings['novalidate'] ) {
				$output .= ' novalidate';
			}

			$output .= '>';
		}

		// Add optional honeypot anti-spam field
		if ( $this->settings['add_honeypot'] ) {
			$this->add_input( 'Leave blank to submit', array(
				'name'             => 'honeypot',
				'slug'             => 'honeypot',
				'id'               => 'form_honeypot',
				'wrap_class'       => array('hidden'),
				'request_populate' => false
			) );
		}

		// Add optional WordPress nonce field
		if ( $this->settings['add_nonce'] && function_exists( 'wp_create_nonce' ) ) {
			if ( !function_exists('wp_create_nonce') ) {
				throw new Exception('Attemping to create nonce outside of Wordpress context');
			}
			$this->add_input( 'WordPress nonce', array(
				'value'            => wp_create_nonce( $this->settings['add_nonce'] ),
				'add_label'        => false,
				'wrap_class' 			 => array('hidden'),
				'request_populate' => false
			) );
		}

		// Iterate through the input queue and add input HTML
		foreach ( $this->inputs as $input ) {
			$output .= $input->build_input();
		}

		// Auto-add submit button
		if ( ! $this->has_submit && $this->settings['add_submit'] ) {
			$output .= '<div class="form_field_wrap"><input type="submit" value="Submit" name="submit"></div>';
		}

		// Close the form tag if one was added
		if ( $this->settings['form_element'] ) {
			$output .= '</form>';
		}

		// Output or return?
		if ( $echo ) {
			echo $output;
		} else {
			return $output;
		}
	}

	/**
	* Runs through each Inputs validation functions, if any
	*
	* @return bool - entire form is valid or not valid
	*/
	public function validate() {
		$valid = TRUE;
		foreach ( $this->inputs as $input ) {
			if ( ! $input->validate() ) $valid = FALSE;
		}
		return  $valid;
	}

	/**
	* Get a safe (validated) value for this field. Safe values are set for the validate
	* function, so if the form has not been validated first, this function will return
	* NULL.
	*
	* @param string - key (slug) for the field we need a value for.
	* @return bool - entire form is valid or not valid
	*/
	public function get_safe_value( $key ) {
		if ( isset($this->inputs[$key] ) ) {
			return $this->inputs[$key]->get_value();
		} else {
			throw new Exception('Field with key '. $key . ' does not exist');
			return;
		}
	}

	/**
	* Get safe (validated) value for all fields in this form. Will get an array
	* of NULLs if the form has not already been validated.
	*
	* @return array of strings
	*/
	public function get_safe_values() {
		$values = array();
		foreach ( $this->inputs as $slug => $input ) {
			$values[$slug] = $this->get_safe_value($slug);
		}
		return $values;
	}

}
