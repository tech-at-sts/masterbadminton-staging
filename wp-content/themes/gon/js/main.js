jQuery(document).ready(function($){
	"use strict";
	var on_touch = ts_is_touch_device();
	
	/** Check IE **/
	if( $.browser.msie ){
		var ie_version = parseInt( $.browser.version );
		if( ie_version >= 10 ){
			$('html').addClass('ie ie' + ie_version);
		}
	}
	
	/** Remove empty paragraph **/
	$('p:empty').remove();
	
	/** Mega menu **/
	ts_mega_menu_change_state($('body').innerWidth());
	$('.widget_nav_menu .menu-item-has-children .sub-menu').before('<span class="ts-menu-drop-icon"></span>');
	
	/** Menu on IPAD **/
	if( on_touch || $(window).width() < 768 ){
		ts_menu_action_on_ipad();
	}
	
	/** Popup search header version 7 **/
	if( $(window).width() > 768 ){
		ts_search_vertical_header_action();
	}
	
	/** Menu on IPAD - Vertical header **/
	if( on_touch || $(window).width() < 1239 ){
		ts_menu_vertical_header_action_on_ipad();
	}
	
	/** Sticky Menu **/
	if( typeof _ts_enable_sticky_header != 'undefined' && _ts_enable_sticky_header ){
		ts_sticky_menu();
	}
	
	/** Device - Resize action **/
	if( on_touch ){
		jQuery(window).bind('orientationchange', function(event){
			ts_mega_menu_change_state($('body').innerWidth());
			ts_set_cloud_zoom();
		});
	}
	else{
		$(window).bind('resize', $.throttle(250, function(){
			if( !( jQuery.browser.msie &&  parseInt( jQuery.browser.version, 10 ) <= 7 ) ){
				ts_mega_menu_change_state($('body').innerWidth());
				ts_set_cloud_zoom();
			}
		}));
	}
	
	/** Tini account **/
	if( on_touch ){ /* Tiny account and tiny cart dropdown on IPAD */
		$('.ts-tiny-cart-wrapper span.drop-icon').live('click', function(){
			var dropdown_form = $(this).parent().siblings('.dropdown-container');
			$(this).parent().parent().toggleClass('active');
			
			return false;
		});
	}
	
	/** Header Currency - Language selector on mobile **/
	if( on_touch ){
		$('.header-currency a.wcml_selected_currency').bind('click', function(){
			$('.header-currency').toggleClass('active');
		});
		$('.header-language a.lang_sel_sel').bind('click', function(){
			$('.header-language').toggleClass('active');
			return false;
		});
	}
	
	/** To Top button **/
	if( $('html').offset().top < 100 ){
		$("#to-top").hide().addClass("off");
	}
	$(window).scroll(function(){
		if( $(this).scrollTop() > 100 ){
			$("#to-top").removeClass("off").addClass("on");
		} else {
			$("#to-top").removeClass("on").addClass("off");
		}
	});
	$('#to-top .scroll-button').click(function(){
		$('body,html').animate({
			scrollTop: '0px'
		}, 1000);
		return false;
	});
	
	/** Quickshop **/
	ts_quickshop_handle();
	
	
	/** Wishlist **/
	$('body').bind('added_to_wishlist', function(){
		ts_update_tini_wishlist();
		$('.yith-wcwl-wishlistaddedbrowse.show, .yith-wcwl-wishlistexistsbrowse.show').closest('.button-in.wishlist').addClass('added');
	});
	
	$('#yith-wcwl-form table tbody tr td a.remove, #yith-wcwl-form table tbody tr td a.add_to_cart_button').live('click',function(){
		var old_num_product = $('#yith-wcwl-form table tbody tr[id^="yith-wcwl-row"]').length;
		var count = 1;
		var time_interval = setInterval(function(){
			count++;
			var new_num_product = $('#yith-wcwl-form table tbody tr[id^="yith-wcwl-row"]').length;
			if( old_num_product != new_num_product || count == 20 ){
				clearInterval(time_interval);
				ts_update_tini_wishlist();
			}
		},500);
	});
	
	/** Compare **/
	setTimeout(function(){
		ts_compare_change_scroll_bar();
	},1000);
	
	/*** Set Cloud Zoom ***/
	ts_set_cloud_zoom();
	
	if( $('.cloud-zoom, .cloud-zoom-gallery').length > 0 ){
		$('form.variations_form').live('found_variation',function( event, variation ){
			$('.cloud-zoom, .cloud-zoom-gallery').CloudZoom({}); 
		}).live('reset_image',function(){
			$('.cloud-zoom, .cloud-zoom-gallery').CloudZoom({});
		});
	}
	
	/*** Product Stock - Variable Product ***/
	$('form.variations_form').live('found_variation',function( event, variation ){
		var wrapper = $(this).parents('.summary');
		if( wrapper.find('.single_variation .stock').length > 0 ){
			var stock_html = wrapper.find('.single_variation .stock').html();
			var classes = '';
			if( wrapper.find('.single_variation .stock').hasClass('in-stock') ){
				classes = 'in-stock';
			}
			if( wrapper.find('.single_variation .stock').hasClass('out-of-stock') ){
				classes = 'out-of-stock';
			}
			wrapper.find('p.availability span').html(stock_html);
			wrapper.find('p.availability').removeClass('in-stock out-of-stock').addClass(classes);
		}
		else{
			var stock_html_original = wrapper.find('p.availability').data('original');
			var classes = wrapper.find('p.availability').data('class');
			if( classes == '' ){
				classes = 'in-stock';
			}
			wrapper.find('p.availability span').html(stock_html_original);
			wrapper.find('p.availability').removeClass('in-stock out-of-stock').addClass(classes);
		}
	}).live('reset_image',function(){
		var wrapper = $(this).parents('.summary');
		var stock_html_original = wrapper.find('p.availability').data('original');
		var classes = wrapper.find('p.availability').data('class');
		if( classes == '' ){
			classes = 'in-stock';
		}
		wrapper.find('p.availability span').html(stock_html_original);
		wrapper.find('p.availability').removeClass('in-stock out-of-stock').addClass(classes);
	});
	
	/*** Custom Orderby on Product Page ***/
	$('form.woocommerce-ordering ul.orderby ul a').bind('click', function(e){
		e.preventDefault();
		if( $(this).hasClass('current') ){
			return;
		}
		var form = $(this).closest('form.woocommerce-ordering');
		var data = $(this).attr('data-orderby');
		form.find('select.orderby').val(data);
		form.submit();
	});
	
	/*** Select2 - Search by Category ***/
	if( typeof $.fn.select2 == 'function' ){
		$('.ts-search-by-category select.select-category').select2();
		
		var MutationObserver = window.MutationObserver || window.WebKitMutationObserver || window.MozMutationObserver;

		$.fn.attrchange = function(callback) {
			if (MutationObserver) {
				var options = {
					subtree: false,
					attributes: true
				};

				var observer = new MutationObserver(function(mutations) {
					mutations.forEach(function(e) {
						callback.call(e.target, e.attributeName);
					});
				});

				return this.each(function() {
					observer.observe(this, options);
				});
			}
		}
		
		$('.ts-header .ts-search-by-category .select2-container').attrchange(function(attrName){
			if( attrName == 'class' ){
				if( $(this).hasClass('select2-dropdown-open') ){
					$('.select2-drop').addClass('category-dropdown');
				}
				else{
					$('.select2-drop').removeClass('category-dropdown');
				}
			}
		});
		
		/** Fix for IE9 - IE10 **/
		if( $.browser.msie ){
			var ie_version = parseInt( $.browser.version );
			if( ie_version == 9 || ie_version == 10 ){
				var search_object = $('.ts-header .ts-search-by-category .select2-container').get(0);
				if( search_object.addEventListener ){
					search_object.addEventListener('DOMAttrModified', ts_search_by_category_change_attr, false);
				}
			}
		}
		
	}
	
	/*** Widget toggle ***/
	$('.widget-title-wrapper a.block-control').bind('click', function(e){
		e.preventDefault();
		$(this).parent().siblings().slideToggle(400);
        $(this).toggleClass('active');
	});
	
	ts_widget_toggle();
	if( !on_touch ){
		$(window).bind('resize', $.throttle(250, function(){
			ts_widget_toggle();
		}));
	}
	/* Background Parallax */
	if( !on_touch ){
		$(window).load(function(){
			ts_parallax_background();
		});
	}
	
	/* Product Image Lazy Load */
	$(window).load(function(){
		$('img.ts-lazy-load').load(function(){
			$(this).parents('.lazy-loading').removeClass('lazy-loading').addClass('lazy-loaded');
			if( $('img.ts-lazy-load').parents('.lazy-loading').length == 0 ){
				ts_parallax_background();
			}
		});
		
		$('img.ts-lazy-load:not(.product-image-back)').each(function(){
			if( $(this).data('src') ){
				$(this).attr('src', $(this).data('src'));
			}
		});
		
		/* Load back image after */
		$('img.ts-lazy-load.product-image-back').each(function(){
			if( $(this).data('src') ){
				$(this).attr('src', $(this).data('src'));
			}
		});
	});
	
	/* WooCommerce Quantity Increment */
	ts_woocommerce_quantity_increment($);
	
	/* Ajax Search */
	if( typeof _ts_enable_ajax_search != 'undefined' && _ts_enable_ajax_search ){
		ts_ajax_search();
	}
	
	/* Ajax Remove Cart Item */
	if( !$('body').hasClass('woocommerce-cart') && !$('body').hasClass('woocommerce-checkout') ){
		$('.cart-item-wrapper .remove').live('click', function(e){
			e.preventDefault();
			$(this).closest('li').addClass('loading');
			var cart_item_key = $(this).data('key');
			
			var $supports_html5_storage = false;
			try {
				$supports_html5_storage = ( 'sessionStorage' in window && window.sessionStorage !== null );
			} catch( err ) {
				$supports_html5_storage = false;
			}
						
			jQuery.ajax({
				type : 'POST'
				,url : _ts_ajax_uri	
				,data : {action : 'ts_remove_cart_item', cart_item_key : cart_item_key}
				,success : function(data){
					if ( data && data.fragments ) {

						$.each( data.fragments, function( key, value ) {
							$( key ).replaceWith( value );
						});

						if ( $supports_html5_storage ) {
							sessionStorage.setItem( wc_cart_fragments_params.fragment_name, JSON.stringify( data.fragments ) );
							sessionStorage.setItem( 'wc_cart_hash', data.cart_hash );
						}

						$( 'body' ).trigger( 'wc_fragments_refreshed' );
					}
				}
			});
		});
	}
	
	/* Product Category Show Top Content Widget Area */
	$('.prod-cat-show-top-content-button a').bind('click', function(){
		$(this).toggleClass('active');
		$('.product-category-top-content').slideToggle();
		return false;
	});
	
	/* Single post - Related posts - Gallery slider */
	$(window).load(function(){
		ts_single_related_post_gallery_slider();
	});
	
	/* Single Product - Variable Product options */
	$('.variations_form .ts-product-attribute .option a').bind('click', function(){
		var _this = $(this);
		var val = _this.closest('.option').data('value');
		var selector = _this.closest('.ts-product-attribute').siblings('select');
		if( selector.length > 0 ){
			if( selector.find('option[value=' + val + ']').length > 0 ){
				selector.val(val).change();
				_this.closest('.ts-product-attribute').find('.option').removeClass('selected');
				_this.closest('.option').addClass('selected');
			}
		}
		return false;
	});
	
	$('.variations_form').on('click', '.reset_variations', function(){
		$(this).closest('.variations').find('.ts-product-attribute .option').removeClass('selected');
	});
	
	/* Product thumbnails slider */
	if( $('.single-product').length > 0 ){
		/* Horizontal slider */
		var wrapper = $('.single-product .product:not(.vertical-thumbnail) .images-thumbnails .thumbnails.loading');
		wrapper.find('.product-thumbnails').owlCarousel({
				loop : true
				,nav : true
				,navText : [,]
				,dots : false
				,navSpeed : 1000
				,rtl: $('body').hasClass('rtl')
				,margin: 10
				,navRewind: false
				,autoplay: true
				,autoplayHoverPause: true
				,autoplaySpeed: 1000
				,responsiveBaseElement: wrapper
				,responsiveRefreshRate: 1000
				,responsive:{
					0:{
						items : 1
					},
					100:{
						items : 2
					},
					200:{
						items : 3
					},
					320:{
						items : 4
					},
					400:{
						items : 5
					},
					600:{
						items : 6
					}
				}
				,onInitialized: function(){
					wrapper.addClass('loaded').removeClass('loading');
				}
			});
			
			/* Vertical slider */
			var wrapper = $('.single-product .product.vertical-thumbnail .images-thumbnails .thumbnails.loading');
			
			if( wrapper.length > 0 && typeof $.fn.carouFredSel == 'function' ){
				var items = 4;
				if( $('#left-sidebar').length > 0 || $('#right-sidebar').length > 0 ){
					items = 3;
				}
				if( $('#left-sidebar').length > 0 && $('#right-sidebar').length > 0 ){
					items = 4;
				}
				
				var _slider_data = {
						items: items
						,direction: 'up'
						,width: 'auto'
						,height: '150px'
						,infinite: true
						,prev: wrapper.find('.owl-prev').selector
						,next: wrapper.find('.owl-next').selector
						,auto: {
							play: true
							,timeoutDuration: 5000
							,duration: 800
							,delay: 3000
							,items: 1
							,pauseOnHover: true
						}
						,scroll: {
							items: 1
						}
						,swipe: {
							onTouch: true
							,onMouse: true
						}
						,onCreate: function(){
							wrapper.addClass('loaded').removeClass('loading');
						}
					};
					
				wrapper.find('.product-thumbnails').carouFredSel(_slider_data);
				
				$(window).load(function(){
					$(window).trigger('resize');
				});
				
				$(window).bind('resize orientationchange', $.debounce( 250, function(){
					if( $(window).width() < 420 ){
						_slider_data.items = 2;
					}
					else if( $(window).width() < 500 ){
						_slider_data.items = 3;
					}
					else if( $(window).width() < 768 ){
						_slider_data.items = 4;
					}
					else{
						_slider_data.items = items;
					}
					wrapper.find('.product-thumbnails').trigger('configuration', _slider_data);
				} ));
			}
	}
	
	/* Single Product Video */
	jQuery('a.ts-product-video-button').prettyPhoto({
		deeplinking: false
		,opacity: 0.9
		,social_tools: false
		,default_width: 900
		,default_height: 506
		,theme: 'ts-product-video'
		,changepicturecallback: function(){
			jQuery('.ts-product-video').addClass('loaded');
		}
	});
	
	/* Related - Upsell - Crosssell products slider */
	$('.single-product .related .products, .single-product .upsells .products, .woocommerce .cross-sells .products').each(function(){
		var _this = $(this);
		if( _this.find('.product').length > 1 ){
			_this.owlCarousel({
				loop : true
				,nav : true
				,navText : [,]
				,dots : false
				,navSpeed : 1000
				,rtl: $('body').hasClass('rtl')
				,margin: 30
				,navRewind: false
				,responsiveBaseElement: _this
				,responsiveRefreshRate: 1000
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
									items : 5
								}
							}
			});
		}
	});
	
	/* Background Video */
	$(window).load( function(){
		/* Background Video - Youtube Video */
		if( typeof $.fn.YTPlayer == 'function' ){
			$('.ts-youtube-video-bg').each(function(index, element){
				var selector = $(this);
				var poster = selector.data('poster');
				var property = selector.data('property') && typeof selector.data('property') == 'string' ? eval('(' + selector.data('property') + ')') : selector.data('property');
				var vol = 50;
				if( property.mute ){
					vol = 0;
				}
				if( ! on_touch ) {
					var player = selector.YTPlayer({mute: property.mute, vol: vol});
					
					player.on('YTPStart',function(){
						selector.removeClass('pausing playing').addClass('playing');
						selector.closest('.vc_row').addClass('playing');
						if( poster ){
							selector.css({'background-image':''});
							selector.find('.mbYTP_wrapper').css({'opacity':1});
						}
						if( property.mute ){
							player.get(0).player.mute();
						}
					});
					
					player.on('YTPPause', function(){
						selector.removeClass('pausing playing').addClass('pausing');
						selector.closest('.vc_row').removeClass('playing');
						if( poster ){
							selector.css({'background-image':'url(' + poster + ')'});
							selector.find('.mbYTP_wrapper').css({'opacity':0});
						}
					});
					
					player.on('YTPChanged', function(){
						if( !property.autoPlay && poster ){
							selector.css({'background-image':'url(' + poster + ')'});
						}
					});
				}
				else if( poster ) {
					selector.css({'background-image':'url(' + poster + ')'});
				}
			});
		}
		
		/* Background Video - Hosted Video */
		$('.ts-hosted-video-bg').each(function(){
			var selector = $(this);
			var video = selector.find('video');
			var video_dom = selector.find('video').get(0);
			if( video.hasClass('loop') ){
				video_dom.loop = true;
			}
			if( video.hasClass('muted') ){
				video_dom.muted = true;
			}
			
			var poster = selector.data('poster');
			if( poster ){
				selector.css({'background-image':'url(' + poster + ')'});
			}
			
			var control = selector.find('.video-control');
			control.bind('click', function(){
				if( ! selector.hasClass('playing') ){
					video_dom.play();
					selector.css({'background-image':''});
					selector.removeClass('pausing').addClass('playing');
					selector.closest('.vc_row').addClass('playing');
				}
				else{
					video_dom.pause();
					if( poster ){
						selector.css({'background-image':'url(' + poster + ')'});
					}
					selector.removeClass('playing').addClass('pausing');
					selector.closest('.vc_row').removeClass('playing');
				}
			});
			if( ! on_touch ){
				selector.addClass('pausing');
				if( video.hasClass('autoplay') ){
					control.trigger('click');
				}
			}
		});
	});
	
	/* Accordion - scroll to activated tab */
	$('.single-product .vc_tta-accordion .vc_tta-panel-heading').bind('click', function(){
		if( $(this).parents('.vc_tta-panel').hasClass('vc_active') ){
			return;
		}
		var acc_header = $(this);
		var header_sticky = 0;
		if( !on_touch ){
			if( $('.is-sticky .header-sticky').length > 0 ){
				header_sticky = $('.is-sticky .header-sticky').height();
			}
			else if( typeof _ts_enable_sticky_header != 'undefined' && _ts_enable_sticky_header && $('.header-sticky').length > 0 ){
				header_sticky = $('.header-sticky').height();
			}
		}
		setTimeout(function(){
			$('body,html').animate({
				scrollTop: acc_header.offset().top - acc_header.height() - header_sticky
			}, 600);
		}, 600);
	});
	
	if( $('.woocommerce-tabs.accordion-tabs').length > 0 ){
		$('a.woocommerce-review-link').bind('click', function(){
			var acc_header = $('#reviews').parents('.vc_tta-panel-body').siblings('.vc_tta-panel-heading');
			if( !acc_header.parents('.vc_tta-panel').hasClass('vc_active') ){
				setTimeout(function(){
					acc_header.trigger('click');
					acc_header.find('.vc_tta-panel-title a').trigger('click');
				}, 100);
			}
		});
	}
	
	/* Custom WP Widget Categories Dropdown */
	$('.widget_categories > ul').each(function(index, ele){
		var _this = $(ele);
		var icon_toggle_html = '<span class="icon-toggle"></span>';
		var ul_child = _this.find('ul.children');
		ul_child.hide();
		ul_child.closest('li').addClass('cat-parent');
		ul_child.before( icon_toggle_html );
	});
	
	$('.widget_categories span.icon-toggle').bind('click', function(){
		var parent_li = $(this).parent('li.cat-parent');
		if( !parent_li.hasClass('active') ){
			parent_li.find('ul.children:first').slideDown();
			parent_li.addClass('active');
		}
		else{
			parent_li.find('ul.children').slideUp();
			parent_li.removeClass('active');
			parent_li.find('li.cat-parent').removeClass('active');
		}
	});
	
	$('.widget_categories li.current-cat').parents('ul.children').siblings('.icon-toggle').trigger('click');
	
	/* Blog widget */
	$('.ts-blogs-widget-wrapper.ts-slider').each(function(){
		var element = $(this);
		var show_nav = element.data('show_nav') == 1;
		var auto_play = element.data('auto_play') == 1;
		
		element.owlCarousel({
				loop : true
				,items : 1
				,nav : show_nav
				,navText: [,]
				,dots : false
				,margin: 10
				,navSpeed : 1000
				,slideBy: 1
				,rtl: $('body').hasClass('rtl')
				,navRewind: false
				,autoplay: auto_play
				,autoplayTimeout: 5000
				,autoplayHoverPause: true
				,autoplaySpeed: false
				,mouseDrag: true
				,touchDrag: true
				,responsiveRefreshRate: 1000
				,responsive:{ /* Fix for mobile */
					0 : {
						items : 1
					}
				}
				,onInitialized: function(){
					element.addClass('loaded').removeClass('loading');
				}
			});
	});
	
	/* Product Categories widget */
	$('.widget-container.ts-product-categories-widget .icon-toggle').bind('click', function(){
		var parent_li = $(this).parent('li.cat-parent');
		if( !parent_li.hasClass('active') ){
			parent_li.addClass('active');
			parent_li.find('ul.children:first').slideDown();
		}
		else{
			parent_li.find('ul.children').slideUp();
			parent_li.removeClass('active');
			parent_li.find('li.cat-parent').removeClass('active');
		}
	});
	$('.widget-container.ts-product-categories-widget').each(function(){
		var element = $(this);
		
		var parent_li = element.find('ul.children').parent('li');
		parent_li.addClass('cat-parent');
		
		element.find('li.current').parents('ul.children').siblings('.icon-toggle').trigger('click');
	});
	
	/* Product Filter By Availability */
	$('.product-filter-by-availability-wrapper > ul input[type="checkbox"]').bind('change', function(){
		$(this).parent('li').siblings('li').find('input[type="checkbox"]').attr('checked', false);
		var val = '';
		if( $(this).is(':checked') ){
			val = $(this).val();
		}
		var form = $(this).closest('ul').siblings('form');
		if( val != '' ){
			form.find('input[name="stock"]').val(val);
		}
		else{
			form.find('input[name="stock"]').remove();
		}
		form.submit();
	});
	
	/* Product Widget */
	$('.ts-products-widget-wrapper.ts-slider').each(function(){
		var element = $(this);
		var show_nav = element.data('show_nav') == 1;
		var auto_play = element.data('auto_play') == 1;
		
		element.owlCarousel({
					loop : true
					,items : 1
					,nav : show_nav
					,navText: [,]
					,dots : false
					,navSpeed : 1000
					,slideBy: 1
					,rtl: $('body').hasClass('rtl')
					,navRewind: false
					,autoplay: auto_play
					,autoplayTimeout: 5000
					,autoplayHoverPause: true
					,autoplaySpeed: false
					,mouseDrag: true
					,touchDrag: true
					,responsiveRefreshRate: 1000
					,responsive:{ /* Fix for mobile */
						0 : {
							items : 1
						}
					}
					,onInitialized: function(){
						element.addClass('loaded').removeClass('loading');
					}
				});
	});
	
	/* Recent Comment Widget */
	$('.ts-recent-comments-widget-wrapper.ts-slider').each(function(){
		var element = $(this);
		var show_nav = element.data('show_nav') == 1;
		var auto_play = element.data('auto_play') == 1;
		
		element.owlCarousel({
					loop : true
					,items : 1
					,margin : 10
					,nav : show_nav
					,navText: [,]
					,dots : false
					,navSpeed : 1000
					,slideBy: 1
					,rtl: $('body').hasClass('rtl')
					,navRewind: false
					,autoplay: auto_play
					,autoplayTimeout: 5000
					,autoplayHoverPause: true
					,autoplaySpeed: false
					,mouseDrag: true
					,touchDrag: true
					,responsiveRefreshRate: 1000
					,responsive:{ /* Fix for mobile */
						0 : {
							items : 1
						}
					}
					,onInitialized: function(){
						element.addClass('loaded').removeClass('loading');
					}
				});
	});
	
	/* Single Portfolio */
	if( !on_touch && $('.single-portfolio.layout-1').length > 0 && $('.single-portfolio .thumbnails').length > 0 ){
		$(window).bind('scroll', function(){
			var window_width = $(window).width();
			var scroll_top = $(this).scrollTop();
			var content_height = $('.single-portfolio .entry-content').height();
			var thumbnails_height = $('.single-portfolio .thumbnails').height();
			var thumbnails_top = $('.single-portfolio .thumbnails').offset().top;
			if( scroll_top > thumbnails_top && window_width > 767 ){
				var margin = scroll_top - thumbnails_top;
				if( margin + content_height > thumbnails_height ){
					margin = thumbnails_height - content_height;
				}
				if( margin >= 0 ){
					$('.single-portfolio .entry-content').css('margin-top', margin);
				}
			}
			else{
				$('.single-portfolio .entry-content').css('margin-top', 0);
			}
		});
	}
	
	/* Single Portfolio Lightbox */
	$('.single-portfolio .thumbnails a[rel^="prettyPhoto"]').prettyPhoto({
		show_title: false
		,deeplinking: false
		,social_tools: false
	});
	
	/* FullPage */
	if( !on_touch && $(window).width() > 1050 && typeof $.fn.fullpage == 'function' ){
		$(window).load(function(){
			$('body.full-page article.page').fullpage({
				sectionSelector: 'body.full-page article.page > .vc_row'
				,navigation: true
				,afterLoad: function(){
					$('html').addClass('full-page');
					$('body').removeClass('full-page');
					ts_mega_menu_change_state();
				}
			});
		});
	}
	else if( $('body').hasClass('full-page') ){
		$('html').addClass('full-page');
		$('body').removeClass('full-page');
	}
	
	/* Compatible with YITH Infinite Scrolling */
	$(document).bind( 'yith_infs_added_elem', function(){
		ts_quickshop_handle();
	});
});

