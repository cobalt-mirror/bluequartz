<?php
/*
 * Copyright 2000-2002 Sun Microsystems, Inc.  All rights reserved.
 * $Id: userMod.php 1136 2008-06-05 01:48:04Z mstauber $
 */
include_once("ArrayPacker.php");
include_once("ServerScriptHelper.php");
include_once("Product.php");
include_once("uifc/PagedBlock.php");
include_once("AutoFeatures.php");
include_once("Capabilities.php");

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
$capabilities = new Capabilities($cceClient);
$product = new Product($cceClient);
$factory = $serverScriptHelper->getHtmlComponentFactory("base-user", "/base/user/userModHandler.php");

$i18n = $factory->i18n;

// get user
$oids = $cceClient->find("User", array("name" => $userNameField));
$useroid = $oids[0];

$user = $cceClient->get($oids[0]);
$userDisk = $cceClient->get($oids[0], "Disk");
$userEmail = $cceClient->get($oids[0], "Email");
$group = $user["site"];

// Find out if FTP access for non-siteAdmins is enabled or disabled for this site:
list($ftpvsite) = $cceClient->find("Vsite", array("name" => $group));
$ftpPermsObj = $cceClient->get($ftpvsite, 'FTPNONADMIN');
$ftpnonadmin = $ftpPermsObj['enabled'];

$page = $factory->getPage();

$block = new PagedBlock($page, "modifyUser", $factory->getLabel("modifyUser", false, array("userName" => $userNameField)));
//$block = $factory->getPagedBlock($page, "modifyUser", $factory->getLabel("modifyUser", false, array("userName" => $userNameField)));

//$block = $factory->getPagedBlock("modifyUser", array("account", "email"));

//$factory->getLabel("modifyUser", false, array("userName" => $userNameField)));

//$block = $factory->getPagedBlock("yumgui_head", array("yumTitle", "Settings", "Logs"));

$block->addPage("account", $factory->getLabel("account"));
$block->addPage("email", $factory->getLabel("email"));

$block->addFormField(
  $factory->getFullName("fullNameField", $user["fullName"]),
  $factory->getLabel("fullNameField"),
  "account"
);

$prop=$i18n->getProperty("needSortName");
if($prop=="yes"){
	$sortName=$factory->getFullName("sortNameField",$user["sortName"]);
	$sortName->setOptional('silent');
	$block->addFormField(
          $sortName,
	  $factory->getLabel("sortNameField"),
	  "account"
	);
}

$password = $factory->getPassword("passwordField");
$password->setOptional(true);
$block->addFormField(
  $password,
  $factory->getLabel("newPasswordField"),
  "account"
);


// Load site quota
if ($group)
{
    list($vsite_oid) = $cceClient->find('Vsite', array("name" => $group));
    $disk = $cceClient->get($vsite_oid, 'Disk');
    $max_quota = $disk['quota'];
}

$site_quota = ($max_quota == -1 ? 499999999 : $max_quota);

$quota = $factory->getInteger(
                        "maxDiskSpaceField",
                        ($userDisk['quota'] != -1 ? $userDisk['quota'] : ""),
                        1, $site_quota);
// quota is not optional for site members on raqs
if (!isset($group)) {
	$quota->setOptional('silent');
}

if($max_quota && $max_quota != -1)
    $quota->showBounds(1);


$block->addFormField(
  $quota,
  $factory->getLabel("maxDiskSpaceField"),
  "account"
);


// add autofeatures
if ( isset($group) && $group != "" ) {
  list($userServices) = $cceClient->find("UserServices", array("site" => $group));
  list($vsite) = $cceClient->find("Vsite", array("name" => $group));
  $autoFeatures->display($block, "modify.User", array("CCE_SERVICES_OID" => $userServices, "CCE_OID" => $useroid, "VSITE_OID" => $vsite));

  $block->addFormField(
    $factory->getBoolean("siteAdministrator", $capabilities->getAllowed('siteAdmin', $useroid) ),
    $factory->getLabel("siteAdministratorField"),
    "account"
  );

  $block->addFormField(
    $factory->getBoolean("dnsAdministrator", $capabilities->getAllowed('dnsAdmin', $useroid) ),
    $factory->getLabel("dnsAdministratorField"),
    "account"
  );

  $block->addFormField($factory->getTextField("group", $group, ""));

} else {
    list($userServices) = $cceClient->find("UserServices");
    $autoFeatures->display(
            $block, "modify.User", 
            array("CCE_SERVICES_OID" => $userServices, "CCE_OID" => $useroid));
}

