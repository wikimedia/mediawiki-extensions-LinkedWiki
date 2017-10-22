/*jshint node:true */
module.exports = function ( grunt ) {
	grunt.loadNpmTasks( 'grunt-jsonlint' );
	grunt.loadNpmTasks( 'grunt-banana-checker' );

	grunt.initConfig( {
		banana: {
			linkedwiki: 'i18n/linkedwiki/',
			linkedwikiconfig: 'i18n/linkedwikiconfig/',
			rdfsave: 'i18n/rdfsave/',
			rdfunit: 'i18n/rdfunit/',
			sparqlflinteditor: 'i18n/sparqlflinteditor/',
			sparqlquery: 'i18n/sparqlquery/'
		},
		jsonlint: {
			all: [
				'**/*.json',
				'!node_modules/**',
				'!vendor/**'
			]
		}
	} );

	grunt.registerTask( 'test', [ 'jsonlint', 'banana' ] );
	grunt.registerTask( 'default', 'test' );
};
