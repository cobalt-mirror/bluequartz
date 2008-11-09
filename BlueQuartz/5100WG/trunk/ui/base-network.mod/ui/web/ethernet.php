<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: ethernet.php 3 2003-07-17 15:19:15Z will $

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-network", "/base/network/ethernetHandler.php");
$i18n = $serverScriptHelper->getI18n("base-network");

// get settings
$system = $cceClient->getObject("System");
$eth0 = $cceClient->getObject("Network", array("device" => "eth0"));
$eth1 = $cceClient->getObject("Network", array("device" => "eth1"));

$page = $factory->getPage();
$form = $page->getForm();
$formId = $form->getId();

$block = $factory->getPagedBlock("tcpIpSettings");

$block->addFormField(
  $factory->getDomainName("hostNameField", $system["hostname"]),
  $factory->getLabel("hostNameField")
);

$block->addFormField(
  $factory->getDomainName("domainNameField", $system["domainname"]),
  $factory->getLabel("domainNameField")
);

$dns = $factory->getIpAddressList("dnsAddressesField", $system["dns"]);
$dns->setOptional(true);
$block->addFormField(
  $dns,
  $factory->getLabel("dnsAddressesField")
);

// primary interface
$block->addDivider($factory->getLabel("primaryInterface", false));

$block->addFormField(
  $factory->getIpAddress("ipAddressField1", $eth0["ipaddr"]),
  $factory->getLabel("ipAddressField1")
);

$block->addFormField(
  $factory->getIpAddress("netMaskField1", $eth0["netmask"]),
  $factory->getLabel("netMaskField1")
);

$block->addFormField(
  $factory->getMacAddress("macAddressField1", $eth0["mac"], "r"),
  $factory->getLabel("macAddressField")
);

// retain orginal information
$block->addFormField($factory->getIpAddress("ipAddressOrig1", $eth0["ipaddr"], ""), "");
$block->addFormField($factory->getIpAddress("netMaskOrig1", $eth0["netmask"], ""), "");
$block->addFormField($factory->getTextField("bootProtoField1", $eth0["bootproto"], ""), "");

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
$block->addFormField($factory->getTextField("bootProtoField2", $eth1["bootproto"], ""), "");
$block->addFormField($factory->getBoolean("enabled2", $eth1["enabled"], ""), "");

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

// static routes button
$routeButton = $factory->getButton("/base/network/routes.php", "routes");
// port forwarding button
$portFwdButton = $factory->getButton("/base/portforward/list.php", "portFwd");

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<table><tr>
<td><?php print($routeButton->toHtml()); ?></td>
<td><?php print($portFwdButton->toHtml()); ?></td>
</tr></table>
<BR>

<?php print($block->toHtml()); ?>

<? 
if($SERVER_ADDR == $eth0["ipaddr"]) {	// admin connecting to  IP on this interface
	$isAdminEth = 0;
} else if($SERVER_ADDR == $eth1["ipaddr"]){
	$isAdminEth = 1;	
} else
	$isAdminEth = -1;

?>
<SCRIPT  LANGUAGE="javascript">
	var isAdminEth = <? echo $isAdminEth;?>;
</SCRIPT>


<SCRIPT LANGUAGE="javascript">
var oldFormSubmitHandler = document.<?php print($formId)?>.onsubmit;

function formSubmitHandler() {
  if(!oldFormSubmitHandler())
    return false;

  var form = document.<?php print($formId)?>;

  var isChanged1 = form.ipAddressField1.value != form.ipAddressOrig1.value || form.netMaskField1.value != form.netMaskOrig1.value;

  var isChanged2 = form.ipAddressField2.value != form.ipAddressOrig2.value || form.netMaskField2.value != form.netMaskOrig2.value;

  if(isChanged1 && form.bootProtoField1.value == "dhcp")
    // restore settings if user do not want to turn off DHCP
    if(!confirm("<?php print($i18n->getJs("confirmNoDhcp", "", array('interface' => '[[base-network.primaryInterface]]')))?>")) {
      form.ipAddressField1.value = form.ipAddressOrig1.value;
      form.netMaskField1.value = form.netMaskOrig1.value;
      isChanged1 = false;
    }
    else
      form.bootProtoField1.value = "none";

  if(isChanged2 && form.bootProtoField2.value == "dhcp" && form.enabled2.value == '1')
	if(!confirm("<?php print($i18n->getJs("confirmNoDhcp", "", array('interface' => '[[base-network.secondaryInterface]]')))?>")) {
		form.ipAddressField2.value = form.ipAddressOrig2.value;
		form.netMaskField2.value = form.netMaskOrig2.value;
		isChanged1 = false;
	} else
		form.bootProtoField2.value = "none";

  if((isChanged1 && isAdminEth == 0) || (isChanged2 && isAdminEth == 1))
    alert("<?php print($i18n->getJs("ethernetChanged"))?>");

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

