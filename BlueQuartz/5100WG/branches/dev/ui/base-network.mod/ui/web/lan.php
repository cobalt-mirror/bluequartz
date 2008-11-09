<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: lan.php 201 2003-07-18 19:11:07Z will $

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-network", "/base/network/lanHandler.php");
$i18n = $serverScriptHelper->getI18n("base-network");

// get settings
$oids = $cceClient->find("System");
$system = $cceClient->get($oids[0]);
$network = $cceClient->get($oids[0], "Network");
$eth1 = $cceClient->getObject("Network", array("device" => "eth1"));

$page = $factory->getPage();
$form = $page->getForm();
$formId = $form->getId();

$block = $factory->getPagedBlock("lanSettings");

$block->addFormField(
  $factory->getIpAddress("gatewayField", $system["gateway"]),
  $factory->getLabel("gatewayField")
);

$forward = $factory->getMultiChoice("forwardField", array("forwardNat", "forward", "forwardOff"));
if($network["ipForwarding"] && $network["nat"])
  $forward->setSelected(0, true);
else if($network["ipForwarding"])
  $forward->setSelected(1, true);
else
  $forward->setSelected(2, true);
$block->addFormField(
  $forward,
  $factory->getLabel("forwardField")
);

// secondary interface
$block->addDivider($factory->getLabel("secondaryInterface", false));

if ($eth1["enabled"]) {
        $ipaddr = $eth1["ipaddr"];
        $netmask = $eth1["netmask"];
} else {
        $ipaddr = "";
        $netmask = "";
}

$ipAddress2 = $factory->getIpAddress("ipAddressField2", $ipaddr);
$ipAddress2->setOptional(true);
$block->addFormField(
  $ipAddress2,
  $factory->getLabel("ipAddressField2")
);

$netMask2 = $factory->getIpAddress("netMaskField2", $netmask);
$netMask2->setOptional(true);
$block->addFormField(
  $netMask2,
  $factory->getLabel("netMaskField2")
);

$block->addFormField(
  $factory->getMacAddress("macAddressField2", $eth1["mac"], "r"),
  $factory->getLabel("macAddressField")
);

// retain orginal information
$block->addFormField($factory->getIpAddress("ipAddressOrig2", $ipaddr, ""), "");
$block->addFormField($factory->getIpAddress("netMaskOrig2", $netmask, ""), "");

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

if($current != "lan")
  $block->addButton($factory->getCancelButton("/base/network/wan.php"));
?>
<?php print($page->toHeaderHtml()); ?>

<?php
$button = $factory->getMultiButton(
                "changeMode",
                array(
                        "/base/network/wanNoneConfirm.php?select=1&current=$current",
                        "/base/network/broadband.php?select=1&current=$current",
                        "/base/network/lan.php?select=1&current=$current",
                        "/base/network/modem.php?select=1&current=$current"
                ),
                array(  "none",
                        "broadband",
                        "lan",
                        "narrowband"
                ));

if($select)
	$button->setSelectedIndex(2);

print($button->toHtml());
print("<BR><BR>");
?>
<?php print($block->toHtml()); ?>


<? 
if($SERVER_ADDR == $eth1["ipaddr"]) {	// admin connecting to  IP on this interface
	$isAdminEth = 1;
} else {
	$isAdminEth = 0;	
}

?>
<SCRIPT  LANGUAGE="javascript">
	var isAdminEth = <? echo $isAdminEth;?>;
</SCRIPT>


<SCRIPT LANGUAGE="javascript">
var oldFormSubmitHandler = document.<?php print($formId)?>.onsubmit;

function formSubmitHandler() {
	if(!oldFormSubmitHandler()) {
  		return false;
	}
	
	var form = document.<?php print($formId)?>;

		if(isAdminEth) {
			if(form.ipAddressField2.value != form.ipAddressOrig2.value || form.netMaskField2.value != form.netMaskOrig2.value){
				alert("<?php print($i18n->getJs("ethernetChanged"))?>");	
	   		}
   		}
   		return true;
}

document.<?php print($formId)?>.onsubmit = formSubmitHandler;

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

