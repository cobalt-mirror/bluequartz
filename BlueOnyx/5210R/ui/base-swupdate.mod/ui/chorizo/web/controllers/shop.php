<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Shop extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /swupdate/shop.
     *
     */

    public function index() {

        $CI =& get_instance();
        
        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Need to load 'BxPage' for page rendering:
        $this->load->library('BxPage');

        // Get $CI->BX_SESSION['sessionId'] and $CI->BX_SESSION['loginName'] from Cookie (if they are set):
        $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
        $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');
        
        // Line up the ducks for CCE-Connection:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();
        $user = $CI->BX_SESSION['loginUser'];
        $i18n = new I18n("base-shop", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

        // We start without any active errors:
        $errors = array();
        $ci_errors = array();
        $my_errors = array();

        $extra_headers = array();

        // Shove submitted input into $form_data after passing it through the XSS filter:
        $form_data = $CI->input->post(NULL, TRUE);
        $get_data = $CI->input->get(NULL, TRUE);

        // -- Actual page logic start:

        // Not 'managePackage'? Bye, bye!
        if (!$Capabilities->getAllowed('managePackage')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }
        else {

            // Get CODB-Object Shop:
            $shopObj = $CI->cceClient->getObject("Shop", array(), "");
            $api_url = $shopObj['shop_url'];
            $cat_from_codb = $shopObj['shop_category'];

            // Get Serial:
            $serialNumber = $system['serialNumber'];

            if (strlen($serialNumber) == 0) {
                $url_ext = '';
            }
            else {
                $url_ext = $serialNumber;
            }

            // Location (URLs) of the various NewLinQ query resources:
            $bluelinq_server    = 'newlinq.blueonyx.it';
            $shoplist_url       = "http://$bluelinq_server/showshops/$url_ext";
            $categories_url     = "http://$bluelinq_server/showcategories/$url_ext";
            $products_url       = "http://$bluelinq_server/showproducts/$url_ext";
            $catprod_url        = "http://$bluelinq_server/showcatprod/$url_ext";

            // Check if we are online:
            if (areWeOnline($shoplist_url, "4")) {

              // Poll NewLinQ about our status:
              $snstatus = "RED";
              $snstatus = get_data("http://$bluelinq_server/snstatus/$serialNumber");
              if (!$snstatus === "RED") {
                 $string = $i18n->interpolateHtml("[[status-sn$snstatus]]");
              }
              else {
                if ($snstatus === "ORANGE") {
                    $string = $i18n->interpolateHtml("[[status-sn$snstatus]]");
                    $snstatusx = get_data("http://$bluelinq_server/snchange/$serialNumber");
                } 
                else {
                    $ipstatus = get_data("http://$bluelinq_server/ipstatus/$serialNumber");
                    $string = $i18n->interpolateHtml("[[status-ip$ipstatus]]");
                    if ( $ipstatus === "ORANGE" ) {
                        $string = $i18n->interpolateHtml("[[status-ip$ipstatus]]");
                        $ipstatusx = get_data("http://$bluelinq_server/ipchange/$serialNumber");
                    }
                }
              }
              // Are we online and in the green?
              if ($snstatus == "GREEN") {
                  $online = "1";
              }
            }
            else {
                // Not online, poll of 'newlinq.blueonyx.it' failed. Show error message and good bye:
                $online = "0";
                $errors[] = '<div class="alert alert_light"><img width="40" height="36" src="/.adm/images/icons/small/white/alert_2.png"><strong>' . $i18n->getHtml("[[base-yum.ErrorMSGdesc]]") . '</strong></div>';

                // Prepare Page:
                $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-yum", "/swupdate/shop");
                $BxPage = $factory->getPage();
                $i18n = $factory->getI18n();

                // Set Menu items:              
                $BxPage->setVerticalMenu('base_software');
                $page_module = 'base_software';

                $page_body[] = addInputForm(
                                                $i18n->get("[[base-shop.ShopSelector_General_head]]", false, array()), 
                                                array("toggle" => "#"),
                                                '               <div class="flat_area grid_16">
                                                                    <h2>' . $i18n->getHtml("[[base-shop.ErrorMSGNoProducts]]", false) . '</h2>
                                                                    <p>' . $i18n->getHtml('[[base-shop.ErrorNoProductsInCategory]]') . '</p>
                                                                </div>',
                                                "",
                                                $i18n,
                                                $BxPage,
                                                $errors
                                            );
                // Out with the page:
                $BxPage->render($page_module, $page_body);
                return;
            }

            // Well, we're at least online. So let's continue:
            if (($snstatus === "RED") || ($snstatus === "ORANGE") || ($snstatus === "GREEN")) {

                // Prepare Page:
                $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-shop", "/swupdate/shop");
                $BxPage = $factory->getPage();
                $i18n = $factory->getI18n();

                // Set Menu items:
                $BxPage->setVerticalMenu('base_software');
                $page_module = 'base_software';

                // Extra JavaScript to handle CAT_SELECTOR:
                $BxPage->setExtraHeaders('
                    <SCRIPT LANGUAGE="javascript">
                    // Javascript
                    // Javascript function to go to a new page defined by a SELECT element
                    function goToPage( id ) {
                      var node = document.getElementById( id );
                      // Check to see if valid node and if node is a SELECT form control
                      if( node &&
                        node.tagName == "SELECT" ) {
                        // Go to web page defined by the VALUE attribute of the OPTION element
                        window.location.href = node.options[node.selectedIndex].value;
                      } // endif
                    }
                    </SCRIPT>');

                $BxPage->setExtraHeaders('
                        <script>
                            $(document).ready(function() {
                                $(".various").fancybox({
                                    overlayColor: "#000",
                                    fitToView   : false,
                                    width       : "80%",
                                    height      : "80%",
                                    autoSize    : false,
                                    closeClick  : false,
                                    openEffect  : "none",
                                    closeEffect : "none"
                                });
                            });
                        </script>');

                $start_time = time();

                // Process the Shoplist:
                $output = get_data($shoplist_url);
                $output = preg_replace('/"/', '', $output);
                $arr_shoplist = explode("\n", $output);
                $numshop = "0";

                // Legend:
                // 0 = shop_id    (numerical shop ID)
                // 1 = shop_url   (URL)
                // 2 = shop_cur   (shop currency)

                foreach ($arr_shoplist as $items) {
                    $item = explode(",", $items);
                    if (isset($item[0])) {
                        $shop_id[] = $item[0];
                        // Start: Small work around for wrong NewLinQ response on shop URLs
                        if ($item[1] == "shop.solarspeed.net") {
                              $new_item = "www.solarspeed.net";
                              $shop_url[] = $new_item;
                        }
                        elseif ($item[1] == "www2.compassnetworks.com.au") {
                              $new_item = "www.compassnetworks.com.au";
                              $shop_url[] = $new_item;
                        }
                        else {
                             $shop_url[] = $item[1];
                        }
                        // End: Small work around for wrong NewLinQ response on shop URLs
                        $shop_cur[] = $item[2];
                        $numshop++;
                    }
                }

                // Process the Categories:
                $output = get_data($categories_url);
                $output = preg_replace('/"/', '', $output);
                $arr_catlist = explode("\n", $output);
                $categories = array();

                foreach ($arr_catlist as $items) {
                    $item = explode(",", $items);
                    if (isset($item[1])) {
                        // For now we ignore the empty platform specific categories that are just there for historic reasons:
                        if (($item[1] != "blueonyx/5106r") && ($item[1] != "blueonyx/5107r") && ($item[1] != "blueonyx/5108r")) {
                             $categories[$item[0]] = $item[1];
                        }
                    }
                }

                // Process the Products:
                $output = get_data($products_url);

                // The parsed CSV of the product list has each product end with a quotation mark followed by a newline.
                // So this is where we split the products:
                $arr_prodlist = preg_split('/"\n/', $output, -1, PREG_SPLIT_NO_EMPTY);

                $products = array();
                foreach ($arr_prodlist as $key => $items) {
                    $item = explode(",", $items);
                    // Legend:
                    // 0 = product_id
                    // 1 = product_name
                    // 2 = product_url
                    // 3 = product_img
                    // 4 = category
                    // 5 = product_desc
                    $index = preg_replace('/"/', '', $item[0]);
                    $products[$index]["product_id"] = preg_replace('/"/', '', $item[0]);
                    $products[$index]["product_name"] = preg_replace('/"/', '', $item[1]);
                    $products[$index]["product_url"] = preg_replace('/"/', '', $item[2]);
                    $products[$index]["product_img"] = preg_replace('/"/', '', $item[3]);
                    $products[$index]["category"] = "n/a";  // We set this to a default early on and sort it further below.
                    // Element $item[4] contains a leading double quotation mark, which we need to remove:
                    $item[4] = preg_replace('/"/', '', $item[4]);
                    // Now it gets messy. As we split $item at the ',' and in the product description we also have them for sure.
                    // So the descriptions are probably split up as well. We first remove the four known items via unset() and then
                    // impode() the rest back together to get the full description again:
                    unset($item[0]);
                    unset($item[1]);
                    unset($item[2]);
                    unset($item[3]);
                    // Assemble the product description again:
                    $product_desc = implode(",", $item);
                    //
                    // Clean up some translational issues:
                    //
                    // Remove newlines:
                    $product_desc = preg_replace("/[\n\r]/", '', $product_desc);
                    // Replace &#34; with ":
                    $product_desc = preg_replace('/&#34;/', '"', $product_desc);
                    // Replace \N - And the joys of UTF-8: We have to triple-escape the slash:
                    $product_desc = preg_replace("/\\\\N/", '', $product_desc);
                    // Need to replace this:
                    // <span style="font-family: verdana,arial,helvetica,sans-serif; font-size: x-small;">
                    $product_desc = preg_replace('/<span style="(.*)">/i', '', $product_desc);
                    $product_desc = preg_replace('/<\/span>/i', '', $product_desc);
                    // Need to remove links in the description:
                    // <a href="http://www.group-office.com/">GroupOffice</a>
                    $product_desc = preg_replace('/<a href="(.*)">/i', '', $product_desc);
                    $product_desc = preg_replace('/<\/a>/i', '', $product_desc);

                    // Finally, we have a cleaned up product description:
                    $products[$index]["product_desc"] = $product_desc;
                }

                // Process the Catprod and map the products to their parent categories:
                $output = get_data($catprod_url);
                $output = preg_replace('/"/', '', $output);
                $arr_catprods = explode("\n", $output);
                foreach ($arr_catprods as $items) {
                    $item = explode(",", $items);
                    if (isset($categories[$item[0]])) {
                        $products[$item[1]]["category"] = $categories[$item[0]];
                    }
                }
            }
        }
        $end_time = time();

        // Do we have form data?
        if (isset($form_data["SHOP_SELECTOR"])) {
            // If so, set the shop selector to the submitted form data:
            $needle = $form_data["SHOP_SELECTOR"];
            $api_url = $shop_url[$needle];
        }

        if (isset($get_data["CAT_SELECTOR"])) {
            // If so, set the category selector to the submitted form data:
            $needle = $get_data["CAT_SELECTOR"];
            $cat = $needle;
        }

        if ((isset($form_data["SHOP_SELECTOR"])) || (isset($get_data["CAT_SELECTOR"]))) {
            if (isset($get_data["CAT_SELECTOR"])) {
                $cat = $get_data["CAT_SELECTOR"];
            }

            if (isset($form_data["CAT_SELECTOR"])) {
                $long_cat = preg_split('/=/', $form_data["CAT_SELECTOR"]);
                $cat = $long_cat[1];
            }

            // Join the various error messages:
            $errors = array_merge($ci_errors, $my_errors);

            // If we have no errors and have POST data, we submit to CODB:
            if (((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) || ((count($errors) == "0") && ($CI->input->get(NULL, TRUE)))) {
                if (isset($form_data["SHOP_SELECTOR"])) {
                    $CI->cceClient->setObject("Shop", 
                                                    array(
                                                        "shop_url" => $api_url, 
                                                        "update" => time()
                                                    ), 
                                            "");
                }
                if (isset($cat)) {
                    $CI->cceClient->setObject("Shop", 
                                                    array(
                                                        "shop_category" => $cat, 
                                                        "update" => time()
                                                    ), 
                                            "");
                }
                // CCE errors that might have happened during submit to CODB:
                $errors = $CI->cceClient->errors();
            }
        }
        else {
            // Set current category to the last one the user visited (as stored in CODB):
            $cat = $cat_from_codb;
        }

        //
        //### Shop Selector:
        //

        // Selector for Categories:
        //
        // And yes, this is dirty, but the only other way around this would require chanegs to UIFC again:

        $Shop_Label_CatSelector = $i18n->getHtml('[[base-shop.CAT_SELECTOR]]');

        if (is_array($categories)) {
            array_multisort($categories, SORT_STRING, SORT_ASC);
        }

        // Fallback:
        $extra_page_body = "";
        if (!$cat) { $cat = "1"; }

        // Assemble pulldown HTML code on foot:
        $extra_page_body .= "\n" . '                                 <fieldset class="label_side top">
                                        <label for="CAT_SELECTOR" title="' . $i18n->getHtml("[[base-shop.CAT_SELECTOR_help]]", false) . '" class="tooltip left"> ' . $Shop_Label_CatSelector . '</label>
                                        <div class="clearfix">' . "<select name=\"CAT_SELECTOR\" id=\"CAT_SELECTOR\" onchange=\"goToPage('CAT_SELECTOR')\">";
        foreach ($categories as $cats) {
            if ($cats == $cat) { 
              $selected = " selected=\"selected\"";
            }
            else {
              $selected = "";
            }
            $extra_page_body .= "<option $selected value=\"/swupdate/shop/?&CAT_SELECTOR=" . urlencode($cats) . "\">$cats</option>";
        }
        $extra_page_body .= "\n" . "</select></p>";
        $extra_page_body .= "\n" . '</p></div></fieldset>';

        //-- Generate page:

        // Show shop and category selector block:
        $page_body[] = addInputForm(
                                        $i18n->get("[[base-shop.ShopSelector_General_head]]", false, array()), 
                                        array("toggle" => "#"),
                                        '               <div class="flat_area grid_16">
                                                            <h2>' . $i18n->getHtml("[[base-shop.ShopSelector_General_configuration]]", false) . '</h2>
                                                            <p>' . $i18n->getHtml('[[base-shop.ShopSelector_Info_Text]]') . '</p>
                                                        </div>' .

                                        addPullDown("SHOP_SELECTOR", $shop_url, $api_url, "base-shop", $i18n) . 
                                        $extra_page_body,
                                        addSaveButton($i18n),
                                        $i18n,
                                        $BxPage,
                                        $errors
                                    );

        //
        //### Show Shop Products:
        //

        $ProductsTable = array();
        foreach ($categories as $cats) {
            foreach ($products as $key => $product) {
                if ($cats == $product["category"]) {
                    $cat_product[$cats][] = $product;
                }
            }

            // Count number of Products in this category:
            if (isset($cat_product[$cats])) {
                $num_prods = count($cat_product[$cats]);
            }
            else {
                $num_prods = "0";
            }

            if ($cat == $cats) {
                if ($num_prods > "0") {
                    foreach ($cat_product[$cats] as $product) {
                        // Populate the scroll list rows:
                        if (is_https() == TRUE) {
                            $proto = 'http://'; // We will change this to HTTPS later on.
                        }
                        else {
                            $proto = 'http://';
                        }
                        if (isset($product["product_id"]) && isset($product["category"]) && isset($product["product_name"]) && isset($product["product_url"]) && isset($product["product_img"])) {
                          $image = $proto . $api_url . '/get.php/media/catalog/product' . $product["product_img"];
                          $url_product = $proto . $api_url . '/index.php/' . $product["category"] . '/' . $product["product_url"];
                          $out_img = "<a class=\"various\" target=\"_blank\" href=\"$url_product\" data-fancybox-type=\"iframe\"><img src=\"$image\" width=\"150\" height=\"150\" align=\"left\"></a>";
                          $out_prod = "<H3>" . $product["product_name"] . "</H3>" . $product["product_desc"];
                          $product_output = "<table width=\"100%\" border=\"1\" cellspacing=\"2\" cellpadding=\"2\">\n  <tr>\n    <td>\n    <p align=\"left\">\n      <table width=\"180\" border=\"0\" align=\"left\" cellpadding=\"5\" cellspacing=\"5\">\n         <tr>\n        <td>\n        $out_img\n        </td>\n       </tr>\n       </table>\n    $out_prod\n   </p>\n    </td>\n  </tr>\n</table>\n";

                          $product_url_button = '<a class="various" target="_blank" href="' . $url_product . '" data-fancybox-type="iframe"><button class="fancybox tiny icon_only img_icon tooltip hover" title="' . $i18n->getWrapped('[[base-shop.openURL_help]]') . '"><img src="/.adm/images/icons/small/white/magnifying_glass.png"></button></a>';

                          $ProductsTable[0][] = $product_output;
                          $ProductsTable[1][] = $product_url_button;

                        }
                    } // Foreach cat_product
                } // if num_prods
            } //if cats
        }

        if (!isset($ProductsTable[0])) {
            $page_body[] = '                <div class="flat_area grid_16">
                                                    <h2>' . $i18n->getHtml("[[base-shop.ErrorMSGNoProducts]]", false) . '</h2>
                                                    <p>' . $i18n->getHtml('[[base-shop.ErrorNoProductsInCategory]]') . '</p>
                                                </div>';
        }
        else {      

            // For debugging of the loading time of the cURL requests:
            //$page_body[] = '<pre>Load time: ' . $end_time . ' vs ' . $start_time . "</pre>";

            $scrollList = $factory->getScrollList("products", array("product_name", "openURL"), $ProductsTable); 
            $scrollList->setAlignments(array("left", "center"));
            $scrollList->setDefaultSortedIndex('0');
            $scrollList->setSortOrder('ascending');
            $scrollList->setSortDisabled(array('1'));
            $scrollList->setPaginateDisabled(FALSE);
            $scrollList->setSearchDisabled(FALSE);
            $scrollList->setSelectorDisabled(FALSE);
            $scrollList->enableAutoWidth(FALSE);
            $scrollList->setInfoDisabled(FALSE);
            $scrollList->setColumnWidths(array("98%", "35"));

            $page_body[] = $scrollList->toHtml();
        }

        // Out with the page:
        $BxPage->render($page_module, $page_body);

    }
}
/*
Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.

*/
?>