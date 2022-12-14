<?php

namespace PageImages\Hooks;

use MediaWiki\Page\PageIdentity;
use MediaWiki\Search\Entity\SearchResultThumbnail;
use MediaWiki\Search\Hook\SearchResultProvideThumbnailHook;
use PageImages\PageImages;
use PageProps;
use RepoGroup;
use Title;

class SearchResultProvideThumbnailHookHandler implements SearchResultProvideThumbnailHook {

	public const THUMBNAIL_SIZE = 200;

	/** @var PageProps */
	private $pageProps;

	/** @var RepoGroup */
	private $repoGroup;

	/**
	 * @param PageProps $pageProps
	 * @param RepoGroup $repoGroup
	 */
	public function __construct( PageProps $pageProps, RepoGroup $repoGroup ) {
		$this->pageProps = $pageProps;
		$this->repoGroup = $repoGroup;
	}

	/**
	 * Returns a list fileNames associated with given pages
	 *
	 * @param array $pagesByPageId key-value array where key is pageID and value is Title
	 * @return array
	 */
	private function getFileNamesForPageTitles( $pagesByPageId ): array {
		$propValues = $this->pageProps->getProperties(
			$pagesByPageId,
			PageImages::getPropNames( PageImages::LICENSE_ANY )
		);
		$fileNames = array_map( static function ( $prop ) {
			return $prop[ PageImages::getPropName( false ) ]
				?? $prop[ PageImages::getPropName( true ) ]
				?? null;
		}, $propValues );

		return array_filter( $fileNames, static function ( $fileName ) {
			return $fileName != null;
		} );
	}

	/**
	 * Returns a list fileNames for with given LinkTarget, where title is NS_FILE
	 *
	 * @param array $linkFileTargetsByPageId key-value array of where key
	 *   is pageId, value is LinkTarget
	 * @return array
	 */
	private function getFileNamesForFileTitles( $linkFileTargetsByPageId ): array {
		return array_map( static function ( $linkFileTarget ) {
			return $linkFileTarget->getDBkey();
		}, $linkFileTargetsByPageId );
	}

	/**
	 * Returns thumbnails for given list
	 *
	 * @param array $titlesByPageId a key value array where key is pageId and value is Title
	 * @param int $size size of thumbnail height and width in points
	 * @return SearchResultThumbnail[]
	 */
	private function getThumbnails( array $titlesByPageId, int $size ): array {
		$pagesByPageId = array_filter( $titlesByPageId, static function ( $title ) {
			return !$title->inNamespace( NS_FILE );
		} );
		$titleFilesByPageId = array_filter( $titlesByPageId, static function ( $title ) {
			return $title->inNamespace( NS_FILE );
		} );

		$files = $this->getFileNamesForPageTitles( $pagesByPageId )
			+ $this->getFileNamesForFileTitles( $titleFilesByPageId );

		$res = [];
		foreach ( $files as $pageId => $fileName ) {
			$file = $this->repoGroup->findFile( $fileName );
			if ( !$file ) {
				continue;
			}
			$thumb = $file->transform( [ 'width' => $size , 'height' => $size ] );
			if ( !$thumb || $thumb->isError() ) {
				continue;
			}

			$localPath = $thumb->getLocalCopyPath();
			$thumbSize = $localPath && file_exists( $localPath ) ? filesize( $localPath ) : null;

			$res[$pageId] = new SearchResultThumbnail(
				$thumb->getFile()->getMimeType(),
				$thumbSize,
				$thumb->getWidth(),
				$thumb->getHeight(),
				null,
				wfExpandUrl( $thumb->getUrl(), PROTO_RELATIVE ),
				$fileName
			);
		}

		return $res;
	}

	/**
	 * @param array $pageIdentities array that contain $pageId => SearchResultPageIdentity.
	 * @param array &$results Placeholder for result. $pageId => SearchResultThumbnail
	 * @param int|null $size size of thumbnail height and width in points
	 */
	public function onSearchResultProvideThumbnail( array $pageIdentities, &$results, int $size = null ): void {
		$pageIdTitles = array_map( static function ( PageIdentity $identity ) {
			return Title::makeTitle( $identity->getNamespace(), $identity->getDBkey() );
		}, $pageIdentities );

		$data = $this->getThumbnails( $pageIdTitles, $size ?? self::THUMBNAIL_SIZE );
		foreach ( $data as $pageId => $thumbnail ) {
			$results[ $pageId ] = $thumbnail;
		}
	}
}
