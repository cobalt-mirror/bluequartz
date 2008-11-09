<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: workgroupList.php 201 2003-07-18 19:11:07Z will $

include("ArrayPacker.php");
include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-workgroup");
$i18n = $serverScriptHelper->getI18n("base-workgroup");

$page = $factory->getPage();

$defaults = $factory->getButton("javascript: location='/base/workgroup/workgroupDefaults.php'; top.code.flow_showNavigation(false)", "groupDefaults");

// build the scroll list
$scrollList = $factory->getScrollList("groupList", array("groupName", "groupMembers", "groupDesc", "listAction"), array(0));
$scrollList->setAlignments(array("left", "left", "left", "center"));
$scrollList->setColumnWidths(array("20%", "20%", "", "1%"));

$addbutt = $factory->getAddButton(
        "javascript: location='/base/workgroup/workgroupAdd.php'; top.code.flow_showNavigation(false)",
        "[[base-workgroup.add_group_help]]");
$addbutt->setHeader(true);

$scrollList->addButton($addbutt);

// disable sorting
$scrollList->setSortEnabled(false);

// find page length
$pageLength = $scrollList->getLength();

// find start point
$start = $scrollList->getPageIndex()*$pageLength;

$oids = $cceClient->findSorted("Workgroup", "name");

// sort in the right direction
if($scrollList->getSortOrder() == "descending")
  $oids = array_reverse($oids);

// set number of entries
$scrollList->setEntryNum(count($oids));

for($i = $start; $i < count($oids) && $i < $start+$pageLength; $i++) {
  $group = $cceClient->get($oids[$i]);
  $groupName = $group["name"];
  $members = stringToArray($group["members"]);
  $memberList = implode(", ", $members);

  $removeButton = $factory->getRemoveButton("javascript: confirmRemove('$groupName')");
  // cannot remove special groups
  if($group["dont_delete"])
    $removeButton->setDisabled(true);

  $desc = $factory->getTextField("",
	$i18n->interpolate($group["description"]), "r");
  $desc->setMaxLength(85);

  $scrollList->addEntry(array(
    $factory->getGroupName("", $groupName, "r"),
    $factory->getTextField("", $memberList, "r"),
    $desc,
    $factory->getCompositeFormField(array(
      $factory->getModifyButton("javascript: location='/base/workgroup/workgroupMod.php?groupName=$groupName'; top.code.flow_showNavigation(false)"),
      $removeButton
    ))
  ), "", false, $i);
}

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<SCRIPT LANGUAGE="javascript">
function confirmRemove(groupName) {
  var message = "<?php print($i18n->get("removeGroupConfirm"))?>";
  message = top.code.string_substitute(message, "[[VAR.groupName]]", groupName);

  if(confirm(message))
    location = "/base/workgroup/workgroupRemoveHandler.php?groupName="+groupName;
}

// show flow buttons
if(top.code != null && top.code.flow_showNavigation != null)
  top.code.flow_showNavigation(true);
</SCRIPT>

<?php print($defaults->toHtml()); ?>
<BR>

<?php print($scrollList->toHtml()); ?>

<?php print($page->toFooterHtml()); 
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

