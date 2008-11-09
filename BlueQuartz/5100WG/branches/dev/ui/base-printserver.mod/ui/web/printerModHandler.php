<?php
include("ServerScriptHelper.php");

/* 
 * This file has a two step SET.  We first set all our data, then we call our 
 * helper application that will cp the PPD file (if any), then we set the new
 * PPD value into CCE.  This is done so that we can have the handler do any 
 * removals of old PPD data.  This keeps us from letting the user do a rm
 * as root 
 */

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

$newprinter = array();

$oldprinter=$cceClient->getObject("Printer", array("name"=>$oldPrinterName));

/* we shouldn't get anything other than a-z, 0-9 space and underscore here 
 * anyway but if we do, get rid of the bad stuff. */
$printerNameField=preg_replace("/[^a-z0-9\s_]/i", "",$printerNameField);
$newprinter["name"]=$printerNameField;
$newprinter["hostname"] = $printerHostNameField;
$newprinter["spool"] = $printerSpoolField;

$oid=$cceClient->find("Printer", array("name"=>$oldPrinterName));
$cceClient->set($oid[0], "",  $newprinter);
$errors = $cceClient->errors();

/* Now take care of the PPD stuff */
/* we would usually need code to handle the change of the name of a printer,
 * but we currently don't allow that... */
/* I have a new PPD File to be set */
if($ppdFileField != "none") {
	$serverScriptHelper->shell("/usr/sausalito/sbin/install-printer.pl ".
		escapeShellArg($ppdFileField)
		." ".escapeShellArg($oldprinter["OID"]), $out, "root");
}

sleep(2); //give lpd time to figure out the new printer name
	  //otherwise we end up with bad status data.

print($serverScriptHelper->toHandlerHtml("/base/printserver/printerList.php", $errors));
 
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

