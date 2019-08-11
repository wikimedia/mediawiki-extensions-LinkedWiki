<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-sa V4.0
 */

class SpecialLinkedWikiConfig extends SpecialPage
{

    public function __construct()
    {
        parent::__construct('linkedwiki-speciallinkedwikiconfig');
    }
    function getGroupName() {
        return 'linkedwiki_group';
    }

    public function execute($par = null)
    {
        global $wgOut;

        $wgOut->addWikiTextAsInterface("Endpoints configurated in the system via the file LinkedWiki/extension.json.");

        $wgOut->addWikiTextAsInterface("== Configuration of endpoints SPARQL ==");
        $wgOut->addWikiTextAsInterface("Endpoints configurated in the system.");
        $config = new LinkedWikiConfig();
        $wgOut->addHTML($config->info());
        $this->setHeaders();
    }
}
