<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: userAddHandler.php 3 2003-07-17 15:19:15Z will $

include("ArrayPacker.php");
include("ServerScriptHelper.php");
include("uifc/Stylist.php");
include("I18n.php");
include("CceError.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$i18n = new I18n("base-user",$HTTP_ACCEPTED_LANGS);

$errors=array();

$stylist = new Stylist();
// we arbitrarily pick a locale here because we discard the i18ned names
// anyways
$styleIdToName = $stylist->getAllResources("en");

// get IDs of all available styles
$styleIds = array_keys($styleIdToName);

// style preference is the "trueBlue" if it is available, the first available
// style of nothing if no styles are available
$firstChoice = "trueBlue";
$stylePreference = "";
if(count($styleIds) > 0)
  if(in_array($firstChoice, $styleIds))
    $stylePreference = $firstChoice;
  else
    $stylePreference = $styleIds[0];

// create user
if($sortNameField){
  $sortby = $sortNameField;
}else{
  $sortby = "";
}

// collect a list of capabilities that were selected
$capGroups = $serverScriptHelper->getAllCapabilityGroups();
$capsToSet = array();
foreach($capGroups as $cap)
  if ($HTTP_POST_VARS["capgroup_" . $cap["name"]])
    $capsToSet[] = $cap["name"];
$oid = $cceClient->create("User", array("name" => $userNameField, "sortName" => $sortby, "fullName" =>$fullNameField, "password" => $passwordField, "localePreference" => "browser", "stylePreference" => $stylePreference, "description" => $userDescField, "capLevels" => arrayToString($capsToSet)));
$errors = array_merge($errors,$cceClient->errors());


for ($i = 0; $i < count($errors); $i++) {
	if ( ($errors[$i]->code == 2) && ($errors[$i]->key === "password"))
	{
		$errors[$i]->message = "[[base-user.error-invalid-password]]";
	}
}

// if user was created without errors, setup the rest of their information
if(count($errors) == 0) {
  // set quota
  if(!$maxDiskSpaceField)
	$maxDiskSpaceField = -1;

  $cceClient->set($oid, "Disk", array("quota" => $maxDiskSpaceField));
  $errors = array_merge($errors, $cceClient->errors());
  
  // add to groups
  $groupNames = stringToArray($memberGroupsField);
  for($i = 0; $i < count($groupNames); $i++) {
    $oids = $cceClient->find("Workgroup", array("name" => $groupNames[$i]));
    $group = $cceClient->get($oids[0]);
    $members = stringToArray($group["members"]);
    $members[] = $userNameField;
    $cceClient->set($oids[0], "", array("members" => arrayToString($members)));
    $errors = array_merge($errors, $cceClient->errors());
  }

  // add AddressbookEntry
  $cceClient->set($oid, "AddressbookEntry", array("owner" => ""));
  $errors = array_merge($errors, $cceClient->errors());

  // set email information

  // replace && with &, to avoid always getting a blank alias in the field
  // in cce, this also skirts around dealing with browser issues
  $emailAliasesField = str_replace("&&", "&", $emailAliasesField);

  $cceClient->set($oid, "Email", array("aliases" => $emailAliasesField));
  $errors = array_merge($errors, $cceClient->errors());
} else {
	for ($i = 0; $i < count($errors); $i++) {	
		if ( ($errors[$i]->code == 2) && ($errors[$i]->key === "password")) // check for cce reject of password (too short)
		{
			$errors[$i]->message = "[[base-user.error-invalid-password]]";
		}
	}
}

print($serverScriptHelper->toHandlerHtml("/base/user/userList.php", $errors, "base-user"));

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

