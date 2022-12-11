<?php

namespace Wikibase\Lib\Tests\Store\Sql;

use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Lib\Store\Sql\SqlChangeStore;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Lib\WikibaseSettings;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * @covers \Wikibase\Lib\Store\Sql\EntityChangeLookup
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class EntityChangeLookupTest extends MediaWikiIntegrationTestCase {

	use LocalRepoDbTestHelper;

	private function newEntityChangeLookup() {
		return new EntityChangeLookup(
			new EntityChangeFactory(
				new EntityDiffer(),
				new BasicEntityIdParser(),
				[ 'item' => EntityChange::class ]
			),
			new ItemIdParser(),
			$this->getRepoDomainDb()
		);
	}

	public function testGetRecordId() {
		$change = $this->createMock( EntityChange::class );
		$change->expects( $this->once() )
			->method( 'getId' )
			->willReturn( 42 );

		$changeLookup = $this->newEntityChangeLookup();

		$this->assertSame( 42, $changeLookup->getRecordId( $change ) );
	}

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		if ( !WikibaseSettings::isRepoEnabled() ) {
			self::markTestSkipped( "Skipping because WikibaseClient doesn't have a local wb_changes table." );
		}
	}

	public function loadChunkProvider() {
		[ $changeOne, $changeTwo, $changeThree ] = $this->getEntityChanges();

		return [
			'Get one change' => [
				[ $changeOne ],
				[ $changeThree, $changeTwo, $changeOne ],
				3,
				1
			],
			'Get two changes, with offset' => [
				[ $changeOne, $changeTwo ],
				[ $changeTwo, $changeTwo, $changeTwo ],
				3,
				2
			],
			'Ask for six changes (get two), with offset' => [
				[ $changeTwo, $changeThree ],
				[ $changeThree ],
				6,
				100
			]
		];
	}

	/**
	 * @dataProvider loadChunkProvider
	 */
	public function testLoadChunk( array $expected, array $changesToStore, $start, $size ) {
		$changeStore = new SqlChangeStore( $this->getRepoDomainDb() );
		foreach ( $changesToStore as $change ) {
			$change->setField( 'id', null ); // Null the id as we save the same changes multiple times
			$changeStore->saveChange( $change );
		}
		$start = $this->offsetStart( $start );

		$lookup = $this->newEntityChangeLookup();

		$changes = $lookup->loadChunk( $start, $size );

		$this->assertChangesEqual( $expected, $changes, $start );
	}

	/**
	 * @depends testLoadChunk
	 */
	public function testLoadByChangeIds() {
		$start = $this->offsetStart( 3 );

		$lookup = $this->newEntityChangeLookup();

		$changes = $lookup->loadByChangeIds( [ $start, $start + 1, $start + 4 ] );
		[ $changeOne, $changeTwo, $changeThree ] = $this->getEntityChanges();

		$this->assertChangesEqual(
			[
				$changeOne,
				$changeTwo,
				$changeThree,
			],
			$changes,
			$start
		);
	}

	public function testLoadChangesBefore(): void {
		$this->db->delete( 'wb_changes', '*', __METHOD__ );
		$changesToStore = $this->getEntityChanges();
		$changeStore = new SqlChangeStore( $this->getRepoDomainDb() );
		foreach ( $changesToStore as $change ) {
			$changeStore->saveChange( $change );
		}
		$lookup = $this->newEntityChangeLookup();

		$jan1st2013Timestamp = ConvertibleTimestamp::convert( TS_MW, 1356998400 );

		$changes = $lookup->loadChangesBefore( $jan1st2013Timestamp, 500, 0 );

		$this->assertChangesEqual( [ $changesToStore[0] ], $changes );
	}

	private function assertChangesEqual( array $expected, array $changes, $start = 0 ) {
		$this->assertCount( count( $expected ), $changes );

		$i = 0;
		foreach ( $changes as $change ) {
			$expectedFields = $expected[$i]->getFields();
			$actualFields = $change->getFields();

			$this->assertGreaterThanOrEqual( $start, $actualFields['id'] );
			unset( $expectedFields['id'] );
			unset( $actualFields['id'] );

			$this->assertEquals( $expectedFields, $actualFields );
			$i++;
		}
	}

	/**
	 * We (might) already have changes in wb_changes, thus we potentially need
	 * to offset $start.
	 *
	 * @param int $start
	 * @return int
	 */
	private function offsetStart( $start ) {
		$changeIdOffset = (int)$this->db->selectField(
			'wb_changes',
			'MIN( change_id )',
			// First change inserted by this test
			[ 'change_time' => $this->db->timestamp( '20141008161232' ) ],
			__METHOD__
		);

		if ( $changeIdOffset ) {
			$start = $start + $changeIdOffset - 1;
		}

		return $start;
	}

	private function getEntityChanges() {
		$changeOne = [
			'type' => 'wikibase-item~remove',
			'time' => '20121026200049',
			'object_id' => 'Q42',
			'revision_id' => '0',
			'user_id' => '0',
			'info' => '{"diff":{"type":"diff","isassoc":null,"operations":[]}}',
		];

		$changeTwo = [
			'type' => 'wikibase-item~remove',
			'time' => '20151008161232',
			'object_id' => 'Q4662',
			'revision_id' => '0',
			'user_id' => '0',
			'info' => '{"diff":{"type":"diff","isassoc":null,"operations":[]}}',
		];

		$changeThree = [
			'type' => 'wikibase-item~remove',
			'time' => '20141008161232',
			'object_id' => 'Q123',
			'revision_id' => '343',
			'user_id' => '34',
			'info' => '{"metadata":{"user_text":"BlackMagicIsEvil","bot":0,"page_id":2354,"rev_id":343,' .
				'"parent_id":897,"comment":"Fake data!"}}',
		];

		$changeOne = new EntityChange( $changeOne );
		$changeTwo = new EntityChange( $changeTwo );
		$changeThree = new EntityChange( $changeThree );

		return [ $changeOne, $changeTwo, $changeThree ];
	}

}
