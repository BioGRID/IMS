
/**
 * Dataset Javascript Bindings that apply
 * to the dataset specific pages of the site.
 */
 
 (function( yourcode ) {

	yourcode( window.jQuery, window, document );

} (function( $, window, document ) {

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

}));