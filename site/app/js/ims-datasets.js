
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
		setupAttributeIcons( );
		setupParticipantPopover( );
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
			setCheckAllButtonStatus( sectionType, "check", false );
		});
		
		// SETUP Check All Button
		$("#dataTable-" + sectionType + "-checkAll").click( function( ) {
			var statusText = $(this).attr( "data-status" );
			
			if( statusText == "check" ) {
				setCheckAllButtonStatus( sectionType, "uncheck", true );
			} else if( statusText == "uncheck" ) {
				setCheckAllButtonStatus( sectionType, "check", false );
			}
			
		});
	
	}
	
	function setCheckAllButtonStatus( sectionType, statusText, propVal ) {
		$("#dataTable-" + sectionType + " :checkbox").prop( "checked", propVal );
		$("#dataTable-" + sectionType + "-checkAll").attr( "data-status", statusText );
	}
	
	function datatableFilterGlobal( datatable, filterVal, isRegex, isSmartSearch, sectionType ) {
		datatable.search( filterVal, isRegex, isSmartSearch, true ).draw( );
		setCheckAllButtonStatus( sectionType, "check", false );
	}
	
	function datatableFilterColumn( datatable, filterVal, columnIndex, isRegex, isSmartSearch, sectionType ) {
		datatable.filter( filterVal, columnIndex, isRegex, isSmartSearch ).draw( );
		setCheckAllButtonStatus( sectionType, "check", false );
	}
		
	function setupAvailabilitySwitch( ) {
		
		$('.datasetSidebar').on( "click", "#availabilitySwitch", function( event ) {
			
			var availabilityPopup = $(this).qtip({
				overwrite: false,
				content: {
					text: function( event, api ) {
						var availabilityForm = "<select class='form-control availability_select'><option value='public'>Public</option><option value='private'>Private</option><option value='website-only'>Website-Only</option></select><button type='button' class='availability_submit btn btn-success btn-block marginTopSm'>Submit</button>";
						return availabilityForm;
					},
					title: {
						text: "<strong>Change Availability</strong>",
						button: true
					}
				},
				style: {
					classes: 'qtip-bootstrap',
					width: '250px'
				},
				position: {
					my: 'left center',
					at: 'right center'
				},
				show: {
					event: event.type,
					ready: true,
					solo: true
				},
				hide: {
					delay: 3000,
					fixed: true,
					leave: false
				}
			}, event);
			
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
					availabilityPopup.qtip( 'hide' );
				});
				
			});
				
		});
			
	}
	
	function setupParticipantPopover( ) {
		
		$(".datasetContent").on( 'click', '.participantPopover', function( event ) {
			
			$(this).qtip({
				overwrite: false,
				content: {
					text: function( event, api ) {
						return $(this).parent( ).find( '.participantContent' ).html( );
					},
					title: {
						text: function( event, api ) {
							return "<strong>" + $(this).data( "title" ) + "</strong>";
						},
						button: true
					}
				},
				style: {
					classes: 'qtip-bootstrap',
					width: '300px'
				},
				position: {
					my: 'left bottom',
					at: 'right top'
				},
				show: {
					event: event.type,
					ready: true,
					solo: true
				},
				hide: {
					delay: 1000,
					fixed: true,
					event: 'mouseleave'
				}
			}, event);
			
		});
		
	}
	
	function setupAttributeIcons( ) {
		
		$(".datasetContent").on( 'mouseover', '.attributeIcon', function( event ) {
			
			$(this).qtip({
				overwrite: false,
				content: {
					text: function( event, api ) {
						return $(this).parent( ).find( '.attributeContent' ).html( );
					},
					title: {
						text: function( event, api ) {
							return "<strong>" + $(this).data( "title" ) + "</strong>";
						},
						button: true
					}
				},
				style: {
					classes: 'qtip-bootstrap',
					width: '600px'
				},
				position: {
					my: 'bottom center',
					at: 'top center',
					viewport: $(".datasetContent")
				},
				show: {
					event: event.type,
					ready: true,
					solo: true
				},
				hide: {
					delay: 1000,
					fixed: true,
					event: 'mouseleave'
				}
			}, event);
			
		});
		
	}

}));