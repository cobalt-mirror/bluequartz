<?php
// Author: Jesse Throwe
// Copyright 2001 Sun Microsystems, Inc. All rights reserved.
// $Id: overflowHandler.php,v 1.6 2001/10/22 20:51:40 jthrowe Exp $


// -------------------------------------
// Includes

include("ServerScriptHelper.php");

// -------------------------------------
// Variables

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$i18n = $serverScriptHelper->getI18n("base-overflow");

// now update cce
if ($enabled) {
        $cceClient->setObject("ActiveMonitor", array("enabled" => 1), "Overflow");
} else {
        $cceClient->setObject("ActiveMonitor", array("enabled" => 0), "Overflow");
}

$settings = array();
$settings["enabled"] = $enabled;

// Use ActiveMonitor's email contact list if possible
$am_obj = $cceClient->getObject('ActiveMonitor', array(), '');
if( ! $am_obj["alertEmailList"] )
        $settings["alertEmail"] = $alertEmail;

$cceClient->setObject("System", $settings, "Overflow");
$errors = $cceClient->errors();

// return to the original page with any errors
print($serverScriptHelper->toHandlerHtml("/base/overflow/overflow.php", $errors, "base-overflow"));

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
