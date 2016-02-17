
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
		setupDataTables( );
	}
	
	function setupDataTables( ) {
		
		var baseURL = $("head base").attr( "href" );
		
		$("#interactionTable").DataTable({
			processing: true,
			serverSide: true,
			searchDelay: 5000,
			ajax : {
				url: baseURL + "/scripts/LoadInteractions.php",
				type: 'POST'
			}
		});
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
					var availabilityForm = "<select class='form-control availability_select'><option value='public'>Public</option><option value='private'>Private</option><option value='website-only'>Website-Only</option></select><button type='button' class='availability_submit btn btn-success btn-block marginTopSm'>Submit</button>";
					return availabilityForm;
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