/*** Mega menu ***/
function ts_mega_menu_change_state(case_size){
	if( typeof case_size == 'undefined' )
		case_size = jQuery('body').innerWidth();
	case_size += ts_get_scrollbar_width();
	
	/* Hide Mobile Menu */
	jQuery('.mobile-menu-wrapper').hide();
	jQuery('.ts-mobile-menu-icon-toggle').removeClass('active');
	/* Hide Group Meta Header */
	if( case_size < 991 ){
		jQuery('.group-meta-header').hide();
		jQuery('.ts-group-meta-icon-toggle').removeClass('active');
		
		jQuery('.ts-group-meta-icon-toggle').unbind('click');
		jQuery('.ts-group-meta-icon-toggle').bind('click', function(){
			jQuery('.group-meta-header').slideToggle(600);
			jQuery(this).toggleClass('active');
		});
	}
	/* Reset dropdown icon class on Ipad */
	jQuery('span.ts-menu-drop-icon').removeClass('active');
	
	/* Remove hide class after loading on vertical header */
	jQuery('.header-vertical ul.menu li.menu-item').removeClass('hide');
	
	/* Calc sub menu position on vertical header */
	jQuery('.header-vertical .pc-menu ul.menu > li.menu-item').each(function(){
		var sub_menu = jQuery(this).find('> .sub-menu');
		var window_height = jQuery(window).height();
		var header_top = jQuery('header.header-vertical').offset().top;
		if( sub_menu.length > 0 ){
			var sub_menu_height = sub_menu.outerHeight();
			var item_top = jQuery(this).offset().top;
			item_top -= header_top; /* Fixed header */
			
			if( item_top + sub_menu_height > window_height ){
				var top = item_top + sub_menu_height - window_height + 20;
				sub_menu.css({'top': -top, 'bottom': 'auto'});
			}
		}
	});
	
	if( case_size > 767 ){
	
		jQuery('nav.pc-menu > ul.menu').show(200);
		var padding_left = 0, container_width = 0;
		var container = jQuery('.header-sticky > .container');
		if( container.length <= 0 ){
			container = jQuery('.header-sticky');
			if( container.length <= 0 ){
				return;
			}
			container_width = container.outerWidth();
		}
		else{
			container_width = container.width();
			padding_left = parseInt(container.css('padding-left'));
		}
		var container_offset = container.offset();
		
		setTimeout(function(){
			jQuery('nav.pc-menu > ul.menu').children('.ts-megamenu-fullwidth').each(function(index, element){
				var current_offset = jQuery(element).offset();
				var left = current_offset.left - container_offset.left - padding_left;
				jQuery(element).children('ul.sub-menu').css({'width':container_width+'px','left':-left+'px'});
			});
			
			jQuery('nav.pc-menu > ul.menu').children('.ts-megamenu-columns-1, .ts-megamenu-columns-2, .ts-megamenu-columns-3, .ts-megamenu-columns-4').each(function(index, element){	
				jQuery(element).children('ul.sub-menu').css({'max-width':container_width+'px'});
				var sub_menu_width = jQuery(element).children('ul.sub-menu').outerWidth();
				var item_width = jQuery(element).outerWidth();
				jQuery(element).children('ul.sub-menu').css({'left':'-'+(sub_menu_width/2 - item_width/2)+'px'});
				
				
				var container_left = container_offset.left;
				var container_right = container_left + container_width;
				var item_left = jQuery(element).offset().left;
				
				var overflow_left = (sub_menu_width/2 > (item_left + item_width/2 - container_left));
				var overflow_right = ((sub_menu_width/2 + item_left + item_width/2) > container_right);
				if( overflow_left ){
					var left = item_left - container_left - padding_left;
					jQuery(element).children('ul.sub-menu').css({'left':-left+'px'});
				}
				if( overflow_right && !overflow_left ){
					var left = item_left - container_left - padding_left;
					left = left - ( container_width - sub_menu_width );
					jQuery(element).children('ul.sub-menu').css({'left':-left+'px'});
				}
			});
			
			/* Remove hide class after loading */
			jQuery('ul.menu li.menu-item').removeClass('hide');
			
		},800);
		
	}
	else{ /* Mobile menu action */
		jQuery('nav.pc-menu > ul.menu').hide(200);
		
		jQuery('.ts-mobile-menu-icon-toggle').unbind('click');
		jQuery('.ts-mobile-menu-icon-toggle').bind('click', function(){
			jQuery('.mobile-menu-wrapper').slideToggle(350);
			jQuery(this).toggleClass('active');
		});
		
		/* Remove hide class after loading */
		jQuery('ul.menu li.menu-item').removeClass('hide');
	}
	
}

