<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit;

use Exception;
use Wikibase\Client\PropertyLabelNotResolvedException;

/**
 * @covers \Wikibase\Client\PropertyLabelNotResolvedException
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class PropertyLabelNotResolvedExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testAsMessageException() {
		$ex = new PropertyLabelNotResolvedException( '<LABEL>', '<LANGUAGECODE>' );

		$this->assertSame( 'wikibase-property-notfound', $ex->getKey() );
		$this->assertSame( [ '<LABEL>', '<LANGUAGECODE>' ], $ex->getParams() );
	}

	public function testAsException() {
		$message = 'some message';
		$previous = new Exception();

		$ex = new PropertyLabelNotResolvedException( '', '', $message, $previous );

		$this->assertSame( $message, $ex->getMessage() );
		$this->assertSame( $previous, $ex->getPrevious() );
	}

}
