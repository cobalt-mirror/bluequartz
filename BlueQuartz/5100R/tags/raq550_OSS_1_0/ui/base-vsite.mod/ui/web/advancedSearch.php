<?php
/*
 * Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
 * $Id: advancedSearch.php,v 1.16.2.5 2002/04/05 09:55:51 pbaltz Exp $
 *
 * Provide searching by multiple properties.
 */

include_once('ServerScriptHelper.php');
include_once('base/vsite/vsite_common.php');

$helper =& new ServerScriptHelper();
$factory =& $helper->getHtmlComponentFactory('base-vsite',
					     '/base/vsite/advancedSearch.php');

$cce =& $helper->getCceClient();

session_start();

if ($new_search == 'yes') {
	session_unset();
	session_destroy();
	session_start();
}

// check if we've been here before (ie resorting the list)
$search_criteria =& parse_search_criteria();

if (($new_search != 'yes' && $modify_search != 'yes') || $pointless_search) {
	generate_search_results($factory, $cce, $search_criteria);
} else {
	generate_search_fields($factory, $cce);
}

$helper->destructor();

/*
 * parse the post vars to sort out search information into
 * the exact match and regex match arrays
 */
function &parse_search_criteria()
{
	global $searchProperty, $searchLimit, $searchText, $services,
	       $suspended, $numResultsPerPage;

	$return = array('exact' => array(), 'regex' => array());
	
	// handle fqdn and ip search
	session_register('searchProperty');
	session_register('searchLimit');
	session_register('searchText');
	$searchProperty = session_get_var('searchProperty');
	$searchLimit = session_get_var('searchLimit');
	$searchText = session_get_var('searchText');
	if ($searchText != '') {
		parse_text_search($searchProperty, $searchText, $searchLimit,
				  $return);
	}

	// handle search for sites with certain services enabled
	session_register('services');
	$services = session_get_var('services');

	if (session_get_var('services') != '') {
		foreach (explode('&', $services) as $service) {
			if ($service == '') {
				continue;
			}

			$return['exact']["$service.enabled"] = 1;
		}
	}

	// handle search for suspended sites
	session_register('suspended');
	$suspended = session_get_var('suspended');
	if (session_get_var('suspended')) {
		$return['exact']['suspend'] = 1;
	}
	
	session_register('numResultsPerPage');
	$numResultsPerPage = session_get_var('numResultsPerPage');

	return $return;
}


function &generate_search_fields(&$factory, &$cce)
{
	global $searchProperty, $searchText, $searchLimit, $services,
	       $suspended, $bw_limit, $numResultsPerPage;

	// Set default for search op
	$searchLimit = empty($searchLimit) ? "contains" : $searchLimit;
	
	$search_block =& $factory->getPagedBlock('vsiteSearch');
	
	$search_block->addDivider($factory->getLabel('searchOptions', false));

	$property =& $factory->getMultiChoice('searchProperty');
	$property->addOption($factory->getOption('fqdn'));
	$ip_option =& $factory->getOption('ipaddr');
	$ip_option->setLabel($factory->getLabel('ipAddr', false));
	$property->addOption($ip_option);

	// select the search property for search modify
	$property->setSelected(
	    (empty($searchProperty) || ($searchProperty == 'fqdn') ? 0 : 1));
	
	$search_text =& $factory->getTextField('searchText', $searchText);
	$search_text->setOptional(true);

	$limits = array('contains', '=', 'begins', 'ends', 'not_contains');
	$limit_field =& $factory->getMultiChoice('searchLimit');
	foreach ($limits as $limit) {
		$limit_field->addOption(
		    $factory->getOption($limit, 
					($limit == $searchLimit ? true : false)));
	}

	$i18n = $factory->getI18n();
	$sentenceOrder = $i18n->getProperty('sentenceOrder', 'base-vsite');
	if ($sentenceOrder != '') {
		$order = explode(' ', trim($sentenceOrder));
	} else {
		// default to english, subject verb object
		$order = array('S', 'V', 'O');
	}
	$fieldOrder = array();
	for ($i = 0; $i < count($order); $i++) {
		if ($order[$i] == 'S') {
			array_push($fieldOrder, $property);
		} else if ($order[$i] == 'V') {
			array_push($fieldOrder, $limit_field);
		} else if ($order[$i] == 'O') {
			array_push($fieldOrder, $search_text);
		}
	}

	$search_field =& $factory->getCompositeFormField($fieldOrder);
	$search_field->setId('search_field');
	$search_block->addFormField($search_field, 
				    $factory->getLabel('searchCriteria'));

	// add the services select field
	$services_select =& $factory->getMultiChoice('services');
	$services_select->setMultiple(true);

	list($services_oid) = $cce->findx('VsiteServices');
	$possible_services =& $cce->names('VsiteServices');
	foreach ($possible_services as $service) {
		$ns = $cce->get($services_oid, $service);
		$selected = ereg("&$service&", $services);
		$option =& $factory->getOption($service, $selected);
		$option->setLabel($factory->getLabel($ns['i18nName'], false));
		$services_select->addOption($option);
	}

	$search_block->addFormField($services_select,
				    $factory->getLabel('siteUses'));

	$search_block->addFormField(
	    $factory->getBoolean('suspended', $suspended),
	    $factory->getLabel('suspended'));

	if (!isset($numResultsPerPage)) {
		$numResultsPerPage = 25;
	}

	$search_block->addDivider($factory->getLabel('displayOptions', false));

	$search_block->addFormField(
		$factory->getInteger('numResultsPerPage', $numResultsPerPage, 1),
		$factory->getLabel('numResultsPerPage')
		);

	// mark that we've been here before for pointless search
	$search_block->addFormField($factory->getTextField('pointless_search',
							   1, ''));

	$page =& $factory->getPage();
	$form =& $page->getForm();

	$search_block->addButton(
		$factory->getButton($form->getSubmitAction(), 'searchbut'));
	$search_block->addButton(
		$factory->getCancelButton('/base/vsite/vsiteList.php'));
	
	print $page->toHeaderHtml();
	print $search_block->toHtml();
	print $page->toFooterHtml();
}