function ts_search_vertical_header_action(){
	jQuery('#ts-search-popup-button-open').removeClass('active');
	
	jQuery('#ts-search-popup-button-open').bind('click', function(){
		if( jQuery('#ts-search-popup-button-open').hasClass('active') ){
			jQuery('.header-v7 #page').removeClass('active-popup-search');
			jQuery('#ts-search-popup-button-open').removeClass('active');
		}
		else{
			jQuery('.header-v7 #page').addClass('active-popup-search');
			jQuery('#ts-search-popup-button-open').addClass('active');
		}
	});
	
	jQuery('#ts-search-popup-button-close').bind('click', function(){
		jQuery('.header-v7 #page').removeClass('active-popup-search');
		jQuery('#ts-search-popup-button-open').removeClass('active');
	});
}

function ts_menu_vertical_header_action_on_ipad(){
	jQuery('.header-v7 #page').removeClass('active-navi');
	
	jQuery('.ts-menu-ipad-icon-toggle-open').bind('click', function(){
		jQuery('.header-v7 #page').addClass('active-navi');
	});
	jQuery('.ts-menu-ipad-icon-toggle-close').bind('click', function(){
		jQuery('.header-v7 #page').removeClass('active-navi');
		jQuery('nav.pc-menu > ul.menu').find('.sub-menu').hide();
	});
}

