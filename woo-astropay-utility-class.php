<?php

/*
 * Class with some utility functions for this addon
 */

class WC_AstroPay_Utility {


    function __construct() {
	
    }

    static function is_card_number_valid( $crdNum ) {
		if ( ! is_numeric( $crdNum ) )
			return false;
	
		$number	 = preg_replace( '/[^0-9]+/', '', $crdNum );
		$strlen	 = strlen( $number );
		$sum	 = 0;
	
		if ( $strlen < 16 )
			return false;
	
		for ( $i = 0; $i < $strlen; $i ++  ) {
			$digit = substr( $number, $strlen - $i - 1, 1 );
			if ( $i % 2 == 1 ) {
				$sub_total = $digit * 2;
				if ( $sub_total > 9 ) {
					$sub_total = 1 + ($sub_total - 10);
				}
			} 
			else {
				$sub_total = $digit;
			}
			$sum += $sub_total;
		}
	
		if ( $sum > 0 AND $sum % 10 == 0 )
			return true;
	
		return false;
    }


    static function is_expirydt_valid( $month, $year ) {
		$now		 = time();
		$thisYear	 = (int) date( 'Y', $now );
		$thisMonth	 = (int) date( 'm', $now );
	
		if ( is_numeric( $year ) && is_numeric( $month ) ) {
			$thisDate	 = mktime( 0, 0, 0, $thisMonth, 1, $thisYear );
			$expireDate	 = mktime( 0, 0, 0, $month, 1, $year );
	
			return $thisDate <= $expireDate;
		}
	
		return false;
    }

    static function is_cvv_valid( $crdNum ) {
		$length = strlen( $crdNum );
		return is_numeric( $crdNum ) AND $length > 2 AND $length < 5;
    }

}
