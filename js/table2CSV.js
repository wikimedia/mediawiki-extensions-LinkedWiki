/**
 * JavaScript for the LinkedWiki extension.
 *
 * @see https://www.mediawiki.org/wiki/Extension:LinkedWiki
 *
 * @author Karima Rafes < karima dot rafes@gmail.com >
 */
/* globals jQuery */
( function ( $ ) {
	$( () => {
		jQuery.fn.table2CSV = function ( pOptions ) {
			const
				options = Object.assign( {
					separator: ',',
					header: [],
					delivery: 'popup' // popup or value
				}, pOptions ),
				csvData = [],
				table = this,
				numCols = options.header.length, // header
				tmpRow = []; // construct header available array

			// constructor****************************
			if ( numCols > 0 ) {
				for ( let i = 0; i < numCols; i++ ) {
					tmpRow[ tmpRow.length ] = formatData( options.header[ i ] );
				}
			}

			// functions*****************************
			function row2CSV( row ) {
				const tmp = row.join( '' ); // to remove any blank rows
				if ( row.length > 0 && tmp !== '' ) {
					csvData[ csvData.length ] = row.join( options.separator );
				}
				// console.log(csvData);
			}

			function formatData( input ) {
				// replace " with “
				let output = input.replace( /["]/g, '“' );
				output = output.replace( /\\&lt;[^\\&lt;]+\\&gt;/g, '' );
				if ( output === '' ) {
					return '';
				}
				return '"' + output + '"';
			}

			function popup( data ) {
				const generator = window.open( '', 'csv', 'height=400,width=600' );
				generator.document.write( '<html><head><title>CSV</title>' );
				generator.document.write( '</head><body >' );
				generator.document.write( '<textArea cols=70 rows=15 wrap="off" >' );
				generator.document.write( data );
				generator.document.write( '</textArea>' );
				generator.document.write( '</body></html>' );
				generator.document.close();
				return true;
			}

			// actual data
			$( table ).find( 'tr' ).each( function () {
				const row = [];
				// eslint-disable-next-line  no-jquery/no-each-util
				$.each( this.cells, function () {
					row[ row.length ] = formatData( $( this ).text() );
				} );
				row2CSV( row );
			} );
			if ( options.delivery === 'popup' ) {
				return popup( csvData.join( '\n' ) );
			} else {
				return csvData.join( '\n' );
			}
		};

		// eslint-disable-next-line no-jquery/no-global-selector
		$( 'table' ).each( function () {
			const
				$table = $( this ),
				$l = $table.find( '.csv' );

			$l.on( 'click', () => {
				let csv = $table.table2CSV( { delivery: 'value' } );
				// remove last line table
				csv = csv.slice( 0, csv.lastIndexOf( '\n' ) );
				window.location.href = 'data:text/csv;charset=UTF-8,' + encodeURIComponent( csv );
			} );
		} );

	} );
}( jQuery ) );
