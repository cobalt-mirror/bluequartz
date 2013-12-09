<?php
/*
 * $Id: vsiteList.php
 * Copyright 2001-2002 Sun Microsystems, Inc. All rights reserved.
 *
 * List all the virtual sites on the machine.
 */

include_once("ServerScriptHelper.php");
include_once("uifc/ImageButton.php");
include_once("base/vsite/vsite_common.php");

$helper = new ServerScriptHelper();

// Only 'manageSite' should be here
if (!$helper->getAllowed('manageSite')) {
  header("location: /error/forbidden.html");
  return;
}

$factory =& $helper->getHtmlComponentFactory("base-vsite");
$cce =& $helper->getCceClient();
$i18n = $helper->getI18n();

$page =& $factory->getPage();
$form =& $page->getForm();
$formId =& $form->getId();

$script_siteAdd = "/base/vsite/vsiteAdd.php";
$script_siteDefaults = "/base/vsite/vsiteDefaults.php";

// FIXME: hard coded since we don't have anywhere to store it
if (!isset($numResultsPerPage)) {
	$sites_per_page = 25;
} else {
	$sites_per_page = $numResultsPerPage;
}

// generate search field
$searchBlock =& $factory->getPagedBlock("vsiteSearch");
$searchBlock->setColumnWidths(array("8%", "92%"));

$searchby_field =& $factory->getMultiChoice('searchby');
$searchby_field->addOption($factory->getOption('fqdn'));
$searchby_field->addOption($factory->getOption('ipAddr'));
$searchby_field->addOption($factory->getOption('createdUser'));

$searchTextField =& $factory->getTextField('searchtext');
$searchTextField->setOptional('silent');

$searchButton =& $factory->getButton("javascript: document.$formId.isSearch.value = 1; document.$formId.submit();", 
				     "searchbut");
$advSearch =& $factory->getButton(
			'/base/vsite/advancedSearch.php?new_search=yes', 
			'advancedSearch');

// 'Advanced Search' disabled for now
//$searchField =& $factory->getCompositeFormField(
//			array(
//				$searchby_field, 
//				$searchTextField, 
//				$searchButton, 
//				$advSearch
//			));

$searchField =& $factory->getCompositeFormField(
			array(
				$searchby_field, 
				$searchTextField, 
				$searchButton 
			));

$searchBlock->addFormField($searchField, $factory->getLabel("searchBy"));

// add hidden boolean to indicate whether this is a search or not
$searchBoolean =& $factory->getTextField('isSearch', 0, '');
$searchBoolean->setPreserveData(false);
$searchBlock->addFormField($searchBoolean);

$siteList = $factory->getScrollList("virtualSiteList", 
				    array(
						"fqdn",
						"ipAddr",
						"createdUser", 
						"listSuspended",
						" "
				    ),
				    array(0, 1, 2, 3)); 

// reset page index if the search button was clicked
if ($isSearch == 1) {
	$siteList->setPageIndex(0);
}

// figure out how to sort
$siteList->setSortEnabled(false);
$sortMap = array(0 => 'fqdn', 1 => 'ipaddr', 2 => 'createdUser', 3 => 'suspend'); 
$sort_types_map = array(0 => 'hostname', 1 => 'ip', 2 => 'accountname', 3 => 'old_numeric');

$sortField = $sortMap[$siteList->getSortedIndex()];
$sort_type = $sort_types_map[$siteList->getSortedIndex()];

global $loginName; 

// build up our search array
if ($searchtext !== '') {
	$exact = array();
	$regex = array();
	$searchtext = preg_replace("/([][^$\{\}\|\+\.\?\*-])/", "\\\\$0", $searchtext);

	switch ($searchby) {

	// by hostname
	case 'fqdn': 
		// make the search case insensitive
		$searchtext = makeCaseInsensitive($searchtext);
		if ($searchop == '=') {
			$regex = array('fqdn' => "^$searchtext$");
		} else if ($searchop == 'begins') {
			$regex = array('fqdn' => "^$searchtext");
		} else if ($searchop == 'ends') {
			$regex = array('fqdn' => "$searchtext$");
		} else {
			// match the same for match and nomatch
			$regex = array('fqdn' => $searchtext);
		}
		break;

	// by ip
	case 'ipAddr':
		if ($searchop == '=') {
			$exact = array('ipaddr' => $searchtext);
		} else if ($searchop == 'begins') {
			$regex = array('ipaddr' => "^$searchtext");
		} else if ($searchop == 'ends') {
			$regex = array('ipaddr' => "$searchtext$");
		} else {
			// same as above
			$regex = array('ipaddr' => $searchtext);
		}
		break;
	default:
		break;
	}

    if ( $loginName != 'admin') { 
            $exact = array_merge($exact, array('createdUser' => $loginName)); 
    } 

	$vsites = $cce->findx('Vsite', $exact, $regex, $sort_type, $sortField);
	// special case not contains, because of missing functionality in cce
	if ($searchop == 'not_contains') {
		// get all the vsites remembering to sort
		$all_sites = $cce->findx('Vsite', array(), array(), 
					 $sort_type, $sortField);
		$new_list = array();
		foreach ($all_sites as $i => $oid) {
			if (!in_array($oid, $vsites)) {
				$new_list[] = $oid;
			}
		}

		$vsites = $new_list;
	}
} else {

        // display the site for the administrator 
        if ( $loginName !== 'admin') { 
                $exact = array('createdUser' => $loginName); 
        } 
        $vsites = $cce->findx('Vsite', $exact, array(), $sort_type, $sortField); 
} 
 
