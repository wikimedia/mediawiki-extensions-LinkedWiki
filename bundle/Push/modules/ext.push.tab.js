/**
 * JavaScript for the Push tab in the Push extension.
 * @see https://www.mediawiki.org/wiki/Extension:Push
 *
 * @author Jeroen De Dauw <jeroendedauw at gmail dot com>
 */

(function( $ ) { $( document ).ready( function() {

	// Compatibility with pre-RL code.
	// Messages will have been loaded into wgPushMessages.
	if ( typeof mediaWiki === 'undefined' ) {
		mediaWiki = new Object();

		mediaWiki.msg = function() {
			message = window.wgPushMessages[arguments[0]];

			for ( var i = arguments.length - 1; i > 0; i-- ) {
				message = message.replace( '$' + i, arguments[i] );
			}

			return message;
		}
	}

	mw = mediaWiki;

	var pages;
	var targetData = [];

	$.each($(".push-button"), function(i,v) {
		getRemoteArticleInfo( $(v).attr( 'targetid' ), $(v).attr( 'pushtarget' ) );
	});

	$('.push-button').click(function() {
		this.disabled = true;

		var errorDiv = $( '#targeterrors' + $(this).attr( 'targetid' ) );
		errorDiv.fadeOut( 'fast' );

		pages = [$('#pageName').attr('value')];

		if ( $('#checkIncTemplates').is(':checked') ) {
			pages = pages.concat(window.wgPushTemplates);

            if ( $('#checkIncSubpages').is(':checked') ) {
                pages = pages.concat(window.wgPushSubpagesTemplates);
            }
		}

        if ( $('#checkIncSubpages').is(':checked') ) {
            pages = pages.concat(window.wgPushSubpages);
        }


		setButtonToImgPush(
			this,
			pages,
			$(this).attr( 'pushtarget' ),
			$(this).attr( 'targetname' )
		);
	});

	$('#push-all-button').click(function() {
		this.disabled = true;
		this.innerHTML = mw.msg( 'push-button-pushing' );
		$.each($(".push-button"), function(i,v) {
			$(v).click();
		});
	});

	$('#divIncTemplates').hover(
		function() {
			var isHidden = $('#txtTemplateList').css( 'opacity' ) == 0;

			if ( isHidden ) {
				$('#txtTemplateList').css( 'display', 'inline' );
				setIncludeTemplatesText();
			}

			$('#txtTemplateList').fadeTo(
				isHidden? 'slow' : 'fast',
				1
			);
		},
		function() {
			$('#txtTemplateList').fadeTo( 'fast', 0.5 )
		}
	);

	$('#divIncTemplates').click(function() {
		setIncludeFilesText();
		setIncludeTemplatesText();
		displayTargetsConflictStatus();
	});


	$('#divIncSubpages').hover(
		function() {
			var isHidden = $('#txtSubpageList').css( 'opacity' ) == 0;

			if ( isHidden ) {
				$('#txtSubpageList').css( 'display', 'inline' );
			}

			$('#txtSubpageList').fadeTo(
				isHidden? 'slow' : 'fast',
				1
			);
		},
		function() {
			$('#txtSubpageList').fadeTo( 'fast', 0.5 )
		}
	);

	$('#divIncSubpages').click(function() {
		setIncludeFilesText();
		setIncludeTemplatesText();
		displayTargetsConflictStatus();
	});

	$('#divIncFiles').click(function() {
		displayTargetsConflictStatus();
	});

	$('#divIncFiles').hover(
		function() {
			var isHidden = $('#txtFileList').css( 'opacity' ) == 0;

			if ( isHidden ) {
				$('#txtFileList').css( 'display', 'inline' );
				setIncludeFilesText();
			}

			$('#txtFileList').fadeTo(
					isHidden ? 'slow' : 'fast',
				1
			);
		},
		function() {
			$('#txtFileList').fadeTo( 'fast', 0.5 )
		}
	);

	function setIncludeFilesText() {
		if ( $('#checkIncFiles').length != 0 ) {
			var files = window.wgPushPageFiles;

			if ( $('#checkIncTemplates').is(':checked') ) {
				files = files.concat( window.wgPushTemplateFiles );
			}

			if ( $('#checkIncSubpages').is(':checked') ) {
				files = files.concat( window.wgPushSubpagesFiles );
			}

			if ( files.length > 0 ) {
				$('#txtFileList').text( '(' + mw.msg( 'push-tab-embedded-files' ) + ' ' );

				for ( i in files ) {
					if ( i > 0 ) $('#txtFileList').append( ', ' );
					$('#txtFileList').append( $( '<a>' ).attr( 'href', window.wgPushIndexPath + '?title=' + files[i] ).text( files[i] ) );
				}

				$('#txtFileList').append( ')' );
			}
			else {
				$('#txtFileList').text( mw.msg( 'push-tab-no-embedded-files' ) );
			}
		}
	}

	function setIncludeTemplatesText() {
		if ( $('#checkIncFiles').length != 0 ) {
			var templates = window.wgPushTemplates;

			if ( $('#checkIncSubpages').is(':checked') ) {
				templates = templates.concat( window.wgPushSubpagesTemplates );
			}

			if ( templates.length > 0 ) {
				$('#txtTemplateList').text( '(' + mw.msg( 'push-tab-used-templates' ) + ' ' );

				for ( i in templates ) {
					if ( i > 0 ) $('#txtTemplateList').append( ', ' );
					$('#txtTemplateList').append( $( '<a>' ).attr( 'href', window.wgPushIndexPath + '?title=' + templates[i] ).text( templates[i] ) );
				}

				$('#txtTemplateList').append( ')' );
			}
			else {
				$('#txtTemplateList').text( mw.msg( 'push-tab-no-used-templates' ) );
			}
		}
	}

	function getRemoteArticleInfo( targetId, targetUrl ) {
		var pageName = $('#pageName').attr('value');

		$.post(
			targetUrl + '/api.php',
			{
				'action': 'query',
				'format': 'json',
				'prop': 'revisions',
				'rvprop': 'timestamp|user|comment',
				'titles': [pageName]
					.concat( window.wgPushTemplates )
					.concat( window.wgPushPageFiles )
					.concat( window.wgPushTemplateFiles )
					.concat( window.wgPushSubpages )
					.concat( window.wgPushSubpagesTemplates )
					.concat( window.wgPushSubpagesFiles )
					.join( '|' )
			},
			function( data ) {
				if ( data.query ) {
					var infoDiv = $( '#targetinfo' + targetId );

					var existingPages = [];
					var remotePage = false;

					for ( remotePageId in data.query.pages ) {
						if ( remotePageId > 0 ) {
							if ( data.query.pages[remotePageId].title == pageName ) {
								remotePage = data.query.pages[remotePageId];
							}
							else {
								existingPages.push( data.query.pages[remotePageId] );
							}
						}
					}

					targetData[targetId] = { 'existingPages': existingPages };

					if ( remotePage ) {
						$( '#targetlink' + targetId ).attr( {'class': ''} );

						var revision = remotePage.revisions[0];
						var dateTime = revision.timestamp.split( 'T' );

						var message = mw.msg(
							'push-tab-last-edit',
							revision.user,
							dateTime[0],
							dateTime[1].replace( 'Z', '' )
						);
					}
					else {
						$( '#targetlink' + targetId ).attr( {'class': 'new'} );
						var message = mw.msg( 'push-tab-not-created' );
					}

					infoDiv.text( message );
					infoDiv.fadeIn( 'slow' );

					displayTargetConflictStatus( targetId );
				}
			}
			, "jsonp");
	}

	function displayTargetsConflictStatus() {
		$.each($(".push-button"), function(i,v) {
			displayTargetConflictStatus( $(v).attr( 'targetid' ) );
		});
	}

	function displayTargetConflictStatus( targetId ) {
		if ( !targetData[targetId] ) {
			// It's possible the request to retrieve this data failed, so don't do anything when this is the case.
			return;
		}

		if ( $('#checkIncTemplates').is(':checked') ) {
			var overideTemplates = [];

			for ( remotePageId in targetData[targetId].existingPages ) {
				if ( targetData[targetId].existingPages[remotePageId].ns == 10 ) {
					// Add the template, but get rid of the namespace prefix first.
					overideTemplates.push( targetData[targetId].existingPages[remotePageId].title.split( ':', 2 )[1] );
				}
			}

			if ( overideTemplates.length > 0 ) {
				$( '#targettemplateconflicts' + targetId )
					.text( mw.msg( 'push-tab-template-override',
						overideTemplates.join( ', ' ),
						overideTemplates.length
					) )
					.fadeIn( 'slow' );
			}
			else {
				$( '#targettemplateconflicts' + targetId ).fadeOut( 'slow' );
			}
		}
		else {
			$( '#targettemplateconflicts' + targetId ).fadeOut( 'fast' );
		}

		if ( $('#checkIncFiles').length != 0 && $('#checkIncFiles').is(':checked') ) {
			var overideFiles = [];

			for ( remotePageId in targetData[targetId].existingPages ) {
				if ( targetData[targetId].existingPages[remotePageId].ns == 6 ) {
					// Add the file, but get rid of the namespace prefix first.
					overideFiles.push( targetData[targetId].existingPages[remotePageId].title.split( ':', 2 )[1] );
				}
			}

			if ( overideFiles.length > 0 ) {
				$( '#targetfileconflicts' + targetId )
					.text( mw.msg( 'push-tab-files-override',
						overideFiles.join( ', ' ),
						overideFiles.length
					) )
					.fadeIn( 'slow' );
			}
			else {
				$( '#targetfileconflicts' + targetId ).fadeOut( 'slow' );
			}
		}
		else {
			$( '#targetfileconflicts' + targetId ).fadeOut( 'fast' );
		}
	}

	function initiatePush( sender, pages, targetUrl, targetName ) {
		sender.innerHTML = mw.msg( 'push-button-pushing' );

		$.post(
			mw.config.get( 'wgScriptPath' )  + '/api.php',
			{
				'action': 'push',
				'format': 'json',
				'page': pages.join( '|' ),
				'targets': targetUrl
			},
			function( data ) {
				if ( data.error ) {
					handleError( sender, targetUrl, data.error );
				}
				else if ( data.length > 0 && data[0].edit && data[0].edit.captcha ) {
					handleError( sender, targetUrl, { info: mw.msg( 'push-err-captacha', targetName ) } );
				}
				else {
					handlePushingCompletion( sender, targetUrl, targetName );
				}
			}
			, "json");
	}

	function handlePushingCompletion( sender, targetUrl, targetName ) {
		sender.innerHTML = mw.msg( 'push-button-completed' );

		setTimeout( function() {
			reEnableButton( sender, targetUrl, targetName );
		}, 1000 );
	}

	function setButtonToImgPush( button, pages, targetUrl, targetName ) {
		//var images = window.wgPushPageFiles.concat( window.wgPushTemplateFiles );

        var files = window.wgPushPageFiles;

        if ( $('#checkIncTemplates').is(':checked') ) {
            files = files.concat( window.wgPushTemplateFiles );
        }

        if ( $('#checkIncSubpages').is(':checked') ) {
            files = files.concat( window.wgPushSubpagesFiles );
        }


        if ( files.length > 0 && $('#checkIncFiles').length != 0 && $('#checkIncFiles').is(':checked') ) {
			var currentFile = files.pop();
			button.innerHTML = mw.msg( 'push-button-pushing-files' );
			initiateImagePush( button, pages, targetUrl, targetName, files, currentFile );
		}
		else {
			initiatePush( button, pages, targetUrl, targetName );
		}
	}

	function initiateImagePush( sender, pages, targetUrl, targetName, images, fileName ) {
		$.post(
			mw.config.get( 'wgScriptPath' )  + '/api.php',
			{
				'action': 'pushimages',
				'format': 'json',
				'images': fileName,
				'targets': targetUrl
			},
			function( data ) {
				var fail = false;

				if ( data.error ) {
					data.error.info = mw.msg( 'push-tab-err-filepush', data.error.info );
					handleError( sender, targetUrl, data.error );
					fail = true;
				}
				else {
					for ( i in data ) {
						if ( data[i].error ) {
                            if(data[i].error.code !== "fileexists-no-change"){
                                data[i].error.info = mw.msg( 'push-tab-err-filepush', data[i].error.info );
                                handleError( sender, targetUrl, data[i].error );
                                fail = true;
                                break;
                            }
						}
						else if ( !data[i].upload ) {
							data[i].error.info = mw.msg( 'push-tab-err-filepush-unknown' );
							handleError( sender, targetUrl, data[i].error );
							fail = true;
							break;
						}
					}
				}

				if ( !fail ) {
					if ( images.length > 0 ) {
						var currentFile = images.pop();
						initiateImagePush( sender, pages, targetUrl, targetName, images, currentFile );
					}
					else {
						initiatePush( sender, pages, targetUrl, targetName );
					}
				}
			}
			, "json");
	}

	function reEnableButton( button, targetUrl, targetName ) {
		button.innerHTML = mw.msg( 'push-button-text' );
		button.disabled = false;

		getRemoteArticleInfo( $(button).attr( 'targetid' ), $(button).attr( 'pushtarget' ) );

		var pushAllButton = $('#push-all-button');

		// If there is a "push all" button, make sure to reset it
		// when all other buttons have been reset.
		if ( typeof pushAllButton !== "undefined" ) {
			var hasDisabled = false;

			$.each($(".push-button"), function(i,v) {
				if ( v.disabled ) {
					hasDisabled = true;
				}
			});

			if ( !hasDisabled ) {
				pushAllButton.attr( "disabled", false );
				pushAllButton.text( mw.msg( 'push-button-all' ) );
			}
		}
	}

	function handleError( sender, targetUrl, error ) {
		var errorDiv = $( '#targeterrors' + $(sender).attr( 'targetid' ) );

		if ( error.code && error.code == 'uploaddisabled' ) {
			error.info = mw.msg( 'push-tab-err-uploaddisabled' );
		}

		errorDiv.text( error.info );
		errorDiv.fadeIn( 'slow' );

		sender.innerHTML = mw.msg( 'push-button-failed' );
		setTimeout( function() {reEnableButton( sender );}, 2500 );
	}

} ); })( jQuery );
