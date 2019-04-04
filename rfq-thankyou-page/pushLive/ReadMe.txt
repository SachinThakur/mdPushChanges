Steps to Live RFQ Module:

1.) add new library(rfq-page) in /code/themes/moldev/moldev.libraries.yml file

2.) move md_quote folder's file in same old module folder.

3.) move css,js file in themes css,js folder.


4.) Move images to theme images folder, move  category-static-images 
    to /sites/default/files/styles/related_products/public/2019-02/

5.) -create two new fields(RFQ Icon,RFQ Hover Icon) in Product Group taxonomy
	- https://dev-web30.pantheonsite.io/admin/structure/taxonomy/manage/product_groups/overview/fields
	
6.) Remove product-finder.css from RFQ landing page
	- Go to finder.module and comment finder_page_attachments hook

7.) Tag category images in product_groups taxonomy.

---------------------------------------------------------------------------
-Changes in finder controller

	- replace :  get_rfq_multiproducts($products_id = NULL, $multiproduct = NULL)
	- By 	  :  get_rfq_multiproducts($products_id = NULL, $multiproduct = NULL, $pagetype = NULL) 
	
	
	- add new case after case 'multiproduct' 
		
		case 'quote-request-v2':		
			if ($products_id != '') {       		
				if (is_numeric($products_id)) {		
						   $productused_data[]->field_customer_product_target_id = $products_id;		
				}				
			}		
		break;
		
		
	- after  global $base_url;  add below line
		$pagetype = !empty($_POST['pagetype']) ? $_POST['pagetype'] : $pagetype;
	
	
	- add below code after   $all_Primary_Application =  implode(",",$products_data['Primary_Application']);
			if ($pagetype == 'rfq'){						
				$pardoturl = 'https://go.moleculardevices.com/l/83942/2018-12-06/bdjrhw';		
			}elseif($pagetype == 'quote-request'){ //quote-request-v2		
				$pardoturl = 'https://go.moleculardevices.com/l/83942/2019-02-18/bdx3bc';		
			}else{		
				$pardoturl = 'https://go.moleculardevices.com/l/83942/2018-03-24/9tfrr9';			
			}

			$iframeurl = $pardoturl.'?Line_of_Business='.$all_Line_of_Business.'&Product_Family='.$first_product_family.'&product_selection='.$all_Product_Family.'&Primary_Application='.$all_Primary_Application.'&Return_URL='. $Return_URL;
			
	- add below code after $html .= $rfq_bottom_setproducts;
	
		//return array('#markup' => t($html));
		return [		
			    '#markup' => t($html),		
			    '#attached' => array(		
			        'library' => array(		
			            'finder/finder',		
					),		
			    ),		
			];
			
	-
		
	
	
