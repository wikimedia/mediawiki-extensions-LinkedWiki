<?php

use MediaWiki\MediaWikiServices;

/**
 * @copyright (c) 2021 Bordercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

class SpecialSparqlQuery extends SpecialPage {

	public function __construct() {
		parent::__construct( 'linkedwiki-specialsparqlquery' );
	}

	/**
	 * @return string
	 */
	public function getGroupName() {
		return 'linkedwiki_group';
	}

	/**
	 * @param null $par
	 */
	public function execute( $par = null ) {
		// https://www.mediawiki.org/wiki/OOjs_UI/Using_OOjs_UI_in_MediaWiki
		$output = $this->getOutput();
		$output->addModules( [ 'ext.LinkedWiki.SpecialSparqlQuery' ] );

		$configFactory = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'wgLinkedWiki' );
		$querySparqlInSpecialPage = $configFactory->get( "QuerySparqlInSpecialPage" );
		$configDefault = $configFactory->get( "SPARQLServiceByDefault" );

		$query = isset( $_REQUEST["query"] ) ? stripslashes( $_REQUEST["query"] ) : "";
		$endpoint = isset( $_REQUEST["endpoint"] ) ? trim( $_REQUEST["endpoint"] ) : '';
		$config = isset( $_REQUEST["config"] ) ? trim( $_REQUEST["config"] ) : $configDefault;
		$idConfig = !empty( $config ) && $config != "other" ? $config : "";
		$radioCache = isset( $_REQUEST["radio"] ) ? trim( $_REQUEST["radio"] ) : 'sgvizler2';
		$html = "";

		// build page
		$output->addWikiMsg( 'linkedwiki-specialsparqlquery_mainpage' );
		$html .= "<form method='post' name='formQuery' id='formSparqlQuery'>";

		$html .= "<div class=\"form-group row\">
            <label for=\"endpoint\" class=\"col-2 col-form-label\">"
			. wfMessage( 'linkedwiki-specialsparqlquery_chooseaconfiguration' )->text() . "</label>
            <div class=\"col-10\">";
		$html .= $this->printSelectConfig( $idConfig );
		$html .= "</div>
        </div>";

		$html .= "<div class=\"form-group row\" id='fieldEndpoint' ";

		if ( empty( $endpoint ) ) {
			$html .= "style='display: none;'";
		}

		$html .= ">
            <label for=\"endpointOther\" class=\"col-2 col-form-label\">";
		$html .= wfMessage( 'linkedwiki-specialsparqlquery_endpointsparql' )->text() . "</label>
            <div class=\"col-10\">
                <input class=\"form-control\" type=\"url\"
                 value=\"" . $endpoint . "\" id=\"endpointOther\"
                 name=\"endpoint\"/>
                <small class=\"form-text text-muted\"
                >Example: https://query.wikidata.org/sparql</small>
            </div>
        </div>";

		$html .= "
        <div class=\"form-group row\">
            <label for=\"query\" class=\"col-2 col-form-label\">
            " . wfMessage( 'linkedwiki-specialsparqlquery_query' )->text() . "</label>
            <div class=\"col-10\">
        <textarea class=\"form-control\" id=\"query\" name='query'  rows=\"8\"  lang=\"sparql\">";

		$strQuery = $query != "" ? $query : $querySparqlInSpecialPage;
		$html .= $strQuery;

		$html .= "</textarea>
            </div>
        </div>
        ";

		$checkedPhp = $radioCache == "php" ? "checked" : "";
		$checkedSgvizler = $radioCache == "sgvizler2" ? "checked" : "";
		$html .= "<div class=\"form-group row\">
            <label for=\"endpoint\" class=\"col-2 col-form-label\"></label>
            <div class=\"col-10\">
                <div class=\"custom-control custom-radio\">
                    <input id=\"radio1\" type=\"radio\"  name=\"radio\"
                    aria-label=\"Charts of Sgvizler2 (wihtout cache and only for public data)\"
                           class=\"custom-control-input\"
                           value='sgvizler2' " . $checkedSgvizler . ">
                    <label class=\"custom-control-label\" for=\"radio1\">
                    " . wfMessage(
						'linkedwiki-specialsparqlquery_option1part1javascriptvisualizationsof'
						)->text() . "
                        <a href=\"https://bordercloud.github.io/sgvizler2\">Svizgler2</a>
                    " . wfMessage( 'linkedwiki-specialsparqlquery_option1part2details' )->text() . "
                         </label>
                </div>
                <div class=\"custom-control custom-radio\">
                    <input id=\"radio2\" type=\"radio\" name=\"radio\"
                     aria-label=\"With cache, table only (PHP)\"
                           class=\"custom-control-input\"
                           value='php' " . $checkedPhp . ">
                    <label class=\"custom-control-label\" for=\"radio2\"
                    >" . wfMessage( 'linkedwiki-specialsparqlquery_option2tableonly' )->text() . "
                    </label>
                </div>
            </div>
        </div>
        <div id=\"sgvizlerInputsForm\" ";

		if ( $radioCache == "php" ) {
			$html .= "style='display: none;'";
		}

		$html .= ">
            <div class=\"form-group row\">
                <label for=\"options\" class=\"col-2 col-form-label\">
                " . wfMessage( 'linkedwiki-specialsparqlquery_options' )->text() . "</label>
                <div class=\"col-10\">
                    <input class=\"form-control\" type=\"input\" id=\"options\"
                     value='width=100%!height=500px'>
                </div>
            </div>
            <div class=\"form-group row\">
                <label for=\"logsLevel\" class=\"col-2 col-form-label\">
                " . wfMessage( 'linkedwiki-specialsparqlquery_loglevel' )->text() . "</label>
                <div class=\"col-10\">
                    <select class=\"form-control\" id=\"logsLevel\">
                        <option value=\"0\">0</option>
                        <option value=\"1\">1</option>
                        <option value=\"2\" selected>2</option>
                    </select>
                </div>
            </div>
            <div class=\"form-group row\">
                <label for=\"logsLevel\" class=\"col-2 col-form-label\">
                " . wfMessage( 'linkedwiki-specialsparqlquery_visualization' )->text() . "</label>
                <div class=\"col-10\">
                    <select class=\"selectpicker\" id=\"chart\"></select>
                    <button id=\"seeDoc\" type=\"button\"
                     class=\"btn btn-info info\">
                     " . wfMessage( 'linkedwiki-specialsparqlquery_seethedoc' )->text() . "</button>
                </div>
            </div>
        </div>";

		$html .= "
