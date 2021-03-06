<?php

namespace SMW\Tests\MediaWiki;

use SMW\Cache\CacheFactory;
use SMW\ApplicationFactory;

/**
 * @covers \SMW\Cache\CacheFactory
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class CacheFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Cache\CacheFactory',
			new CacheFactory( 'hash' )
		);
	}

	public function testGetMainCacheType() {

		$instance = new CacheFactory( 'hash' );

		$this->assertEquals(
			'hash',
			$instance->getMainCacheType()
		);

		$instance = new CacheFactory( CACHE_NONE );

		$this->assertEquals(
			CACHE_NONE,
			$instance->getMainCacheType()
		);
	}

	public function testCanConstructFixedInMemoryCache() {

		$instance = new CacheFactory( 'hash' );

		$this->assertInstanceOf(
			'Onoi\Cache\Cache',
			$instance->newFixedInMemoryCache()
		);
	}

	public function testCanConstructCacheOptions() {

		$instance = new CacheFactory( 'hash' );

		$cacheOptions = $instance->newCacheOptions( array(
			'useCache' => true
		) );

		$this->assertTrue(
			$cacheOptions->useCache
		);
	}

	public function testCanConstructMediaWikiCompositeCache() {

		$instance = new CacheFactory( 'hash' );

		$this->assertInstanceOf(
			'Onoi\Cache\Cache',
			$instance->newMediaWikiCompositeCache( CACHE_NONE )
		);

		$this->assertInstanceOf(
			'Onoi\Cache\Cache',
			$instance->newMediaWikiCompositeCache( $instance->getMainCacheType() )
		);
	}

}
