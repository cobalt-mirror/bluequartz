<?php 
// This screen allows users to be exported from the system
// to a TSV format compatible with the import format.  
//
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: export.php 1050 2008-01-23 11:45:43Z mstauber $

include_once("ServerScriptHelper.php");

$helper = new ServerScriptHelper();

// Only adminUser and siteAdmin should be here
if (!$helper->getAllowed('adminUser') &&
    !($helper->getAllowed('siteAdmin') &&
      $group == $helper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $helper->getCceClient();

// FIND the user OIDS
if ( $group ) {
	$findvars = array("site" => $group);
} else {
 	$findvars = array();
}

$oids = $cceClient->findSorted("User", "name", $findvars);

$factory = $helper->getHtmlComponentFactory("base-import", "/base/import/exportHandler.php?group=$group");

$page = $factory->getPage();
$i18n = $helper->getI18n("base-import");

$number = count($oids);
if ( $number < 1 ) {
	$message = $i18n->getHtml("noUsersForExport");
} else if ( 1 == $number) {
	$message = $i18n->getHtml("oneUserForExport");
} else {
	$message = $i18n->getHtml("usersForExport", "base-import", array("number" => $number) );
}

if ( $number > 0 ) {

	$block = $factory->getPagedBlock("userExport");

	list($site_oid) = $cceClient->find('Vsite', array('name' => $group)); 
	$vsite = $cceClient->get($site_oid);
	$block->setLabel($factory->getLabel('exportTitle', false, array('fqdn' => $vsite['fqdn'])));

	$format = $factory->getMultiChoice("pwFormat");
	$format->addOption($factory->getOption("namePw"));
	$format->addOption($factory->getOption("randomPw"));
	$format->setSelected(1);
	
	$block->addFormField(
		$format,
		$factory->getLabel("pwFormat")
	);

	$groupField = $factory->getTextField("group", $group, "");
	
	$form = $page->getForm();
	$formId = $form->getId();

	// use our own submit handler so that spinny clock doesn't show
	// otherwise, it never disappears
	$block->addButton($factory->getButton("javascript: if (document.$formId.onsubmit()) { document.$formId.submit(); }", "downloadList"));
}

$helper->destructor();

print($page->toHeaderHtml());
print "$message <BR><BR>";

// MSIE-specific patch to send args via GET instead of POST.  For some
// reason, the handler is called twice, the second without POST args.
print "<SCRIPT> document.$formId.method = 'GET'; </SCRIPT>";
if ( $number > 0 ) {
	print($block->toHtml());
	print($groupField->toHtml());
}

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
