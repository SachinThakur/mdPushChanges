(function($) {
    var feature_ajax = false;
    var selected_products = {};
    var selected_appliproducts = {};

    Drupal.behaviors.finder = {
        attach: function(context, settings) {
            var base_url = window.location.origin;
            var base_fullurl = settings.path.curentPath; //window.location.pathname;    
            var checkurl = base_fullurl.split('/');           
            if (checkurl[1].trim() == 'quote-request' || (checkurl[1].trim() == 'rfq')) {  // && window.location.href.indexOf("customer_breakthrough_id") > -1)				
                var customer_breakthrough_id = '';
                var productid = '';
                var multi_productid = '';
                var productid = jQuery.urlParam('pid');

                if (productid) {
                    //get_parent_category_product_type(productid);
					jQuery('#step1').removeClass('active');
					jQuery('#step2').removeClass('active');
				    jQuery('#step3').addClass('active');
                    get_rfq_multiproducts(productid, 'quote-request-v2', checkurl[1].trim());
                }
                customer_breakthrough_id = jQuery.urlParam('customer_breakthrough_id');
                if (customer_breakthrough_id) {
					jQuery('#step1').removeClass('active');
					jQuery('#step2').removeClass('active');
				    jQuery('#step3').addClass('active');
                    get_rfq_multiproducts(customer_breakthrough_id, 'customerstory', checkurl[1].trim());
                }
                multi_productid =  getURLParam('pid[]');
                if(!multi_productid){
                    multi_productid =  getURLParam('pid%5b%5d');
                }
                
                if (multi_productid) {
                    get_rfq_multiproducts(multi_productid, 'multiproduct');
                }
								
            }
			/*if (checkurl[1].trim() == 'quote-request-v2') {				
				var productid = jQuery.urlParam('pid');				
				if (productid) {
					jQuery('#step1').removeClass('active');
					jQuery('#step2').removeClass('active');
				    jQuery('#step3').addClass('active');
                    get_rfq_multiproducts(productid, 'quote-request-v2', checkurl[1].trim());
                }				
				var customer_breakthrough_id = jQuery.urlParam('customer_breakthrough_id');
                if (customer_breakthrough_id) {
					jQuery('#step1').removeClass('active');
					jQuery('#step2').removeClass('active');
				    jQuery('#step3').addClass('active');
                    get_rfq_multiproducts(customer_breakthrough_id, 'customerstory', checkurl[1].trim());
                }
			}*/
			if (checkurl[1].trim() == 'quote-request'){
			    var subcat = jQuery.urlParam('cat');
				if (subcat) {			
					jQuery('#step1').removeClass('active');
					jQuery('#step2').removeClass('active');
				    jQuery('#step3').addClass('active');
				    var secstep_parent = get_steptwo_parent(subcat); //subcat id
                    get_categorypage_rfq(secstep_parent, checkurl[1].trim()); // pass subcat id	
				}
			}
						
            /**selected product request quote bootom bar code start here**/
            var checkbox_rfqs = {};
            $(document).on("change", ".quotepage-content #step3 .filter-checkbox input", function() {
                var product_count = $('.rfq_bottom_setproducts .product_count').text();
                if (product_count == 0 || !product_count) { 
                    checkbox_rfqs = {}; 
                }
                var productElement = jQuery(this);
                var productTitle = productElement.closest(".pro-container").find('.product-finder-desc a').text();
                var productid = productElement.attr("productid");
                if (productElement.is(':checked')) {
                    checkbox_rfqs['pid_' + productid] = productTitle;
                } else {
                    delete checkbox_rfqs['pid_' + productid];
                }
                checkbox_rfqs_bottom_bar(checkbox_rfqs);

            });
            $(document).on("click", ".rfq_bottom_setproducts .deselect_product", function() {
                var deselect_product_id = $(this).attr('id');
                delete checkbox_rfqs['pid_' + $(this).attr('product_id')];
                $(this).closest('.compare-cell').remove();
                jQuery('.' + deselect_product_id).prop('checked', false);
                checkbox_rfqs_bottom_bar(checkbox_rfqs);

            });
            $(document).on("click", ".rfq_bottom_setproducts .rfq_selected_reset", function() {
                 var checkbox_rfqs = {};
                jQuery('#step3 .procampcheckbox').prop('checked', false);
                checkbox_rfqs_bottom_bar(checkbox_rfqs);
            });
            /**selected product request quote bootom bar code ends here**/

            $(document).on("click", ".quotepage-content .tab-heading.side_bar_pro a", function() {
                $('.appendsteps').remove();
                var ariacontrols = $(this).attr('aria-controls');
                if (ariacontrols == 'step3') {
                    var rfqs_bottom_item_length = jQuery('.rfq_bottom_setproducts .compare-row.procompare-box2 .compare-cell').length;
                    if (rfqs_bottom_item_length > 0) { $('.rfq_bottom_setproducts').show(); }
                } 
                else {
                    $('.rfq_bottom_setproducts').html('');
                    $('.rfq_bottom_setproducts').hide();

                }

            });
            // Request Quote page js code ends here
            $('.step1').click(function() {
                var cat = $(this).attr('alt');			
                var cat_title_id = $(this).attr('id');
				var cat_title_val = $(this).attr('title');
                if (cat_title_val == 'Instruments') {
                    cat_title_val = cat_title_val.slice(0, -1);
                }		
			    if (cat_title_id == 'Instruments') {
					cat_title_id = cat_title_id.slice(0, -1);
                }
				//console.log(cat_titleval);
                $(".step1title, #step2 .selecthead").text(cat_title_val);
				$(".step1title").attr('id', cat_title_id);
                step_one(cat);
            });

            $(document).on("click", ".step2", function(event) {
                var checkbox_rfqs = {};
                var parent = $(this).attr('alt');
			
                step_two(parent);

            });
            $(document).on("click", ".path-quote-request a.step4", function() {
                var pageurl = $(location).attr("href").split('/').pop();                
				if(pageurl !== 'rfq'){
                step_three();
				}
            });

            $(".pro_specifi_hidden_values_tmp").val('');

            $(document, context).once('finder').on('change', ".prodreadmode", function(e) {
                var currentpage = jQuery('.product-finder-container').attr('currentpage');
                var parentname = $('#step2 .step1title').attr('id');
                var firststep_cat = getparentid(parentname);
                var readmode_type = $(this).val();
                var type_split = readmode_type.split('_');
                var parent = type_split[0];
                var readmode = type_split[1];
                var prodtypesval = type_split[2];

                if (readmode == 0) {
                    $(".pro_specifi_hidden_values_tmp").val('');
                }
                if (parent == 24) {
                    $(".pro_specifi_hidden_values_tmp").val('');
                }
                var prodtypemodes = $('.prodtypes :selected').val();

                var prodtypemodes_split = prodtypemodes.split('_');
                var prodtypesplit = prodtypemodes_split[1];
                if (prodtypesplit !== undefined && prodtypesplit !== "") {
                    var prodtype = prodtypesplit;
                } else {
                    var prodtype = 0;
                }
                var specifi = 0;
                $('.finder-scroll').show();
                if (parent == '')
                    return false;

                if (parent == 681 && firststep_cat == 2) {
                    var firststep_cat = 5;
                } else {
                    var firststep_cat = firststep_cat;
                }

                $.ajax({
                    url: base_url + "/productfinder/filtercat/" + parent + '/' + readmode + '/' + prodtype + '/' + specifi + '/' + firststep_cat,
                    method: 'GET',
                    data: {
                        'specifiarr': $(".pro_specifi_hidden_values_tmp").val(),
                        currentpage: currentpage,
                    },
                    dataType: "json",
                    success: function(result) {
                        $('.finder-scroll').hide();
                        $('.prodspecifi span.filter-option').html('Please Select');
                        $('.prodspecifi button.dropdown-toggle').attr('title', 'Please Select');

                        if (parent == 12) {
                            if (readmode == '0') {
                                $(".pro_specifi_hidden_values_tmp").val('');
                                $(".pro_specifi_hidden_values").val('');
                                if ($('.prodtypes .dropdown-menu li').hasClass('selected')) {
                                    $('.prodtypes .dropdown-menu li:first-child').find('a').trigger('click')
                                }
                                $('.finderprodspec .dropdown-menu li').each(function() {
                                    if ($(this).hasClass('selected')) {
                                        $(this).find('a').trigger('click')
                                    }
                                })
                                $('select.prodtypes, select.prodspecifi').prop('disabled', true);
                            } else {
                                $('select.prodtypes').prop('disabled', false);
                            }
                        }

                        if (parent == '24') {
                            $.ajax({
                                url: base_url + "/productfinder/readmodetype/" + parent + '/' + readmode + '/' + prodtype,
                                method: 'GET',
                                dataType: "json",
                                success: function(result) {
                                    if (result.readmodetype != 'no_result') {
                                        $('select.prodtypes').prop('disabled', false);
                                    }
                                    $(".cell-match-height").matchHeight();
                                    $('select.prodtypes').html(result.readmodetype);

                                    $('.selectpicker').selectpicker('refresh');
                                }
                            });

                            $.ajax({
                                url: base_url + "/productfinder/readmodespecifi/" + parent + '/' + prodtypesval + '/' + specifi,
                                method: 'GET',
                                dataType: "json",
                                success: function(result) {
                                    if (result.readmodespecifi != 'no_result') {
                                        $('select.prodspecifi').prop('disabled', false);
                                    }
                                    $(".cell-match-height").matchHeight();
                                    $('select.prodspecifi').html(result.readmodespecifi);
                                    $('.selectpicker').selectpicker('refresh');
                                    $('.finder-scroll').hide();
                                }
                            });
                        }
                        $(".all-products").html(result.allproducts);
                        $(".result-count").html(result.countprod);
                        $('.selectpicker').selectpicker('refresh');
                        $(".cell-match-height").matchHeight();
                        selectcompareproduct();


                    }
                });

                e.preventDefault();
            });

            $(document).on("click", 'a.start-again, .step1title', function(event) {
                $('.pro_specifi_hidden_values').val('');
                $('.pro_specifi_hidden_values_tmp').val('');
            });
            $(document).on("change", ".prodtypes", function(event) {
                var currentpage = jQuery('.product-finder-container').attr('currentpage');
                var specifi_arr = $(this).val();
                var parentname = $('#step2 .step1title').attr('id');
                var firststep_cat = getparentid(parentname);
                var arr_split = specifi_arr.split('_');
                var parent = arr_split[0];
                var prodtype = arr_split[1];
                var readmodes = $('.prodreadmode :selected').val();
                var readmode_split = readmodes.split('_');
                var readmode = readmode_split[1];
                var specifi = 0;
                $('.finder-scroll').show();
                if (parent == '')
                    return false;


                if (parent != '24') {
                    $.ajax({
                        url: base_url + "/productfinder/filtercat/" + parent + '/' + readmode + '/' + prodtype + '/' + specifi + '/' + firststep_cat,
                        method: 'GET',
                        data: {
                            'specifiarr': $(".pro_specifi_hidden_values_tmp").val(),
                            currentpage: currentpage,
                        },
                        dataType: "json",
                        success: function(result) {
                            $(".all-products").html(result.allproducts);
                            $(".result-count").html(result.countprod);
                            $(".step2title").html(result.sidebar_catname);
                            $('.selectpicker').selectpicker('refresh');
                            $(".cell-match-height").matchHeight();
                            $('.finder-scroll').hide();
                            selectcompareproduct();
                        }
                    });
                } else {
                    $.ajax({
                        url: base_url + "/productfinder/readmodespecifi/" + parent + '/' + prodtype + '/' + specifi,
                        method: 'GET',
                        dataType: "json",
                        success: function(result) {
                            if (result.readmodespecifi != 'no_result') {
                                $('select.prodspecifi').prop('disabled', false);
                            }
                            $(".cell-match-height").matchHeight();
                            $('select.prodspecifi').html(result.readmodespecifi);
                            $('.selectpicker').selectpicker('refresh');
                            $('.finder-scroll').hide();
                        }
                    });
                }

                if (parent == 12) {
                    if (prodtype == '0') {
                        $(".pro_specifi_hidden_values_tmp").val('');
                        $('.finderprodspec .dropdown-menu li').each(function() {
                            if ($(this).hasClass('selected')) {
                                $(this).find('a').trigger('click')
                            }
                        })
                        $('select.prodspecifi').prop('disabled', true);
                    } else {
                        $('select.prodspecifi').prop('disabled', false);
                    }
                }
            });

            $(document).on("change", "select.prodspecifi", function(e) {
                var currentpage = jQuery('.product-finder-container').attr('currentpage');
                var featureids = [];
                $.each($(".prodspecifi :selected"), function() {
                    featureids.push($(this).val());
                });
                $(".pro_specifi_hidden_values").val(featureids);
                $(".pro_specifi_hidden_values_tmp").val(featureids);

                var readmodes = $('.prodreadmode :selected').val();
                var readmode_split = readmodes.split('_');
                var parent = readmode_split[0];
                var readmode = readmode_split[1];
                var parentname = $('#step2 .step1title').attr('id');
                var firststep_cat = getparentid(parentname);
                var readmodetype = $('.prodtypes :selected').val();
                if (readmodetype != '') {
                    var readmodetype_split = readmodetype.split('_');
                    var prodtype = readmodetype_split[1];
                } else {
                    var prodtype = 0;
                }
                $('.finder-scroll').show();

                var specifi = 0;

                if (parent == '')
                    return false;

                $.ajax({
                    url: base_url + "/productfinder/filtercat/" + parent + '/' + readmode + '/' + prodtype + '/' + specifi + '/' + firststep_cat,
                    method: 'GET',
                    data: {
                        'specifiarr': $(".pro_specifi_hidden_values_tmp").val(),
                        currentpage: currentpage,
                    },
                    dataType: "json",
                    success: function(result) {
                        $(".all-products").html(result.allproducts);
                        $(".result-count").html(result.countprod);
                        $(".step2title").html(result.sidebar_catname);
                        $('.selectpicker').selectpicker('refresh');
                        $(".cell-match-height").matchHeight();
                        $('.finder-scroll').hide();
                        selectcompareproduct();
                    }
                });
                e.preventDefault();

            });


            $(document).on("click", ".start-again, span#step2title, span#step3title", function(e) {
                $(".prodtypes option").remove();
                $(".prodspecifi option").remove();
            });
			
			$('#progressBar').LineProgressbar({
				percentage:33,
				radius: '20px',
				height: '60px',
			});
			
			$('#progressBar1').LineProgressbar({
				percentage:62,
				radius: '20px',
				height: '60px',
			});
        }
    };

    function selectcompareproduct() {
        var comparecountno = 0;
        jQuery('.compare-strip .compare-row .compare-cell a.close').each(function(i, obj) {
            var selectpid = jQuery(this).attr('id');
            var splitpid = selectpid.split("_");
            var poductid = splitpid[1];
            jQuery('.finder_row .pro-container .compare-box .filter-checkbox .procampcheckbox' + poductid).prop('checked', true);
            comparecountno = comparecountno + 1;
        });
        //jQuery('.compare-box .comparecountno').text(comparecountno);
		jQuery('.compare-box input:checked').closest('.compare').find('.comparecountno').text(comparecountno);
    }


    function getparentid(parentname) {

        if (parentname == 'Instrument') {
            var parentid = 1;
        } else if (parentname == 'Reagents / Media') {
            var parentid = 2;
        } else if (parentname == 'Accessories') {
            var parentid = 3;
        } else if (parentname == 'Accessories & Consumables') {
            var parentid = 4;
        } else if (parentname == 'Software') {
            var parentid = 6;
        } else if (parentname == 'Assay Kits') {
            var parentid = 2;
        }
        return parentid;

    }
	
	function get_steptwo_parent(subcat) {

        if (subcat == 'microplate-readers') {
            var parentid = 12;
        } else if (subcat == 'cellular-imaging') {
            var parentid = 25;
        } else if (subcat == 'clone-screening' || subcat == 'biologics') {
            var parentid = 33;
        } else if (subcat == 'axon-instruments-patch-clamp-systems') {
            var parentid = 24;
        } else if (subcat == 'fliper') {
            var parentid = 545;
        }
        return parentid;
    }

    $(document).on('click', '.finderpage-class .start-again', function(event) {
        event.preventDefault();
        $('.finderpage-class .tab-pane').removeClass('active in');
        $('.finderpage-class .tab-pane:first-child').addClass('active in');
    })

    function theme_carousel() {
        $('.pro_car').each(function() {
            if ($(window).width() < 768) {
                //console.log($(window).width());
                if ($(this).find('.item').length > 1) {
                    $(this).find('.item').removeClass('col-sm-4')
                    $(this).find('.item').removeClass('two-column')
                    $(this).find('.item').removeClass('one-column')
                    $(this).find('.item').addClass('col-xs-12')
                    $(this).owlCarousel({
                        items: 1,
                        loop: true
                    })
                } else {
                    $(this).trigger('destroy.owl.carousel');
                    if ($(this).find('.item').length == 1) {
                        $(this).find('.item').addClass('col-xs-12 one-column')
                    }
                }
            } else {
                $(this).trigger('destroy.owl.carousel');
                $(this).find('.item').removeClass('[class^="col-"]')
                $(this).find('.item').addClass('col-sm-4')
            }
        })
    }
    /***bottom green bar for RFQ page only code start here***/
    function checkbox_rfqs_bottom_bar(checkbox_rfqs) {
        var checkbox_rfqs_counter = 0;
        var rfq_selected = '';
        rfq_selected += '<div class="compare-row">';
        rfq_selected += '<div class="compare-cell"><div class="compare-row procompare-box2">';
        jQuery.each(checkbox_rfqs, function(j, checkbox_rfq) {
            if (checkbox_rfqs_counter < 4) {
                var product_id = j.split("_");
                rfq_selected += "<div class='compare-cell'><div class='compare-data'> " + checkbox_rfq + "<a href='javascript:void(0)' class='close deselect_product' id='rfq_selectedpid" + product_id[1] + "' product_id='" + product_id[1] + "'><img src='/themes/moldev/images/close3.png'></a></div></div>";
            }
            checkbox_rfqs_counter++;
        });
        rfq_selected += '</div></div>';
        rfq_selected += '<div class="procompare compare-cell strip-button"><div class="compare-data"><a href="javascript:void(0);" class="step4 gradiantBlueBtn btn">Request Quote (<span class="product_count">' + checkbox_rfqs_counter + '</span>)</a><a href="javascript:void(0);" class="gradiantBlueBtn btn rfq_selected_reset" title="Reset"><i class="fa fa-close"></i></a></div></div>';
        rfq_selected += '</div>';
        jQuery('.rfq_bottom_setproducts').html(rfq_selected);

        if (Object.keys(checkbox_rfqs).length <= 0) {
            jQuery('.rfq_bottom_setproducts').hide();
        } else {
            jQuery('.rfq_bottom_setproducts').show();
        }
    }
	 /*** code to go back previous tab functionality **/
	    jQuery(document).on("click", 'a.start-again', function(event) {
			
			var href_val = $(this).attr('href');
			console.log('start-again clicked'+href_val);
			//jQuery(".tab-content .tab-pane").each(function() { 
				console.log('--loop--');
				//$(this).find('.tab-pane').removeClass('active');
				jQuery(".tab-content .tab-pane").removeClass('active');
				
			//});
			
			
			jQuery(href_val).addClass('active');
			
			jQuery('html, body').animate({
				'scrollTop' : jQuery(".rfq_main").position().top - jQuery('header').height()
			}, 1000);
		});
		
		
	
    /***bottom green bar for RFQ page only code ends here***/
    // Step one data calling
    function step_one(cat) {
        $('.proggress').css('display','none');
        var base_url = window.location.origin;
		$(".productfinder_subcat").html('');
        $('.finder-scroll').show();
		jQuery(".search-loader").css("display","block");		
        $.ajax({
            url: base_url + "/rfq-product/subcat/" + cat,
            method: 'GET',
            dataType: "json",
            success: function(result) {
                $(".productfinder_subcat").html(result.category);
                $(".cell-match-height").matchHeight();
                $('.finder-scroll').hide();
                $('.proggress').css('display','block');
				jQuery('html, body').animate({
					'scrollTop' : jQuery(".rfq_main").position().top - jQuery('header').height()
				}, 1000);
				jQuery(".search-loader").css("display","none");
            }
        });
        $('.readmode_title').text('Read Mode');
    }
    // Step two data calling
    function step_two(parent, checkclicked) {
		
        var base_url = window.location.origin;
        var currentpage = jQuery('.product-finder-container').attr('currentpage');		
        var parentname = $('#step2 .step1title').attr('id');
        var firststep_cat = getparentid(parentname);
        var prodtype = 0;
        var specifi = 0;
        var readmode = 0;
		var selected_pids = {};
		
		
		var cat_line_of_busines = $('.productfinder_subcat a.rfqcat'+parent).attr('line_of_busines');
		var cat_product_family = $('.productfinder_subcat a.rfqcat'+parent).attr('product_family');
		var cat_rfqtitle = $('.productfinder_subcat a.rfqcat'+parent).attr('rfqtitle');
		var cat_subcat_id = $('.productfinder_subcat a.rfqcat'+parent).attr('alt');
			
		if(currentpage == 'quote-request'){	//quote-request-v2
            //selected_pids[selected_val] = [];		   
        	selected_pids['id'] = cat_subcat_id;
            selected_pids['cat'] = cat_rfqtitle;
            selected_pids['line_of_busines'] = cat_line_of_busines;
            selected_pids['product_family'] = cat_product_family;		
			step_three(selected_pids);			
			return false;
		}
				
		
		/*if(currentpage == 'quote-request-v2'){
			
			  $.ajax({
				url: base_url + "/productfinder/get_request_form",
				method: 'GET',
				dataType: "json",
				success: function(result) {					
					$('.request-quote-container .tiles-container').html(result.request_form);
					console.log(result.request_form);										
				},
				complete: function() {
					alert('---chk---');
					$('.tiles-container iframe#contact-quote-request').attr('src', $('iframe#contact-quote-request').attr('src'));	
				},
			});
		
			return false;
		}*/
		
        if ((parent == 25 && firststep_cat == 4) || (parent == 25 && firststep_cat == 1) || (parent == 24 && firststep_cat == 4) || (parent == 12 && firststep_cat == 4) || parent == 545 || (parent == 33 && firststep_cat == 4) || parent == 22 || (parent == 25 && firststep_cat == 6) || (parent == 12 && firststep_cat == 6) || (parent == 24 && firststep_cat == 6)) { //|| parent == 4 
            $('.filter_productcat').hide();
            $('.no_filter_data').addClass('hide')
        } else {
            $('.filter_productcat').show();
            $('.no_filter_data').removeClass('hide')
        }

        if (parent == 21 || parent == 25 || parent == 33 || (parent == 681 && firststep_cat == 2) || (parent == 24 && firststep_cat == 1)) {
            //$('.finderprodtype').hide();
            $('.finderprodtype').addClass('hide');
            $('.finderprodspec').addClass('hide');
            //$('.finderprodspec').hide();
        } else {
            //$('.finderprodtype').show();
            $('.finderprodtype').removeClass('hide');
            $('.finderprodspec').removeClass('hide');
            //$('.finderprodspec').show();
        }
		
        $('select.prodtypes').prop('disabled', true);
        $('select.prodspecifi').prop('disabled', true);
        $.ajax({
            url: base_url + "/productfinder/readmode/" + parent,
            method: 'GET',
            dataType: "json",
            success: function(result) {
                if (parent == '33') {
                    $('.readmode_title').text('cell type');
                    // $("label").(your value);                         
                } else if (parent == '24') {
                    $('.readmode_title').text('Products');
                    $('.prodtype_title').text('Types');
                } else if (parent == '12') {
                    $('.prodtype_title').text('Featured Assays');
                } else if (parent == '681') {
                    $('.readmode_title').text('Media Type');
                } else if (parent == '21' && firststep_cat == 2) {
                    $('.readmode_title').text('Assay Kits');
                } else {
                    $('.readmode_title').text('Read Mode');
                }

                if (parentname != 'Software') {
                    $('.filter_productcat').show();
                } else {
                    $('.filter_productcat').hide();
                }

                $("select.prodreadmode").html(result.readmodefilter);
                if (parent == 22) {
                    $(".step2title").html('Reagents');
                } else {
                    //alert(result.sidebar_catname);
                    $(".step2title").html(result.sidebar_catname);
                }

                $(".step2title").attr("catid", parent);
                $(".step2title").attr('product_family', result.product_family);
                $(".step2title").attr('line_of_busines', result.line_of_busines);
                $('.selectpicker').selectpicker('refresh');
                if (parent == '24') {
                    $("select.prodtypes").prop('disabled', 'disabled');
                    $(".prodtypes .dropdown-toggle").addClass("disabled");
                    $("select.prodspecifi").prop('disabled', 'disabled');
                    $(".prodspecifi .dropdown-toggle").addClass("disabled");
                }
            }
        });

        $.ajax({
            url: base_url + "/productfinder/readmodetype/" + parent + '/' + readmode + '/' + prodtype,
            method: 'GET',
            dataType: "json",
            success: function(result) {
                if (result.readmodetype != 'no_result') {

                }
                $(".cell-match-height").matchHeight();
                $('select.prodtypes').html(result.readmodetype);
                if (parent == 22) {
                    $(".step2title").html('Reagents');
                } else {
                    $(".step2title").html(result.sidebar_catname);
                }
                $('.selectpicker').selectpicker('refresh');
            }
        });


        $.ajax({
            url: base_url + "/productfinder/readmodespecifi/" + parent + '/' + readmode + '/' + specifi,
            method: 'GET',
            dataType: "json",
            success: function(result) {
                if (result.readmodespecifi != 'no_result') {
                    // $('select.prodspecifi').prop('disabled', false);
                }
                $(".cell-match-height").matchHeight();
                $('select.prodspecifi').html(result.readmodespecifi);
                if (parent == 22) {
                    $(".step2title, .step2cattitle").html('Reagents');
                } else {
                    $(".step2title, .step2cattitle").html(result.sidebar_catname);
                }
                $('.selectpicker').selectpicker('refresh');
            }
        });

        $('.finder-scroll').show();
        if (parent == 681 && firststep_cat == 2) {
            var firststep_cat = 5;
        } else if (parent == 22 && firststep_cat == 2) {
            var firststep_cat = 7;
        } else {
            var firststep_cat = firststep_cat;
        }
        /*** get pid individual RFQ start here***/
        if (checkclicked == 'notclicked') {
            var productid = jQuery.urlParam('pid');
            if (productid === 'undefined') {
                productid = '';
            }
            $('.filter_productcat').hide();
            $('.no_filter_data').addClass('hide')
        } else {
            var productid = '';
        }
        /*** get pid individual RFQ code ends here***/
        $.ajax({
            url: base_url + "/productfinder/filtercat/" + parent + '/' + readmode + '/' + prodtype + '/' + specifi + '/' + firststep_cat,
            method: 'GET',
            data: {
                currentpage: currentpage,
                pid: productid,
            },
            dataType: "json",
            success: function(result) {
                $(".result-count").html(result.countprod);
                $(".all-products").html(result.allproducts);
                $('.finder-scroll').hide();
                //hide top message  for specific category and product 
                var check_message_category = $('#step3 .step2title').text();
                var hidden_message_category = ['Clone Screening', 'Axon Instruments Patch-Clamp Systems', 'FLIPR'];
                if(jQuery.inArray(check_message_category, hidden_message_category) !== -1){ 
                    $('.step2_topmessage').hide(); 
                }
                else{ $('.step2_topmessage').show(); }
                var check_messageproduct = jQuery('.filter-checkbox span .rfq_selectedpid437').val();
                if (check_messageproduct) {
                     $('.step2_topmessage').hide(); 
                }

                //$(".continuelink").html(result.continuelink);
                if (checkclicked == 'notclicked') {
                    var checkbox_rfqs = {};
                    $('.procampcheckbox[value=' + productid + ']').click();
                    jQuery(".quotepage-content #step3 .filter-checkbox input").each(function() {
                        //console.log(checkbox_rfqs);
                        var productElement = jQuery(this);
                        var productTitle = productElement.closest(".pro-container").find('.product-finder-desc a').text();
                        var productid = productElement.attr("productid");
                        if (productElement.is(':checked')) {
                            checkbox_rfqs['pid_' + productid] = productTitle;
                        } else {
                            delete checkbox_rfqs['pid_' + productid];
                        }
                    });
                    checkbox_rfqs_bottom_bar(checkbox_rfqs);
                    $('.rfq_bottom_setproducts').hide();
					var pageurl = $(location).attr("href").split('/').pop();                
				    if(pageurl !== 'rfq'){
                    step_three();
					}                    
                }
            }

        });
        
        $('.product-finder-container .nav-tabs li').removeClass('active');
        $('.product-finder-container .nav-tabs li a[href = "#step3"]').click();
        setTimeout(function() {
            $(".cell-match-height").matchHeight();
        }, 3000);
        return true;
    }

    $('#contact-quote-request').on('load', function() {
        $('#contact-quote-request').css('display', 'block');
        //alert('load done');
    });


    // Step three data Calling
    function step_three(selected_pids) {		
		
        $('.rfq_bottom_setproducts').hide();
       // var selected_pids = {};
        var targetproductli = '';
        var line_of_busines = '';
        var first_product_family = '';
        var all_product_family = '';
        var selected_primary_application = '';
		var selected_primary_application_geo = '';
        var selectedpids = '';
		
		//var catid = jQuery('.productfinder_subcat .step2').attr('alt');
		
		
       
		
        /*jQuery(".quotepage-content #step3 .filter-checkbox input").each(function() {
            if (jQuery(this).is(':checked')) {
                var selected_val = jQuery(this).val();
                if (selected_pids[selected_val] == undefined) {
                    selected_pids[selected_val] = [];
                }
                var selected_title = jQuery(this).attr('product_title');
				var selected_title_id = jQuery(this).attr('id');
                var selected_cat = jQuery(this).attr('alt');
                var product_family = jQuery(this).attr('product_family');
                var lineofbusines = jQuery(this).attr('line_of_busines');
                //selected_pids[selected_val] = selected_val;
                selected_pids[selected_val]['title'] = selected_title;
				selected_pids[selected_val]['id'] = selected_title_id;
                selected_pids[selected_val]['cat'] = selected_cat;
                selected_pids[selected_val]['line_of_busines'] = lineofbusines;
                selected_pids[selected_val]['product_family'] = product_family;
            }
        });
        var counter = 0;

        jQuery.each(selected_pids, function(selectedpid, selectedvalue) {
            targetproductli += '<li><strong>' + selectedvalue.title + '</strong></li>';
            var lob_index = line_of_busines.indexOf(selectedvalue.line_of_busines);
            if (lob_index == -1) {
                line_of_busines += selectedvalue.line_of_busines + ',';
            }
            var apf_index = all_product_family.indexOf(selectedvalue.product_family);
            if (apf_index == -1) {
                all_product_family += selectedvalue.product_family + ',';
            }
            selected_primary_application += selectedvalue.id + ',';
			selected_primary_application_geo += selectedvalue.title + ',';
            selectedpids += 'pid[' + selectedpid + ']=' + selectedpid + '&';
            if (counter == 0) {
                first_product_family += selectedvalue.product_family;

            }
            counter++;
        });
		
		alert('load iframe');
        var ifrmaepath = 'https://go.moleculardevices.com/l/83942/2018-06-12/9xbwtm';
        var quote_success_path = window.location.origin + '/quote-request-success'
        if (targetproductli) {
            var lastIndex_line_of_busines = line_of_busines.lastIndexOf(",");
            line_of_busines = line_of_busines.substring(0, lastIndex_line_of_busines);
            var lastIndex_product_family = all_product_family.lastIndexOf(",");
            all_product_family = all_product_family.substring(0, lastIndex_product_family);
            var lastIndex_selected_primary_app = selected_primary_application.lastIndexOf(",");
            selected_primary_application = selected_primary_application.substring(0, lastIndex_selected_primary_app);
			
			var lastIndex_selected_primary_app_geo = selected_primary_application_geo.lastIndexOf(",");
            selected_primary_application_geo = selected_primary_application_geo.substring(0, lastIndex_selected_primary_app_geo);
	
	
            var lastIndex = selectedpids.lastIndexOf("&");
            selectedpids = selectedpids.substring(0, lastIndex);
            selectedpids = encodeURIComponent(selectedpids);
            jQuery("#step4 .side_bar_pro > ul >li.selected-products").append('<ul class="appendsteps">' + targetproductli + '</ul>');
            //cmp=70170000000vKfS'
            var appendpath = '?Line_of_Business=' + line_of_busines + '&Product_Family=' + first_product_family + '&product_selection=' + all_product_family + '&Primary_Application=' + selected_primary_application +  '&Primary_Application_geo=' + selected_primary_application_geo +'&Return_URL=' + quote_success_path + '?' + selectedpids;
        } else {
            line_of_busines = jQuery('#step3 .side_bar_pro .step2title').attr('line_of_busines');
            first_product_family = jQuery('#step3 .side_bar_pro .step2title').attr('product_family');
            all_product_family = first_product_family;
            selected_primary_application = jQuery('#step3 .side_bar_pro .step2title').text();
            var madatary_category = ['Clone Screening', 'Axon Instruments Patch-Clamp Systems', 'FLIPR'];
            if(jQuery.inArray(selected_primary_application, madatary_category) !== -1){
               alert("Please select at least one product for this category.");
               return false;
            }
            var singleselectproduct = jQuery('.filter-checkbox span .rfq_selectedpid437').val();
            if (singleselectproduct) {
                if(singleselectproduct == '437'){
                  alert("Please select at least one product for this category.");
                   return false;
                }
            }
            var categoryid = jQuery('#step3 .side_bar_pro .step2title').attr('catid');
            jQuery("#step4 .side_bar_pro > ul >li.selected-products").append('<ul class="appendsteps"><li><strong>' + selected_primary_application + '</strong></li></ul>');
            var appendpath = '?Line_of_Business=' + line_of_busines + '&Product_Family=' + first_product_family + '&product_selection=' + first_product_family + '&Primary_Application=' + first_product_family +  '&Primary_Application_geo=' + selected_primary_application +'&Return_URL=' + quote_success_path + '?catid=' + categoryid;
        }*/
		
		//alert(JSON.stringify(selected_pids['id']+'=='+selected_pids['line_of_busines']+'=='+selected_pids['cat']+'=='+selected_pids['product_family']));
		
		var categoryid = selected_pids['id'];
		var line_of_busines = selected_pids['line_of_busines'];
		var first_product_family = selected_pids['product_family'];
		var selected_primary_application = selected_pids['cat'];
		
		var ifrmaepath = 'https://go.moleculardevices.com/l/83942/2019-02-18/bdx3bc';			
        var quote_success_path = window.location.origin + '/quote-request-success'
		
		//var categoryid = jQuery('#step3 .side_bar_pro .step2title').attr('catid');
        //jQuery("#step4 .side_bar_pro > ul >li.selected-products").append('<ul class="appendsteps"><li><strong>' + selected_primary_application + '</strong></li></ul>');
        var appendpath = '?Line_of_Business=' + line_of_busines + '&Product_Family=' + first_product_family + '&product_selection=' + first_product_family + '&Primary_Application=' + first_product_family +  '&Primary_Application_geo=' + selected_primary_application +'&Return_URL=' + quote_success_path + '?catid=' + categoryid;
		
		//var appendpath ='?Line_of_Business=Drug%20Discovery&Product_Family=Imaging&product_selection=Imaging&Primary_Application=Imaging&Primary_Application_geo=Cellular%20Imaging&Return_URL=https://www.moleculardevices.com/quote-request-success?catid=25&cmp=70170000000hlRa';
		
        jQuery(".hiddenforprotitle").html('<span class="appendsteps setprdtcs text-center"><strong>' + selected_primary_application + '</strong></span>');		
			
        var campaign_id = jQuery.urlParam('cmp');
        if (campaign_id) {
            appendpath = appendpath + '&cmp=' + campaign_id;
        }
        else{
            appendpath = appendpath + '&cmp=70170000000hlRa';
        }
		
		var utm_medium_val = getCookie('utm_medium');
		var utm_source_val = getCookie('utm_source');
		var gclid_val = getCookie('gclid');
		
		if(utm_source_val || utm_source_val){
			appendpath = appendpath +'&utm_medium_='+utm_medium_val+'&utm_source_='+utm_source_val;		
		}
		
		if(gclid_val){
			appendpath = appendpath +'&GCLID='+gclid_val;		
		}
		
        ifrmaepath = ifrmaepath + appendpath;
        $('.quotepage-content .tab-pane').removeClass("in active");
        $('.quotepage-content .tab-pane#step4').addClass("in active");
        jQuery('.search-loader').css('display', 'block');
        //jQuery('#contact-quote-request').css('display', 'none');
        jQuery('#contact-quote-request').attr('src', ifrmaepath);
		console.log('-scroll test : 1 ');
		jQuery('html, body').animate({
			'scrollTop' : jQuery(".rfq_main").position().top - jQuery('header').height()
		}, 1000);
				
        /*jQuery('html, body').animate({
            scrollTop: jQuery(".quotepage-content #contact-quote-request").offset().top
        }, 1000);*/
    }

    // Get product parent category and product type using product id
    function get_parent_category_product_type(pid) {
        if ($.isNumeric(pid)) {
            var base_url = window.location.origin;
            $.ajax({
                url: base_url + "/parentcategory-producttype",
                method: 'POST',
                data: {
                    pid: pid
                },
                dataType: "json",
                success: function(result) {
                    if (result.product_type_id) {
                        if (result.product_type_id == 5 || result.product_type_id == 7) {
                            var parent_category_en = 'Reagents / Media';
                            $('.quotepage-content .step1title, #step2 .selecthead').text(parent_category);
							$('.quotepage-content .step1title').attr('id', parent_category_en);							
                            step_one(2);
                            step_two(result.parent_category_id, 'notclicked');
                        } else {
                            var selected_product_type = result.product_type_en;
                            if (selected_product_type == 'Instruments') {
                                selected_product_type = selected_product_type.slice(0, -1);
                            }
                            $('.quotepage-content .step1title, #step2 .selecthead').text(result.product_type);
							$('.quotepage-content .step1title').attr('id',selected_product_type);							
                            $('.quotepage-content .step2title').text(result.parent_category);
                            $('.quotepage-content .step2title').attr('catid', result.parent_category_id);
                            step_one(result.product_type_id);
                            step_two(result.parent_category_id, 'notclicked');
                        }
                    }
                }

            });

        }
    }
	
	// Get get category page RFQ using cat id..
    function get_categorypage_rfq(secstep_parent, pagetype) { // arg=subcat id
        if ($.isNumeric(secstep_parent)) {
			var cat = jQuery.urlParam('cat');
			
            var base_url = window.location.origin;
            $.ajax({
                url: base_url + "/categorypage-rfq",
                method: 'POST',
                data: {
                    subcatid: secstep_parent
                },
                dataType: "json",
                success: function(result) {
					//console.log(result);
                    if (result.product_type_id) {						
                        if (result.product_type_id == 5 || result.product_type_id == 7) {
                            var parent_category_en = 'Reagents / Media';
                            $('.quotepage-content .step1title, #step2 .selecthead').text(parent_category);
							$('.quotepage-content .step1title').attr('id', parent_category_en);							
                            
							if(cat){
								/*if(pagetype=='rfq'){
								step_newtwo(1, 33);
								step_newone(1);
								}else{*/
								step_one(2);	
							    step_two(result.parent_category_id);	
								//}
							}else{
								step_one(2);
                            step_two(result.parent_category_id, 'notclicked');
							}
                        } else {
                            var selected_product_type = result.product_type_en;
                            if (selected_product_type == 'Instruments') {
                                selected_product_type = selected_product_type.slice(0, -1);
                            }
                            $('.quotepage-content .step1title, #step2 .selecthead').text(result.product_type);
							$('.quotepage-content .step1title').attr('id',selected_product_type);
							$('.quotepage-content .step1title').text(selected_product_type);
							
                            $('.quotepage-content .step2title').text(result.parent_category);
                            $('.quotepage-content .step2title').attr('catid', result.parent_category_id);
                            
						//console.log(cat);
							if(cat){
								/*if(pagetype=='rfq'){
								step_newone(1);
								step_newtwo(1, 33);
								}else{*/
							    step_one(result.product_type_id);								
								step_two(result.parent_category_id);                                
								//}                            
							}else{
								/*if(pagetype=='rfq'){
								step_newone(1);
								step_newtwo(1, 33);
								}else{*/
								step_one(result.product_type_id);								
							    step_two(result.parent_category_id, 'notclicked');	
								//}
							}
                        }
                    }
                }

            });

        }
    }
	
    // Get RFQ for Multiple Products
    function get_rfq_multiproducts(products_id, multiproduct, pagetype){
        //console.log(pagetype);
        $('.quotepage-content .tab-pane').removeClass("in active");
        $('.quotepage-content .tab-pane#step4').addClass("in active");
        $('.tab-heading.side_bar_pro ul li:not(.marg-tp-10)').hide();
        var base_url = window.location.origin;
        $.ajax({
            url: base_url + "/rfq-multiproducts",
            method: 'POST',
            data: {
                products_id:  products_id,
                multiproduct: multiproduct,
				pagetype: pagetype
            },
            dataType: "json",
            success: function(result) {
                if (result.iframeurl) {
                    var campaign_id = jQuery.urlParam('cmp');
                    if (campaign_id) {
                        var iframecompleteurl = result.iframeurl+ '&Primary_Application_geo='+result.prdcts+'&cmp=' + campaign_id;
                    }
                    else{
                        var iframecompleteurl = result.iframeurl+ '&Primary_Application_geo='+result.prdcts+ '&cmp=70170000000hlRa';
                    }				
					//console.log('cstory--> '+iframecompleteurl);
                    $('.quotepage-content .tab-pane').removeClass("in active");
                    $('.quotepage-content .tab-pane#step4').addClass("in active");
                    jQuery('.search-loader').css('display', 'block');
                    jQuery('#contact-quote-request').css('display', 'none');
                    jQuery('#contact-quote-request').attr('src', iframecompleteurl);
					var pageurl = $(location).attr("href").split('/').pop();					    
                    var brkpage = pageurl.split("?");					
					if(brkpage[0] !== 'rfq' && brkpage[0] !== 'quote-request'){ //quote-request-v2
						jQuery('html, body').animate({
							scrollTop: jQuery(".quotepage-content #contact-quote-request").offset().top
						}, 1000);
					}
					jQuery(".hiddenforprotitle").html('<span class="appendsteps setprdtcs text-center"><strong>' + result.prdcts + '</strong></span>');
                }
                else{
                    $('.tab-heading.side_bar_pro ul li').show();
                    $('.quotepage-content .tab-pane').removeClass("in active"); 
                    $('.quotepage-content .tab-pane#step1').addClass("in active");
                }
            }

        });
    }
    // Get URL Parameters value // console.log(getURLParam('pid[]'));
    function getURLParam(key,target){
        var values = [];
        if (!target) target = location.href;

        key = key.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");

        var pattern = key + '=([^&#]+)';
        var o_reg = new RegExp(pattern,'ig');
        while (true){
            var matches = o_reg.exec(target);
            if (matches && matches[1]){
                values.push(matches[1]);
            } else {
                break;
            }
        }

        if (!values.length){
            return null;   
        } else {
            return values.length == 1 ? values : values;
        }
    }
	
	/*Hide back button if user directly comes on RFQ page */
	jQuery(document).ready(function () {
		if ( (window.location.href.indexOf("?customer_breakthrough_id=") > -1)|| (window.location.href.indexOf("?pid=") > -1) ) {
			console.log('hide back btn');
			jQuery(".start-again").css("display","none");
		}
	});
	
})(jQuery);
