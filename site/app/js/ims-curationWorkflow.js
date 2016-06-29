
/**
 * Curation Workflow is a plugin used to create a full curation interface with
 * the ability to create and display additional plugins such as CurationBlock
 * and OntologySelector.
 */
 
;(function( yourcode ) {

	yourcode( window.jQuery, window, document );

} (function( $, window, document ) {
		
	$.curationWorkflow = function( el, options ) {
	
		var base = this;
		base.$el = $(el);
		base.el = el;
		
		base.data = { 
			id: base.$el.attr( "id" ),
			baseURL: $("head base").attr( "href" )
		};
		
		base.components = {
			curationTypeSelect: $("#curationType"),
			curationInterface: $("#curationInterface"),
			addSubAttributePopup: "",
			addChecklistItemPopup: "",
			submitWorkflowBtn: $("#submitCurationWorkflowBtn"),
			curationWorkflow: $("#curationWorkflow")
		};
		
		base.init = function( ) {
			base.options = $.extend( {}, $.curationWorkflow.defaultOptions, options );
			
			base.initializeCurationTypeDropdown( );
			
			base.$el.on( "click", ".workflowLink", function( ) {
				base.clickWorkflowLink( $(this) );
			});
			
			base.$el.on( "click", "#submitCurationWorkflowBtn", function( ) {
				base.clickSubmitBtn( );
			});

		};
		
		base.clickSubmitBtn = function( ) {
			var isValidated = false;
			$("#curationChecklist").find( ".activityIcons" ).each( function( index, element ) {
				var blockStatus = $(element).data( "status" );
				console.log( blockStatus );
				if( blockStatus == "VALID" || blockStatus == "WARNING" ) {
					isValidated = true;
				}
			});
			
			if( isValidated ) {
				console.log( "ALL ARE VALIDATED" );
			} else {
				console.log( "SOME ARE INVALID" );
			}
		};
		
		// Process functionality of clicking on a workflow link
		base.clickWorkflowLink = function( link ) {
			
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
			
			base.loadCurationBlock( link );
		};
		
		// Load a curation block into the curation workflow
		base.loadCurationBlock = function( link ) {
			
			var dataAttribs = link.data( );
			dataAttribs['curationType'] = base.components.curationTypeSelect.val( );
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
					
					url: base.data.baseURL + "/scripts/curation/Workflow.php",
					method: "POST",
					dataType: "html",
					data: dataAttribs
					
				}).done( function(data) {
					
					$("#curationWorkflow").append(data);
					var curationBlock = $("#" + dataAttribs['blockid']).curationBlock( {}, base );
					base.setupAddChecklistSubItemButton( );
					
					var ontSelect = $("#" + dataAttribs['blockid'] + " .ontologySelector");
					if( ontSelect.length ) {
						var options = ontSelect.data( );
						ontSelect.ontologySelector( options, curationBlock.data('curationBlock') );
					}
					
				});
				
			}
			
		};
		
		// Setup the curation checklist functionality
		// so it can be correctly interacted with
		base.setupCurationChecklist = function( ) {
			var firstChecklistItem = $(".workflowLink:first");
			base.clickWorkflowLink( firstChecklistItem );
		};
		
		// Initialize the basic structure of a curation workflow and populate
		// the checklist for workflow navigation
		base.initializeCurationWorkflow = function( ) {
		
			$("#curationMenu > .list-group").affix( );
			
			$(".curationBlock").each( function( i, val ) {
				var cp = $(this).curationBlock({});
				cp.data('curationBlock').clickMe( );
			});
			
			base.setupCurationChecklist( );
			base.setupAddChecklistItemButton( );
			base.setupAddChecklistItemSubmitButton( );
			base.setupAddChecklistSubItemButton( );
			base.setupChecklistSubItemSubmitButton( );
			
		};
			
		// Setup the curation type dropdown in the top right corner
		base.initializeCurationTypeDropdown = function( ) {
			
			base.components.curationTypeSelect.change( function( ) {
				
				var ajaxData = {
					type: base.components.curationTypeSelect.val( ), 
					script: 'loadCurationChecklist'
				};
				
				$.ajax({
					
					url: base.data.baseURL + "/scripts/curation/Workflow.php",
					method: "POST",
					dataType: "html",
					data: ajaxData
					
				}).done( function(data) {
					
					base.components.curationInterface.html(data);
					base.initializeCurationWorkflow( );
					
				});
				
			});
		};
		
		// Setup a checklist item popup
		// that lets you select a new item to add
		base.setupAddChecklistItemButton = function( ) {
					
			base.components.addChecklistItemPopup = $("#addNewChecklistItem").qtip({
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
			
		};
		
		// Setup the functionality of the submit button
		// in the add checklist item popup
		base.setupAddChecklistItemSubmitButton = function( ) {
			
			$("body").on( "click", "#fullAttributeSubmit", function( ) {
					
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
						
						url: base.data.baseURL + "/scripts/curation/Workflow.php",
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
						base.components.addChecklistItemPopup.qtip( 'hide' );
						base.clickWorkflowLink( $("#workflowLink-block-" + data['show']) );
						
					});
					
				} else {
					
					// Otherwise, simply show the one that 
					// already exists
					
					base.components.addChecklistItemPopup.qtip( 'hide' );
					base.clickWorkflowLink( linkToShow );
				}
				
			});
		
		};
		
		// Setup a checklist subitem popup
		// that lets you select a new subitem to add
		base.setupAddChecklistSubItemButton = function( ) {
					
			base.components.addSubAttributePopup = $(".addSubAttribute").qtip({
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
			
		};
		
		// Setup a checklist subitem submit button
		base.setupChecklistSubItemSubmitButton = function( ) {
			
			$("body").on( "click", "#subAttributeSubmit", function( ) {
					
				var form = $(this).parent( );
				
				var ajaxData = {
					selected: form.find( ".attributeAddSelect" ).val( ),
					parent: form.find( ".subAttributeParent" ).val( ),
					parentName: form.find( ".subAttributeParentName" ).val( ),
					subCount: form.find( ".subAttributeCount" ).val( ),
					blockCount: $("#checklistBlockCount").val( ),
					script: 'appendChecklistSubItem'
				};
			
				$.ajax({
					
					url: base.data.baseURL + "/scripts/curation/Workflow.php",
					method: "POST",
					dataType: "json",
					data: ajaxData
					
				}).done( function(data) {
					
					$("#workflowSubLink-" + ajaxData['parent']).parent( ).before( data['checklist'] );
					$("#" + ajaxData['parent'] + " .curationErrors").before( data['body'] );
					
					$("#workflowSubLink-" + ajaxData['parent']).data( "subcount", data['subCount'] )
					
					base.components.addSubAttributePopup.qtip( 'hide' );
					base.clickWorkflowLink( $("#workflowLink-" + ajaxData['parent']) );
					
				});
				
			});
			
		};

		base.init( );
	
	};

	$.curationWorkflow.defaultOptions = { };

	$.fn.curationWorkflow = function( options ) {
		return this.each( function( ) {
			(new $.curationWorkflow( this, options ));
		});
	};
		
}));