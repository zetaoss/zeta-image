<?php

namespace MediaWiki\CheckUser\Tests;

use FormOptions;
use MediaWiki\User\UserIdentity;
use MediaWikiIntegrationTestCase;
use Wikimedia\TestingAccessWrapper;

abstract class CheckUserPagerCommonTest extends MediaWikiIntegrationTestCase {

	/** @var string One of the SpecialCheckUser::SUBTYPE_... constants */
	protected $checkSubtype;

	/** @var UserIdentity the default UserIdentity to be used as the target in tests. */
	protected $defaultUserIdentity;

	/** @var string the default check type to be used in tests. */
	protected $defaultCheckType;

	/**
	 * Gets the default values for a row from the DB.
	 *
	 * @return array
	 */
	abstract protected function getDefaultRowFieldValues(): array;

	/**
	 * Set up the object for the pager that is being tested
	 * wrapped in a TestingAccessWrapper so that the tests
	 * can modify and access protected / private methods and
	 * properties.
	 *
	 * @param UserIdentity|null $userIdentity the target for the check
	 * @param string|null $checkType the check type (e.g. ipedits).
	 * @return TestingAccessWrapper
	 */
	protected function setUpObject( ?UserIdentity $userIdentity = null, ?string $checkType = null ) {
		$opts = new FormOptions();
		$opts->add( 'reason', '' );
		$opts->add( 'period', 0 );
		$opts->add( 'limit', '' );
		$opts->add( 'dir', '' );
		$opts->add( 'offset', '' );
		$specialCheckUser = TestingAccessWrapper::newFromObject(
			$this->getServiceContainer()->getSpecialPageFactory()->getPage( 'CheckUser' )
		);
		$specialCheckUser->opts = $opts;
		$object = $specialCheckUser->getPager(
			$this->checkSubtype,
			$userIdentity ?? $this->defaultUserIdentity,
			$checkType ?? $this->defaultCheckType
		);
		return TestingAccessWrapper::newFromObject( $object );
	}
}
