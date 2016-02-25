
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
		setupHeaderCollapseToggle( );
		setupAvailabilitySwitch( );
		setupSidebarLinks( );
		//setupDataTables( );
	}
	
	function setupHeaderCollapseToggle( ) {
		$("#datasetDetailsToggle").on( "click", function( ) {
			var detailsWrap = $(this).parent( ).parent( ).find( ".datasetDetailsWrap" );
			
			if( detailsWrap.is( ":visible" ) ) {
				$(this).html( "<i class='fa fa-lg fa-angle-double-down'></i> Expand Dataset Details <i class='fa fa-lg fa-angle-double-down'></i>" );
				detailsWrap.slideUp( );
			} else {
				$(this).html( "<i class='fa fa-lg fa-angle-double-up'></i> Collapse Dataset Details <i class='fa fa-lg fa-angle-double-up'></i>" );
				detailsWrap.slideDown( );
			}
		});
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
			section.show( );
			setupDataTables( section );
			
		});
		
	}
	
	function setupDataTables( section ) {
		
		var baseURL = $("head base").attr( "href" );
		var sectionBody = section.find( '.section-body' );
		var sectionType = section.attr( "data-type" );
		var sectionActivated = section.attr( "data-activated" );
		var sectionDisabled = section.attr( "data-disabled" );
		var sectionCombined = section.attr( "data-combined" );
		var sectionStatus = $("#dataTable-" + sectionType + "-statusSelect").val( );
		var dsetID = $("#datasetID").val( );
		var table = "#dataTable-" + sectionType;
		
		if( !$.fn.DataTable.isDataTable( table )) {
			
			$.ajax({
				
				url: baseURL + "/scripts/FormatTable.php",
				data: { type: sectionType },
				method: "POST",
				dataType: "json"
				
			}).done( function( results ) {
				
				var datatable = $(table).DataTable({
					processing: true,
					serverSide: true,
					columns: results,
					pageLength: 1000,
					deferRender: true,
					order: [[7,'desc']],
					language: {
						processing: "Loading Data... <i class='fa fa-spinner fa-pulse fa-lg'></i>"
					},
					ajax : {
						url: baseURL + "/scripts/LoadInteractions.php",
						type: 'POST',
						data: function( d ) {  
							d.type = sectionType;
							d.datasetID = dsetID; 
							d.activated = sectionActivated;
							d.disabled = sectionDisabled;
							d.combined = sectionCombined; 
							d.status = $("#dataTable-" + d.type + "-statusSelect").val( );
						}
					},
					infoCallback: function( settings, start, end, max, total, pre ) {
						var subhead = section.find( '.dataTable-info' );
						subhead.html( pre );
					},
					dom : "<'row'<'col-sm-12'tr>><'row'<'col-sm-5'i><'col-sm-7'p>>"
				});
				
				initializeDatatableTools( datatable, sectionType );
				
			});
				
		} 
		
	}
	
	function initializeDatatableTools( datatable, sectionType ) {
		
		// SETUP Global Filter
		// By Button Click
		$("#dataTable-" + sectionType + "-submit").click( function( ) {
			datatableFilterGlobal( datatable, $("#dataTable-" + sectionType + "-filterTerm").val( ), true, false, sectionType ); 
		});
		
		// By Pressing the Enter Key
		$("#dataTable-" + sectionType + "-filterTerm").keyup( function( e ) {
			if( e.keyCode == 13 ) {
				datatableFilterGlobal( datatable, $(this).val( ), true, false, sectionType ); 
			}
		});
		
		// SETUP View Change Dropdown List
		$("#dataTable-" + sectionType + "-statusSelect").change( function( ) {
			datatable.ajax.reload( );
			setCheckAllButtonStatus( sectionType, status, "Check All", false );
		});
		
		// SETUP Check All Button
		$("#dataTable-" + sectionType + "-checkAll").click( function( ) {
			var statusText = $(this).attr( "data-status" );
			
			if( statusText == "check" ) {
				setCheckAllButtonStatus( sectionType, "uncheck", "Uncheck All", true );
			} else if( statusText == "uncheck" ) {
				setCheckAllButtonStatus( sectionType, "check", "Check All", false );
			}
			
		});
	
	}
	
	function setCheckAllButtonStatus( sectionType, statusText, statusHTML, propVal ) {
		$("#dataTable-" + sectionType + " :checkbox").prop( "checked", propVal );
		$("#dataTable-" + sectionType + "-checkAll").attr( "data-status", statusText );
		$("#dataTable-" + sectionType + "-checkAll > .checkButtonText").html( statusHTML );
	}
	
	function datatableFilterGlobal( datatable, filterVal, isRegex, isSmartSearch, sectionType ) {
		datatable.search( filterVal, isRegex, isSmartSearch, true ).draw( );
		setCheckAllButtonStatus( sectionType, "check", "Check All", false );
	}
	
	function datatableFilterColumn( datatable, filterVal, columnIndex, isRegex, isSmartSearch, sectionType ) {
		datatable.filter( filterVal, columnIndex, isRegex, isSmartSearch ).draw( );
		setCheckAllButtonStatus( sectionType, "check", "Check All", false );
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