function ts_menu_action_on_ipad(){
	/* Vertical Menu Heading */
	jQuery('.vertical-menu-heading').bind('click',function(){
		jQuery('nav.pc-menu > ul.menu').find('.sub-menu').hide();
		jQuery('nav.pc-menu > ul.menu li.menu-item').removeClass('active');
		jQuery('span.ts-menu-drop-icon').removeClass('active');
		var is_active = jQuery(this).hasClass('active');
		var vertical_menu = jQuery(this).siblings('.vertical-menu');
		
		jQuery('.vertical-menu-heading').removeClass('active');
		jQuery('.vertical-menu-wrapper > .vertical-menu').hide();
		jQuery(this).parents('.vertical-menu').show();
		
		if( vertical_menu.length > 0 ){
			if( is_active ){
				vertical_menu.fadeOut(250);
				jQuery(this).removeClass('active');
			}
			else{
				vertical_menu.fadeIn(250);
				jQuery(this).addClass('active');
			}
		}
	});
	
	/* Vertical Menu Drop Icon */
	jQuery('.vertical-menu-wrapper span.ts-menu-drop-icon').bind('click', function(){
		var is_active = jQuery(this).hasClass('active');
		var sub_menu = jQuery(this).siblings('.sub-menu');
		
		jQuery('nav.vertical-menu span.ts-menu-drop-icon').removeClass('active');
		jQuery('nav.vertical-menu > ul.menu').find('.sub-menu').hide();
		jQuery(this).parents('.sub-menu').show();
		
		if( sub_menu.length > 0 ){
			if( is_active ){
				sub_menu.fadeOut(250);
				jQuery(this).removeClass('active');
			}
			else{
				sub_menu.fadeIn(250);
				jQuery(this).addClass('active');
			}
			jQuery(this).parents('.sub-menu').siblings('.ts-menu-drop-icon').addClass('active');
		}
	});
	
	/* Widget Menu Drop Icon */
	jQuery('.ts-menus-widget .vertical-menu span.ts-menu-drop-icon').bind('click', function(){
		var is_active = jQuery(this).hasClass('active');
		var sub_menu = jQuery(this).siblings('.sub-menu');
		
		jQuery('nav.vertical-menu span.ts-menu-drop-icon').removeClass('active');
		jQuery('nav.vertical-menu > ul.menu').find('.sub-menu').hide();
		jQuery(this).parents('.sub-menu').show();
		
		if( sub_menu.length > 0 ){
			if( is_active ){
				sub_menu.fadeOut(250);
				jQuery(this).removeClass('active');
			}
			else{
				sub_menu.fadeIn(250);
				jQuery(this).addClass('active');
			}
			jQuery(this).parents('.sub-menu').siblings('.ts-menu-drop-icon').addClass('active');
		}
	});
	
	/* Main Menu Drop Icon */
	jQuery('.main-menu span.ts-menu-drop-icon').bind('click', function(){
		jQuery('.vertical-menu-heading').removeClass('active');
		jQuery('.vertical-menu-wrapper > .vertical-menu').hide();
		
		var is_active = jQuery(this).hasClass('active');
		var sub_menu = jQuery(this).siblings('.sub-menu');
		
		jQuery('nav.main-menu span.ts-menu-drop-icon').removeClass('active');
		jQuery('nav.main-menu li.menu-item').removeClass('active');
		jQuery('nav.main-menu > ul.menu').find('.sub-menu').hide();
		jQuery(this).parents('.sub-menu').show();
		
		if( sub_menu.length > 0 ){
			if( is_active ){
				sub_menu.fadeOut(250);
				jQuery(this).removeClass('active');
				jQuery(this).parent().removeClass('active');
			}
			else{
				sub_menu.fadeIn(250);
				jQuery(this).addClass('active');
				jQuery(this).parent().addClass('active');
			}
			jQuery(this).parents('.sub-menu').siblings('.ts-menu-drop-icon').addClass('active');
		}
	});
	
	/* Mobile Menu Drop Icon */
	jQuery('.mobile-menu .sub-menu').before('<span class="ts-menu-drop-icon"></span>');
	jQuery('.mobile-menu .sub-menu').hide();
	jQuery('.mobile-menu span.ts-menu-drop-icon').bind('click', function(){
		var is_active = jQuery(this).hasClass('active');
		var sub_menu = jQuery(this).siblings('.sub-menu');
		
		if( is_active ){
			sub_menu.slideUp(250);
			sub_menu.find('.sub-menu').hide();
			sub_menu.find('.ts-menu-drop-icon').removeClass('active');
		}
		else{
			sub_menu.slideDown(250);
		}
		jQuery(this).toggleClass('active');
	});
	
}

