<?php
// Author: Jesse Throwe
// Copyright 2001 Sun Microsystems, Inc. All rights reserved.
// $Id: overflow.php,v 1.8 2001/12/07 03:05:29 pbaltz Exp $



// -------------------------------------
// Includes

include("ServerScriptHelper.php");


// -------------------------------------
// Variables

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient() or die ("CCE was not found, or is not running");
$factory = $serverScriptHelper->getHtmlComponentFactory("base-overflow", "/base/overflow/overflowHandler.php");
$i18n = $serverScriptHelper->getI18n("base-overflow");
$page = $factory->getPage();
$block = $factory->getPagedBlock("overflowSettings");

// -------------------------------------
// Process error messages from the handler script for display.
$block->processErrors($serverScriptHelper->getErrors());

// -------------------------------------
// Get values from CCE and parse the values out to their appropriate places

$overflowCCE = $cceClient->getObject("System", array(), "Overflow");

$enabled = $overflowCCE["enabled"];
$alertEmail = $overflowCCE["alertEmail"];


// -------------------------------------
// Construct buttons

// -------------------------------------
// Block Form


// set the enabled button

$enabledBox = $factory->getBoolean("enabled", $enabled);
$block->addFormField($enabledBox, $factory->getLabel("enabledBox"));

// now set the admin form
$adminEmail = $factory->getEmailAddress("alertEmail", $alertEmail);
$adminEmail->setOptional(true);

// Use ActiveMonitor's email contact list if possible
$am_obj = $cceClient->getObject('ActiveMonitor', array(), '');
if( ! $am_obj["alertEmailList"] )
        $block->addFormField($adminEmail, $factory->getLabel("alertEmail"));

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

// Free Helper
$serverScriptHelper->destructor();


// -------------------------------------
// Print out all the data
print $page->toHeaderHtml();
print $block->toHtml();
print $page->toFooterHtml();
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
