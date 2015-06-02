<?php

class BaseClass {
	protected $settings, $defaults;

	protected function create_settings( $args ) {
		// Merge with arguments, if present
		if ( $args ) {
			$this->settings = array_merge( $this->defaults, $args );
		} else {
			$this->settings = $this->defaults;
		}
	}

	public function set_att( $key, $value ) {
		$this->settings[$key] = $value;
	}

	protected function add_att( $key, $value ) {
		if ( isset($this->settings[$key]) && is_array($this->settings[$key]) ) {
			$this->settings[$key] = $value;
			return true;
		} else {
			return false;
		}
	}

	// Validates id and class attributes
	// TODO: actually validate these things
	protected function _check_valid_attr( $string ) {

		$result = true;

		// Check $name for correct characters
		// "^[a-zA-Z0-9_-]*$"

		return $result;

	}

	// Create a slug from a label name
	protected function _make_slug( $string ) {

		$result = '';

		$result = str_replace( '"', '', $string );
		$result = str_replace( "'", '', $result );
		$result = str_replace( '_', '-', $result );
		$result = preg_replace( '~[\W\s]~', '-', $result );

		$result = strtolower( $result );

		return $result;

	}

	// Parses and builds HTML classes
	protected function _output_classes( $classes ) {

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


	// /**
	//  * Validate and set form
	//  *
	//  * @param string        $key A valid key; switch statement ensures validity
	//  * @param string | bool $this->settings A valid value; validated for each key
	//  *
	//  * @return bool
	//  */
	// function set_att( $key, $this->settings ) {
	//
	// 	switch ( $key ) :
	//
	// 		case 'action':
	// 			break;
	//
	// 		case 'method':
	// 			if ( ! in_array( $this->settings, array( 'post', 'get' ) ) ) {
	// 				return false;
	// 			}
	// 			break;
	//
	// 		case 'enctype':
	// 			if ( ! in_array( $this->settings, array( 'application/x-www-form-urlencoded', 'multipart/form-data' ) ) ) {
	// 				return false;
	// 			}
	// 			break;
	//
	// 		case 'markup':
	// 			if ( ! in_array( $this->settings, array( 'html', 'xhtml' ) ) ) {
	// 				return false;
	// 			}
	// 			break;
	//
	// 		case 'class':
	// 		case 'id':
	// 			if ( ! $this->_check_valid_attr( $this->settings ) ) {
	// 				return false;
	// 			}
	// 			break;
	//
	// 		case 'novalidate':
	// 		case 'add_honeypot':
	// 		case 'form_element':
	// 		case 'bootstrap':
	// 		case 'add_submit':
	// 			if ( ! is_bool( $this->settings ) ) {
	// 				return false;
	// 			}
	// 			break;
	//
	// 		case 'add_nonce':
	// 			if ( ! is_string( $this->settings ) && ! is_bool( $this->settings ) ) {
	// 				return false;
	// 			}
	// 			break;
	//
	// 		default:
	// 			return false;
	//
	// 	endswitch;
	//
	// 	$this->settings[ $key ] = $this->settings;
	//
	// 	return true;
	//
	// }

	/**
	 * Add an input field to the form for outputting later
	 *
	 * @param string $label
	 * @param string $args
	 * @param string $slug
	 */
	function add_input( $label, $args = array(), $slug = '' ) {
		$input = new Input( $label, $args, $slug );
		if ( $this->settings['bootstrap'] ) {
			$input->add_att( 'class', 'form-control' );
			$input->set_att( 'wrap_tag', 'div' );
			$input->set_att( 'wrap_class', 'form-group' );
		}
		$this->inputs[] = $input;
	}

	/**
	 * Add multiple inputs to the input queue
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

		// Add honeypot anti-spam field
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

		// Add a WordPress nonce field
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

	public function validate() {
		$valid = TRUE;
		foreach ( $this->inputs as $input ) {
			if ( ! $input->validate() ) $valid = FALSE;
		}
		return  $valid;
	}

}

class Input extends BaseClass {

	protected $label, $args, $slug;

	private $value, $valid, $message = '';

	function __construct( $label, $args, $slug ) {

		$this->label = $label;

		// Create a valid id or class attribute
		if ( $slug === '' ) {
			$slug = $this->slug = $this->_make_slug( $label );
		} else {
			$slug = $this->slug = $slug;
		}

		$this->defaults = array(
			'type'             => 'text',
			'name'             => $slug,
			'id'               => $slug,
			'label'            => $label,
			'value'            => '',
			'placeholder'      => '',
			'class'            => array(),
			'min'              => '',
			'max'              => '',
			'step'             => '',
			'autofocus'        => false,
			'checked'          => false,
			'selected'         => false,
			'required'         => false,
			'add_label'        => true,
			'options'          => array(),
			'wrap_tag'         => 'div',
			'wrap_class'       => array( 'form_field_wrap' ),
			'wrap_id'          => '',
			'wrap_style'       => '',
			'before_html'      => '',
			'after_html'       => '',
			'request_populate' => true,
			'validators' 			 => array()
		);

		$this->create_settings( $args );

		//attach validators
		$this->attach_validators( $this->args['type'] );

		//replace numerically indexed options with a slug
		$options = array();
		foreach ($this->settings['options'] as $key => $option) {
			if ( is_int($key) ) $options[$this->_make_slug($option)] = $option;
			else $options[$key] = $option;
		}
		$this->settings['options'] = $options;
	}


	// Easy way to auto-close fields, if necessary
	private function field_close() {
		return ' />';
	}

	// Build the label
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

	private function build_field() {

		$min_max_range = $element = $end = $attr = $field = '';

		// Automatic population of values using $_REQUEST data
		if ( $this->settings['request_populate'] && isset( $_REQUEST[ $this->settings['name'] ] ) ) {

			// Can this field be populated directly?
			if ( ! in_array( $this->settings['type'], array( 'html', 'title', 'radio', 'checkbox', 'select', 'submit' ) ) ) {
				$this->settings['value'] = $_REQUEST[ $this->settings['name'] ];
			}
		}

		// Automatic population for checkboxes and radios
		if (
			$this->settings['request_populate'] &&
			( $this->settings['type'] == 'radio' || $this->settings['type'] == 'checkbox' ) &&
			empty( $this->settings['options'] )
		) {
			$this->settings['checked'] = isset( $_REQUEST[ $this->settings['name'] ] ) ? true : $this->settings['checked'];
		}

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
							isset( $_REQUEST ) &&

							// Is the selected item(s) in the $_REQUEST data?
							in_array( $key, $_REQUEST )
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
		$class = $this->_output_classes( $this->settings['class'] );

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

	private function build_errors() {
		return '<div class="error">'.$this->message.'</div>';
	}

	public function build_input() {

		if ( $this->settings['type'] != 'hidden' && $this->settings['type'] != 'html' ) {

			$inner = $this->build_label() . $this->build_field(). $this->build_errors();

			$wrap_before = $this->settings['before_html'];
			if ( ! empty( $this->settings['wrap_tag'] ) ) {
				$wrap_before .= '<' . $this->settings['wrap_tag'];
				$wrap_before .= count( $this->settings['wrap_class'] ) > 0 ? $this->_output_classes( $this->settings['wrap_class'] ) : '';
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


	private function attach_validators( $type ) {

		// $validators = array(
		// 	'email' => 'email',
		// 	'url' => 'url',
		// 	'number' => 'number'
		// );
		// if ( isset($validators[$type]) ) {
		// 	$this->add_att($validators, 'AutoValidators::'.$validators[$type]);
		// 	return TRUE;
		// } else {
		// 	return FALSE;
		// }

		return;

	}

	public function validate() {
		// $value = isset($_REQUEST[$this->slug]) ? $_REQUEST[$this->slug] : '';
		//
		// if ( $this->settings['required'] && $value === '') {
		// 	$this->message = 'This field is required';
		// 	$this->valid = FALSE;
		// 	return FALSE;
		// } else if ( count($this->settings['validators']) ) {
		// 	foreach ($this->settings['validators'] as $validator) {
		// 		$message = call_user_func($validator, $value);
		// 		if ( $message !== '' ) {
		// 			$this->message = $message;
		// 			$this->valid = FALSE;
		// 			return FALSE;
		// 		}
		// 	}
		// }
		// if ( $this->settings['type'] === 'radio' || $this->settings['type'] === 'select' ) {
		// 	if ( ! array_key_exists($value, $this->settings['options'] ) ) {
		// 		$this->message = 'You entered an invalid option';
		// 		$this->valid = FALSE;
		// 		return FALSE;
		// 	}
		// }
		//
		// $this->valid = TRUE;
		return TRUE;
	}
}


class Validators {
	protected $validator = array();

	public static function add_validator( $key, $function, $message ) {

	}

	public static function get_validator( $key ) {
		return $this->validators[$key]['function'];
	}

}
