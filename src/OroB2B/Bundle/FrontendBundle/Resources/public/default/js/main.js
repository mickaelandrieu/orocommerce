require(['jquery', 'orob2bfrontend/default/js/app'], function(jQuery) {
    require(['lodash', 'chosen', 'slick', 'bootstrapDatepicker', 'raty', 'perfectScrollbar', 'fastclick'], function(
        _,
        chosen,
        slick,
        datepicker,
        raty,
        perfectScrollbar,
        FastClick
    ) {

        (function($) {
            'use strict';

            var oroCommerce = OroCommerce();

            function OroCommerce() {
                var app = app || {},
                    catalogViews = {
                        gallery: 'gallery-view',
                        list: 'list-view',
                        noImage: 'no-image-view'
                    };

                app.init = function() {
                    sidebarToggleBinding(); //Sidebar toggling binding function
                    oroMPAccordionBinding(); //Multi pupose accordion toggling binding function
                    tabToggleBinding(); //Tabs toggling binding function
                    ratyInit(); //Initing function of raty plugin
                    chosenInit(); // Initing function of chosen (custom selectboxes) plugin
                    stickyNavigationInit(); // Init mobile sticky navigation
                    ieDetectInit(); //InterNet Explorer detecting function
                    mobileNavigationButtonsBinding(); //Mobile navigation button binding
                    clickElseWhereBinding(); //Click outside the container binding
                    toggleNestedListBinding(); //Toggling of nested lists binding
                    heroSliderInit(); //Initing of hero slider (fading slick slider)
                    productsSliderInit(); //Initing of products slider (slick slider)
                    moreInfoExpandBinding(); //More info button binding
                    pinClickBinding(); //Pin click binding
                    customCheckboxBinding(); //Custom checkbox click binding
                    customRadioBinding(); //Custom radio click binding
                    filterWidgetToggleBinding(); //Filter Widget toggle binding
                    datepickerInit(); //Initing of the datepicker
                    datepickerSetDateBindingInit();
                    topbarButtonsBinding(); //Topbar buttons clicks binding
                    customInputFileInit(); //Custom input file initing
                    salesPanelToggleInit(); //Sales panel toggle binding
                    flexSelectResizeInit(); //resizing the selects
                    salesMobileNavMenuInit(); //sales mobile navigation
                    stickyCatalogHeaderInit(); //sticky catalog, cart header
                    avoidDropdwonMenuHideInit();
                    dropdownMenuHideByCloseBtnBinding();
                    customScrollbarInit();

                    countInit().init({
                        plus: '[data-count-plus]',
                        minus: '[data-count-minus]',
                        inputNum: '[data-count-input]'
                    });

                    //On page load init list view catalog
                    catalogViewTemplatingInit(catalogViews.list);

                    //fast click for mobile devices
                    FastClick.attach(document.body);
                };

                function sidebarToggleBinding() {
                    var btn = '[data-sidebar-trigger]';

                    $(document).on('click', btn, function(event) {
                        $(this)
                            .toggleClass('active')
                            .closest('[data-collapse-sidebar]').toggleClass('sidebar_collapsed')
                            .end()
                            .closest('.main').find('[data-collapse-content]').toggleClass('content_sidebar-collapsed')
                            .end()
                            .closest('.wrapper').find('[data-collapse-search]').toggleClass('search-section_sidebar-collapsed');


                        //Reinitializing of hero carousel
                        if ($('[data-hero-slider]').length) {
                            $('[data-hero-slider]').slick('unslick');
                            heroSliderInit();
                        }

                        //Reinitializing of products carousel
                        if ($('[data-products-slider]').length) {
                            $('[data-products-slider]').slick('unslick');
                            productsSliderInit();
                        }
                    });
                }

                function oroMPAccordionBinding() {
                    var trigger = '[data-oro-mpa-trigger]';

                    $(document).on('click', trigger, function(event) {
                        var $widget = $(this).closest('[data-oro-mpa-widget]'),
                            $container = $widget.find('[data-oro-mpa-container]');

                        $widget.toggleClass('oro-mp-widget__list-expanded');
                        $container.toggleClass('hidden');

                        event.preventDefault();
                    });
                }

                function tabToggleBinding() {
                    var tabTrigger = '[data-tab-trigger]',
                        horizontalTab = '[data-horizontal-tab]';

                    $(document).on('click', tabTrigger, function(event) {
                        var isHorizontal = $(this).data('tab-type-horizontal') || false,
                            tabId = $(this).data('tab-trigger');

                        if (isHorizontal) {
                            $(horizontalTab).removeClass('active');
                            $(tabTrigger).closest('[data-tab-nav-list]').find('li').removeClass('active');

                            $(this).parent().addClass('active');

                            $(horizontalTab).each(function() {
                                if ($(this).data('horizontal-tab') === tabId) {
                                    $(this).addClass('active');
                                }
                            });

                        } else {
                            if ($(this).closest('[data-tab]').hasClass('active')) {
                                $(this).closest('[data-tab]').toggleClass('active');
                            } else {
                                $('[data-tab]').removeClass('active');
                                $(this).closest('[data-tab]').addClass('active');
                            }
                        }

                        //Reinitializing of products carousel
                        if ($('[data-products-slider]').length) {
                           $('[data-products-slider]').slick('unslick');
                           productsSliderInit();
                        }

                        event.stopPropagation();
                        event.preventDefault();
                    });
                }

                function ratyInit() {
                    var $rating = $('[data-rating]'),
                        $rated = $('[data-rated]');

                    if ($rating.length) {
                        $rating.each(function() {
                            $(this).raty({
                                number: 4,
                                score: $(this).data('rating') !== '' ? $(this).data('rating') : 3,
                                starType: 'i'
                            });
                        });
                    }

                    if ($rated.length) {
                        $rated.raty({
                            readOnly: true,
                            number: 4,
                            score: 3,
                            starType: 'i'
                        });
                    }
                }

                function chosenInit() {
                    var $selectbox = $('[data-chosen-selectbox]');

                    if ($selectbox.length) {
                        $selectbox.chosen({
                            disable_search_threshold: 10,
                            width: "100%"
                        });
                    }
                }

                function stickyNavigationInit() {
                    var $sticky = $('[data-sticky-navigation]'),
                        $main = $('.main'),
                        $win = $(window);

                    $win.on('scroll', function() {
                        if (this.hasOwnProperty('scrollY')) {
                            if (this.scrollY >= 53) {
                                $sticky.addClass('sticky');
                                $main.addClass('sticky-nav');
                            } else {
                                $sticky.removeClass('sticky');
                                $main.removeClass('sticky-nav');
                            }
                        } else {
                            if (this.pageYOffset >= 53) {
                                $sticky.addClass('sticky');
                                $sticky.addClass('sticky');
                            } else {
                                $sticky.removeClass('sticky');
                                $main.removeClass('sticky-nav');
                            }
                        }
                    });
                }

                function ieDetectInit() {
                    if (window.navigator.appName.indexOf("Internet Explorer") !== -1) {
                        $('html').addClass('ie');
                    }

                    if (window.navigator.msPointerEnabled) {
                        $('html').addClass('ie');
                    }
                }

                function mobileNavigationButtonsBinding() {
                    var btn = '[data-trigger-mobile-navigation]',
                        container = '[data-mobile-navigation-dropdown]';

                    $(document).on('click', btn, function(event) {
                        //Switch on current target button and container
                        if ($(this).attr('data-open') === 'true') {
                            $(this).attr('data-open', false);
                            $(this).removeClass('active');
                            $(this).find(container).removeClass('active');

                            //Show browser native srcollbar
                            $('body.hidden-scrollbar').removeClass('hidden-scrollbar').css('overflow-y', 'auto');
                        } else {
                            //Switch off all other navigation buttons, and containers
                            $(btn).attr('data-open', false);
                            $(btn).removeClass('active');
                            $(container).removeClass('active');

                            $(this).attr('data-open', true);
                            $(this).addClass('active');
                            $(this).find(container).addClass('active');

                            //Hide browser native srcollbar
                            $('body').addClass('hidden-scrollbar').css('overflow-y', 'hidden');
                        }

                        //Resize drwopdown according to window height
                        mobileNavigitionDropdownToWindowSize('[data-mobile-navigation-dropdown]');

                        event.preventDefault();
                        event.stopPropagation();
                        window.event.cancelBubble = true;
                    });
                }

                function clickElseWhereBinding() {
                    var $btn = $('[data-trigger-mobile-navigation]'),
                        $container = $('[data-mobile-navigation-dropdown]');

                    $(document).on('click', function(event) {
                        if (!$container.is(event.target) && $container.has(event.target).length === 0 && !$btn.is(event.target) && !$btn.has(event.target).length) {
                            //Mobile navigation item
                            $('body.hidden-scrollbar').removeClass('hidden-scrollbar').css('overflow-y', 'auto');
                            $btn.attr('data-open', false);
                            $btn.removeClass('active');
                            $container.removeClass('active');

                            //Sales mode mobile navigation item
                            $container.css('max-height', 'auto');
                            $container.css('transform', 'translateX(0)');
                            $('[data-sales-m-navigation-level]').removeClass('active');
                        }
                    });
                }

                function mobileNavigitionDropdownToWindowSize(container) {
                    var $win = $(window),
                        $nav = $('[data-sticky-navigation]'),
                        $middleBar = $('.middlebar'),
                        middleBarHeight = $middleBar.outerHeight(),
                        winHeight = $win.outerHeight(),
                        navHeight = $nav.outerHeight(),
                        $container = $(container);

                    if ($nav.hasClass('sticky')) {
                        $container.css('max-height', winHeight - navHeight);
                    } else {
                        $container.css('max-height', winHeight - navHeight - middleBarHeight);
                    }
                }

                function toggleNestedListBinding() {
                    var trigger = '[data-trigger-nested-list]',
                        $list = $('[data-nested-list]');

                    $(document).on('click', trigger, function(event) {
                        $(trigger).toggleClass('expanded');
                        $list.toggleClass('expanded');

                        event.preventDefault();
                    });
                }

                function heroSliderInit() {
                    var $hero = $('[data-hero-slider');

                    if ($hero.length) {
                        $hero.slick({
                            autoplay: true,
                            dots: true,
                            infinite: true,
                            speed: 500,
                            fade: true,
                            cssEase: 'linear',
                            prevArrow: false,
                            nextArrow: false
                        });
                    }
                }

                function productsSliderInit() {
                    var $products = $('[data-products-slider]'),
                        slidesToShowMd = $products.data('slides-to-show-md'),
                        slidesToShowSm = $products.data('slides-to-show-sm');

                    if ($products.length) {
                        $products.slick({
                            dots: false,
                            infinite: false,
                            speed: 300,
                            slidesToShow: slidesToShowMd,
                            slidesToScroll: slidesToShowMd,
                            responsive: [
                                {
                                    breakpoint: 1200,
                                    settings: {
                                        slidesToShow: slidesToShowSm,
                                        slidesToScroll: slidesToShowSm,
                                    }
                                },
                                {
                                    breakpoint: 992,
                                    settings: 'unslick'
                                }
                            ]
                        });
                    }
                }

                function moreInfoExpandBinding() {
                    var btn = '[data-more-info-expand]';

                    $(document).on('click', btn, function(event) {
                        $(this).parent().find('i').toggleClass('expanded');
                        $(this).parent().find('[data-more-info]').slideToggle();

                        event.preventDefault();
                    });
                }

                function countInit() {
                    var Count = Count || {};

                    Count.init = function(config) {
                        this.plus = config.plus;
                        this.minus = config.minus;
                        this.inputNum = config.inputNum;

                        this.plusEvent.click.call(this);
                        this.minusEvent.click.call(this);
                    };

                    Count.plusEvent = {
                        click: function() {
                            var self = this;

                            $(document).on('click', self.plus, function(event) {
                                var $input = $(this).parent().find(self.inputNum),
                                    value = $input.val(),
                                    current;

                                if (typeof value === 'string' && value === '') {
                                    value = 0;
                                    current = parseInt(value);
                                } else {
                                    current = parseInt(value);
                                }

                                current++;
                                $input.val(current);

                                event.preventDefault();
                            });
                        }
                    };

                    Count.minusEvent = {
                        click: function() {
                            var self = this;

                            $(document).on('click', self.minus, function(event) {
                                var $input = $(this).parent().find(self.inputNum),
                                    current = $input.val();

                                if (current > 1) {
                                    current--;
                                } else {
                                    return false;
                                }

                                $input.val(current);

                                event.preventDefault();
                            });
                        }
                    };

                    return Count;
                }

                function catalogViewTemplatingInit(view) {
                    var $wrapper = $('[data-products-list-wrapper]'),
                        $viewTemplate = $('[data-catalog-view-template]'),
                        viewTrigger = '[data-catalog-view-trigger]',
                        productsList = _.template($viewTemplate.html());

                    //Render template on init
                    $wrapper.html(productsList({
                        view: {
                            class: view
                        }
                    }));

                    $(document).on('click', viewTrigger, function(event) {
                        var view = $(this).data('catalog-view-trigger'),
                            $btns = $('[data-catalog-view-trigger]');

                        $btns.removeClass('current');

                        $btns.each(function(index) {
                            if ($(this).data('catalog-view-trigger') === view) {
                                $(this).addClass('current');
                            }
                        });

                        //Render template, when change the view
                        $wrapper.html(productsList({
                            view: {
                                class: view
                            }
                        }));

                        event.preventDefault();
                    });
                }

                function pinClickBinding() {
                    var pinBtn = '[data-pin-trigger]',
                        pinContent = '[data-pin-content]';

                    $(document).on('click', pinBtn,function(event) {
                        $(this).closest('.pin-widget').find(pinContent).toggle();
                        event.preventDefault();
                    });

                    $(document).on('click', function(event) {
                        if (!$(pinContent).is(event.target) && $(pinContent).has(event.target).length === 0 && !$(pinBtn).is(event.target) && !$(pinBtn).has(event.target).length) {
                            $(pinContent).hide();
                        }
                    });
                }

                function customCheckboxBinding() {
                    var label = '[data-checkbox]',
                        $checkbox = $(label).find('input');

                    $checkbox.on('change', function(event) {
                        if ($(this).attr('checked') !== 'checked' || typeof $(this).attr('checked') === 'undefined') {
                            $(this).attr('checked', true);
                            $(this).parent().addClass('checked');

                            toggleOrderPinContent(false);
                        } else {
                            $(this).attr('checked', false);
                            $(this).parent().removeClass('checked');

                            toggleOrderPinContent(true);
                        }

                        event.stopPropagation();
                    });
                }

                function customRadioBinding() {
                    var label = '[data-radio]',
                        $radio = $(label).find('input[type="radio"]');

                    $radio.on('change', function(event) {
                        var inputName = $(this).attr('name');

                        if ($(this).attr('checked') !== 'checked' || typeof $(this).attr('checked') === 'undefined') {
                            $(label).find('input[type="radio"][name="' + inputName + '"]').attr('checked', false);
                            $('input[type="radio"][name="' + inputName + '"]').closest('label').removeClass('checked');

                            $(this).attr('checked', true);
                            $(this).parent().addClass('checked');
                        }
                    });
                }

                function toggleOrderPinContent(value) {
                    var $content = $('[data-checkbox-triggered-content]');
                    value ? $content.hide() : $content.show();
                }

                function filterWidgetToggleBinding() {
                    var trigger = '[data-open-filter-trigger]',
                        filter = '[data-filter]';

                    $(document).on('click', trigger, function(event) {
                        $(this).closest('.sticky-widget').find(filter).toggleClass('opened');

                        if ($(this).closest('.sticky-widget').find(filter).hasClass('opened')) {
                            $(this).removeClass('collapsed');
                        } else {
                            $(this).addClass('collapsed')
                        }

                        event.preventDefault();
                    });
                }

                function datepickerInit() {
                    var $datepicker = $('[data-datepicker]');

                    if ($datepicker.length) {
                        $datepicker.datepicker({
                            orientation: "bottom left"
                        });
                    }
                }

                function datepickerSetDateBindingInit() {
                    var $datepicker = $('[data-datepicker]');

                    $datepicker.on('changeDate', function(event) {
                        $(this).prev().addClass('date-applied');
                    });
                }

                function topbarButtonsBinding() {
                    var trigger = '[data-topbar-dropdown-trigger]';

                    $(document).on('click', trigger, function (event) {
                        $(this).toggleClass('active');
                        event.preventDefault();
                    });
                }

                function customInputFile() {
                    // Browser supports HTML5 multiple file?
                    var multipleSupport = typeof $('<input/>')[0].multiple !== 'undefined',
                        isIE = /msie/i.test(navigator.userAgent);

                    $.fn.customFile = function() {
                        return this.each(function() {
                            var $file = $(this).addClass('custom-file-upload-hidden'), // the original file input
                                $wrap = $('<div class="file-upload-wrapper">'),
                                $input = $('<input type="text" class="file-upload-input" placeholder="No Files Uploaded" />'),
                                // Button that will be used in non-IE browsers
                                $button = $('<button type="button" class="btn theme-btn_sm btn-dark pull-left file-upload-btn">Choose</button>'),
                                // Hack for IE
                                $label = $('<label class="btn theme-btn_sm btn-dark pull-left file-upload-btn" for="'+ $file[0].id +'">Choose</label>');

                            // Hide by shifting to the left so we
                            // can still trigger events
                            $file.css({
                                position: 'absolute',
                                left: '-9999px'
                            });

                            $wrap
                                .insertAfter($file)
                                .append($file, (isIE ? $label : $button), $input);

                            // Prevent focus
                            $file.attr('tabIndex', -1);
                            $button.attr('tabIndex', -1);

                            $button.click(function () {
                                $file.focus().click(); // Open dialog
                            });

                            $file.change(function() {
                                var files = [], fileArr, filename;

                                // If multiple is supported then extract
                                // all filenames from the file array
                                if (multipleSupport) {
                                    fileArr = $file[0].files;
                                    for (var i = 0, len = fileArr.length; i < len; i++) {
                                      files.push(fileArr[i].name);
                                    }
                                    filename = files.join(', ');

                                    // If not supported then just take the value
                                    // and remove the path to just show the filename
                                } else {
                                    filename = $file.val().split('\\').pop();
                                }

                                $input
                                    .val(filename) // Set the value
                                    .attr('title', filename) // Show filename in title tootlip
                                    .focus(); // Regain focus
                            });

                            $input.on({
                                blur: function() {
                                    $file.trigger('blur');
                                },
                                keydown: function(event) {
                                    if (event.which === 13) { // Enter
                                        if (!isIE) {
                                            $file.trigger('click');
                                        }
                                    } else if (event.which === 8 || event.which === 46) { // Backspace & Del
                                        // On some browsers the value is read-only
                                        // with this trick we remove the old input and add
                                        // a clean clone with all the original events attached
                                        $file.replaceWith($file = $file.clone(true));
                                        $file.trigger('change');
                                        $input.val('');
                                    } else if (event.which === 9) { // TAB
                                        return;
                                    } else { // All other keys
                                        return false;
                                    }
                                }
                            });
                        });
                    };

                    // Old browser fallback
                    if (!multipleSupport) {
                        $(document).on('change', 'input.customfile', function() {
                            var $this = $(this),
                                // Create a unique ID so we
                                // can attach the label to the input
                                uniqId = 'customfile_'+ (new Date()).getTime(),
                                $wrap = $this.parent(),
                                // Filter empty input
                                $inputs = $wrap.siblings().find('.file-upload-input')
                                                          .filter(function(){ return !this.value }),
                                $file = $('<input type="file" id="'+ uniqId +'" name="'+ $this.attr('name') +'"/>');

                                // 1ms timeout so it runs after all other events
                                // that modify the value have triggered
                            setTimeout(function() {
                                // Add a new input
                                if ($this.val()) {
                                    // Check for empty fields to prevent
                                    // creating new inputs when changing files
                                    if (!$inputs.length) {
                                        $wrap.after($file);
                                        $file.customFile();
                                    }
                                    // Remove and reorganize inputs
                                } else {
                                    $inputs.parent().remove();
                                    // Move the input so it's always last on the list
                                    $wrap.appendTo($wrap.parent());
                                    $wrap.find('input').focus();
                                }
                            }, 1);
                        });
                    }
                }

                function customInputFileInit() {
                    customInputFile();
                    $('input[type=file]').customFile();
                }

                function salesPanelToggleInit() {
                    var handle = '[data-sales-panel-toggle]',
                        $panel = $('[data-sales-panel]'),
                        $wrapper = $('[data-wrapper]'),
                        $stickyHeader =  $('[data-sticked]'),
                        expanded = false;

                    setPanelHeight();

                    $(document).on('click', handle, function(event) {

                        if (expanded) {
                            $panel.css('transform', 'translate3d(0px, ' + -calculatePanelHeight() + 'px, 0px)');
                            $stickyHeader.removeAttr('style');
                            $wrapper.css('transform', 'translate3d(0px, 0px, 0px)');
                            expanded = false;
                        } else {
                            $panel.css('transform', 'translate3d(0px, 0px, 0px)');
                            $stickyHeader.css('transform', 'translate3d(0px, ' + calculatePanelHeight() + 'px, 0px)');
                            $wrapper.css('transform', 'translate3d(0px, ' + calculatePanelHeight() + 'px, 0px)');
                            expanded = true;
                        }

                        event.preventDefault();
                    });

                    $(window).on('resize', _.throttle(setPanelHeight, 100));
                }

                //Helper functions to calculate panel height for salesPanelToggleInit function
                function calculatePanelHeight() {
                    var $panel = $('[data-sales-panel]'),
                        $panelTop = $('.sales-panel__top'),
                        $panelBottom = $('.sales-panel__bottom'),
                        panelTopHeight = $panelTop.outerHeight(),
                        panelBottomHeight = $panelBottom.outerHeight(),
                        panelHeight;

                    if (window.innerWidth >= 992) {
                        panelHeight = panelTopHeight + panelBottomHeight - 11;
                    } else {
                        panelHeight = 130;
                    }

                    return panelHeight;
                }

                function setPanelHeight() {
                    var $panel = $('[data-sales-panel]'),
                        $wrapper = $('[data-wrapper]'),
                        $stickyHeader =  $('[data-sticked]');

                    $panel.css('transform', 'translate3d(0px, ' + -calculatePanelHeight() + 'px, 0px)');
                    $stickyHeader.removeAttr('style');

                    if (window.innerWidth >= 992) {
                        $wrapper.addClass('sales-mode');
                        $wrapper.css('transform', 'translate3d(0px, 0px, 0px)');
                    } else {
                        $wrapper.removeAttr('style').removeClass('sales-mode');
                    }
                }

                function flexSelectResizeInit() {
                    calculateSelectSize();

                    $(window).on('resize', _.debounce(calculateSelectSize, 100));
                }

                //Helper function for the flexSelectInit function
                function calculateSelectSize() {
                    var select = '[data-flex-select]',
                        winWidth = window.innerWidth;

                    if (winWidth >= 992 && winWidth <= 1400) {
                        $(select).css('width', ((winWidth - 175 - 105) / 6) + 'px');
                    }
                }

                function salesMobileNavMenuInit() {
                    var btn = '[data-sales-mobile-navigation-trigger]',
                        back = '[data-sales-m-back]';

                    $(document).on('click', btn, function(event) {
                        var type = $(this).data('sales-mobile-navigation-trigger'),
                            level = $(this).closest('[data-sales-m-navigation-level]').data('level');

                        mobileNavigitionDropdownToWindowSize('[data-sales-m-navigation-level]');
                        $('body').addClass('hidden-scrollbar').css('overflow-y', 'hidden');

                        $(this).closest('[data-sales-m-navigation-level]').css('transform', 'translateX(-100%)');
                        $('[data-sales-m-navigation-level="' + type + '"]').addClass('active');

                        event.preventDefault();
                        event.stopPropagation();
                        window.event.cancelBubble = true;
                    });

                    $(document).on('click', back, function(event) {
                        var type = $(this).data('sales-m-back');

                        $('[data-sales-m-navigation-level="' + type + '"]').removeAttr('style');
                        $(this).closest('[data-sales-m-navigation-level]').removeClass('active');

                        event.preventDefault();
                        event.stopPropagation();
                        window.event.cancelBubble = true;
                    });
                }

                function stickyCatalogHeaderInit() {
                    var sticked = '[data-sticked]',
                        $header = $('header'),
                        headerHeight = $header.height() - $('.top-nav').height(),
                        $win = $(window);

                    $win.on('scroll', function(event) {
                        if ($win.scrollTop() > headerHeight) {
                            $(sticked).addClass('sticked');
                        } else {
                            $(sticked).removeClass('sticked');
                        }
                    });
                }

                function avoidDropdwonMenuHideInit() {
                    $(document).on('click', '.dropdown-menu', function(event) {
                        event.stopPropagation();
                    });

                    $(document).on('click', '.navigation_mobile__dropdown-container', function(event) {
                        event.stopPropagation();
                    });

                    $('body').on('touchstart.dropdown', '.dropdown-menu', function (e) { e.stopPropagation(); });
                    //To fix touch event with dropdown-menu: http://stackoverflow.com/questions/17435359/bootstrap-collapsed-menu-links-not-working-on-mobile-devices/17440942#17440942
                }

                function dropdownMenuHideByCloseBtnBinding() {
                    $(document).on('click', '.close', function() {
                        $(this)
                            .closest('.dropdown-menu')
                            .parent()
                            .toggleClass('open')
                            .trigger('hide.bs.dropdown');
                    });
                }

                function customScrollbarInit() {
                    $('.columnsSettings').each(function() {
                        $(this).perfectScrollbar();
                    });

                    $(document).on('click', '.oro-oq__sorting-settings', function() {
                        $('.columnsSettings').each(function() {
                            $(this).perfectScrollbar('update');
                        });
                    });
                }

                return app;
            };

            oroCommerce.init();
        })(jQuery);

    });
});