<?php
// phpcs:disable
/*
origin : https://github.com/balrok/phpstuff/blob/master/coverage/coverage.php
*/

/**
 * Creates codecoverage files for your code.
 * Based on phpunits: https://github.com/sebastianbergmann/php-code-coverage
 * With following advantages:
 *  - faster (at each request)
 *  - can be used outside of phpunit
 *  - can merge the generated coverage files
 *
 * Usage:
 * ------
 * First you need to configure xdebug to enable coverage
 * Then you need to determine the code which needs the coverage.
 * At the beginning add:
 * include coverage.php;
 * $wrapper = new Coverage\Wrapper();
 * $wrapper->dir = '/path/for/output/';
 * $wrapper->start();
 * // here comes your code..
 * $wrapper->stop();
 *
 * -----------------
 * After you do some requests the /path/for/output contains some files - those files are serialized arrays with the coverage info + some meta
 * To process them further this package comes with some tools:
 * include "coverage.php";
 * $reader = new Coverage\Reader('/path/for/output/');
 * $filter = new Coverage\Filter();
 * $filter->excludeFileRegex = '/\/yii\/.*\/framework\//';
 * $merger = new Coverage\Merger();
 * $converter = new Coverage\Converter();
 * foreach ($reader as $k=>$data)
 * {
 *         $data = $filter->filterData($data);
 *             $merger->mergeData($data);
 * }
 * file_put_contents('clover.xml', $converter->toClover($merger->data));
 *
 * As you see it is quite straight forward
 */
namespace Coverage {
	/**
	 * used to create codecoverage from a web application.
	 * it is meant to wrap around the code which should be covered.
	 * How to use it:
	 * $wrapper = new Wrapper();
	 * $wrapper->start();
	 * // some code
	 * $wrappre->dir = 'path/to/coverage/dir';
	 * $wrapper->stop();
	 */
	class Wrapper {
		public $dir;

		function __construct() {
			if ( !extension_loaded( 'xdebug' ) ) {
				throw new Exception( 'Xdebug is not loaded.' );
			}
			if ( !ini_get( 'xdebug.coverage_enable' ) ) {
				throw new Exception(
					'You need to set xdebug.coverage_enable=On in your php.ini.'
				);
			}
		}

		public function start() {
			xdebug_start_code_coverage( XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE );
		}

		public function stop() {
			if ( !$this->dir ) {
				throw new Exception( 'You need to specifiy a directory' );
			}
			$codeCoverage = xdebug_get_code_coverage();
			if ( !is_dir( $this->dir ) ) {
				mkdir( $this->dir, 0777 );
			}
			/* enhance the coverage with some metadata: */
			$data = [
				'time' => microtime( true ),
				'coverage' => $codeCoverage,
				'request' => empty( $_SERVER['REQUEST_URI'] ) ? '-' : $_SERVER['REQUEST_URI'],
				'post' => !empty( $_POST ),
			];
			$uniq = microtime( true );
			$file = sprintf( '%s%s.json', $this->dir, $uniq );
			file_put_contents( $file, json_encode( $data ) );
			chmod( $file, 777 );
			xdebug_stop_code_coverage();
		}
	}

	/**
	 * Iterator which will read the directory where the wrapper saved all coverage files
	 * after that it returns the decoded content of those files
	 * it is ordered by the creation date
	 * usage:
	 * $reader = new Reader('/path');
	 * foreach ($reader as $k=>$data)
	 */
	class Reader implements \Iterator {
		/// filled on initialization: contains a sorted list of all files
		protected $files = [];
		/// directory where coverage files were stored
		protected $dir;

		/**
		 * $dir string directory where the coverage files were stored
		 */
		public function __construct( $dir ) {
			$this->dir = $dir;
			$handle = opendir( $dir );
			while ( $file = readdir( $handle ) ) {
				if ( substr( $file, -5 ) == '.json' ) {
					$this->files[] = $file;
				}
			}
			sort( $this->files );
		}

		/**
		 * returns an array of all decoded files
		 */
		public function asArray() {
			$allData = [];
			foreach ( $this as $data ) {
				$allData[] = $data;
			}
			return $allData;
		}

		public function current() {
			$current = current( $this->files );
			if ( $current === false ) {
				return $current;
			}
			return json_decode( file_get_contents( $this->dir . $current ), true );
		}

		public function rewind() {
			reset( $this->files );
		}

		public function key() {
			return key( $this->files );
		}

		public function next() {
			return next( $this->files );
		}

		public function valid() {
			return $this->current() !== false;
		}
	}

	/**
	 * Will filter the coverage based on some rules
	 * minTime the coverage must be minimum as old as this unix timestamp
	 * maxTime the coverage must be maximum as old as this unix timestamp
	 * excludeFileRegex a regex which matches coverage files which should be excluded
	 * Usage:
	 * $filter = new Filter();
	 * $filter->minTime = time();
	 * $data = $filter->filterData($data);
	 */
	class Filter {
		public $minTime;
		public $maxTime;
		public $excludeFileRegex;

