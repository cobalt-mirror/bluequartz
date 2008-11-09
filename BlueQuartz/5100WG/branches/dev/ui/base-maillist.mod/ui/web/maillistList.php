<?php
// Author: jmayer@cobalt.com
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: maillistList.php 201 2003-07-18 19:11:07Z will $

include("ServerScriptHelper.php");
include("ArrayPacker.php");
include("Product.php");

$serverScriptHelper = new ServerScriptHelper() or die ("no SSH");
$cceClient = $serverScriptHelper->getCceClient() or die ("no CCE");

// Update subscriber counts
if(!$nosync) {
	$time = time();
	$sysoid = $cceClient->find('System');
	$ret = $cceClient->set($sysoid, 'MailList', 
		array('site'=>$group, 'commit'=>$time));
}

$factory = $serverScriptHelper->getHtmlComponentFactory(
	"base-maillist");
	// "base-maillist", "/base/maillist/maillistList.php?group=$group");
$i18n = $serverScriptHelper->getI18n("base-maillist") or die ("no i18n");

$page = $factory->getPage();

$product = new Product($cceClient);

if($product->isRaq() && ($group == '')) {
	header("location: /error/forbidden.html");
	return;
}

// deal with remove actions
if ($_REMOVE) {
  $cceClient->destroy($_REMOVE);
  $errors = $cceClient->errors();
}

// build scroll list of mailing lists
$scrollList = $factory->getScrollList("maillistList", array("maillistNameHeader", "recipientsHeader", "maillistDescHeader" ,"maillistActionHeader"), array(0));
if ($product->isRaq()) {
  list($vsite) = $cceClient->find('Vsite', array('name' => $group));
  $vsiteObj = $cceClient->get($vsite);
  $groupName = $vsiteObj['fqdn'];
} else {
  // find the workgroup name
  $groupName = "something";
}

// The title reflects the group membership, if any  
$label = 'maillistListNoGroup';
if ($product->isRaq()) {
  $label = 'maillistList';
}

$scrollList->setLabel($factory->getLabel($label, false, array('group' => $groupName)));
$scrollList->setAlignments(array("left", "left", "left", "center"));
$scrollList->setColumnWidths(array("99", "141", "248", "62"));

$addbutt = $factory->getAddButton("/base/maillist/maillistMod.php?group=$group");
$addbutt->setHeader(true);
$scrollList->addButton($addbutt);

// disable sorting
$scrollList->setSortEnabled(false);
// $scrollList->setArrowVisible(true);

// find page length
$pageLength = $scrollList->getLength();

// find start point
$start = $scrollList->getPageIndex()*$pageLength;

$oids = $cceClient->findSorted("MailList", "name", array('site' => $group));

// sort in the right direction
if($scrollList->getSortOrder() == "descending")
  $oids = array_reverse($oids);

// set total number of entries in list
$scrollList->setEntryNum(count($oids));

for($i = $start; $i < count($oids) && $i < $start+$pageLength; $i++) {
  $oid = $oids[$i];
  $ml = $cceClient->get($oid, "");
  
  $members = array();
  // magic variables! if subscriber list is empty, then 'nobody' is the sole recipient
  // parse it out so that we don't show it to the user
  if ($ml['local_recips'] != '&nobody&') {
    $members = stringToArray($ml['local_recips']);
  }
  $members = array_merge($members, stringToArray($ml['remote_recips']));
  if ($ml['group']) {
    $groupText = $i18n->get("groupSubscriber", "", 
      array("group"=>$ml['group']));
    $members = array_merge($members, "". $groupText . "");
  }

  $desc = $factory->getTextField("", $i18n->interpolate($ml["description"]),
	"r");
  $desc->setMaxLength(80);

  $msg = $i18n->get("confirm_removal_of_list", "",
    array('list' => $ml["name"]));

  $w = $factory->getRemoveButton(
	"javascript: confirmRemove('$msg', '$oid')");
  if ($ml['group']) { $w->setDisabled(true); }
  $scrollList->addEntry( array(
    $factory->getTextField("", $ml["name"], "r"),
    $factory->getTextField("", 
	   $i18n->interpolate("[[base-maillist.numSubs]]", array('num' => count($members), 'plural' => (count($members) == 1 ? '':'s'))),
      "r"),
    $desc,
    $factory->getCompositeFormField(array(
      $factory->getModifyButton(
        "/base/maillist/maillistMod.php?group=$group&_TARGET=$oid&_LOAD=1" ),
      $w
    ))
  ), "", false, $i);
}

print $page->toHeaderHtml();

?>

<SCRIPT LANGUAGE="javascript">
function confirmRemove(msg, oid) {
  if(confirm(msg))
    location = "/base/maillist/maillistList.php?group=<?php print $group; ?>&_REMOVE=" + oid;
}
</SCRIPT>

<?php

print $scrollList->toHtml();

if ($group) {
  $groupfield = $factory->getTextField('group', $group, '');
  print $groupfield->toHtml();
}

if (count($errors))
{
	print "<SCRIPT LANGUAGE=\"javascript\">\n";
	print $serverScriptHelper->toErrorJavascript($errors);
	print "</SCRIPT>\n";
}

print $page->toFooterHtml();


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

