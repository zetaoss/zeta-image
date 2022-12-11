<?php

namespace MediaWiki\Extension\OAuth\Tests\Rest;

use MediaWiki\Extension\OAuth\Backend\Consumer;
use MediaWiki\Extension\OAuth\Backend\Utils;
use MediaWiki\Extension\OAuth\Tests\TestHandlerFactory;
use MediaWiki\Rest\Handler;
use MWRestrictions;
use User;

/**
 * @covers \MediaWiki\Extension\OAuth\Rest\Handler\ListClients
 * @group Database
 * @group OAuth
 */
class ListClientsEndpointTest extends EndpointTest {

	/**
	 * @throws \Exception
	 */
	public function setUp(): void {
		parent::setUp();
		$this->tablesUsed[] = 'oauth_registered_consumer';
	}

	/**
	 * @var array
	 */
	protected $consumerData = [
		'id' => null,
		'consumerKey' => null,
		'name' => 'lc_test_name',
		'userId' => null,
		'version' => '1',
		'callbackUrl' => 'https://test.com',
		'callbackIsPrefix' => null,
		'description' => 'test_description',
		'email' => 'test@test.com',
		'emailAuthenticated' => 1577836800,
		'oauthVersion' => 1,
		'developerAgreement' => 1,
		'ownerOnly' => false,
		'wiki' => '*',
		'grants' => '["test"]',
		'registration' => 1577836800,
		'secretKey' => 'sk111111111111111111111111111111',
		'rsaKey' => '',
		'restrictions' => '{"IPAddresses": ["127.0.0.1"]}',
		'stage' => 1,
		'stageTimestamp' => 1577836800,
		'deleted' => 0,
		'oauth2IsConfidential' => 1,
		'oauth2GrantTypes' => null,
	];

	public function testNeedsWriteAccess() {
		$this->assertFalse( $this->newHandler()->needsWriteAccess() );
	}

	/**
	 * @return array
	 */
	public function provideTestHandlerExecute() {
		return [
			'Non-empty result OAuth 1' => [
				[
					'method' => 'GET',
					'uri' => self::makeUri( '/oauth2/client' ),
					'queryParams' => [
						'oauth_version' => '1'
					]
				],
				[
					'statusCode' => 200,
					'reasonPhrase' => 'OK',
					'protocolVersion' => '1.1',
					'body' => '{"clients":[{"name":"lc_test_name","version":"1","callback_url":' .
						'"https://test.com","description":"test_description","stage":1,"oauth_version":1,' .
						'"registration_formatted":"00:00, 1 January 2020","scopes":["[\"test\"]"],' .
						'"client_key":"lc111111111111111111111111111111", "owner_only":false}],"total":1}',
				],
				function () {
					$user = User::createNew( 'ListClientsTestUser1' );
					$centralId = Utils::getCentralIdFromUserName( $user->getName() );
					$db = Utils::getCentralDB( DB_PRIMARY );

					$this->consumerData['userId'] = $centralId;
					$this->consumerData['consumerKey'] = 'lc111111111111111111111111111111';

					if ( isset( $this->consumerData['restrictions'] ) ) {
						$this->consumerData['restrictions'] =
							MWRestrictions::newFromJson( $this->consumerData['restrictions'] );
					}

					Consumer::newFromArray( $this->consumerData )->save( $db );

					return $user;
				}
			],
			'Non-empty result OAuth 2' => [
				[
					'method' => 'GET',
					'uri' => self::makeUri( '/oauth2/client' ),
					'queryParams' => []
				],
				[
					'statusCode' => 200,
					'reasonPhrase' => 'OK',
					'protocolVersion' => '1.1',
					'body' => '{"clients":[{"name":"lc_test_name","version":"1","callback_url":' .
						'"https://test.com","description":"test_description","stage":1,"oauth_version":2,' .
						'"registration_formatted":"00:00, 1 January 2020","allowed_grants":null,' .
						'"scopes":["[\"test\"]"],' .
						'"client_key":"lc222222222222222222222222222222", "owner_only":false}],"total":1}'
				],
				function () {
					$user = User::createNew( 'ListClientsTestUser2' );
					$centralId = Utils::getCentralIdFromUserName( $user->getName() );
					$db = Utils::getCentralDB( DB_PRIMARY );

					$this->consumerData['userId'] = $centralId;
					$this->consumerData['consumerKey'] = 'lc222222222222222222222222222222';
					$this->consumerData['oauthVersion'] = '2';

					if ( isset( $this->consumerData['restrictions'] ) ) {
						$this->consumerData['restrictions'] =
							MWRestrictions::newFromJson( $this->consumerData['restrictions'] );
					}

					Consumer::newFromArray( $this->consumerData )->save( $db );

					return $user;
				}
			],
			'Empty result' => [
				[
					'method' => 'GET',
					'uri' => self::makeUri( '/oauth2/client' ),
					'queryParams' => []
				],
				[
					'statusCode' => 200,
					'reasonPhrase' => 'OK',
					'protocolVersion' => '1.1',
					'body' => '{"clients":[],"total":0}'
				],
				function () {
					$user = User::createNew( 'ListClientsTestUser3' );
					$db = Utils::getCentralDB( DB_PRIMARY );

					/*
					 * Inserting client id for a different user than the one making the request.
					 * This proves filtering works.
					 */
					$this->consumerData['userId'] = 99999;
					$this->consumerData['consumerKey'] = 'lc333333333333333333333333333333';

					Consumer::newFromArray( $this->consumerData )->save( $db );

					return $user;
				}
			],
			'Nonexistent user' => [
				[
					'method' => 'GET',
					'uri' => self::makeUri( '/oauth2/client' ),
					'queryParams' => []
				],
				[
					'statusCode' => 404,
					'reasonPhrase' => 'Not Found',
					'protocolVersion' => '1.1',
					'body' => [
						'httpCode' => 404,
						'httpReason' => 'Not Found',
					]
				]
			],
		];
	}

	protected function newHandler(): Handler {
		return TestHandlerFactory::getListClients();
	}
}
