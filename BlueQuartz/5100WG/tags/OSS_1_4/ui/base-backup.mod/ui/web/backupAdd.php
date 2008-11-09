<?php
// Author: Brenda Mula, Tim Hockin
// Copyright 2000 Cobalt Networks.  All rights reserved
// $Id: backupAdd.php 3 2003-07-17 15:19:15Z will $

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-backup", 
	"/base/backup/backupAddHandler.php");
$page = $factory->getPage();
$i18n = $factory->i18n;

print($page->toHeaderHtml());

// get the pagedblock
$block = $factory->getPagedBlock("backupAddHeader");

// Backup Name
$defaultBackupName = date("dMY");
$block->AddFormField(
	$factory->getTextField("backupNameField", $defaultBackupName),
	$factory->getLabel("backupNameField")
);

// SCALEBACK
/*
// backup CCE?
$block->addFormField(
	$factory->getBoolean("backupConfigurationField", "1"),
	$factory->getLabel("backupConfigurationField")
);

// make the User radio button list
$backupUserScope = $factory->getMultiChoice("backupUserScopeField", 
	array("backupAllUsers","backupNoUsers"));

// get all users
$userNames = array();
$userOids = $cceClient->find("User");
for ($i = 0; $i < count($userOids); $i++) {
	$user = $cceClient->get($userOids[$i]);
	array_push($userNames, $user["name"]);
}
sort($userNames);
$userNamesString = arrayToString($userNames);

// make the picklist for users
$backupUsers = $factory->getSetSelector("backupUsersField", "", 
	$userNamesString, "usersToBackup", "usersNotToBackup");
$backupUserSubset = $factory->getOption("backupUserSubset");
$backupUserSubset->addFormField($backupUsers, $factory->getLabel("userList"));

// Add the special "subset of all users" item
$backupUserScope->addOption($backupUserSubset);

 // Set Default to Backup all users
$backupUserScope->setSelected(0, true);
$block->addFormField($backupUserScope, $factory->getLabel("usersToBackupField"));


// make the group radio list
$backupGroupScope = $factory->getMultiChoice("backupGroupScopeField", 
	array("backupAllGroups", "backupNoGroups"));

// get all the groups
$groupNames = array();
$groupOids = $cceClient->find("Workgroup");
for ($i = 0; $i < count($groupOids); $i++) {
	$group = $cceClient->get($groupOids[$i]);
	array_push($groupNames, $group["name"]);
}
sort($groupNames);
$groupNamesString = arrayToString($groupNames);

// Make backup group picklist
$backupGroups = $factory->getSetSelector("backupGroupsField", "", 
	$groupNamesString, "groupsToBackup", "groupsNotToBackup");

// add the special subset item
$backupGroupSubset = $factory->getOption("backupGroupSubset");
$backupGroupSubset->addFormField($backupGroups, 
	$factory->getLabel("groupList"));

$backupGroupScope->addOption($backupGroupSubset);

// Set Default to Backup all Groups
$backupGroupScope->setSelected(0, true);
$block->addFormField($backupGroupScope, $factory->getLabel("groupsToBackupField"));
*/

// Default is full backup
//backupGroupScopeField=backupAllGroups
//backupUserScopeField=backupAllUsers
//backupConfigurationField=1
$block->addFormField(
	$factory->getBoolean("backupConfigurationField", "1", "")
);
$block->addFormField(
	$factory->getBoolean("backupGroupScopeField", "backupAllGroups", "")
);
$block->addFormField(
	$factory->getBoolean("backupUserScopeField", "backupAllUsers", "")
);

// File set
$backupFileSet = $factory->getMultiChoice("backupFileSetField", array(
	"backupAllFiles", 
	"backupModified31days",
	"backupModified14days",
	"backupModified7days",
	"backupModified2days",
	"backupModified1days")
);
$block->addFormField($backupFileSet, $factory->getLabel("backupFileSetField"));  

// Windows File Sharing
$backupWinMethod = $factory->getOption("backupViaWinFile");
$backupWinMethod->AddFormField(
	$factory->getTextField("backupSambaDestination", ""),
	$factory->getLabel("backupSambaDestination")
);
$backupWinMethod->AddFormField(
	$factory->getTextField("backupSambaUsername", ""),
	$factory->getLabel("backupUsername")
);
$backupWinMethod->AddFormField(
	$factory->getPassword("backupSambaPassword", "", false),
	$factory->getLabel("backupPassword")
);

// FTP
$backupFTPMethod = $factory->getOption("backupViaFTP");
$backupFTPMethod->AddFormField(
	$factory->getTextField("backupFTPDestination", ""),
	$factory->getLabel("backupFTPDestination")
);
$backupFTPMethod->AddFormField(
	$factory->getTextField("backupFTPUsername", ""),
	$factory->getLabel("backupUsername")
);
$backupFTPMethod->AddFormField(
	$factory->getPassword("backupFTPPassword", "", false),
	$factory->getLabel("backupPassword")
);

// NFS
$backupNFSMethod = $factory->getOption("backupViaNFS");
$backupNFSMethod->AddFormField(
	$factory->getTextField("backupNFSDestination", ""),
	$factory->getLabel("backupNFSDestination")
);

$backupMethod = $factory->getMultiChoice("backupMethodField");
$backupMethod->addOption($backupWinMethod);
$backupMethod->addOption($backupFTPMethod);
$backupMethod->addOption($backupNFSMethod);

// By default, set Windows as Method.
$backupMethod->setSelected(0, true);

// Add the big Method block
$block->addFormField($backupMethod, $factory->getLabel("backupMethodField"));

// Drop-down selection for Schedule
$backupScheduleField = $factory->getMultiChoice("backupScheduleField", array(
	"backupImmediate",
	"daily",
	"weekly",
	"monthly")
);
$block->addFormField(
	$backupScheduleField,
	$factory->getLabel("backupIntervalField")
);  

// save and cancel buttons
$block->addButton($factory->getSaveButton($page->getSubmitaction()));
$block->addButton($factory->getCancelButton("/base/backup/scheduleList.php"));

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

