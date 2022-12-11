<?php

namespace Wikibase\Repo\Tests\Specials;

use Wikibase\Repo\CopyrightMessageBuilder;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;
use Wikibase\Repo\Specials\SpecialSetLabel;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Specials\SpecialSetLabel
 * @covers \Wikibase\Repo\Specials\SpecialModifyTerm
 * @covers \Wikibase\Repo\Specials\SpecialModifyEntity
 * @covers \Wikibase\Repo\Specials\SpecialWikibaseRepoPage
 * @covers \Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialSetLabelTest extends SpecialModifyTermTestCase {

	protected function newSpecialPage() {
		$copyrightView = new SpecialPageCopyrightView( new CopyrightMessageBuilder(), '', '' );

		return new SpecialSetLabel(
			[],
			WikibaseRepo::getChangeOpFactoryProvider(),
			$copyrightView,
			WikibaseRepo::getSummaryFormatter(),
			WikibaseRepo::getEntityTitleLookup(),
			WikibaseRepo::getEditEntityFactory(),
			WikibaseRepo::getEntityPermissionChecker(),
			WikibaseRepo::getTermsLanguages()
		);
	}

}
