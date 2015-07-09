<?php

namespace PhpFormBuilder;
use PhpFormBuilder;

class Field extends BaseClass {

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
	function __construct( $label, $args, $slug = '' ) {

		$this->label = $label;

		// Create slug if we don't have one
		if ( $slug === '' ) {
			$this->slug = $slug = $this->make_slug( $label );
		} else {
			$this->slug = $slug;
		}

		$this->defaults = array(
			'type'             => 'text',
			'name'             => $slug,
			'id'               => $slug,
			'label'            => $label,
			'label_class' 		 => array(),
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
			'format' 					 => '<div %s>%s%s%s</div>',
			'wrap_class'  		 => array('form-group'),
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
		$type_validators = array('email', 'url', 'number', 'honeypot', 'nonce');
		if ( in_array($this->settings['type'], $type_validators ) )
			array_unshift($this->validators, $this->settings['type']);

		//place required validator
		//ignore if this is a file
		//TODO: split files off into their own class
		if ( $this->settings['required'] && $this->settings['type'] !== 'file' )
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
			$class = $this->output_classes( $this->settings['label_class'] );
			return '<label for="' . $this->settings['id'] . '" '.$class.'>' . $this->settings['label'] . '</label>';
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

						$slug = $this->make_slug( $opt );
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
							in_array( $key, $_REQUEST )
						) {
							$end .= ' checked';
						}
						$end .= $this->field_close();
						$end .= ' <label for="' . $slug . '">' . $opt . '</label><br />';
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

			$output = sprintf( $this->settings['format'],
				$this->output_classes( $this->settings['wrap_class']),
				$this->build_label(),
				$this->build_field(),
				$this->build_errors()
			);

		} else {
			$output = $this->build_field();
		}

		return $output;
	}

	/*
	* This is a temporary method until we split off classes for handling files
	*/
	public function validate_file() {
		$file = isset($_FILES[$this->slug]) ? $_FILES[$this->slug] : false;
		if ( !$file ) {
			throw new Exception('No file found for field with slug '.$this->slug);
		}
		if ( ! $file['name'] || $file['name'] === '' ) {
			$this->add_setting('class', 'invalid');
			$this->message = 'This field is required';
			return FALSE;
		}
		$accepted = array(
			'application/pdf',
			'application/x-pdf',
			'application/msword',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
		);
		if ( ! in_array($file['type'], $accepted) ) {
			$this->add_setting('class', 'invalid');
			$this->message = 'Please use .doc, .docx, or .pdf files only';
			return FALSE;
		}
		return true;
	}

	/**
	* Run through validators attached to this field.
	* If valid, set the value (now considered safe) and return true.
	* If ANY ONE OF the validators fails, set an error message and return false.
	* @return bool
	*/
	public function validate() {

		if ( $this->settings['type'] === 'file' ) {
			//pass this off to validate file
			return $this->validate_file();
		}

		//get value in a safe way
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
				$this->add_setting('class', 'invalid');
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
