(function ($) {

    "use strict";

    // Document ready function 
    $(function () {

        /*-------------------------------------
         Booking dates and time
         -------------------------------------*/
        var datePicker = $('.rt-date');
        if (datePicker.length) {
            datePicker.datetimepicker({
                format: 'Y-m-d',
                timepicker: false
            });
        }

        var timePicker = $('.rt-time');
        if (timePicker.length) {
            timePicker.datetimepicker({
                format: 'H:i',
                datepicker: false
            });
        }

        /*-------------------------------------
         JQuery Serch Box
         -------------------------------------*/
        $('#search-button').on('click', function (e) {
            e.preventDefault();
            $(this).prev('.search-form').slideToggle('slow');
        });


        /*-------------------------------------
         On click loadmore functionality 
         -------------------------------------*/
        $('.loadmore').on('click', 'a', function (e) {
            e.preventDefault();
            var _this = $(this),
                    _parent = _this.parents('.menu-list-wrapper'),
                    _target = _parent.find('.menu-list'),
                    _set = _target.find('.menu-item.hidden').slice(0, 4); // Herre 2 is the limit
            if (_set.length) {
                _set.animate({opacity: 0});
                _set.promise().done(function () {
                    _set.removeClass('hidden');
                    _set.show().animate({opacity: 1}, 1000);
                });
            } else {
                _this.text('No more item to display');
            }

            return false;
        });


    });

    /*-------------------------------------
     jQuery MeanMenu initialization
     --------------------------------------*/
    $('nav#dropdown').meanmenu({siteLogo: "<a href='index.html' class='logo-mobile-menu'><img src='../../img/mobile-logo.png' /></a>"});

    /*-------------------------------------
     Wow js Initiation 
     -------------------------------------*/
    new WOW().init();

    /*-------------------------------------
     Jquery Scollup Initiation
     -------------------------------------*/
    $.scrollUp({
        scrollText: '<i class="fa fa-arrow-up"></i>',
        easingType: 'linear',
        scrollSpeed: 900,
        animation: 'fade'
    });

    /*-------------------------------------
     Window load function
     -------------------------------------*/
    $(window).on('load', function () {

        // Page Preloader
        $('#preloader').fadeOut('slow', function () {
            $(this).remove();
        });

        /*-------------------------------------
         jQuery for Isotope initialization
         -------------------------------------*/
        var $container = $('#inner-isotope');

        if ($container.length > 0) {

            // Isotope initialization
            var $isotope = $container.find('.featuredContainer').isotope({
                filter: '*',
                animationOptions: {
                    duration: 750,
                    easing: 'linear',
                    queue: false
                }
            });

            // Isotope filter
            $container.find('.isotop-classes-tab').on('click', 'a', function () {

                var $this = $(this);
                $this.parent('.isotop-classes-tab').find('a').removeClass('current');
                $this.addClass('current');
                var selector = $this.attr('data-filter');
                $isotope.isotope({
                    filter: selector,
                    animationOptions: {
                        duration: 750,
                        easing: 'linear',
                        queue: false
                    }
                });
                return false;

            });
        }
    });// end window load function

    /*-------------------------------------
     About Counter
     -------------------------------------*/
    var aboutContainer = $('.about-counter');

    if (aboutContainer.length) {

        aboutContainer.counterUp({
            delay: 50,
            time: 5000
        });

    }

    /*-------------------------------------
     Contact Form initiating
     -------------------------------------*/
    var contactForm = $('#contact-form');
    if (contactForm.length) {

        contactForm.validator().on('submit', function (e) {
            var $this = $(this),
                    $target = contactForm.find('.form-response');
            if (e.isDefaultPrevented()) {
                $target.html("<div class='alert alert-success'><p>Please select all required field.</p></div>");
            } else {
                // Ajax call to load php file to process mail function
                $.ajax({
                    url: "vendor/php/form-process.php",
                    type: "POST",
                    data: contactForm.serialize(),
                    beforeSend: function () {
                        $target.html("<div class='alert alert-info'><p>Loading ...</p></div>");
                    },
                    success: function (text) {
                        if (text == "success") {
                            $this[0].reset();
                            $target.html("<div class='alert alert-success'><p>Message has been sent successfully.</p></div>");
                        } else {
                            $target.html("<div class='alert alert-success'><p>" + text + "</p></div>");
                        }
                    }
                });
                return false;
            }
        });

    }

    /*-------------------------------------
     Reservation Form initiating
     -------------------------------------*/
    var reservationForm = $('#reservation-form');
    if (reservationForm.length) {

        reservationForm.validator().on('submit', function (e) {
            var $this = $(this),
                    $target = reservationForm.find('.form-response');
            if (e.isDefaultPrevented()) {
                $target.html("<div class='alert alert-success'><p>Please select all required field.</p></div>");
            } else {
                // Ajax call to load php file to process mail function
                $.ajax({
                    url: "vendor/php/reservation-form-process.php",
                    type: "POST",
                    data: reservationForm.serialize(),
                    beforeSend: function () {
                        $target.html("<div class='alert alert-info'><p>Loading ...</p></div>");
                    },
                    success: function (text) {
                        if (text == "success") {
                            $this[0].reset();
                            $target.html("<div class='alert alert-success'><p>Message has been sent successfully.</p></div>");
                        } else {
                            $target.html("<div class='alert alert-success'><p>" + text + "</p></div>");
                        }
                    }
                });
                return false;
            }
        });

    }

    /*-----------------------------------
     get sub total function 
     *-----------------------------------*/
    var total_price = '';
    var adult_price = '';
    var child_price = '';

    $(".book_btn").on('click', function () {
        // alert("hii");
        var btn_id = $(this).attr('id');
        var id_pack = btn_id.split("_");
        var packid = btn_id.substr(5, 5);
        if ($("#subtotal_" + id_pack[1]).html() != '') {
            //        alert('hi');
        } else {
            var main_holder = $("#subtotal_" + id_pack[1]).parents(".package-price").attr('id');

            //alert(main_holder);venkatesh added
            var adult_qty = $("#" + main_holder).find(".quantity-input-adult").val();
            var child_qty = $("#" + main_holder).find(".quantity-input-child").val();
            if((packid == 30219) || (packid == 30220) || (packid == 30221) || (packid == 30222)){
            var couple_qty = $("#" + main_holder).find(".quantity-input-couple").val();
            var couple_price = $("#tariff_couple_" + id_pack[1]).val();
            }else{
           var  couple_qty = 0;
           var couple_price = 0
            }
            var couple_total_price = (parseInt(couple_price) * parseInt(couple_qty));
            var adult_total_price = (parseInt(adult_price) * parseInt(adult_qty));
            adult_price = $("#tariff_adult_" + id_pack[1]).val();
            var adult_total_price = (parseInt(adult_price) * parseInt(adult_qty));
            child_price = $("#tariff_child_" + id_pack[1]).val();
            var child_total_price = (parseInt(child_price) * parseInt(child_qty));
            total_price = (parseInt(couple_total_price) + parseInt(adult_total_price) + parseInt(child_total_price));
            if (total_price > 0) {
                $("#subtotal_" + id_pack[1]).html(total_price);
            } else {
                //            total_price  = '0';
                $("#subtotal_" + id_pack[1]).html(total_price);
            }
        }

    });
    /*-------------------------------------
     Input Quantity Up & Down initialize ---- ADULT COUNT
     -------------------------------------*/
    $(".quantity-input-couple").change(function(){
	var id = $(this).attr('id');
	var packid = id.split("_");
	func_ad(packid[1]);
    });

    $(".quantity-input-adult").change(function(){
	var id = $(this).attr('id');
	var packid = id.split("_");
	func_ad(packid[1]);
    });

    $(".quantity-input-child").change(function(){
	var id = $(this).attr('id');
	var packid = id.split("_");
	func_ad(packid[1]);
    });
    
    $('.couple-count').on('click', '.quantity-plus', function () {
        var btn_id = $(this).attr('id');
        var id_pack = btn_id.split("_");
        var $holder = $(this).parents('.couple-count');
        var $target = $holder.find('input.quantity-input-couple');
        var $quantity = parseInt($target.val(), 10);
        if ($.isNumeric($quantity) && $quantity > 0) {
            $quantity = $quantity + 1;
            $target.val($quantity);
        } else {
            $target.val($quantity);
        }
        func_ad(id_pack[1]);
    }).on('click', '.quantity-minus', function () {
        var btn_id = $(this).attr('id');
        var id_pack = btn_id.split("_");

        var $holder = $(this).parents('.couple-count');
        var $target = $holder.find('input.quantity-input-couple');
        var $quantity = parseInt($target.val(), 10);
        if ($.isNumeric($quantity) && $quantity >= 2) {
            $quantity = $quantity - 1;
            $target.val($quantity);
        }
        func_ad(id_pack[1]);
    });

    $('.adult-count').on('click', '.quantity-plus', function () {
        var btn_id = $(this).attr('id');
        var id_pack = btn_id.split("_");
        var $holder = $(this).parents('.adult-count');
        var $target = $holder.find('input.quantity-input-adult');
        var $quantity = parseInt($target.val(), 10);
        if ($.isNumeric($quantity) && $quantity >= 0) {
            $quantity = $quantity + 1;
            $target.val($quantity);
        } else {
            $target.val($quantity);
        }
        func_ad(id_pack[1]);
    }).on('click', '.quantity-minus', function () {
        var btn_id = $(this).attr('id');
        var id_pack = btn_id.split("_");

        var $holder = $(this).parents('.adult-count');
        var $target = $holder.find('input.quantity-input-adult');
        var $quantity = parseInt($target.val(), 10);
        if ($.isNumeric($quantity) && $quantity >= 2) {
            $quantity = $quantity - 1;
            $target.val($quantity);
        }
        func_ad(id_pack[1]);
    });

    /*-------------------------------------
     Input Quantity Up & Down initialize --- CHILD COUNT
     -------------------------------------*/
    $('.child-count').on('click', '.quantity-plus', function () {
        var btn_id = $(this).attr('id');
        var id_pack = btn_id.split("_");

        var $holder = $(this).parents('.child-count');
        var $target = $holder.find('input.quantity-input-child');
        var $quantity = parseInt($target.val(), 10);
        if ($.isNumeric($quantity)) {
            $quantity = $quantity + 1;
            $target.val($quantity);
        } else {
            $target.val($quantity);
        }
        func_ad(id_pack[1]);
    }).on('click', '.quantity-minus', function () {
        var btn_id = $(this).attr('id');
        var id_pack = btn_id.split("_");
        var $holder = $(this).parents('.child-count');
        var $target = $holder.find('input.quantity-input-child');
        var $quantity = parseInt($target.val(), 10);
        if ($.isNumeric($quantity) && $quantity >= 1) {
            $quantity = $quantity - 1;
            $target.val($quantity);
        }

        func_ad(id_pack[1]);
    });

//to get the price based on input text field

    function func_ad(packid) {
        if((packid == 30219) || (packid == 30220) || (packid == 30221) || (packid == 30222)){
        var $holder = $(this).parents('.couple-count');
        var $target = $holder.find('input.quantity-input-couple');
        var $quantity = parseInt($target.val(), 10);
        var cplcnt = document.getElementById('couple-qty_' + packid).value;
        var couple_price = $("#tariff_couple_" + packid).val();
        var couple_tot_price = adult_price * $quantity;
        var total_price = parseInt(couple_tot_price);
    }else{
        var couple_price = 0;
        var cplcnt = 0;
    }
        var $holder = $(this).parents('.adult-count');
        var $target = $holder.find('input.quantity-input-adult');
        var $quantity = parseInt($target.val(), 10);
        var adltcnt = document.getElementById('adlt-qty_' + packid).value;
        var adult_price = $("#tariff_adult_" + packid).val();
        var adult_tot_price = adult_price * $quantity;
        var total_price = parseInt(adult_tot_price);

        var $holder1 = $(this).parents('.child-count');
        var $target1 = $holder1.find('input.quantity-input-child');
        var $quantity1 = parseInt($target1.val(), 10);
        var chldcnt = document.getElementById('chld-qty_' + packid).value;
        var child_price = $("#tariff_child_" + packid).val();
        var child_tot_price = child_price * $quantity1;

        var tot = (cplcnt * couple_price) + (adult_price * adltcnt) + (chldcnt * child_price);
        $("#subtotal_" + packid).html(tot);
    }

    function func_ch(packid) {

        var $holder1 = $(this).parents('.child-count');
        var $target1 = $holder1.find('input.quantity-input-child');
        var $quantity1 = parseInt($target1.val(), 10);
        //   alert();
        var chldcnt = document.getElementById('chld-qty_' + packid).value;
//        alert(chldcnt);
        var child_price = $("#tariff_child_" + packid).val();
        var child_tot_price = child_price * $quantity1;
        var total_price = parseInt(child_tot_price);
//        var pack_total_amt = $('#subtotal_' + packid).html();

        var tot = child_price * chldcnt;


//        $("#subtotal_" + packid).html(parseInt(pack_total_amt) - parseInt(adult_price));
        $("#subtotal_" + packid).html(tot);
    }

    /*-------------------------------------
     Google Map
     -------------------------------------*/
    if ($('#googleMap').length) {

        //Map initialize
        var initialize = function () {
            var mapOptions = {
                zoom: 15,
                scrollwheel: false,
                center: new google.maps.LatLng(-37.81618, 144.95692)
            };
            var map = new google.maps.Map(document.getElementById("googleMap"),
                    mapOptions);
            var marker = new google.maps.Marker({
                position: map.getCenter(),
                animation: google.maps.Animation.BOUNCE,
                icon: 'img/map-marker.png',
                map: map
            });
        }

        // Add the map initialize function to the window load function
        google.maps.event.addDomListener(window, "load", initialize);
    }

    /*-------------------------------------
     Carousel slider initiation
     -------------------------------------*/
    $('.rc-carousel').each(function () {

        // Declared all carousel variable
        var carousel = $(this),
                loop = carousel.data('loop'),
                items = carousel.data('items'),
                margin = carousel.data('margin'),
                stagePadding = carousel.data('stage-padding'),
                autoplay = carousel.data('autoplay'),
                autoplayTimeout = carousel.data('autoplay-timeout'),
                smartSpeed = carousel.data('smart-speed'),
                dots = carousel.data('dots'),
                nav = carousel.data('nav'),
                navSpeed = carousel.data('nav-speed'),
                rXsmall = carousel.data('r-x-small'),
                rXsmallNav = carousel.data('r-x-small-nav'),
                rXsmallDots = carousel.data('r-x-small-dots'),
                rXmedium = carousel.data('r-x-medium'),
                rXmediumNav = carousel.data('r-x-medium-nav'),
                rXmediumDots = carousel.data('r-x-medium-dots'),
                rSmall = carousel.data('r-small'),
                rSmallNav = carousel.data('r-small-nav'),
                rSmallDots = carousel.data('r-small-dots'),
                rMedium = carousel.data('r-medium'),
                rMediumNav = carousel.data('r-medium-nav'),
                rMediumDots = carousel.data('r-medium-dots');

        // Call carousel main function to load carousel layout
        carousel.owlCarousel({
            loop: (loop ? true : false),
            items: (items ? items : 4),
            lazyLoad: true,
            margin: (margin ? margin : 0),
            autoplay: (autoplay ? true : false),
            autoplayTimeout: (autoplayTimeout ? autoplayTimeout : 1000),
            smartSpeed: (smartSpeed ? smartSpeed : 250),
            dots: (dots ? true : false),
            nav: (nav ? true : false),
            navText: ["<i class='fa fa-angle-left'></i>", "<i class='fa fa-angle-right'></i>"],
            navSpeed: (navSpeed ? true : false),
            responsiveClass: true,
            responsive: {
                0: {
                    items: (rXsmall ? rXsmall : 1),
                    nav: (rXsmallNav ? true : false),
                    dots: (rXsmallDots ? true : false)
                },
                480: {
                    items: (rXmedium ? rXmedium : 2),
                    nav: (rXmediumNav ? true : false),
                    dots: (rXmediumDots ? true : false)
                },
                768: {
                    items: (rSmall ? rSmall : 3),
                    nav: (rSmallNav ? true : false),
                    dots: (rSmallDots ? true : false)
                },
                992: {
                    items: (rMedium ? rMedium : 5),
                    nav: (rMediumNav ? true : false),
                    dots: (rMediumDots ? true : false)
                }
            }
        });

    });


    /*-------------------------------------
     Window onLoad and onResize event trigger
     -------------------------------------*/
    $(window).on('load resize', function () {

        //Define the maximum height for mobile menu
        var wHeight = $(window).height(),
                mLogoH = $('a.logo-mobile-menu').outerHeight();
        wHeight = wHeight - 50;
        $('.mean-nav > ul').css('height', wHeight + 'px');

    });


    /*-------------------------------------
     Jquery Stiky Menu at window Load
     -------------------------------------*/
    $(window).on('scroll', function () {

        var s = $('#sticker'),
                w = $('.wrapper'),
                h = s.outerHeight(),
                windowpos = $(window).scrollTop(),
                windowWidth = $(window).width(),
                h1 = s.parent('.header1-area'),
                h2 = s.parent('.header2-area'),
                h3 = s.parent('.header3-area'),
                h3H = h3.find('.header-top-area').outerHeight(),
                topBar = s.prev('.header-top-area');

        if (windowWidth > 767) {
            w.css('padding-top', '');
            var topBarH, mBottom = 0;
            if (h1.length) {
                topBarH = h = 1;
                mBottom = 0;
            } else if (h2.length) {
                mBottom = h2.find('.header-bottom-area').outerHeight();
                topBarH = topBar.outerHeight();
            } else if (h3.length) {
                topBarH = topBar.outerHeight();
            }

            if (windowpos >= topBarH) {
                s.addClass('stick');
                if (h2.length) {
                    topBar.css('margin-bottom', mBottom + 'px');
                }
            } else {
                s.removeClass('stick');
                if (h2.length) {
                    topBar.css('margin-bottom', 0);
                }
            }
        }

    });


})(jQuery);
