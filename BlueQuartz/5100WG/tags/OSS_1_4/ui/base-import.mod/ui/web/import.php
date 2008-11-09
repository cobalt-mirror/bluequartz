<?php 
// Authort: Harris Vaegan-Lloyd
// Copyright 2000, Cobalt Networks, All rights reserved.
// $Id: import.php 3 2003-07-17 15:19:15Z will $

include("uifc/FileUpload.php");
include("ServerScriptHelper.php");
include("uifc/ScrollList.php");
include("uifc/CancelButton.php");

$helper = new ServerScriptHelper();

$factory = $helper->getHtmlComponentFactory("base-import", "/base/import/importHandler.php");

$page = $factory->getPage();
$i18n = $helper->getI18n("base-import");

$block = $factory->getPagedBlock("userImport");

$block->addFormField(
	new FileUpload($page, "dataUpload", "", "", "", $i18n->get("dataUpload_empty")),
	$factory->getLabel("dataUpload")
);

#$block->addFormField(
#	$factory->getBoolean("verbose"),
#	$factory->getLabel("verbose")
#);

#$block->addFormField(
#	$factory->getBoolean("dryRun"),
#	$factory->getLabel("dryRun")
#);



$block->addButton($factory->getButton($page->getSubmitAction(),"importNow"));
#$block->addButton($factory->getCancelButton("/base/import/import.php"));

$pageChanger = $factory->getMultiButton("[[base-import.importChanger]]",
	array("/base/import/import.php?valset=1", 
	"/base/ldap/import.php?valset=1"),
	array("[[base-import.importChangerFile]]",
	"[[base-ldap.importChangerLdap]]"));

if ($valset) {
	$pageChanger->setSelectedIndex(0);
}

print($page->toHeaderHtml());

print $pageChanger->toHtml();
print("<BR><BR>");
$helper->destructor();

print($block->toHtml());
print($page->toFooterHtml());


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

