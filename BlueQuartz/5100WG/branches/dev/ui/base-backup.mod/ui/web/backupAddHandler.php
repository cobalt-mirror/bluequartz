<?
include("ServerScriptHelper.php");
include("ArrayPacker.php");

$serverScriptHelper = new ServerScriptHelper($sessionId);
$cceClient = $serverScriptHelper->getCceClient();

// tmpname function
function getrand () {
	# Generate Random cronjob name
	$chars = array ( 
		'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o',
		'p','q','r','s','t','u','v','w','x','y','z','A','B','C','D',
		'E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S',
		'T','U','V','W','X','Y','Z','1','2','3','4','5','6','7','8',
		'9','0');
	srand ((double) microtime() * 10000000);
        $rand = array_rand($chars,8);
        for($i=0;$i<count($rand);$i++) {
		$filename .= $chars[$rand[$i]];
        }

	return $filename;
}

// create the CCE object
$newobj = array();

// Backup Name
$newobj["backupTag"] = $backupNameField;	

// cronfile
if ($backupScheduleField != "backupImmediate") {
	for ($i=0;$i<10;$i++) {
		$cfile = getrand();
		$cronfile = sprintf("/etc/cron.%s/%s.backup",
			$backupScheduleField, $cfile);
		if ( ! file_exists( $cronfile ) ) {		
			$newobj["backupCronFile"] = $cronfile;
			break;
		}
	}
}

// Backup Configuration Data
$newobj["backupConfiguration"] = $backupConfigurationField ? "1" : "0";

// user list
$backupUserScopeMap = array(
	"backupAllUsers" => "all", 
	"backupNoUsers" => "none", 
	"backupUserSubset" => "subset");
$newobj["backupUserScope"] = $backupUserScopeMap[$backupUserScopeField];

// Put users into correct format, ui has &name1&name2& format
$userNames = stringToArray($backupUsersField);
$userNameString = "";
for($i = 0; $i < count($userNames); $i++) {
	if ($i != 0) {
		$userNameString .= ",";
	}
	$userNameString .= $userNames[$i];
}
$newobj["backupUsers"] = $userNameString;

// Groups
$backupGroupScopeMap = array(
	"backupAllGroups" => "all", 
	"backupNoGroups" => "none", 
	"backupGroupSubset" => "subset");
$newobj["backupGroupScope"] = $backupGroupScopeMap[$backupGroupScopeField];

// Put groups into correct format, ui has &name1&name2& format
$groupNames = stringToArray($backupGroupsField);
$groupNameString = "";
for($i = 0; $i < count($groupNames); $i++) {
	if ($i != 0) {
		$groupNameString .= ",";
	}
	$groupNameString .= $groupNames[$i];
}
$newobj["backupGroups"] = $groupNameString;

// file set
$backupFileSetMap = array(
	"backupModified31days" => "31", 
	"backupModified14days" => "14", 
	"backupModified7days" => "7", 
	"backupModified2days" => "2", 
	"backupModified1days" => "1", 
	"backupAllFiles" => "0");
$newobj["backupFileSet"] = $backupFileSetMap[$backupFileSetField];

// Method
$backupMethodMap = array(
	"backupViaNFS"		=> "nfs", 
	"backupViaWinFile"	=> "smb",
	"backupViaFTP"		=> "ftp"
);
$newobj["backupMethod"] = $backupMethodMap[$backupMethodField];

if ($backupMethodField == "backupViaFTP") {
	$newobj["password"] = $backupFTPPassword;
	$newobj["backupUsername"] = $backupFTPUsername;
} else if ($backupMethodField == "backupViaWinFile") {
	$newobj["password"] = $backupSambaPassword;
	$newobj["backupUsername"] = $backupSambaUsername;
} 

// Schedule
$newobj["backupSchedule"] = $backupScheduleField;

// Check Destination before setting
$backupDestinationMap = array(
	"backupViaWinFile"	=> "$backupSambaDestination", 
	"backupViaNFS"		=> "$backupNFSDestination",
	"backupViaFTP"		=> "$backupFTPDestination"
);

$newobj["backupDestination"] = $backupDestinationMap[$backupMethodField];

// actually set the thing in CCE
$newoid = $cceClient->create("Backup", $newobj);
$errors = $cceClient->errors();

// Trigger the Handler
if($newoid) {
	$modobj = array("pendingBackup" => "1" , 
		"password" => $newobj["password"]);
	$cceClient->set($newoid, "",$modobj);
	$errors = array_merge($errors, $cceClient->errors());
}

if (!count($errors)) {
	if ($backupScheduleField == "backupImmediate") {
		$imm = $backupNameField;
	}
	print($serverScriptHelper->toHandlerHtml(
		"/base/backup/scheduleList.php?didImmediate=$imm", 
		$errors, "base-backup"));
} else {
        for ($i = 0; $i < count($errors); $i++) {
                if ( ($errors[$i]->code == 2) && ($errors[$i]->key === "backupDestination")) // check for cce reject of backupDestination
                {
                        $errors[$i]->message = "[[base-backup.backupDestination_invalid]]";
                }
        }

	print($serverScriptHelper->toHandlerHtml(
		"/base/backup/backupAdd.php", $errors, "base-backup"));
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