		/**
		 * will filter and return array() or a filtered array
		 */
		public function filterData( $data ) {
			if ( $this->minTime ) {
				if ( $data['time'] < $this->minTime ) {
					return [];
				}
			}
			if ( $this->maxTime ) {
				if ( $data['time'] > $this->maxTime ) {
					return [];
				}
			}
			if ( $this->excludeFileRegex ) {
				foreach ( $data['coverage'] as $file => $lines ) {
					if ( preg_match( $this->excludeFileRegex, $file ) ) {
						unset( $data['coverage'][$file] );
					}
				}
				if ( $data['coverage'] == [] ) {
					return [];
				}
			}
			return $data;
		}
	}

	/**
	 * will merge all coverages into one
	 * Usage:
	 * $merger = new Merger();
	 * foreach(.. as $data)
	 *    $merger->mergeData($data);
	 * $allData = $merger->data;
	 */
	class Merger {
		public $data = [];

		/**
		 * expects an array of coverage data
		 *
		 * @param $data
		 * @return array
		 */
		public function mergeData( $data ) {
			foreach ( $data['coverage'] as $file => $coverData ) {
				if ( !isset( $this->data[$file] ) ) {
					$this->data[$file] = [];
				}
				foreach ( $coverData as $line => $val ) {
					if ( $val > 0 ) {
						$this->data[$file][$line] = $val;
					}
				}
			}
			return $this->data;
		}
	}

	/**
	 * will convert the data in various formats
	 * Usage:
	 * $converter = new Converter();
	 * echo $converter->toClover($data);
	 */
	class Converter {
		/**
		 * @param array $coverage
		 * @return string
		 */
		public function toVim( $coverage ) {
			$return = [];
			foreach ( $coverage as $file => $lines ) {
				// echo PHP_EOL.$file,' :  ',join(',',array_keys($lines));
				$return[] = $file . PHP_EOL . 'match cursorline /\%'
					. implode( 'l\|\%', array_keys( $lines ) ) . "l/";
			}
			return implode( PHP_EOL, $return );
		}

		// /**
		// * @todo
		// *
		// * @param $coverage
		// * @return string
		// */
		// public function toHtml( $coverage ) {
		// $html = '';
		// return $html;
		// }

		/**
		 *
		 * @param array $coverage
		 * @param string $name projectname
		 * @return string
		 */
		public function toClover( $coverage, $name = '' ) {
			$time = time();
			$xmlDocument = new \DOMDocument( '1.0', 'UTF-8' );
			$xmlDocument->formatOutput = true;
			$xmlCoverage = $xmlDocument->createElement( 'coverage' );
			$xmlCoverage->setAttribute( 'generated', $time );
			$xmlDocument->appendChild( $xmlCoverage );
			$xmlProject = $xmlDocument->createElement( 'project' );
			$xmlProject->setAttribute( 'timestamp', $time );
			if ( is_string( $name ) ) {
				$xmlProject->setAttribute( 'name', $name );
			}
			$xmlCoverage->appendChild( $xmlProject );
			$packages = [];
			foreach ( $coverage as $file => $coverageData ) {
				$namespace = 'global';
				$xmlFile = $xmlDocument->createElement( 'file' );
				$xmlFile->setAttribute( 'name', $file );
				$lines = [];
				foreach ( $coverageData as $line => $data ) {
					if ( $line > 0 ) {
						$lines[$line] = [
							'count' => 1, 'type' => 'stmt'
						];
					}
				}
				ksort( $lines );
				foreach ( $lines as $line => $data ) {
					$xmlLine = $xmlDocument->createElement( 'line' );
					$xmlLine->setAttribute( 'num', $line );
					$xmlLine->setAttribute( 'type', $data['type'] );
					if ( isset( $data['name'] ) ) {
						$xmlLine->setAttribute( 'name', $data['name'] );
					}
					if ( isset( $data['crap'] ) ) {
						$xmlLine->setAttribute( 'crap', $data['crap'] );
					}
					$xmlLine->setAttribute( 'count', $data['count'] );
					$xmlFile->appendChild( $xmlLine );
				}
				if ( $namespace == 'global' ) {
					$xmlProject->appendChild( $xmlFile );
				} else {
					if ( !isset( $packages[$namespace] ) ) {
						$packages[$namespace] = $xmlDocument->createElement(
							'package'
						);
						$packages[$namespace]->setAttribute( 'name', $namespace );
						$xmlProject->appendChild( $packages[$namespace] );
					}
					$packages[$namespace]->appendChild( $xmlFile );
				}
			}
			return $xmlDocument->saveXML();
		}
	}
// end namespace Coverage
}
// phpcs:enable