// make group list if Workgroup exists
$cceClient->names("Workgroup");
if (count($cceClient->errors()) == 0)
{
    $groups = $cceClient->getObjects("Workgroup");
    $memberGroupNames = array();
    $allGroupNames = array();
    for($i = 0; $i < count($groups); $i++) 
    {
        $group = $groups[$i];
        if(isInArrayString($user["name"], $group["members"]))
            $memberGroupNames[] = $group["name"];
  
        $allGroupNames[] = $group["name"];
    }
    $memberGroupNamesString = arrayToString($memberGroupNames);
    $allGroupNamesString = arrayToString($allGroupNames);
  
    $groupSelector = $factory->getSetSelector("memberGroupsField", $memberGroupNamesString, $allGroupNamesString, "memberGroups", "allGroups");
    $groupSelector->setOptional(true);
    $block->addFormField(
        $groupSelector,
        $factory->getLabel("memberGroupsField"),
        "account"
        );
}

$block->addFormField(
  $factory->getBoolean("emailDisabled", $user["emailDisabled"]),
  $factory->getLabel("emailDisabled"),
  "email"
);

$emailAliases = $factory->getEmailAliasList("emailAliasesField", $userEmail["aliases"]);
$emailAliases->setOptional(true);
$block->addFormField(
  $emailAliases,
  $factory->getLabel("emailAliasesField"),
  "email"
);

// Start: PHP5 work around against data loss in composite form fields:

if ($_PagedBlock_selectedId_modifyUser == "email") {

    // This displays when we're on the "Email" tab:

    $forwardEnable = $factory->getOption("forwardEnable", $userEmail["forwardEnable"]);
    $forward_emails =& $factory->getEmailAddressList("forwardEmailField", $userEmail["forwardEmail"]);
    $forward_emails->setOptional('silent');

    $forwardEnable->addFormField(
	$forward_emails,
	$factory->getLabel("forwardEmailField")
    );
    $forwardEnable->addFormField(
        $factory->getBoolean("forwardSaveField", $userEmail["forwardSave"]),
        $factory->getLabel("forwardSaveField")
    );
  
    $forward = $factory->getMultiChoice("forwardEnableField");
    $forward->addOption($forwardEnable);
    $block->addFormField(
	$forward,
	$factory->getLabel("forwardEnableField"),
	"email"
    );

    $enableAutoResponder = $factory->getOption("enableAutoResponderField", $userEmail["vacationOn"]);
    $enableAutoResponder->addFormField($factory->getTextBlock("autoResponderMessageField", $userEmail["vacationMsg"]), $factory->getLabel("autoResponderMessageField"));
    $autoResponder = $factory->getMultiChoice("autoResponderField");
    $autoResponder->addOption($enableAutoResponder);
    $block->addFormField(
	$autoResponder,
	$factory->getLabel("autoResponderField"),
	"email"
    );

}
else {

    // When we're on the "Account" tab we instead input hidden fields with our data:

    $block->addFormField(
	$factory->getEmailAddressList("forwardEmailField", $userEmail["forwardEmail"], 'r'),
	$factory->getLabel("forwardEmailField"),
	"Hidden"
    );

    $block->addFormField(
	$factory->getBoolean("forwardSaveField", $userEmail["forwardSave"], 'r'),
	$factory->getLabel("forwardSaveField"),
	"Hidden"
    );

    $block->addFormField(
	$factory->getBoolean("forwardEnableField", $userEmail["forwardEnable"], 'r'),
	$factory->getLabel("forwardEnableField"),
	"Hidden"
    );

    $block->addFormField(
	$factory->getBoolean("enableAutoResponderField", $userEmail["vacationOn"], 'r'),
	$factory->getLabel("enableAutoResponderField"),
	"Hidden"
    );

    $block->addFormField(
	$factory->getTextBlock("autoResponderMessageField", $userEmail["vacationMsg"], 'r'),
	$factory->getLabel("autoResponderMessageField"),
	"Hidden"
    );

}

// End: PHP5 related work around

$block->addFormField(
  $factory->getBoolean("suspendUser", !$user["ui_enabled"]),
  $factory->getLabel("suspendUser"),
  "account"
);

$flags = $user["desc_readonly"] ? "r" : "rw";
$textblock = $factory->getTextBlock("userDescField", 
	     $i18n->interpolate($user["description"]), $flags);
$textblock->setWidth(2*$textblock->getWidth());
if (!$user["desc_readonly"]) {
  $textblock->setOptional(true);
}
$block->addFormField(
  $textblock,
  $factory->getLabel("userDescField"),
  "account"
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton($factory->getCancelButton("/base/user/userList.php?group=$group"));

$userName = $factory->getUserName("userNameField", $userNameField, "");

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php print($block->toHtml()); 

// Hidden ftpForNonSiteAdmins flag:
print "<br><input type=\"hidden\" name=\"ftpForNonSiteAdmins\" value=\"" . $ftpnonadmin . "\"><br>";

?>

<?php print($userName->toHtml()); ?>

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
