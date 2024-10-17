/**
 * JavaScript for the LinkedWiki extension.
 *
 * @see https://www.mediawiki.org/wiki/Extension:LinkedWiki
 *
 * @author Karima Rafes < karima dot rafes@gmail.com >
 */
/* globals jQuery sgvizler2 */
( function ( $ ) {
	$( () => {
		const $sgvizlerInputsForm = $( '#sgvizlerInputsForm' );

		function eventChangeSelectConfig() {
			const selectConfig = document.getElementById( 'config' );
			const divFieldEndpoint = document.getElementById( 'fieldEndpoint' );
			const inputFieldEndpoint = document.getElementById( 'endpointOther' );
			const value = selectConfig.options[ selectConfig.selectedIndex ].value;
			if ( value !== 'other' ) {
				inputFieldEndpoint.value = '';
				divFieldEndpoint.style.display = 'none';
			} else {
				divFieldEndpoint.style.display = '';
			}
			// console.log( 'change' );
		}

		const $chart = $( '#chart' );
		const $config = $( '#config' );
		const $formSparqlQuery = $( '#formSparqlQuery' );
		const $endpointOther = $( '#endpointOther' );

		$chart.selectchart( {
			action: 'render',
			subtext: 'classFullName',
			selected: 'bordercloud.visualization.DataTable'
		} );

		$chart.selectpicker( 'refresh' );

		$config.on( 'change', eventChangeSelectConfig );

		$( '#execQuery' ).on( 'click', () => {

			const inputValue = $formSparqlQuery.find( 'input[name=radio]:checked' ).val();
			if ( inputValue === 'php' ) {
				$formSparqlQuery.trigger( 'submit' );
			} else {
				let endpoint = $endpointOther.val();
				const config = $config.val();
				const query = $( '#query' ).val();
				const chart = $formSparqlQuery.find( '.selectpicker' ).selectpicker( 'val' );
				const options = $( '#options' ).val();
				const logsLevel = $( '#logsLevel' ).val();
				// eslint-disable-next-line no-jquery/no-sizzle
				const $optionConfigSelected = $config.find( 'option:selected' );
				const credential = $optionConfigSelected.attr( 'credential' );
				const method = $optionConfigSelected.attr( 'method' );
				const parameter = $optionConfigSelected.attr( 'parameter' );
				let wiki = '';
				let errorMessage = '';

				// build container for Wiki

				wiki = '{{#sparql:\n' + query;

				if ( config === 'other' && endpoint !== '' ) {
					wiki += '\n|endpoint=' + endpoint;
				} else if ( config !== '' ) {
					wiki += '\n|config=' + config;
				// } else if (!EMPTY($config)) {
				//     //do nothing
				} else {
					errorMessage = 'An endpoint Sparql or ' +
                    'a configuration by default is not found.';
				}
				wiki += '\n|chart=' + chart;

				if ( options !== '' ) {
					wiki += '\n|options=' + options;
				}
				if ( logsLevel !== '0' ) {
					wiki += '\n|log=' + logsLevel;
				}
				wiki += '\n}}';

				$( '#consoleWiki' ).text(
					wiki !== '' ? wiki : errorMessage
				);

				// build container for html
				const $result = $( '#result' );
				$result.children().remove();

				$( '#tabSparqlQuery' ).find( 'a[href="#resultTab"]' ).tab( 'show' );
				if ( credential === 'true' ) {
					// eslint-disable-next-line no-jquery/no-parse-html-literal
					$result.html( '<b>This SPARQL service is not accessible via Javascript.</b>' );
				} else {
					if ( config === 'other' ) {
						endpoint = $endpointOther.val();
					} else {
						endpoint = $optionConfigSelected.attr( 'endpoint' );
					}
					sgvizler2.create(
						'result',
						endpoint,
						query,
						chart,
						options,
						logsLevel,
						'',
						method,
						parameter
					);

					// eslint-disable-next-line no-jquery/no-global-selector
					$( '[data-sgvizler-query]' ).each(
						function () {
							const $obj = $( this );
							$obj.containerchart( {
								googleApiKey: $obj.data( 'googleapikey' ),
								osmAccessToken: $obj.data( 'osmaccesstoken' ),
								path: mw.config.get( 'wgScriptPath' ) + '/extensions/LinkedWiki/node_modules/sgvizler2/build/browser'
							} );
						}
					);
				}
			}

			// $("#result").html("")
			// $("#console").html("")
			//
			//
			//
			// $("#consoleHtml").text(
			// )
			//
			// $("#consoleScript").text(
			// )

		} );

		$( '#seeDoc' ).on( 'click', () => {
			const url = sgvizler2.getChartDoc(
				$sgvizlerInputsForm.find( '.selectpicker' ).selectpicker( 'val' )
			);
			window.open( url, '_blank' );
		} );

		$sgvizlerInputsForm.find( 'input[type="radio"]' ).on( 'click', function () {
			if ( $( this ).prop( 'checked' ) ) {
				const inputValue = $( this ).attr( 'value' );
				if ( inputValue === 'php' ) {
					$sgvizlerInputsForm.hide();
				} else {
					$sgvizlerInputsForm.show();
				}
			}
		} );

	} );
}( jQuery ) );
