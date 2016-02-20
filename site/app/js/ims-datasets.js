
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
		setupSidebarLinks( );
		//setupDataTables( );
	}
	
	function setupSidebarLinks( ) {
		
		$(".datasetSidebar").on( "click", ".sidebarLink", function( ) {
			$(".sidebarLink").removeClass( "active" );
			$(this).addClass( "active" );
			var type = $(this).attr( "data-type" );
			
			$(".datasetSubsection").each( function( index ) {
				var sectionType = $(this).attr( "data-type" );
				if( sectionType != type ) {
					$(this).hide( );
				}
			});
			
			var section = $("#section-" + type);
			section.slideDown( 500 );
			setupDataTables( section );
			
		});
		
	}
	
	function setupDataTables( section ) {
		
		var baseURL = $("head base").attr( "href" );
		var sectionBody = section.find( '.section-body' );
		var sectionType = section.attr( "data-type" );
		var table = "#dataTable-" + sectionType;
		
		if( !$.fn.DataTable.isDataTable( table )) {
			
			$.ajax({
				
				url: baseURL + "/scripts/FormatTable.php",
				data: { type: sectionType },
				method: "POST",
				dataType: "json"
				
			}).done( function( results ) {
				
				$(table).DataTable({
					processing: true,
					serverSide: true,
					columns: results,
					order: [[7,'desc']],
					ajax : {
						url: baseURL + "/scripts/LoadInteractions.php",
						type: 'POST'
					}
				});
				
			});
				
		} 
		
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