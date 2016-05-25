
/**
 * Curation Block is a plugin used to grant a curation interface item
 * that has several common components shared between all of them such as an
 * error panel and the ability to expand to add additional fields
 */
 
;(function( yourcode ) {

	yourcode( window.jQuery, window, document );

} (function( $, window, document ) {
		
	$.curationBlock = function( el, options ) {
	
		var base = this;
		base.$el = $(el);
		base.el = el;
		
		var timer;
		
		base.data = { 
			id: base.$el.attr( "id" ),
			type: base.$el.data( "type" ),
			attribute: base.$el.data( "attribute" ),
			category: base.$el.data( "category" ),
			name: base.$el.data( "name" ),
			required: base.$el.data( "required" ),
			baseURL: $("head base").attr( "href" )
		};
		
		base.components = {
			removeBtn: base.$el.find( ".removeBlockBtn" ),
			validateBtn: base.$el.find( ".validateBlockBtn" ),
			checklistItem: $("#workflowLink-" + base.data.id).parent( ),
			errorBox: base.$el.find( ".curationErrors" )
		};
		
		base.components.activityIcons = base.components.checklistItem.find( ".activityIcons" );
		base.components.errorList = base.components.errorBox.find( ".curationErrorList" );
		
		base.$el.data( "status", "NEW" );
		base.$el.data( "curationBlock", base );
		
		base.init = function( ) {
			base.options = $.extend( {}, $.curationBlock.defaultOptions, options );
			base.initRemoveBtn( );
			base.initValidateBtn( );
			
			base.$el.on( "change", "select.changeField", function( ) {
				base.setStatus( "NEW" );
			});
			
			base.$el.on( "input", "textarea.changeField, input.changeField", function( ) {
				clearTimeout( timer );
				timer = setTimeout( function( ) { base.setStatus( "NEW" ); }, base.options.validateDelay );
			});
			
			base.$el.on( "click", ".lineReplace", function( ) {
				base.swapIdentifiers( $(this).data( "lines" ), $(this).data( "value" ), $(this).parent( ).find( ".statusMsg" ) );
			});
			
			base.$el.on( "click", ".closeError", function( ) {
				$(this).parent( ).remove( );
			});
			
			base.$el.on( "click", ".unknownReplaceSubmit", function( ) {
				var inputGroup = $(this).parent( ).parent( );
				var replacementVal = inputGroup.find( ".unknownReplaceField" ).val( );
				if( replacementVal.trim( ).length > 0 ) {
					base.swapIdentifiers( $(this).data( "lines" ), replacementVal, inputGroup.find( ".statusMsg" ) );
				}
			});
		};
		
		base.clickRemoveBtn = function( ) {
			var prevItem = base.components.checklistItem.prev( ).find( ".workflowLink" );
			clickWorkflowLink( prevItem );
			base.components.checklistItem.remove( );
			base.el.remove( );
		};
		
		base.setStatus = function( status ) {
			status = status.toUpperCase( );
			base.components.activityIcons.find( ".activityIcon" ).hide( );
			base.components.activityIcons.find( ".activityIcon" + status ).show( );
			base.$el.data( "status", status );
		};
		
		base.clickValidateBtn = function( ) {
			
			base.setStatus( "PROCESSING" );
			
			var ajaxData = $("#" + base.data.id + " :input").serializeArray( );
			ajaxData.push({name: 'curationCode', value: $("#curationCode").val( )});
			ajaxData.push({name: 'id', value: base.data.id});
			ajaxData.push({name: 'type', value: base.data.type});
			ajaxData.push({name: 'attribute', value: base.data.attribute});
			ajaxData.push({name: 'category', value: base.data.category});
			ajaxData.push({name: 'name', value: base.data.name});
			ajaxData.push({name: 'required', value: base.data.required});
				
			$.ajax({
				
				url: base.data.baseURL + "/scripts/curation/Validate.php",
				method: "POST",
				dataType: "json",
				data: ajaxData,
				beforeSend: function( ) {
					base.components.errorList.html( "" );
					base.components.errorBox.hide( );
				}
				
			}).done( function(results) {
				 
				console.log( results );
				base.components.errorList.html( results['ERRORS'] );
				
				if( results['STATUS'] == "ERROR" ) {
					base.setStatus( "ERROR" );
					base.components.errorBox.show( );
				} else if( results['STATUS'] == "WARNING" ) {
					base.setStatus( "WARNING" );
					base.components.errorBox.show( );
				} else {
					base.setStatus( "VALID" );
					base.components.errorBox.hide( );
				}
				
				// If participants, set the checklist item showing stats
				// on what was found to the correct count values
				
				if( base.data.type == "participant" && "COUNTS" in results ) {
					base.setCounts( results['COUNTS'] );
				}
				
			}).fail( function( jqXHR, textStatus ) {
				console.log( textStatus );
			});
			
		};
		
		base.setCounts = function( stats ) {
			base.components.checklistItem.find( ".validParticipants" ).html( stats["VALID"] );
			base.components.checklistItem.find( ".unknownParticipants" ).html( stats["UNKNOWN"] );
			base.components.checklistItem.find( ".ambiguousParticipants" ).html( stats["AMBIGUOUS"] );
		};
		
		base.swapIdentifiers = function( lines, identifier, msgOutput ) {
			
			var participantField = base.$el.find( ".participants" );
			var participantData = participantField.val( );
			
			$.ajax({
				url: base.data.baseURL + "/scripts/curation/Replace.php",
				method: "POST",
				dataType: "json",
				data: { data: participantData, lines: lines, identifier: identifier },
				beforeSend: function( ) {
					msgOutput.html( "" );
				}
				
			}).done( function(results) {
				participantField.val( results['REPLACEMENT'] );
				msgOutput.html( results['MESSAGE'] );
			});
			
		};
		
		base.initRemoveBtn = function( ) {
			base.components.removeBtn.on( "click", function( ) {
				base.clickRemoveBtn( );
			});
		};
		
		base.initValidateBtn = function( ) {
			base.components.validateBtn.on( "click", function( ) {
				base.clickValidateBtn( );
			});
		};
		
		base.hideBlock = function( ) {
			if( base.$el.data( "status" ) == "NEW" ) {
				base.clickValidateBtn( );
			} 
		};

		
		base.init( );
	
	};

	$.curationBlock.defaultOptions = { 
		validateDelay: 1200
	};

	$.fn.curationBlock = function( options ) {
		return this.each( function( ) {
			(new $.curationBlock( this, options ));
		});
	};
		
}));