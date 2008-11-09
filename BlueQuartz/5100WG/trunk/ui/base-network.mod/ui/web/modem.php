<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: modem.php 3 2003-07-17 15:19:15Z will $

include("ArrayPacker.php");
include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-network", "/base/network/modemHandler.php");
$i18n = $serverScriptHelper->getI18n("base-network");

// get settings
$oids = $cceClient->find("System");
$modem = $cceClient->get($oids[0], "Modem");
$network = $cceClient->get($oids[0], "Network");

$page = $factory->getPage();

$block = $factory->getPagedBlock("modemSettings");

$serverScriptHelper->shell("/usr/sausalito/sbin/modem_status.pl" , $output);
$output = ereg_replace("\n", "", $output);
$formatted_output = $i18n->get( "$output" );
$block->addFormField(
  $factory->getTextField("modemConnStatus", $formatted_output, 'r'),
  $factory->getLabel("modemConnStatus")
);

$block->addFormField(
  $factory->getMultiChoice("modemConnModeField", array("demand", "on", "off"), array($modem["connMode"])),
  $factory->getLabel("modemConnModeField")
);

####################################
# PPP dial-out windowing

$dialtimes = stringToArray($modem["dialhours"]);
sort($dialtimes);
$dialtimesString = arrayToString($dialtimes);

$nodialtimes = array();
$timelabels = array();
$valuelabelsString = '';

for($i = 0; $i < 24; $i++) {
  $nodialtimes[$i] = $i;
  $timelabels[$i] = $i18n->get("modem_hour_$i");
}
for($i = 0; $i < 24; $i++) {
  if(isInArrayString($i, $dialtimesString)) {
    if($valuelabelsString == '') {
      $valuelabelsString = $timelabels[$i];
    } else {
      $valuelabelsString .= '&'.$timelabels[$i];
    }
  }
}

sort($nodialtimes);
$nodialtimesString = arrayToString($nodialtimes);
$timelabelsString = arrayToString($timelabels);

// getSetSelector($id, 
//   $value, $entries, $valueLabelId = "", $entriesLabelId = "", $access = "rw", 
//   $valueVals="",$entriesVals="")
$memberSelector = $factory->getSetSelector("dialoutWindowing", 
  $valuelabelsString, $timelabelsString, "dialTimes", "noDialTimes", "rw", 
  $dialtimesString, $nodialtimesString);

$block->addFormField(
  $memberSelector,
  $factory->getLabel("dialoutWindowing")
);

###################################

$block->addFormField(
  $factory->getTextField("modemPhoneField", $modem["phone"]),
  $factory->getLabel("modemPhoneField")
);

$block->addFormField(
  $factory->getTextField("modemUserNameField", $modem["userName"]),
  $factory->getLabel("modemUserNameField")
);

$password = $factory->getPassword("modemPasswordField");
$password->setOptional('silent');

$block->addFormField(
  $password,
  $factory->getLabel("modemPasswordField")
);

$block->addFormField(
  $factory->getTextField("modemInitStrField", $modem["initStr"]),
  $factory->getLabel("modemInitStrField")
);

// do not show 0.0.0.0
$ip = $modem["localIp"];
if($ip == "0.0.0.0")
  $ip = "";
$modemIp = $factory->getIpAddress("modemIpField", $ip);
$modemIp->setOptional(true);
$block->addFormField(
  $modemIp,
  $factory->getLabel("modemIpField")
);

# pap/chap auth select

// $block->addFormField(
//   $factory->getMultiChoice("modemAuthModeField", array("pap", "chap"), array($modem["authMode"])),
//   $factory->getLabel("modemAuthModeField")
// );

$remoteHost = $factory->getDomainName("modemAuthHostField", $modem["serverName"]);
$remoteHost->setOptional(true);
$block->addFormField(
  $remoteHost,
  $factory->getLabel("modemAuthHostField")
);

# Fin pap/chap


$block->addFormField(
  $factory->getMultiChoice("modemSpeedField", array(115200, 57600, 38400, 28800, 19200, 9600, 2400, 300), array($modem["speed"])),
  $factory->getLabel("modemSpeedField")
);

$block->addFormField(
  $factory->getBoolean("modemPulseField", $modem["pulse"]),
  $factory->getLabel("modemPulseField")
);

// for modem we need to default to having forwarding and masquerading on
// so if we are switching our internet mode to narrowband default this to on
$block->addFormField(
  $factory->getBoolean("natField", (($network["internetMode"] != 'narrowband') ? 1 : $network["nat"]) ),
  $factory->getLabel("natField")
);

$block->addFormField($factory->getBoolean("test", false, ''));

$wait = $i18n->get("[[palette.wait]]");
$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton($factory->getButton("javascript:
if (document.form.onsubmit()) { 
	top.code.info_show('$wait', 'wait'); 
	document.form._save.value = 1; 
	document.form.test.value = 1;
	document.form.submit(); 
}", "saveTest"));

if($current != "narrowband")
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
	$button->setSelectedIndex(3);

print($button->toHtml());
print("<BR><BR>");
?>
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

