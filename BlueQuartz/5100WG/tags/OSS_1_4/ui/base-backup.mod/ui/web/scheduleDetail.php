<?
// Author: Brenda Mula, Tim Hockin
// Copyright 2000, Cobalt Networks.  All rights reserved.

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-backup");
$i18n = $factory->i18n;

$page = $factory->getPage();
$block = $factory->getPagedBlock("scheduleDetailHeader");

print($page->toHeaderHtml());

$unknown = $i18n->get("unknown");
$none = $i18n->get("none");
$all = $i18n->get("all");

$backupObj = $cceClient->get($oid);

// Backup Name
$str = $backupObj["backupTag"];
$block->addFormField(
	$factory->getTextField("name", $str ? $str : $unknown, "r"),
	$factory->getLabel("backupName"));

// Backup Schedule
$str = $backupObj["backupSchedule"];
if ($str) {
	$str = $i18n->get($str);
} else {
	$str = $unknown;
}
$block->addFormField(
	$factory->getTextField("interval", $str, "r"),
	$factory->getLabel("backupInterval"));

// method
$methodMap = array(
	"smb" => "backupViaWinFile",
	"nfs" => "backupViaNFS",
	"ftp" => "backupViaFTP"
);
$str = $backupObj["backupMethod"];
if ($str) {
	$str = $i18n->get($methodMap[$str]);
} else {
	$str = $unknown;
}
$block->addFormField(
	$factory->getTextField("method", $str, "r"),
	$factory->getLabel("backupMethod"));

// location
$str = $backupObj["backupDestination"];
$block->addFormField(
	$factory->getTextField("destination", $str ? $str : $unknown, "r"),
	$factory->getLabel("backupDestination"));

// backup CCE?
$str = $backupObj["backupConfiguration"];
if ($str) {
	$str = "yes";
} else {
	$str = "no";
}
$str = $i18n->get($str);
$block->addFormField(
	$factory->getTextField("backupconfig", $str, "r"),
	$factory->getLabel("backupConfiguration"));

// Users
if ($backupObj["backupUserScope"] == "subset") {
	$users = $backupObj["backupUsers"];
} else if ($backupObj["backupUserScope"] == "none") {
	$users = $none;
} else {
	$users = $all;
}
$block->addFormField(
	$factory->getTextField("users", $users, "r"),
	$factory->getLabel("usersToBackup"));

// groups
if ($backupObj["backupGroupScope"] == "subset") {
	$groups = $backupObj["backupGroups"];
} else if ($backupObj["backupGroupScope"] == "none") {
	$groups = $none;
} else {
	$groups = $all;
}
$block->addFormField(
	$factory->getTextField("groups", $groups, "r"),
	$factory->getLabel("groupsToBackup"));

// file set
$setmap = array(
	"0" => "backupAllFiles",
	"1" => "backupModified1days",
	"2" => "backupModified2days",
	"7" => "backupModified7days",
	"14" => "backupModified14days",
	"31" => "backupModified31days");
$str = $setmap[$backupObj["backupFileSet"]];
if ($str) {
	$str = $i18n->get($str);
} else {
	$str = $unknown;
}
$block->addFormField(
	$factory->getTextField("fileset", $str, "r"),
	$factory->getLabel("backupFileSet"));

// Add a back button
$block->addButton($factory->getBackButton("/base/backup/scheduleList.php"));
$block->addButton($factory->getButton("javascript: confirmRemove('" 
	. $backupObj["backupTag"] ."','" 
	. $oid . "')", "removeBackup"));
?>

<SCRIPT LANGUAGE="javascript">
function confirmRemove(backupName, oid) {
  var msg = "<?php print($i18n->get("removeScheduleConfirm"))?>";
  msg = top.code.string_substitute(msg, "[[VAR.backupName]]", backupName);

  if(confirm(msg))
    location = "/base/backup/scheduleRemoveHandler.php?oid="+oid;
}
// show flow buttons
top.code.flow_showNavigation(true);
</SCRIPT>

<?
print($block->toHtml());
print($page->toFooterHtml());

$serverScriptHelper->destructor();

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

