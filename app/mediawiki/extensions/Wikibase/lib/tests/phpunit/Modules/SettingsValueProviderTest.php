<?php

namespace Wikibase\Lib\Tests\Modules;

use Prophecy\Prophecy\ObjectProphecy;
use Wikibase\Lib\Modules\SettingsValueProvider;
use Wikibase\Lib\SettingsArray;

/**
 * @covers \Wikibase\Lib\Modules\SettingsValueProvider
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SettingsValueProviderTest extends \PHPUnit\Framework\TestCase {

	public function testGetKeyReturnsJSSettingName() {
		$settingsValueProvider = new SettingsValueProvider(
			$this->createMock( SettingsArray::class ),
			$jsSettingName = 'jsName',
			'does not matter'
		);

		self::assertEquals( $jsSettingName, $settingsValueProvider->getKey() );
	}

	public function testGetValueReturnsSettingWithGivenName() {

		/** @var SettingsArray|ObjectProphecy $settings */
		$settings = $this->prophesize( SettingsArray::class );

		$settings->getSetting( 'setting_name' )->willReturn( 'setting value' );

		$settingsValueProvider = new SettingsValueProvider(
			$settings->reveal(),
			'does not matter',
			'setting_name'
		);

		self::assertEquals( 'setting value', $settingsValueProvider->getValue() );
	}

}
