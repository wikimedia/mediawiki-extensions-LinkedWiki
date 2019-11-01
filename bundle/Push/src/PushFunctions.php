<?php
/**
 * Static class with utility methods for the Push extension.
 *
 * @since 0.2
 *
 * @file Push_Functions.php
 * @ingroup Push
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Karima Rafes < karima.rafes@gmail.com >
 */
final class PushFunctions {

	/**
	 * Returns the latest revision.
	 * Has support for the ApprovedRevs extension, and will
	 * return the latest approved revision where appropriate.
	 *
	 * @since 0.2
	 *
	 * @param Title $title
	 *
	 * @return int
	 */
	public static function getRevisionToPush( Title $title ) {
		if ( defined( 'APPROVED_REVS_VERSION' ) ) {
			$revId = ApprovedRevs::getApprovedRevID( $title );
			return $revId ?: $title->getLatestRevID();
		}

		return $title->getLatestRevID();
	}

	/**
	 * Expand a list of pages to include templates used in those pages.
	 *
	 * @since 0.4
	 *
	 * @param array $inputPages list of titles to look up
	 * @param array $pageSet associative array indexed by titles for output
	 *
	 * @return array associative array index by titles
	 */
	public static function getTemplates( $inputPages, $pageSet ) {
		return self::getLinks( $inputPages, $pageSet,
				'templatelinks',
				[ 'tl_namespace AS namespace', 'tl_title AS title' ],
				[ 'page_id=tl_from' ]
			);
		// select distinct tl_namespace, page_title from templatelinks, page where page_id=tl_from
	}

	/**
	 * Expand a list of pages to include subpages used in those pages.
	 *
	 * @since 1.4.0
	 *
	 * @param array $inputPages list of titles to look up
	 * @param array $pageSet associative array indexed by titles for output
	 *
	 * @return array associative array index by titles
	 */
	public static function getSubpages( $inputPages, $pageSet ) {
		$dbr = wfGetDB( DB_REPLICA );

		foreach ( $inputPages as $page ) {
			$title = Title::newFromText( $page );

			if ( $title ) {
				$pageSet[$title->getPrefixedText()] = true;

				// extract of extension SubPageList of Jeroen De Dauw < jeroendedauw@gmail.com >
				$dbr = wfGetDB( DB_REPLICA );
				$result = TitleArray::newFromResult(
					$dbr->select( 'page',
						// [ 'tl_namespace AS namespace', 'tl_title AS title' ],
						[ 'page_id', 'page_namespace', 'page_title', 'page_is_redirect' ],
						[
							'page_namespace' => $title->getNamespace(),
							'page_title' . $dbr->buildLike( $title->getDBkey() . '/', $dbr->anyString() )
						],
						__METHOD__
					)
				);
				foreach ( $result as $row ) {
					$pageSet[$row->getPrefixedText()] = true;
				}
			}
		}

		// print_r($pageSet);
		return $pageSet;
	}

	/**
	 * Expand a list of pages to include items used in those pages.
	 *
	 * @since 0.4
	 *
	 * @param array $inputPages
	 * @param array $pageSet
	 * @param string $table
	 * @param array $fields
	 * @param array $join
	 * @return array
	 */
	protected static function getLinks( $inputPages, $pageSet, $table, $fields, $join ) {
		$dbr = wfGetDB( DB_REPLICA );

		foreach ( $inputPages as $page ) {
			$title = Title::newFromText( $page );

			if ( $title ) {
				$pageSet[$title->getPrefixedText()] = true;
				/// @todo Fixme: May or may not be more efficient to batch these
				///        by namespace when given multiple input pages.
				$result = $dbr->select(
					[ 'page', $table ],
					$fields,
					array_merge(
						$join,
						[
							'page_namespace' => $title->getNamespace(),
							'page_title' => $title->getDBkey()
						]
					),
					__METHOD__
				);

				foreach ( $result as $row ) {
					$template = Title::makeTitle( $row->namespace, $row->title );
					$pageSet[$template->getPrefixedText()] = true;
				}
			}
		}
		return $pageSet;
	}

	/**
	 * Returns the names of the images embedded in a set of pages.
	 *
	 * @param array $inputPages
	 *
	 * @return array
	 */
	public static function getImages( array $inputPages ) {
		$images = [];

		$requestData = [
			'action' => 'query',
			'format' => 'json',
			'prop' => 'images',
			'titles' => implode( '|', $inputPages ),
			'imlimit' => 500
		];

		$api = new ApiMain( new FauxRequest( $requestData, true ), true );
		$api->execute();
		if ( defined( 'ApiResult::META_CONTENT' ) ) {
			$response = $api->getResult()->getResultData( null, [ 'Strip' => 'all' ] );
		} else {
			$response = $api->getResultData();
		}

		if (
			is_array( $response )
			&& array_key_exists( 'query', $response )
			&& array_key_exists( 'pages', $response['query'] )
		) {
			foreach ( $response['query']['pages'] as $page ) {
				if ( array_key_exists( 'images', $page ) ) {
					foreach ( $page['images'] as $image ) {
						$title = Title::newFromText( $image['title'], NS_FILE );

						if ( !is_null( $title ) && $title->getNamespace() == NS_FILE && $title->exists() ) {
							$images[] = $image['title'];
						}
					}
				}
			}
		}

		return array_unique( $images );
	}

	/**
	 * Function to change the keys of $egPushLoginUsers and $egPushLoginPasswords
	 * from target url to target name using the $egPushTargets array.
	 *
	 * @since 0.5
	 *
	 * @param array &$arr
	 * @param string $id Some string to identify the array and keep track of it having been flipped.
	 */
	public static function flipKeys( array &$arr, $id ) {
		static $handledArrays = [];

		if ( !in_array( $id, $handledArrays ) ) {
			$handledArrays[] = $id;

			// global $egPushTargets;
			$config = ConfigFactory::getDefaultInstance()->makeConfig( 'egPush' );
			$egPushTargets = [];
			if ( !$config->has( "Targets" ) ) {
				// throw new MWException( "$egPushPushTargets is not precised in the bundle/Push/extension.json
				// of the LinkedWiki extension." );
			} else {
				$egPushTargets = $config->get( "Targets" );
			}

			$flipped = [];

			foreach ( $arr as $key => $value ) {
				if ( array_key_exists( $key, $egPushTargets ) ) {
					$flipped[$egPushTargets[$key]] = $value;
				}
			}

			$arr = $flipped;
		}
	}
}
