<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Language;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UserLanguageTest extends ServiceWiringTestCase {

	private $cachedLang;

	protected function setUp(): void {
		parent::setUp();

		global $wgLang;

		$this->cachedLang = clone $wgLang;
	}

	protected function tearDown(): void {
		parent::tearDown();

		global $wgLang;

		$wgLang = $this->cachedLang;
	}

	public function testReturnsGlobal(): void {
		global $wgLang;

		$wgLang = $this->createMock( Language::class );

		$this->assertSame(
			$wgLang,
			$this->getService( 'WikibaseRepo.UserLanguage' )
		);
	}

	public function testThrowsWhenNoLanguageDefined(): void {
		global $wgLang;

		$wgLang = null;

		$this->expectException( 'MWException' );
		$this->getService( 'WikibaseRepo.UserLanguage' );
	}
}
