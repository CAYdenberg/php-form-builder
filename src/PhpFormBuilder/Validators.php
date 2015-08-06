<?php namespace PhpFormBuilder;

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

/*
* Built in validators
*/

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

//valid: 4242424242424242, 4242-4242-4242-4242, 4242 4242 4242 4242
//invalid: anything without 16 digits exactly
Validators::add('credit_card', 'Please enter a valid credit card number',
  function ( $value, $args ) {
    //remove anything that's not a digit
    $number = preg_replace('/(\D)/', '', $value);
		settype($number, 'string');
		$sumTable = array(
			array(0,1,2,3,4,5,6,7,8,9),
			array(0,2,4,6,8,1,3,5,7,9));
		$sum = 0;
		$flip = 0;
		for ($i = strlen($number) - 1; $i >= 0; $i--) {
			$sum += $sumTable[$flip++ & 0x1][$number[$i]];
		}
		return $sum % 10 === 0;
  }
);

//valid: 07/19
//invalid: 0719, 00/19, 07/01/19
Validators::add('expiry_date', 'Please enter a valid expiry date in the format MM/YY',
  function ( $value, $args ) {
    $date_parts = explode('/', $value);
    //date does not have exactly 2 parts
    if ( count($date_parts) !== 2 ) return false;

    $month = $date_parts[0];
    //make sure month is a string of exactly 2 digits
    if ( !preg_match('/^\d{2}$/', $month) ) return false;
    //make sure month is in range 1 - 12
    if ( intval($month) < 1 || intval($month) > 12 ) return false;

    $year = $date_parts[1];
    //make sure year is a string of exactly 2 digits
    if ( !preg_match('/^\d{2}$/', $month) ) return false;
    return true;
  }
);

Validators::add('cvc', 'Please enter a valid card verification code',
	function ( $value, $args ) {
		if ( preg_match('/\D/', $value) ) return false;
		if ( strlen($value) < 3 || strlen($value) > 4 ) return false;
		return true;
	}
);

Validators::add('honeypot', 'Something isn\'t right here',
	function( $value, $args ) {
		return ($value === '');
	}
);

Validators::add('nonce', 'Something smells fishy',
	function( $value, $args ) {
		if ( !function_exists('wp_verify_nonce') ) {
			throw new Exception('Attemping to create nonce outside of Wordpress context');
		}
		return wp_verify_nonce($value);
	}
);