$vsite_disk = 0; 
$vsite_user = 0; 
foreach($vsites as $vsites_oid) { 
    $vsite = $cce->get($vsites_oid); 
    $vsite2 = $cce->get($vsites_oid, "Disk"); 
    $vsite_user += $vsite['maxusers']; 
    $vsite_disk += $vsite2['quota']; 
} 
 
list($user_oid) = $cce->find('User', array('name' => $loginName)); 
$sites = $cce->get($user_oid, 'Sites'); 

$visible = TRUE;
if($loginName != "admin") {
    if($sites['max'] <= count($vsites)) {
        $visible = FALSE;
    }
    if($sites['user'] <= $vsite_user) {
        $visible = FALSE;
    }
    if($sites['quota'] <= $vsite_disk) {
        $visible = FALSE;
    }
}
 
if($visible) { 
    // generate site add button 
    $siteList->addButton( 
            $factory->getAddButton($script_siteAdd, 
                                   '[[base-vsite.siteaddbut_help]]')); 
} 
 
generate_site_list($siteList, $cce, $factory, $vsites, $sites_per_page); 

print $page->toHeaderHtml();
?>
<SCRIPT LANGUAGE="javascript">
/*
 * if you change this function, update it in advancedSearch.php too.
 * It is split, because for some reason it works better to have it
 * output directly instead of as a function.  Who knows why?
 */
function openSiteMgmt(site, fqdn, title)
{
	var siteManageItem = top.siteMap["base_siteadmin"];

	// go through all descendents to substitute all [[VAR]] properties
	var items = new Array();
	items[0] = siteManageItem;

	// set the visibility to true, so things show up
	siteManageItem.setVisible(true, true);

	while (items.length > 0) {
		var item = items[items.length-1];
		items.length--;

		/*
		 * FIX ME
		 * substitute all other item properties and variables as well
		 */
		var tag = "[[VAR.group]]";
		var tag2 = "[[VAR.hostname]]";
		var tag3 = "[[VAR.title]]";

		// Substitue vars into URL
		if (item.origUrl == null) {
			item.origUrl = item.getUrl();
		}
		var origUrl = item.origUrl;
		if((origUrl.indexOf(tag) != -1) ||
		   (origUrl.indexOf(tag2) != -1) || 
		   (origUrl.indexOf(tag3) != -1)) {
			item.setUrl(
			    top.code.string_substitute(origUrl, tag, site, tag2,
			    			       fqdn, tag3, title));
		}

		// Substitue vars into description
		if (item.origDesc == null) {
			item.origDesc = item.getDescription();
		}
		var origDesc = item.origDesc;
		if((origDesc.indexOf(tag) != -1) ||
		   (origDesc.indexOf(tag2) != -1) || 
		   (origDesc.indexOf(tag3) != -1)) {
			item.setDescription(
			    top.code.string_substitute(origDesc, tag, site, 
			    			       tag2, fqdn, tag3, 
						       title));
		}

		// Substitue vars into label
		if (item.origName == null) {
			item.origName = item.getName();
		}
		var origName = item.origName;
		if((origName.indexOf(tag) != -1) ||
		   (origName.indexOf(tag2) != -1) || 
		   (origName.indexOf(tag3) != -1)) {
			item.setName(
			    top.code.string_substitute(origName, tag, site, 
			    			       tag2, fqdn, tag3,
						       title));
		}

		// add children to the list
		var children = item.getItems();
		for (var i = 0; i < children.length; i++) {
			items[items.length] = children[i];
		}
	}

	top.code.cList_setRoot(siteManageItem);
 	/*
	  this is essentially doing a .5 second sleep before running the selectPath.
	  that's because some browsers encounter race conditions when running our javascript
	  and will fail to display the new menu unless we put in a sleep.
	*/
	setTimeout('top.code.cList_selectPath(\"base_userList\")', 500);
}

</SCRIPT>
<?php
print $searchBlock->toHtml(); 
print "<br>\n";

// generate site defaults button

if ($loginName == 'admin') { 
    $siteDefaultsButton =& $factory->getButton($script_siteDefaults,  
                                                   "sitedefaultsbut"); 
        print $siteDefaultsButton->toHtml(); 
        print "<p></p>\n"; 
} 
 
// Print administrative information:
if ($loginName != 'admin') { 
        global $sites; 
        print "[[base-vsite.maxVsiteField]]: {$sites['max']} / [[base-vsite.quota]] : {$sites['quota']} / [[base-vsite.userSitesUser]]: {$sites['user']}"; 
} 

print "<p></p>\n";
print $siteList->toHtml();

// insert various javascript functions needed
print getDelSiteScript($i18n);
print $page->toFooterHtml();

$helper->destructor();
/*
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
