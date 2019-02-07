var slider = null;
var sliderTO = null;
var showPopup = null;
var closePopup = null;
var handlePopups = null;
var ajax_is_running = false;
var switchLogins;
var prepareMapFucntion;
var mapsLoaded = false;
var mapsWaiting = [];
var initMap;
var mapMarkers = {};
var fixFlickty;
var suggestTO;
var refreshOnClosePopup = false;
var onloadCallback;

jQuery(document).ready(function($){

	//To be deleted
	$('.country-select').change( function() {
		if(ajax_is_running) {
			return;
		}
		ajax_is_running = true;

    	var city_select = $(this).closest('form').find('.city-select').first();
    	city_select.attr('disabled', 'disabled');
    	var that = this;
		$.ajax( {
			url: '/cities/' + $(this).val(),
			type: 'GET',
			dataType: 'json',
			success: function( data ) {
				city_select.attr('disabled', false)
			    .find('option')
			    .remove();
    			city_select.append('<option value="">-</option>');
			    for(var i in data.cities) {
    				city_select.append('<option value="'+i+'">'+data.cities[i]+'</option>');
			    }
			    $('.phone-code-holder').html(data.code);
			    ajax_is_running = false;
				//city_select
				//$('#modal-message .modal-body').html(data);
				$(that).trigger('changed');
			},
			error: function(data) {
				console.log(data);
			    ajax_is_running = false;
			}
		});

    });

	$('input').focus( function() {
		$(this).removeClass('has-error');
	});

    switchLogins = function(what) {
        if(what=='login') {
            $('#signin-form-popup').hide();
            $('#signin-form-popup-left').hide();
            $('#login-form-popup').show();
            $('#login-form-popup-left').show();
        } else {
            $('#signin-form-popup').show();
            $('#signin-form-popup-left').show();
            $('#login-form-popup').hide();
            $('#login-form-popup-left').hide();
        }
    }

    onloadCallback = function() {
        grecaptcha.render('captcha-div', {
          'sitekey' : '6LfmCmEUAAAAAH20CTYH0Dg6LGOH7Ko7Wv1DZlO0',
          'size' : 'compact'
        });
      };

    var loadCaptchaScript = function() {
    	if (!$('#captcha-script').length) {

		    $('body').append( $('<link rel="stylesheet" type="text/css" href="https://hosted-sip.civic.com/css/civic-modal.min.css" />') );
    		$.getScript('https://hosted-sip.civic.com/js/civic.sip.min.js', function() {
    			$('body').append( $('<script src="'+window.location.origin+'/js-trp/login.js"></script>') );
		    	$('body').append( $('<script src="'+window.location.origin+'/js-trp/upload.js"></script>') );
		    	$('body').append( $('<script src="'+window.location.origin+'/js-trp/address.js"></script>') );

	    		$('body').append( $('<script id="captcha-script" src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer"></script>') );
    		} );
    	}
    }

	showPopup = function(id, e) {
		if(id=='popup-login') {
			loadCaptchaScript();
			id = 'popup-register';
			switchLogins('login');
		} else if(id=='popup-register-dentist') {
			loadCaptchaScript();
			id = 'popup-register';
			switchLogins('register');
			$('.form-wrapper').removeClass('chosen');
			$('.form-button.white-form-button').closest('.form-wrapper').addClass('chosen');
		} else if(id=='popup-register') {
			loadCaptchaScript();
			switchLogins('register');
		} else if(id=='map-results-popup') {
			prepareMapFucntion( function() {
				    
				var search_map = new google.maps.Map(document.getElementById('search-map'), {
					center: {
						lat: parseFloat($('#search-map').attr('lat')), 
						lng: parseFloat($('#search-map').attr('lon'))
					},
					zoom: parseInt($('#search-map').attr('zoom')),
					backgroundColor: 'none'
				});

				console.log( parseInt($('#search-map').attr('zoom')) );

				mapMarkers = {};
				var bounds = new google.maps.LatLngBounds();

				$('#map-results-popup .result-container[lat]').each( function() {
					if( !$(this).attr('lat') || !$(this).attr('lon') ) {
						return false;
					}

					var did = $(this).attr('dentist-id');
					var LatLon = {
						lat: parseFloat($(this).attr('lat')), 
						lng: parseFloat($(this).attr('lon'))
					};
					mapMarkers[did] = new google.maps.Marker({
						position: LatLon,
						map: search_map,
						icon: images_path+'/map-pin-inactive.png',
						id: did,
					});

					bounds.extend(LatLon);

					google.maps.event.addListener(mapMarkers[did], 'mouseover', (function() {
						$('#map-results-popup .result-container[dentist-id="'+this.id+'"]').addClass('active');
						this.setIcon(images_path+'/map-pin-active.png');
					}).bind(mapMarkers[did]) );
					google.maps.event.addListener(mapMarkers[did], 'mouseout', (function() {
						$('#map-results-popup .result-container[dentist-id="'+this.id+'"]').removeClass('active');
						this.setIcon(images_path+'/map-pin-inactive.png');
					}).bind(mapMarkers[did]) );
					google.maps.event.addListener(mapMarkers[did], 'click', (function() {
						if( $(window).width()<768 ) {
							var container = $('.search-results-wrapper .result-container[full-dentist-id="'+this.id+'"]');
							$('#map-mobile-tooltip').html( container.html() ).attr('href', container.attr('href') ).css('display', 'flex');

							for(var i in mapMarkers) {
								mapMarkers[i].setIcon(images_path+'/map-pin-inactive.png');
							}

							this.setIcon(images_path+'/map-pin-active.png');
						} else {
							var container = $('#map-results-popup .result-container[dentist-id="'+this.id+'"]');
							for(i=0;i<3;i++) {
								container.fadeTo('slow', 0).fadeTo('slow', 1);
							}

							var st = 0;
							var prev = container.prev();
							while(prev.length) {
								st += prev.height() + 10;
								prev = prev.prev();
							}
				            $('#map-results-popup .flex-3').animate({
				                scrollTop: st
				            }, 500);
						}
					}).bind(mapMarkers[did]) );

				} );


				if(!$('#search-map').attr('worldwide')) {
					if( $('#map-results-popup .result-container[lat]').length ) {
						search_map.fitBounds(bounds);
					} else {
						search_map.setZoom(12);
					}
				}

				$('#map-results-popup .result-container').off('mouseover').mouseover( function() {
					var did = $(this).attr('dentist-id');					
					if( mapMarkers[did] ) {
						mapMarkers[did].setIcon(images_path+'/map-pin-active.png');	    								
					}
				} )
				$('#map-results-popup .result-container').off('mouseout').mouseout( function() {
					var did = $(this).attr('dentist-id');					
					if( mapMarkers[did] ) {
						mapMarkers[did].setIcon(images_path+'/map-pin-inactive.png');	    		
					}
				} )


			} );
		} else if(id=='submit-review-popup') {
			$('.questions-wrapper .question').addClass('hidden');
			if( $(window).width()<768 ) {
				$('.questions-wrapper .question .review-answers .subquestion').addClass('hidden');
			}

			$('.questions-wrapper .question input[type="hidden"]').off('change').change( function() {
				if( $(window).width()<768 ) {
					if( $(this).closest('.subquestion').next().length ) {
						$(this).closest('.subquestion').next().removeClass('hidden');
					}
				}

				var ok = true;
				$(this).closest('.question').find('input[type="hidden"]').each( function() {
					var v = parseInt($(this).val());
					if( !v ) {
						ok = false;
						return false;
					}
				} );

				if(ok) {
					$(this).closest('.question').next().removeClass('hidden');

					if( !$(this).closest('.question').next().next().hasClass('question') || $(this).closest('.question').next().hasClass('skippable') ) {
						$(this).closest('.question').next().next().removeClass('hidden');
					}
				}

	            $('.popup, .popup-inner').animate({
	                scrollTop: $('.questions-wrapper').innerHeight()
	            }, 500);

			} );

			$('.questions-wrapper .question').first().removeClass('hidden');
			$('.questions-wrapper .question').each( function() {
				$(this).find('.review-answers .subquestion').first().removeClass('hidden');
			} )
			
		} else if(id=='popup-share') {
			var url = $(e.target).closest('[share-href]').length ? $(e.target).closest('[share-href]').attr('share-href') : window.location.href;
			$('#share-url').val(url);
			$('#share-address').val(url);
			$('#popup-share .share-buttons').attr('data-href', url);


			$('#popup-share .share-buttons .share').off('click').click( function() {
				var post_url = $(this).closest('.share-buttons').attr('data-href');
				var post_title = $(document).find("title").text();;
				if ($(this).attr('network')=='fb') {
					var url = 'https://www.facebook.com/dialog/share?app_id=1906201509652855&display=popup&href=' + escape(post_url);
				} else if ($(this).attr('network')=='twt') {
					var url = 'https://twitter.com/share?url=' + escape(post_url) + '&text=' + post_title;
				}
				window.open( url , 'ShareWindow', 'height=450, width=550, top=' + (jQuery(window).height() / 2 - 275) + ', left=' + (jQuery(window).width() / 2 - 225) + ', toolbar=0, location=0, menubar=0, directories=0, scrollbars=0');
			});

		}

		$('.popup').removeClass('active');
		$('#'+id+'.popup').addClass('active');
		handlePopups();
		$('body').addClass('popup-visible');
	}

	closePopup = function() {
		$('.popup').removeClass('active');
		$('body').removeClass('popup-visible');		

		if( refreshOnClosePopup ) {
			window.location.reload();
		}
	}

	handlePopups = function(id) {
		var dataPopupClick = function(e) {
			showPopup( $(this).attr('data-popup'), e );
		}

		var dataPopupClickLogged = function(e) {
			if( user_id ) {
				showPopup( $(this).attr('data-popup-logged'), e );				
			} else {
				showPopup( 'popup-register', e );
				var cta = $('#popup-register .cta');
				cta.show();
				for(i=0;i<3;i++) {
					cta.fadeTo('slow', 0).fadeTo('slow', 1);
				}

			}
		}

		$('[data-popup]').off('click', dataPopupClick).click( dataPopupClick );
		$('[data-popup-logged]').off('click', dataPopupClickLogged).click( dataPopupClickLogged );

		// $('.fixed-popup').css( 'height', $('.fixed-popup .popup-inner').outerHeight() + 100 );
		// $('.fixed-popup').css( 'min-height', $(document).height() );

	}
	handlePopups();

	if(getUrlParameter('popup-loged')) {
		if( user_id ) {
			showPopup( getUrlParameter('popup-loged') );
		} else {
			showPopup( 'popup-register' );
			var cta = $('#popup-register .cta');
			cta.show();
			for(i=0;i<3;i++) {
				cta.fadeTo('slow', 0).fadeTo('slow', 1);
			}
		}
	}
	if(getUrlParameter('popup')) {
		showPopup( getUrlParameter('popup') );
	}

	function fix_header(e){

		if ( ($('header').outerHeight() - 20 < $(window).scrollTop()) ) {
			$('header').addClass('fixed-header');
		} else {
			$('header').removeClass('fixed-header');
		}
	}
	$(window).scroll(fix_header);
	fix_header();


	$('.special-checkbox').change( function() {
		$(this).closest('label').toggleClass('active');
	});

	$('.tab').click( function() {
		$('.tab').removeClass('active');
		$(this).addClass('active');
		$('.tab-container').removeClass('active');
		$('#'+ $(this).attr('data-tab')).addClass('active');
	});

	$('.close-popup').click( function() {
		closePopup();
	});

	$('.popup').click( function(e) {
		if( !$(e.target).closest('.popup-inner').length ) {
			closePopup();
		}
	} );

	$('#share-link-form').submit( function(e) {
        e.preventDefault();
        $(this).find('.alert').hide();

        if(ajax_is_running) {
            return;
        }
        ajax_is_running = true;

        $.post( 
            $(this).attr('action'), 
            $(this).serialize() , 
            (function( data ) {
                if(data.success) {
                	$(this).find('.alert-success').show();
                	$(this).find('[name="email"]').val('').focus();
                } else {
                	$(this).find('.alert-warning').show();
                }
                ajax_is_running = false;
            }).bind(this), "json"
        );          

        return false;

	} )


	$('.invite-tabs a').click( function() {
		$('.invite-tabs a').removeClass('active');
		$(this).addClass('active');
		$('.invite-content').hide();
		$('#invite-option-'+$(this).attr('data-invite')).show();
	});

	$('.widget-tabs a').click( function() {
		$('.widget-tabs a').removeClass('active');
		$(this).addClass('active');
		$('.widget-content').hide();
		$('#widget-option-'+$(this).attr('data-widget')).show();
	});

    $('.copy-link').click( function(e) {
    	e.preventDefault();
    	e.stopPropagation();

        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val($(this).closest('.flex').find('input').val()).select();
        document.execCommand("copy");
        $temp.remove();        

        $(this).attr('alternative', $(this).text().trim());
        $(this).html('<i class="fas fa-check-circle"></i>');

        setTimeout( (function() {
        	$(this).html( $(this).attr('alternative').length ? $(this).attr('alternative') : '<i class="far fa-copy"></i>' );
        }).bind(this), 3000 );
    } );


    $('.widget-radio').change( function(e) {
		$(this).closest('.widget-options').find('label').removeClass('active');
		$(this).closest('label').addClass('active');
	});

    $('.type-radio').change( function(e) {
		$(this).closest('.mobile-radios').find('label').removeClass('active');
		$(this).closest('label').addClass('active');
	});

	//
	//Flickty fixes
	//

	fixFlickty = function() {
		$('.flickity-slider').each( function() {
			var mh = 0;
			$(this).find('.slider-wrapper').css('height', 'auto');
			$(this).find('.slider-wrapper').each( function() {
				if( $(this).height() > mh ) {
					mh = $(this).height();
				}
			} );
			$(this).find('.slider-wrapper').css('height', mh+'px');
		} );


	}
	$(window).resize( fixFlickty );
	fixFlickty();

	$('header .profile-btn').click( function(e) {
		if($(window).width()<768) {
			e.preventDefault();
		}
	} );

	$('.slider-wrapper [href]').click( function(e) {
		e.stopPropagation();
		e.preventDefault();
		window.location.href = $(this).attr('href');
	} );



	if(!Cookies.get('no-ids')) {
		$('#ids').css('display', 'block');

		$('#ids i').click( function(e) {
			e.preventDefault();
			e.stopPropagation();
			Cookies.set('no-ids', true, { expires: 365 });
			$('#ids').hide();
		});
	}

	if(!Cookies.get('cookiebar')) {
		$('#cookiebar').css('display', 'flex');
		$('#cookiebar a.accept').click( function() {
			Cookies.set('cookiebar', true, { expires: 365 });
			$('#cookiebar').hide();
		} );
	}


	if($('img[data-tooltip]').length) {

		$('img[data-tooltip]').on('mouseover mousemove', function(e) {
			$('.partner-tooltip').text($(this).attr('data-tooltip'));
			$('.partner-tooltip').css('left', e.pageX  );
			$('.partner-tooltip').css('top', e.pageY + ($(this).outerWidth() / 2) );
			$('.partner-tooltip').show();
		});

		$('img[data-tooltip]').on('mouseout', function(e) {

			$('.partner-tooltip').hide();
		});
	}

	$('.button-sign-up-dentist').click( function() {
		fbq('track', 'DentistInitiateRegistration');
		gtag('event', 'ClickSignup', {
			'event_category': 'DentistRegistration',
			'event_label': 'InitiateDentistRegistration',
		});
	});

	$('.button-next-step').click( function() {
		gtag('event', 'ClickNext', {
			'event_category': 'DentistRegistration',
			'event_label': 'DentistRegistrationStep'+ $(this).attr('step-number'),
		});
	});

	$('.button-sign-up-patient').click( function() {
		console.log('bb');
		fbq('track', 'PatientInitiateRegistration');
		gtag('event', 'ClickSignup', {
			'event_category': 'PatientRegistration',
			'event_label': 'PatientInitiateRegistration',
		});
	});

	$('.button-login-patient').click( function() {
		fbq('track', 'PatientLogin');
		gtag('event', 'ClickLogin', {
			'event_category': 'PatientLogin',
			'event_label': 'LoginPopup',
		});
	});

});

//
//Maps stuff
//


prepareMapFucntion = function( callback ) {
    if(mapsLoaded) {
        callback();
    } else {
        mapsWaiting.push(callback);
    }
}

initMap = function () {
    mapsLoaded = true;
    for(var i in mapsWaiting) {
        mapsWaiting[i]();
    }

	$('.map').each( function(){
		var address = $(this).attr('data-address') ;

		var geocoder = new google.maps.Geocoder();
		geocoder.geocode( { 'address': address}, (function(results, status) {
			console.log(address);
			console.log(status);
	        if (status == google.maps.GeocoderStatus.OK) {
				if (status != google.maps.GeocoderStatus.ZERO_RESULTS) {
					var position = {
						lat: results[0].geometry.location.lat(), 
						lng: results[0].geometry.location.lng()
					};

					map = new google.maps.Map($(this)[0], {
						center: position,
    					scrollwheel: false,
						zoom: 15
					});

					new google.maps.Marker({
						position: position,
						map: map,
						title: results[0].formatted_address
					});

				} else {
					console.log('456');
					$(this).remove();
				}
			} else {
				console.log('123');
				$(this).remove();
			}
		}).bind( $(this) )  );

	});
}


var getUrlParameter = function(sParam) {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }
};