<?php

namespace NCBITaxonomyLookup;

use Exception;
use Parser;
use SimpleXMLElement;

class NCBITaxonomyLookupHooks {

	/**
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'taxonomy', 'NCBITaxonomyLookup\\NCBITaxonomyLookupHooks::taxonomy' );
	}

	/**
	 * @param Parser $parser
	 * @param string|null $taxonomyId
	 * @param string|null $xpath
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function taxonomy( Parser $parser, $taxonomyId = null, $xpath = null ) {
		if ( !$taxonomyId ) {
			return [ '', 'markerType' => 'nowiki' ];
		}
		if ( !is_numeric( $taxonomyId ) ) {
			return [ '', 'markerType' => 'nowiki' ];
		}
		if ( !$xpath ) {
			throw new Exception( 'The $xpath parameter must be set' );
		}

		$data = NCBITaxonomyLookup::getCachedTaxonomyData( $taxonomyId );
		$value = '';
		if ( $data ) {
			// Checking xpath
			try {
				$xml = new SimpleXMLElement( $data );
			} catch ( Exception $e ) {
				// API not returning valid XML probably means a transient
				// error like a 500. Reduce page cache to avoid caching bad values
				$parser->getOutput()->updateCacheExpiry( 90 );
				return [ '' ];
			}
			$found = $xml->xpath( $xpath );
			// Getting the first element value
			if ( $found && count( $found ) ) {
				// Internal to string conversion
				$value = $found[0][0];
			}
		} else {
			// Transient api error. Reduce cache time
			$parser->getOutput()->updateCacheExpiry( 90 );
		}
		return [
			$value,
			'markerType' => 'nowiki'
		];
	}
}
