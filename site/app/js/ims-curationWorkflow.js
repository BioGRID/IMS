
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
			baseURL: $("head base").attr( "href" ),
			curationBlocks: [],
			submissionOps: [ "init", "validateblocks", "annotate", "validatesubmission", "build", "insert", "complete" ],
			submissionOpTitles: {
				"init" : "Initializing Submission Process...",				
			    "validateblocks" : "Validating Data in Curation Blocks...",
				"annotate" : "Annotating Records...",
				"validatesubmission" : "Validating Overall Dataset...",
				"build" : "Building Database Records...",
				"insert" : "Inserting Records to Database...",
				"complete" : "Submission Process Complete!"
			}
		};
		
		base.components = {
			curationTypeSelect: $("#curationType"),
			curationInterface: $("#curationInterface"),
			addSubAttributePopup: "",
			addChecklistItemPopup: "",
			curationWorkflow: $("#curationWorkflow"),
			submitBtn: "",
			addChecklistItemBtn: ""
		};
		
		base.init = function( ) {
			base.options = $.extend( {}, $.curationWorkflow.defaultOptions, options );
			
			base.initializeCurationTypeDropdown( );
			
			base.$el.on( "click", ".workflowLink", function( ) {
				if( base.components.submitBtn.is( ":disabled" ) ) {
					return false;
				} else {
					base.clickWorkflowLink( $(this) );
				}
			});
			
			base.$el.on( "click", "#submitCurationWorkflowBtn", function( ) {
				base.clickSubmitBtn( );
			});
			
			base.$el.on( "click", "#curationWorkflowErrorBtn", function( ) {
				base.showWorkflowDetails( );
			});

		};
		
		base.showWorkflowDetails = function( ) {
			$(".workflowLink").parent( ).removeClass( "active" ).find( ".curationSubmenu" ).slideUp( 'fast' );
			
			// Hide currently showing curation panel
			var closingBlock = $(".curationBlock:visible").hide( ).attr( "id" );
			if( closingBlock != undefined ) {
				$("#" + closingBlock).data( 'curationBlock' ).hideBlock( );
			}
			
			// Show Workflow Errors
			$("#curationWorkflowDetails").show( );
		};
		
		base.toggleSubmitBtn = function( enableBtn ) {
			
			if( enableBtn ) {
				base.components.submitBtn.prop( "disabled", false );
				base.components.submitBtn.find( ".submitCheck" ).show( );
				base.components.submitBtn.find( ".submitProgress" ).hide( );
				base.components.addChecklistItemBtn.prop( "disabled", false );
				base.components.curationTypeSelect.prop( "disabled", false );
			} else {
				base.components.submitBtn.prop( "disabled", true );
				base.components.submitBtn.find( ".submitCheck" ).hide( );
				base.components.submitBtn.find( ".submitProgress" ).show( );
				base.components.addChecklistItemBtn.prop( "disabled", true );
				base.components.curationTypeSelect.prop( "disabled", true );
			}
			
		};
		
		base.clickSubmitBtn = function( ) {
			
			base.toggleSubmitBtn( false );
			$("#curationSubmitNotifications").html( "" ); 
			
			var allValidated = true;
			var curationBlockCount = base.data.curationBlocks.length;
			var invalidBlocks = [];
			var promises = [];
			
			for( var i = 0; i < curationBlockCount; i++ ) {
				var blockStatus = base.data.curationBlocks[i].data( "status" );
				
				// If blocks are new, run the validate process on them and
				// create a promise to wait until that validation completes
				if( blockStatus == "NEW" ) {
					var blockStatus = base.data.curationBlocks[i].data( "status" );
					promises.push( base.data.curationBlocks[i].data( "curationBlock" ).clickValidateBtn( ) );
				} 
				
			}
			
			$.when.apply( $, promises ).done( function( ) {
			
				for( var i = 0; i < curationBlockCount; i++ ) {
					var blockStatus = base.data.curationBlocks[i].data( "status" );
					
					// Block is not validated
					if( blockStatus == "ERROR" || blockStatus == "NEW" || blockStatus == "PROCESSING" ) {
						invalidBlocks.push( base.data.curationBlocks[i].data( "name" ) );
						allValidated = false;
					}
				}
				
				var ajaxData = {
					"validationStatus" : allValidated,
					"invalidBlocks" : JSON.stringify( invalidBlocks ),
					"curationType" : $("#curationType").val( ),
					"curationCode" : $("#curationCode").val( ),
					"datasetID" : $("#datasetID").val( ),
					"script" : 'submitCuratedDataset'
				};

				var notificationPromise = base.showSubmitNotification( "SUBMIT" );
				
				notificationPromise.done( function( ) {
					
					$("#curationWorkflowResults").html( base.fetchProgressHeader( "init", "running" ));
					
					var progressCheck = setInterval( function( ) {
						base.updateProgress( ajaxData['curationCode'], "running" );
					}, 3000);
					
					$.ajax({
						
						url: base.data.baseURL + "/scripts/curation/Workflow.php",
						method: "POST",
						dataType: "json",
						data: ajaxData,
						beforeSend: function( ) {
							$(".curationWorkflowDetails").html( "" );
							base.showWorkflowDetails( );
						}
						
					}).done( function(data) {
						
						console.log( data );
						clearInterval( progressCheck );
						
						var progressUpdatePromise = base.updateProgress( ajaxData['curationCode'], data["STATUS"].toLowerCase( ) );
						progressUpdatePromise.always( function( ) {
						
							base.toggleSubmitBtn( true );
							if( data["STATUS"] == "SUCCESS" ) {
								console.log( "Submitted Successfully!" );
								
								base.showSubmitNotification( "SUCCESS" );
								base.showWorkflowDetails( );
								
							} else {
								
								$("#curationWorkflowResults").append( data["ERRORS"] );
								
								base.showSubmitNotification( "ERROR" );
								base.showWorkflowDetails( );
							}
							
						});
						
					}).fail( function( jqXHR, textStatus ) {
						clearInterval( progressCheck );
						base.updateProgress( ajaxData['curationCode'], "error" );
						console.log( textStatus );
						base.toggleSubmitBtn( true );
					});
				});
				
			}).fail( function( jqXHR, textStatus ) {
				console.log( textStatus );
				base.toggleSubmitBtn( true );
			});
			
		};
		
		// Update Curation Submission Progress
		base.updateProgress = function( curCode, status ) {
			
			return $.ajax({
				
				url: base.data.baseURL + "/scripts/curation/Progress.php",
				method: "POST",
				dataType: "json",
				data: { curationCode: curCode, "script" : "updateSubmissionProgress" }
				
			}).done( function( data ) {
				
				// Step through all operations and print them out in
				// order based on where we are now and where we were
				// last time
				currentOp = data['PROGRESS'];
				
				// Step through each op one by one, until we find the last
				// one we printed out
				for( var i = 0; i < base.data.submissionOps.length; i++ ) {
					
					// Print out the new headers
					style = "success";
					if( base.data.submissionOps[i] == currentOp ) {
						if( status == "success" ) {
							style = "success";
						} else if( status == "error" ) {
							style = "error";
						} else {
							style = "running";
						}
					}
					
					if( i == 0 ) {
						$("#curationWorkflowResults").html( base.fetchProgressHeader( base.data.submissionOps[i], style ));
					} else {
						$("#curationWorkflowResults").append( base.fetchProgressHeader( base.data.submissionOps[i], style ));
					}
					
					// Once we find where we're at, we stop
					if( base.data.submissionOps[i] == currentOp ) {
						break;
					}
				}
				
			});
			
		};
		
		// Grab a formatted progress header
		base.fetchProgressHeader = function( progressOp, style ) {
			
			var progressHeader = "<i class='fa fa-lg fa-angle-double-right'></i>";
			var progressClass = "progressDefault";
			
			if( style == "running" ) {
				progressHeader = progressHeader + "<i class='pull-right fa fa-spinner fa-pulse fa-lg fa-fw'></i> ";
				progressClass = "progressRunning";
			} else if( style == "success" ) {
				progressHeader = progressHeader + "<i class='pull-right fa fa-lg fa-check-circle'></i> ";
				progressClass = "progressSuccess";
			} else if( style == "error" ) {
				progressHeader = progressHeader + "<i class='pull-right fa fa-lg fa-exclamation-circle'></i> ";
				progressClass = "progressError";
			} 
			
			progressHeader = "<h3 class='progressHeader " + progressClass + "'>" + progressHeader + base.data.submissionOpTitles[progressOp] + "</h3>";
			return progressHeader;
		};
		
		// Generate a standard submission notification
		base.showSubmitNotification = function( notificationType ) {
			
			return $.ajax({
				
				url: base.data.baseURL + "/scripts/curation/Workflow.php",
				method: "POST",
				dataType: "html",
				data: { "notificationType" : notificationType, "script" : "loadWorkflowNotification" },
				beforeSend: function( ) {
					$("#curationSubmitNotifications").html( "" );
				}
				
			}).done( function(data) {
				$("#curationSubmitNotifications").html( data );
			});
			
		};
		
		
		// Process functionality of clicking on a workflow link
		base.clickWorkflowLink = function( link ) {
			
			$(".workflowLink").not(link).parent( ).removeClass( "active" ).find( ".curationSubmenu" ).slideUp( 'fast' );
			$("#curationWorkflowDetails").hide( );
			
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
			
			// Hide currently showing curation panel
			var closingBlock = $(".curationBlock:visible").hide( ).attr( "id" );
			if( closingBlock != undefined ) {
				$("#" + closingBlock).data( 'curationBlock' ).hideBlock( );
			}
			
			var blockID = link.data( "blockid" );
			if( $("#" + blockID).length ) {
				$("#" + blockID).show( );
			} else {
				$.when( base.loadCurationBlock(link) ).done( function( ) {
					$("#" + blockID).show( );
				});
			}
		};
		
		// Load a curation block into the curation workflow
		base.loadCurationBlock = function( link ) {
			
			var dataAttribs = link.data( );
			dataAttribs['curationType'] = base.components.curationTypeSelect.val( );
			dataAttribs['blockName'] = link.html( );
			dataAttribs['script'] = 'loadCurationBlock';
				
			return $.ajax({
				
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
				
				base.data.curationBlocks.push( curationBlock );
				
			});
			
		};
		
		// Setup the curation checklist functionality
		// so it can be correctly interacted with
		base.setupCurationChecklist = function( ) {
			
			var promises = [];
			$(".workflowLink").each( function( index, element ) {
				promises.push( base.loadCurationBlock( $(element) ));
			});
			
			$.when.apply( $, promises ).done( function( ) {
				var firstChecklistItem = $(".workflowLink:first");
				base.clickWorkflowLink( firstChecklistItem );
				
				base.components.submitBtn = $("#submitCurationWorkflowBtn");
				base.components.addChecklistItemBtn = $("#addNewChecklistItem");
			});
			
		};
		
		// Initialize the basic structure of a curation workflow and populate
		// the checklist for workflow navigation
		base.initializeCurationWorkflow = function( ) {
		
			$("#curationMenu > .list-group").affix( );
			
			// THIS NEEDS TO BE REMOVED, DOES NOTHING
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