/*** End Mega menu ***/
(function(a){jQuery.browser.ts_mobile=/android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))})(navigator.userAgent||navigator.vendor||window.opera);
function ts_is_touch_device(){
	var is_touch = !!("ontouchstart" in window) ? true : false; 
	if( jQuery.browser.ts_mobile ) {
		is_touch = true;
	}
	return is_touch;   
}

function ts_get_scrollbar_width() {
    var $inner = jQuery('<div style="width: 100%; height:200px;">test</div>'),
        $outer = jQuery('<div style="width:200px;height:150px; position: absolute; top: 0; left: 0; visibility: hidden; overflow:hidden;"></div>').append($inner),
        inner = $inner[0],
        outer = $outer[0];
     
    jQuery('body').append(outer);
    var width1 = inner.offsetWidth;
    $outer.css('overflow', 'scroll');
    var width2 = outer.clientWidth;
    $outer.remove();
 
    return (width1 - width2);
}

/*** Sticky Menu ***/
function ts_sticky_menu(){
	var on_touch = ts_is_touch_device();
	
	if( !on_touch && jQuery(window).width() > 768 ){
		var top_spacing = 0;
		if( jQuery('body').hasClass('logged-in') && jQuery('body').hasClass('admin-bar') && jQuery('#wpadminbar').length > 0 ){
			top_spacing = jQuery('#wpadminbar').height();
		}
		var top_begin = jQuery('header.ts-header').height() + 30;
		
		setTimeout( function(){
			jQuery('.header-sticky').sticky({
					topSpacing: top_spacing
					,topBegin: top_begin
					,scrollOnTop : function (){
						ts_mega_menu_change_state();
					}
					,scrollOnBottom : function (){
						ts_mega_menu_change_state();
					}					
				});
		}, 200);
		
		var old_scroll_top = 0;
		var extra_space = 650 + top_spacing + top_begin;
		if( jQuery('.top-slideshow').length > 0 ){
			extra_space += jQuery('.top-slideshow').height();
		}
		jQuery(window).scroll(function(){
			if( jQuery('.is-sticky').length > 0 ){
				var scroll_top = jQuery(this).scrollTop();
				if( scroll_top > old_scroll_top && scroll_top > extra_space ){ /* Scroll Down */
					jQuery('.is-sticky .header-sticky').addClass('header-sticky-hide');
				}
				else{ /* Scroll Up */
					if( jQuery('.is-sticky .header-sticky').hasClass('header-sticky-hide') ){
						jQuery('.is-sticky .header-sticky').removeClass('header-sticky-hide');
					}
				}
				old_scroll_top = scroll_top;
			}
		});
	}
}

