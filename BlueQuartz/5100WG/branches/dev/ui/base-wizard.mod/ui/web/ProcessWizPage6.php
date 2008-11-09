<?php
// process the data on wizard page 1

include_once("ServerScriptHelper.php");
include_once("base/wizard/WizardSupport.php");

global $WizError;

global $WizFullNameField;
global $WizEmailAddressField;
global $WizCompanyField;
global $WizPhoneNumberField;
global $WizFullAddressField;
global $WizCountryField;

WizDebugVar("WizFullNameField");
WizDebugVar("WizEmailAddressField");
WizDebugVar("WizCompanyField");
WizDebugVar("WizPhoneNumberField");
WizDebugVar("WizFullAddressField");
WizDebugVar("WizCountryField");

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-wizard");

// if anything was filled in
if($WizFullNameField != "" ||
	 $WizEmailAddressField != "" ||
	 $WizCompanyField != "" ||
	 $WizPhoneNumberField != "" ||
	 $WizFullAddressField != "" ||
	 $WizCountryField != "")
{
  $cceClient = $serverScriptHelper->getCceClient();

  // get System
  $system = $cceClient->getObject("System");
  
  // get Network information
  $networkInfo = "";
  $oids = $cceClient->find("Network");
  for($i = 0; $i < count($oids); $i++)
	{
    $cceClient->set($oids[$i], "", array("refresh" => time()));

    // get object
    $network = $cceClient->get($oids[$i]);

    $device = $network["device"];

    $networkInfo .= "IP Address ($device): " . $network["ipaddr"] . "\n";
    $networkInfo .= "Netmask ($device): " . $network["netmask"] . "\n";
    $networkInfo .= "MAC Address ($device): " . $network["mac"] . "\n";
  }

  // don't set isRegistered, so that if someone fills in junk data
  // they can register in System->Information still
  // $cceClient->setObject("System",array("isRegistered"=>"1"));

  $hostName = $system["hostname"];
  $domainName = $system["domainname"];
  $gateway = $system["gateway"];
  $productName = $system["productName"];
  $serialNumber = $system["serialNumber"];
  $prodSerialNumber = $system["productSerialNumber"];
  $build = $system["productBuildString"];

  $header = "From: $fullNameField <admin@" . trim(`/bin/hostname --fqdn`) . ">";
  $registrationAddress = "register@cobalt.com";
  $subject = "Registration ($productName)";
  $message = "
Full name: $WizFullNameField
Company: $WizCompanyField
Address: $WizFullAddressField
Country: $WizCountryField
Phone: $WizPhoneNumberField
Email: $WizEmailAddressField

Product: $productName
Serial: $prodSerialNumber
HW Serial: $serialNumber
Build: $build

Host Name: $hostName
Domain Name: $domainName
Gateway: $gateway
$networkInfo
";

  // send email
  // mail($registrationAddress, $subject, $message, $header);
  imap_mail($registrationAddress, $subject, $message, $header);

	WizDebug("Registration mail sent to $registrationAddress\n");
}

$serverScriptHelper->shell("/bin/cp -f /usr/sausalito/ui/web/base/workgroup/default_home.html.en /home/groups/home/web/index.html", $trash, "root");

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

