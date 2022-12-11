<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Hooks;

use MediaWikiIntegrationTestCase;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Hooks\OutputPageJsConfigHookHandler;

/**
 * @covers \Wikibase\Repo\Hooks\OutputPageJsConfigHookHandler
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch
 */
class OutputPageJsConfigHookHandlerTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider doOutputPageBeforeHtmlRegisterConfigProvider
	 */
	public function testDoOutputPageBeforeHtmlRegisterConfig( array $expected, Title $title, string $message ) {
		$entityNamespaceLookup = new EntityNamespaceLookup( [ $title->getNamespace() ] );

		$hookHandler = new OutputPageJsConfigHookHandler(
			$entityNamespaceLookup,
			'https://creativecommons.org',
			'CC-0',
			[],
			42,
			false
		);

		$context = new RequestContext();
		$context->setTitle( $title );

		$output = $context->getOutput();
		$text = '';

		$hookHandler->onOutputPageBeforeHTML( $output, $text );

		$configVars = $output->getJsConfigVars();

		$this->assertEquals( $expected, array_keys( $configVars ), $message );
	}

	public function doOutputPageBeforeHtmlRegisterConfigProvider() {
		$expected = [ 'wbCopyright', 'wbBadgeItems', 'wbMultiLingualStringLimit', 'wbTaintedReferencesEnabled' ];

		$entityId = new ItemId( 'Q4' );
		$title = $this->getTitleForId( $entityId );

		return [
			[ $expected, $title, 'config vars added to OutputPage' ]
		];
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return Title
	 */
	public function getTitleForId( EntityId $entityId ) {
		$name = $entityId->getEntityType() . ':' . $entityId->getSerialization();
		return Title::makeTitle( NS_MAIN, $name );
	}

}
