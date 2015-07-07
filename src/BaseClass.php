<?php

namespace PhpFormBuilder;

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
	public function add_setting( $key, $value ) {
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

?>
