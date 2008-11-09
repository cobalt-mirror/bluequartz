<?php
// Author: jmayer@cobalt.com
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: maillistList.php 3 2003-07-17 15:19:15Z will $

include("ServerScriptHelper.php");
include("ArrayPacker.php");

$serverScriptHelper = new ServerScriptHelper() or die ("no SSH");
$cceClient = $serverScriptHelper->getCceClient() or die ("no CCE");
$factory = $serverScriptHelper->getHtmlComponentFactory(
	"base-maillist", "/base/maillist/maillistList.php");
$i18n = $serverScriptHelper->getI18n("base-maillist") or die ("no i18n");

$page = $factory->getPage();

// deal with remove actions
if ($_REMOVE) {
  $cceClient->destroy($_REMOVE);
}

// build scroll list of mailing lists
$scrollList = $factory->getScrollList("maillistList", array("maillistNameHeader", "recipientsHeader", "maillistDescHeader" ,"maillistActionHeader"), array(0));
$scrollList->setAlignments(array("left", "left", "left", "center"));
$scrollList->setColumnWidths(array("20%", "", "", "1%"));

$scrollList->addButton($factory->getAddButton("/base/maillist/maillistAdd.php"));

// disable sorting
$scrollList->setSortEnabled(false);
$scrollList->setArrowVisible(true);
// find page length
$pageLength = $scrollList->getLength();

// find start point
$start = $scrollList->getPageIndex()*$pageLength;

$oids = $cceClient->findSorted("MailList", "name");

// sort in the right direction
if($scrollList->getSortOrder() == "descending")
  $oids = array_reverse($oids);

// set total number of entries in list
$scrollList->setEntryNum(count($oids));

for($i = $start; $i < count($oids) && $i < $start+$pageLength; $i++) {
  $oid = $oids[$i];
  $ml = $cceClient->get($oid, "");
  
  $members = stringToArray($ml['local_recips']);
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
      implode(', ', $members),
      "r"),
    $desc,
    $factory->getCompositeFormField(array(
      $factory->getModifyButton(
        "/base/maillist/maillistAdd.php?_TARGET=$oid&_LOAD=1" ),
      $w
    ))
  ), "", false, $i);
}

print $page->toHeaderHtml();

?>

<SCRIPT LANGUAGE="javascript">
function confirmRemove(msg, oid) {
  if(confirm(msg))
    location = "/base/maillist/maillistList.php?_REMOVE=" + oid;
}
</SCRIPT>

<?php

print $scrollList->toHtml();

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

