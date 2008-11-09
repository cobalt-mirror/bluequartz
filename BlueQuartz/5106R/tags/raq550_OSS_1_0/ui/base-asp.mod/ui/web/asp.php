<?php
// Author: Kenneth C.K. Leung
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: asp.php,v 1.8 2001/12/07 03:05:29 pbaltz Exp $

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-asp", "/base/asp/aspHandler.php");

// get settings
$systemObj = $cceClient->getObject("System",array(),"Asp");

$page = $factory->getPage();

$block = $factory->getPagedBlock("aspSettings");
$block->processErrors($serverScriptHelper->getErrors());

// Asp public server is in/devoked by the handler asp-toggle.pl on class AspSite modifications
// 
// $block->addFormField(
//   $factory->getBoolean("enableServer", $systemObj["enabled"]),
//   $factory->getLabel("enableServer")
// );

$block->addFormField(
	$factory->getBoolean("enableAdminServer", $systemObj["enabledAdmin"]), 
	$factory->getLabel("enableAdminServer")
);

// Only edit if enabled
$action = $factory->getCompositeFormField();

$link = $factory->getButton("http://$SERVER_NAME:5100/", 'accessAdmin');
$link->setTarget("new");

if($systemObj["enabledAdmin"] == 0)
	$link->setDisabled(true); 

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php print($link->toHtml()); ?>
<P>
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
