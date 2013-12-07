<?php

// Author: Michael Stauber
// Copyright 2006-2013, SOLARSPEED.NET. All rights reserved.
// Copyright 2008-2013, Team BlueOnyx. All rights reserved.

include_once("ServerScriptHelper.php");
include_once("uifc/ImageButton.php");
$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-swupdate");

// Only 'managePackage' should be here:
if (!$serverScriptHelper->getAllowed('managePackage')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-shop", "/base/swupdate/shop/switcher.php");
$i18n = $serverScriptHelper->getI18n("base-shop");

// Get CODB-Object Shop:
$shopObj = $cceClient->getObject("Shop", array(), "");
$api_url = $shopObj['shop_url'];
$cat_from_codb = $shopObj['shop_category'];

// Handle 'Entries' selector:
$_ScrollList_pageIndex_2=$id;

// Extra JavaScript to handle CAT_SELECTOR:
$javascript = "<SCRIPT LANGUAGE=\"javascript\">
// Javascript
// Javascript function to go to a new page defined by a SELECT element
function goToPage( id ) {
  var node = document.getElementById( id );
  // Check to see if valid node and if node is a SELECT form control
  if( node &&
    node.tagName == \"SELECT\" ) {
    // Go to web page defined by the VALUE attribute of the OPTION element
    window.location.href = node.options[node.selectedIndex].value;
  } // endif
}
</SCRIPT>";

// Start Output generation:
$page = $factory->getPage();
print($page->toHeaderHtml());
echo $javascript . "\n";

// Location (URLs) of the various NewLinQ query resources:
$bluelinq_server	= 'newlinq.blueonyx.it';
$shoplist_url 		= "http://$bluelinq_server/showshops/";
$categories_url 	= "http://$bluelinq_server/showcategories/";
$products_url 		= "http://$bluelinq_server/showproducts/";
$catprod_url 		= "http://$bluelinq_server/showcatprod/";

// This kicks in if we're called frim inside the Wizard:
if ($reg == "true") {
  echo "<H1>" . $i18n->get('[[base-shop.thx_bxUsage]]') . "</H1>";
  echo "<H3>" . $i18n->get('[[base-shop.bx_ffa]]') . "<br>" . $i18n->get('[[base-shop.bx_comBlurb]]') . "</H3>";
}

// Check if we are online:
if (areWeOnline($shoplist_url)) {

  // Get Serial:
  $SystemObj = $cceClient->getObject("System", array(), "");
  $serialNumber = $SystemObj['serialNumber'];

  // Poll NewLinQ about our status:
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
  $online = "1";
}
else {
    $online = "0";
}

if ($online == "1") {
  // Process the Shoplist:
  $ch = curl_init($shoplist_url);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'BlueLinQ/1.0');
  $output = curl_exec($ch);
  curl_close($ch);
  $output = preg_replace('/"/', '', $output);
  $arr_shoplist = explode("\n", $output);
  $numshop = "0";

    // Legend:
    // 0 = shop_id    (numerical shop ID)
    // 1 = shop_url   (URL)
    // 2 = shop_cur   (shop currency)

  foreach ($arr_shoplist as $items) {
  	$item = explode(",", $items);
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

  // Process the Categories:
  $ch = curl_init($categories_url);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'BlueLinQ/1.0');
  $output = curl_exec($ch);
  curl_close($ch);
  $output = preg_replace('/"/', '', $output);
  $arr_catlist = explode("\n", $output);
  $categories = array();

  foreach ($arr_catlist as $items) {
  	$item = explode(",", $items);
    // For now we ignore the empty platform specific categories that are just there for historic reasons:
    if (($item[1] != "blueonyx/5106r") && ($item[1] != "blueonyx/5107r") && ($item[1] != "blueonyx/5108r")) {
  	 $categories[$item[0]] = $item[1];
    }
  }

  // Process the Products:
  $ch = curl_init($products_url);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'BlueLinQ/1.0');
  $output = curl_exec($ch);
  curl_close($ch);

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
  	$products[$index]["category"] = "n/a";	// We set this to a default early on and sort it further below.
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
  $ch = curl_init($catprod_url);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'BlueLinQ/1.0');
  $output = curl_exec($ch);
  curl_close($ch);
  $output = preg_replace('/"/', '', $output);
  $arr_catprods = explode("\n", $output);

  foreach ($arr_catprods as $items) {
  	$item = explode(",", $items);
  	$products[$item[1]]["category"] = $categories[$item[0]];
  }

  // Show shop selector block:
  $block = $factory->getPagedBlock("ShopSelector_General_head");
  $block->processErrors($serverScriptHelper->getErrors());

  $block->addDivider($factory->getLabel("ShopSelector_General_configuration", false), "");

  $Shop_Info_Text = $i18n->get('[[base-shop.ShopSelector_Info_Text]]');

  $block->addFormField(
      $factory->getTextList("ShopSelector_Info", $Shop_Info_Text, 'r'),
      $factory->getLabel("ShopSelector_Info"),
      ""
  );

  //
  //### Shop Selector:
  //
  
  //array_multisort($products[4], SORT_STRING, SORT_ASC);
  array_multisort($categories, SORT_STRING, SORT_ASC);

  // Set current category to the last one the user visited (as stored in CODB):
  $cat = $cat_from_codb;
  // Fallback:
  if (!$cat) { $cat = "blueonyx/web-applications"; }

  $SHOP_SELECTOR_select = $factory->getMultiChoice("SHOP_SELECTOR",array_values($shop_url));
  $SHOP_SELECTOR_select->setSelected($api_url, true);
  $block->addFormField($SHOP_SELECTOR_select,$factory->getLabel("SHOP_SELECTOR"), "");
  $block->addButton($factory->getSaveButton($page->getSubmitAction()));
  print($block->toHtml());

  // Selector for Categories:
  //
  // And yes, this is dirty, but the only other way around this would require changes to UIFC again:
  $Shop_Label_CatSelector = $i18n->get('[[base-shop.CAT_SELECTOR]]');
  echo "<p><label for=\"CAT_SELECTOR\"><H3>$Shop_Label_CatSelector</H3></label><select id=\"CAT_SELECTOR\" onchange=\"goToPage('CAT_SELECTOR')\">";
  foreach ($categories as $cats) {
  	if ($cats == $cat) { 
  	  $selected = " selected=\"selected\"";
  	}
  	else {
  	  $selected = "";
  	}
  	echo "<option $selected value=\"/base/swupdate/shop/switcher.php?&CAT_SELECTOR=" . urlencode($cats) . "\">$cats</option>";
  }
  echo "</select></p>";

  // General parameters for the scroll list:
  $scrollList = $factory->getScrollList("[[base-shop.ShopTitle]]", array("title", "desc", "date", "internal", "link"));
  $scrollList->setAlignments(array("left", "left", "center", "center", "center"));
  $scrollList->setSortEnabled(false);
  $scrollList->setColumnWidths(array("250", "*", "120", "25", "25"));
  $scrollList->setWidth("100%");

  //
  //### Show Shop Products:
  //

  foreach ($categories as $cats) {
  	foreach ($products as $key => $product) {
  	    if ($cats == $product["category"]) {
  		    $cat_product[$cats][] = $product;
  	    }
  	}
  }

  foreach ($categories as $cats) {

    // Count number of Products in this category:
    $num_prods = count($cat_product[$cats]);

    if ($cat == $cats) {

    	if ($num_prods > "0") {

    	  // General parameters for the scroll list:
    	  $ProductsTable[$cats] = $factory->getScrollList($cats, array("product_name", ""));
    	  $ProductsTable[$cats]->setAlignments(array("left", "center"));
    	  $ProductsTable[$cats]->setSortEnabled(true);
    	  $ProductsTable[$cats]->setDefaultSortedIndex(0);
    	  $ProductsTable[$cats]->setColumnWidths(array("575", "25"));
    	  $ProductsTable[$cats]->setWidth("100%");

    	  foreach ($cat_product[$cats] as $product) {
    	      // Populate the scroll list rows:
    	      if ($product["product_id"] && $product["category"] && $product["product_name"] && $product["product_url"] && $product["product_img"]) {
        		  $image = 'http://' . $api_url . '/get.php/media/catalog/product' . $product["product_img"];

        		  // Create the image link button for the external news article URL:
        		  $url_product = 'http://' . $api_url . '/index.php/' . $product["category"] . '/' . $product["product_url"];
        		  $linkExternal = new ImageButton($page, $url_product, "/libImage/BlueOnyx/MerlotInspectActive.gif", "openPdf", "openURL_help");
        		  $linkExternal->setTarget('_self');

        		  $out_img = "<a href=\"$url_product\"><img src=\"$image\" width=\"150\" height=\"150\" align=\"left\"></a>";
        		  $out_prod = "<H3>" . $product["product_name"] . "</H3>" . $product["product_desc"];
        		  $product_output = "<table width=\"570\" border=\"1\" cellspacing=\"2\" cellpadding=\"2\">\n  <tr>\n    <td>\n	<p align=\"left\">\n	  <table width=\"180\" border=\"0\" align=\"left\" cellpadding=\"5\" cellspacing=\"5\">\n	      <tr>\n		<td>\n		  $out_img\n		</td>\n	      </tr>\n	    </table>\n	  $out_prod\n	</p>\n    </td>\n  </tr>\n</table>\n";

        		  $ProductsTable[$cats]->addEntry(array(
        		      $factory->getHtmlField("", $product_output, "r"),
        		      $linkExternal
        		  ));
    	      }
        } // Foreach cat_product
    	  print($ProductsTable[$cats]->toHtml());
    	} // if num_prods
    	else {
    	    // This category has no products:
    	    $noProducts = $factory->getScrollList("[[base-shop.ShopTitle]]", array("ErrorMSGNoProducts"));
    	    $noProducts->setAlignments(array("left"));
    	    $noProducts->setSortEnabled(false);
    	    $noProducts->setWidth("500");
    	    $noProducts->addEntry(array(
        		$factory->getTextField("", $i18n->get('[[base-shop.ErrorNoProductsInCategory]]'), "r")
          ));
    	    print($noProducts->toHtml());
    	} // else
    } //if cats
  } // foreach categories
}
else {
    // No connection to the shop possible. Showing that we're offline:
    $scrollList = $factory->getScrollList("[[base-shop.ShopTitle]]", array("ErrorMSG"));
    $scrollList->setAlignments(array("left"));
    $scrollList->setSortEnabled(false);
    $scrollList->setWidth("500");
    $scrollList->addEntry(array(
        $factory->getTextField("", $i18n->get('[[base-shop.ErrorMSGdesc]]'), "r")
    ));
    print($scrollList->toHtml());
}