/*** Quickshop Action ***/
function ts_quickshop_handle(){
	jQuery('a.quickshop').prettyPhoto({
		deeplinking: false
		,opacity: 0.9
		,social_tools: false
		,default_width: 900
		,default_height:450
		,theme: 'pp_woocommerce'
		,changepicturecallback : function(){
			jQuery("div.quantity:not(.buttons_added), td.quantity:not(.buttons_added)").addClass('buttons_added').append('<input type="button" value="+" id="add1" class="plus" />').prepend('<input type="button" value="-" id="minus1" class="minus" />');
			jQuery('.pp_inline').find('form.variations_form').wc_variation_form();
			jQuery('.pp_inline').find('form.variations_form .variations select').change();
			jQuery('body').trigger('wc_fragments_loaded');
			
			jQuery('.pp_woocommerce').addClass('loaded');

			var _this = jQuery('.ts-quickshop-wrapper .images-slider-wrapper');
			
			if( _this.find('.image-item').length <= 1 ){
				return;
			}
			
			var owl = _this.find('.image-items').owlCarousel({
					items : 1
					,loop : true
					,nav : true
					,navText : [,]
					,dots : false
					,navSpeed : 1000
					,slideBy: 1
					,rtl: jQuery('body').hasClass('rtl')
					,margin:10
					,navRewind: false
					,autoplay: false
					,autoplayTimeout: 5000
					,autoplayHoverPause: false
					,autoplaySpeed: false
					,mouseDrag: true
					,touchDrag: true
					,responsiveBaseElement: _this
					,responsiveRefreshRate: 1000
					,onInitialized: function(){
						_this.addClass('loaded').removeClass('loading');
					}
				});

		}
	});
	
}
/*** End Quickshop Action ***/

