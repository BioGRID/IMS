
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
		initCurationPanel( );
	});
	
	/**
	 * Initialize curation specific UI components
	 */
		
	function initializeUI( ) {
		initializeCurationTypeDropdown( );
		
		
	}
	
	/**
	 * Setup the curation type dropdown in the top right corner
	 */
	 
	function initializeCurationTypeDropdown( ) {
		$("#curationType").change( function( ) {
			var baseURL = $("head base").attr( "href" );
			var curationType = $(this).val( );
			
			$.ajax({
				url: baseURL + "/scripts/LoadCurationWorkflow.php",
				method: "POST",
				dataType: "html",
				data: { type: curationType }
			}).done( function(data) {
				$("#curationInterface").html(data);
				initializeCurationWorkflow( );
			});
			
		});
	}
	
	function initializeCurationWorkflow( ) {
		
		$("#curationMenu > .list-group").affix( );
		
		$(".curationPanel").each( function( i, val ) {
			var cp = $(this).curationPanel({});
			cp.data('curationPanel').clickMe( );
		});
		
		setupCurationChecklist( );
		setupParticipantAttributeLinks( );
		
	}
	
	/**
	 * Setup the curation checklist functionality
	 * so it can be correctly interacted with
	 */
	 
	function setupCurationChecklist( ) {
		
		$(".curationSubmenu:first").show( );
		
		$(".workflowLink").on( "click", function( ) {
			$(".workflowLink").not(this).parent( ).find( ".curationSubmenu" ).slideUp( 'fast' );
			$(this).parent( ).find( ".curationSubmenu" ).slideDown( 'fast' );
		})
		
	}
	
	/**
	 * Curation Panel is a plugin used to grant a curation interface item
	 * that has several common components shared between all of them such as an
	 * error panel and the ability to expand to add additional fields
	 */
	 
	function initCurationPanel( ) {
		
		$.curationPanel = function( el, options ) {
			
			var base = this;
			base.$el = $(el);
			base.el = el;
			
			base.$el.data( "curationPanel", base );
			
			base.init = function( ) {
				base.options = $.extend( {}, $.curationPanel.defaultOptions, options );
			};
			
			base.$el.click( function( ) {
				
			});
			
			base.clickMe = function( ) {
				//alert( "CLICK ME" );
			};
			
			base.init( );
			
		};
		
		$.curationPanel.defaultOptions = { };
		
		$.fn.curationPanel = function( options ) {
			return this.each( function( ) {
				(new $.curationPanel( this, options ));
			});
		};
		
	}
	
	/**
	 * Setup an attribute addition popup
	 * that lets you select a new attribute type to add
	 */
	 
	function setupParticipantAttributeLinks( ) {
			
		var parentPanel = $(".participantAddAttribute").closest( ".curationPanel" ).attr( "id" );
				
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

}));