<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace MediaWiki\CheckUser;

use ActorMigrationBase;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\ActorStoreFactory;

class CheckUserActorMigration extends ActorMigrationBase {
	/**
	 * Field information
	 * @see ActorMigrationBase::getFieldInfo()
	 */
	public const FIELD_INFOS = [
		'cuc_user' => [],
	];

	/**
	 * @return self
	 */
	public static function newMigration() {
		return MediaWikiServices::getInstance()->get( 'CheckUserActorMigration' );
	}

	/**
	 * @param int $stage
	 * @param ActorStoreFactory $actorStoreFactory
	 */
	public function __construct(
		$stage,
		ActorStoreFactory $actorStoreFactory
	) {
		parent::__construct(
			self::FIELD_INFOS,
			$stage,
			$actorStoreFactory
		);
	}
}
