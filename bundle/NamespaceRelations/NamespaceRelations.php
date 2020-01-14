<?php
 class NamespaceRelations {

	const SUBJECT_WEIGHT = 10;
	const TALK_WEIGHT = 20;
	const STARTING_WEIGHT = 20;
	const WEIGHT_INCREMENT = 10;

	 /**
	  * Inject tabs
	  *
	  * @param SkinTemplate	$skinTemplate parameter of hook SkinTemplateNavigation
	  * @param array &$navigation parameter of hook SkinTemplateNavigation
	  * @return bool
	  */
	public static function onSkinTemplateNavigation( $skinTemplate, &$navigation ) {
		$nsRelations = new NamespaceRelations();
		$nsRelations->injectTabs( $skinTemplate, $navigation['namespaces'] );
		return true;
	}

	public static function makeConfig() {
		return new GlobalVarConfig( 'ext-conf-namespacerelations' );
	}

	private $currentWeight = self::STARTING_WEIGHT;

	/**
	 * Processed $wgNamespaceRelations configuration
	 *
	 * @var array
	 */
	private $namespaces;

	/**
	 * References to $this->namespaces per target
	 *
	 * @var array
	 */
	private $namespacesToTarget;

	/**
	 * References to $this->namespaces per allowed namespace
	 *
	 * @var array
	 */
	private $namespacesToNamespace;

	/**
	 * Per-namespace array of patterns to match against the title
	 *
	 * @var array
	 */
	private $namespacesSubjectPattern;

	public function __construct() {
		// global $wgNamespaceRelations;
		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'ext-conf-namespacerelations' );
		$wgNamespaceRelations = [];
		if ( !$config->has( "NamespaceRelations" ) ) {
			// throw new MWException( "NamespaceRelations is not precised in the extension.json." );
		} else {
			$wgNamespaceRelations = $config->get( "NamespaceRelations" );
		}

		$this->namespaces = [];
		if ( !empty( $wgNamespaceRelations ) ) {
			foreach ( $wgNamespaceRelations as $key => $data ) {
				$this->setNamespace( $key, null,
					[
						 'message'    => 'nstab-extra-' . $key,
						 'namespace'  => $data['namespace'],
						 'target'     => $data['target'],
						 'inMainPage' => isset( $data['inMainPage'] ) ? $data['inMainPage'] : false,
						 'query'      => isset( $data['query'] ) ? $data['query'] : '',
						 'hideTalk'   => isset( $data['hideTalk'] ) ? $data['hideTalk'] : false
					] );
				if ( !isset( $data['weight'] ) ) {
					$this->setNamespace( $key, 'weight', $this->generateWeight() );
				} else {
					$this->setNamespace( $key, 'weight', $data['weight'] );
				}
				if ( isset( $data['customTarget'] ) ) {
					$this->namespacesSubjectPattern[$this->getNamespace(
						$key, 'target' )][] =
						'#^' . str_replace( '$1', '(.*)', $data['customTarget'] ) . '$#';
					$this->setNamespace( $key, 'customTarget', $data['customTarget'] );
				}

				$this->addToNamespace( $data['namespace'], $key );
				$this->addToTarget( $data['target'], $key );
			}
		}
	}

	/**
	 * @param SkinTemplate $skinTemplate
	 * @param array &$navigation
	 */
	public function injectTabs( $skinTemplate, &$navigation ) {
		$title = $skinTemplate->getRelevantTitle();
		$titleText = $title->getText();
		$subjectNS = $title->getSubjectPage()->getNamespace();
		$rootText = $this->getRootTitle( $titleText, $subjectNS );
		// the root title to link other tabs against
		$userCanRead = $title->quickUserCan( 'read', $skinTemplate->getUser() );

		/**
		 * * key (subject, talk, or custom key)
		 * ** Title $title title object
		 * ** array|string $messages list of messages or a message
		 * ** bool $isActive is active?
		 * ** string $query URL query
		 * ** bool $checkExists check if exists
		 * ** int $weight weight
		 * ** string $context context
		 */
		$tabs = [];

		if ( array_key_exists( $subjectNS, $this->namespacesToNamespace ) ) {
			// in Main/Talk NS
			// make a Subject tab
			$subjectOptions['checkExists'] = $userCanRead;
			$subjectOptions['title'] = Title::makeTitle( $subjectNS, $rootText );
			if ( $title->equals( $subjectOptions['title'] ) ) {
				$subjectOptions['isActive'] = true;
			}
			$tabs['subject'] = $this->makeSubjectTab(
				$subjectNS,
				$rootText,
				$subjectOptions
			);

			// make a Talk tab
			$talkOptions['checkExists'] = $userCanRead;
			$talkOptions['title'] = Title::makeTitle(
				MWNamespace::getTalk( $subjectNS ),
				$rootText
			);
			if ( $title->equals( $talkOptions['title'] ) ) {
				$talkOptions['isActive'] = true;
			}
			$tabs['talk'] = $this->makeTalkTab(
				MWNamespace::getTalk( $subjectNS ),
				$rootText,
				$talkOptions
			);
			unset( $talkOptions );

			foreach ( $this->namespacesToNamespace[$subjectNS] as $key ) {
				if ( $title->getSubjectPage()->isMainPage()
					&& !$this->getNamespace( $key, 'inMainPage' )
				) {
					continue;
				}
				if ( $this->getNamespace( $key, 'hideTalk' ) ) {
					// if inMainPage=false, then ignore hideTalk
					unset( $tabs['talk'] );
				}

				$tabOptions = [
					'title'       => $this->getCustomTargetTitle( $key, $rootText ),
					'messages'    => $this->getNamespace( $key, 'message' ),
					'query'       => $this->getKeyQuery(
						$key,
						$this->getCustomTargetTitle( $key, $rootText )
					),
					'checkExists' => $userCanRead,
					'weight'      => $this->getNamespace( $key, 'weight' )
				];
				if ( $title->equals( $tabOptions['title'] ) ) {
					$tabOptions['isActive'] = true;
				}
				$tabs[$key] = $this->makeTab(
					$this->getNamespace( $key, 'target' ),
					$this->getCustomTargetTitle( $key, $rootText, true ),
					$tabOptions
				);
				unset( $tabOptions );
			}
		} elseif ( array_key_exists( $subjectNS, $this->namespacesToTarget ) ) {
			// in additional NS
			// redefine the subject namespace to point to the real one
			$subjectNS = $this->getNamespace(
				$this->namespacesToTarget[$subjectNS],
				'namespace'
			);

			// make a Subject tab
			$subjectOptions['checkExists'] = $userCanRead;
			$subjectOptions['title'] = Title::makeTitle( $subjectNS, $rootText );
			if ( $title->equals( $subjectOptions['title'] ) ) {
				$subjectOptions['isActive'] = true;
			}
			$tabs['subject'] = $this->makeSubjectTab(
				$subjectNS,
				$rootText,
				$subjectOptions
			);

			// make a Talk tab
			$talkOptions['checkExists'] = $userCanRead;
			$talkOptions['title'] = Title::makeTitle(
				MWNamespace::getTalk( $subjectNS ),
				$rootText
			);
			if ( $title->equals( $talkOptions['title'] ) ) {
				$talkOptions['isActive'] = true;
			}
			$tabs['talk'] = $this->makeTalkTab(
				MWNamespace::getTalk( $subjectNS ),
				$rootText,
				$talkOptions
			);
			unset( $talkOptions );

			foreach ( $this->namespacesToNamespace[$subjectNS] as $key ) {
				$tabOptions = [
					'title'       => $this->getCustomTargetTitle( $key, $rootText ),
					'messages'    => $this->getNamespace( $key, 'message' ),
					'query'       => $this->getKeyQuery(
						$key,
						$this->getCustomTargetTitle( $key, $rootText )
					),
					'checkExists' => $userCanRead,
					'weight'      => $this->getNamespace( $key, 'weight' ),
					'isActive'    => false
				];
				if ( $title->equals( $tabOptions['title'] ) ) {
					$tabOptions['isActive'] = true;
				}
				$tabs[$key] = $this->makeTab(
					$this->getNamespace( $key, 'target' ),
					$this->getCustomTargetTitle( $key, $rootText, true ),
					$tabOptions
				);
				unset( $tabOptions );

				if ( isset( $tabs['talk'] ) ) {
					if (
						( $tabs['subject']['title']->isMainPage()
						&& $this->getNamespace( $key, 'inMainPage' )
						&& $this->getNamespace( $key, 'hideTalk' ) )
						|| $this->getNamespace( $key, 'hideTalk' )
					) {
						unset( $tabs['talk'] );
					}
				}
			}
		} else {
			// do nothing if the current namespace is not affected by the config
			return;
		}

		// sort the tabs according to their weights
		$this->sortNavigation( $tabs );
		// rebuild the navigation
		$navigation = [];

		// get Subject&Talk IDs
		list( $subjectTabId, $talkTabId ) = $this->getDefaultTabsIDs(
			$tabs['subject']['title']
		);
		foreach ( $tabs as $key => $definition ) {
			$tabId = $key;
			// assign real IDs to default $navigation members
			if ( $key === 'subject' ) {
				$tabId = $subjectTabId;
			} elseif ( $key === 'talk' ) {
				$tabId = $talkTabId;
			}
			$navigation[$tabId] = $skinTemplate->tabAction(
				$definition['title'],
				$definition['messages'],
				$definition['isActive'],
				$definition['query'],
				$definition['checkExists']
			);
			// for subject/talk it's essential, otherwise MediaWiki will just ignore it
			$navigation[$tabId]['context'] = $key;
		}
	}

	/**
	 * Prepares a subject tab definition
	 *
	 * @param int $subjectNS Tab link namespace
	 * @param string $subjectTitle Tab link title
	 * @param array $options Tab options
	 *
	 * @return array
	 */
	private function makeSubjectTab( $subjectNS, $subjectTitle, $options = [] ) {
		$defaultOptions = [
			'messages'    => [],
			'isActive'    => false,
			'query'       => '',
			'checkExists' => true,
			'weight'      => self::SUBJECT_WEIGHT,
			'context'     => 'subject'
		];
		$options = array_replace( $defaultOptions, $options );

		// prepare messages
		list( $subjectId ) = $this->getDefaultTabsIDs( $options['title'] );
		$options['key'] = $subjectId;
		$options['messages'] = [ 'nstab-' . $subjectId ];
		if ( $options['title']->isMainPage() ) {
			array_unshift( $options['messages'], 'mainpage-nstab' );
		}

		return $this->makeTab( $subjectNS, $subjectTitle, $options );
	}

	/**
	 * Prepares a talk tab definition
	 *
	 * @param int $talkNS Tab link namespace
	 * @param string $talkTitle Tab link title
	 * @param array $options Tab options
	 *
	 * @return array
	 */
	private function makeTalkTab( $talkNS, $talkTitle, $options = [] ) {
		$defaultOptions = [
			'messages'    => [],
			'isActive'    => false,
			'query'       => '',
			'checkExists' => true,
			'weight'      => self::TALK_WEIGHT,
			'context'     => 'talk'
		];
		$options = array_replace( $defaultOptions, $options );

		list( , $talkId ) = $this->getDefaultTabsIDs( $options['title'] );
		$options['messages'] = [
			'nstab-' . $talkId,
			'talk'
		];

		return $this->makeTab( $talkNS, $talkTitle, $options );
	}

	/**
	 * Prepares a tab definition
	 *
	 * @param int $tabNS Tab link namespace
	 * @param string $tabTitle Tab link title
	 * @param array $options Tab options
	 *
	 * @return array
	 */
	private function makeTab( $tabNS, $tabTitle, $options = [] ) {
		$defaultOptions = [
			'messages'    => [],
			'isActive'    => false,
			'query'       => '',
			'checkExists' => true,
			'weight'      => $this->generateWeight()
		];
		$options = array_replace( $defaultOptions, $options );

		// get the title object
		if ( !isset( $options['title'] ) || !( $options['title'] instanceof Title ) ) {
			$options['title'] = Title::makeTitle( $tabNS, $tabTitle );
		}

		return $options;
	}

	/**
	 * Returns a title with a customTarget applied
	 *
	 * @param string $key Custom tab key
	 * @param string $title Root title to replace in the custom target
	 * @param bool $raw Return the title text only
	 *
	 * @return Title|string
	 */
	private function getCustomTargetTitle( $key, $title, $raw = false ) {
		$customTarget = $this->getNamespace( $key, 'customTarget' );
		if ( $customTarget !== null ) {
			$title = wfMsgReplaceArgs( $customTarget, [ $title ] );
		}
		if ( $raw ) {
			return $title;
		} else {
			return Title::makeTitle( $this->getNamespace( $key, 'target' ), $title );
		}
	}

	/**
	 * Sorts namespace tabs according to their appropriate weights
	 *
	 * @param array &$navigation
	 */
	private function sortNavigation( &$navigation ) {
		uasort( $navigation, function ( $first, $second ) {
			return $first['weight'] - $second['weight'];
		} );
	}

	/**
	 * Finds a root page title text
	 *
	 * @param string $title Title text (without namespace)
	 * @param int $namespace Target (current) namespace ID
	 *
	 * @return string
	 */
	private function getRootTitle( $title, $namespace ) {
		if ( isset( $this->namespacesSubjectPattern[$namespace] ) ) {
			foreach ( $this->namespacesSubjectPattern[$namespace] as $pattern ) {
				$title = preg_replace( $pattern, '$1', $title );
			}
		}

		return $title;
	}

	/**
	 * Checks if title is known and returns an appropriate query string
	 *
	 * @param string $key
	 * @param Title $title
	 *
	 * @return string
	 */
	private function getKeyQuery( $key, $title ) {
		if ( $title->isKnown() ) {
			return '';
		} else {
			return $this->getNamespace( $key, 'query', '' );
		}
	}

	/**
	 * Returns Subject and Talk IDs according to given title
	 *
	 * @param Title $title
	 *
	 * @return array
	 */
	private function getDefaultTabsIDs( $title ) {
		$subjectId = $title->getSubjectPage()->getNamespaceKey( '' );
		if ( $subjectId == 'main' ) {
			$talkId = 'talk';
		} else {
			$talkId = $subjectId . '_talk';
		}

		return [
			$subjectId,
			$talkId
		];
	}

	/**
	 * Returns full NS tab definition or one of its fields
	 *
	 * @param string $key NS tab key
	 * @param string|null $param NS tab parameter
	 * @param mixed $default Value to return if parameter doesn't exist
	 *
	 * @return array|mixed
	 */
	private function getNamespace( $key, $param = null, $default = null ) {
		if ( $param === null && isset( $this->namespaces[$key] ) ) {
			return $this->namespaces[$key];
		} elseif ( isset( $this->namespaces[$key][$param] )
			&& $this->namespaces[$key][$param] !== null
		) {
			return $this->namespaces[$key][$param];
		} else {
			return $default;
		}
	}

	/**
	 * Sets full NS tab definition or one of its fields
	 *
	 * @param string $key NS tab key
	 * @param string|null $param NS tab parameter
	 * @param mixed $value Value to set, defines the whole tab if param is null
	 *
	 * @return NamespaceRelations
	 */
	private function setNamespace( $key, $param = null, $value = null ) {
		if ( $param === null && $value !== null ) {
			$this->namespaces[$key] = $value;
		} elseif ( $param !== null && $value !== null ) {
			$this->namespaces[$key][$param] = $value;
		}

		return $this;
	}

	/**
	 * Attaches tabs handling to a source namespace
	 *
	 * @param int $ns Namespace ID
	 * @param string $key NS tab key
	 *
	 * @throws MWException Thrown if namespace doesn't exist
	 */
	private function addToNamespace( $ns, $key ) {
		if ( MWNamespace::exists( $ns ) ) {
			$this->namespacesToNamespace[$ns][] = $key;
		} else {
			throw new MWException( "Namespace doesn't exist." );
		}
	}

	/**
	 * Attaches tabs handling to a target namespace
	 *
	 * @param int $ns Namespace ID
	 * @param string $key NS tab key
	 *
	 * @throws MWException Thrown if namespace doesn't exist
	 */
	private function addToTarget( $ns, $key ) {
		if ( MWNamespace::exists( $ns ) ) {
			$this->namespacesToTarget[$ns] = $key;
		} else {
			throw new MWException( "Namespace doesn't exist." );
		}
	}

	/**
	 * Generates a new incremented weight for a tab
	 *
	 * @param int $increment Custom increment value
	 *
	 * @return int
	 */
	private function generateWeight( $increment = self::WEIGHT_INCREMENT ) {
		$this->currentWeight += $increment;

		return $this->currentWeight;
	}
 }
