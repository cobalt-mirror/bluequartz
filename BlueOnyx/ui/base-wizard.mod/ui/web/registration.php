<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: registration.php 1050 2008-01-23 11:45:43Z mstauber $

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-wizard", "/base/wizard/registrationHandler.php");
$i18n = $serverScriptHelper->getI18n("base-wizard");
$page = $factory->getPage();
$page->setOnLoad('top.code.flow_setPageLoaded(true);');
$form = $page->getForm();
$formId = $form->getId();

$block = false;

$online = 0;
$data = "";
$serverScriptHelper->shell(
  "/usr/bin/nslookup -silent -timeout=4 mail.cobalt.com", 
  $data);
if (preg_match("/Name:\s+mail\.cobalt\.com/", $data)) {
    // we're online, proceed:
    $online = 1;

    $block = $factory->getPagedBlock("registration");

    $fullName = $factory->getFullName("fullNameField");
    $fullName->setOptional('silent');

    $block->addFormField(
      $fullName,
      $factory->getLabel("fullNameField")
    );

    $title = $factory->getTextField("titleField");
    $title->setOptional('silent');
    $block->addFormField(
      $title,
      $factory->getLabel("titleField")
    );

    $company = $factory->getTextField("companyField");
    $company->setOptional('silent');
    $block->addFormField(
      $company,
      $factory->getLabel("companyField")
    );

    $address = $factory->getTextBlock("addressField");
    $address->setOptional('silent');
    $block->addFormField(
      $address,
      $factory->getLabel("addressField")
    );

    $country = $factory->getTextField("countryField");
    $country->setOptional('silent');
    $block->addFormField(
      $country,
      $factory->getLabel("countryField")
    );

    $email = $factory->getEmailAddress("emailField");
    $email->setOptional('silent');
    $block->addFormField(
      $email,
      $factory->getLabel("emailField")
    );

    $phone = $factory->getTextField("phoneField");
    $phone->setOptional('silent');
    $block->addFormField(
      $phone,
      $factory->getLabel("phoneField")
    );


} else {
    // we're not online ... tell the user to register by snail mail:
}

if($notWizard=="true" && $block){

  $notWiz=$factory->getTextField("notWizard","true");
  $notWiz->setAccess(""); //hidden
  $block->addFormField($notWiz, "");

  $block->addButton($factory->getButton($page->getSubmitAction(), "regSubmit"));
  $block->addButton($factory->getButton("/base/system/system.php", "regCancel"));
}

?>
<?php print($page->toHeaderHtml()); ?>
<?php 
if($notWizard == "true")
	$button = "[[base-wizard.regSubmit]]";
else
	$button = "[[base-wizard.regRightArrow]]";

if ($online) {
  print($i18n->get(
    "regText", 
    "base-wizard", 
    array( "button" => $button )));
} else {
  print($i18n->get(
    "regTextOffline",
    "base-wizard",
    array( "button" => $button )));
}

?>
<P>
<?php if ($block) { print($block->toHtml()); ?>
<SCRIPT LANGUAGE="javascript">
var oldFormSubmitHandler = document.<?php print($formId)?>.onsubmit;

 function formSubmitHandler() {
   if (!oldFormSubmitHandler()) {
     return false;
   }

   var form = document.<?php print($formId)?>;
   if (!form.fullNameField.value &&
       !form.titleField.value &&
       !form.companyField.value &&
       !form.addressField.value &&
       !form.countryField.value &&
       !form.emailField.value &&
       !form.phoneField.value) {
     return confirm(<?php print("\"" . $i18n->get("empty_reg", "base-wizard")  . "\"")  ?> );
   }
   
   return true;
 }

 document.<?php print($formId)?>.onsubmit = formSubmitHandler;


</SCRIPT>

<?php } ?>
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
