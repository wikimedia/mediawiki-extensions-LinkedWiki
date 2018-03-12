<?php
/**
 * @copyright (c) 2018 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link http://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-sa V4.0
 *
 *  Last version : http://github.com/BorderCloud/LinkedWiki
 *
 *
 * This work is licensed under the Creative Commons
 * Attribution-NonCommercial-ShareAlike 3.0
 * Unported License. To view a copy of this license,
 * visit http://creativecommons.org/licenses/by-nc-sa/3.0/
 * or send a letter to Creative Commons,
 * 171 Second Street, Suite 300, San Francisco,
 * California, 94105, USA.
 */

if (!defined('MEDIAWIKI'))
    die();

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
            return true;
        } else {
            return false;
        }
    }
}
