/* eslint-env node */
module.exports = function ( grunt ) {
	// const conf = grunt.file.readJSON( 'extension.json' );

	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-stylelint' );

	grunt.initConfig( {
		eslint: {
			options: {
				cache: true,
				// this will get params from the flags
				fix: grunt.option( 'fix' )
			},
			all: [
				'**/*.{js,json}',
				'js/SparqlEditor/*.js',
				'!js/flowchart.js',
				'!js/lwgraph.js',
				'!**/coverage/**',
				'!node_modules/**',
				'!vendor/**',
				'!js/FlintSparqlEditor/**',
				'!resources/**'
			]
		},
		stylelint: {
			all: [
				'**/*.{css,less}',
				'!**/coverage/**',
				'!node_modules/**',
				'!vendor/**',
				'!js/FlintSparqlEditor/**',
				'!resources/**'
			]
		},
		banana: {
			all: {
				files: {
					src: 'i18n'
				},
				options: {
					requireCompleteTranslationLanguages: [
						'en',
						'fr'
					]
				}
			}
		}
	} );

	grunt.registerTask( 'test', [ 'eslint', 'stylelint', 'banana' ] );
	grunt.registerTask( 'default', 'test' );
};
