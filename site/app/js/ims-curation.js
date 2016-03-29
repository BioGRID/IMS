
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
			var curationType = $(this).val( );
			
			$.ajax({
				
				url: baseURL + "/scripts/LoadCurationChecklist.php",
				method: "POST",
				dataType: "html",
				data: { type: curationType }
				
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
		setupParticipantAttributeLinks( );
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
		
		// Hide all currently showing curation panels
		$(".curationBlock").hide( );
			
		if( $("#" + dataAttribs['blockid']).length ) {
			
			// Show the one we clicked instead of reloading
			// the code
			
			$("#" + dataAttribs['blockid']).show( );
			
		} else {
			
			// Haven't loaded this one yet, load it via
			// ajax into the form
			
			$.ajax({
				
				url: baseURL + "/scripts/LoadCurationBlock.php",
				method: "POST",
				dataType: "html",
				data: dataAttribs
				
			}).done( function(data) {
				
				$("#curationWorkflow").append(data);
				
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
			
			base.$el.data( "curationBlock", base );
			
			base.init = function( ) {
				base.options = $.extend( {}, $.curationBlock.defaultOptions, options );
			};
			
			base.$el.click( function( ) {
				
			});
			
			base.clickMe = function( ) {
				//alert( "CLICK ME" );
			};
			
			base.init( );
			
		};
		
		$.curationBlock.defaultOptions = { };
		
		$.fn.curationBlock = function( options ) {
			return this.each( function( ) {
				(new $.curationBlock( this, options ));
			});
		};
		
	}
	
	/**
	 * Setup an attribute addition popup
	 * that lets you select a new attribute type to add
	 */
	 
	function setupParticipantAttributeLinks( ) {
			
		var parentPanel = $(".participantAddAttribute").closest( ".curationBlock" ).attr( "id" );
				
		var attributePopup = $(".participantAddAttribute").qtip({
			overwrite: false,
			content: {
				text: function( event, api ) {
					var attributeForm = "<select class='form-control participantAttributeSelect'><option value='alleles'>Alleles</option><option value='notes'>Notes</option></select><button type='button' data-parent='" + parentPanel + "' class='participantAttributeSubmit btn btn-success btn-block marginTopSm'>ADD <i class='fa fa-lg fa-plus-square-o'></i></button>";
					return attributeForm;
				},
				title: {
					text: "<strong>Add Attribute</strong>",
					button: true
				}
			},
			style: {
				classes: 'qtip-bootstrap',
				width: '250px'
			},
			position: {
				my: 'bottom center',
				at: 'top center'
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
		
		$("body").on( "click", ".participantAttributeSubmit", function( ) {
				
			var selectVal = $(this).parent( ).find( ".participantAttributeSelect" ).val( );
			var datasetID = $("#datasetID").val( );
			var baseURL = $("head base").attr( "href" );
			var parentPanel = $(this).data( "parent" );
			
			$.ajax({
				
				url: baseURL + "/scripts/AppendCurationWorkflow.php",
				method: "POST",
				dataType: "html",
				data: { parent: parentPanel, selected: selectVal }
				
			}).done( function(data) {
				
				$('#' + parentPanel + ' > .panel-body').append( data );
				
			});
			
		});
	
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
				
			var selectVal = $(this).parent( ).find( ".attributeAddSelect" ).val( );
			var datasetID = $("#datasetID").val( );
			var baseURL = $("head base").attr( "href" );
			var blockCount = $("#checklistBlockCount").val( );
			var partCount = $("#checklistPartCount").val( );
			
			// Check to see if this attribute is already in the
			// checklist, no need to add the same attribute twice
			
			var itemExists = false;
			var linkToShow = "";
			$(".workflowLink").each( function( i, val ) {
				var linkData = $(this).data( );
				if( linkData['block'] == 'attribute' && linkData['type'] == selectVal ) {
					itemExists = true;
					linkToShow = $(this);
					return false;
				}
			});
			
			if( !itemExists ) {
				
				// If the item doesn't exist, create it
				// and append it to the right spot
			
				$.ajax({
					
					url: baseURL + "/scripts/AppendChecklistItem.php",
					method: "POST",
					dataType: "json",
					data: { selected: selectVal, blockCount: blockCount, partCount: partCount }
					
				}).done( function(data) {
					
					// If it's a new Participant, append it after participants
					// rather than to the end
					
					if( selectVal == "participant" ) {
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
			var selectVal = form.find( ".attributeAddSelect" ).val( );
			var parentBlockID = form.find( ".subAttributeParent" ).val( );
			var parentBlockName = form.find( ".subAttributeParentName" ).val( );
			var subCount = form.find( ".subAttributeCount" ).val( );
			var datasetID = $("#datasetID").val( );
			var baseURL = $("head base").attr( "href" );
			var blockCount = $("#checklistBlockCount").val( );
		
			$.ajax({
				
				url: baseURL + "/scripts/AppendChecklistSubItem.php",
				method: "POST",
				dataType: "json",
				data: { selected: selectVal, parent: parentBlockID, parentName: parentBlockName, blockCount: blockCount, subCount: subCount }
				
			}).done( function(data) {
				
				console.log( data );
				
				$("#workflowSubLink-" + parentBlockID).parent( ).before( data['checklist'] );
				$("#" + parentBlockID + " .curationErrors").before( data['body'] );
				
				$("#workflowSubLink-" + parentBlockID).data( "subcount", data['subCount'] )
				
				addItemPopup.qtip( 'hide' );
				clickWorkflowLink( $("#workflowLink-" + parentBlockID) );
				
			});
			
		});
		
	}

}));