/*** Custom Wishlist ***/
function ts_update_tini_wishlist(){
	if( typeof _ts_ajax_uri == 'undefined' ){
		return;
	}
		
	var wishlist_wrapper = jQuery('.my-wishlist-wrapper');
	if( wishlist_wrapper.length == 0 ){
		return;
	}
	
	wishlist_wrapper.addClass('loading');
	
	jQuery.ajax({
		type : 'POST'
		,url : _ts_ajax_uri	
		,data : {action : 'update_tini_wishlist'}
		,success : function(response){
			var first_icon = wishlist_wrapper.children('i.fa:first');
			wishlist_wrapper.html(response);
			if( first_icon.length > 0 ){
				wishlist_wrapper.prepend(first_icon);
			}
			wishlist_wrapper.removeClass('loading');
		}
	});
}

/*** End Custom Wishlist***/

/*** Custom Compare ***/
function ts_compare_change_scroll_bar(){
	var yith_compare_wrapper = jQuery('.DTFC_ScrollWrapper');
	if( yith_compare_wrapper.length > 0 ){
		var div_html = '<div class="ts-scroll-wrapper" style="position: fixed; bottom: 0; overflow-x: auto;"><div class="ts-scroll-content" style="display: inline-block;"></div></div>';
		yith_compare_wrapper.append(div_html);
		var div_temp = yith_compare_wrapper.find(".ts-scroll-wrapper");
		var left = parseInt(yith_compare_wrapper.find(".dataTables_scroll").css("left").replace(/px/gi,"")) + parseInt(yith_compare_wrapper.parents("body").css("padding-left")) + 3; /* 3px = border of body tag + table tag */
		div_temp.css({
			width: yith_compare_wrapper.find(".dataTables_scroll .dataTables_scrollBody").width()
			,height: ts_get_scrollbar_width+"px" 
			,left: left+"px" 
		});
		yith_compare_wrapper.find(".dataTables_scroll .dataTables_scrollBody").css({"overflow":"hidden"});
		div_temp.find(".ts-scroll-content").css({
			width: yith_compare_wrapper.find(".dataTables_scroll .dataTables_scrollBody table").width()
		});
		div_temp.scroll(function(){
			yith_compare_wrapper.find(".dataTables_scrollBody").scrollLeft(jQuery(this).scrollLeft());
		});
	}
}
/*** End Custom Compare ***/

/*** Set Cloud Zoom ***/
function ts_set_cloud_zoom(){
	jQuery('.cloud-zoom, .cloud-zoom-gallery').unbind('click');
	var clz_width = jQuery('.cloud-zoom, .cloud-zoom-gallery').width();
	var clz_img_width = jQuery('.cloud-zoom, .cloud-zoom-gallery').children('img').width();
	var cl_zoom = jQuery('.cloud-zoom, .cloud-zoom-gallery').not('.on_pc');
	var temp = (clz_width-clz_img_width)/2;
	if(cl_zoom.length > 0 ){
		cl_zoom.data('zoom',null).siblings('.mousetrap').unbind().remove();
		cl_zoom.CloudZoom({ 
			adjustX:temp	
		});
	}
}

/*** Widget toggle ***/
function ts_widget_toggle(){
	if( typeof _ts_enable_responsive != 'undefined' && ! _ts_enable_responsive ){
		return;
	}
	jQuery('.wpb_widgetised_column .widget-title-wrapper a.block-control, .footer-container .widget-title-wrapper a.block-control').remove();
	var window_width = jQuery(window).width();
	window_width += ts_get_scrollbar_width();
	if( window_width >= 768 ){
		jQuery('.widget-title-wrapper a.block-control').removeClass('active').hide();
		jQuery('.widget-title-wrapper a.block-control').parent().siblings().show();
	}
	else{
		jQuery('.widget-title-wrapper a.block-control').removeClass('active').show();
		jQuery('.widget-title-wrapper a.block-control').parent().siblings().hide();
		jQuery('.wpb_widgetised_column .widget-title-wrapper, .footer-container .widget-title-wrapper').siblings().show();
	}
}

/*** WooCommerce Quantity Increment ***/
function ts_woocommerce_quantity_increment($){
	// Quantity buttons
	$( 'div.quantity:not(.buttons_added), td.quantity:not(.buttons_added)' ).addClass( 'buttons_added' ).append( '<input type="button" value="+" class="plus" />' ).prepend( '<input type="button" value="-" class="minus" />' );

	$( document ).on( 'click', '.plus, .minus', function() {

		// Get values
		var $qty		= $( this ).closest( '.quantity' ).find( '.qty' ),
			currentVal	= parseFloat( $qty.val() ),
			max			= parseFloat( $qty.attr( 'max' ) ),
			min			= parseFloat( $qty.attr( 'min' ) ),
			step		= $qty.attr( 'step' );

		// Format values
		if ( ! currentVal || currentVal === '' || currentVal === 'NaN' ) currentVal = 0;
		if ( max === '' || max === 'NaN' ) max = '';
		if ( min === '' || min === 'NaN' ) min = 0;
		if ( step === 'any' || step === '' || step === undefined || parseFloat( step ) === 'NaN' ) step = 1;

		// Change the value
		if ( $( this ).is( '.plus' ) ) {

			if ( max && ( max == currentVal || currentVal > max ) ) {
				$qty.val( max );
			} else {
				$qty.val( currentVal + parseFloat( step ) );
			}

		} else {

			if ( min && ( min == currentVal || currentVal < min ) ) {
				$qty.val( min );
			} else if ( currentVal > 0 ) {
				$qty.val( currentVal - parseFloat( step ) );
			}

		}

		// Trigger change event
		$qty.trigger( 'change' );

	});
}

