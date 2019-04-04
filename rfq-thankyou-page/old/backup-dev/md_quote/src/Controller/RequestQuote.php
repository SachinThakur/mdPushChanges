<?php
/**
 * @thanks file controller
 */

namespace Drupal\md_quote\Controller;

use Drupal\node\Entity\Node;
use Drupal\image\Entity\ImageStyle;
use Drupal\file\Entity\File;
use Drupal\finder\Controller\FinderController;

class RequestQuote extends FinderController {

    public function quote_request() {
	   /*	$step_one_sidebar = '<div class="col-md-2">
	                    <div class="sidebar">
	                        <h3>Talk TO A REPRESENTATIVE</h3>
	                        <div class="selection-container">
	                        <ul>
	                        <li>1 (877) 589-2214 (US/Canada)<br/><a href="mailto:om@moldev.com">om@moldev.com</a></li>
	                        </ul>
	                        
	                        </div>
	                    </div>
	                </div>'; */ 
		$step_four = '<div role="tabpanel" class="tab-pane fade" id="step4">
					<div class="row">
						<!--<div class="col-xs-12">
							<div class="tab-heading">
								<h4 class="text-center">Request quote </h4> 
							</div>
						</div>-->
						<div class="col-xs-12">
							<div class="tab-heading side_bar_pro">
								<h6>Steps for Request Quote:</h6>
								<ul>
									<li><a href="#step1" aria-controls="step1" role="tab" data-toggle="tab" class="count checked_list"></a> <span>Select Product type: <strong class="step1title" id=""></strong></span></li>
									<li><a href="#step2" aria-controls="step2" role="tab" data-toggle="tab" class="count checked_list"></a> <span>Select Category: <strong class="step2title"></strong></span></li>
									<li class="selected-products"><a href="#step3" aria-controls="step3" role="tab" data-toggle="tab" class="count checked_list"></a> <span>Select Product(s): <strong class="step3title"></strong></span></li>
									<li><span class="count">4</span><span class="">Fill out form </span></li>
									<li class="marg-tp-10"><a href="#step1" aria-controls="step1" role="tab" data-toggle="tab" class="start-again" aria-expanded="true"><strong><span class="count"><i class="fa-arrow-left fa"></i></span> Start Over</strong></a></li>
								</ul>
							</div>
							
						</div>
						
							<div class="col-xs-12" >
								<div class=" tiles-container marg-tp-20" ></div>
							</div>
							<div class="category-container-holder">
							<div class="search-loader" id="overlay">
								<img src="/sites/default/files/icons/ajax_common_loader_gray.gif" height="50" width="50">
							</div>

								<script>
								function onIframeLoad(){									
									jQuery(".search-loader").css("display","none");
									jQuery("#contact-quote-request").css("display","block");
									iFrameResize({log:true}, "#contact-quote-request");									
								}
        						</script>
					        	<iframe  onload="onIframeLoad();" style="width:1px;min-width:100%;height: 850px;" id="contact-quote-request" src="https://go.moleculardevices.com/l/83942/2018-03-24/9tfrr9" frameborder="0"></iframe>
					        	<script src="https://cdnjs.cloudflare.com/ajax/libs/iframe-resizer/3.5.16/iframeResizer.min.js" integrity="sha256-55VLYmU+PX1ae0VViacjjWYd+669GGULxLnYIImrcVY=" crossorigin="anonymous"></script>
							</div>	
					</div>		
				</div>';
	    return $this->product_finder('quote_request', NULL, $step_four);
  	}
  	/***Request Quote Success page function****/
  	public function quote_request_success(){
  		$catrelatedproducts = $catrelatedproducts = '';
  		$catid = $_GET['catid'];
  		$pids = $_GET['pid'];
  		if ($catid) {
  			$catrelatedproducts = $this->getRelatedProducts( $catid);
  			$quote_related_products['catrelatedproducts']['data'] = $catrelatedproducts;
  			$selectedterm = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($catid);
  			$quote_related_products['catrelatedproducts']['selected_cat'] = $selectedterm->label();
  		}
  		elseif($pids){
			$relatedproducts = $this->relatedproducts( $pids);
			$product_nodequery = \Drupal::database()->select('node_field_data', 'nfd');
			$product_nodequery->fields('nfd', array('title'));
			$product_nodequery->condition('nfd.nid', $pids, 'IN');
			$product_nodequery->condition('nfd.status', 1);
			$product_nodequery->condition('nfd.type', 'products');
			$selected_productdata = $product_nodequery->execute()->fetchAll();
			$quote_related_products['relatedproducts']['selected_products'] = $selected_productdata;
			$quote_related_products['relatedproducts']['data'] = $relatedproducts;
  		}
  		return array(
	      '#theme' => 'quote_request_thanks',
	      '#quote_related_products' => $quote_related_products,
	    );

  	}
  	/***Get products related product****/
    public function relatedproducts($typeids = array(), $type = NULL){
    	$relatedproducts = array();
        global $base_url;
        switch ($type) {
        	case 'cat':
        		break;
        	
        	default:
		        if (!empty($typeids)) {
		            $product_ids = array();
		            $products = \Drupal::database()->select('node__field_product_reference', 'frc');
		            $products->join('node_field_data','nfd','nfd.nid=frc.entity_id');
		            //$products->leftjoin('node__field_product_type', 'fpt', 'fpt.entity_id=frc.field_product_reference_target_id');
		            $products->leftjoin('node__field_category', 'nfc', 'nfc.entity_id=frc.field_product_reference_target_id');
		            $products->fields('frc', array('field_product_reference_target_id'));
		            $products->condition('frc.entity_id', $typeids, 'IN');
		            $products->condition('nfd.status', 1);
		            //$products->orderBy('fpt.field_product_type_value', 'ASC');
		            $products->orderBy('nfc.field_category_target_id', 'ASC');
		            $products_id = $products->execute()->fetchAll();
		            foreach ($products_id as $pid) {
		                $product_ids[$pid->field_product_reference_target_id] = $pid->field_product_reference_target_id;
		            }
		    
		            $product_ids = array_unique($product_ids);
		            //print_r($product_ids);
		            $product_counter = count($product_ids);

					if (!empty($product_ids)) {
		                foreach ($product_ids as $productid) { 
		                    $proimg ='';  
		                    $nodeload = Node::load($productid);  
		                    $product_image = \Drupal\image\Entity\ImageStyle::load('related_products')->buildUrl($nodeload->field_banner_image->entity->getFileUri());
		                    if ($nodeload->get('field_select_series')->target_id != '') {
		                        $serieslink = $nodeload->get('field_select_series')->target_id;

		                    } else {
		                        $serieslink = $nodeload->id();
		                    }
		                    $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $serieslink);
		                    if(($nodeload->get('field_product_type')->value!='2') && ($nodeload->get('field_product_type')->value!='4')){ //2 for assay kits, 4=labware
		                        $proimg = $product_image;
		                    }
		                    $relatedproducts[$productid]['nodeid'] = $serieslink;
		                    $relatedproducts[$productid]['title'] = $nodeload->getTitle();    
		                    $relatedproducts[$productid]['body'] = trim_text($nodeload->get('field_product_summary')->value, $length = 100, $ellipses = true, $strip_html = true);
		                    $relatedproducts[$productid]['productalias'] = $base_url.$alias;
		                    $relatedproducts[$productid]['product_image'] = $proimg;
		                }
		            }
		        	return $relatedproducts;
		        } 
        		break;
        }
    }   
}
