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
	 * @param int $taxonomyId
	 *
	 * @return string|bool
	 */
	public static function getCache( $taxonomyId ) {
		$cache = \ObjectCache::getInstance( CACHE_ANYTHING );
		$key = $cache->makeKey( 'ncbitaxonomylookup', $taxonomyId );
		$cached = $cache->get( $key );
		wfDebugLog( "NCBITaxonomyLookup",
			__METHOD__ . ": got " . var_export( $cached, true ) .
			" from cache." );
		return $cached;
	}

	/**
	 * Fetches the cache record with TTL value
	 *
	 * @param int $taxonomyId
	 *
	 * @return mixed
	 */
	public static function getCacheTTL( $taxonomyId ) {
		$cache = \ObjectCache::getInstance( CACHE_ANYTHING );
		$key = $cache->makeKey( 'ncbitaxonomylookup_ttl', $taxonomyId );
		return $cache->get( $key );
	}

	/**
	 * Stores the value and TTL in cache
	 *
	 * @param int $taxonomyId
	 * @param string $data
	 * @param int $cache_expire
	 */
	public static function setCache( $taxonomyId, $data, $cache_expire = 0 ) {
		$cache = \ObjectCache::getInstance( CACHE_ANYTHING );
		$key = $cache->makeKey( 'ncbitaxonomylookup', $taxonomyId );
		wfDebugLog( "NCBITaxonomyLookup",
			__METHOD__ . ": caching " . var_export( $data, true ) .
			" from Google." );
		$cache->set( $key, $data );
		// Separately store the artificial TTL
		$ttlKey = $cache->makeKey( 'ncbitaxonomylookup_ttl', $taxonomyId );
		$cache->set( $ttlKey, $cache_expire );
	}

	/**
	 * Expires the cache record and TTL
	 *
	 * @param int $taxonomyId
	 * @deprecated perhaps we dont need this at all since setCache will overwrite existing records
	 */
	public static function deleteCache( $taxonomyId ) {
		$cache = \ObjectCache::getInstance( CACHE_ANYTHING );
		$key = $cache->makeKey( 'ncbitaxonomylookup', $taxonomyId );
		$cache->delete( $key );
		$ttlKey = $cache->makeKey( 'ncbitaxonomylookup_ttl', $taxonomyId );
		$cache->delete( $ttlKey );
	}
}
