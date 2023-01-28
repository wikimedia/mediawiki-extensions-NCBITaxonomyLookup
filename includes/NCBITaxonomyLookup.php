<?php

namespace NCBITaxonomyLookup;

use Exception;
use MediaWiki\MediaWikiServices;
use WanObjectCache;

class NCBITaxonomyLookup {

	/**
	 * Get the result from cache or regenerate it.
	 *
	 * @param string $id The id number to lookup
	 * @return string|bool The XML as a string, or false on failure
	 */
	public static function getCachedTaxonomyData( $id ) {
		global $wgNCBITaxonomyApiTimeoutFallbackToCache,
			$wgNCBITaxonomyLookupCacheTTL,
			$wgNCBITaxonomyApiTimeout;

		wfDebugLog( "NCBITaxonomyLookup", __METHOD__ . " Looking up $id" );
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();

		$key = $cache->makeKey( 'ncbitaxonomylookup', $id );
		return $cache->getWithSetCallback(
			$key,
			$wgNCBITaxonomyLookupCacheTTL,
			static function ( $oldVal, &$ttl ) use ( $id ) {
				return self::getTaxonomyDataXML( (int)$id, $oldVal, $ttl );
			},
			[
				// Keep a version around in process. We often look up the same
				// one over and over again when parsing pages, so don't go to redis
				// each time if we have already looked it up.
				'pcTTL' => WANObjectCache::TTL_PROC_LONG,
				// We often look the same key up over and over again. Store last 100
				'pcGroup' => 'ncbitaxonomylookup:100',
				// Keep stale stuff around and fallback to it if site doesn't respond.
				'staleTTL' => $wgNCBITaxonomyApiTimeoutFallbackToCache ? 60 * 60 * 24 * 30 : 0,
				// If this is going to expire in the next 12 hours, do an automatic
				// refresh. Automatic refreshes happen after the page is sent
				// so are less visible to the user than a stale key.
				'lowTTL' => 60 * 60 * 12,
				// If we have a stale value, and someone else is refetching,
				// use the stale value instead of multiple people refetching.
				'lockTSE' => $wgNCBITaxonomyApiTimeout + 3,
			]
		);
	}

	/**
	 * @param int $taxonomyId
	 * @param string $oldVal Stale data
	 * @param int &$ttl Override how long to cache value for
	 * @return bool|string
	 */
	public static function getTaxonomyDataXML( $taxonomyId, $oldVal, &$ttl ) {
		$newResult = self::fetchApi( $taxonomyId );
		if ( $newResult ) {
			return $newResult;
		}

		if ( $oldVal ) {
			wfDebugLog( "NCBITaxonomyLookup", "Using stale value from cache" );
			// Our api request failed, but at least we still have a
			// stale result. Return it and cache it just for a little bit.
			$ttl = 60 * 30;
			return $oldVal;
		}

		wfDebugLog( "NCBITaxonomyLookup", "Could not fetch and no stale version." );
		// We have nothing!
		$ttl = WANObjectCache::TTL_UNCACHEABLE;
		return false;
	}

	/**
	 * @param int $taxonomyId
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
		if ( $wgNCBITaxonomyApiKey ) {
			$uri .= '&api_key=' . $wgNCBITaxonomyApiKey;
		}
		return self::fetchRemote( $uri );
	}

	/**
	 * @param string $uri
	 *
	 * @return bool|string
	 */
	protected static function fetchRemote( $uri ) {
		global $wgNCBITaxonomyApiTimeout;
		try {
			$ctx = stream_context_create( [
				'http' => [
					'timeout' => $wgNCBITaxonomyApiTimeout
				]
			] );
			$result = file_get_contents( $uri, false, $ctx );
		} catch ( Exception $e ) {
			$curl = curl_init( $uri );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $curl, CURLOPT_TIMEOUT, $wgNCBITaxonomyApiTimeout );
			$result = curl_exec( $curl );
			curl_close( $curl );
		}
		wfDebugLog( "NCBITaxonomyLookup",
				__METHOD__ . ": got " . var_export( $result, true ) .
				" from internet."
		);
		return $result;
	}

}
