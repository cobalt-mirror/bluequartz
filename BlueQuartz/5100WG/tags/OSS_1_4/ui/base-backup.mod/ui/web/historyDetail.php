<?php
// Author: Brenda Mula, Tim Hockin
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: historyDetail.php 3 2003-07-17 15:19:15Z will $

include("ServerScriptHelper.php");
include("ArrayPacker.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-backup");
$page = $factory->getPage();
$i18n = $factory->i18n;

print($page->toHeaderHtml());

$unknown = $i18n->get("unknown");
$none = $i18n->get("none");
$all = $i18n->get("all");

$block = $factory->getPagedBlock("historyDetailHeader");

// args are passed in as an encoded hash (no re-parsing history)
$history = stringToHash($args);

// Backup Name
$block->addFormField(
	$factory->getTextField("backupTag", $history["NAME"], "r"),
	$factory->getLabel("backupName"));

// Backup date/time
$backupTime = $history["START_TIME"];
if (!$backupTime || $backupTime == "???") {
	$idate = $unknown;
} else {
	$idate = $i18n->strftime("%c", $backupTime);
}
$block->addFormField(
	$factory->getTextField("backupDate", $idate, "r"),
	$factory->getLabel("backupDate"));

// end time
$timestr = $history["STOP_TIME"];
if ($timestr && $timestr != "???") {
	$timestr = $i18n->strftime("%c", $timestr);
} else {
	$timestr = $unknown;
}
$block->addFormField(
	$factory->getTextField("backupEnded", $timestr, "r"),
	$factory->getLabel("backupEnded"));

// status
if ($history["RETURNCODE"] == 0) {
	$iconstr = "success";
	$iconmsg = "statusSuccess";
} else if ($history["RETURNCODE"] == -1) {
	$iconstr = "pending";
	$iconmsg = "statusPending";
} else {
	$iconstr = "failure";
	$iconmsg = "statusFailure";
}
$icon = $factory->getStatusSignal($iconstr);
$block->addFormField(
	$factory->getCompositeFormField(array(
		$icon, $factory->getLabel($iconmsg)
	), "&nbsp;&nbsp;", "r"), $factory->getLabel("backupRetval"));
	
// method	
$methodMap = array(
	"smb" => "backupViaWinFile",
	"nfs" => "backupViaNFS",
	"ftp" => "backupViaFTP"
);
$method = $history["METHOD"];
if ($method) {
	$method = $i18n->get($methodMap[$method]);
} else {
	$method = $unknown;
}
$block->addFormField(
	$factory->getTextField("method", $method, "r"),
	$factory->getLabel("backupMethod"));

// location
$block->addFormField(
	$factory->getTextField("location", 
		$history["LOCATION"] ? $history["LOCATION"] : $unknown, "r"),
	$factory->getLabel("backupLocation"));

// backup CCE?
$str = $history["BACKUP_CONFIG"] ? "yes" : "no";
$str = $i18n->get($str);
$block->addFormField(
	$factory->getTextField("backup_config", $str , "r"),
	$factory->getLabel("backupConfiguration"));

//user list
if ($history["USERS"] == "all") {
	$users = $all;
} else if ($history["USERS"] == "none") {
	$users = $none;
} else {
	$users = $history["USERS"];
}
$block->addFormField(
	$factory->getTextField("users", $users ? $users : $none, "r"),
	$factory->getLabel("usersInBackup"));

// group list
if ($history["GROUPS"] == "all") {
	$groups = $all;
} else if ($history["GROUPS"] == "none") {
	$groups = $none;
} else {
	$groups = $history["GROUPS"];
}
$block->addFormField(
	$factory->getTextField("groups", $groups ? $groups : $none, "r"),
	$factory->getLabel("groupsInBackup"));

// Add a back button
$block->addButton($factory->getBackButton("/base/backup/restoreList.php"));

// Add a restore button
if ($history["RETURNCODE"] == 0) {
	$block->addButton($factory->getButton("/base/backup/restore.php?" .  
		"args=" . urlencode(hashToString($history)), "restoreButton"));
}

// history
$block->addButton($factory->getButton("javascript: confirmRemove('" . 
	$history["START_TIME"] . "','" .
	$history["NAME"] . "','$idate')", "removeButton"));
?>

<SCRIPT LANGUAGE="javascript">
function confirmRemove(backupTime, backupName, idate) {
  var message = "<?php print($i18n->get("removeHistoryConfirm"))?>";
  message = top.code.string_substitute(message, "[[VAR.time]]", idate);
  message = top.code.string_substitute(message, "[[VAR.name]]", backupName);

  if(confirm(message))
    location = "/base/backup/historyRemoveHandler.php"+
        "?backupTime="+backupTime+"&backupName="+backupName;
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

