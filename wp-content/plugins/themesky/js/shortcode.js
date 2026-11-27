jQuery(document).ready(function($){
	"use strict";
	
	/*** Product Slider shortcode ***/
	$('.ts-product-slider-wrapper').each(function(){
		var element = $(this);
		var is_slider = false;
		
		if( element.data('is_slider') ){
			is_slider = true;
		}
		
		if( is_slider ){
			var show_nav = false;
			var columns = 5;
			var margin = 0;
			
			if( element.data('nav') ){
				show_nav = true;
			}
			
			if( element.data('columns') ){
				columns = element.data('columns');
			}
			
			if( element.data('margin') ){
				margin = element.data('margin');
			}
			
			var _slider_data = {
						loop : true
						,nav : show_nav
						,navText : [,]
						,dots : false
						,navSpeed: 1000
						,slideBy: 1
						,rtl: $('body').hasClass('rtl')
						,margin : margin
						,navRewind: false
						,autoplay: false
						,autoplayTimeout: 5000
						,autoplayHoverPause: true
						,autoplaySpeed: 1000
						,mouseDrag: true
						,touchDrag: true
						,responsiveBaseElement: element
						,responsiveRefreshRate: 400
						,responsive:{
									0:{
										items : 1
									},
									300:{
										items : 2
									},
									579:{
										items : 3
									},
									767:{
										items : 4
									},
									880:{
										items : columns
									}
								}
						,onInitialized: function(){
							element.find('.content-wrapper').addClass('loaded').removeClass('loading');
						}
					};
					
			if( columns == 1 ){
				_slider_data.responsive = { 0: { items: 1 } };
			}
			
			element.find('.products').owlCarousel(_slider_data);
		}
	});
	
	/*** Product Deals Shortcode ***/
	$('.ts-product-deals-slider-wrapper').each(function(){
		var element = $(this);
		var show_nav = false;
		var auto_play = false;
		var margin = 20;
		var columns = 4;
		var is_slider = false;
		
		if( element.data('nav') ){
			show_nav = true;
		}
		if( element.data('autoplay') ){
			auto_play = true;
		}
		if( element.data('margin') != undefined ){
			margin = element.data('margin');
		}
		if( element.data('columns') ){
			columns = element.data('columns');
		}
		if( element.data('is_slider') ){
			is_slider = true;
		}
		
		var _slider_data = {
				loop : true
				,nav : show_nav
				,navText : [,]
				,dots : false
				,navSpeed: 1000
				,slideBy: 1
				,rtl: $('body').hasClass('rtl')
				,margin : margin
				,navRewind: false
				,autoplay: auto_play
				,autoplayTimeout: 5000
				,autoplayHoverPause: true
				,autoplaySpeed: 1000
				,mouseDrag: true
				,touchDrag: true
				,responsiveBaseElement: element
				,responsiveRefreshRate: 400
				,responsive:{
							0:{
								items : 1
							},
							300:{
								items : 2
							},
							579:{
								items : 3
							},
							730:{
								items : 4
							},
							800:{
								items : columns
							}
						}
				,onInitialized: function(){
					element.find('.content-wrapper').addClass('loaded').removeClass('loading');
				}
			};
			
		if( columns == 1 ){
			_slider_data.responsive = { 0:{ items: 1 } };
		}
		
		element.find('.products').owlCarousel(_slider_data);
	});
	
	/*** Product Category Shortcode ***/
	$('.ts-product-category-slider-wrapper').each(function(){
		var element = $(this);
		var show_nav = false;
		var auto_play = false;
		var margin = 0;
		var columns = 4;
		if( element.data('nav') ){
			show_nav = true;
		}
		if( element.data('autoplay') ){
			auto_play = true;
		}
		if( element.data('margin') ){
			margin = parseInt( element.data('margin') );
		}
		if( element.data('columns') ){
			columns = parseInt( element.data('columns') );
		}
		var _slider_data = { 
			loop : true
			,nav : show_nav
			,navText : [,]
			,dots : false
			,navSpeed: 1000
			,slideBy: 1
			,rtl: $('body').hasClass('rtl')
			,margin : margin
			,navRewind: false
			,autoplay: auto_play
			,autoplayTimeout: 5000
			,autoplayHoverPause: false
			,autoplaySpeed: 1000
			,mouseDrag: true
			,touchDrag: true
			,responsiveBaseElement: element
			,responsiveRefreshRate: 400
			,responsive:{
						0:{
							items : 1
						},
						420:{
							items : 2
						},
						767:{
							items : 3
						},
						930:{
							items : columns
						}
					}
			,onInitialized: function(){
				element.find('.content-wrapper').addClass('loaded').removeClass('loading');
			}
		};
		
		element.find('.products').owlCarousel( _slider_data );
	});
	
	/*** Load Products In Category Tab ***/
	var ts_product_in_category_tab_data = [];
	
	/* Change tab */
	$('.ts-product-in-category-tab-wrapper .column-tabs .tab-item').bind('click', function(){
		if( $(this).hasClass('current') || $(this).parents('.ts-product-in-category-tab-wrapper').find('.column-products').hasClass('loading') ){
			return;
		}
		var element = $(this).parents('.ts-product-in-category-tab-wrapper');
		var element_id = element.attr('id');
		var product_cat = $(this).data('product_cat');
		var see_more_link = $(this).data('link');
		var atts = element.data('atts');
		
		var is_general_tab = $(this).hasClass('general-tab')?1:0;
		
		if( element.find('a.see-more-button').length > 0 ){
			element.find('a.see-more-button').attr('href', see_more_link);
		}
		
		element.find('.column-tabs .tab-item').removeClass('current');
		$(this).addClass('current');
		
		/* Check cache */
		var tab_data_index = element_id + '-' + product_cat.toString().split(',').join('-');
		if( ts_product_in_category_tab_data[tab_data_index] != undefined ){
			/* destroy slider first */
			element.find('.column-products .products.owl-carousel').owlCarousel('destroy');
			
			element.find('.column-products .products').remove();
			element.find('.column-products').append( ts_product_in_category_tab_data[tab_data_index] );
			if( typeof ts_quickshop_handle == 'function' ){
				ts_quickshop_handle();
			}
			element.find('.lazy-loading img').each(function(){
				if( $(this).data('src') ){
					$(this).attr('src', $(this).data('src'));
				}
			});
			element.find('.lazy-loading').removeClass('lazy-loading').addClass('lazy-loaded');
			/* See more button handle */
			ts_product_in_category_tab_see_more_handle( element, atts );
			
			/* Generate slider */
			ts_product_slider_in_category_tab( element, atts.show_nav, atts.auto_play, atts.columns );
			
			return;
		}
		
		element.find('.column-products').addClass('loading');
		
		$.ajax({
			type : "POST",
			timeout : 30000,
			url : ts_shortcode_params.ajax_uri,
			data : {action: 'ts_get_product_content_in_category_tab', atts: atts, product_cat: product_cat, is_general_tab: is_general_tab},
			error: function(xhr,err){
				
			},
			success: function(response) {
				if( response ){
					/* destroy slider first */
					element.find('.column-products .products.owl-carousel').owlCarousel('destroy');
					
					element.find('.column-products .products').remove();
					element.find('.column-products').append( response );
					if( typeof ts_quickshop_handle == 'function' ){
						ts_quickshop_handle();
					}
					/* save cache */
					ts_product_in_category_tab_data[tab_data_index] = response;
					
					/* See more button handle */
					ts_product_in_category_tab_see_more_handle( element, atts );
					
					/* Generate slider */
					ts_product_slider_in_category_tab( element, atts.show_nav, atts.auto_play, atts.columns );
				}
				element.find('.column-products').removeClass('loading');
			}
		});
	});
	
	$('.ts-product-in-category-tab-wrapper').each(function(){
		$(this).find('.column-tabs .tab-item:first').trigger('click');
	});
	
	function ts_product_in_category_tab_see_more_handle(element, atts){
		var hide_see_more = element.find('.products .hide-see-more').length;
		element.find('.products .hide-see-more').remove();
		
		if( element.find('.tab-item.current').hasClass('general-tab') && atts.hide_see_more_general_tab == 1 ){
			hide_see_more = true;
		}
		
		if( element.find('.products .product').length == 0 ){
			hide_see_more = true;
		}
		
		if( atts.show_see_more_button == 1 ){
			if( hide_see_more ){
				element.find('.see-more-wrapper').addClass('hidden');
				element.removeClass('has-see-more-button');
			}
			else{
				element.find('.see-more-wrapper').removeClass('hidden');
				element.addClass('has-see-more-button');
			}
		}
	}
	
	function ts_product_slider_in_category_tab( element, show_nav, auto_play, columns, responsive, margin ){
		if( element.find('.products .product-group').length > 0 ){
			show_nav = (show_nav == 1)?true:false;
			auto_play = (auto_play == 1)?true:false;
			columns = parseInt(columns);
			var _slider_data = { 
				loop : true
				,nav : show_nav
				,navText : [,]
				,dots : false
				,navSpeed: 1000
				,slideBy: 1
				,rtl: $('body').hasClass('rtl')
				,margin : 0
				,navRewind: false
				,autoplay: auto_play
				,autoplayTimeout: 5000
				,autoplayHoverPause: false
				,autoplaySpeed: 1000
				,mouseDrag: true
				,touchDrag: true
				,responsiveBaseElement: element.find('.products')
				,responsiveRefreshRate: 400
				,responsive:{
							0:{
								items : 1
							},
							320:{
								items : 2
							},
							470:{
								items : 3
							},
							670:{
								items : 4
							},
							870:{
								items : 5
							},
							1100:{
								items : columns
							}
						}
				,onInitialized: function(){
					
				}
			};
			
			if( responsive != undefined ){
				_slider_data.responsive = responsive;
			}
			
			if( margin != undefined ){
				_slider_data.margin = margin;
			}
			
			element.find('.products').owlCarousel( _slider_data );
		}
	}
	
	/* Create banner slider */
	$('.ts-product-in-category-tab-wrapper .column-banners, .ts-product-in-sub-category-tab-wrapper .row-banners').each(function(){
		var element = $(this);
		var is_slider = false;
		if( element.find('img').length > 1 ){
			is_slider = true;
		}
		else{
			element.removeClass('loading');
		}
		
		if( is_slider ){
			var slider_data = {
				loop : true
				,nav : false
				,navText: [,]
				,navSpeed: 1000
				,dots: true
				,slideBy: 1
				,rtl: $('body').hasClass('rtl')
				,margin : 10
				,navRewind: false
				,autoplay: true
				,autoplayTimeout: 5000
				,autoplayHoverPause: true
				,autoplaySpeed: false
				,mouseDrag: true
				,touchDrag: true
				,responsiveBaseElement: element
				,responsiveRefreshRate: 400
				,responsive:{ 0:{ items : 1 } }
				,onInitialized: function(){
					element.addClass('loaded').removeClass('loading');
				}
			};
			element.find('figure').owlCarousel(slider_data);
		}
	});
	
	/*** Load Products In Category Tab 2 ***/
	var ts_product_in_category_tab_2_data = [];
	
	/* Change tab */
	$('.ts-product-in-category-tab-2-wrapper .row-tabs .tab-item').bind('click', function(){
		if( $(this).hasClass('current') || $(this).parents('.ts-product-in-category-tab-2-wrapper').find('.row-content').hasClass('loading') ){
			return;
		}
		var element = $(this).parents('.ts-product-in-category-tab-2-wrapper');
		var element_id = element.attr('id');
		var product_cat = $(this).data('product_cat');
		var atts = element.data('atts');
		
		var responsive = { 0:{items : 1}, 300:{items : 2}, 600:{items : 3}, 900:{items : atts.columns} };
		var margin = 20;
		
		element.find('.row-tabs .tab-item').removeClass('current');
		$(this).addClass('current');
		
		/* Check cache */
		var tab_data_index = element_id + '-' + product_cat.toString().split(',').join('-');
		if( ts_product_in_category_tab_2_data[tab_data_index] != undefined ){
			/* destroy slider first */
			element.find('.column-products .products.owl-carousel').owlCarousel('destroy');
			
			element.find('.row-content > div').remove();
			element.find('.row-content').append( ts_product_in_category_tab_2_data[tab_data_index] );
			if( typeof ts_quickshop_handle == 'function' ){
				ts_quickshop_handle();
			}
			element.find('.lazy-loading img').each(function(){
				if( $(this).data('src') ){
					$(this).attr('src', $(this).data('src'));
				}
			});
			element.find('.lazy-loading').removeClass('lazy-loading').addClass('lazy-loaded');
			
			/* Update class */
			element.removeClass('no-banner has-banner no-product has-product');
			if( element.find('.flag-no-banner').length > 0 ){
				element.addClass('no-banner');
			}
			else{
				element.addClass('has-banner');
			}
			if( element.find('.flag-no-product').length > 0 ){
				element.addClass('no-product');
			}
			else{
				element.addClass('has-product');
			}
			
			/* Generate slider */
			ts_product_slider_in_category_tab( element, atts.show_nav, atts.auto_play, atts.columns, responsive, margin );
			
			return;
		}
		
		element.find('.row-content').addClass('loading');
		
		$.ajax({
			type : "POST",
			timeout : 30000,
			url : ts_shortcode_params.ajax_uri,
			data : {action: 'ts_get_product_content_in_category_tab_2', atts: atts, product_cat: product_cat},
			error: function(xhr,err){
				
			},
			success: function(response) {
				if( response ){
					/* destroy slider first */
					element.find('.column-products .products.owl-carousel').owlCarousel('destroy');
					
					element.find('.row-content > div').remove();
					element.find('.row-content').append( response );
					if( typeof ts_quickshop_handle == 'function' ){
						ts_quickshop_handle();
					}
					/* save cache */
					if( element.find('.product .counter-wrapper').length == 0 ){
						ts_product_in_category_tab_2_data[tab_data_index] = response;
					}
					
					/* Update class */
					element.removeClass('no-banner has-banner no-product has-product');
					if( element.find('.flag-no-banner').length > 0 ){
						element.addClass('no-banner');
					}
					else{
						element.addClass('has-banner');
					}
					if( element.find('.flag-no-product').length > 0 ){
						element.addClass('no-product');
					}
					else{
						element.addClass('has-product');
					}
					
					/* Counter */
					ts_counter( element.find('.product .counter-wrapper') );
					
					/* Generate slider */
					ts_product_slider_in_category_tab( element, atts.show_nav, atts.auto_play, atts.columns, responsive, margin );
				}
				element.find('.row-content').removeClass('loading');
			}
		});
	});
	
	$('.ts-product-in-category-tab-2-wrapper').each(function(){
		var active_tab = 1;
		var number_tab = $(this).find('.row-tabs .tab-item').length;
		var atts = $(this).data('atts');
		if( atts.active_tab != undefined ){
			var defined_active_tab = parseInt(atts.active_tab);
			if( defined_active_tab > 1 && defined_active_tab <= number_tab ){
				active_tab = defined_active_tab;
			}
		}
		
		$(this).find('.row-tabs .tab-item').eq(active_tab - 1).trigger('click');
	});
	
	/*** Load Products In Sub Category Tab ***/
	var ts_product_in_sub_category_tab_data = [];
	
	/* Change tab */
	$('.ts-product-in-sub-category-tab-wrapper .tabs .tab-item:not(.link-to-all)').bind('click', function(){
		if( $(this).hasClass('current') || $(this).parents('.ts-product-in-sub-category-tab-wrapper').find('.row-products').hasClass('loading') ){
			return;
		}
		var element = $(this).parents('.ts-product-in-sub-category-tab-wrapper');
		var element_id = element.attr('id');
		var product_cat = $(this).data('product_cat');
		var atts = element.data('atts');
		
		element.find('.tabs .tab-item').removeClass('current');
		$(this).addClass('current');
		
		/* Check cache */
		var tab_data_index = element_id + '-' + product_cat;
		if( ts_product_in_sub_category_tab_data[tab_data_index] != undefined ){
			/* destroy slider first */
			element.find('.row-products .products.owl-carousel').owlCarousel('destroy');
			
			element.find('.row-products .products').remove();
			element.find('.row-products').append( ts_product_in_sub_category_tab_data[tab_data_index] );
			if( typeof ts_quickshop_handle == 'function' ){
				ts_quickshop_handle();
			}
			element.find('.lazy-loading img').each(function(){
				if( $(this).data('src') ){
					$(this).attr('src', $(this).data('src'));
				}
			});
			element.find('.lazy-loading').removeClass('lazy-loading').addClass('lazy-loaded');
			ts_product_slider_in_sub_category_tab( element, atts.show_nav, atts.auto_play );
			return;
		}
		
		element.find('.row-products').addClass('loading');
		
		$.ajax({
			type : "POST",
			timeout : 30000,
			url : ts_shortcode_params.ajax_uri,
			data : {action: 'ts_get_product_content_in_sub_category_tab', atts: atts, product_cat: product_cat},
			error: function(xhr,err){
				
			},
			success: function(response) {
				if( response ){
					/* destroy slider first */
					element.find('.row-products .products.owl-carousel').owlCarousel('destroy');
					
					element.find('.row-products .products').remove();
					element.find('.row-products').append( response );
					if( typeof ts_quickshop_handle == 'function' ){
						ts_quickshop_handle();
					}
					ts_product_slider_in_sub_category_tab( element, atts.show_nav, atts.auto_play );
					/* save cache */
					ts_product_in_sub_category_tab_data[tab_data_index] = response;
				}
				element.find('.row-products').removeClass('loading');
			}
		});
	});
	
	function ts_product_slider_in_sub_category_tab( element, show_nav, auto_play ){
		show_nav = (show_nav == 1)?true:false;
		auto_play = (auto_play == 1)?true:false;
		var _slider_data = {
				loop : true
				,nav : show_nav
				,navText : [,]
				,dots : false
				,navSpeed: 1000
				,slideBy: 1
				,rtl: $('body').hasClass('rtl')
				,margin : 0
				,navRewind: false
				,autoplay: auto_play
				,autoplayTimeout: 5000
				,autoplayHoverPause: true
				,autoplaySpeed: 1000
				,mouseDrag: true
				,touchDrag: true
				,responsiveBaseElement: element.find('.row-products')
				,responsiveRefreshRate: 400
				,responsive:{
							0:{
								items : 1
							},
							320:{
								items : 2
							},
							470:{
								items : 3
							},
							670:{
								items : 4
							},
							870:{
								items : 5
							},
							1100:{
								items : 6
							}
						}
				,onInitialized: function(){
					
				}
			};
			
			element.find('.row-products .products').owlCarousel( _slider_data );
	}
	
	$('.ts-product-in-sub-category-tab-wrapper').each(function(){
		var element = $(this);
		if( element.find('.tabs .tab-item').length > 2 ){
			element.find('.tabs .tab-item:not(.parent-cat):first').trigger('click');
		}
		else{
			element.find('.tabs .tab-item:first').trigger('click');
		}
	});

	/*** Blog Shortcode ***/
	$('.ts-blogs-wrapper.ts-shortcode').each(function(){
		var element = $(this);
		var atts = element.data('atts');
		
		/* Slider */
		if( atts.is_slider ){
			var show_nav = parseInt(atts.show_nav) == 1;
			var auto_play = parseInt(atts.auto_play) == 1;
			var margin = parseInt(atts.margin);
			var columns = parseInt(atts.columns);
			var slider_data = {
				loop : true
				,nav : show_nav
				,navText: [,]
				,navSpeed: 1000
				,slideBy: 1
				,rtl: $('body').hasClass('rtl')
				,margin : margin
				,navRewind: false
				,autoplay: auto_play
				,autoplayTimeout: 5000
				,autoplayHoverPause: true
				,autoplaySpeed: false
				,autoHeight: true
				,mouseDrag: true
				,touchDrag: true
				,responsiveBaseElement: element
				,responsiveRefreshRate: 400
				,responsive:{
							0:{
								items : 1
							},
							550:{
								items : 2
							},
							767:{
								items : 3
							},
							870:{
								items : columns
							}
						}
				,onInitialized: function(){
					element.addClass('loaded').removeClass('loading');
				}
			};
			element.find('.content-wrapper > .blogs').owlCarousel(slider_data);
		}
		
		/* Blog Gallery - Masonry - Load more */
		var is_masonry = false;
		if( atts.is_masonry && typeof $.fn.isotope == 'function' ){
			is_masonry = true;
		}
		
		$(window).load(function(){
			ts_blog_shortcode_gallery_slider( element, atts );
		});
		
		if( is_masonry ){
			$(window).load(function(){
				element.find('.blogs').isotope();
			});
		}
		
		/* Show more */
		element.find('a.load-more').bind('click', function(){
			var button = $(this);
			if( button.hasClass('loading') ){
				return false;
			}
			
			button.addClass('loading');
			var paged = button.attr('data-paged');
			
			$.ajax({
				type : "POST",
				timeout : 30000,
				url : ts_shortcode_params.ajax_uri,
				data : {action: 'ts_blogs_load_items', paged: paged, atts : atts},
				error: function(xhr,err){
					
				},
				success: function(response) {
					button.removeClass('loading');
					button.attr('data-paged', ++paged);
					if( response != 0 && response != '' ){
						if( is_masonry ){										
							element.find('.blogs').isotope('insert', $(response));
							setTimeout(function(){
								element.find('.blogs').isotope('layout');
							}, 200);
						}
						else { /* Append and Update first-last classes */
							element.find('.blogs').append(response);
							
							var columns = parseInt(atts.columns);
							element.find('.blogs .item').removeClass('first last');
							element.find('.blogs .item').each(function(index, ele){
								if( index % columns == 0 ){
									$(ele).addClass('first');
								}
								if( index % columns == columns - 1 ){
									$(ele).addClass('last');
								}
							});
						}
						
						ts_blog_shortcode_gallery_slider( element, atts );
					}
					else{ /* No results */
						button.parent().remove();
					}
				}
			});
			
			return false;
		});
		
	});
	
	function ts_blog_shortcode_gallery_slider( element, atts ){
		var show_nav = parseInt(atts.show_nav) == 1;
		var slider_data = {
			items: 1
			,loop: true
			,nav: false
			,dots: show_nav
			,animateIn: 'fadeIn'
			,animateOut: 'fadeOut'
			,navText: [,]
			,navSpeed: 1000
			,slideBy: 1
			,rtl: $('body').hasClass('rtl')
			,margin: 10
			,navRewind: false
			,autoplay: true
			,autoplayTimeout: 4000
			,autoplayHoverPause: true
			,autoplaySpeed: false
			,autoHeight: true
			,mouseDrag: false
			,touchDrag: false
			,responsive:{
				0:{
					items : 1
				}
			}
			,onInitialized: function(){
				element.find('.thumbnail.gallery').addClass('loaded').removeClass('loading');
			}
		};
		if( element.find('.thumbnail.gallery').length > 0 ){
			element.find('.thumbnail.gallery:not(.loaded) figure').owlCarousel(slider_data);
		}
	}
	
	/*** Logo Slider shortcode ***/
	$('.ts-logo-slider-wrapper.loading').each(function(){
		var element = $(this);
		var margin = element.data('margin');
		var show_nav = false;
		var auto_play = false;
		var break_point = element.data('break_point');
		var item = element.data('item');
		
		if( element.data('nav') ){
			show_nav = true;
		}
		
		if( element.data('auto_play') ){
			auto_play = true;
		}
		
		var _slider_data = {
				loop : true
				,nav : show_nav
				,navText: [,]
				,dots : false
				,navSpeed: 1000
				,slideBy: 1
				,rtl: $('body').hasClass('rtl')
				,margin : margin
				,navRewind: false
				,autoplay: auto_play
				,autoplayTimeout: 5000
				,autoplayHoverPause: true
				,autoplaySpeed: false
				,mouseDrag: true
				,touchDrag: true
				,responsiveBaseElement: element
				,responsiveRefreshRate: 400
				,responsive:{
							0:{
								items : 1
							},
							300:{
								items : 2
							},
							400:{
								items : 3
							},
							640:{
								items : 4
							},
							930:{
								items : 5
							}
						}
				,onInitialized: function(){
					element.addClass('loaded').removeClass('loading');
				}
			};
			
		if( break_point.length > 0 ){
			_slider_data.responsive = {};
			for( var i = 0; i < break_point.length; i++ ){
				_slider_data.responsive[break_point[i]] = {items: item[i]};
			}
		}
			
		element.find('.logos').owlCarousel(_slider_data);
	});
	
	/*** Fix min-height of Visual Composer's tab ***/	
	$(window).bind('load resize', function(){
		ts_update_tab_min_height();
	});
	
	$('.vc_tta-tabs .vc_tta-tabs-list .vc_tta-tab').bind('click', function(){
		ts_update_tab_min_height();
	});
	
	function ts_update_tab_min_height(){
		setTimeout(function(){
			$('.vc_tta-tabs .vc_tta-panels').each(function(){
				$(this).find('.vc_tta-panel').css('min-height', 0);
				var min_height = $(this).find('.vc_tta-panel.vc_active').height();
				$(this).find('.vc_tta-panel').css('min-height', min_height);
			});
		}, 500);
	}
	
	/*** Remove Hash Url ***/
	$('.vc_tta-tabs .vc_tta-tabs-list .vc_tta-tab a, .vc_tta-accordion .vc_tta-panel-title a').bind('click', function(){
		if( history.pushState ){
			setTimeout(function(){
				history.pushState(null, null, ' ');
			}, 0);
		}
	});
	
	/*** Counter ***/
	function ts_counter( elements ){
		if( elements.length > 0 ){
			var interval = setInterval(function(){
				elements.each(function(index, element){
					var day = 0;
					var hour = 0;
					var minute = 0;
					var second = 0;
					
					var delta = 0;
					var time_day = 60 * 60 * 24;
					var time_hour = 60 * 60;
					var time_minute = 60;
					
					var wrapper = $(element);
					
					wrapper.find('.days .number-wrapper .number').each(function(i, e){
						day = parseInt( $(e).text() );
					});
					wrapper.find('.hours .number-wrapper .number').each(function(i, e){
						hour = parseInt( $(e).text() );
					});
					wrapper.find('.minutes .number-wrapper .number').each(function(i, e){
						minute = parseInt( $(e).text() );
					});
					wrapper.find('.seconds .number-wrapper .number').each(function(i, e){
						second = parseInt( $(e).text() );
					});
					
					if( day != 0 || hour != 0  || minute != 0 || second != 0 ){
						delta = (day * time_day) + (hour * time_hour) + (minute * time_minute) + second;
						delta--;
						
						day = Math.floor(delta / time_day);
						delta -= day * time_day;
						
						hour = Math.floor(delta / time_hour);
						delta -= hour * time_hour;
						
						minute = Math.floor(delta / time_minute);
						delta -= minute * time_minute;
						
						if( delta > 0 ){
							second = delta;
						}
						else{
							second = '0';
						}
						
						day = ( day < 10 )? zeroise(day, 2) : day.toString();
						hour = ( hour < 10 )? zeroise(hour, 2) : hour.toString();
						minute = ( minute < 10 )? zeroise(minute, 2) : minute.toString();
						second = ( second < 10 )? zeroise(second, 2) : second.toString();
						
						wrapper.find('.days .number-wrapper .number').each(function(i, e){
							$(e).text(day);
						});
						
						wrapper.find('.hours .number-wrapper .number').each(function(i, e){
							$(e).text(hour);
						});
						
						wrapper.find('.minutes .number-wrapper .number').each(function(i, e){
							$(e).text(minute);
						});
						
						wrapper.find('.seconds .number-wrapper .number').each(function(i, e){
							$(e).text(second);
						});
					}
					
				});
			}, 1000);
		}
	}
	
	ts_counter( $('.product .counter-wrapper, .ts-countdown .counter-wrapper') );
	
	/*** Portfolio ***/
	$(window).load( function(){
		if( typeof $.fn.isotope == 'function' ){
			$('.ts-portfolio-wrapper.ts-masonry .portfolio-inner').isotope({filter: '*'});
		}
	});
	
	$('.ts-portfolio-wrapper .filter-bar li').bind('click', function(){
		$(this).siblings('li').removeClass('current');
		$(this).addClass('current');
		var container = $(this).parents('.ts-portfolio-wrapper').find('.portfolio-inner');
		var data_filter = $(this).data('filter');
		container.isotope({filter: data_filter});
	});
	
	/* Load more + Slider */
	$('.ts-portfolio-wrapper').each(function(){
		var element = $(this);
		var atts = element.data('atts');
		var is_slider = parseInt(atts.is_slider);
		var auto_play = parseInt(atts.auto_play)?true:false;
		var show_nav = parseInt(atts.show_nav)?true:false;
		var columns = parseInt(atts.columns);
		
		element.find('a.load-more').bind('click', function(){
			var button = $(this);
			if( button.hasClass('loading') ){
				return false;
			}
			
			button.addClass('loading');
			var paged = button.attr('data-paged');
			
			$.ajax({
				type : "POST",
				timeout : 30000,
				url : ts_shortcode_params.ajax_uri,
				data : {action: 'ts_portfolio_load_items', paged: paged, atts : atts},
				error: function(xhr,err){
					
				},
				success: function(response) {
					button.removeClass('loading');
					button.attr('data-paged', ++paged);
					if( response != 0 && response != '' ){
						if( typeof $.fn.isotope == 'function' ){										
							element.find('.portfolio-inner').isotope('insert', $(response));
							element.find('.filter-bar li.current').trigger('click');
							setTimeout(function(){
								element.find('.portfolio-inner').isotope('layout');
							}, 200);
						}
					}
					else{ /* No results */
						button.parent().remove();
					}
				}
			});
			
			return false;
		});
		
		if( is_slider ){
			$(window).load(function(){
				var slider_data = {
					loop : true
					,nav : show_nav
					,navText: [,]
					,dots : false
					,navSpeed: 1000
					,slideBy: 1
					,rtl: $('body').hasClass('rtl')
					,margin : 0
					,navRewind: false
					,autoplay: auto_play
					,autoplayTimeout: 5000
					,autoplayHoverPause: true
					,autoplaySpeed: false
					,mouseDrag: true
					,touchDrag: true
					,responsiveBaseElement: element
					,responsiveRefreshRate: 400
					,responsive: {
						0: {
							items: 1
						},
						500: {
							items: 2
						},
						1000: {
							items: 3
						},
						1300: {
							items : columns
						}
					}
					,onInitialized: function(){
						element.addClass('loaded').removeClass('loading');
					}
				};
				element.find('.portfolio-inner').owlCarousel(slider_data);
			});
		}
		
	});
	
	/* Update like */
	$('.ts-portfolio-wrapper .icon-group .like, .single-portfolio .portfolio-like .ic-like').bind('click', function(e){
		var _this = $(this);
		
		if( _this.hasClass('loading') ){
			return false;
		}
		_this.addClass('loading');
		
		var already_like = _this.hasClass('already-like');
		var is_single = _this.hasClass('ic-like');
		
		var post_id = _this.data('post_id');
		$.ajax({
			type : "POST",
			timeout : 30000,
			url : ts_shortcode_params.ajax_uri,
			data : {action: 'ts_portfolio_update_like', post_id: post_id},
			error: function(xhr,err){
				_this.removeClass('loading');
			},
			success: function(response) {
				if( response != '' ){
					if( already_like ){
						_this.removeClass('already-like');
						if( !is_single ){
							_this.attr('title', _this.data('like-title'));
						}
					}
					else{
						_this.addClass('already-like');
						if( !is_single ){
							_this.attr('title', _this.data('liked-title'));
						}
					}
					if( !is_single ){
						_this.text(response);
					}
					else{
						_this.siblings('.like-num').text(response);
					}
				}
				_this.removeClass('loading');
			}
		});
		
		return false;
	});
	
	/*** Reload SoundClound Iframe ***/
	$(window).load(function(){
		$('.owl-item .ts-soundcloud iframe').each(function(){
			var iframe = $(this);
			var src = iframe.attr('src');
			iframe.attr('src', src);
		});
	});
	
	/*** Twitter slider ***/
	$(window).load( function(){
		$('.ts-twitter-slider, .ts-testimonial-wrapper.ts-slider').each(function(){
			var element = $(this);
			var validate_slider = true;
			
			if( element.find('.item').length <= 1 ){
				validate_slider = false;
			}
			
			if( validate_slider ){
				var show_nav = false;
				var show_dots = false;
				var autoplay = false;
				if( element.data('nav') ){
					show_nav = true;
				}
				if( element.data('dots') ){
					show_dots = true;
				}
				if( element.data('autoplay') ){
					autoplay = true;
				}
				
				var slider_data = {
					items: 1
					,loop: true
					,nav: show_nav
					,dots: show_dots
					,animateIn: 'fadeIn'
					,animateOut: 'fadeOut'
					,navText: [,]
					,navSpeed: 1000
					,slideBy: 1
					,rtl: $('body').hasClass('rtl')
					,margin: 0
					,navRewind: false
					,autoplay: autoplay
					,autoplayTimeout: 5000
					,autoplayHoverPause: true
					,autoplaySpeed: false
					,mouseDrag: false
					,touchDrag: true
					,responsive:{
						0:{
							items : 1
						}
					}
					,onInitialized: function(){
						element.addClass('loaded').removeClass('loading');
					}
				};
				element.owlCarousel(slider_data);
			}
			else{
				element.removeClass('loading');
			}
		});
	});
	
	/*** Milestone ***/
	if( typeof $.fn.waypoint == 'function' && typeof $.fn.countTo == 'function' ){
		$('.ts-milestone').waypoint(function(){
			if( typeof this.disable == 'function' ){
				this.disable();
				var element = $(this.element);
				var end_num = element.data('number');
			}
			else{ /* Fix for old version of waypoint */
				var element = $(this);
				var end_num = element.data('number');
			}
			
			element.find('.number').countTo({
							from: 0
							,to: end_num
							,speed: 1500
							,refreshInterval: 30
						});
		}, {offset: '105%', triggerOnce: true});
	}
	
	/*** Button Popup ***/
	if( typeof $.fn.prettyPhoto == 'function' ){
		$('.ts-button.ts-button-popup').prettyPhoto({
			theme : 'ts-lightbox'
			,social_tools: false
			,show_title: false
			,default_width: 680
			,default_height: 315
			,deeplinking: false
			,changepicturecallback: function(){
				$('.pp_pic_holder.ts-lightbox').addClass('loaded');
			}
		});
	}
	
	/*** Google Map ***/
	function ts_gmap_initialize( map_content_obj, address, zoom, map_type, title ){
		var geocoder, map;
		geocoder = new google.maps.Geocoder();
	
		geocoder.geocode( {'address': address}, function(results, status) {
			if( status == google.maps.GeocoderStatus.OK ){
				var _ret_array =  new Array(results[0].geometry.location.lat(),results[0].geometry.location.lng());
				map.setCenter(results[0].geometry.location);
				var marker = new google.maps.Marker({
					map: map
					,title: title
					,position: results[0].geometry.location
				});
			}
		});
		
		var mapCanvas = map_content_obj.get(0);
		var mapOptions = {
			center: new google.maps.LatLng(44.5403, -78.5463)
			,zoom: zoom
			,mapTypeId: google.maps.MapTypeId[map_type]
			,scrollwheel : false
			,zoomControl : true
			,panControl : true
			,scaleControl : true
			,streetViewControl : false
			,overviewMapControl : true
			,disableDoubleClickZoom : false
		}
		map = new google.maps.Map(mapCanvas, mapOptions)
	}
	
	$(window).bind('load resize', function(){
		$('.google-map-container').each(function(){
			var element = $(this);
			var map_content = $(this).find('> div');
			var address = element.data('address');
			var zoom = element.data('zoom');
			var map_type = element.data('map_type');
			var title = element.data('title');
			ts_gmap_initialize( map_content, address, zoom, map_type, title );
		});
	});
	
});

function zeroise( str, max ){
	str = str.toString();
	return str.length < max ? zeroise('0' + str, max) : str;
}