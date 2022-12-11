<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\PrefetchingTermLookup;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$prefetchingTermLookup = $this->createMock( PrefetchingTermLookup::class );
		$this->mockService( 'WikibaseClient.PrefetchingTermLookup', $prefetchingTermLookup );

		$termLookup = $this->getService( 'WikibaseClient.TermLookup' );

		$this->assertSame( $prefetchingTermLookup, $termLookup );
	}

}
