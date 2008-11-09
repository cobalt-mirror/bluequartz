<?php
include_once("ServerScriptHelper.php");
include_once("base/wizard/WizardSupport.php");
include_once("base/wizard/SettingsSheetPDF.php");

function ValueIfSet($str)
{
	return ($str == "") ? "<i>not set</i>" : $str;
}

global $WizError;

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-wizard");

$cceClient = $serverScriptHelper->getCceClient();

// get System
$system = $cceClient->getObject("System");

	echo "<PRE>";
	print_r($system);
	echo "</PRE>";

// get network information
$networkInfo = array();
$oids = $cceClient->find("Network");
$ni = 0;
for($i = count($oids) - 1; $i >= 0; $i--)
{
	$cceClient->set($oids[$i], "", array("refresh" => time()));

	// get object
	$network = $cceClient->get($oids[$i]);

	echo "<PRE>";
	print_r($network);
	echo "</PRE>";

	$device = $network["device"];

	$networkInfo[$ni++] = "IP Address ($device)";
	$networkInfo[$ni++] = ValueIfSet($network["ipaddr"]);
	$networkInfo[$ni++] = "Netmask ($device)";
	$networkInfo[$ni++] = ValueIfSet($network["netmask"]);
	$networkInfo[$ni++] = "MAC Address ($device)";
	$networkInfo[$ni++] = ValueIfSet($network["mac"]);
}
$networkInfo[$ni++] = "Gateway";
$networkInfo[$ni++] = ValueIfSet($system["gateway"]);
$networkInfo[$ni++] = "DNS";
$networkInfo[$ni++] = ValueIfSet($system["dns"]);

$hostname = $system["hostname"] . "." . $system["domainname"];
$productName = $system["productName"];
$serialNumber = $system["serialNumber"];
$prodSerialNumber = $system["productSerialNumber"];
$build = $system["productBuildString"];

$si = 0;
$systemInfo[$si++] = "Product";
$systemInfo[$si++] = ValueIfSet($productName);
$systemInfo[$si++] = "Serial Number";
$systemInfo[$si++] = ValueIfSet($prodSerialNumber);
// $systemInfo[$si++] = "HW Serial Number";
// $systemInfo[$si++] = ValueIfSet($serialNumber);

$settingSheet = new SettingsSheetPDF();
$settingSheet->WriteSettings($hostname, $build, $systemInfo, $networkInfo);

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

