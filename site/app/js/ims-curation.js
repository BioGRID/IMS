
/**
 * Dataset Javascript Bindings that apply
 * to the dataset specific pages of the site.
 */
 
 (function( yourcode ) {

	yourcode( window.jQuery, window, document );

} (function( $, window, document ) {
	
	var fieldID = 1;

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
		$(".workflowLink").not(link).parent( ).find( ".curationSubmenu" ).slideUp( 'fast' );
		link.parent( ).find( ".curationSubmenu" ).slideDown( 'fast' );
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
				data: { parent: parentPanel, selected: selectVal, field: fieldID  }
			}).done( function(data) {
				$('#' + parentPanel + ' > .panel-body').append( data );
			});
			
			fieldID++;
			
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
			
			$.ajax({
				url: baseURL + "/scripts/AppendChecklistItem.php",
				method: "POST",
				dataType: "json",
				data: { selected: selectVal, field: fieldID, blockCount: blockCount, partCount: partCount }
			}).done( function(data) {
				$('#curationChecklist').append( data['view'] );
				$("#checklistBlockCount").val( data['blockCount'] );
				$("#checklistPartCount").val( data['partCount'] );
				addItemPopup.qtip( 'hide' );
				
				clickWorkflowLink( $("#workflowLink-block-" + data['show']) );
			});
			
		});
	
	}

}));