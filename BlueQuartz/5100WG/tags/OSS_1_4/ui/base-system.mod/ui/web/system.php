<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: system.php 3 2003-07-17 15:19:15Z will $

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-system");
$i18n = $serverScriptHelper->getI18n("base-system");

// refresh information
$unique = microtime();
$cceClient->setObject("System", array("refresh" => $unique), "Disk");
$cceClient->setObject("System", array("refresh" => $unique), "Memory");

// get objects
$system = $cceClient->getObject("System");
$systemDisk = $cceClient->getObject("System", array(), "Disk");
$systemMemory = $cceClient->getObject("System", array(), "Memory");
$eth0 = $cceClient->getObject("Network", array("device" => "eth0"));
$eth1 = $cceClient->getObject("Network", array("device" => "eth1"));

$page = $factory->getPage();

$block = $factory->getPagedBlock("systemInformation");

$block->addFormField(
  $factory->getTextField("productNameField", $system["productName"], "r"),
  $factory->getLabel("productNameField")
);

// System may contain the literal "Uninitialized"
$formattedSerial = $system["productSerialNumber"];
if($formattedSerial == 'Uninitialized') {
  $formattedSerial = $i18n->get("serialUninitialized");
}

if($formattedSerial && !ereg('invalid', $formattedSerial)) {
  $block->addFormField(
    $factory->getTextField("productSerialNumberField",
      $formattedSerial, "r"),
    $factory->getLabel("productSerialNumberField")
  );
}

$block->addFormField(
  $factory->getTextField("serialNumberField", $system["serialNumber"], "r"),
  $factory->getLabel("serialNumberField")
);

$block->addFormField(
  $factory->getMacAddress("mac0Field", $eth0["mac"], "r"),
  $factory->getLabel("mac0Field")
);

$block->addFormField(
  $factory->getMacAddress("mac1Field", $eth1["mac"], "r"),
  $factory->getLabel("mac1Field")
);

// convert to GB
$diskTotal = round($systemDisk["disk1Total"]*10/1024/1024)/10;
$block->addFormField(
  $factory->getInteger("diskField", $diskTotal, "", "", "r"),
  $factory->getLabel("diskField")
);

$block->addFormField(
  $factory->getInteger("memoryField", $systemMemory["physicalMemTotal"], "", "", "r"),
  $factory->getLabel("memoryField")
);

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php 

print($block->toHtml());
print("<BR>");

  //add "Regsiter Now" button
  $button = $factory->getButton(
    "javascript: location.replace('/base/wizard/registration.php?notWizard=true')",
    "register");

 
  
  $webLink = $factory->getButton($i18n->get("webLink"), "webLinkText");
  $webLink->setTarget("_blank");
 
?>
<TABLE CELLSPACING="3" CELLPADDING="3">
	<TR>
		<?
		if(!$system["isRegistered"])
			print("<TD>" . $button->toHtml() . "</TD>\n");
		?>
		<TD><?  print($webLink->toHtml()); ?></TD>
	</TR>
</TABLE>


<P><BLOCKQUOTE>
<? echo $i18n->get("copyright"); ?>
</BLOCKQUOTE>


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

