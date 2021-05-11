<?php

namespace NCBITaxonomyLookup;

use Exception;
use Parser;

class NCBITaxonomyLookupHooks {

	/**
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser &$parser ) {
		$parser->setFunctionHook( 'taxonomy', 'NCBITaxonomyLookup\\NCBITaxonomyLookupHooks::taxonomy' );
	}

	/**
	 *
	 * @param Parser $parser
	 * @param null $taxonomyId
	 *
	 * @param null $xpath
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function taxonomy( Parser &$parser, $taxonomyId = null, $xpath = null ) {
		if( !$taxonomyId ) {
			return [ '', 'markerType' => 'nowiki' ];
		}
		if ( !is_numeric( $taxonomyId ) ) {
			return [ '', 'markerType' => 'nowiki' ];
		}
		if( !$xpath ) {
			throw new Exception( 'The $xpath parameter must be set' );
		}

		$data = NCBITaxonomyLookup::getTaxonomyDataXML( $taxonomyId );
		$value = '';
		if( $data ) {
			// Checking xpath
			$found = $data->xpath( $xpath );
			// Getting the first element value
			if( $found && count( $found ) ) {
				// Internal to string conversion
				$value = $found[0][0];
			}
		}
		return [
			$value,
			'markerType' => 'nowiki'
		];
	}
}
