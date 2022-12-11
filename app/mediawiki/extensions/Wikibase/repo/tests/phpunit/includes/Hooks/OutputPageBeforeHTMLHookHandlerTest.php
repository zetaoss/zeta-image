<?php

namespace Wikibase\Repo\Tests\Hooks;

use FauxRequest;
use Language;
use MediaWikiIntegrationTestCase;
use NullHttpRequestFactory;
use NullStatsdDataFactory;
use OutputPage;
use PHPUnit\Framework\MockObject\MockObject;
use RequestContext;
use Title;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\EntityFactory;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\UserLanguageLookup;
use Wikibase\Repo\Hooks\Helpers\OutputPageEditability;
use Wikibase\Repo\Hooks\Helpers\OutputPageEntityViewChecker;
use Wikibase\Repo\Hooks\Helpers\UserPreferredContentLanguagesLookup;
use Wikibase\Repo\Hooks\OutputPageBeforeHTMLHookHandler;
use Wikibase\Repo\Hooks\OutputPageEntityIdReader;
use Wikibase\Repo\ParserOutput\TermboxView;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers \Wikibase\Repo\Hooks\OutputPageBeforeHTMLHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class OutputPageBeforeHTMLHookHandlerTest extends MediaWikiIntegrationTestCase {

	private $editability;
	private $uiLanguageCode;
	private $userLanguageLookup;
	private $entityRevisionLookup;
	private $outputPageEntityIdReader;
	private $entityFactory;

	/**
	 * @var ItemId
	 */
	private $itemId;
	private $languageNameLookup;
	private $preferredLanguageLookup;

	/**
	 * @var StaticContentLanguages
	 */
	private $contentLanguages;

	/**
	 * @var bool
	 */
	private $isExternallyRendered;

	/**
	 * @var MockObject|OutputPageEntityViewChecker
	 */
	private $entityViewChecker;

	protected function setUp(): void {
		parent::setUp();
		$this->itemId = new ItemId( 'Q1' );
		$this->uiLanguageCode = 'en';

		$this->userLanguageLookup = $this->createMock( UserLanguageLookup::class );
		$this->contentLanguages = new StaticContentLanguages( [ 'en', 'es', 'ru' ] );
		$this->entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$this->languageNameLookup = $this->createMock( LanguageNameLookup::class );
		$this->outputPageEntityIdReader = $this->createMock( OutputPageEntityIdReader::class );
		$this->entityFactory = $this->createMock( EntityFactory::class );
		$this->editability = $this->mockEditability();
		$this->isExternallyRendered = false;

		$this->preferredLanguageLookup = $this->createMock( UserPreferredContentLanguagesLookup::class );
		$this->preferredLanguageLookup->method( 'getLanguages' )
			->willReturn( [ [ $this->uiLanguageCode, 'de', 'es', 'ru' ] ] );

		$this->entityViewChecker = $this->createMock( OutputPageEntityViewChecker::class );
		$this->entityViewChecker->method( 'hasEntityView' )
			->willReturn( true );
	}

	/**
	 * @return OutputPage
	 */
	private function newOutputPage() {
		$mockContext = $this->createMock( RequestContext::class );
		$mockContext->method( 'getLanguage' )
			->willReturn( Language::factory( $this->uiLanguageCode ) );
		$mockContext->method( 'getUser' )->willReturn( new User() );
		$mockContext->method( 'getRequest' )->willReturn( new FauxRequest() );
		$mockContext->method( 'getConfig' )->willReturn( RequestContext::getMain()->getConfig() );
		$outputPage = new OutputPage( $mockContext );
		$outputPage->setTitle( $this->createMock( Title::class ) );
		$outputPage->setArticleFlag( true );

		return $outputPage;
	}

	private function getHookHandler() {
		return new OutputPageBeforeHTMLHookHandler(
			new NullHttpRequestFactory(),
			new NullStatsdDataFactory(),
			WikibaseRepo::getSettings(),
			TemplateFactory::getDefaultInstance(),
			$this->userLanguageLookup,
			$this->contentLanguages,
			$this->entityRevisionLookup,
			$this->languageNameLookup,
			$this->outputPageEntityIdReader,
			$this->entityFactory,
			'',
			$this->editability,
			$this->isExternallyRendered,
			$this->preferredLanguageLookup,
			$this->entityViewChecker,
			WikibaseRepo::getLanguageFallbackChainFactory(),
			$this->getServiceContainer()->getUserOptionsLookup()
		);
	}

	/**
	 * Integration test mostly testing that things don't fatal/ throw.
	 */
	public function testOutputPageBeforeHTMLHookHandler() {
		$out = $this->newOutputPage();

		$this->userLanguageLookup = $this->getUserLanguageLookupReturnsSpecifiedLangs();
		$this->languageNameLookup->expects( $this->never() )
			->method( 'getName' );

		$this->outputPageEntityIdReader = $this->getOutputPageEntityIdReaderReturningEntity( $this->itemId );
		$this->entityRevisionLookup = $this->getEntityRevisionLookupReturningEntity( $this->itemId );

		$this->preferredLanguageLookup->expects( $this->once() )
			->method( 'getLanguages' )
			->with( $this->uiLanguageCode, $out->getUser() )
			->willReturn( [ [ $this->uiLanguageCode, 'de', 'es', 'ru' ] ] );

		$outputPageBeforeHTMLHookHandler = $this->getHookHandler();

		$html = '';
		$out->setTitle( Title::makeTitle( 0, 'OutputPageBeforeHTMLHookHandlerTest' ) );
		$out->setProperty(
			'wikibase-view-chunks',
			[ '$1' => [ 'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class' ] ]
		);
		$out->setArticleFlag( true );

		$outputPageBeforeHTMLHookHandler->onOutputPageBeforeHTML( $out, $html );

		// Verify the wbUserSpecifiedLanguages JS variable
		$jsConfigVars = $out->getJsConfigVars();
		$wbUserSpecifiedLanguages = $jsConfigVars['wbUserSpecifiedLanguages'];

		$this->assertSame( [ 'es', 'ru' ], $wbUserSpecifiedLanguages );
	}

	public function testOutputPageBeforeHTMLHookHandlerShouldNotWorkOnNonEntityViewPages() {
		$out = $this->newOutputPage();
		$this->userLanguageLookup->expects( $this->never() )
			->method( 'getUserSpecifiedLanguages' );
		$this->userLanguageLookup->expects( $this->never() )
			->method( 'getAllUserLanguages' );

		$html = '';
		$this->entityViewChecker = $this->createMock( OutputPageEntityViewChecker::class );
		$this->entityViewChecker->expects( $this->once() )
			->method( 'hasEntityView' )
			->willReturn( false );

		$this->getHookHandler()->onOutputPageBeforeHTML( $out, $html );

		// Verify the wbUserSpecifiedLanguages JS variable
		$jsConfigVars = $out->getJsConfigVars();
		$this->assertFalse( isset( $jsConfigVars['wbUserSpecifiedLanguages'] ) );
	}

	public function testGivenDeletedRevision_hookHandlerDoesNotFail() {
		$this->outputPageEntityIdReader->expects( $this->once() )
			->method( 'getEntityIdFromOutputPage' )
			->willReturn( null );

		$this->userLanguageLookup = $this->getUserLanguageLookupReturnsSpecifiedLangs();

		$out = $this->newOutputPage();
		$out->setProperty( 'wikibase-view-chunks', [ '$1' => [ 'termbox' ] ] );
		$out->setArticleFlag( true );

		$html = '$1';
		$this->getHookHandler()->onOutputPageBeforeHTML( $out, $html );
		$this->assertSame( '', $html );
	}

	public function testGivenExternallyRenderedMarkup_usesRespectivePlaceholderExpander() {
		$this->entityFactory->expects( $this->once() )
			->method( 'newEmpty' )
			->willReturn( new Item( $this->itemId ) );
		$this->userLanguageLookup->expects( $this->once() )
			->method( 'getUserSpecifiedLanguages' )
			->willReturn( [] );
		$this->isExternallyRendered = true;

		$this->outputPageEntityIdReader = $this->getOutputPageEntityIdReaderReturningEntity( $this->itemId );

		$expectedHtml = '<div>termbox</div>';
		$placeholder = '$1';

		$out = $this->newOutputPage();
		$out->setProperty( TermboxView::TERMBOX_MARKUP, $expectedHtml );
		$out->setProperty( 'wikibase-view-chunks', [ $placeholder => [ TermboxView::TERMBOX_PLACEHOLDER ] ] );
		$out->setArticleFlag( true );

		$html = $placeholder;
		$this->getHookHandler()->onOutputPageBeforeHTML( $out, $html );

		$this->assertSame( $expectedHtml, $html );
	}

	private function newUserLanguageLookup() {
		$userLanguageLookup = $this->createMock( UserLanguageLookup::class );
		$userLanguageLookup->method( 'getUserSpecifiedLanguages' )
			->willReturn( [] );
		$userLanguageLookup->method( 'getAllUserLanguages' )
			->willReturn( [] );
		return $userLanguageLookup;
	}

	/**
	 * @param $itemId
	 * @return \PHPUnit\Framework\MockObject\MockObject
	 */
	private function getOutputPageEntityIdReaderReturningEntity( $itemId ) {
		$outputPageEntityIdReader = $this->createMock( OutputPageEntityIdReader::class );
		$outputPageEntityIdReader->expects( $this->once() )
			->method( 'getEntityIdFromOutputPage' )
			->willReturn( $itemId );

		return $outputPageEntityIdReader;
	}

	/**
	 * @param $itemId
	 * @return MockObject
	 */
	private function getEntityRevisionLookupReturningEntity( $itemId ): EntityRevisionLookup {
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->willReturn( new EntityRevision( new Item( $itemId ) ) );
		return $entityRevisionLookup;
	}

	public function testGivenPageIsEditable_keepsEditButtonsAndRemovesSpecialMarkup() {
		$contentBetweenEditLinks = 'hello';
		$editLink1 = 'edit link 1';
		$editLink2 = 'edit link 2';
		$html = "<wb:sectionedit>$editLink1</wb:sectionedit> $contentBetweenEditLinks <wb:sectionedit>$editLink2</wb:sectionedit>";
		$out = $this->newOutputPage();
		$this->userLanguageLookup = $this->newUserLanguageLookup();

		$this->getHookHandler()->onOutputPageBeforeHTML( $out, $html );

		$this->assertEquals( "$editLink1 $contentBetweenEditLinks $editLink2", $html );
	}

	public function testGivenPageIsNotEditable_removesEditButtonsAndSpecialMarkup() {
		$contentBetweenEditLinks = 'hello';
		$html = "<wb:sectionedit>edit link 1</wb:sectionedit>$contentBetweenEditLinks<wb:sectionedit>edit link 2</wb:sectionedit>";
		$out = $this->newOutputPage();
		$this->userLanguageLookup = $this->newUserLanguageLookup();
		$this->editability = $this->mockEditabilityDismissive();

		$this->getHookHandler()->onOutputPageBeforeHTML( $out, $html );

		$this->assertSame( $contentBetweenEditLinks, $html );
	}

	private function mockEditability( $permissive = true ) {
		$editability = $this->createMock( OutputPageEditability::class );
		$editability->method( 'validate' )->willReturn( $permissive );
		return $editability;
	}

	private function mockEditabilityDismissive() {
		return $this->mockEditability( false );
	}

	private function getUserLanguageLookupReturnsSpecifiedLangs(): UserLanguageLookup {
		$userLanguageLookup = $this->createMock( UserLanguageLookup::class );
		$userLanguageLookup->expects( $this->once() )
			->method( 'getUserSpecifiedLanguages' )
			->willReturn( [ 'de', 'es', 'ru' ] );
		return $userLanguageLookup;
	}

}
