<?php

namespace MediaWiki\CheckUser\Tests\Integration\Investigate\Services;

use MediaWiki\CheckUser\Investigate\Services\CompareService;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\MediaWikiServices;
use MediaWiki\Tests\Unit\Libs\Rdbms\AddQuoterMock;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserIdentityLookup;
use MediaWikiIntegrationTestCase;
use Wikimedia\IPUtils;
use Wikimedia\Rdbms\Database;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\Platform\MySQLPlatform;
use Wikimedia\TestingAccessWrapper;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * @group CheckUser
 * @group Database
 * @covers \MediaWiki\CheckUser\Investigate\Services\CompareService
 */
class CompareServiceTest extends MediaWikiIntegrationTestCase {

	/** @var CompareService */
	private $service;

	/**
	 * Lazy load CompareService
	 *
	 * @return CompareService
	 */
	private function getCompareService(): CompareService {
		if ( !$this->service ) {
			$this->service = MediaWikiServices::getInstance()->get( 'CheckUserCompareService' );
		}

		return $this->service;
	}

	/**
	 * Sanity check for the subqueries built by getQueryInfo. Checks for the presence
	 * of valid targets and the presence of the expected per-target limit. Whitespace
	 * is not always predictable so look for the bare minimum in the SQL string.
	 *
	 * Invalid targets are tested in ComparePagerTest::testDoQuery.
	 *
	 * @dataProvider provideGetQueryInfo
	 */
	public function testGetQueryInfo( $options, $expected ) {
		$serviceOptions = $this->createMock( ServiceOptions::class );
		$serviceOptions->method( 'get' )
			->willReturn( $options['limit'] );

		$db = $this->getMockBuilder( Database::class )
			->onlyMethods( [
				'dbSchema',
				'tablePrefix',
			] )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
		$db->method( 'strencode' )
			->will( $this->returnArgument( 0 ) );
		$db->method( 'dbSchema' )
			->willReturn( '' );
		$db->method( 'tablePrefix' )
			->willReturn( '' );
		$wdb = TestingAccessWrapper::newFromObject( $db );
		$wdb->platform = new MySQLPlatform( new AddQuoterMock() );

		$loadBalancer = $this->createMock( ILoadBalancer::class );
		$loadBalancer->method( 'getConnection' )
			->willReturn( $db );

		$user = $this->createMock( UserIdentity::class );
		$user->method( 'getId' )
			->willReturn( 11111 );

		$user2 = $this->createMock( UserIdentity::class );
		$user2->method( 'getId' )
			->willReturn( 22222 );

		$userIdentityLookup = $this->createMock( UserIdentityLookup::class );
		$userIdentityLookup->method( 'getUserIdentityByName' )
			->willReturnMap(
				[
					[ 'User1', 0, $user, ],
					[ 'User2', 0, $user2, ],
				]
			);

		$compareService = new CompareService(
			$serviceOptions,
			$loadBalancer,
			$userIdentityLookup
		);

		$queryInfo = $compareService->getQueryInfo(
			$options['targets'],
			$options['excludeTargets'],
			$options['start']
		);

		foreach ( $expected['targets'] as $target ) {
			$this->assertStringContainsString( $target, $queryInfo['tables']['a'] );
		}

		foreach ( $expected['excludeTargets'] as $excludeTarget ) {
			$this->assertStringContainsString( $excludeTarget, $queryInfo['tables']['a'] );
		}

		$this->assertStringContainsString( 'LIMIT ' . $expected['limit'], $queryInfo['tables']['a'] );

		[ 'start' => $start ] = $expected;
		if ( $start === '' ) {
			$this->assertStringNotContainsString( 'cuc_timestamp >=', $queryInfo['tables']['a'] );
		} else {
			$this->assertStringContainsString( "cuc_timestamp >= '$start'", $queryInfo['tables']['a'] );
		}
	}

