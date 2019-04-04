<?php

/**
 * @file
 * contains \Drupal\finder\Controller\FinderController.
 */

namespace Drupal\finder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * groups routes.
 */
class FinderController extends ControllerBase {

    /**
     * Gets the category products.
     *
     * @param int $tid
     *   A Shopify tid.
     * @param string $base_fullurl
     *   The Variant's base_fullurl.
     */
    /*     * ****** Get product category and product type using product id ******* */
    function get_parent_category_product_type($pid = NULL) {
        $pid = !empty($_POST['pid']) ? $_POST['pid'] : $pid;
        if (is_numeric($pid) && !empty($pid)) {
            $product_type_array = array(1 => 'Instruments', 2 => 'Reagents / Media', 3 => 'Accessories', 4 => 'Accessories & Consumables', 5 => 'Media', 6 => 'Software');
            $product_query = \Drupal::database()->select('node_field_data', 'nfd');
            $product_query->leftjoin('node__field_product_type', 'fpt', 'fpt.entity_id=nfd.nid');
            $product_query->leftjoin('node__field_category', 'nfc', 'nfc.entity_id=nfd.nid');
            $product_query->leftjoin('taxonomy_term_field_data', 'ttfd', 'ttfd.tid=nfc.field_category_target_id');
            $product_query->fields('fpt', array('field_product_type_value'));
            $product_query->fields('ttfd', array('name', 'tid'));
            $product_query->condition('nfd.nid', $pid);
            $product_query->condition('nfd.status', 1);
            $selected_data = $product_query->execute()->fetch();
            $loadParents = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadParents($selected_data->tid);
            $first_parent_cat = reset($loadParents);
            if (!empty($first_parent_cat)) {
                $selected_data->tid = $first_parent_cat->id();
                $selected_data->name = $first_parent_cat->getName();
            }
            $product_type = $product_type_array[$selected_data->field_product_type_value];
            $parent_category = $selected_data->name;
            $productresult = array('product_type_id' => $selected_data->field_product_type_value, 'product_type' => $product_type, 'product_type_en' => $product_type, 'parent_category_id' => $selected_data->tid, 'parent_category' => $parent_category, 'parent_category_en' => $parent_category);
            if ($_POST) {
                return new JsonResponse($productresult);
            } else {
                return $productresult;
            }
        }
    }
	
	/** ****** Get product category and product type using product id ******* */
    function get_categorypage_rfq($subcatid = NULL) {
        $subcatid = !empty($_POST['subcatid']) ? $_POST['subcatid'] : $subcatid;
        if (is_numeric($subcatid) && !empty($subcatid)) {
			$product_type_array = array(1 => 'Instruments', 2 => 'Reagents / Media', 3 => 'Accessories', 4 => 'Accessories & Consumables', 5 => 'Media', 6 => 'Software');
			$subcat_load = \Drupal\taxonomy\Entity\Term::load($subcatid);
			$subcat_name = $subcat_load->name->value;					
			$ptypeid = '1'; //1 for instrument..
            $catresult = array('product_type_id' => $ptypeid, 'product_type' => $subcat_name, 'product_type_en' => 'Instruments', 'parent_category_id' => $subcatid, 'parent_category' => $subcat_name, 'parent_category_en' => $subcat_name);
            if ($_POST) {
                return new JsonResponse($catresult);
            } else {
                return $catresult;
            }
        }
    }
	
    /******** Get RFQ for used product in customer breakthrough ********/
    function get_rfq_multiproducts($products_id = NULL, $multiproduct = NULL, $pagetype = NULL) {
        $productused_data = array();
        $products_id = !empty($_POST['products_id']) ? $_POST['products_id'] : $products_id;
        $multiproduct = !empty($_POST['multiproduct']) ? $_POST['multiproduct'] : $multiproduct;
        switch ($multiproduct) {
            case 'customerstory':
                if (is_numeric($products_id) && !empty($products_id)) {
                    $productused_query = \Drupal::database()->select('node_field_data', 'nfd');
                    $productused_query->leftjoin('node__field_customer_product', 'nfcp', 'nfcp.entity_id=nfd.nid');
                    $productused_query->fields('nfcp', array('field_customer_product_target_id'));
                    $productused_query->condition('nfd.nid', $products_id);
                    $productused_query->condition('nfd.status', 1);
                    $productused_data = $productused_query->execute()->fetchAll();
                }
                break;

            case 'multiproduct':
                if (is_array($products_id)) {
                    foreach ($products_id as $product_id) {
                        if (is_numeric($product_id)) {
                            $productused_data[]->field_customer_product_target_id = $product_id;
                        }
                    }
                }
                break;
			case 'quote-request-v2':		
			if ($products_id != '') {       		
				if (is_numeric($products_id)) {		
						   $productused_data[]->field_customer_product_target_id = $products_id;		
				}				
			}		
			break;
        }
        if (!empty($productused_data)) {
            global $base_url;
			$pagetype = !empty($_POST['pagetype']) ? $_POST['pagetype'] : $pagetype;
            $product_family = $line_of_busines = $pids = '';
            $loop_counter = 0;
            foreach ($productused_data as $productused) {
                $productused_id = $productused->field_customer_product_target_id;
                $nodeload = \Drupal\node\Entity\Node::load($productused_id); 
                if ($nodeload) {
                    if ($nodeload->get('field_select_series')->target_id != '') {
                        $serieslink = $nodeload->get('field_select_series')->target_id;
                        $seriesnodeload = Node::load($serieslink);
                        $producttitle = $seriesnodeload->getTitle();
                        $field_product_product_family = \Drupal\taxonomy\Entity\Term::load($seriesnodeload->get('field_product_product_family')->target_id);
                        $product_family = $field_product_product_family->name->value;
                        $field_product_line_of_bussiness = \Drupal\taxonomy\Entity\Term::load($seriesnodeload->get('field_product_line_of_bussiness')->target_id);
                        $line_of_busines = $field_product_line_of_bussiness->name->value;
                    } else {
                        $serieslink = $nodeload->id();
                        $producttitle = $nodeload->getTitle();
                        $nodeload->get('field_product_product_family')->target_id;
                        $field_product_product_family = \Drupal\taxonomy\Entity\Term::load($nodeload->get('field_product_product_family')->target_id);
                        $product_family = $field_product_product_family->name->value;
                        $field_product_line_of_bussiness = \Drupal\taxonomy\Entity\Term::load($nodeload->get('field_product_line_of_bussiness')->target_id);
                        $line_of_busines = $field_product_line_of_bussiness->name->value;
                    }
                    if ($loop_counter==0) { $first_product_family = $product_family; }
                    $pids  .= 'pid['.$serieslink.']='.$serieslink;
                    $pids  .= '&';
                    $pid[$productused_id] = $productused_id;
                    $products_data['Line_of_Business'][$line_of_busines] = $line_of_busines;
                    $products_data['Product_Family'][$product_family] = $product_family;
                    $products_data['Primary_Application'][$productused_id] = $producttitle;
                    $loop_counter++;
                }
                
            }
            if (!empty($pid)) {
                $rtrim_pids =  rtrim($pids,"&");
                $Return_URL  = $base_url.'/quote-request-success?'.urlencode($rtrim_pids);
                $all_Line_of_Business =  implode(",",$products_data['Line_of_Business']);
                $all_Product_Family =  implode(",",$products_data['Product_Family']);
                $all_Primary_Application =  implode(",",$products_data['Primary_Application']);
				
				if ($pagetype == 'rfq'){						
					$pardoturl = 'https://go.moleculardevices.com/l/83942/2018-12-06/bdjrhw';		
				}elseif($pagetype == 'quote-request'){ //quote-request-v2		
					$pardoturl = 'https://go.moleculardevices.com/l/83942/2019-02-18/bdx3bc';		
				}else{		
					$pardoturl = 'https://go.moleculardevices.com/l/83942/2018-03-24/9tfrr9';			
				}
	
                $iframeurl = $pardoturl.'?Line_of_Business='.$all_Line_of_Business.'&Product_Family='.$first_product_family.'&product_selection='.$all_Product_Family.'&Primary_Application='.$all_Primary_Application.'&Return_URL='. $Return_URL;
				
                $productresult = array('iframeurl' => $iframeurl, 'prdcts' => $all_Primary_Application);
            }
            else{
                $productresult = array('iframeurl' => '');
            }

        }
        else{
            $productresult = array('iframeurl' => '');
        }
        return new JsonResponse($productresult);
    }
    /**
     * Related products mapping 
     */	 

