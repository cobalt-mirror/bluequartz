<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: personalAccount.php 1398 2010-03-10 10:41:57Z shibuya $

include_once("ArrayPacker.php");
include_once("ServerScriptHelper.php");
include_once("uifc/PagedBlock.php");
include_once("uifc/Label.php");
include_once("uifc/Option.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-user", "/base/user/personalAccountHandler.php");
$stylist = $serverScriptHelper->getStylist();
$i18n = $serverScriptHelper->getI18n("palette");

$loginName = $serverScriptHelper->getLoginName();
$browserLocalePreference = $serverScriptHelper->getLocalePreference($HTTP_ACCEPT_LANGUAGE);
$stylePreference = $serverScriptHelper->getStylePreference();

$admin=$cceClient->getObject("User", array("name"=>$loginName));
$localePreference=$admin["localePreference"];

// get objects
$user = $cceClient->getObject("User", array("name" => $loginName));
$system = $cceClient->getObject("System");

$page = $factory->getPage();

$block = new PagedBlock($page, "accountSettings", $factory->getLabel("accountSettings", false, array("userName" => $loginName)));

$errors = $serverScriptHelper->getErrors();
if (count($errors) > 0) {
  $block->processErrors($errors, array('fullName' => 'fullNameField', 'localePreference' => 'languageField', 'password' => 'newPasswordField', 'stylePreference' => 'styleField'));
}

$block->addFormField(
  $factory->getFullName("fullNameField", $user["fullName"]),
  $factory->getLabel("fullNameField")
);

// find all possible locales
$possibleLocales = stringToArray($system["locales"]);
/*
 * don't show browser option for admin, because then it becomes unclear
 * what the system locale is.
 */
if ($serverScriptHelper->getLoginName() != "admin") {
	$possibleLocales = array_merge(array("browser"), $possibleLocales);
}

$locale = $factory->getLocale("languageField", $localePreference);
$locale->setPossibleLocales($possibleLocales);
$block->addFormField(
  $locale,
  $factory->getLabel("languageField")
);

// make style
$availableStyles = $stylist->getAllResources($browserLocalePreference);
$keys = array_keys($availableStyles);
// only need to select if more than 1 style
if(count($keys) > 1) {
  $style = $factory->getMultiChoice("styleField");
  for($i = 0; $i < count($keys); $i++)
    $style->addOption(new Option(new Label($page, $availableStyles[$keys[$i]], ""), $keys[$i], $stylePreference == $keys[$i]));

  $block->addFormField(
    $style,
    $factory->getLabel("styleField")
  );
}

$passwordField = $factory->getPassword("newPasswordField");
$passwordField->setOptional(true);
$block->addFormField(
  $passwordField,
  $factory->getLabel("newPasswordField")
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php print($block->toHtml()); ?>

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
