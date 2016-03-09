
/**
 * Dataset Javascript Bindings that apply
 * to the dataset specific pages of the site.
 */
 
 (function( yourcode ) {

	yourcode( window.jQuery, window, document );

} (function( $, window, document ) {

	$(function( ) {
		initializeUI( );
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
				$(".curationInterface").html(data);
			});
			
		});
	}
	
	/**
	 * Curation Panel is a common object used to wrap a curation interface item
	 * that has several common components shared between all of them such as an
	 * error panel and the ability to expand to add additional fields
	 */
	 
	function curationPanel( options ) {
		
		this.type = options.type;
		this.display = function( ) {
			alert( this.type );
		};
		
	}

}));