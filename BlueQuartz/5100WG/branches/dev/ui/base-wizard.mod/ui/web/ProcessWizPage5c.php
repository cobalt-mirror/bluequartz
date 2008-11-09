<?php
// process the data on wizard page 1

include_once("ServerScriptHelper.php");
include_once("base/wizard/WizardSupport.php");

global $WizError;

global $WizModemConnectionMode;
global $WizModemAccountNameField;
global $WizModemPasswordField;
global $WizModemPhoneNumberField;
global $WizModemPulseField;
global $WizModemInitStringField;
global $WizModemLocalIpField;

WizDebugVar("WizModemConnectionMode");
WizDebugVar("WizModemAccountNameField");
WizDebugVar("WizModemPasswordField");
WizDebugVar("WizModemPhoneNumberField");
WizDebugVar("WizModemPulseField");
WizDebugVar("WizModemInitStringField");
WizDebugVar("WizModemLocalIpField");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$i18n = $serverScriptHelper->getI18n("base-wizard");

if ($WizModemConnectionMode != "off")
{
	if (strlen($WizModemAccountNameField) == "")
	{
		$WizError .= $i18n->getHtml("userNameField_help") . "<BR>";
	}
	if (strlen($WizModemPasswordField) == "")
	{
		$WizError .= $i18n->getHtml("modemPasswordField_empty") . "<BR>";
	}
	if (strlen($WizModemPhoneNumberField) == "")
	{
		$WizError .= $i18n->getHtml("modemPhoneField_help") . "<BR>";
	}
	if (strlen($WizModemInitStringField) == "")
	{
		$WizError .= $i18n->getHtml("initStringField_empty") . "<BR>";
	}
}

if ($WizError == "")
{
	$localIp = ($WizModemLocalIpField != "") ? $WizModemLocalIpField : "0.0.0.0";

	if ($WizModemPulseField == "")
	{
		$pulse = 0;
	}
	else
	{
		$pulse = 1;
	}

	$cceClient->setObject("System", array("connMode" => $WizModemConnectionMode, "phone" => $WizModemPhoneNumberField, "userName" => $WizModemAccountNameField, "password" => $WizModemPasswordField, "initStr" => $WizModemInitStringField, "localIp" => $localIp, "pulse" => $pulse), "Modem");
	$errors = $cceClient->errors();

	// make sure default gateway isn't set
	$cceClient->setObject("System", array("gateway" => ""));
	$errors = array_merge($errors, $cceClient->errors());

	$WizError = WizDecodeErrors($cceClient->errors());
}

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