<div class=\"form-group row\">
            <label  class=\"col-2 col-form-label\"></label>
            <div class=\"col-10\">
                <button id=\"execQuery\" type=\"button\" class=\"btn btn-primary btn-lg\">"
			. wfMessage( 'linkedwiki-specialsparqlquery_sendquery' )->text() . "</button>
            </div>
</div>";

		$html .= " </form>
 <ul class=\"nav nav-tabs\" role=\"tablist\" id='tabSparqlQuery'>
    <li class=\"nav-item\">
        <a class=\"nav-link active\" data-toggle=\"tab\" href=\"#resultTab\" role=\"tab\">
        " . wfMessage( 'linkedwiki-specialsparqlquery_result' )->text() . "</a>
    </li>
    <li class=\"nav-item\">
        <a class=\"nav-link\" data-toggle=\"tab\" href=\"#htmlTab\" role=\"tab\">"
			. wfMessage( 'linkedwiki-specialsparqlquery_usethisquery' )->text() . "</a>
    </li>
</ul>
 <div class=\"tab-content\">
    <div class=\"tab-pane active\" id=\"resultTab\" role=\"tabpanel\">
        <div id=\"example\" style=\"padding: 25px;\"><div id=\"result\" ";

		// insert api keys
		$configFactory = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'wgLinkedWiki' );
		if ( $configFactory->has( "GoogleApiKey" ) ) {
			$html .= "data-googleapikey=\"" . $configFactory->get( "GoogleApiKey" ) . "\" \n";
		}
		if ( $configFactory->has( "OSMAccessToken" ) ) {
			$html .= "data-osmaccesstoken=\"" . $configFactory->get( "OSMAccessToken" ) . "\" \n";
		}
		// end of div id=result
		$html .= ">";

		if ( !empty( $query ) ) {
			$arr = SparqlParser::tableHTML(
				null,
				$query,
				$idConfig,
				$endpoint,
				'wikitable sortable',
				'',
				'',
				'',
				'',
				null,
				false,
				false,
				2,
				2,
				'no result'
			);
			$html .= $arr[0];
		}
		$html .= "</div></div>
    </div>
    <div class=\"tab-pane\" id=\"htmlTab\" role=\"tabpanel\">
        <div class=\"bg-faded\" style=\"padding: 25px;\">";

		$output->addHTML( $html );
		$output->addWikiMsg( 'linkedwiki-specialsparqlquery_usethisquery_tutorial' );

		$html2 = "<pre lang=\"html\" id=\"consoleWiki\">";
		if ( !empty( $query ) ) {
			$template = "{{#sparql:\n" . htmlentities( $query, ENT_QUOTES, 'UTF-8' );
			$errorMessage = "";
			if ( $config == "other" && !empty( $endpoint ) ) {
				$template .= "\n|endpoint=" . htmlentities( $endpoint, ENT_QUOTES, 'UTF-8' );
			} elseif ( !empty( $config ) && $config != $configDefault ) {
				$template .= "\n|config=" . htmlentities( $config, ENT_QUOTES, 'UTF-8' );
			} elseif ( !empty( $config ) ) {
				// do nothing
			} else {
				$errorMessage = "An endpoint Sparql or "
					. "a configuration by default is not found.";
			}
			$template .= "\n}}";
			if ( empty( $errorMessage ) ) {
				$html2 .= $template;
			} else {
				$html2 .= $errorMessage;
			}
		}
		$html2 .= "</pre></div>
    </div>
</div>";

		$output->addHTML( $html2 );
		$this->setHeaders();
	}

	/**
	 * @param string $configIri
	 * @return string
	 */
	protected function printSelectConfig( $configIri ) {
		$html = "";
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'wgLinkedWiki' );

		$configs = $config->get( "ConfigSPARQLServices" );

		$html .= "<select id='config' name='config'  class=\"form-control\">";
		foreach ( $configs as $key => $value ) {

			if ( $key === "http://www.example.org" ) {
				continue;
			}

			$html .= '<option value="' . $key . '" ';
			if ( $key === $configIri ) {
				$html .= "selected='selected' ";
			}
			if ( isset( $value["login"] ) ) {
				$html .= "credential='true' ";
			} else {
				$html .= "credential='false' ";
				if ( isset( $value["endpointRead"] ) ) {
					$html .= "endpoint='" . $value["endpointRead"] . "' ";
				}

				if ( isset( $value["HTTPMethodForRead"] ) ) {
					$html .= "method='" . $value["HTTPMethodForRead"] . "' ";
				}

				if ( isset( $value["nameParameterRead"] ) ) {
					$html .= "parameter='" . $value["nameParameterRead"] . "' ";
				}
			}
			// print_r($value);
			$html .= ">";
			$html .= $key;
			$html .= "</option>";
			// print_r($html);
		}

		$html .= '<option value="other" ';
		if ( $configIri === "" ) {
			$html .= "selected='selected'";
		}
		$html .= '>Other</option>';
		$html .= "</select>";
		return $html;
	}
}