function &generate_search_results(&$factory, &$cce, &$criteria)
{
	// bleh, hack because can't do !~ or != finds til after alpine ships
	global $searchProperty, $searchLimit, $searchText;
   
	$siteList = $factory->getScrollList("virtualSiteList", 
					    array(
						"fqdn",
						"ipAddr",
						"listSuspended",
						""
					    ),
					    array(0, 1, 2)); 
  
	// figure out how to sort
	$siteList->setSortEnabled(false);
	$sortMap = array(0 => 'fqdn', 1 => 'ipaddr', 2 => 'suspend');
	$sort_types_map = array(0 => 'hostname', 1 => 'ip', 2 => 'old_numeric');
	$sortField = $sortMap[$siteList->getSortedIndex()];
	$sort_type = $sort_types_map[$siteList->getSortedIndex()];

	// find sites matching whatever criteria is left
	$vsites = $cce->findx('Vsite', $criteria['exact'], $criteria['regex'],
			      $sort_type, $sortField);
   
	// now subtract out the contains part
	$not_contains = array();
	if (($searchText != '') && ($searchLimit == 'not_contains')) {
		$not_contains[] = $searchProperty;
	}

	// handle not contains if necessary
	if (count($not_contains)) {
		$vsites = handle_not_contains($cce, $not_contains, $criteria,
					      $vsites, $sort_type, $sortField);
	}

	$page =& $factory->getPage();
	$form =& $page->getForm();
	$form_id = $form->getId();
   
	$siteList->addButton(
		$factory->getButton('/base/vsite/advancedSearch.php?modify_search=yes', 
				    'modifySearch'));

	$siteList->addButton(
		$factory->getButton('/base/vsite/advancedSearch.php?new_search=yes', 
				    'newSearch'));
	
	global $numResultsPerPage;
	generate_site_list($siteList, $cce, $factory, $vsites,
			   $numResultsPerPage);

	print $page->toHeaderHtml();
?>
<SCRIPT LANGUAGE="javascript">
/*
 * if you change this function, update it in vsiteList.php too.
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
	if (navigator.appName.indexOf('Netscape') != -1) {
		/*
		 * this is essentially doing a .5 second sleep before running 
		 * the selectPath.  that's because Netscape needs the extra time
		 * to finish processing other javascript, otherwise it breaks
		 * when trying to display the new menu.
		 */
		setTimeout('top.code.cList_selectPath(\"base_userList\")', 500);
	} else {
		top.code.cList_selectPath("base_userList");
	}
}

</SCRIPT>
<?
	print $siteList->toHtml();

	// add javascript for site deletion
	print getDelSiteScript($factory->getI18n(), 'advancedSearch.php');
	print $page->toFooterHtml();
}

// little function to generically parse the text search fields
function parse_text_search($attr, $searchtext, $limit, &$return)
{

	switch ($limit) {
	case '=':
		$return['regex'][$attr] = "^" .
			makeCaseInsensitive(escapeSpecialChars($searchtext)) .
			"$";
		break;
		
	case 'begins':
		$return['regex'][$attr] = "^" .
			makeCaseInsensitive(escapeSpecialChars($searchtext));
		break;
	
	case 'ends':
		$return['regex'][$attr] = 
			makeCaseInsensitive(escapeSpecialChars($searchtext)) .
			"$";
		break;
	
	case 'contains':
	case 'not_contains':
		$return['regex'][$attr] = 
			makeCaseInsensitive(escapeSpecialChars($searchtext));
		break;
	default:
		break;
	}
}

function session_get_var($name)
{
	global $HTTP_SESSION_VARS, $HTTP_POST_VARS, $$name;

	if (isset($HTTP_POST_VARS[$name])) {
		return $HTTP_POST_VARS[$name];
	} else if (isset($HTTP_SESSION_VARS[$name])) {
		return $HTTP_SESSION_VARS[$name];
	} else {
		return $$name;
	}
}
		
function handle_not_contains(&$cce, $props, $criteria, $vsites, $stype, $sfield)
{
	// find all vsites without the regex criteria in props
	for ($i = 0; $i < count($props); $i++) {
		unset($criteria['regex'][$props[$i]]);
	}

	$other_vsites = $cce->findx('Vsite', $criteria['exact'],
				    $criteria['regex'], $stype, $sfield);

	/*
	 * for each oid that matched the criteria for contains for the fields
	 * in $props, build up the list of oids that should get displayed
	 */
	$ret_vsites = array();
	foreach ($other_vsites as $oid) {
		// need to get the key of the oid, to remove it from the array
		$foo = array_search($oid, $vsites);
		
		if (!$foo && $foo !== 0) {
			$ret_vsites[] = $oid;
		}
	}

	return $ret_vsites;
}

// escape special regex characters from the passed in string
function escapeSpecialChars($text)
{
	return preg_replace("/([][^$\{\}\|\+\.\?\*-])/", "\\\\$0", $text);
}
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
