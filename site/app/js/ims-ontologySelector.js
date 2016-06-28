
/**
 * Ontology Selector is a plugin used to create a functional tool for searching and
 * selecting from an ontology in the database
 */
 
;(function( yourcode ) {

	yourcode( window.jQuery, window, document );

} (function( $, window, document ) {
		
	$.ontologySelector = function( el, options, curationBlock ) {
		
		var base = this;
		base.$el = $(el);
		base.el = el;
		
		var timer;
		
		base.data = { 
			baseURL: $("head base").attr( "href" )
		};
		
		base.components = {
			searchTxt: base.$el.find( ".ontologySearchTxt" ),
			searchBtn: base.$el.find( ".ontologySearchBtn" ),
			viewBtns: base.$el.find( ".ontologyViewBtns" ),
			views: base.$el.find( ".ontologyViews" ),
			selectList: base.$el.find( ".ontologySelect" ),
			headerTxt: base.$el.find( ".ontologyHeaderText" ),
			viewOptions: base.$el.find( ".ontologyViewOptions" ),
			selectedTerms: base.$el.find( ".ontologySelectedTerms" ),

		};
		
		base.components.popularViewBtn = base.components.viewBtns.find( ".ontologyViewPopularBtn" );
		base.components.searchViewBtn = base.components.viewBtns.find( ".ontologyViewSearchBtn" );
		base.components.treeViewBtn = base.components.viewBtns.find( ".ontologyViewTreeBtn" );
		
		base.components.popularView = base.components.views.find( ".ontologyViewPopular" );
		base.components.searchView = base.components.views.find( ".ontologyViewSearch" );
		base.components.treeView = base.components.views.find( ".ontologyViewTree" );
		
		base.$el.data( "ontologySelector", base );
		
		base.init = function( ) {
			base.options = $.extend( {}, $.ontologySelector.defaultOptions, options );
			base.initSearchFunctionality( );
		};
		
		base.initSearchFunctionality = function( ) {
			
			// Search when search button is clicked
			base.components.searchBtn.on( "click", function( ) {
				base.search( );
			});
			
			// Search if Enter is pressed while in Text Box
			base.components.searchTxt.on( "keypress", function( e ) {
				if( e.keyCode == 13 ) {
					base.search( );
				}
			});
			
			base.$el.on( "click", ".ontologyViewBtn", function( ) {
				base.changeView( $(this) );
			});
			
			base.$el.on( "change", "select.ontologySelect", function( ) {
				base.changeSelect( );
			});
			
			base.$el.on( "mouseenter", "button.ontologyTermButton", function( ) {
				var button = $(this);
				clearTimeout( timer );
				timer = setTimeout( function( ) { 
					var buttonText = button.data( "btntext" );
					button.find( ".btnText" ).html( buttonText );
				}, base.options.hoverdelay );
			});
			
			base.$el.on( "mouseleave", "button.ontologyTermButton", function( ) {
				$(this).find( ".btnText" ).html( "" );
				clearTimeout( timer );
			});
			
			base.$el.on( "click", "button.ontologyTermButtonTree", function( ) {
				base.updateLineageView( $(this) );
				base.changeView( base.components.treeViewBtn );
			});
			
			base.$el.on( "mouseenter", "a.ontologyTermDetails", function( ) {
				base.loadTermDetailsTooltip( $(this) );
			});
			
			base.$el.on( "click", ".ontologyTermFolder", function( ) {
				base.toggleChildren( $(this) );
			});
			
			base.$el.on( "click", ".ontologyResetTree", function( ) {
				base.updateTreeView( );
			});
			
			base.$el.on( "click", ".ontologyTermButtonAdd", function( ) {
				base.addSelectedTerm( $(this) );
				curationBlock.setStatus( "NEW" );
			});
			
			base.$el.on( "click", ".ontologyTermButtonQualifier", function( ) {
				base.addSelectedQualifier( $(this) );
				curationBlock.setStatus( "NEW" );
			});
			
			base.$el.on( "click", ".ontologyRemoveSelectedTerm", function( ) {
				base.resetTermWarning( $(this) );
				curationBlock.setStatus( "NEW" );
				$(this).parent( ).parent( ).remove( );
			});
			
			base.$el.on( "click", ".ontologyClearAllLink", function( ) {
				curationBlock.setStatus( "NEW" );
				base.clearSelectedTerms( );
			});
			
			base.$el.on( "click", ".ontologyClearChecked", function( ) {
				curationBlock.setStatus( "NEW" );
				base.clearCheckedTerms( );
			});
			
			base.$el.on( "click", ".ontologyClearAllQualifiers", function( ) {
				curationBlock.setStatus( "NEW" );
				base.clearQualifiers( );
			});
			
			base.$el.on( "click", ".ontologyClearCheckedQualifiers", function( ) {
				curationBlock.setStatus( "NEW" );
				base.clearCheckedQualifiers( );
			});
			
			base.$el.on( "click", ".ontologyTermCheck", function( ) { 
				base.toggleTermChecked( true );
			});
			
			base.$el.on( "click", ".ontologyTermUncheck", function( ) { 
				base.toggleTermChecked( false );
			});
			
			base.$el.on( "click", ".ontologyQualifierCheck", function( ) { 
				base.toggleQualifierChecked( true );
			});
			
			base.$el.on( "click", ".ontologyQualifierUncheck", function( ) { 
				base.toggleQualifierChecked( false );
			});
			
			base.loadPopularView( );
			base.updateTreeView( );
			
		};
		
		base.resetTermWarning = function( term ) {
	
			// Reshow the warning in the case that it was previously
			// there and then removed to show a qualifier instead
			var termWrap = term.closest( ".ontologySelectedTermWrap" );
			var qualifiers = termWrap.find( ".ontologySelectedQualifiers" ).val( );
			var qualWarning = termWrap.find( ".ontologyTermQualifierWarning" );
			if( qualWarning.length && qualifiers == "" ) {
				qualWarning.show( );
			}
	
		};
		
		base.changeSelect = function( ) {
			var searchTerm = base.components.searchTxt.val( );
				
			base.updatePopularView( );
			base.updateTreeView( );
			
			if( searchTerm.length > 0 ) {
				base.updateSearchView( );
			} else {
				base.components.searchView.html( "Search for terms above to populate this list..." );
			}
		};
		
		base.loadPopularView = function( ) {
			base.changeView( base.components.popularViewBtn );
			base.updatePopularView( );
		};
		
		base.fetchOntologyDetails = function( ) {
			var ontologyDetails = base.components.selectList.val( );
			ontologyDetails = ontologyDetails.split( "|" );
			return ontologyDetails;
		};
		
		base.updatePopularView = function( ) {
			
			var ontologyDetails = base.fetchOntologyDetails( );
			
			var ajaxData = {
				ontology_id: ontologyDetails[0],
				allow_qualifiers: ontologyDetails[2],
				allow_terms: ontologyDetails[1],
				script: "loadPopularOntologyTerms"
			};
				
			$.ajax({
				
				url: base.data.baseURL + "/scripts/curation/Ontology.php",
				method: "POST",
				dataType: "json",
				data: ajaxData,
				beforeSend: function( ) {
					base.components.popularView.html( '<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i>' );
				}
				
			}).done( function(results) {
				base.components.popularView.html( results['VIEW'] );
			}).fail( function( jqXHR, textStatus ) {
				console.log( textStatus );
			});
			
		};
		
		base.search = function( ) {
			base.changeView( base.components.searchViewBtn );
			base.updateSearchView( );
		};
		
		base.updateSearchView = function( ) {
			
			var searchTerm = base.components.searchTxt.val( );
			var ontologyDetails = base.fetchOntologyDetails( );
			
			var ajaxData = {
				ontology_id: ontologyDetails[0],
				search: searchTerm,
				allow_qualifiers: ontologyDetails[2],
				allow_terms: ontologyDetails[1],
				script: "loadSearchOntologyTerms"
			};
				
			$.ajax({
				
				url: base.data.baseURL + "/scripts/curation/Ontology.php",
				method: "POST",
				dataType: "json",
				data: ajaxData,
				beforeSend: function( ) {
					base.components.searchView.html( '<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i>' );
				}
				
			}).done( function(results) {
				base.components.searchView.html( results['VIEW'] );
			}).fail( function( jqXHR, textStatus ) {
				console.log( textStatus );
			});
			
		};
		
		base.changeView = function( clickedBtn ) {
			
			base.components.viewBtns.children( ).removeClass( "active" );
			clickedBtn.addClass( "active" );
			
			var viewToShow = base.components.views.find( "." + clickedBtn.data( "show" ) );
			base.components.views.find( ".ontologyView" ).not( viewToShow ).hide( );
			viewToShow.show( );
			
			if( clickedBtn.data( "show" ) == "ontologyViewTree" ) {
				base.components.viewOptions.show( );
			} else {
				base.components.viewOptions.hide( );
			}
			
		};
		
		base.loadTermDetailsTooltip = function( clickedLink ) {
			
			clickedLink.qtip({
				overwrite: false,
				content: {
					text: function( event, api ) {
						
						var ajaxData = {
							ontology_term_id: clickedLink.data( "termid" ),
							script: "fetchOntologyTermDetails"
						};
						
						$.ajax({
				
							url: base.data.baseURL + "/scripts/curation/Ontology.php",
							method: "POST",
							dataType: "json",
							data: ajaxData
				
						}).done( function(results) {
							
							api.set( 'content.text', results['VIEW'] );
							
						}).fail( function( jqXHR, textStatus ) {
							console.log( textStatus );
							api.set( 'content.text', "Unable to fetch ontology term details..." );
						});
						
						return '<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> Loading...';
						
					}
				},
				style: {
					classes: 'qtip-bootstrap',
					width: '600px'
				},
				position: {
					my: 'left center',
					at: 'right center'
				},
				show: {
					ready: true,
					solo: true
				},
				hide: {
					fixed: true,
					leave: false
				}
			}, event);
			
		};
		
		base.tree = function( ) {
			base.changeView( base.components.treeViewBtn );
			base.updateTreeView( );
		};
		
		base.updateTreeView = function( ) {
			
			var ontologyDetails = base.fetchOntologyDetails( );
			
			var ajaxData = {
				ontology_id: ontologyDetails[0],
				allow_qualifiers: ontologyDetails[2],
				allow_terms: ontologyDetails[1],
				script: "loadTreeOntologyTerms"
			};
				
			$.ajax({
				
				url: base.data.baseURL + "/scripts/curation/Ontology.php",
				method: "POST",
				dataType: "json",
				data: ajaxData,
				beforeSend: function( ) {
					base.components.treeView.html( '<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i>' );
				}
				
			}).done( function(results) {
				 
				base.components.treeView.html( results['VIEW'] );
				
			}).fail( function( jqXHR, textStatus ) {
				console.log( textStatus );
			});
			
		};
		
		base.fetchChildren = function( treeBtn, termID, treeExpand ) {
			
			var ontologyDetails = base.fetchOntologyDetails( );
			
			var ajaxData = {
				ontology_term_id: termID,
				allow_qualifiers: ontologyDetails[2],
				allow_terms: ontologyDetails[1],
				script: "loadTreeOntologyChildren"
			};
			
			$.ajax({
				
				url: base.data.baseURL + "/scripts/curation/Ontology.php",
				method: "POST",
				dataType: "json",
				data: ajaxData,
				beforeSend: function( ) {
					treeBtn.html( '<i class="fa fa-spinner fa-pulse fa-lg fa-fw"></i>' );
				}
				
			}).done( function(results) {
				 
				treeExpand.html( results['VIEW'] );
				
			}).fail( function( jqXHR, textStatus ) {
				console.log( textStatus );
			});
			
		};
		
		base.toggleChildren = function( treeBtn ) {
			
			var termID = treeBtn.data( "termid" );
			var treeExpand = treeBtn.closest( ".popularOntologyTerm" ).find( ".ontologyTermExpand" );
			var notfull = treeExpand.data( "notfull" );
			
			if( treeExpand.html( ) === "" || treeExpand.data( "notfull" ) == true ) {
				base.fetchChildren( treeBtn, termID, treeExpand );
				treeBtn.html( '<i class="fa fa-angle-double-down fa-lg"></i>' );
				treeExpand.show( );
				treeExpand.data( "notfull", "false" );
			} else {
				if( treeExpand.is( ":visible" ) ) {
					treeExpand.hide( );
					treeBtn.html( '<i class="fa fa-angle-double-right fa-lg"></i>' );
				} else {
					treeExpand.show( );
					treeBtn.html( '<i class="fa fa-angle-double-down fa-lg"></i>' );
				}
			}
			
			
		};
		
		base.updateLineageView = function( lineageBtn ) {
			
			var ontologyDetails = base.fetchOntologyDetails( );
			
			var ajaxData = {
				ontology_term_id: lineageBtn.data( "termid" ),
				allow_qualifiers: ontologyDetails[2],
				allow_terms: ontologyDetails[1],
				script: "loadLineageOntologyTerms"
			};
				
			$.ajax({
				
				url: base.data.baseURL + "/scripts/curation/Ontology.php",
				method: "POST",
				dataType: "json",
				data: ajaxData,
				beforeSend: function( ) {
					base.components.treeView.html( '<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i>' );
				}
				
			}).done( function(results) {
				 
				base.components.treeView.html( results['VIEW'] );
				
			}).fail( function( jqXHR, textStatus ) {
				console.log( textStatus );
			});
			
		};
		
		base.clearSelectedTerms = function( ) {
			base.components.selectedTerms.html( "" );
		};
		
		base.clearCheckedTerms = function( ) {
			base.components.selectedTerms.find( ".ontologySelectedCheck:checked" ).each( function( index, element ) {
				$(this).parent( ).parent( ).remove( );
			});
		};
		
		base.clearCheckedQualifiers = function( ) {
			base.components.selectedTerms.find( ".ontologySelectedQualifierCheck:checked" ).each( function( index, element ) {
				base.resetTermWarning( $(this) );
				$(this).parent( ).parent( ).remove( );
			});
		};
		
		base.clearQualifiers = function( ) {
			base.components.selectedTerms.find( ".ontologySelectedQualifiers" ).each( function( index, element ) {
				base.resetTermWarning( $(this) );
				$(this).html( "" );
			});
		};
		
		base.toggleTermChecked = function( checkAll ) {
			if( checkAll ) {
				base.components.selectedTerms.find( ".ontologySelectedCheck" ).prop( "checked", true );
			} else {
				base.components.selectedTerms.find( ".ontologySelectedCheck:checked" ).prop( "checked", false );
			}
		};
		
		base.toggleQualifierChecked = function( checkAll ) {
			if( checkAll ) {
				base.components.selectedTerms.find( ".ontologySelectedQualifierCheck" ).prop( "checked", true );
			} else {
				base.components.selectedTerms.find( ".ontologySelectedQualifierCheck:checked" ).prop( "checked", false );
			}
		};
		
		base.addSelectedTerm = function( addBtn ) {
			
			addBtn.prop( "disabled", true );
			
			var overallTerm = addBtn.closest( ".popularOntologyTerm" )
			var termID = overallTerm.data( "termid" );
			var termName = overallTerm.data( "termname" );
			var termOfficial = overallTerm.data( "termofficial" );
			var attributeTypeID = base.$el.closest( ".curationBlock" ).data( "attribute" );
			
			var selectedTerms = base.components.selectedTerms.find( ".ontologySelectedCheck:checked" ).map( function( ) {
				return this.value;
			}).get( );
			
			var selectedList = "";
			if( selectedTerms.length > 0 ) {
				selectedList = selectedTerms.join( "|" );
			}
			
			var ajaxData = {
				ontology_term_id: termID,
				ontology_term_name: termName,
				ontology_term_official: termOfficial,
				selected_terms: selectedList,
				attribute_type_id: attributeTypeID,
				script: "addSelectedTerm"
			};
				
			$.ajax({
				
				url: base.data.baseURL + "/scripts/curation/Ontology.php",
				method: "POST",
				dataType: "json",
				data: ajaxData
				
			}).done( function(results) {
				
				// If single select is true, you can only
				// pick a single term, so it always overwrites
				if( base.options.singleselect ) {
					if( results['VIEW'] != "" ) {
						base.components.selectedTerms.html( results['VIEW'] );
					}
				} else {
					base.components.selectedTerms.append( results['VIEW'] );
				}
				
				if( results['SWITCH'] != 0 ) {
					base.components.selectList.val( results['SWITCH'] );
					base.changeSelect( );
				}
				
				addBtn.prop( "disabled", false );
				
			}).fail( function( jqXHR, textStatus ) {
				console.log( textStatus );
				addBtn.prop( "disabled", false );
			});
			
		};
		
		base.addSelectedQualifier = function( addBtn ) {
			
			addBtn.prop( "disabled", true );
			
			var overallTerm = addBtn.closest( ".popularOntologyTerm" )
			var termID = overallTerm.data( "termid" );
			var termName = overallTerm.data( "termname" );
			var termOfficial = overallTerm.data( "termofficial" );
			
			var ajaxData = {
				ontology_term_id: termID,
				ontology_term_name: termName,
				ontology_term_official: termOfficial,
				script: "addSelectedQualifier"
			};
				
			$.ajax({
				
				url: base.data.baseURL + "/scripts/curation/Ontology.php",
				method: "POST",
				dataType: "json",
				data: ajaxData
				
			}).done( function(results) {
				
				base.components.selectedTerms.find( ".ontologySelectedCheck:checked" ).each( function( index, element ) {
					
					var qualifierBox = $(element).closest( ".ontologySelectedTerm" ).find( ".ontologySelectedQualifiers" );
					if( qualifierBox.find( "input[type=checkbox][value=" + results["VALUE"] + "]" ).length <= 0 ) {
						
						var qualWarning = $(element).parent( ).find( ".ontologyTermQualifierWarning" );
						
						if( base.options.allowqual || qualWarning.length ) {
							
							if( qualWarning.length ) {
								qualWarning.hide( );
							}
						
							if( base.options.singlequal ) {
								qualifierBox.html( results['VIEW'] );
							} else {
								if( qualifierBox.has( ".ontologySelectedQualifier" ).length ) {
									qualifierBox.append( results['VIEW'] );
								} else {
									qualifierBox.html( results['VIEW'] );
								}
							}
							
						}
					}
					
				});
				
				addBtn.prop( "disabled", false );
				
			}).fail( function( jqXHR, textStatus ) {
				console.log( textStatus );
				addBtn.prop( "disabled", false );
			});
				
			
		};
		
		base.init( );
		
	};
	
	$.ontologySelector.defaultOptions = { 
		hoverdelay: 1000,
		singleselect: false,
		singlequal: false,
		allowqual: true
	};
	
	$.fn.ontologySelector = function( options, curationBlock ) {
		return this.each( function( ) {
			(new $.ontologySelector( this, options, curationBlock ));
		});
	};
		

}));