<?php

class BaseClass {
	protected $defaults = array();
	//Usually set in the Constructor and merged with $args to create settings

	protected $settings;
	//settings - created by merging defaults with arrays


	/**
	 * Merge $this->settings with $this->defaults;
	 *
	 * @param bool/array   $args
	 */
	protected function create_settings( $args ) {
		if ( $args ) {
			$this->settings = array_merge( $this->defaults, $args );
		} else {
			$this->settings = $this->defaults;
		}
	}

	/**
	* Add or update a setting.
	*
	* @param string   $key
	* @param string 	$value
	*
	* @return bool (true)
	*/
	public function set( $key, $value ) {
		$this->settings[$key] = $value;
		return true;
	}

	/**
	* Add to an array within a setting.
	* Do so only if that setting already exists and represents an array.
	* Return false on failure.
	*
	* @param string   $key
	* @param string 	$value
	*
	* @return bool
	*/
	protected function add_setting( $key, $value ) {
		if ( isset($this->settings[$key]) && is_array($this->settings[$key]) ) {
			$this->settings[$key][] = $value;
			return true;
		} else {
			throw new Exception('Attempting to add value to a non-array');
			return false;
		}
	}

	// Validates id and class attributes
	// TODO: actually validate these things
	private function check_valid_attr( $string ) {

		$result = true;

		// Check $name for correct characters
		// "^[a-zA-Z0-9_-]*$"

		return $result;

	}

	/**
	* Create a slug from a label name
	* e.g. if $string = 'Make a payment', slug will be 'make-a-payment'
	*
	* @param  string   $string
	* @return string
	*/
	protected function make_slug( $string ) {

		$result = '';

		$result = str_replace( '"', '', $string );
		$result = str_replace( "'", '', $result );
		$result = str_replace( '_', '-', $result );
		$result = preg_replace( '~[\W\s]~', '-', $result );

		$result = strtolower( $result );

		return $result;

	}