    public function getRelatedProducts($catparent, $type = 'cat', $nid = '') {
        $html = '';
        /* backup code */
        $cards = array();
        $cards[0]['name'] = 'ASSAY KITS';
        $cards[0]['url'] = '/products/assay-kits';
        $cards[0]['img'] = '/themes/moldev/images/cards-images/assay-kits.png';

        $cards[1]['name'] = 'SOFTWARE';
        $cards[1]['url'] = '/products/microplate-readers/acquisition-and-analysis-software/softmax-pro-software';
        $cards[1]['img'] = '/themes/moldev/images/cards-images/software.png';

        $cards[2]['name'] = 'CONSUMABLES';
        $cards[2]['url'] = '/products/accessories-consumables';
        $cards[2]['img'] = '/themes/moldev/images/cards-images/consumables.png';

        $cards[3]['name'] = 'SERVICES';
        $cards[3]['url'] = '/services/customization-and-automation';
        $cards[3]['img'] = '/themes/moldev/images/cards-images/services.jpg';

        $cards[4]['name'] = 'MEDIA';
        $cards[4]['url'] = '/products/cell-culture-media';
        $cards[4]['img'] = '/themes/moldev/images/cards-images/media.png';

        $cards[5]['name'] = 'Instruments';
        $cards[5]['url'] = '/products/microplate-readers';
        $cards[5]['img'] = '/sites/default/files/styles/related_products/public/products/banners/Default%20Image_8.jpg';

        $cards[6]['name'] = 'Microplate Readers';
        $cards[6]['url'] = '/products/microplate-readers';
		if($nid=='2811'){
		$cards[6]['img'] = '/themes/moldev/images/cards-images/smax-m-series.jpg';	
		}else{
        $cards[6]['img'] = '/themes/moldev/images/cards-images/microplate-readers.png';
		}
        
        $cards[7]['name'] = 'Professional Services';
        $cards[7]['url'] = '/services/professional-services';
        $cards[7]['img'] = '/themes/moldev/images/cards-images/professional-services.jpg';

        $cards[8]['name'] = 'Validation and Compliance';
        $cards[8]['url'] = '/products/microplate-readers/validation-and-compliance';
        $cards[8]['img'] = '/themes/moldev/images/cards-images/validation-and-compliance.png';

        $cards[9]['name'] = 'SOFTWARE';
        $cards[9]['url'] = '/products/patch-clamp-system/acquisition-and-analysis-software/pclamp-11-software';
        $cards[9]['img'] = '/themes/moldev/images/cards-images/software.png';

        $cards[10]['name'] = 'SOFTWARE';
        $cards[10]['url'] = '/products/cellular-imaging-systems#Acquisition-and-Analysis-Software';
        $cards[10]['img'] = '/themes/moldev/images/cards-images/software.png';

        $cards[11]['name'] = 'Amplifiers';
        $cards[11]['url'] = '/products/axon-patch-clamp-system#Amplifiers';
        $cards[11]['img'] = '/themes/moldev/images/cards-images/amplifiers.jpg';

        $cards[12]['name'] = 'Digitizers';
        $cards[12]['url'] = '/products/axon-patch-clamp-system#Digitizers';
        $cards[12]['img'] = '/themes/moldev/images/cards-images/digitizers.jpg';

        $cards[13]['name'] = 'Stacker';
        $cards[13]['url'] = '/products/microplate-readers/washers/stakmax-microplate-handling-system';
        $cards[13]['img'] = '/themes/moldev/images/cards-images/stacker.jpg';

        $cards[14]['name'] = 'Microplate Washers';
        $cards[14]['url'] = '/products/microplate-readers/washers';
        $cards[14]['img'] = '/themes/moldev/images/cards-images/washers.jpg';

        $cards[15]['name'] = 'High Throughput screening';
        $cards[15]['url'] = '/products/flipr-tetra-high-throughput-cellular-screening-system';
        $cards[15]['img'] = '/themes/moldev/images/cards-images/high-throughput.jpg';
		
		$cards[16]['name'] = 'SOFTWARE';     
		if($nid=='437'){		
		$cards[16]['url'] = '/products/microplate-readers/acquisition-and-analysis-software/softmax-pro-gxp-software';
		$cards[16]['img'] = '/themes/moldev/images/cards-images/softmax-pro-gxp-software.png';	
		}else{        
		$cards[16]['url'] = '/products/microplate-readers#Acquisition-and-Analysis-Software';
		$cards[16]['img'] = '/themes/moldev/images/cards-images/software.png';
		}

        /* For BR */
        $r_products['12']['cat'] = array(1, 0, 2, 3, 8);
        $r_products['12']['prdct'] = array(1, 0, 2, 3, 8);

        /* IMG */
        $r_products['25']['cat'] = array(10, 0, 3);
        $r_products['25']['prdct'] = array(10, 0, 3);

        /* BPD */
        $r_products['33']['cat'] = array(4, 2, 3);
        $r_products['33']['prdct'] = array(4, 2, 3);

        /* CNS */
        $r_products['24']['cat'] = array(11, 12); //array (9,2,3);old
        $r_products['24']['prdct'] = array(11, 12, 9); //array (9,2,3);old

        /* Microplate Washers */
        $r_products['20']['cat'] = array(6, 1, 7, 13);
        $r_products['20']['prdct'] = array(6, 1, 7, 13);

        /* Assay Kits */
        $r_products['21']['cat'] = array(6);
        //$r_products['21']['prdct'] = array (6);

        /* Validation and Compliance */
        $r_products['19']['cat'] = array(6, 1, 7);
		if($nid=='2811'){
		$r_products['19']['prdct'] = array(6, 3, 8);
		}else{
        $r_products['19']['prdct'] = array(6, 1, 7);
		}
		
        /* Validation and Compliance */
        $r_products['18']['cat'] = array(6, 3, 8);
        $r_products['18']['prdct'] = array(16, 6, 3, 8);

        /* FLIPR */
        $r_products['545']['cat'] = array(0, 3);
        $r_products['545']['prdct'] = array(0, 3);

        /* Stacker */
        $r_products['427']['cat'] = array();
        $r_products['427']['prdct'] = array();
        $r_products['426']['cat'] = array(11, 12); //array (9,2,3);old
        $r_products['426']['prdct'] = array(11, 12); //array (9,2,3);old

        /* Stacker */
        $r_products['800']['cat'] = array(6, 14);
        $r_products['800']['prdct'] = array(6, 14);

        if (array_key_exists($catparent, $r_products)) {

            foreach ($r_products[$catparent][$type] as $obj_key) {
				$unique_class = $alt ="";
				$soft_url = "/products/cellular-imaging-systems#Acquisition-and-Analysis-Software";
				if($cards[$obj_key]['url'] == $soft_url){
					$unique_class = "acquisition-software ";
					$alt = 427;
				}

                $html .= '<div class="item full_cards col-xs-12 col-sm-4">
							<div class="pro-container" style="background:url(' . $cards[$obj_key]['img'] . ') no-repeat center; background-size:cover;">							
							<label class="pro-label">' . $cards[$obj_key]['name'] . '</label>		
							<a class="'.$unique_class.'" alt="'.$alt.'" href="' . $cards[$obj_key]['url'] . '"><div class="prothumb"></div></div>
							</a>							
						</div>';
            }
        }
        return $html;
    }

