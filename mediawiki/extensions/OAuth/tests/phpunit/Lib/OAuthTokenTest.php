<?php

namespace MediaWiki\Extension\OAuth\Tests\Lib;

use MediaWiki\Extension\OAuth\Lib\OAuthToken;

/**
 * The MIT License
 *
 * Copyright (c) 2007 Andy Smith
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files ( the "Software" ), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * @group OAuth
 */
class OAuthTokenTest extends \PHPUnit\Framework\TestCase {
	public function testSerialize() {
		$token = new OAuthToken('token', 'secret');
		$this->assertEquals('oauth_token=token&oauth_token_secret=secret', $token->to_string());

		$token = new OAuthToken('token&', 'secret%');
		$this->assertEquals('oauth_token=token%26&oauth_token_secret=secret%25', $token->to_string());
	}
	public function testConvertToString() {
		$token = new OAuthToken('token', 'secret');
		$this->assertEquals('oauth_token=token&oauth_token_secret=secret', (string) $token);

		$token = new OAuthToken('token&', 'secret%');
		$this->assertEquals('oauth_token=token%26&oauth_token_secret=secret%25', (string) $token);
	}
}
