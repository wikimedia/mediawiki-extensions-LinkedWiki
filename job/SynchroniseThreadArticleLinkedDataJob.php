<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-sa V4.0
 */

class SynchroniseThreadArticleLinkedDataJob extends Job {
    public function __construct( $DBkey, $params ) {
        // Replace synchroniseThreadArticleData with an identifier for your job.
        parent::__construct( 'SynchroniseThreadArticleLinkedDataJob', $DBkey, $params );
    }

    /**
     * Execute the job
     *
     * @return bool
     */
    public function run() {
        $page = WikiPage::factory( $this->title );
        if ( is_null( $page ) ) {
            return false;
        }
        if ( !$page->exists() ) {
            return false;
        }
        if ( $page->doPurge() ) {
            $cmd = 'wget -q "'. $this->title->getFullURL().'" -O /dev/null';
            exec($cmd. " > /dev/null");
            return true;
        } else {
            return false;
        }
    }
}