    public function categorypage_product($tid = "", $base_fullurl) {  //Function For Category Page Products
        global $base_url;
        $html = '';
        if ($tid != "" && $tid != 0) {
			$checkarr = array('14','15','16');
			if(in_array($tid,$checkarr)){
               $allcat = array('13',$tid);
			}else{
			   $allcat[] = $tid;	
			}
        } else {
            $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('product_groups', $base_fullurl, NULL, TRUE);
            if (!empty($tree)) {
                foreach ($tree as $term) {
                    $getcat = \Drupal\taxonomy\Entity\Term::load($term->get('tid')->value);
                    $display_cat = $getcat->get('field_display_on_category')->value;
                    if ($display_cat == '1') {
                        $allcat[] = $term->id();
                    }
                }
            } else {
            $allcat[] = $base_fullurl;
            }
        }

        $products = \Drupal::database()->select('node__field_category', 'gfd');
        $products->join('node_field_data', 'nfd', 'nfd.nid=gfd.entity_id');
        $products->leftjoin('node__field_is_series_product', 'sp', 'sp.entity_id=gfd.entity_id');
		$products->leftjoin('node__field_weight', 'fw', 'fw.entity_id=nfd.nid');
        $products->leftjoin('taxonomy_term_field_data', 'ttfd', 'ttfd.tid=gfd.field_category_target_id');
        $products->fields('gfd', array('entity_id'));
        $products->fields('nfd', array('created'));
        $products->condition('nfd.status', 1);
        $products->condition('sp.field_is_series_product_value', 1, '!=');
        $products->condition('gfd.field_category_target_id', $allcat, 'IN');
		if ($tid != "" && $tid != 0) {
        $products->orderBy('ttfd.weight', 'DESC');
		}else{
		$products->orderBy('ttfd.weight', 'ASC');	
		}
        //$products->orderBy('nfd.created', 'ASC');
		$products->orderBy('fw.field_weight_value', 'ASC');
        $product_ids = $products->execute()->fetchAll();
        foreach ($product_ids as $pid) {
            $productids[] = $pid->entity_id;
        }
        if (!empty($productids)) {
            foreach ($productids as $pids) {
                $nodeload = Node::load($pids);
                $title = $nodeload->getTitle();
                $body = trim_text($nodeload->get('field_product_summary')->value, $length = 100, $ellipses = true, $strip_html = true);
                $fid = $nodeload->get('field_banner_image')->target_id;
                $fileload = \Drupal\file\Entity\File::load($fid);
                $product_image = \Drupal\image\Entity\ImageStyle::load('related_products')->buildUrl($fileload->getFileUri());
				$product_image_alt = $nodeload->field_banner_image->getValue()[0]['alt'];
                $catid = $nodeload->get('field_category')->target_id;
                if ($nodeload->get('field_select_series')->target_id != '') {
                    $serieslink = $nodeload->get('field_select_series')->target_id;
                } else {
                    $serieslink = $nodeload->id();
                }
                $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $serieslink);

				$nid = $nodeload->id();
				$check_specs = db_query("SELECT * FROM `product_features` WHERE product_id='".$nid."'");
				$check_specs->allowRowCount = TRUE;
				$count_specs = $check_specs->rowCount();	
				
                if ($nodeload->get('field_product_type')->value == '1') { //1 = instrument
				    if ($count_specs != 0) {
                    $compare_section = '<span class="compare">Compare (<span class="comparecountno">0</span>) 
							<div class="filter-checkbox"><span><input type="checkbox" name="procomp[]" class="procamp procampcheckbox' . $nid . '" id="' . $title . '" value="' . $nid . '" alt="' . $catid . '"><span class="icheck"></span></span></div></span>';
					}else{
					$compare_section = '';	
					}
                } else {
                    $compare_section = '';
                }

                if ($nodeload->get('field_product_type')->value != '2') { //2 for assay kits
                    $proimg = '<div class="prothumb"><img src="' . $product_image . '" alt="'.$product_image_alt.'"></div>';
                } else {
                    $proimg = '';
                }
				
				$node_created = $nodeload->getCreatedTime();
				if(strtotime('18 month ago') < ($node_created)){
					$batch = '<div class="batch">New</div>';
				}else{
					$batch = '';
				}
				
                $html .= '<div class="item col-xs-12">
						<div class="pro-container"> '.$batch.'
							<a href="' . $base_url . $alias . '"> ' . $proimg . '
							<div class="pro-details">
								<h3>' . $title . '</h3>
								<p>' . $body . '</p>
							</div>
							</a>
    				    <div class="compare-box">
							<a href="' . $base_url . $alias . '" class="linkBtn">Details<span class="icon-icon_link"></span></a>
							' . $compare_section . '
					    </div>	
						
						</div>
					 </div>';
            }
        } else {
            $html .= '<p class="text-center marg-tp-30">No Product Found.</p>';
        }
        $result = array('status' => 'productslist', 'products' => $html);
        return new JsonResponse($result);
    }

    public function categorypage_relatedproduct($tid = "", $base_fullurl) {  //Function For Category Page Related Products
        global $base_url;
        $html = '';
        $html = $this->getRelatedProducts($base_fullurl);
        $result = array('status' => 'relatedproductslist', 'relatedproducts' => $html);
        return new JsonResponse($result);
    }

    public function productpage_specifications($base_fullurl) {  //Function For Product Specifications
        $value = \Drupal::request()->request->get('specifiheadarr');
        if (!empty($value)) {
            $spec = explode(',', $value);
            foreach ($spec as $specc) {
                $speci[] = $specc;
            }
        }

        $first_key_val = '';
        $pid = $base_fullurl;
        $prodet = Node::load($pid);
        if ($prodet->get('field_is_series_product')->value == '1') {

            $prodcat = \Drupal::database()->select('node__field_select_series', 'ss');
            $prodcat->innerjoin('node_field_data', 'nfd', 'nfd.nid=ss.entity_id');
            $prodcat->fields('ss', array('entity_id'));
            $prodcat->condition('ss.field_select_series_target_id', $prodet->id());
            $prodcat->condition('nfd.status', 1);
            $catparent = $prodcat->execute()->fetchAll();
            foreach ($catparent as $catpe) {
                $prodett = Node::load($catpe->entity_id);
                $pids[] = $prodett->id();
                $catid[] = $prodett->get('field_category')->target_id;
            }
            if (!empty($pids)) {
                foreach ($pids as $pid) {
                    $products[] = $this->logic_series_specification($pid);
                }
            }

            if (!empty($speci)) {
                $products = $speci;
                $products = array_unique($products);
            }

            $results = $this->render_multi_specification($products, $pids); //for multiple result array
            return new JsonResponse($results);
        } else {

            $products[] = $this->logic_series_specification($pid);

            $get_arr_keys = array_keys($products[0]);
            if (count($get_arr_keys) >= 1) {
                if (in_array('322', $get_arr_keys))
                    $first_key_val = '322';
                else
                    $first_key_val = $get_arr_keys[0];
            }

            $new_prod_speci_arr = array();
            $new_prod_speci_arr[$first_key_val] = $products[0][$first_key_val];

            foreach ($products[0] as $k => $prod_arr) {
                if ($k != '322') {
                    $new_prod_speci_arr[$k] = $prod_arr;
                }
            }

            $custom_arr = $new_prod_speci_arr;
            $results = $this->render_specification($custom_arr, $pid);   //for single result array
            return new JsonResponse($results);
        }
    }

    public function render_multi_specification($products, $pid) {  //Function For Product multiple Specifications
        global $base_url;
        $specoptions = '';
        $html = '';
        $nodeids_array = array();
        $arr_fixed_spec = array();
        $arr_scroll_spec = array();

        $result = \Drupal::database()->select('product_features', 'pf');
        $result->join('node__field_display_status', 'ds', 'ds.entity_id=pf.feature_id');
        $result->join('node_field_data', 'nfd', 'nfd.nid=pf.product_id');
        $result->join('node__field_product_specification_head', 'sh', 'sh.entity_id=pf.feature_id');
        $result->fields('pf', ['product_id', 'feature_id', 'feature_value', 'feature_comparison']);
        $result->fields('sh', ['field_product_specification_head_target_id']);
        $result->condition('pf.product_id', $pid, 'IN');
        $result->condition('nfd.status', 1);
        $result->condition('ds.field_display_status_value', 2);  //2 = product specification 
        $result->orderBy('nfd.created', 'ASC');
        $res = $result->execute()->fetchAll();

        if (!empty($res)) {
            foreach ($res as $subpro) {
                $featureids = \Drupal\node\Entity\Node::load($subpro->feature_id);
                $tiemget = db_query("select * from node_field_data where nid='$subpro->feature_id'");
                foreach ($tiemget as $key => $timeg) {
                    $credtime = $timeg->created;
                }

                if (!empty($featureids)) {
                    $cat_name = \Drupal\taxonomy\Entity\Term::load($subpro->field_product_specification_head_target_id);

                    $arr3[$subpro->field_product_specification_head_target_id][$featureids->getTitle()]['created'] = $timeg->created;

                    $arr3[$subpro->field_product_specification_head_target_id][$featureids->getTitle()]['speci_title'] = $featureids->getTitle();

                    $arr3[$subpro->field_product_specification_head_target_id][$featureids->getTitle()][$subpro->product_id]['checkbox'] = $subpro->feature_comparison;

                    $arr3[$subpro->field_product_specification_head_target_id][$featureids->getTitle()][$subpro->product_id]['text'] = $subpro->feature_value;
                }
                $nodeids_array[$subpro->product_id] = $subpro->product_id;
            }
            sort($nodeids_array);
        }

        $new_arr = array();
        $default_specifi_key = '322'; //322 is General Specification type should show on top..
        if (!empty($arr3[$default_specifi_key])) {
            foreach ($arr3 as $k => $v) {
                if ($k != $default_specifi_key)
                    $new_arr[$k] = $v;
            }
            $new_arr = array($default_specifi_key => $arr3[$default_specifi_key]) + $new_arr;
        }else {
            $new_arr = $arr3;
        }

        $new_arr3 = $new_arr;
        $count_prod = count($nodeids_array);
        if ($count_prod == 2) {
            $newpod_responsive = 'col-xs-12 col-sm-6';
        } else if ($count_prod == 3) {
            $newpod_responsive = 'col-xs-12 col-sm-4';
        } else if ($count_prod >= 4) {
            $newpod_responsive = 'col-xs-12 col-sm-4 col-md-3';
        }
        $specification_main_product = '';

        $tot_multi_spec_prod = count($nodeids_array);

        foreach ($nodeids_array as $node_id) {
            $nodeids = \Drupal\node\Entity\Node::load($node_id);
            if ($nodeids->get('field_select_series')->target_id != '') {
                $serieslink = $nodeids->get('field_select_series')->target_id;
            } else {
                $serieslink = $nodeids->id();
            }
            $imageUrl = $nodeids->get('field_banner_image')->entity->uri->value;
            if ($imageUrl != '') {
                $img2 = '<img src="' . file_create_url($imageUrl) . '" width="100%">';
            } else {
                $img2 = '<img src="' . $base_url . '/sites/default/files/No-image-found.jpg" width="100%">';
            }

            $specification_main_product .= '<td>
						<div class="pro-container no_scale">	
							<div class="pro-details ">
								<h3 data-toggle="tooltip" data-placement="bottom" title="' . $nodeids->getTitle() . '">' . $nodeids->getTitle() . '</h3>
							</div>
							<div class="link_wrap">														
								<a href="/quote-request?pid=' . $serieslink . '" class="linkBtn">Request a Quote<span class="icon-icon_link"></span></a>
							</div>
						</div>
					</td>';
        }

        $specification_headngs = '';
        $temp_specification_headings = '';
        $specification_prod_values = '';
        $specification_main_product_ids = array();

        // Start code for specification options..
        if (count($new_arr3) > 1) {
            $specoptions .= '<div class="tab-heading marg-tp-20-mb marg-tp-20-sm">
                              <span class="multi_specifi"><a class="gradiantBlueBtn btn pull-right">Options</a></span>
                            </div>';
        } else {
            $specoptions = '';
        }

        // End code of specification options..	
        foreach ($new_arr3 as $k => $final_prod_arr) {
            $cat_name = \Drupal\taxonomy\Entity\Term::load($k);
            if ($cat_name->name->value == 'Technical Specifications') {
                $head_catname = 'Technical Specifications for SpectraMax MiniMax 300 Imaging Cytometer';
            } else {
                $head_catname = $cat_name->name->value;
            }
            $specification_headngs .= '<td>
										<h3 class="spec_title" id="' . $k . '">' . $head_catname . '</h3>
									   </td>';

            usort($final_prod_arr, function($a, $b) {
                return $a['created'] <=> $b['created']; //for asc order
            });

            foreach ($final_prod_arr as $k1 => $v1) {
                $bool_status = false;
                $arr_text = array_unique(array_column($v1, 'text'));
                $arr_chkbox = array_unique(array_column($v1, 'checkbox'));
                if (array_diff($arr_text, array("0", "", "-"))) {
                    $bool_status = true;
                }

                if (!$bool_status && in_array("1", $arr_chkbox)) {
                    $bool_status = true;
                }

                if (!$bool_status) {
                    continue;
                }

                $specification_headngs .= '<tr><td>
                                                    <p><strong>' . $v1['speci_title'] . '</strong></p>
                                            </td></tr>';
            }
        }

        $numOfItemsInArr = count($new_arr3);
        $indexArr = 0;


        foreach ($new_arr3 as $pk => $final_prod_values_arr) {
            usort($final_prod_values_arr, function($a, $b) {
                return $a['created'] <=> $b['created'];
            });

            foreach ($final_prod_values_arr as $pk2 => $pv2) {
                $keys = array_keys($pv2);
                sort($keys);
                $pv1 = array();
                foreach ($keys as $k => $v) {
                    $pv1[$v] = $pv2[$v];
                }
                $pv2 = array();
                $pv2 = $pv1;

                $bool_status = false;
                $arr_text = array_unique(array_column($pv2, 'text'));
                $arr_chkbox = array_unique(array_column($pv2, 'checkbox'));

                if (array_diff($arr_text, array("0", "", "-"))) {
                    $bool_status = true;
                }
                if (!$bool_status && in_array("1", $arr_chkbox)) {
                    $bool_status = true;
                }

                if (!$bool_status) {
                    continue;
                }
                $specification_prod_values .= '<tr>';
                if (count($pv2) < count($nodeids_array)) {

                    $pv2_keys = array_keys($pv2);
                    $nodeids_keys = array_values($nodeids_array);
                    $not_assign_keys = array_diff($nodeids_keys, $pv2_keys);

                    if (!empty($not_assign_keys)) {
                        foreach ($not_assign_keys as $nk => $nv) {
                            $pv2[$nv] = array();
                        }
                    }
                }

                ksort($pv2);

                foreach ($pv2 as $k3 => $v3) {
                    if ($k3 != 'created' && $k3 != 'speci_title') {
                        $count_prod = count($nodeids_array);
                        if ($count_prod == 2) {
                            $newpod_responsive = 'col-xs-12 col-sm-6';
                        } else if ($count_prod == 3) {
                            $newpod_responsive = 'col-xs-12 col-sm-4';
                        } else if ($count_prod >= 4) {
                            $newpod_responsive = 'col-xs-12 col-sm-4 col-md-3';
                        }
                        $specification_prod_values .= '<td>';
                        if (isset($v3['checkbox']) && $v3['checkbox'] != '0') {
                            $specification_prod_values .= '<p class="a1"><img src="' . $base_url . '/themes/moldev/images/done.png" height="30" width="30"></p>';
                        }

                        if (isset($v3['text']) && $v3['text'] != '') {
                            if (($v3['text'] != '0') && ($v3['text'] != '-')) {
                                $specification_prod_values .= '<p id="' . $k3 . '">' . nl2br($v3['text']) . '</p>';
                            } else {
                                if (isset($v3['checkbox']) && $v3['checkbox'] != '0') {
                                    
                                } else {
                                    $specification_prod_values .= '<p class="a3"><img src="' . $base_url . '/themes/moldev/images/horizontal-line.png" height="30" width="30"></p>';
                                }
                            }
                        }

                        if ($v3['text'] == '' && $v3['checkbox'] == '0') {
                            $specification_prod_values .= '<p class="a3"><img src="' . $base_url . '/themes/moldev/images/horizontal-line.png" height="30" width="30"></p>';
                        }
                        $specification_prod_values .= '</td>';
                    }
                }
                $specification_prod_values .= '</tr>';
            }

            if (++$indexArr != $numOfItemsInArr) {
                $specification_prod_values .= '<tr class="wt_bg">
                        <td colspan="' . $tot_multi_spec_prod . '">
                        </td>
                </tr>';
            }
        }

        $html .= '<div class="">		
                <div class="fixedTable clearfix" id="demo">						

                        <!--Header-->
                        <div class="fixedTable-header col-xs-6 col-sm-9 col-xs-offset-6 col-sm-offset-3">
                                <table class="">
                                <tr>
                                        ' . $specification_main_product . '
                                        </tr>
                                </table>
                        </div>
                        <!--Header-->

                        <!--Pannel Left-->
                        <div class="fixedTable-sidebar col-xs-6 col-sm-3">
                                <table class="">
                                        ' . $specification_headngs . '
                                </table>
                        </div>
                        <!--Pannel Left-->

                        <!--Pannel Right-->
                        <div class="fixedTable-body col-xs-6 col-sm-9">
                                <table class="">
                                        <tr class="wt_bg">
                                                <td colspan="' . $tot_multi_spec_prod . '"></td>
                                        </tr>
                                        ' . $specification_prod_values . '
                                </table>
                        </div>
                        <!--Pannel Right-->

                </div>
                </div>';


        $result = array('status' => 'relatedproductslist', 'relatedproducts' => $html, 'specoptions' => $specoptions);
        return $result;
    }

    public function render_specification($products, $pid) {   //Function For Product single Specifications
	global $base_url;
        $specoptions = '';
        $html = '';
	
	    $spec_heads = array_keys($products);
		$spechead_arr = array();
		if(!empty($spec_heads)){
			foreach($spec_heads as $spechead){			
				$spechading = \Drupal\taxonomy\Entity\Term::load($spechead);                
				$spechead_name = $spechading->name->value;
				$spechead_arr[$spechead] = $spechead_name;
			}
		}
		$option_str_exist = false;
		
		if(!empty($spechead_arr)){
			foreach($spechead_arr as $key=>$specificationName){
				if (strpos($specificationName, 'Options') !== false)
					$option_str_exist = true;
				
			}
			
		}
		
         if ((count($products) > 1) && $option_str_exist){
            $specoptions .= '<div class="tab-heading marg-tp-20-mb marg-tp-20-sm col-sm-12 col-xs-12">
                              <span class="single_specifi"><a class="gradiantBlueBtn btn pull-right">Options</a></a>
                            </div>';
        } else {
            $specoptions = '';
        }
        $html .= '<div class="table_specs">
                    <div class="row">
                                <div class="col-xs-12">
                                        <div class="section-heading text-center">
                                        </div>
                                </div>
                        </div>


                        <div class="row">
                                <div class="col-xs-12">';

        $html .= ' <div class="horizontal-scroll">
                    <div class="comparison-table">

                    <div class="comparison-row">
                          <div class="row">
                                  <div class="col-xs-6 col-sm-4">
                                          <div class="comparison-cell title">
                                                  &nbsp;
                                          </div>
                                  </div>';

        $nodeids = \Drupal\node\Entity\Node::load($pid);
        $imageUrl = $nodeids->get('field_banner_image')->entity->uri->value;
        if ($imageUrl != '') {
            $img2 = '<img src="' . file_create_url($imageUrl) . '" width="100%">';
        } else {
            $img2 = '<img src="' . $base_url . '/sites/default/files/No-image-found.jpg" width="100%">';
        }
        $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $nodeids->id());
        $html .= '<div class="col-xs-6 col-sm-8">
                        <div class="comparison-cell">
                                        <div class="pro-container">													

                                        <div class="pro-details">
                                                <h3>' . $nodeids->getTitle() . '</h3>
                                        </div>
                                        <div class="link_wrap">														
                                                <a href="/quote-request?pid=' . $nodeids->id() . '" class="linkBtn">Request a Quote<span class="icon-icon_link"></span></a>
                                        </div>
                                        </div>
                        </div>
                </div>';


        $html .= '</div>
		 </div>';

        if (!empty($products) && count($products)>0) {
			
            foreach ($products as $key => $pros) {

                if ($key == 'specification_keys') {
                    continue;
                }
                $single_specific_arr = array();

                $cat_name = \Drupal\taxonomy\Entity\Term::load($key);
				
                if ($cat_name->name->value == 'Technical Specifications' && $pid == '223') {
                    $head_catname = 'Technical Specifications for SpectraMax MiniMax 300 Imaging Cytometer';
                } else {
                    $head_catname = $cat_name->name->value;
                }
				
				if($key=='810'){ //$head_catname == 'Explore System Modifications with Advanced Workflow Engineering Solutions*'
				    $borderclass = 'upperspec-class';
				}else{
				    $borderclass = '';
				}				
				
				if (strpos($head_catname, 'Options') !== false){
					$op_strexist = 'options-title';
				}else{
					$op_strexist = '';
				}
				
                $html .= '
                        <div class="comparison-row '.$borderclass.'">
                                <div class="row">
                                      <div class="col-xs-6 col-sm-4"><div class="comparison-cell"><h3 class="spec_title '.$op_strexist.'"  id="">' . $head_catname . '</h3></div>
                                      </div><div class="col-xs-6 col-sm-8 wt_bg">

                                      </div>
                                </div>
                        </div>';

                if (!empty($pros)) {
                    foreach ($pros as $pp) {

                        $pnode = Node::load($pp);
                        $label = $pnode->getTitle();

                        $display_pos = $pnode->get('field_display_status')->value;
                        $fea_id = $pnode->id();

                        $default_feature = \Drupal::database()->select('product_features', 'efc');
                        $default_feature->leftjoin('node_field_data', 'nfd', 'nfd.nid=efc.feature_id');
                        $default_feature->fields('efc', array('feature_comparison', 'feature_value'));
                        $default_feature->fields('nfd', array('created'));
                        $condition = db_or()
                                ->condition('efc.feature_value', '', '!=')
                                ->condition('efc.feature_comparison', '0', '!=');
                        $default_feature->condition($condition);
                        $default_feature->condition('efc.product_id', $pid);
                        $default_feature->condition('efc.feature_id', $pnode->id());
                        $default_feature->orderBy('nfd.created', 'ASC');
                        $def_feature = $default_feature->execute();

                        foreach ($def_feature as $default_feat) {
                            $cred2 = date('d-M-Y', $default_feat->created);
                            $cred = $default_feat->created;

                            if ($default_feat->feature_comparison != '0') {
                                $single_specific_arr[$label] = 'img';
                            }
                            if ($default_feat->feature_value != '') {
                                if ($default_feat->feature_value != '0') {

                                    $single_specific_arr[$label] = nl2br($default_feat->feature_value);
                                }
                            }
                            if ($default_feat->feature_comparison == '0' && ($default_feat->feature_value == '' || $default_feat->feature_value == '0')) {
                                $single_specific_arr[$label] = 'blank';
                            }
                        }
                        $feavalue = array();
                    }
                }

                foreach ($single_specific_arr as $label => $val) {
                    if ($val == '-')
                        continue;
                    $html .= '<div class="comparison-row">
                                <div class="row">
                                <div class="col-xs-6 col-sm-4">
                                  <div class="comparison-cell">
                                        <p><strong>' . $label . '</strong></p>
                                  </div>
                                </div>';
                    $html .= '<div class="col-xs-6 col-sm-8">
                            <div class="comparison-cell">';
                    if ($val == 'img') {
                        $html .= '<p><img src="' . $base_url . '/themes/moldev/images/done.png" height="30" width="30"></p>'; //&#10004;
                    }
                    if (($val != '') && ($val != '-') && ($val != 'img')) {
                        $html .= '<p>' . $val . '</p>';
                    }
                    if ($val == 'blank') {
                        $html .= '<p><img src="' . $base_url . '/themes/moldev/images/horizontal-line.png" height="30" width="30"></p>'; //&#10004;
                    }


                    $html .= '</div>
                             </div>';
                    $html .= '</div>
                             </div>';
                }
            }
        }
        $html .= '</div>
					</div>';
        $single_specific_arr = array();

        $html .= '</div>
					</div>					
				
			</div>';

        $result = array('status' => 'relatedproductslist', 'relatedproducts' => $html, 'specoptions' => $specoptions);
        return $result;
    }

    public function logic_series_specification($pid) {

        $products = array();
        $prodet = Node::load($pid);

        $catid = $prodet->get('field_category')->target_id;

        $query = \Drupal::database()->select('taxonomy_term__parent', 'th');
        $query->fields('th', array('parent_target_id'));
        $query->condition('th.entity_id', $catid);
        $catparent = $query->execute()->fetchField();
        $query_par = \Drupal::database()->select('taxonomy_term__parent', 'thpar');
        $query_par->fields('thpar', array('parent_target_id'));
        $query_par->condition('thpar.entity_id', $catparent);
        $query_par->condition('thpar.parent_target_id', 0, '!=');
        $catparentmain = $query_par->execute()->fetchField();
        if ($catparentmain != '') {
            $cate[] = $catparentmain;
        }
        if ($catparent != '') {
            $cate[] = $catparent;
        }

        $query1 = \Drupal::database()->select('node__field_product_reference', 'fp');
        $query1->leftjoin('node__field_product_specification_head', 'sh', 'sh.entity_id=fp.entity_id');
        $query1->leftjoin('node__field_display_status', 'ds', 'ds.entity_id=fp.entity_id');
        $query1->join('node_field_data', 'nfd', 'nfd.nid=fp.entity_id');
        $query1->join('product_features', 'pf', 'pf.feature_id=fp.entity_id');
        $query1->fields('fp', array('entity_id'));
        $query1->fields('sh', array('field_product_specification_head_target_id'));
        $query1->fields('nfd', array('created'));
        $condition = db_or()
                ->condition('pf.feature_value', '', '!=')
                ->condition('pf.feature_comparison', '0', '!=');
        $query1->condition($condition);
        $query1->condition('fp.field_product_reference_target_id', $pid);
        $query1->condition('pf.product_id', $pid);
        $query1->condition('ds.field_display_status_value', 2); // 2 = display in product specification..
        $query1->condition('nfd.status', 1);
        $query1->orderBy('nfd.created', 'ASC');
        $products_list = $query1->execute()->fetchAll();

        $unsorted_prod_obj = array();

        if (count($products_list) > 0) {
            $unsorted_prod_obj = $products_list;
        }

        $query2 = \Drupal::database()->select('node__field_category', 'fc');
        $query2->leftjoin('node__field_category_reference', 'cr', 'cr.field_category_reference_target_id=fc.field_category_target_id');
        $query2->leftjoin('node__field_product_specification_head', 'sh', 'sh.entity_id=cr.entity_id');
        $query2->leftjoin('node__field_display_status', 'ds', 'ds.entity_id=cr.entity_id');
        $query2->join('node_field_data', 'nfd', 'nfd.nid=cr.entity_id');
        $query2->join('product_features', 'pf', 'pf.feature_id=cr.entity_id');
        $query2->fields('cr', array('entity_id'));
        $query2->fields('nfd', array('created'));
        $query2->fields('sh', array('field_product_specification_head_target_id'));
        $condition = db_or()
                ->condition('pf.feature_value', '', '!=')
                ->condition('pf.feature_comparison', '0', '!=');
        $query2->condition($condition);
        $query2->condition('ds.field_display_status_value', 2); // 2 = display in product specification..
        $query2->condition('nfd.status', 1);
        $query2->condition('fc.entity_id', $pid);
        $query2->condition('pf.product_id', $pid);
        $query2->orderBy('nfd.created', 'ASC');
        $cat_list = $query2->execute()->fetchAll();

        if (count($cat_list) > 0) {
            $unsorted_prod_obj = array_merge($unsorted_prod_obj, $cat_list);
        }

        $query3 = \Drupal::database()->select('node__field_category_reference', 'nfc');
        $query3->leftjoin('node__field_product_specification_head', 'sh', 'sh.entity_id=nfc.entity_id');
        $query3->leftjoin('node__field_display_status', 'ds', 'ds.entity_id=sh.entity_id');
        $query3->join('node_field_data', 'nfd', 'nfd.nid=nfc.entity_id');
        $query3->join('product_features', 'pf', 'pf.feature_id=nfc.entity_id');
        $query3->fields('nfc', array('entity_id'));
        $query3->fields('nfd', array('created'));
        $query3->fields('sh', array('field_product_specification_head_target_id'));
        $condition = db_or()
                ->condition('pf.feature_value', '', '!=')
                ->condition('pf.feature_comparison', '0', '!=');
        $query3->condition($condition);
        $query3->condition('nfc.field_category_reference_target_id', $cate, 'IN');
        $query3->condition('pf.product_id', $pid);
        $query3->condition('ds.field_display_status_value', 2); // 2 = display in product specification..
        $query3->condition('nfd.status', 1);
        $query3->orderBy('nfd.created', 'ASC');
        $parent_list = $query3->execute()->fetchAll();

        if (count($parent_list) > 0) {
            $unsorted_prod_obj = array_merge($unsorted_prod_obj, $parent_list);
        }

        $unsorted_prod_arr = array();
        foreach ($unsorted_prod_obj as $key => $obj) {
            $unsorted_prod_arr[] = (array) $obj;
        }

        usort($unsorted_prod_arr, function($a, $b) {
            return $a['created'] <=> $b['created'];
        });

        $new_prod = array();
        foreach ($unsorted_prod_arr as $pplist) {
            if ($pplist['entity_id'] != '') {
                $new_prod[$pplist['field_product_specification_head_target_id']][$pplist['entity_id']] = $pplist['entity_id'];
            }
        }

        return $new_prod;
    }

    public function product_finder($page_type = NULL, $step_one_sidebar = NULL, $step_four = NULL) {
        global $base_url;
        $steps_li = $step4continue = $rfq_bottom_setproducts = '';
        switch ($page_type) {
            case 'quote_request':
                $sidebar_step_four = '<li><span class="count">4</span><span class="">Fill out form</span></li>';
                $sidebar_step_three_desc = "Select Product(s)";
                $page_title = 'Request Quote';
                $page_top_dec = '<p class="marg-bt-30 marg-tp-20">Follow the steps below to receive a quote from our sales team</p>';

                $breadcrumb_li = '<li itemprop="itemListElement"  itemscope=""
      itemtype="http://schema.org/ListItem"><a itemprop="item" href="/"><span itemprop="name">Home</span></a><meta itemprop="position" content="1" /></li>
                      <li itemprop="itemListElement"  itemscope=""
      itemtype="http://schema.org/ListItem"><span itemtitle="Request Quote" itemprop="name">Request Quote</span><meta itemprop="position" content="2" /></li>';

                $step4continue = '<div class="col-xs-12"><div class="marg-tp-10 row">
                <div class="col-xs-12 col-sm-9 marg-tp-10"><p class="marg-tp-20 small step2_topmessage"><b>For general request on ALL our <em class="step2cattitle">microplate readers</em> please skip this step and select request quote.</b></p></div><div class="col-xs-12 col-sm-3 marg-tp-10 step4continue text-right">
                        <a class="step4 gradiantBlueBtn btn">Request Quote</a>
                        </div></div></div>';
                $colmdoffset = '';
                $rfq_bottom_setproducts = "<div class='rfq_bottom_setproducts compare-strip' style='display:none;'></div>";
                break;
            default:
                $sidebar_step_three_desc = "Compare and select product";
                $page_title = 'Product Finder';
                $page_top_dec = $sidebar_step_four = '';
                $breadcrumb_li = '<li temprop="itemListElement"  itemscope=""
      itemtype="http://schema.org/ListItem"><a itemprop="item" href="/"><span itemprop="name">Home</span></a><meta itemprop="position" content="1" /></li>
                      <li class="active" itemprop="itemListElement"  itemscope=""
      itemtype="http://schema.org/ListItem"><span itemtitle="Product Finder" itemprop="name">Product Finder</span><meta itemprop="position" content="2" /></li>';
                $colmdoffset = 'col-md-offset-2';
                break;
        }
        $path = \Drupal::request()->getpathInfo();
        $arg = explode('/', $path);
        $fpage = $arg[1];
        if ($fpage == 'product-finder') {
            $fclass = "finderpage-class";
        } else {
            $fclass = "quotepage-content";
        }

        $productfinder_repres = '<div class="rfq_banner_text OneLinkHide"><h3>Talk to a Representative:<br>
										1 (877) 589-2214 (US/Canada)
										</h3></div>
										<div class="rfq_banner_text OneLinkTxShow_zh"><h3>Talk to a Representative: 400-821-3787
										</h3></div>
										<div class="rfq_banner_text OneLinkTxShow_de"><h3>Vertrieb (kostenlose Telefonnummer) : 00800 665 32860<br>
                                        Vertrieb : +44 118 944 8000
										</h3></div>
										<div class="rfq_banner_text OneLinkTxShow_fr"><h3>Ventes (téléphone gratuit) : 00800 665 32860<br>
                                        Ventes : +44 118 944 8000
										</h3></div>
										';
        $html = '';
        $html .= '<section>
	<div class="section-image short_banners white-text cover-bg xs-banner" style="background-image: url(\'/themes/moldev/images/bannerBg.jpg\'); margin-bottom: 0px;">
			<div class="container">
				<div class="verticaly-middle bannerInnerPages">
					<ol class="breadcrumb breadcrumbBanner marg-bt-10" itemscope="" itemtype="http://schema.org/BreadcrumbList">' . $breadcrumb_li . '</ol>
					<div class="row">
						<div class="col-md-8">
							<h1 class="hero-heading">' . $page_title . '</h1>
                            ' . $page_top_dec . '
						</div>
						<div class="col-md-4">
						' . $productfinder_repres . '	
						</div>
						
					</div>
				</div>
			</div>
	</div>

	<div class="product-finder-container quote-container finder-container molecules_bg" currentpage="' . $fpage . '">
		<div class="container">
			<div class="row">
				<div class="col-xs-12">
				  <!-- Nav tabs -->
					<!--<div class="row">

						<div class="col-xs-12 col-md-10 col-md-offset-2 text-center mobile_position">
						<ul class="nav nav-tabs" role="tablist">' . $steps_li . '</ul>
						</div>
					</div>-->

				  <!-- Tab panes -->
					<div class="tab-content ' . $fclass . '">
						<div role="tabpanel" class="tab-pane select-product fade in active" id="step1">
							<div class="row">' . $step_one_sidebar . '
								
								<div class="col-xs-12">
									<div class="category-container-holder">
										<div class="pfinder_tp_wrap">
											<div class="row">
												<div class="col-xs-12">
													<div class="tab-heading">
														<h4 class="">Select a Product Type</h4>
													</div>
												</div>
												<div class="col-xs-12">
													<div class="tab-heading side_bar_pro">
														<h6>Steps for ' . $page_title . ':</h6>
														<ul>
															<li><a class="count"></a> <span>Select Product type</span></li>
															<li><a class="count"></a> <span>Select Category</span></li>
															<li><a class="count"></a> <span>' . $sidebar_step_three_desc . ' </span></li>	
															' . $sidebar_step_four . '
														</ul>
													</div>
												</div>
											
											</div>
											
											
										</div>
										
										<div class="category-container">											
											<div class="row">
											<div class="rfq_row_center">
												<div class="col-xs-6 col-sm-6 col-md-3 rfq_ico">
													<a href="#step2" aria-controls="step2" role="tab" data-toggle="tab" class="step1" id="Instruments" title="Instruments" alt="1">
														<span class="icons-finder"><img src="/themes/moldev/images/finder-icons/instruments.png" alt="Instruments"></span>
														<span class="ico-title">Instruments</span>
													</a>
												</div>
												<div class="col-xs-6 col-sm-6 col-md-3 rfq_ico">
													<a href="#step2" aria-controls="step2" role="tab" data-toggle="tab" class="step1" id="Software" title="Software" alt="6">
														<span class="icons-finder"><img src="/themes/moldev/images/finder-icons/software.png" alt="Software"></span> 
														<span class="ico-title">Software</span>
													</a>
												</div>
												<div class="col-xs-6 col-sm-6 col-md-3 rfq_ico">
													<a href="#step2" aria-controls="step2" role="tab" data-toggle="tab" class="step1" id="Reagents / Media" title="Reagents / Media"  
													alt="2">
														<span class="icons-finder"><img src="/themes/moldev/images/finder-icons/assay_regent_media.png" alt="Assay Kits / Reagents and Media"></span>
														<span class="ico-title">Assay Kits / Reagents and Media</span> <!-- , Reagents & Media-->
													</a>
												</div>												
												<div class="col-xs-6 col-sm-6 col-md-3 rfq_ico">
													<a href="#step2" aria-controls="step2" role="tab" data-toggle="tab" class="step1" id="Accessories & Consumables" title="Accessories & Consumables" alt="4">
														<span class="icons-finder"><img src="/themes/moldev/images/finder-icons/labware.png" alt="Accessories & Consumables"></span>
														<span class="ico-title">Accessories & Consumables</span>
													</a>
												</div>
												</div>
												
											</div>
										</div>
									</div>
								</div>
							</div>
						</div> 
						<div role="tabpanel" class="tab-pane fade in" id="step2">
							<div class="pfinder_tp_wrap">
							<div class="row">
							     <div class="col-xs-12">
									<div class="tab-heading">
										<h4 class="text-center">Select <span class="selecthead"> <!-- coming by ajax --></span> Category</h4>
									</div>
								</div>
								<div class="col-xs-12">
									<div class="tab-heading side_bar_pro">
										<h6>Steps for ' . $page_title . ':</h6>
										<ul>
											<li><a href="#step1" aria-controls="step1" role="tab" data-toggle="tab" class="count checked_list"></a>  <span>Select Product type: <strong class="step1title" id="Instruments">Instruments</strong></span></li>
											<li><a class="count">2</a>  <span class="">Select Category </span></li>
											<li><a class="count">3</a>  <span class="">' . $sidebar_step_three_desc . ' </span></li>
											' . $sidebar_step_four . '
											<li class="marg-tp-10"><a href="#step1" aria-controls="step1" role="tab" data-toggle="tab" class="start-again" aria-expanded="true"><strong><span class="count"><i class="fa-arrow-left fa"></i></span> Start Over</strong></a></li>
										</ul>
									</div>
								</div>
								
							
								</div>
								<div class="col-xs-12">
									<div class="category-container-holder">
										
										<!--<p>1. Select Category   2. Select Options  3. Fill Out Form</p>-->
										<div class="category-container">
										<div class="row">
											<div class="rfq_row_center productfinder_subcat">
												
												<!-- Result of Product Subcategory by ajax.-->
												
											</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div role="tabpanel" class="tab-pane request-quote-container fade" id="step3">
							<div class="pfinder_tp_wrap clearfix">
							<div class="row">
							    <div class="col-xs-12">
									<div class="tab-heading">
										<h4 class="noMargtp text-center">Select Product</h4>
									</div>
								</div>
								<div class="col-xs-12">
									<div class="tab-heading side_bar_pro">
										<h6>Steps for ' . $page_title . ':</h6>
										<ul>
											<li><a href="#step1" aria-controls="step1" role="tab" data-toggle="tab" class="count checked_list"></a> <span>Select Product type: <strong class="step1title"></strong></span>   </li>
											<li><a href="#step2" aria-controls="step2" role="tab" data-toggle="tab" class="count checked_list"></a> <span class="">Select Category: <strong class="step2title"></strong></span>  </li>										
											<li><a class="count" aria-controls="step3"></a> <span class="">' . $sidebar_step_three_desc . ' <strong></strong></span> </li>
											' . $sidebar_step_four . '
											<li class="marg-tp-10"><a href="#step1" aria-controls="step1" role="tab" data-toggle="tab" class="start-again" aria-expanded="true"><strong><span class="count"><i class="fa-arrow-left fa"></i></span> Start Over</strong></a></li>
										</ul>
									</div>
									
								</div>
								' . $step4continue . '
								</div>
							</div>
							
							<div class="tiles-container">
									<div class="row">
										<div class="col-xs-12">
											<div class="row no_filter_data">
												<div class="col-xs-12 finder-filters">
													<div class="row">
														<div class="col-xs-12 col-md-4">
															<div class="tab-heading">
																<h4 class="text-left marg-tp-10-sm">Refine Products:</h4>
															</div>
														</div>
														<div class="col-xs-12 col-md-8">
															<div class="category-container-holder">
																<div class="filter_productcat">
																  <!-- filter code -->
																 <input type="hidden" name="pro_specifi_hidden_values[]" class="pro_specifi_hidden_values" value="" >
																 <div class="row result-filters">
																	<div class="form-group col-sm-4 col-xs-12">
																		<label class="readmode_title">Read Mode</label>
																		<select class="selectpicker customSelect form-control prodreadmode">
																		<!-- ajax response for readmode filter..-->
																		</select>
																	</div>
																	<div class="form-group col-sm-4 col-xs-12 finderprodtype">
																		<label class="prodtype_title">Featured Assays</label>
																		<select class="selectpicker customSelect form-control prodtypes" title="Please Select">
																		</select>
																	</div>
																	<div class="form-group col-sm-4 col-xs-12 finderprodspec">
																		<label>Features</label>
																		<select class="selectpicker customSelect form-control prodspecifi" name="specifeature" title="Please Select" multiple>
																		</select>
																	</div>
																</div>
															</div>
															
														</div>
													</div>
												</div>
											</div>
											</div>
										</div>
									</div>
								<span class="result-count"> </span>
								<div class="finder-scroll" id="overlay" style="position:fixed;z-index:999;top:0px;left:0px;width:100%;height:100%;display:none;"><img class="finder-loader" src="' . $base_url . '/sites/default/files/icons/ajax_common_loader_gray.gif" height="50" width="50"></div>
							<div class="row finder_row allproducts all-products">
							
                                <!-- Result of all products list by ajax -->								 
								 
							</div>
                            <div class="continuelink"></div>	
                           <div class="row marg-tp-20 continuelinkstep4">
                                <div class="col-xs-12step4continue text-right">
                                    <a class="step4 gradiantBlueBtn btn">Request Quote</a>
                                </div>
                            </div>	
						</div>
					</div>	
                    ' . $step_four . '	
				</div>
			</div>
		</div>
	</div>
	</section>';
        $html .= $rfq_bottom_setproducts;
        //return array('#markup' => t($html));
		return [		
			    '#markup' => t($html),		
			    '#attached' => array(		
			        'library' => array(		
			            'finder/finder',		
					),		
			    ),		
			];

    }

    public function productfinder_subcat($cat) {
        $html = '';
        if ($cat == '2') {
            $html .= '<div class="col-xs-6 col-sm-4 col-md-3  rfq_ico">
                            <a href="#step3" class="profinder_subcat step2" alt="21" aria-controls="step3" role="tab" data-toggle="tab"><span class="icons-finder"><img src="/themes/moldev/images/finder-icons/assay_regent_media.png" alt="Assay Kits"></span>Assay Kits</a>
                    </div>
                    <div class="col-xs-6 col-sm-4 col-md-3  rfq_ico">
                            <a href="#step3" class="profinder_subcat step2" alt="22" aria-controls="step3" role="tab" data-toggle="tab"><span class="icons-finder"><img src="/themes/moldev/images/finder-icons/assay_regent_media.png" alt="Reagents"></span>Reagents</a>
                    </div>
                    <div class="col-xs-6 col-sm-4 col-md-3  rfq_ico">
                            <a href="#step3" class="profinder_subcat step2" alt="681" aria-controls="step3" role="tab" data-toggle="tab"><span class="icons-finder"><img src="/themes/moldev/images/finder-icons/assay_regent_media.png" alt="Media"></span>Media</a>
                    </div>';
        } else {

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
                    $cat_image = \Drupal\image\Entity\ImageStyle::load('related_products')->buildUrl($subcat_detail->field_finder_icons->entity->getFileUri());
                    $html .= '<div class="col-xs-6 col-sm-4 col-md-3  rfq_ico">
                                <a href="#step3" class="profinder_subcat step2" alt="' . $subcat_tid . '" aria-controls="step3" role="tab" data-toggle="tab"><span class="icons-finder"><img src="' . $cat_image . '" alt="' . $subcat_detail->get('field_finder_icons')->alt . '"></span>' . $subcat_name . '</a>
                                </div>';
                }
            }
        }

        $result = array('status' => 'success', 'category' => $html);
        return new JsonResponse($result);
    }

    public function product_finder_readmode($parent) {
        $readmode_options = '';

        if ($parent == '12') {
            $readmode_options .= '<option value="' . $parent . '_0">Please Select</option>';
            $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('read_mode_types');
            foreach ($tree as $term) {
                $readmode_options .= '<option value="' . $parent . '_' . $term->tid . '">' . $term->name . '</option>';
            }
        } elseif ($parent == '24') {
            $readmode_options .= '<option value="' . $parent . '_0">Please Select</option>';
            $readmode_options .= '<option value="24_339_602">Amplifiers</option>';
            $readmode_options .= '<option value="24_340_621">Digitizers</option>';
        } else {
            $readmode_options .= '<option value="' . $parent . '_0">Please Select</option>';
            $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('product_groups', $parent);

            foreach ($tree as $term) {
                $getcat = \Drupal\taxonomy\Entity\Term::load($term->tid);
                $display_cat = $getcat->get('field_display_on_category')->value;
                if ($display_cat == '1') {
                    $readmode_options .= '<option value="' . $parent . '_' . $term->tid . '">' . $term->name . '</option>';
                }
            }
        }
        $subcat_detail = \Drupal\taxonomy\Entity\Term::load($parent);
        $field_product_family = \Drupal\taxonomy\Entity\Term::load($subcat_detail->get('field_product_family')->target_id);
        $product_family = $field_product_family->name->value;
        $field_line_of_busines = \Drupal\taxonomy\Entity\Term::load($subcat_detail->get('field_line_of_busines')->target_id);
        $line_of_busines = $field_line_of_busines->name->value;
        $sidebar_catname = $subcat_detail->name->value;

        if ($parent == '25') {
            $readmode_options = '';
        } else {
            $readmode_options = $readmode_options;
        }
        $result = array('status' => 'success', 'readmodefilter' => $readmode_options, 'sidebar_catname' => $sidebar_catname, 'product_family' => $product_family, 'line_of_busines' => $line_of_busines);
        return new JsonResponse($result);
    }

    public function productfinder_filtercat($parent, $readmode = '', $prodtype = '', $specifi = '', $firststep_cat = '') {
        global $base_url;
        $filterhtml = '';
		$filterjoins = '';
		$current_pid = '';
        $allprod = array();
        $prod_spec = array();

        
        if (!empty($_GET['specifiarr'])) {
			$value = $_GET['specifiarr'];
            $spec = explode(',', $value);
            foreach ($spec as $specc) {
                $speci[] = $specc;
            }
        } else {
            $speci = array();
        }

        if ($readmode == 0 && $parent == '12') {
            $childtree2 = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('product_groups', $parent);
            foreach ($childtree2 as $childtaxo2) {
                $allprod[] = $childtaxo2->tid;
            }
            $allprod_ids = join("', '", $allprod);
            $filtercond = "fc.field_category_target_id IN ('$allprod_ids')";
        }
        if (($readmode == 0 && $parent == '25') || ($readmode == 0 && $parent == '33' && ($firststep_cat == '1' || $firststep_cat == '4')) ||
                ($readmode == 0 && $parent == '24' && $firststep_cat == '6') || ($readmode == 0 && $parent == '545') || ($readmode == 0 && $parent == '24' && $firststep_cat == '4')) {
            $childtree2 = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('product_groups', $parent);
            foreach ($childtree2 as $childtaxo2) {
                $allprod[] = $childtaxo2->tid;
            }
            $allprod_ids = join("', '", $allprod);
            $filtercond = "fc.field_category_target_id IN ('$allprod_ids')";
        }

        if ($readmode == 0 && $parent == '24' && $firststep_cat == '1') {
            $filtercond = "fc.field_category_target_id IN ('339','340')";
        }

		if($parent=='12' && $readmode!='598' && $readmode!='0'){
			//$orderbyval = 'fc.field_category_target_id DESC, nfd.created ASC';
			$orderbyval = 'fc.field_category_target_id DESC, fw.field_weight_value ASC';
		}else{
			//$orderbyval = 'fc.field_category_target_id ASC, nfd.created ASC';
			$orderbyval = 'fc.field_category_target_id ASC, fw.field_weight_value ASC';
		}

        $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('product_groups', $parent, NULL, TRUE);
        if (!empty($tree)) {
            foreach ($tree as $term) {
                $getcat = \Drupal\taxonomy\Entity\Term::load($term->get('tid')->value);
                $allcat[] = $term->id();
            }
        }
        $cate_ids = join("', '", $allcat);

        if ($readmode != '0' && $prodtype == '0' && empty($speci)) {
            if ($parent == '12') {
                $filterjoins = 'left join node__field_product_read_mode_types as pr on pr.entity_id=nfd.nid';
                $filtercond .= "pr.field_product_read_mode_types_target_id='$readmode' and fc.field_category_target_id IN ('$cate_ids')";
            } else {
                $filtercond = "fc.field_category_target_id IN ('$readmode')";
            }
        } elseif ($prodtype != '0' && $readmode == '0' && empty($speci)) {

            if ($parent == '12') {
                $filterjoins = 'left join node__field_product_assay_kits as ak on ak.entity_id=nfd.nid';
                $filtercond = "ak.field_product_assay_kits_target_id='$prodtype'";
            }
        } elseif ($prodtype == '0' && $readmode == '0' && !empty($speci)) {

            if ($parent == '12') {
                $speci_ids = join("', '", $speci);

                $filterjoins = 'left join product_features as pf on pf.product_id=fc.entity_id';
                $filtercond = "(pf.feature_comparison!='0') and pf.feature_id IN ('$speci_ids') and fc.field_category_target_id IN ('$cate_ids')";
            }
        } elseif (($readmode != '0') && ($prodtype != '0') && empty($speci)) {
            if ($parent == '12') {
                $filterjoins = 'left join node__field_product_read_mode_types as pr on pr.entity_id=nfd.nid left join node__field_product_assay_kits as ak on ak.entity_id=nfd.nid';
                $filtercond .= "pr.field_product_read_mode_types_target_id='$readmode' and ak.field_product_assay_kits_target_id='$prodtype'";
            } elseif ($parent == '24') {
                $filterjoins = 'left join node__field_patch_clamp_selectors as pc on pc.entity_id=nfd.nid';
                $filtercond .= "fc.field_category_target_id IN ('$readmode')";
            } else {
                $filterjoins = 'inner join node__field_address_type as at on at.entity_id=fc.entity_id';
                $filtercond .= "fc.field_category_target_id IN ('$readmode') and ak.field_product_assay_kits_target_id='$prodtype'";
            }
        } elseif ($readmode != '0' && ($prodtype == 'undefined' || $prodtype == '0') && !empty($speci)) {

            $speci_ids = join("', '", $speci);

            $filterjoins = 'left join node__field_product_read_mode_types as pr on pr.entity_id=nfd.nid left join product_features as pf on pf.product_id=fc.entity_id';
            $filtercond .= "pr.field_product_read_mode_types_target_id='$readmode' and fc.field_category_target_id IN ('$cate_ids') and (pf.feature_comparison!='0') and pf.feature_id IN ('$speci_ids')";
        } elseif ($readmode == '0' && ($prodtype != '0') && !empty($speci)) {

            $speci_ids = join("', '", $speci);

            $filterjoins = 'left join node__field_product_assay_kits as ak on ak.entity_id=nfd.nid left join product_features as pf on pf.product_id=fc.entity_id';
            $filtercond .= " and ak.field_product_assay_kits_target_id='$prodtype' and (pf.feature_comparison!='0') and pf.feature_id IN ('$speci_ids')";
        } elseif (($readmode != '0') && ($prodtype != '0') && (!empty($speci))) {
            if ($parent == '24') {
                $speci_ids = join("', '", $speci);
                $filterjoins = 'inner join node__field_patch_clamp_selectors as pc on pc.entity_id=nfd.nid';
                $filtercond .= "fc.field_category_target_id IN ('$readmode') and pc.field_patch_clamp_selectors_target_id IN ('$speci_ids')";
            } else {
                $speci_ids = join("', '", $speci);
                $filterjoins = 'left join node__field_product_read_mode_types as pr on pr.entity_id=nfd.nid left join node__field_address_type as at on at.entity_id=fc.entity_id left join node__field_product_assay_kits as ak on ak.entity_id=nfd.nid inner join product_features as pf on pf.product_id=fc.entity_id';
                $filtercond .= "fc.field_category_target_id IN ('$cate_ids') and pr.field_product_read_mode_types_target_id='$readmode' and ak.field_product_assay_kits_target_id='$prodtype' and (pf.feature_comparison!='0') and pf.feature_id IN ('$speci_ids')";
            }
        }
        $current_pid = !empty($_GET['pid']) ? $_GET['pid'] : $current_pid;
        $field_is_series_product_value = "and sp.field_is_series_product_value = '0'";
        if (!empty($filtercond)) {
            $filtercond = $filtercond . ' AND';
        } elseif (is_numeric($current_pid) && !empty($current_pid)) {
            $filtercond = "";
            $child_tree_load = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('product_groups', $parent);
            if (empty($child_tree_load)) {
                $field_is_series_product_value = '';
                $filtercond = "fc.field_category_target_id IN ('$parent') AND";
            }
        } else {
            $filtercond = "";
        }

        $product_ids = db_query("select DISTINCT nfd.nid as nid from node_field_data as nfd 
		                         left join node__field_category as fc on fc.entity_id=nfd.nid
		                         inner join node__field_product_type as fpt on fpt.entity_id=nfd.nid
								 left join node__field_is_series_product as sp on sp.entity_id=nfd.nid
								 inner join node__field_show_in_product_finder as ipf on ipf.entity_id=nfd.nid	
                                 left join node__field_weight as fw on fw.entity_id=nfd.nid							 								 
								 " . $filterjoins . "
								 where " . $filtercond . " fpt.field_product_type_value='$firststep_cat' and ipf.field_show_in_product_finder_value='1' and nfd.status='1' " . $field_is_series_product_value . "
								 order by ".$orderbyval." "); //".$orderbyval." fw.field_weight_value ASC


        $product_ids->allowRowCount = TRUE;
        $countprod = $product_ids->rowCount();
        $prodcount = $countprod . ' Results';
        $allprodhtml = '';

        if ($countprod != 0) {
            foreach ($product_ids as $prodslist) {
                $nodeload = Node::load($prodslist->nid);
                if (!empty($nodeload)) {
                    if ($nodeload->get('field_select_series')->target_id != '') {
                        $serieslink = $nodeload->get('field_select_series')->target_id;
                    } else {
                        $serieslink = $nodeload->id();
                    }
                    $catid = $nodeload->get('field_category')->target_id;
                    $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $serieslink);

                    $product_image = \Drupal\image\Entity\ImageStyle::load('related_products')->buildUrl($nodeload->field_banner_image->entity->getFileUri());
					$product_image_alt = $nodeload->field_banner_image->getValue()[0]['alt'];
                    $body = trim_text($nodeload->get('field_product_summary')->value, $length = 100, $ellipses = true, $strip_html = true);

                    $url = Url::fromRoute('entity.node.canonical', ['node' => $nodeload->id()])->toString();

					$nid = $nodeload->id();
					$check_specs = db_query("SELECT * FROM `product_features` WHERE product_id='".$nid."'");
					$check_specs->allowRowCount = TRUE;
					$count_specs = $check_specs->rowCount();	
					
                    $title = $nodeload->getTitle();
                    if ($_REQUEST['currentpage'] == 'quote-request') {
                        $product_family = $line_of_busines = '';
                        if ($nodeload->get('field_select_series')->target_id != '') {
                            $serieslink = $nodeload->get('field_select_series')->target_id;
                            $seriesnodeload = Node::load($serieslink);
                            $title = $seriesnodeload->getTitle();
                            $catid = $seriesnodeload->get('field_category')->target_id;
                            $field_product_product_family = \Drupal\taxonomy\Entity\Term::load($seriesnodeload->get('field_product_product_family')->target_id);
                            $product_family = $field_product_product_family->name->value;
                            $field_product_line_of_bussiness = \Drupal\taxonomy\Entity\Term::load($seriesnodeload->get('field_product_line_of_bussiness')->target_id);
                            $line_of_busines = $field_product_line_of_bussiness->name->value;
                        } else {
                            $serieslink = $nodeload->id();
                            $field_product_product_family = \Drupal\taxonomy\Entity\Term::load($nodeload->get('field_product_product_family')->target_id);
                            $product_family = $field_product_product_family->name->value;
                            $field_product_line_of_bussiness = \Drupal\taxonomy\Entity\Term::load($nodeload->get('field_product_line_of_bussiness')->target_id);
                            $line_of_busines = $field_product_line_of_bussiness->name->value;
                        }

						//if ($count_specs != 0) {
                        $checkboxdata = '<span class="compare text-left">Request Quote<div class="filter-checkbox"><span><input type="checkbox" name="procomp" class="procampcheckbox rfq_selectedpid' . $nodeload->id() . '" productid="' . $nodeload->id() . '" id="' . $title . '" product_title="' . $title . '" value="' . $serieslink . '" alt="' . $catid . '" product_family="' . $product_family . '" line_of_busines="' . $line_of_busines . '"><span class="icheck"></span></span></div></span>';
                        $xtraclas = 'quote-requestpage';
						/*}else{
						$checkboxdata = '';		
						}*/
                    } else {
                        if ($firststep_cat == '1') { //1=instrument	
						    if ($count_specs != 0) {
                            $checkboxdata = '<span class="compare">Compare(<span class="comparecountno">0</span>)<div class="filter-checkbox">
                                <span><input type="checkbox" name="procomp[]" class="procamp procampcheckbox' . $nodeload->id() . '" id="' . $title . '" value="' . $nodeload->id() . '" alt="' . $catid . '"><span class="icheck"></span>
                                    </span></div></span>';
							}else{
							$checkboxdata = '';		
							}		
                        }
                        $xtraclas = 'product-finderpage';
                    }

                    $title = $nodeload->getTitle();
                    if (($nodeload->get('field_product_type')->value != '2') && ($nodeload->get('field_product_type')->value != '4')) { //2 for assay kits, 4=labware
                        $proimg = '<div class="prothumb"><img src="' . $product_image . '" alt="'.$product_image_alt.'"></div>';
                    } else {
                        $proimg = '';
                    }
					
					$node_created = $nodeload->getCreatedTime();
					if(strtotime('18 month ago') < ($node_created)){
						$batch = '<div class="batch">New</div>';
					}else{
						$batch = '';
					}
					
                    $allprodhtml .= '<div class="col-xs-6 col-sm-4 col-md-3 ">
									<div class="pro-container ' . $xtraclas . '"> '.$batch.'										
									' . $proimg . '
										<div class="pro-details">
											<div class="product-finder-desc">';

                    $allprodhtml .= '<a href="' . $alias . '" class="linkBtn" target="_blank">' . $title . '<span class="icon-icon_link"></span></a>';


                    $allprodhtml .= '<p>' . $body . '</p>	
											</div>
										</div>
										<div class="compare-box pFCompareBox">';
                    if ($_REQUEST['currentpage'] == 'quote-request') {
                        $allprodhtml .= '';
                    } else {
                        if ($nodeload->get('field_set_global_request_quote')->value != '1') {
                            $allprodhtml .= '<a target="_blank" href="' . $base_url . '/quote-request?pid=' . $serieslink . '" class="linkBtn">Request Quote<span class="icon-icon_link"></span></a>';
                        } else {
                            $allprodhtml .= '<a target="_blank" href="' . $base_url . '/quote-request" class="linkBtn">Request Quote<span class="icon-icon_link"></span></a>';
                        }
                    }

                    $allprodhtml .= $checkboxdata . '</div>
									</div>
							        </div>';
                }
            }
        } else {
            $allprodhtml .= '<div class="col-xs-12"><h3>No Products Found.</h3></div>';
        }
        $subcat_detail = \Drupal\taxonomy\Entity\Term::load($parent);
        $sidebar_catname = $subcat_detail->name->value;
        $result = array('status' => 'success', 'parent_filter' => $filterhtml, 'allproducts' => $allprodhtml, 'countprod' => $prodcount, 'sidebar_catname' => $sidebar_catname);
        return new JsonResponse($result);
    }

    /**
     * {@inheritdoc}
     */
    public function product_compare() {  //$allcompare
        $html = '';
        global $base_url;
        $arr2 = array();
        $cmpid = $_POST['proids'];
        if (!empty($cmpid)) {
            $nodeids_array = array();
            foreach ($cmpid as $probrk) {
                $result = \Drupal::database()->select('product_features', 'pf');
                $result->join('node__field_display_status', 'ds', 'ds.entity_id=pf.feature_id');
                $result->join('node_field_data', 'nfd', 'nfd.nid=pf.feature_id');
                $result->fields('pf', ['product_id', 'feature_id', 'feature_value', 'feature_comparison']);
                $result->condition('pf.product_id', $probrk);
                $result->condition('ds.field_display_status_value', 1);  //1 = product comparison	
                $result->condition('nfd.status', 1);
                $res = $result->execute()->fetchAll();
                if (!empty($res)) {
                    foreach ($res as $subpro) {
                        $featureids = \Drupal\node\Entity\Node::load($subpro->feature_id);
                        if (!empty($featureids)) {
                            $arr2[$featureids->getTitle()][$subpro->product_id]['checkbox'] = $subpro->feature_comparison;
                            $arr2[$featureids->getTitle()][$subpro->product_id]['text'] = $subpro->feature_value;
                        } else {
                            $arr2[$featureids->getTitle()][$subpro->product_id]['checkbox'] = '0';
                            $arr2[$featureids->getTitle()][$subpro->product_id]['text'] = '';
                        }
                        $nodeids_array[$subpro->product_id] = $subpro->product_id;
                    }
                }
            }

            $html .= '<div class="container">
		<div class="product-comparison">
			<div class="row">
			<div class="col-xs-12">
				<div class="section-heading text-center">
                <button type="button" class="btn btn-primary pull-right" id="take_print">Print</button>
					<h2>Product Comparison</h2>
					<a href="javascript:void(0)" class="img-ico img-ico-close emptycomparebox"><img src="' . $base_url . '/themes/moldev/images/close.png"></a>
				</div>
				<div class="comparison-table fixed-header-table">
					<a href="javascript:void(0)" class="img-ico img-ico-close"><img src="' . $base_url . '/themes/moldev/images/close.png"></a>
					<div class="comparison-row">
						<div class="row"> 
							<div class="col-xs-12 col-sm-3"></div>';
            foreach ($nodeids_array as $node_id) {
                $nodeids = \Drupal\node\Entity\Node::load($node_id);
                if ($nodeids->get('field_select_series')->target_id != '') {
                    $serieslink = $nodeids->get('field_select_series')->target_id;
                } else {
                    $serieslink = $nodeids->id();
                }
                $imageUrl = $nodeids->get('field_banner_image')->entity->uri->value;
                if ($imageUrl != '') {
                    $img2 = '<img src="' . file_create_url($imageUrl) . '" width="100%">';
                } else {
                    $img2 = '<img src="' . $base_url . '/sites/default/files/No-image-found.jpg" width="100%">';
                }
                $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $serieslink);

                $html .= '<div class="col-xs-6 col-sm-3">
										 <h4><a href="' . $alias . '">' . $nodeids->getTitle() . '</a></h4>
										 <a href="/quote-request?pid=' . $serieslink . '" class="linkBtn">Request a Quote<span class="icon-icon_link"></span></a>
										</div>';
            }
            $html .= '</div>
					</div>
				</div>
			</div>
			</div>
			<!--Header-->
			<div id ="comp-table-section" class="scroll_div">
				<div class="row">
					<div class="col-xs-12">
						<div class="comparison-table">
							<div class="comparison-row">
								<div class="row">
								<div class="col-xs-12 col-sm-3">
											<div class="comparison-cell title">
												<h3>Features</h3>
											</div>
										</div>';
            foreach ($nodeids_array as $node_id) {
                $nodeids = \Drupal\node\Entity\Node::load($node_id);
                $catid = $nodeids->get('field_category')->target_id;
                $imageUrl = $nodeids->get('field_banner_image')->entity->uri->value;
				$product_image_alt = $nodeids->field_banner_image->getValue()[0]['alt'];
                if ($imageUrl != '') {
                    $img2 = '<img src="' . file_create_url($imageUrl) . '" width="100%" alt="'.$product_image_alt.'">';
                } else {
                    $img2 = '<img src="' . $base_url . '/sites/default/files/No-image-found.jpg" width="100%">';
                }
                if ($nodeids->get('field_select_series')->target_id != '') {
                    $serieslink = $nodeids->get('field_select_series')->target_id;
                } else {
                    $serieslink = $nodeids->id();
                }
                $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $serieslink);
                $html .= '<div class="col-xs-6 col-sm-3">
							<div class="comparison-cell">
								<div class="pro-container">
									<a href="javascript:void(0)" id="' . $nodeids->id() . '" alt="' . $catid . '" class="img-ico img-ico-trash compare_pop_remove"><img src="' . $base_url . '/themes/moldev/images/trash.png"></a>
									' . $img2 . '
									<div class="pro-details">
										<h3>' . $nodeids->getTitle() . '</h3>
									</div>
									<div class="link_wrap">
										<a href="' . $alias . '" class="linkBtn">Details<span class="icon-icon_link"></span></a><br>
										<a href="/quote-request?pid=' . $serieslink . '" class="linkBtn">Request a Quote<span class="icon-icon_link"></span></a>
									</div>
								</div>
							</div>
						</div>';
            }
            $html .= '</div>
					      </div>';

            foreach ($arr2 as $productkey => $productval) {
                $bool_status = false;
                $prodid_arr = array();
                $prodid_arr = array_keys($productval);

                $arr_text = array_unique(array_column($productval, 'text'));
                $arr_chkbox = array_unique(array_column($productval, 'checkbox'));
                if (array_diff($arr_text, array("0", "", "-"))) {
                    $bool_status = true;
                }
                if (!$bool_status && in_array("1", $arr_chkbox)) {
                    $bool_status = true;
                }
                if (!$bool_status) {
                    continue;
                }
                $html .= '<div class="comparison-row">
                            <div class="row">
                                    <div class="col-xs-12 col-sm-3">
                                            <div class="comparison-cell">
                                                    <p><strong>' . $productkey . '</strong></p>
                                            </div>
                                    </div>';

                foreach ($nodeids_array as $node_id) {

                    $html .= '<div class="col-xs-6 col-sm-3"><div class="comparison-cell">';
                    if (isset($productval[$node_id]['checkbox']) && $productval[$node_id]['checkbox'] != '0') {
                        $html .= '<p><img src="' . $base_url . '/themes/moldev/images/done.png" height="30" width="30"></p>&nbsp;&nbsp;'; //&#10004;
                    }
                    if (isset($productval[$node_id]['text']) && $productval[$node_id]['text'] != '') {

                        if (($productval[$node_id]['text'] != '0') && ($productval[$node_id]['text'] != '-')) {
                            $html .= '<p>' . nl2br($productval[$node_id]['text']) . '</p>';
                        } else {
                            if (isset($productval[$node_id]['checkbox']) && $productval[$node_id]['checkbox'] != '0') {
                                
                            } else {
                                $html .= '<p><img src="' . $base_url . '/themes/moldev/images/horizontal-line.png" height="30" width="30"></p>';
                            }
                        }
                    }

                    $diff = array_diff($nodeids_array, $prodid_arr);
                    if (!empty($diff) && in_array($node_id, $diff)) {
                        $html .= '<p><img src="' . $base_url . '/themes/moldev/images/horizontal-line.png" height="30" width="30"></p>';
                    }

                    if (isset($productval[$node_id]) && ($productval[$node_id]['text'] == '') && ($productval[$node_id]['checkbox'] == '0')) {
                        $html .= '<p><img src="' . $base_url . '/themes/moldev/images/horizontal-line.png" height="30" width="30"></p>';
                    }

                    $html .= '</div></div>';
                }
                $html .= '</div>
								</div>
								';
            }
            $html .= '</div>
					</div>
				</div>
			</div>
		</div>
	</div>';
        }

        $result = array('status' => 'success', 'procompare' => $html);
        return new JsonResponse($result);
    }

    public function readmode_type($parent, $readmode, $prodtype = '') {

        if ($parent == '12') {
            $readmodetype = '<option value="' . $parent . '_0">Please Select</option>';
            $assay_childs = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('assays');
            foreach ($assay_childs as $assaychild) {
                if ($prodtype == $assaychild->tid) {
                    $selec = 'selected="selected"';
                } else {
                    $selec = '';
                }
                $readmodetype .= '<option value="' . $parent . '_' . $assaychild->tid . '" ' . $selec . '>' . $assaychild->name . '</option>';
            }
        } elseif ($parent == '24') {
            $selec = 'selected="selected"';
            if ($readmode == '339') {
                $readmodetype .= '<option value="' . $parent . '_602' . '" ' . $selec . '>Axon Applications</option>';
            } elseif ($readmode == '340') {
                $readmodetype .= '<option value="' . $parent . '_621' . '" ' . $selec . '>Noise Cancelation</option>';
            }
        } else {
            $readtype = \Drupal::database()->select('node__field_address_type', 'mr');
            $readtype->join('node__field_category', 'fc', 'fc.entity_id=mr.entity_id');
            $readtype->join('node_field_data', 'nfd', 'nfd.nid=mr.entity_id');
            $readtype->fields('mr', array('field_address_type_target_id'));
            $readtype->condition('fc.field_category_target_id', $readmode);
            $readtype->condition('nfd.status', '1');
            $readtype->groupBy('mr.field_address_type_target_id');
            $readmode_typelist = $readtype->execute()->fetchAll();

            if (!empty($readmode_typelist)) {
                $readmodetype = '<option value="' . $parent . '_0">Please Select</option>';
                foreach ($readmode_typelist as $modetypelist) {
                    $cat_name = \Drupal\taxonomy\Entity\Term::load($modetypelist->field_address_type_target_id);
                    if ($prodtype == $cat_name->tid->value) {
                        $selec = 'selected="selected"';
                    } else {
                        $selec = '';
                    }
                    $readmodetype .= '<option value="' . $parent . '_' . $cat_name->tid->value . '" ' . $selec . '>' . $cat_name->name->value . '</option>';
                }
            } else {
                $readmodetype = 'no_result';
            }
        }

        $subcat_detail = \Drupal\taxonomy\Entity\Term::load($parent);
        $sidebar_catname = $subcat_detail->name->value;
        $result = array('status' => 'success', 'readmodetype' => $readmodetype, 'sidebar_catname' => $sidebar_catname);
        return new JsonResponse($result);
    }

    public function readmode_specifi($parent, $readmode, $specifi) {
        $value = \Drupal::request()->request->get('specifiarr');
        $speci = array();
        if (!empty($value)) {
            $spec = explode(',', $value);
            foreach ($spec as $specc) {
                $speci[] = $specc;
            }
        }

        if ($parent == '24' && $readmode != '0') {
            $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('cns_product_selector', $readmode, NULL, TRUE);
            if (!empty($tree)) {
                $readmodespecifi = '';
                foreach ($tree as $term) {
                    $getcat = \Drupal\taxonomy\Entity\Term::load($term->get('tid')->value);
                    $readmodespecifi .= '<option value="' . $term->id() . '">' . $term->name->value . '</option>';
                }
            }
        } else {
            $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('product_groups', $parent, NULL, TRUE);
            if (!empty($tree)) {
                foreach ($tree as $term) {
                    $getcat = \Drupal\taxonomy\Entity\Term::load($term->get('tid')->value);
                    $allcat[] = $term->id();
                }
            }

            $query3 = \Drupal::database()->select('node__field_category', 'fc');
            $query3->leftjoin('product_features', 'pf', 'pf.product_id=fc.entity_id');
            $query3->leftjoin('node_field_data', 'nfd', 'nfd.nid=pf.feature_id');
            $query3->join('node__field_display_status', 'ds', 'ds.entity_id=pf.feature_id');
            $query3->fields('fc', array('entity_id'));
            $query3->fields('pf', array('feature_id'));
            $query3->condition('fc.field_category_target_id', $allcat, 'IN');
            $query3->condition('pf.feature_value', '-', '!=');
            $query3->condition('ds.field_display_status_value', 0, '='); // 0 for product finder
            $query3->condition('nfd.status', 1);
            $query3->orderBy('nfd.title', 'ASC');
            $parent_list = $query3->execute()->fetchAll();
            $readmodespecifi = '';
            if (!empty($parent_list)) {
                foreach ($parent_list as $pplist) {
                    $prod_spec[$pplist->feature_id] = $pplist->entity_id;
                }

                foreach ($prod_spec as $key => $val) {
                    $nodeload2 = Node::load($key);
                    if (!empty($nodeload2)) {
                        $spectitle = $nodeload2->getTitle();
                        if (is_array($speci) && in_array($nodeload2->id(), $speci)) {
                            $selec = 'selected="selected"';
                        } else {
                            $selec = '';
                        }
                        $readmodespecifi .= '<option value="' . $nodeload2->id() . '" ' . $selec . '>' . $spectitle . '</option>';
                    }
                }
            } else {
                $readmodespecifi = 'no_result';
            }
        }
        $subcat_detail = \Drupal\taxonomy\Entity\Term::load($parent);
        $sidebar_catname = $subcat_detail->name->value;
        $result = array('status' => 'success', 'readmodespecifi' => $readmodespecifi, 'sidebar_catname' => $sidebar_catname);
        return new JsonResponse($result);
    }

}