	public function provideGetQueryInfo() {
		return [
			'Valid username, excluded IP' => [
				[
					'targets' => [ 'User1' ],
					'excludeTargets' => [ '0:0:0:0:0:0:0:1' ],
					'limit' => 100000,
					'start' => ''
				],
				[
					'targets' => [ '11111' ],
					'excludeTargets' => [ 'v6-00000000000000000000000000000001' ],
					'limit' => '100000',
					'start' => ''
				],
			],
			'Valid username, excluded IP, with start' => [
				[
					'targets' => [ 'User1' ],
					'excludeTargets' => [ '0:0:0:0:0:0:0:1' ],
					'limit' => 10000,
					'start' => '111'
				],
				[
					'targets' => [ '11111' ],
					'excludeTargets' => [ 'v6-00000000000000000000000000000001' ],
					'limit' => '10000',
					'start' => '111'
				],
			],
			'Single valid IP, excluded username' => [
				[
					'targets' => [ '0:0:0:0:0:0:0:1' ],
					'excludeTargets' => [ 'User1' ],
					'limit' => 100000,
					'start' => ''
				],
				[
					'targets' => [ 'v6-00000000000000000000000000000001' ],
					'excludeTargets' => [ '11111' ],
					'limit' => '100000',
					'start' => ''
				],
			],
			'Valid username and IP, excluded username and IP' => [
				[
					'targets' => [ 'User1', '1.2.3.4' ],
					'excludeTargets' => [ 'User2', '1.2.3.5' ],
					'limit' => 100,
					'start' => ''
				],
				[
					'targets' => [ '11111', '01020304' ],
					'excludeTargets' => [ '22222', '01020305' ],
					'limit' => '50',
					'start' => ''
				],
			],
			'Two valid IPs' => [
				[
					'targets' => [ '0:0:0:0:0:0:0:1', '1.2.3.4' ],
					'excludeTargets' => [],
					'limit' => 100000,
					'start' => ''
				],
				[
					'targets' => [
						'v6-00000000000000000000000000000001',
						'01020304'
					],
					'excludeTargets' => [],
					'limit' => '50000',
					'start' => ''
				],
			],
			'Valid IP addresses and IP range' => [
				[
					'targets' => [
						'0:0:0:0:0:0:0:1',
						'1.2.3.4',
						'1.2.3.4/16',
					],
					'excludeTargets' => [],
					'limit' => 100000,
					'start' => ''
				],
				[
					'targets' => [
						'v6-00000000000000000000000000000001',
						'01020304',
						'01020000',
						'0102FFFF',
					],
					'excludeTargets' => [],
					'limit' => '33333',
					'start' => ''
				],
			],
		];
	}

	public function testGetQueryInfoNoTargets() {
		$this->expectException( \LogicException::class );
		$db = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$loadBalancer = $this->createMock( ILoadBalancer::class );
		$loadBalancer->method( 'getConnection' )
			->willReturn( $db );

		$compareService = new CompareService(
			$this->createMock( ServiceOptions::class ),
			$loadBalancer,
			$this->createMock( UserIdentityLookup::class )
		);

		$compareService->getQueryInfo( [], [], '' );
	}

	/**
	 * @dataProvider provideGetQueryInfoForSingleTarget
	 */
	public function testGetQueryInfoForSingleTarget( $options, $expected ) {
		$db = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
		$db->method( 'strencode' )
			->will( $this->returnArgument( 0 ) );

		$loadBalancer = $this->createMock( ILoadBalancer::class );
		$loadBalancer->method( 'getConnection' )
			->willReturn( $db );

		$compareServcice = new CompareService(
			$this->createMock( ServiceOptions::class ),
			$loadBalancer,
			$this->createMock( UserIdentityLookup::class )
		);

		$info = $compareServcice->getQueryInfoForSingleTarget(
			'1.2.3.4',
			[],
			'',
			$options['limitPerTarget'],
			$options['limitCheck']
		);

		$this->assertSame( $expected['orderBy'], $info['options']['ORDER BY'] );
		$this->assertSame( $expected['limit'], $info['options']['LIMIT'] );
		$this->assertSame( $expected['offset'], $info['options']['OFFSET'] );
	}

	public function provideGetQueryInfoForSingleTarget() {
		$limitPerTarget = 100;
		return [
			'Main investigation' => [
				[
					'limitPerTarget' => $limitPerTarget,
					'limitCheck' => false,
				],
				[
					'orderBy' => 'cuc_timestamp DESC',
					'offset' => null,
					'limit' => $limitPerTarget
				]
			],
			'Limit check' => [
				[
					'limitPerTarget' => $limitPerTarget,
					'limitCheck' => true,
				],
				[
					'orderBy' => null,
					'offset' => $limitPerTarget,
					'limit' => 1
				]
			],
		];
	}

	/**
	 * @dataProvider provideTotalEditsFromIp
	 */
	public function testGetTotalEditsFromIp( $data, $expected ) {
		$result = $this->getCompareService()->getTotalEditsFromIp(
			$data['ip'], $data['excludeUser'] ?? null
		);

		$this->assertEquals( $expected, $result );
	}

	public function provideTotalEditsFromIp() {
		return [
			'IP address with multiple users' => [
				[
					'ip' => IPUtils::toHex( '1.2.3.5' )
				],
				3,
			],
			'IP address with multiple users, excluding a user' => [
				[
					'ip' => IPUtils::toHex( '1.2.3.4' ),
					'excludeUser' => 'User1'
				],
				4,
			],
		];
	}