/*** Ajax search ***/
function ts_ajax_search(){
	var search_string = '';
	var search_previous_string = '';
	var search_timeout;
	var search_delay = 500;
	var search_input;
	var search_cache_data = {};
	jQuery('body').append('<div id="ts-search-result-container"></div>');
	var ts_search_result_container = jQuery('#ts-search-result-container');
	
	jQuery('.ts-header:not(.header-vertical) .search-content input[name="s"]').bind('keyup', function(e){
		search_input = jQuery(this);
		ts_search_result_container.hide();
		
		search_string = jQuery.trim(jQuery(this).val());
		if( search_string.length < 2 ){
			search_input.parents('.search-content').removeClass('loading');
			return;
		}
		
		if( search_cache_data[search_string] ){
			ts_search_result_container.html(search_cache_data[search_string]);
			ts_search_result_container.show();
			search_previous_string = '';
			search_input.parents('.search-content').removeClass('loading');
			
			ts_search_result_container.find('.view-all-wrapper a').bind('click', function(e){
				e.preventDefault();
				search_input.parents('form').submit();
			});
			
			return;
		}
		
		clearTimeout(search_timeout);
		search_timeout = setTimeout(function(){
			if( search_string == search_previous_string || search_string.length < 2 ){
				return;
			}
			
			search_previous_string = search_string;
		
			search_input.parents('.search-content').addClass('loading');
			
			/* check category */
			var category = '';
			var select_category = search_input.parents('.search-content').siblings('.select-category');
			if( select_category.length > 0 ){
				category = select_category.find(':selected').val();
			}
			
			jQuery.ajax({
				type : 'POST'
				,url : _ts_ajax_uri	
				,data : {action : 'ts_ajax_search', search_string: search_string, category: category}
				,error : function(xhr,err){
					search_input.parents('.search-content').removeClass('loading');
				}
				,success : function(response){
					if( response != '' ){
						response = JSON.parse(response);
						if( response.search_string == search_string ){
							ts_search_result_container.html(response.html);
							search_cache_data[search_string] = response.html;
							
							var top = search_input.offset().top + search_input.outerHeight(true);
							var left = Math.ceil(search_input.offset().left);
							var width = search_input.outerWidth(true);
							var border_width = parseInt(search_input.parent('.search-content').css('border-left-width'));
							left -= border_width;
							width += border_width;
							if( width < 300 ){
								width = 300;
							}
							
							window_width = jQuery(window).width(); /* Overflow window */
							if( (left + width) > window_width ){
								left -= (width - search_input.outerWidth(true));
							}
							
							ts_search_result_container.css({
								'position': 'absolute'
								,'top': top
								,'left': left
								,'width': width
								,'display': 'block'
							});
							
							search_input.parents('.search-content').removeClass('loading');
							
							ts_search_result_container.find('.view-all-wrapper a').bind('click', function(e){
								e.preventDefault();
								search_input.parents('form').submit();
							});
						}
					}
					else{
						search_input.parents('.search-content').removeClass('loading');
					}
				}
			});
		}, search_delay);
	});
	
	ts_search_result_container.hover(function(){}, function(){ts_search_result_container.hide();});
	
	jQuery('body').bind('click', function(){
		ts_search_result_container.hide();
	});
	
	jQuery('.ts-search-by-category select.select-category').bind('change', function(){
		search_previous_string = '';
		search_cache_data = {};
		jQuery(this).parents('.ts-search-by-category').find('.search-content input[name="s"]').trigger('keyup');
	});
}

/*** Single post - Related posts - Gallery slider ***/
function ts_single_related_post_gallery_slider(){
	if( jQuery('.single-post .gallery figure, .list-posts .post-item .gallery figure, .ts-blogs-widget .thumbnail.gallery figure, .single-portfolio.layout-2 .thumbnails figure').length > 0 ){
		var _this = jQuery('.single-post .gallery figure, .list-posts .post-item .gallery figure, .ts-blogs-widget .thumbnail.gallery figure, .single-portfolio.layout-2 .thumbnails figure');
		var slider_data = {
			items: 1
			,loop: true
			,nav: false
			,dots: true
			,animateIn: 'fadeIn'
			,animateOut: 'fadeOut'
			,navText: [,]
			,navSpeed: 1000
			,slideBy: 1
			,rtl: jQuery('body').hasClass('rtl')
			,margin: 10
			,navRewind: false
			,autoplay: true
			,autoplayTimeout: 4000
			,autoplayHoverPause: true
			,autoplaySpeed: false
			,autoHeight: true
			,mouseDrag: false
			,touchDrag: true
			,responsive:{
				0:{
					items : 1
				}
			}
			,onInitialized: function(){
				_this.parent('.gallery').addClass('loaded').removeClass('loading');
			}
		};
		_this.each(function(){
			var validate_slider = true;
			
			if( jQuery(this).find('img').length <= 1 ){
				validate_slider = false;
			}
			
			if( validate_slider ){
			
				jQuery(this).owlCarousel(slider_data);
				
				if( jQuery(this).parents('.single-portfolio').length > 0 ){
					jQuery(this).parents('.thumbnails').addClass('gallery');
				}
				
			}
			else{
				jQuery(this).parent('.gallery').removeClass('loading');
			}
		});
	}
	
	if( jQuery('.single-post .related-posts.loading').length > 0 ){
		var _this = jQuery('.single-post .related-posts.loading');
		var slider_data = {
			loop : true
			,nav : true
			,navText : [,]
			,dots : false
			,navSpeed: 1000
			,slideBy: 1
			,rtl: jQuery('body').hasClass('rtl')
			,margin : 30
			,navRewind: false
			,autoplay: false
			,autoplayTimeout: 5000
			,autoplayHoverPause: true
			,autoplaySpeed: false
			,mouseDrag: true
			,touchDrag: true
			,responsiveBaseElement: _this
			,responsiveRefreshRate: 400
			,responsive:{
						0:{
							items : 1
						},
						640:{
							items : 2
						},
						1150:{
							items : 3
						},
						1400:{
							items : 4
						}
					}
			,onInitialized: function(){
				_this.addClass('loaded').removeClass('loading');
			}
		};
		_this.find('.content-wrapper .blogs').owlCarousel(slider_data);
	}
	
}

/*** Trigger Search By Category Change Attribute - Used for IE9-IE10 ***/
function ts_search_by_category_change_attr( event ){
	if ('attrChange' in event) {
		if( event.attrName == 'class' ){
			if( jQuery('.ts-header .ts-search-by-category .select2-container').hasClass('select2-dropdown-open') ){
				jQuery('.select2-drop').addClass('category-dropdown');
			}
			else{
				jQuery('.select2-drop').removeClass('category-dropdown');
			}
		}
	}
}

/*** Parallax Background ***/
function ts_parallax_background(){
	jQuery('.ts-parallax-bg').each(function(){
		var prlx_speed = jQuery(this).data('prlx-speed');
		if( typeof prlx_speed == 'undefined' ){
			prlx_speed = 1;
		}
		prlx_speed = parseFloat(prlx_speed);
		
		jQuery(this).parallax('50%', prlx_speed);
	});
}