<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityNamespaceLookupTest extends ServiceWiringTestCase {

	private function getEntitySources( array ...$entityNSDefinitions ): EntitySourceDefinitions {
		return new EntitySourceDefinitions( array_map(
			function ( array $nsDefinitions, int $idx ): DatabaseEntitySource {
				return new DatabaseEntitySource(
					'test-' . $idx,
					false,
					$nsDefinitions,
					'',
					'',
					'',
					''
				);
			},
			$entityNSDefinitions,
			range( 0, count( $entityNSDefinitions ) - 1 )
		), new SubEntityTypesMapper( [] ) );
	}

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseClient.EntitySourceDefinitions',
			$this->getEntitySources( [
				'something' => [
					'namespaceId' => 666,
					'slot' => 'main'
				],
				'another' => [
					'namespaceId' => 777,
					'slot' => 'main'
				]
			], [
				'different-thing' => [
					'namespaceId' => 42,
					'slot' => 'main'
				]
			] )
		);

		/** @var EntityNamespaceLookup $entityNSLookup */
		$entityNSLookup = $this->getService( 'WikibaseClient.EntityNamespaceLookup' );

		$this->assertInstanceOf(
			EntityNamespaceLookup::class,
			$entityNSLookup
		);

		$this->assertSame( [
			'something' => 666,
			'another' => 777,
			'different-thing' => 42
		], $entityNSLookup->getEntityNamespaces() );
	}

}
