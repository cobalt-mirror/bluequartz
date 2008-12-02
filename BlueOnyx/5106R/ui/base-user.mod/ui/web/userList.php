<?php
/*
 * Copyright 2000-2002 Sun Microsystems, Inc.  All rights reserved.
 * $Id: userList.php 476 2005-06-12 06:16:11Z shibuya $
 */
include_once("ServerScriptHelper.php");
include_once("uifc/Button.php");
include_once("AutoFeatures.php");
include_once("ArrayPacker.php");
include_once("Capabilities.php");

$serverScriptHelper = new ServerScriptHelper();

// Only adminUser and siteAdmin should be here
if (!$serverScriptHelper->getAllowed('adminUser') &&
    !($serverScriptHelper->getAllowed('siteAdmin') &&
      $group == $serverScriptHelper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

$autoFeatures = new AutoFeatures($serverScriptHelper);
$cceClient = $serverScriptHelper->getCceClient();
$capabilities = new Capabilities($cceClient);
$factory = $serverScriptHelper->getHtmlComponentFactory("base-user", "/base/user/userList.php?group=$group");
$i18n = $serverScriptHelper->getI18n("base-user");

$page = $factory->getPage();
$form = $page->getForm();
$formId = $form->getId();

$defaults = $factory->getButton("javascript: location='/base/user/userDefaults.php?group=$group'; top.code.flow_showNavigation(false)", "userDefaults");

if ($group) {
	$userDefaults = $cceClient->getObject("Vsite", array("name" => $group), "UserDefaults");
	list($vsite) = $cceClient->find("Vsite", array("name" => $group));
	$vsiteObj = $cceClient->get($vsite);
    	list($userServices) = $cceClient->find("UserServices", array("site" => $group));
	$hiddenGroup = $factory->getTextField('group', $group, '');
} else {
	$userDefaults = $cceClient->getObject("System", array(), "UserDefaults");
}

$pageLength = ($userDefaults != null && $userDefaults["userlist_range"] > 0 ) ? $userDefaults["userlist_range"] : 25;

// generate search field
$searchBlock = $factory->getPagedBlock("userSearch");
$searchBlock->setColumnWidths(array("8%", "92%"));

$searchby = $factory->getMultiChoice("searchBy", array("userName", "fullName", "emailAlias"));

$searchTextField =& $factory->getTextField("searchtext");
$searchTextField->setOptional('silent');

$searchButton = $factory->getButton("javascript: document.$formId.isSearch.value = 1; document.$formId.submit();", "searchbut");

$searchField = $factory->getCompositeFormField(array($searchby, $searchTextField, $searchButton));

$searchBlock->addFormField($searchField, $factory->getLabel("userSearchCriteria"));

/*
 * add hidden form field to mark that a search is in progress to reset the page
 * index for the scroll list
 */
$searchBoolean =& $factory->getTextField('isSearch', 0, '');
$searchBoolean->setPreserveData(false);
$searchBlock->addFormField($searchBoolean);

// build the scroll list
$scrollList = $factory->getScrollList("userList", array("fullName", "userName", "emailAliases", "rights", "listAction"), array(1));
$scrollList->setAlignments(array("left", "left", "left", "left", "center"));
$scrollList->setColumnWidths(array("", "1%", "", "", "1%"));
$scrollList->setLength($pageLength);
if ($isSearch == 1) {
	$scrollList->setPageIndex(0);
}

$scrollList->processErrors($serverScriptHelper->getErrors());

if ($group) {
	$scrollList->setLabel($factory->getLabel('userListTitle', false, array('fqdn' => $vsiteObj['fqdn'])));
}

$addurl = "";

$scrollList->addButton(
	$factory->getAddButton(
		"javascript: location='/base/user/userAdd.php?group=$group';"
		. " top.code.flow_showNavigation(false)",
		"[[base-user.add_user_help]]"));

// disable sorting
$scrollList->setSortEnabled(false);

// find sort key
$sortBy = ($i18n->getProperty("needSortName") == "yes") ? "sortName" : "userName";

$sortKeyMap = array(0 => $sortBy, 1 => "name");
$sortKey = $sortKeyMap[$scrollList->getSortedIndex()];

// find start point
$start = $scrollList->getPageIndex() * $pageLength;

if ($group) {
	$exactMatch = array("site" => $group);
} else {
	$exactMatch = array();
}
$regexMatch = array();

$fieldMap = array('userName' => 'name', 'fullName' => 'fullName', 'emailAlias' => 'Email.aliases');
$field = $fieldMap[$searchBy];

if ($searchtext !== '') {
    $searchtext = preg_replace("/([][^$\{\}\|\+\.\?\*-])/", "\\\\$0", $searchtext);
    $regexMatch[$field] = makeCaseInsensitive($searchtext);
}

$oids = $cceClient->findx("User", $exactMatch, $regexMatch, 'ascii', $sortKey);

// sort in the right direction
if ($scrollList->getSortOrder() == "descending") {
	$oids = array_reverse($oids);
}

// admin is not a regular user, so we need to remove it
$adminOids = $cceClient->find("User", array("name" => "admin"));
$adminCount = 0;
for ($i = 0; $i < count($oids); $i++) {
	if($oids[$i] == $adminOids[0]) {
		$oids = array_merge(array_slice($oids, 0, $i),
				    array_slice($oids, $i+1));
		// number of users minus admin
		$adminCount++;
		break;
	}
}

for ($i = $start; $i < count($oids) && $i < $start + $pageLength; $i++) {
	$user = $cceClient->get($oids[$i]);
	$fullName = $user["fullName"];

	$userName = $user["name"];

	$userEmail = $cceClient->get($oids[$i], 'Email');
	$aliasStr = implode(', ', stringToArray($userEmail['aliases']));
	$aliases = $factory->getTextField('', $aliasStr, 'r');

	// Should use display options to dynamically include fields...
	// Comment out for now
	//  $desc = $factory->getTextField("", 
	//	$i18n->interpolate($user["description"]), "r");
	//  $desc->setMaxLength(80);	

	// Create user rights icons
	if (! $user['ui_enabled']) {
		$rights = array($factory->getImageLabel("userSuspended", "/libImage/suspend_icon_small.gif"));
	} else {
		$siteAdmin = ($capabilities->getAllowed('siteAdmin', $oids[$i]) ) ? $factory->getImageLabel("siteAdminEnabled", "/libImage/administrator.gif") : $factory->getImageLabel("blank", "/libImage/blankIcon.gif", false);

		$rights = array($siteAdmin);
  
		if ($group) {
			$autoFeatures->display($rights, "list.User", array("VSITE_OID" => $vsite, "CCE_SERVICES_OID" => $userServices, "CCE_OID" => $oids[$i]));
    		} else {
			$autoFeatures->display($rights, "list.User", array("CCE_SERVICES_OID" => $userServices, "CCE_OID" => $oids[$i]));
		}
	}

	$scrollList->addEntry(
		array($factory->getFullName("", $fullName, "r"),
		      $factory->getUserName("", $userName, "r"),
		      $aliases,
		      $factory->getCompositeFormField($rights),
		      $factory->getCompositeFormField(
				array($factory->getModifyButton("javascript: location='/base/user/userMod.php?userNameField=$userName&group=$group'; top.code.flow_showNavigation(false)"),
      					$factory->getRemoveButton("javascript: confirmRemove('$userName')")
		))), "", false, $i);
}

$scrollList->setEntryNum(count($oids) - $adminCount);

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<SCRIPT LANGUAGE="javascript">
function confirmRemove(userName) {
  var message = "<?php print($i18n->get("removeUserConfirm"))?>";
  message = top.code.string_substitute(message, "[[VAR.userName]]", userName);

  if(confirm(message))
    location = "/base/user/userRemoveHandler.php?userName="+userName<?php if ($group) print("+\"&group=$group\""); ?>;
}

// show flow buttons
if(top.code != null && top.code.flow_showNavigation != null)
  top.code.flow_showNavigation(true);
</SCRIPT>

<?php print $searchBlock->toHtml(); ?>
<BR>
<?php print($defaults->toHtml()); ?>
<BR>

<?php 

print($scrollList->toHtml());

if ($group) {
	print($hiddenGroup->toHtml());
}

print($page->toFooterHtml());

// create a case insensitive regular expression from the given string
function makeCaseInsensitive($string)
{
	return preg_replace("/([[:alpha:]])/e",
		"'[' . strtoupper('$1') . strtolower('$1') . ']'",
		$string);
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
