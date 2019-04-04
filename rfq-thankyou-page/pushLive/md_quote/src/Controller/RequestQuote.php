<?php
/**
 * @thanks file controller
 */

namespace Drupal\md_quote\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\HttpFoundation\JsonResponse;
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
	      '#theme' => 'thanks_template',//'quote_request_thanks',
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
	
	/* new version of rfq */
	public function quote_request_new() {
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
		$step_four = 'test';
	    //return $this->new_product_finder('quote_request', NULL, $step_four);
		$path = \Drupal::request()->getpathInfo();
        $arg = explode('/', $path);		
        $cur_page = $arg[1];
		
		$pid = $_GET['pid'];
		
		$newrfq = $fpage;
		//$newrfq = $step_four;
		
		return array(
	      '#theme' => 'quote_request',
		  '#cur_page' => $cur_page,
		  '#pid' => $pid,
	      '#quote_related_products' => $step_four,
	    );
  	}
	
	public function productfinder_subcat($cat) {
        $html = '';
		
        if ($cat == '2') {
            $html .= '<div class="col-xs-6 col-sm-6 col-md-3  rfq_ico">
                            <a href="#step3" class="profinder_subcat step2 rfqcat21" alt="21" rfqtitle="Assay Kits" line_of_busines="Reagents" product_family="Microplate Reader" aria-controls="step3" role="tab" data-toggle="tab"><span class="icons-finder">
							<img class="rfq_ico_img" src="/themes/moldev/images/finder-icons/rfq-images/assay-kits-media.png" alt="Assay Kits">
							<img class="hover-img" src="/themes/moldev/images/finder-icons/rfq-images/assay-kits-media-hover.png" alt="Assay Kits">
							</span>Assay Kits</a>
                    </div>
                    <div class="col-xs-6 col-sm-6 col-md-3  rfq_ico">
                            <a href="#step3" class="profinder_subcat step2 rfqcat22" alt="22" rfqtitle="Reagents" line_of_busines="Reagents" product_family="BT RC" aria-controls="step3" role="tab" data-toggle="tab"><span class="icons-finder">
							<img class="rfq_ico_img" src="/themes/moldev/images/finder-icons/rfq-images/assay-kits-media.png" alt="Reagents">
							<img class="hover-img" src="/themes/moldev/images/finder-icons/rfq-images/assay-kits-media-hover.png" alt="Reagents">
							</span>Reagents</a>
                    </div>
                    <div class="col-xs-6 col-sm-6 col-md-3  rfq_ico">
                            <a href="#step3" class="profinder_subcat step2 rfqcat681" alt="681" rfqtitle="Media" line_of_busines="Reagents" product_family="BT RC" aria-controls="step3" role="tab" data-toggle="tab"><span class="icons-finder">
							<img class="rfq_ico_img" src="/themes/moldev/images/finder-icons/rfq-images/assay-kits-media.png" alt="Media">
							<img class="hover-img" src="/themes/moldev/images/finder-icons/rfq-images/assay-kits-media-hover.png" alt="Media">
							</span>Media</a>
                    </div>';
        } else {
			//$html .= '<h2>Please select the instrument category</h2>';
			//$html .= '<h2 class="text-center">Select <span class="selecthead"> <!-- coming by ajax --></span> Category</h2>';
            $get_subcat = \Drupal::database()->select('node__field_product_type', 'pt');
            $get_subcat->join('node_field_data', 'nfd', 'nfd.nid=pt.entity_id');
            $get_subcat->join('node__field_category', 'fc', 'fc.entity_id=pt.entity_id');
            $get_subcat->fields('pt', array('entity_id'));
            $get_subcat->fields('fc', array('field_category_target_id'));
            $get_subcat->condition('pt.field_product_type_value', $cat);
            $get_subcat->condition('pt.bundle', 'products');
            $get_subcat->condition('nfd.status', '1');
            $subcat = $get_subcat->execute()->fetchAll();
            foreach ($subcat as $sub_cat) {
                $pids[$sub_cat->entity_id] = $sub_cat->entity_id;
                $cate = $sub_cat->field_category_target_id;
                $query = \Drupal::database()->select('taxonomy_term__parent', 'th');
                $query->fields('th', array('parent_target_id'));
                $query->condition('th.entity_id', $cate);
                $catparent = $query->execute()->fetchField();
                $query_par = \Drupal::database()->select('taxonomy_term__parent', 'thpar');
                $query_par->fields('thpar', array('parent_target_id'));
                $query_par->condition('thpar.entity_id', $catparent);
                $query_par->orderBy('thpar.parent_target_id', 'ASC');
                $catparentmain = $query_par->execute()->fetchField();
                if ($catparentmain != '') {
                    $parent_cat[$catparentmain] = $catparentmain;
                }
                if ($catparent != '') {
                    $parent_cat[$catparent] = $catparent;
                }
            }
		
            foreach ($parent_cat as $key => $val) {
                $get_weight = \Drupal::database()->select('taxonomy_term_field_data', 'tfd');
                $get_weight->fields('tfd', array('weight'));
                $get_weight->condition('tfd.tid', $key);
                $get_weight->condition('tfd.vid', 'product_groups');
                $get_weight->orderBy('tfd.weight', 'ASC');
                $getweight = $get_weight->execute()->fetchField();
                $cate_weight[$getweight] = $val;
            }

            ksort($cate_weight);
            foreach ($cate_weight as $key => $pcate) {

                $subcat_detail = \Drupal\taxonomy\Entity\Term::load($pcate);
                if (!empty($subcat_detail)) {
					
                    $subcat_name = $subcat_detail->name->value;
                    $subcat_tid = $subcat_detail->tid->value;
				
					$field_product_product_family = \Drupal\taxonomy\Entity\Term::load($subcat_detail->get('field_product_family')->target_id);
					$product_family = $field_product_product_family->name->value;
					$field_product_line_of_bussiness = \Drupal\taxonomy\Entity\Term::load($subcat_detail->get('field_line_of_busines')->target_id);
					$line_of_busines = $field_product_line_of_bussiness->name->value;
					
                    //echo $subcat_detail->field_resources_type_icon->entity->getFileUri();
					
					if($subcat_detail->get('field_resources_type_icon')->target_id!= ''){
						$cat_image = \Drupal\image\Entity\ImageStyle::load('related_products')->buildUrl($subcat_detail->field_resources_type_icon->entity->getFileUri());
					    $cat_hover_image = \Drupal\image\Entity\ImageStyle::load('related_products')->buildUrl($subcat_detail->field_speaker_image->entity->getFileUri());
					}else{
					    $cat_image = \Drupal\image\Entity\ImageStyle::load('related_products')->buildUrl($subcat_detail->field_finder_icons->entity->getFileUri());
						$cat_hover_image = $cat_image;
					}
                    $html .= '<div class="col-xs-6 col-sm-6 col-md-3  rfq_ico">
                                <a href="#step3" class="profinder_subcat step2 rfqcat'.$subcat_tid.'" alt="' . $subcat_tid . '" line_of_busines="'.$line_of_busines.'" product_family="'.$product_family.'" rfqtitle="'.$subcat_name.'" aria-controls="step3" role="tab" data-toggle="tab"><span class="icons-finder"><img class="rfq_ico_img" src="'. $cat_image .'" alt="'. $subcat_detail->get('field_resources_type_icon')->alt .'">
								<img class="hover-img" src="'.$cat_hover_image.'" alt="'. $subcat_detail->get('field_resources_type_icon')->alt .'">
								</span>' . $subcat_name . '</a>
                                </div>';
                }
            }
			if($cat == 1){
				 $html .=  '<div class="col-xs-6 col-sm-6 col-md-3  rfq_ico">
							<a href="#step3" class="profinder_subcat step2 rfqcat841" alt="841" line_of_busines="" product_family="" rfqtitle="Microarray Readers" aria-controls="step3" role="tab" data-toggle="tab">
							<span class="icons-finder">
								<img class="rfq_ico_img" src="/sites/default/files/styles/related_products/public/2019-02/microarray%20.png" alt="microarray">
									<img class="hover-img" src="/sites/default/files/styles/related_products/public/2019-02/microarray%20-hover.png" alt="microarray">
									</span>Microarray Readers
								</a>
							</div>

							<div class="col-xs-6 col-sm-6 col-md-3  rfq_ico">
							<a href="#step3" class="profinder_subcat step2 rfqcat842" alt="842" line_of_busines="" product_family="" rfqtitle="Washers" aria-controls="step3" role="tab" data-toggle="tab">
							<span class="icons-finder">
								<img class="rfq_ico_img" src="/sites/default/files/styles/related_products/public/2019-02/washers.png" alt="washers">
									<img class="hover-img" src="/sites/default/files/styles/related_products/public/2019-02/washers-hover.png" alt="washers">
									</span>Washers
								</a>
							</div>';
			}
			
			
			
        }
		
        $result = array('status' => 'success', 'category' => $html);
        return new JsonResponse($result);
    }
	
	
}
