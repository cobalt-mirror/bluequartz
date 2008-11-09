<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: lan.php 1028 2007-06-25 16:57:31Z shibuya $

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-wizard", "/base/wizard/lanHandler.php");
$i18n = $serverScriptHelper->getI18n("base-wizard");

// get settings
$systemObj = $cceClient->getObject("System");
$oids = $cceClient->find("Network", array("device" => "eth1"));
$oid = $oids[0];
$cceClient->set($oid, "", array(
	"refresh" => time()));
$eth1 = $cceClient->get($oid);

$page = $factory->getPage();
$form = $page->getForm();
$formId = $form->getId();

$block = $factory->getPagedBlock("lanSettings");

$block->addFormField(
  $factory->getIpAddress("gatewayField", $systemObj["gateway"]),
  $factory->getLabel("gatewayField")
);

// secondary interface
$block->addDivider($factory->getLabel("secondaryInterface", false));

$ipAddress2 = $factory->getIpAddress("ipAddressField2", 
					($eth1["enabled"] ? $eth1["ipaddr"] : ""));
$ipAddress2->setOptional(true);
$block->addFormField(
  $ipAddress2,
  $factory->getLabel("ipAddressField2")
);

$netMask2 = $factory->getIpAddress("netMaskField2", 
					($eth1["enabled"] ? $eth1["netmask"] : ""));
$netMask2->setOptional(true);
$block->addFormField(
  $netMask2,
  $factory->getLabel("netMaskField2")
);

$block->addFormField(
	$factory->getIpAddress("ipAddressOrig2",
				($eth1["enabled"] ? $eth1["netmask"] : ""), "")
	);
$block->addFormField(
	$factory->getIpAddress("netMaskOrig2",
				($eth1["enabled"] ? $eth1["netmask"] : ""), "")
        );

$block->addFormField(
  $factory->getMacAddress("macAddressField2", $eth1["mac"], "r"),
  $factory->getLabel("macAddressField")
);
?>
<?php print($page->toHeaderHtml()); ?>

<?php print($i18n->getHtml("lanMessage")); ?>
<BR><BR>

<?php print($block->toHtml()); ?>

<SCRIPT LANGUAGE="javascript">
var oldFieldSubmitHandler = document.<?php print($formId)?>.netMaskField2.submitHandler;

function netMask2SubmitHandler() {
  if(oldFieldSubmitHandler != null) {
    var ret = oldFieldSubmitHandler(document.<?php print($formId)?>.netMaskField2);
    if(!ret)
      return ret;
  }

  var form = document.<?php print($formId)?>;

  if(form.ipAddressField2.value == "" && form.netMaskField2.value != "") {
    top.code.error_invalidElement(form.netMaskField2, "<?php print($i18n->getJs("ipAddressNetMaskMismatch")) ?>");
    return false;
  }
  if(form.ipAddressField2.value != "" && form.netMaskField2.value == "") {
    top.code.error_invalidElement(form.ipAddressField2, "<?php print($i18n->getJs("ipAddressNetMaskMismatch")) ?>");
    return false;
  }

  return true;
}

document.<?php print($formId)?>.netMaskField2.submitHandler = netMask2SubmitHandler;
</SCRIPT>

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