	/**
	* Create HTML class string from an array of classes
	* e.g. array('form-control', 'credit-card') becomes 'class="form-control credit-card"'
	*
	* @param  array   $classes
	* @return $string
	*/
	protected function output_classes( $classes ) {

		$output = '';

		if ( is_array( $classes ) && count( $classes ) > 0 ) {
			$output .= ' class="';
			foreach ( $classes as $class ) {
				$output .= $class . ' ';
			}
			$output .= '"';
		} else if ( is_string( $classes ) ) {
			$output .= ' class="' . $classes . '"';
		}

		return $output;
	}

}





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
			'add_submit'   => true,
			'bootstrap'		 => false
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
		//add classes and wrapper element for Bootstrap, if requested
		if ( $this->settings['bootstrap'] ) {
			$input->add_setting( 'class', 'form-control' );
			$input->set( 'wrap_tag', 'div' );
			$input->set( 'wrap_class', 'form-group' );
		}
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
	function add_inputs( $arr ) {

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
				$output .= $this->_output_classes( $this->settings['class'] );
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
				'wrap_tag'         => 'div',
				'wrap_class'       => array( 'form_field_wrap', 'hidden' ),
				'wrap_id'          => '',
				'wrap_style'       => 'display: none',
				'request_populate' => false
			) );
		}

		// Add optional WordPress nonce field
		if ( $this->settings['add_nonce'] && function_exists( 'wp_create_nonce' ) ) {
			$this->add_input( 'WordPress nonce', array(
				'value'            => wp_create_nonce( $this->settings['add_nonce'] ),
				'add_label'        => false,
				'type'             => 'hidden',
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

class Input extends BaseClass {

	//label - nice name for the field. Can go in the label tag or placeholder
	//slug - all lowercase, no spaces version of the label, or another
	//computer-accessible label for the field
	protected $label, $args, $slug;

	//validators - validation objects attached to this field. Copied from $this->settings['validators']
	//value - safe value after the field passes validation
	//valid - boolean
	//message - to be displayed if the field fails validation
	private $validators = array(), $value, $valid, $message = '';

	/**
	*
	* @param string $label - nice name for the field. Can go in the label tag or placeholder
	* @param array $args - merged with defaults to create settings
	* @param string slug - all lowercase, no spaces version of the label, or another
	*/
	function __construct( $label, $args, $slug ) {

		$this->label = $label;

		// Create slug if we don't have one
		if ( $slug === '' ) {
			$slug = $this->slug = $this->make_slug( $label );
		} else {
			$slug = $this->slug;
		}

		$this->defaults = array(
			'type'             => 'text',
			'name'             => $slug,
			'id'               => $slug,
			'label'            => $label,
			'value'            => '', //display (HTML-escaped value)
			'placeholder'      => '',
			'class'            => array(),
			'min'              => '',
			'max'              => '',
			'step'             => '',
			'autofocus'        => false,
			'checked'          => false,
			'selected'         => false,
			'add_label'        => true,
			'options'          => array(),
			'wrap_tag'         => 'div',
			'wrap_class'       => array( 'form_field_wrap' ),
			'wrap_id'          => '',
			'wrap_style'       => '',
			'before_html'      => '',
			'after_html'       => '',
			'request_populate' => true,
			'required'         => false,
			'validators' 			 => array()
		);

		$this->create_settings( $args );

		//attach validators
		$this->attach_validators( $this->settings['validators'] );

		//replace numerically indexed options with a slug index.
		$options = array();
		foreach ($this->settings['options'] as $key => $option) {
			if ( is_int($key) ) $options[$this->make_slug($option)] = $option;
			else $options[$key] = $option;
		}
		$this->settings['options'] = $options;

		// Automatic population of values using $_REQUEST data
		if ( $this->settings['request_populate'] && isset( $_REQUEST[ $this->settings['name'] ] ) ) {

			// Can this field be populated directly?
			if ( ! in_array( $this->settings['type'], array( 'html', 'title', 'radio', 'checkbox', 'select', 'submit' ) ) ) {
				$this->settings['value'] = htmlspecialchars( $_REQUEST[ $this->settings['name'] ] );
			}
		}

		// Automatic population for SINGULAR checkboxes and radios
		if (
			$this->settings['request_populate'] &&
			( $this->settings['type'] == 'radio' || $this->settings['type'] == 'checkbox' ) &&
			empty( $this->settings['options'] )
		) {
			$this->settings['checked'] = isset( $_REQUEST[ $this->settings['name'] ] ) ? true : $this->settings['checked'];
		}
	}


	/**
	* Automaitcally add to the validators if this field is required,
	* or if it is an email, url, or number field
	*
	* We place these at the beginning of the validators array so that they are executed first.
	*/
	private function attach_validators($validators) {

		$this->validators = $validators;

		//place field-type-specific validators
		$type_validators = array('email', 'url', 'number');
		if ( in_array($this->settings['type'], $type_validators ) )
			array_unshift($this->validators, $this->settings['type']);

		//place required validator
		if ( $this->settings['required'] )
			array_unshift($this->validators, 'required');

	}


	/**
	* Little method for closing fields
	*/
	private function field_close() {
		return ' />';
	}

	/**
	* Create the HTML for the label. Called by self::build_input()
	*/
	private function build_label() {
		if ( ! empty( $label_html ) ) {
			return $label_html;
		} elseif ( $this->settings['add_label'] && ! in_array( $this->settings['type'], array( 'hidden', 'submit', 'title', 'html' ) ) ) {
			if ( $this->settings['required'] ) {
				$this->settings['label'] .= ' <strong>*</strong>';
			}
			return '<label for="' . $this->settings['id'] . '">' . $this->settings['label'] . '</label>';
		}
	}

	/**
	* Create the HTML for the field. Called by self::build_input()
	*/
	private function build_field() {

		$min_max_range = $element = $end = $attr = $field = '';

		switch ( $this->settings['type'] ) {

			case 'html':
				$element = '';
				$end     = $this->settings['label'];
				break;

			case 'title':
				$element = '';
				$end     = '
				<h3>' . $this->settings['label'] . '</h3>';
				break;

			case 'textarea':
				$element = 'textarea';
				$end     = '>' . $this->settings['value'] . '</textarea>';
				break;

			case 'select':
				$element = 'select';
				$end     .= '>';
				foreach ( $this->settings['options'] as $key => $opt ) {

					$opt_insert = '';
					if (
						// Is this field set to automatically populate?
						$this->settings['request_populate'] &&

						// Do we have $_REQUEST data to use?
						isset( $_REQUEST[ $this->settings['name'] ] ) &&

						// Are we currently outputting the selected value?
						$_REQUEST[ $this->settings['name'] ] === $key
					) {
						$opt_insert = ' selected';

					// Does the field have a default selected value?
					} else if ( $this->settings['selected'] === $key ) {
						$opt_insert = ' selected';
					}
					$end .= '<option value="' . $key . '"' . $opt_insert . '>' . $opt . '</option>';
				}
				$end .= '</select>';
				break;

			case 'radio':
			case 'checkbox':

				// Special case for multiple check boxes
				if ( count( $this->settings['options'] ) > 0 ) :
					$element = '';
					foreach ( $this->settings['options'] as $key => $opt ) {

						$slug = $this->_make_slug( $opt );
						$end .= sprintf(
							'<input type="%s" name="%s" value="%s" id="%s"',
							$this->settings['type'],
							$this->settings['name'],
							$key,
							$slug
						);
						if (
							// Is this field set to automatically populate?
							$this->settings['request_populate'] &&

							// Do we have $_REQUEST data to use?
							isset( $_REQUEST[ $this->settings['name'] ] ) &&

							// Is the selected item(s) in the $_REQUEST data?
							in_array( $key, $_REQUEST[ $this->settings['name'] ] )
						) {
							$end .= ' checked';
						}
						$end .= $this->field_close();
						$end .= ' <label for="' . $slug . '">' . $opt . '</label>';
					}
					$label_html = '<div class="checkbox_header">' . $this->settings['label'] . '</div>';
					break;
				endif;

			// Used for all text fields (text, email, url, etc), single radios, single checkboxes, and submit
			default :
				$element = 'input';
				$end .= ' type="' . $this->settings['type'] . '" value="' . $this->settings['value'] . '"';
				$end .= $this->settings['checked'] ? ' checked' : '';
				$end .= $this->field_close();
				break;

		}

		// Added a submit button, no need to auto-add one
		if ( $this->settings['type'] === 'submit' ) {
			$this->has_submit = true;
		}

		// Special number values for range and number types
		if ( $this->settings['type'] === 'range' || $this->settings['type'] === 'number' ) {
			$min_max_range .= ! empty( $this->settings['min'] ) ? ' min="' . $this->settings['min'] . '"' : '';
			$min_max_range .= ! empty( $this->settings['max'] ) ? ' max="' . $this->settings['max'] . '"' : '';
			$min_max_range .= ! empty( $this->settings['step'] ) ? ' step="' . $this->settings['step'] . '"' : '';
		}

		// Add an ID field, if one is present
		$id = ! empty( $this->settings['id'] ) ? ' id="' . $this->settings['id'] . '"' : '';

		// Output classes
		$class = $this->output_classes( $this->settings['class'] );

		// Special HTML5 fields, if set
		$attr .= $this->settings['autofocus'] ? ' autofocus' : '';
		$attr .= $this->settings['checked'] ? ' checked' : '';
		$attr .= $this->settings['required'] ? ' required' : '';

		// An $element was set in the $this->settings['type'] switch statement above so use that
		if ( ! empty( $element ) ) {
			if ( $this->settings['type'] === 'checkbox' ) {
				$field = '
				<' . $element . $id . ' name="' . $this->settings['name'] . '"' . $min_max_range . $class . $attr . $end .
								$field;
			} else {
				$field .= '
				<' . $element . $id . ' name="' . $this->settings['name'] . '"' . $min_max_range . $class . $attr . $end;
			}
		// Not a form element
		} else {
			$field .= $end;
		}

		return $field;
	}

	/**
	* Create the HTML for the errors. Called by self::build_input(). Remember to
	* validate the form first or the errors will not display!
	*/
	private function build_errors() {
		return '<div class="error">'.$this->message.'</div>';
	}

	/**
	* Build HTML for entire input widget, including wrapper, label, input and errors.
	*/
	public function build_input() {

		if ( $this->settings['type'] != 'hidden' && $this->settings['type'] != 'html' ) {

			$inner = $this->build_label() . $this->build_field() . $this->build_errors();

			$wrap_before = $this->settings['before_html'];
			if ( ! empty( $this->settings['wrap_tag'] ) ) {
				$wrap_before .= '<' . $this->settings['wrap_tag'];
				$wrap_before .= count( $this->settings['wrap_class'] ) > 0 ? $this->output_classes( $this->settings['wrap_class'] ) : '';
				$wrap_before .= ! empty( $this->settings['wrap_style'] ) ? ' style="' . $this->settings['wrap_style'] . '"' : '';
				$wrap_before .= ! empty( $this->settings['wrap_id'] ) ? ' id="' . $this->settings['wrap_id'] . '"' : '';
				$wrap_before .= '>';
			}

			$wrap_after = $this->settings['after_html'];
			if ( ! empty( $this->settings['wrap_tag'] ) ) {
				$wrap_after = '</' . $this->settings['wrap_tag'] . '>' . $wrap_after;
			}

			$output = $wrap_before . $inner . $wrap_after;
		} else {
			$output = $this->build_field();
		}

		return $output;
	}

	/**
	* Run through validators attached to this field.
	* If valid, set the value (now considered safe) and return true.
	* If ANY ONE OF the validators fails, set an error message and return false.
	* @return bool
	*/
	public function validate() {

		//
		$value = isset($_REQUEST[$this->slug]) ? $_REQUEST[$this->slug] : '';

		foreach ($this->validators as $validator) {
			//validator names can be supplied with args after a dash.
			//e.g. maxlength-35 calls Validator named maxlength and passes 35 as an argument
			$args = explode('-', $validator);
			$validator_name = array_shift( $args );

			//find the function if it has been registered, otherwise throw an Exception
			$function = Validators::get( $validator_name );
			if ( ! $function ) throw new Exception('No validator with key '.$validator.' set');
			//execute the attached function.
			if ( ! $function($value, $args) ) {
				//set to valid to false and stop checking
				$this->valid = FALSE;
				$this->value = NULL;
				$this->message = Validators::get_message( $validator );
				return FALSE;
			}
		}

		//everything OK. Set valid to true.
		$this->valid = TRUE;
		$this->value = $value;
		return TRUE;

	}

	/*
	* Get the slug for this input field.
	*/
	public function get_slug() {
		return $this->slug;
	}

	/*
	* Get the safe value for this input field.
	* This prop is set by the validate() method.
	* If no value is around return NULL.
	*/
	public function get_value() {
		return ($this->value ?: NULL);
	}

}

/*
*  This class acts as a place to publish.
*/
class Validators extends BaseClass {
	//array of registered validators.
	private static $validators;

	static function add( $key, $message, $function ) {
		if ( isset(self::$validators[$key]) ) return FALSE;
		self::$validators[$key]['function'] = $function;
		self::$validators[$key]['message'] = $message;
	}

	public static function update_message( $key, $message ) {
		self::$validators[$key]['message'] = $message;
	}

	public static function update_function( $key, $function ) {
		self::$validators[$key]['message'] = $function;
	}

	public static function get( $key ) {
		if ( ! isset(self::$validators[$key]['function'] ) ) return false;
		return self::$validators[$key]['function'];
	}

	public static function get_message( $key ) {
		if ( ! isset(self::$validators[$key]['message'] ) )
			return 'This value is not valid';
		else return self::$validators[$key]['message'];
	}

}

Validators::add('required', 'This field is required',
	function( $value, $args ) {
		if ( $value !== '' ) return TRUE;
		else return FALSE;
	}
);

Validators::add('email', 'Please enter a valid email address',
	function ( $value, $args ) {
		return (filter_var($value, FILTER_VALIDATE_EMAIL) ? TRUE : FALSE);
	}
);

Validators::add('url', 'Please enter a valid URL',
	function ( $value, $args ) {
		return filter_var($value, FILTER_VALIDATE_URL) ? TRUE : FALSE;
	}
);

Validators::add('number', 'Please enter a number',
	function ( $value, $args ) {
		if (
			filter_var($value, FILTER_VALIDATE_INT) ||
			filter_var($value, FILTER_VALIDATE_FLOAT)
		) return true;
		else return false;
	}
);
