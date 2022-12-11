<?php

namespace Wikibase\Lib\Tests\Store;

use MediaWikiIntegrationTestCase;
use Title;
use Wikibase\Lib\Store\WikiPagePropertyOrderProvider;
use WikitextContent;

/**
 * @covers \Wikibase\Lib\Store\WikiPagePropertyOrderProvider
 * @covers \Wikibase\Lib\Store\WikiTextPropertyOrderProvider
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Lucie-Aimée Kaffee
 * @author Marius Hoch
 */
class WikiPagePropertyOrderProviderTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->tablesUsed[] = 'page';
	}

	public function provideGetPropertyOrder() {
		return WikiTextPropertyOrderProviderTestHelper::provideGetPropertyOrder();
	}

	/**
	 * @dataProvider provideGetPropertyOrder
	 */
	public function testGetPropertyOrder( $text, $expected ) {
		$this->makeWikiPage( 'MediaWiki:Wikibase-SortedProperties', $text );
		$instance = new WikiPagePropertyOrderProvider(
			$this->getServiceContainer()->getWikiPageFactory(),
			Title::newFromTextThrow( 'MediaWiki:Wikibase-SortedProperties' )
		);
		$propertyOrder = $instance->getPropertyOrder();
		$this->assertSame( $expected, $propertyOrder );
	}

	private function makeWikiPage( $name, $text ) {
		$title = Title::newFromTextThrow( $name );
		$wikiPage = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
		$wikiPage->doUserEditContent(
			new WikitextContent( $text ),
			$this->getTestUser()->getUser(),
			'test'
		);
	}

	public function testGetPropertyOrder_pageDoesNotExist() {
		$instance = new WikiPagePropertyOrderProvider(
			$this->getServiceContainer()->getWikiPageFactory(),
			Title::newFromTextThrow( 'MediaWiki:WikiPagePropertyOrderProviderTest-DoesNotExist' )
		);
		$propertyOrder = $instance->getPropertyOrder();
		$this->assertSame( null, $propertyOrder );
	}

}
