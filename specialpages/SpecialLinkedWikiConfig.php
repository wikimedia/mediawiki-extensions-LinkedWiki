<?php
/**
 * @version 1.0.0.0
 * @package Bourdercloud/linkedwiki
 * @copyright (c) 2018 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-sa V4.0
 *
 * Last version: https://github.com/BorderCloud/LinkedWiki
 *
 * Description: https://www.mediawiki.org/wiki/Extension:LinkedWiki
 *
 * Copyright (c) 2010 Bourdercloud.com
 *
 * This work is licensed under the Creative Commons
 * Attribution-NonCommercial-ShareAlike 3.0
 * Unported License. To view a copy of this license,
 * visit http://creativecommons.org/licenses/by-nc-sa/3.0/
 * or send a letter to Creative Commons,
 * 171 Second Street, Suite 300, San Francisco,
 * California, 94105, USA.
 */
if (!defined('MEDIAWIKI')) die();

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
