<?php

namespace NCBITaxonomyLookup;

/**
 * Cache for taxonomy lookups calls
 *
 * @author WikiTeq
 */
class NCBITaxonomyLookupCache {

	/**
	 * Fetches cached value
	 *
	 * @param $taxonomyId
	 *
	 * @return string|boolean
	 */
	public static function getCache( $taxonomyId ) {
		$cache = wfGetCache( CACHE_ANYTHING );
		$key = wfMemcKey( 'ncbitaxonomylookup', $taxonomyId );
		$cached = $cache->get( $key );
		wfDebugLog( "NCBITaxonomyLookup",
			__METHOD__ . ": got " . var_export( $cached, true ) .
			" from cache." );
		return $cached;
	}

	/**
	 * Fetches the cache record with TTL value
	 *
	 * @param $taxonomyId
	 *
	 * @return mixed
	 */
	public static function getCacheTTL( $taxonomyId ) {
		$cache = wfGetCache( CACHE_ANYTHING );
		$key = wfMemcKey( 'ncbitaxonomylookup_ttl', $taxonomyId );
		return $cache->get( $key );
	}

	/**
	 * Stores the value and TTL in cache
	 *
	 * @param $taxonomyId
	 * @param $data
	 * @param integer $cache_expire
	 */
	public static function setCache( $taxonomyId, $data, $cache_expire = 0 ) {
		$cache = wfGetCache( CACHE_ANYTHING );
		$key = wfMemcKey( 'ncbitaxonomylookup', $taxonomyId );
		wfDebugLog( "NCBITaxonomyLookup",
			__METHOD__ . ": caching " . var_export( $data, true ) .
			" from Google." );
		$cache->set( $key, $data );
		// Separately store the artificial TTL
		$ttlKey = wfMemcKey( 'ncbitaxonomylookup_ttl', $taxonomyId );
		$cache->set( $ttlKey, $cache_expire );
	}

	/**
	 * Expires the cache record and TTL
	 *
	 * @param $taxonomyId
	 * @deprecated perhaps we dont need this at all since setCache will overwrite existing records
	 */
	public static function deleteCache( $taxonomyId ) {
		$cache = wfGetCache( CACHE_ANYTHING );
		$key = wfMemcKey( 'ncbitaxonomylookup', $taxonomyId );
		$cache->delete( $key );
		$ttlKey = wfMemcKey( 'ncbitaxonomylookup_ttl', $taxonomyId );
		$cache->delete( $ttlKey );
	}
}
