<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: userAddHandler.php 1117 2008-05-14 16:18:22Z mstauber $

include_once("ArrayPacker.php");
include_once("ServerScriptHelper.php");
include_once("uifc/Stylist.php");
include_once("I18n.php");
include_once("CceError.php");
include_once("AutoFeatures.php");
include_once("Error.php");
include_once("./user.inc");

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
$i18n = new I18n("base-user",$HTTP_ACCEPTED_LANGS);

$errors = array();

$group = $group ? $group : "";

$stylist = new Stylist();
// we arbitrarily pick a locale here because we discard the i18ned names
// anyways
$styleIdToName = $stylist->getAllResources("en");

// get IDs of all available styles
$styleIds = array_keys($styleIdToName);

// style preference is the "BlueOnyx" if it is available, the first available
// style of nothing if no styles are available
$firstChoice = "BlueOnyx";
$stylePreference = "";
if(count($styleIds) > 0)
{
    if(in_array($firstChoice, $styleIds))
    {
        $stylePreference = $firstChoice;
    }
    else
    {
        $stylePreference = $styleIds[0];
    }
}

// create user
if($sortNameField)
{
    $sortby = $sortNameField;
}
else
{
    $sortby = "";
}

// handle setting the proper volume for vsite users
if (isset($group))
{
    $vsite = $cceClient->getObject("Vsite", array("name" => $group));
    $volume = $vsite['volume'];
}
else
{
    // default to wherever the home directory is
    $volume = "/home";
}

// Handle FTP access clauses:
if ($ftpForNonSiteAdmins == "0") {
    $hasNoFTPaccess = "1";
}
else {
    $hasNoFTPaccess = "0";
}

//echo "SiteAdminFlag: $siteAdministrator <br>";

if ($siteAdministrator == "1") {
    $hasNoFTPaccess = "0";
}


$attributes = array(
                "name" => $userNameField, 
                "sortName" => $sortby, 
                "fullName" =>$fullNameField, 
                "password" => $passwordField, 
		"emailDisabled" => $emailDisabled,
		"ftpDisabled" => $hasNoFTPaccess,
                "localePreference" => "browser", 
                "stylePreference" => $stylePreference, 
                "volume" => $volume,
                "description" => $userDescField);

if (isset($group))
{
    $attributes["site"] = $group;
    $attributes['enabled'] = ($vsite['suspend'] ? 0 : 1);
}

if (isset($siteAdministrator))
{
    $attributes["capLevels"] = ($siteAdministrator ? '&siteAdmin&' : '');
}

if (isset($dnsAdministrator))
{
    $attributes["capLevels"] .= ($dnsAdministrator ? '&dnsAdmin&' : '');
}

// dirty trick
$attributes["capLevels"] = str_replace("&&", "&", $attributes["capLevels"]);

// Username = Password? Baaaad idea!
if (strcasecmp($userNameField, $passwordField) == 0) {
        $attributes["password"] = "1";
        $error_msg = "[[base-user.error-password-equals-username]] [[base-user.error-invalid-password]]";
        $errors[] = new Error($error_msg);
}

// Open CrackLib Dictionary for usage:
$dictionary = crack_opendict('/usr/share/dict/pw_dict') or die('Unable to open CrackLib dictionary');

// Perform password check with cracklib:
$check = crack_check($dictionary, $passwordField);

// Retrieve messages from cracklib:
$diag = crack_getlastmessage();

if ($diag == 'strong password') {
        // Nothing to do. Cracklib thinks it's a good password.
}
else {
        $attributes["password"] = "1";
        $errors[] = new Error("[[base-user.error-password-invalid]]" . $diag);
}

// Close cracklib dictionary:
crack_closedict($dictionary);

if (!isReservedUsername($userNameField)) {
	$oid = $cceClient->create("User", $attributes);
	$errors = array_merge($errors, $cceClient->errors());
} else {
	$errors[] = new Error('[[base-user.userNameAlreadyTaken]]');
}

// if user was created without errors, setup the rest of their information
// everything from now on is non-fatal, so go back to the user list unless
// the create above failed
if($oid == 0 || count($errors) > 0) 
{
    // check for cce reject of password (too short)
    for ($i = 0; $i < count($errors); $i++) 
    {    
	if (($errors[$i]->code == 2) && ($errors[$i]->key === "password")) 
	{
		$errors[$i]->message = "[[base-user.error-invalid-password]]";
	}

	// check for username collision, add rejected value to email aliases
	if (($errors[$i]->code == 5) && (preg_match('/userNameSuggest/', $errors[$i]->message)))
	{
	    if($i18n->getProperty("suggestUsername") == "yes") 
	    {
		// We would like to add the collided username to the alias list
		// if possible.  
        	$oids = $cceClient->find("User", array("name" => $userNameField, "site" => $group));
        	$moids = $cceClient->find("MailList", array("site" => $group, "name" => $userNameField));

		// FIXME: missing test for in-site user email aliases
		if(!$oids && !$moids) {
                    // toHandlerHtml will pickup the cgi variables for data preservation
                    global $HTTP_POST_VARS;

	    	    // The following regex tests for identical use of the username in the alias field:
	    	    if(!preg_match('/[^a-zA-Z0-9\-\_]'.$userNameField.'[^a-zA-Z0-9\-\_]/',$emailAliasesField)) 
	   	    {
	  	        $HTTP_POST_VARS['emailAliasesField'] = $userNameField.$emailAliasesField;
	            }
		}
	    }
	    else
	    {
		$errors[$i]->message = '[[base-user.userNameAlreadyTaken]]';
	    }
	}
    }

    // fatal errors, so return to user add
    print $serverScriptHelper->toHandlerHtml("/base/user/userAdd.php", $errors);
}
else
{
    // set quota
    if(!$maxDiskSpaceField)
	    $maxDiskSpaceField = -1;

    $cceClient->set($oid, "Disk", 
            array("quota" => $maxDiskSpaceField));
    $errors = array_merge($errors, $cceClient->errors());
  
    // add to groups
    $groupNames = stringToArray($memberGroupsField);
    for($i = 0; $i < count($groupNames); $i++) 
    {
        $oids = $cceClient->find("Workgroup", array("name" => $groupNames[$i]));
        $group = $cceClient->get($oids[0]);
        $members = stringToArray($group["members"]);
        $members[] = $userNameField;
        $cceClient->set($oids[0], "", array("members" => arrayToString($members)));
        $errors = array_merge($errors, $cceClient->errors());
    }

    $userNames = $cceClient->names("User");
    if (in_array("AddressbookEntry", $userNames)) 
    {
        // add AddressbookEntry
        $cceClient->set($oid, "AddressbookEntry", array("owner" => ""));
        $errors = array_merge($errors, $cceClient->errors());
    }

    if (isset( $group ) && $group != "")
        $site = $group;

    list($userservices) = $cceClient->find("UserServices", array("site" => $site));
    $af_errors = $autoFeatures->handle("create.User", array("CCE_SERVICES_OID" => $userservices, "CCE_OID" => $oid));
    $errors = array_merge($errors, $af_errors);

    // set email information
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

    $cceClient->set($oid, "Email", $attributes);
    $errors = array_merge($errors, $cceClient->errors());

    // return to user list even if there are errors here
    // this is kind of broad, but if the user gets created they
    // can't do user add again
    print($serverScriptHelper->toHandlerHtml("/base/user/userList.php?group=$group", $errors, false));
}

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