// Finish the rest of the page generation:
print($page->toFooterHtml());
$serverScriptHelper->destructor();


function areWeOnline($domain) {
   // Check to see if we're online and if the desired URL is reachable.
   // Returns true, if URL is reachable, false if not

   // Initialize curl:
   $curlInit = curl_init($domain);
   curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
   curl_setopt($curlInit,CURLOPT_HEADER,true);
   curl_setopt($curlInit,CURLOPT_NOBODY,true);
   curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curlInit, CURLOPT_USERAGENT, 'BlueLinQ/1.0');

   // Get answer
   $response = curl_exec($curlInit);

   // Close curl:
   curl_close($curlInit);

   // Generate response:
   if ($response) return true;
       return false;
}

function get_data($url) {
  $ch = curl_init();
  $timeout = 5;
  curl_setopt($ch,CURLOPT_URL,$url);
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
  curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
  curl_setopt($ch, CURLOPT_USERAGENT, 'BlueLinQ/1.0');
  $data = curl_exec($ch);
  curl_close($ch);
  return $data;
}
/*
Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

-Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.

-Redistribution in binary form must reproduce the above copyright notice, 
this list of conditions and the following disclaimer in the documentation and/or 
other materials provided with the distribution.

Neither the name of Sun Microsystems, Inc. or the names of contributors may 
be used to endorse or promote products derived from this software without 
specific prior written permission.

This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
*/
?>