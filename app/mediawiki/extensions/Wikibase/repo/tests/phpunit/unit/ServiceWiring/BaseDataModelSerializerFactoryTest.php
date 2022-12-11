<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class BaseDataModelSerializerFactoryTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->assertInstanceOf(
			SerializerFactory::class,
			$this->getService( 'WikibaseRepo.BaseDataModelSerializerFactory' )
		);
	}

}
