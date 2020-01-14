<?php
/**
 * API module to push images to other MediaWiki wikis.
 *
 * @since 0.5
 *
 * @file ApiPushImages.php
 * @ingroup Push
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Karima Rafes < karima.rafes@gmail.com >
 */
class ApiPushImages extends ApiPushBase {
	protected $editResponses = [];

	/**
	 * ApiPushImages constructor.
	 *
	 * @param string $main main parameter
	 * @param string $action action parameter
	 */
	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}

	/**
	 * Push images
	 */
	public function doModuleExecute() {
		$params = $this->extractRequestParams();
		$target = $params['targets'];

		foreach ( $params['images'] as $image ) {
			$title = Title::newFromText( $image, NS_FILE );
			if ( $title !== null && $title->getNamespace() == NS_FILE && $title->exists() ) {
				$this->doPush( $title, $target );
			}
		}

		foreach ( $this->editResponses as $response ) {
			$this->getResult()->addValue(
				null,
				null,
				FormatJson::decode( $response )
			);
		}
	}

	/**
	 * Pushes the page content to the target wikis.
	 *
	 * @since 0.5
	 *
	 * @param Title $title
	 * @param array $targets
	 */
	protected function doPush( Title $title, array $targets ) {
		foreach ( $targets as $target ) {
			$token = $this->getEditToken( $target );

			if ( $token !== false ) {
				$doPush = true;

				Hooks::run( 'PushAPIBeforeImagePush', [ &$title, &$target, &$token, &$doPush ] );

				if ( $doPush ) {
					$this->pushToTarget( $title, $target, $token );
				}
			}
		}
	}

	/**
	 * Pushes the image to the specified wiki.
	 *
	 * @since 0.5
	 *
	 * @param Title $title
	 * @param string $target
	 * @param string $token
	 */
	protected function pushToTarget( Title $title, $target, $token ) {
		// global $egPushDirectFileUploads;
		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'egPush' );
		$egPushDirectFileUploads = true;
		if ( !$config->has( "DirectFileUploads" ) ) {
			// throw new MWException( "egPushPushLoginUsers is not precised in the localsettings." );
		} else {
			$egPushDirectFileUploads = $config->get( "DirectFileUploads" );
		}

		$imagePage = new ImagePage( $title );

		$requestData = [
			'action' => 'upload',
			'format' => 'json',
			'token' => $token,
			'filename' => $title->getText(),
			'ignorewarnings' => '1'
		];

		if ( $egPushDirectFileUploads ) {
			$file = $imagePage->getFile();
			$be = $file->getRepo()->getBackend();
			$localFile = $be->getLocalReference(
				[ 'src' => $file->getPath() ]
			);
			// $requestData['file'] = '@' . $localFile->getPath();
			$requestData['file'] = new CurlFile( $localFile->getPath() );
		} else {
			$requestData['url'] = $imagePage->getDisplayedFile()->getFullUrl();
		}

		$reqArgs = [
			'method' => 'POST',
			'timeout' => 'default',
			'postData' => $requestData
		];

		if ( $egPushDirectFileUploads ) {
			if ( !function_exists( 'curl_init' ) ) {
				$this->dieWithError(
					wfMessage( 'push-api-err-nocurl' )->text(),
					'image-push-nocurl'
				);
			} elseif (
				!defined( 'CurlHttpRequest::SUPPORTS_FILE_POSTS' )
				|| !CurlHttpRequest::SUPPORTS_FILE_POSTS
			) {
				$this->dieWithError(
					wfMessage( 'push-api-err-nofilesupport' )->text(),
					'image-push-nofilesupport'
				);
			} else {
				Http::$httpEngine = 'curl';
				$req = MWHttpRequest::factory( $target . "/api.php", $reqArgs, __METHOD__ );
			}
		} else {
			Http::$httpEngine = 'curl';
			$req = MWHttpRequest::factory( $target . "/api.php", $reqArgs, __METHOD__ );
		}

		if ( array_key_exists( $target, $this->cookieJars ) ) {
			$req->setCookieJar( $this->cookieJars[$target] );
		}

		$status = $req->execute();

		if ( $status->isOK() ) {
			$response = $req->getContent();
			$responseObj = FormatJson::decode( $req->getContent() );

			if ( isset( $responseObj->error ) ) {
				if ( isset( $responseObj->code ) && $responseObj->code != "fileexists-no-change" ) {
					$this->dieWithError( $responseObj->error->info, 'page-push-failed' );
					return;
				}
			}

			$this->editResponses[] = $response;
			Hooks::run( 'PushAPIAfterImagePush', [ $title, $target, $token, $response ] );

		} else {
			$this->dieWithError( wfMessage( 'push-special-err-push-failed' )->text(), 'page-push-failed' );
		}
	}

	/**
	 * List parameters
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return [
			'images' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_REQUIRED => true,
			],
			'targets' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_REQUIRED => true,
			],
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 *
	 * @return array
	 */
	protected function getExamplesMessages() {
		return [
			'action=pushimages&images=File:Foo.bar&targets=http://en.wikipedia.org/w'
				=> 'apihelp-pushimages-example',
		];
	}
}
