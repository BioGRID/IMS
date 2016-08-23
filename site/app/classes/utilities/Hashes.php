<?php 

namespace IMS\app\classes\utilities;

/**
 * Hashes
 * Utilities for creating and deciphering unique hashes created to speed up
 * testing for duplicate data
 */


class Hashes {
	
	private $hashids;
	
	function __construct( ) {
		$this->hashids = new \Hashids\Hashids( );
	}
	
	/**
	 * Combine two numbers into a single number with triple zero padding 
	 * in the middle, not by addition. Result is an integer, so it's easy to
	 * sort yet we shouldn't run into problems with duplication.
	 */
	
	private function combineNumbers( $num1, $num2 ) {
		return (int)((string)$num1 . "000" . (string)$num2);
	}
	
	/**
	 * Create Hashes for two arrays of values
	 */
	 
	public function generateHash( $values1, $values2 ) {
		
		$combined = array_combine( $values1, $values2 );
		$combinedList = array( );
		foreach( $combined as $val1 => $val2 ) {
			$combinedList[] = $this->combineNumbers( $val1, $val2 );
		}
		
		sort( $combinedList, SORT_NUMERIC );
		
		return $this->hashids->encode( $combinedList );
		
	}
	
}