
/**
 * Core Javascript Bindings that apply
 * to the entirety of the site.
 */
 
(function( yourcode ) {

	yourcode( window.jQuery, window, document );

} (function( $, window, document ) {

	$(function( ) {
		initializeUIComponents( );
	});
	
	function initializeUIComponents( ) {
		
		$("#groupSelect").on( "change", function( ) {
			$.ajax({
				url: "scripts/ExecuteProcess.php",
				method: "POST",
				dataType: "json",
				data: { script: "switchGroup", id: $(this).val( ) }
			}).done( function(data) {
				if( !$.isEmptyObject( data ) ) {
					$(".groupName").html( data.NAME );
				}
			});
		});
		
	}

}));