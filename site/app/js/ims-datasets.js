
/**
 * Dataset Javascript Bindings that apply
 * to the dataset specific pages of the site.
 */
 
 (function( yourcode ) {

	yourcode( window.jQuery, window, document );

} (function( $, window, document ) {

	$(function( ) {
		initializeDatasetUI( );
	});
		
	function initializeDatasetUI( ) {
		setupAvailabilitySwitch( );
	}
		
	function setupAvailabilitySwitch( ) {
		
		var availabilitySwitch = 
			$('#availabilitySwitch').webuiPopover({
				trigger: 'manual',
				placement: 'right',
				closeable: true,
				animation: 'pop',
				type: 'html',
				dismissable: true,
				title: 'Change Availability',
				content: function( ) {
					return $("#availability_form").html( );
				}
			});
			
		$("body").on( "click", ".availability_submit", function( ) {
			
			var selectVal = $(this).parent( ).find( ".availability_select" ).val( );
			var datasetID = $("#datasetID").val( );
			var baseURL = $("head base").attr( "href" );
			
			$.ajax({
				url: baseURL + "/scripts/ExecuteProcess.php",
				method: "POST",
				dataType: "html",
				data: { script: "switchAvailability", id: datasetID, value: selectVal }
			}).done( function(data) {
				$("#availabilitySwitch").html(data);
				availabilitySwitch.webuiPopover( 'toggle' );
			});
			
		});
		
		$(".datasetDetails").on( "click", "#availabilitySwitch", function( ) {
			availabilitySwitch.webuiPopover( 'toggle' );
		});
			
	}

}));