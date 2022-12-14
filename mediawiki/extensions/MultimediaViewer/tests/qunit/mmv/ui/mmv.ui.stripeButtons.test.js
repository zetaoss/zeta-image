/*
 * This file is part of the MediaWiki extension MediaViewer.
 *
 * MediaViewer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * MediaViewer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MediaViewer.  If not, see <http://www.gnu.org/licenses/>.
 */

( function () {
	QUnit.module( 'mmv.ui.StripeButtons', QUnit.newMwEnvironment() );

	function createStripeButtons() {
		var $fixture = $( '#qunit-fixture' );
		return new mw.mmv.ui.StripeButtons( $fixture );
	}

	QUnit.test( 'Sense test, object creation and UI construction', function ( assert ) {
		var buttons,
			oldMwUserIsAnon = mw.user.isAnon;

		// first pretend we are anonymous
		mw.user.isAnon = function () { return true; };
		buttons = createStripeButtons();

		assert.true( buttons instanceof mw.mmv.ui.StripeButtons, 'UI element is created.' );
		assert.strictEqual( buttons.buttons.$descriptionPage.length, 1, 'File page button created for anon.' );

		// now pretend we are logged in
		mw.user.isAnon = function () { return false; };
		buttons = createStripeButtons();

		assert.strictEqual( buttons.buttons.$descriptionPage.length, 1, 'File page button created for logged in.' );

		mw.user.isAnon = oldMwUserIsAnon;
	} );

	QUnit.test( 'set()/empty() sense test:', function ( assert ) {
		var buttons = createStripeButtons(),
			fakeImageInfo = { descriptionUrl: '//commons.wikimedia.org/wiki/File:Foo.jpg' },
			fakeRepoInfo = { displayName: 'Wikimedia Commons', isCommons: function () { return true; } };

		buttons.set( fakeImageInfo, fakeRepoInfo );
		buttons.empty();

		assert.true( true, 'No error on set()/empty().' );
	} );

	QUnit.test( 'Description page button', function ( assert ) {
		var $qf = $( '#qunit-fixture' ),
			buttons = new mw.mmv.ui.StripeButtons( $qf ),
			$button = buttons.buttons.$descriptionPage,
			descriptionUrl = 'http://example.com/desc',
			descriptionUrl2 = 'http://example.com/different-desc',
			imageInfo = { descriptionUrl: descriptionUrl },
			repoInfo = { isCommons: function () { return false; } };

		buttons.setDescriptionPageButton( imageInfo, repoInfo );

		assert.strictEqual( $button.hasClass( 'mw-mmv-repo-button-commons' ), false, 'Button does not have commons class non-Commons files' );
		assert.strictEqual( $button.find( 'a' ).addBack().filter( 'a' ).attr( 'href' ), descriptionUrl, 'Description page link is correct' );

		repoInfo.isCommons = function () { return true; };
		buttons.setDescriptionPageButton( imageInfo, repoInfo );

		assert.strictEqual( $button.hasClass( 'mw-mmv-repo-button-commons' ), true, 'Button commons class for Commons files' );

		imageInfo.pageID = 1;
		imageInfo.title = { getUrl: function () { return descriptionUrl2; } };
		repoInfo.isLocal = false;
		buttons.setDescriptionPageButton( imageInfo, repoInfo );

		assert.strictEqual(
			$button.hasClass( 'mw-mmv-repo-button-commons' ), false,
			'Button does not have commons class for Commons files with local description page'
		);
		assert.strictEqual(
			$button.find( 'a' ).addBack().filter( 'a' ).attr( 'href' ), descriptionUrl2,
			'Description page link for Commons files with local description page is correct'
		);
	} );

}() );