	/**
	 * @dataProvider provideGetTargetsOverLimit
	 */
	public function testGetTargetsOverLimit( $data, $expected ) {
		if ( isset( $data['limit'] ) ) {
			$this->overrideConfigValue( 'CheckUserInvestigateMaximumRowCount', $data['limit'] );
		}

		$result = $this->getCompareService()->getTargetsOverLimit(
			$data['targets'] ?? [],
			$data['excludeTargets'] ?? [],
			$this->db->timestamp()
		);

		$this->assertEquals( $expected, $result );
	}

	public function provideGetTargetsOverLimit() {
		return [
			'Empty targets array' => [
				[],
				[],
			],
			'Targets are all within limits' => [
				[
					'targets' => [ '1.2.3.4', 'User1', '1.2.3.5' ],
					'limit' => 100,
				],
				[],
			],
			'One target is over limit' => [
				[
					'targets' => [ '1.2.3.4', 'User1', '1.2.3.5' ],
					'excludeTargets' => [ '1.2.3.5' ],
					'limit' => 4
				],
				[ '1.2.3.4' ],
			],
			'Two targets are over limit' => [
				[
					'targets' => [ '1.2.3.4', '1.2.3.5' ],
					'limit' => 1,
				],
				[ '1.2.3.4', '1.2.3.5' ],
			],
		];
	}

	public function addDBData() {
		$testData = [
			[
				'cuc_user'       => 0,
				'cuc_user_text'  => '1.2.3.4',
				'cuc_type'       => RC_NEW,
				'cuc_ip'         => '1.2.3.4',
				'cuc_ip_hex'     => IPUtils::toHex( '1.2.3.4' ),
				'cuc_agent'      => 'foo user agent',
			], [
				'cuc_user'       => 0,
				'cuc_user_text'  => '1.2.3.4',
				'cuc_type'       => RC_EDIT,
				'cuc_ip'         => '1.2.3.4',
				'cuc_ip_hex'     => IPUtils::toHex( '1.2.3.4' ),
				'cuc_agent'      => 'foo user agent',
			], [
				'cuc_user'       => 0,
				'cuc_user_text'  => '1.2.3.4',
				'cuc_type'       => RC_EDIT,
				'cuc_ip'         => '1.2.3.4',
				'cuc_ip_hex'     => IPUtils::toHex( '1.2.3.4' ),
				'cuc_agent'      => 'bar user agent',
			], [
				'cuc_user'       => 0,
				'cuc_user_text'  => '1.2.3.5',
				'cuc_type'       => RC_EDIT,
				'cuc_ip'         => '1.2.3.5',
				'cuc_ip_hex'     => IPUtils::toHex( '1.2.3.5' ),
				'cuc_agent'      => 'bar user agent',
			], [
				'cuc_user'       => 0,
				'cuc_user_text'  => '1.2.3.5',
				'cuc_type'       => RC_EDIT,
				'cuc_ip'         => '1.2.3.5',
				'cuc_ip_hex'     => IPUtils::toHex( '1.2.3.5' ),
				'cuc_agent'      => 'foo user agent',
			], [
				'cuc_user'       => 11111,
				'cuc_user_text'  => 'User1',
				'cuc_type'       => RC_EDIT,
				'cuc_ip'         => '1.2.3.4',
				'cuc_ip_hex'     => IPUtils::toHex( '1.2.3.4' ),
				'cuc_agent'      => 'foo user agent',
			], [
				'cuc_user'       => 22222,
				'cuc_user_text'  => 'User2',
				'cuc_type'       => RC_EDIT,
				'cuc_ip'         => '1.2.3.4',
				'cuc_ip_hex'     => IPUtils::toHex( '1.2.3.4' ),
				'cuc_agent'      => 'foo user agent',
			], [
				'cuc_user'       => 11111,
				'cuc_user_text'  => 'User1',
				'cuc_type'       => RC_EDIT,
				'cuc_ip'         => '1.2.3.5',
				'cuc_ip_hex'     => IPUtils::toHex( '1.2.3.5' ),
				'cuc_agent'      => 'foo user agent',
			],
		];

		// Pin time to avoid failure when next second starts - T317411
		ConvertibleTimestamp::setFakeTime( '20220904094043' );

		$commonData = [
			'cuc_namespace'  => NS_MAIN,
			'cuc_title'      => 'Foo_Page',
			'cuc_minor'      => 0,
			'cuc_page_id'    => 1,
			'cuc_timestamp'  => $this->db->timestamp(),
			'cuc_xff'        => 0,
			'cuc_xff_hex'    => null,
			'cuc_actiontext' => '',
			'cuc_comment'    => '',
			'cuc_this_oldid' => 0,
			'cuc_last_oldid' => 0,
		];

		foreach ( $testData as $row ) {
			$this->db->insert( 'cu_changes', $row + $commonData );
		}

		$this->tablesUsed[] = 'cu_changes';
	}
}
