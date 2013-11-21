<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: network.php 1028 2007-06-25 16:57:31Z shibuya $

include_once("ServerScriptHelper.php");
include_once("Product.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-wizard", "/base/wizard/networkHandler.php");
$i18n = $serverScriptHelper->getI18n("base-wizard");

$product = new Product($cceClient);

// get settings
$systemObj = $cceClient->getObject("System");
$networkObj = $cceClient->getObject("System", array(), "Network");

$page = $factory->getPage();
$form = $page->getForm();
$formId = $form->getId();

$block = $factory->getPagedBlock("networkSettings");

$block->addFormField(
  $factory->getDomainName("hostNameField", $systemObj["hostname"]),
  $factory->getLabel("hostNameField")
);

$block->addFormField(
  $factory->getDomainName("domainNameField", $systemObj["domainname"]),
  $factory->getLabel("domainNameField")
);

if (!$product->isRaq())
{
	$block->addFormField(
  		$factory->getMultiChoice("internetField", array("none", "broadband", "lan", "narrowband"), array($networkObj["internetMode"])),
  	$factory->getLabel("internetField")
	);
}

$dns = $factory->getIpAddressList("dnsAddressesField", $systemObj["dns"]);
$dns->setOptional(true);
$block->addFormField(
  $dns,
  $factory->getLabel("dnsAddressesField")
);
print($page->toHeaderHtml());

if (!$product->isRaq())
{
	print "
<SCRIPT LANGUAGE=\"javascript\">
function flow_getNextItemId() {
  var form = document.$formId;

  switch(form.internetField.options[form.internetField.selectedIndex].value) {
    case \"none\":
      return \"base_wizardRegistration\";

    case \"broadband\":
      return \"base_wizardBroadband\";

    case \"lan\":
      return \"base_wizardLan\";

    case \"narrowband\":
      return \"base_wizardModem\";
  }
}
</SCRIPT>
";
}
?>
<?php print($i18n->getHtml("networkMessage")); ?>
<BR><BR>

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
