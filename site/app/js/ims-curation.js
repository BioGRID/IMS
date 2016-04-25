
/**
 * Dataset Javascript Bindings that apply
 * to the dataset specific pages of the site.
 */
 
 (function( yourcode ) {

	yourcode( window.jQuery, window, document );

} (function( $, window, document ) {
	
	var checklistItems = [];

	$(function( ) {
		initializeUI( );
		initCurationBlock( );
	});
	
	/**
	 * Initialize curation specific UI components
	 */
		
	function initializeUI( ) {
		initializeCurationTypeDropdown( );
		initializeWorkflowLinks( );
	}
	
	/**
	 * Setup the curation type dropdown in the top right corner
	 */
	 
	function initializeCurationTypeDropdown( ) {
		
		$("#curationType").change( function( ) {
			
			var baseURL = $("head base").attr( "href" );
			
			var ajaxData = {
				type: $(this).val( ), 
				script: 'loadCurationChecklist'
			};
			
			$.ajax({
				
				url: baseURL + "/scripts/curation/Workflow.php",
				method: "POST",
				dataType: "html",
				data: ajaxData
				
			}).done( function(data) {
				
				$("#curationInterface").html(data);
				initializeCurationWorkflow( );
				
			});
			
		});
	}
	
	/**
	 * Setup workflow link clickability
	 */
	
	function initializeWorkflowLinks( ) {
		$("#section-curation").on( "click", ".workflowLink", function( ) {
			clickWorkflowLink( $(this) );
		});
	}
	
	/**
	 * Initialize the basic structure of a curation workflow and populate
	 * the checklist for workflow navigation
	 */
	
	function initializeCurationWorkflow( ) {
		
		$("#curationMenu > .list-group").affix( );
		
		$(".curationBlock").each( function( i, val ) {
			var cp = $(this).curationBlock({});
			cp.data('curationBlock').clickMe( );
		});
		
		setupCurationChecklist( );
		setupAddChecklistItemButton( );
		setupAddChecklistSubItemButton( );
		
	}
	
	/**
	 * Setup the curation checklist functionality
	 * so it can be correctly interacted with
	 */
	 
	function setupCurationChecklist( ) {
		var firstChecklistItem = $(".workflowLink:first");
		clickWorkflowLink( firstChecklistItem );
	}
	
	/**
	 * Process functionality of clicking on a workflow link
	*/
	
	function clickWorkflowLink( link ) {
		
		$(".workflowLink").not(link).parent( ).removeClass( "active" ).find( ".curationSubmenu" ).slideUp( 'fast' );
		
		// Only Participants have Submenus
		
		var block = link.data( "block" );
		var listItem = link.parent( );
		
		if( block == "participant" ) {
			
			listItem
				.addClass( "active" )
				.find( ".curationSubmenu" )
				.slideDown( 'fast' );
				
		} else {
			
			listItem.addClass( "active" );
				
		}
		
		loadCurationBlock( link );
	}
	
	/**
	 * Load a curation block into the curation workflow
	 */
	 
	function loadCurationBlock( link ) {
		
		var dataAttribs = link.data( );
		var baseURL = $("head base").attr( "href" );
		var curationType = $("#curationType").val( );
		dataAttribs['curationType'] = curationType;
		dataAttribs['blockName'] = link.html( );
		dataAttribs['script'] = 'loadCurationBlock';
		
		// Hide currently showing curation panel
		var closingBlock = $(".curationBlock:visible").hide( ).attr( "id" );
		if( closingBlock != undefined ) {
			$("#" + closingBlock).data( 'curationBlock' ).hideBlock( );
		}
		
		if( $("#" + dataAttribs['blockid']).length ) {
			
			// Show the one we clicked instead of reloading
			// the code
			
			$("#" + dataAttribs['blockid']).show( );
			
		} else {
			
			// Haven't loaded this one yet, load it via
			// ajax into the form
			
			$.ajax({
				
				url: baseURL + "/scripts/curation/Workflow.php",
				method: "POST",
				dataType: "html",
				data: dataAttribs
				
			}).done( function(data) {
				
				$("#curationWorkflow").append(data);
				$("#" + dataAttribs['blockid']).curationBlock( {} );
				
			});
			
		}
		
	}
	
	/**
	 * Curation Block is a plugin used to grant a curation interface item
	 * that has several common components shared between all of them such as an
	 * error panel and the ability to expand to add additional fields
	 */
	 
	function initCurationBlock( ) {
		
		$.curationBlock = function( el, options ) {
			
			var base = this;
			base.$el = $(el);
			base.el = el;
			
			var timer;
			
			base.data = { 
				id: base.$el.attr( "id" ),
				type: base.$el.data( "type" ),
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
			base.components.errorList = base.components.errorBox.find( ".curationErrorList" )
			
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
		
	}
	
	/**
	 * Setup a checklist item popup
	 * that lets you select a new item to add
	 */
	 
	function setupAddChecklistItemButton( ) {
				
		var addItemPopup = $("#addNewChecklistItem").qtip({
			overwrite: false,
			content: {
				text: function( event, api ) {
					return $("#fullAttributeHTML").html( );
				},
				title: {
					text: "<strong>Choose New Item</strong>",
					button: true
				}
			},
			style: {
				classes: 'qtip-bootstrap',
				width: '250px'
			},
			position: {
				my: 'right center',
				at: 'left center'
			},
			show: {
				event: "click",
				solo: true
			},
			hide: {
				delay: 1000,
				fixed: true,
				leave: false
			}
		}, event);
		
		$("body").on( "click", "#fullAttributeSubmit", function( ) {
				
			var baseURL = $("head base").attr( "href" );
			
			var ajaxData = {
				selected: $(this).parent( ).find( ".attributeAddSelect" ).val( ),
				blockCount: $("#checklistBlockCount").val( ),
				partCount: $("#checklistPartCount").val( ),
				script: 'appendChecklistItem'
			};
			
			// Check to see if this attribute is already in the
			// checklist, no need to add the same attribute twice
			
			var itemExists = false;
			var linkToShow = "";
			$(".workflowLink").each( function( i, val ) {
				var linkData = $(this).data( );
				if( linkData['block'] == 'attribute' && linkData['type'] == ajaxData['selected'] ) {
					itemExists = true;
					linkToShow = $(this);
					return false;
				}
			});
			
			if( !itemExists ) {
				
				// If the item doesn't exist, create it
				// and append it to the right spot
			
				$.ajax({
					
					url: baseURL + "/scripts/curation/Workflow.php",
					method: "POST",
					dataType: "json",
					data: ajaxData
					
				}).done( function(data) {
					
					// If it's a new Participant, append it after participants
					// rather than to the end
					
					if( ajaxData['selected'] == "participant" ) {
						var lastPart = $("#lastParticipant").val( );
						$("#" + lastPart).parent( ).after( data['view'] );
						$("#lastParticipant").val( "workflowLink-block-" + data['show'] );
					} else {
						$('#curationChecklist').append( data['view'] );
					}
					
					$("#checklistBlockCount").val( data['blockCount'] );
					$("#checklistPartCount").val( data['partCount'] );
					addItemPopup.qtip( 'hide' );
					clickWorkflowLink( $("#workflowLink-block-" + data['show']) );
					
				});
				
			} else {
				
				// Otherwise, simply show the one that 
				// already exists
				
				addItemPopup.qtip( 'hide' );
				clickWorkflowLink( linkToShow );
			}
			
		});
	
	}
	
	/**
	 * Setup a checklist subitem popup
	 * that lets you select a new subitem to add
	 */
	 
	function setupAddChecklistSubItemButton( ) {
				
		var addItemPopup = $(".addSubAttribute").qtip({
			overwrite: false,
			content: {
				text: function( event, api ) {
					$(".subAttributeCount").val( $(this).data( "subcount" ) );
					$(".subAttributeParent").val( $(this).data( "parentblockid" ) );
					$(".subAttributeParentName").val( $(this).data( "parenttitle" ) );
					return $("#subAttributeHTML").html( );
				},
				title: {
					text: "<strong>Choose New Sub Attribute</strong>",
					button: true
				}
			},
			style: {
				classes: 'qtip-bootstrap',
				width: '250px'
			},
			position: {
				my: 'right center',
				at: 'left center'
			},
			show: {
				event: "click",
				solo: true
			},
			hide: {
				delay: 1000,
				fixed: true,
				leave: false
			}
		}, event);
		
		$("body").on( "click", "#subAttributeSubmit", function( ) {
				
			var form = $(this).parent( );
			var baseURL = $("head base").attr( "href" );
			
			var ajaxData = {
				selected: form.find( ".attributeAddSelect" ).val( ),
				parent: form.find( ".subAttributeParent" ).val( ),
				parentName: form.find( ".subAttributeParentName" ).val( ),
				subCount: form.find( ".subAttributeCount" ).val( ),
				blockCount: $("#checklistBlockCount").val( ),
				script: 'appendChecklistSubItem'
			};
		
			$.ajax({
				
				url: baseURL + "/scripts/curation/Workflow.php",
				method: "POST",
				dataType: "json",
				data: ajaxData
				
			}).done( function(data) {
				
				$("#workflowSubLink-" + ajaxData['parent']).parent( ).before( data['checklist'] );
				$("#" + ajaxData['parent'] + " .curationErrors").before( data['body'] );
				
				$("#workflowSubLink-" + ajaxData['parent']).data( "subcount", data['subCount'] )
				
				addItemPopup.qtip( 'hide' );
				clickWorkflowLink( $("#workflowLink-" + ajaxData['parent']) );
				
			});
			
		});
		
	}

}));