<?php

namespace NCBITaxonomyLookup;

use Exception;
use SimpleXMLElement;

class NCBITaxonomyLookup {

	/**
	 * @param $taxonomyId
	 *
	 * @return bool|SimpleXMLElement
	 */
	public static function getTaxonomyDataXML( $taxonomyId ) {
		global $wgNCBITaxonomyLookupCacheTTL,
			   $wgNCBITaxonomyLookupCacheRandomizeTTL,
			   $wgNCBITaxonomyApiTimeoutFallbackToCache;

		// Do we have records in cache and its TTL is not expired yet?
		$result = $cached = NCBITaxonomyLookupCache::getCache( $taxonomyId );

		if ( $result ) {
			// We have something, let's check TTL
			$ttl = NCBITaxonomyLookupCache::getCacheTTL( $taxonomyId );
			if( $ttl ) {
				// Check if we did pass the time point
				if ( time() > (int)$ttl ) {
					// Try to fetch new data
					$result = false;
				}
			} else {
				// We should not land here ever, but if we do - pretend we have no cached values
				$result = $cached = false;
			}
		}

		if ( !$result ) {
			// We don't have anything for this taxonomy, do the actual fetch
			$result = self::fetchApi( $taxonomyId );
			// Prepare TTL as UNIX stamp
			$cacheTTL = $wgNCBITaxonomyLookupCacheTTL;
			if( $wgNCBITaxonomyLookupCacheRandomizeTTL ) {
				$cacheTTL = mt_rand(
					abs( $wgNCBITaxonomyLookupCacheTTL - $wgNCBITaxonomyLookupCacheTTL / 10 ),
					abs( $wgNCBITaxonomyLookupCacheTTL + $wgNCBITaxonomyLookupCacheTTL / 10 )
				);
			}
			$cacheTTL += time();
			// Test for timeout on the API
			if( $result ) {
				// Set cached value
				NCBITaxonomyLookupCache::setCache( $taxonomyId, $result, $cacheTTL );
			} else {
				// Something is wrong with fetching the data, try to recover from cache
				if( $cached && $wgNCBITaxonomyApiTimeoutFallbackToCache ) {
					$result = $cached;
					// Prolong the cached value TTL
					NCBITaxonomyLookupCache::setCache( $taxonomyId, $result, $cacheTTL );
				}else{
					// We have nothing left to do
					return false;
				}
			}
		}

		if ( $result ) {
			return new SimpleXMLElement( $result );
		}

		return false;
	}

	/**
	 * @param $taxonomyId
	 *
	 * @return bool|string
	 */
	public static function fetchApi( $taxonomyId ) {
		global $wgNCBITaxonomyLookupApiURL, $wgNCBITaxonomyApiKey;
		$uri = $wgNCBITaxonomyLookupApiURL
			   . '?db=taxonomy'
			   . '&rettype=fasta'
			   . '&retmode=xml'
			   . '&id=' . $taxonomyId;
		if( $wgNCBITaxonomyApiKey ) {
			$uri .= '&api_key=' . $wgNCBITaxonomyApiKey;
		}
		return self::fetchRemote( $uri );
	}

	/**
	 * @param $uri
	 *
	 * @return bool|string
	 */
	protected static function fetchRemote( $uri ) {
		global $wgNCBITaxonomyApiTimeout;
		try {
			$ctx = stream_context_create([
				'http' => [
					'timeout' => $wgNCBITaxonomyApiTimeout
				]
			]);
			$result = file_get_contents( $uri, false, $ctx );
		}
		catch ( Exception $e ) {
			$curl = curl_init( $uri );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $curl, CURLOPT_TIMEOUT, $wgNCBITaxonomyApiTimeout);
			$result = curl_exec( $curl );
			curl_close( $curl );
		}
		return $result;
	}

}
