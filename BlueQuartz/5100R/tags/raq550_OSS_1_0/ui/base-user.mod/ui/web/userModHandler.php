<?php
/*
 * Copyright 2000-2002 Sun Microsystems, Inc.  All rights reserved.
 * $Id: userModHandler.php 259 2004-01-03 06:28:40Z shibuya $
 */

include("ArrayPacker.php");
include("ServerScriptHelper.php");
include("AutoFeatures.php");

$serverScriptHelper = new ServerScriptHelper();
$autoFeatures = new AutoFeatures($serverScriptHelper);
$cceClient = $serverScriptHelper->getCceClient();

$oids = $cceClient->find("User", array("name" => $userNameField));
$iam = $serverScriptHelper->getLoginName();

// modify user
$attributes = array("fullName" => $fullNameField);
if($sortNameField)
  $attributes["sortName"]=$sortNameField;
if($passwordField)
  $attributes["password"] = $passwordField;
$obj = $cceClient->get($oids[0], "");
if (!$obj["desc_readonly"]) {
  $attributes["description"] = $userDescField;	
}

# don't set this attribute now if a siteadmin is trying to demote himself
if (isset($siteAdministrator) && ($siteAdministrator || (!$siteAdministrator && ($iam != $userNameField))))
  $attributes["capLevels"] = ($siteAdministrator ? '&siteAdmin&' : '');

if (isset($suspendUser))
  $attributes['ui_enabled'] = ($suspendUser) ? '0' : '1';

$cceClient->set($oids[0], "", $attributes);
$errors = $cceClient->errors();

for ($i = 0; $i < count($errors); $i++) {
	if ( ($errors[$i]->code == 2) && ($errors[$i]->key === "password"))
	{
		$errors[$i]->message = "[[base-user.error-invalid-password]]";
	}
}



// set quota
if(!$maxDiskSpaceField)
	$maxDiskSpaceField = -1;

$cceClient->set($oids[0], "Disk", array("quota" => $maxDiskSpaceField));
$errors = array_merge($errors, $cceClient->errors());

if ( isset( $group ) && $group != "" )
  $site = $group;

// handle autofeatures
list($userservices) = $cceClient->find("UserServices", array("site" => $site));
$af_errors = $autoFeatures->handle("modify.User", array("CCE_SERVICES_OID" =>
$userservices, "CCE_OID" => $oids[0]));
$errors = array_merge($errors, $af_errors);

$userNames = $cceClient->names("User");

// set group membership
$memberGroups = stringToArray($memberGroupsField);
$goids = $cceClient->find("Workgroup");
for($i = 0; $i < count($goids); $i++) {
  $group = $cceClient->get($goids[$i]);
  $members = stringToArray($group["members"]);

  if(in_array($group["name"], $memberGroups)) {
    // add user to group
    $found = false;
    for($j = 0; $j < count($members); $j++)
	if($members[$j] == $userNameField) {
	  $found = true;
	  break;
	}
    if(!$found)
	$members[] = $userNameField;
  }
  else {
    // remove user from group
    for($j = 0; $j < count($members); $j++)
	if($members[$j] == $userNameField) {
	  array_splice($members, $j, 1);
	  break;
	}
  }

  // update CCE
  $cceClient->set($goids[$i], "", array("members" => arrayToString($members)));
  $errors = array_merge($errors, $cceClient->errors());
}

// set email forwarding info
$cceClient->set($oids[0], "Email", array(
	"forwardEnable" => ($forwardEnableField ? 1 : 0), 
	"forwardEmail" => $forwardEmailField, 
	"forwardSave" => $forwardSaveField));
$errors = array_merge($errors, $cceClient->errors());

// set email aliases info

//Prune the duplicate email aliases
$emailAliasesFieldArray = $cceClient->scalar_to_array($emailAliasesField);
$emailAliasesFieldArray = array_unique($emailAliasesFieldArray);
$emailAliasesField = $cceClient->array_to_scalar($emailAliasesFieldArray);

// replace && with &, to avoid always getting a blank alias in the field
// in cce, this also skirts around dealing with browser issues
$emailAliasesField = str_replace("&&", "&", $emailAliasesField);
if ($emailAliasesField == '&') {
	$emailAliasesField = '';
}

$attributes = array("aliases" => $emailAliasesField);
#if ( isset($apop) ) 
#  $attributes["apop"] = $apop;

$cceClient->set($oids[0], "Email", $attributes);
$errors = array_merge($errors, $cceClient->errors());

// set vacation info
$cceClient->set($oids[0], "Email", array( 
	"vacationOn" => ($autoResponderField ? 1 : 0), 
	"vacationMsg" => $autoResponderMessageField));
$errors = array_merge($errors, $cceClient->errors());

# log the user out if they are trying to demote themself
if (isset($siteAdministrator) && !$siteAdministrator && ($iam == $userNameField)) {
  $cceClient->set($oids[0], "", array('capLevels' => ''));
  print($serverScriptHelper->toHandlerHtml("/logoutHandler.php"));
  $serverScriptHelper->destructor();
  exit(0);
}

print($serverScriptHelper->toHandlerHtml("/base/user/userList.php?group=" . $obj["site"], $errors, false));

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
