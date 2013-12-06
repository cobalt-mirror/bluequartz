<?php 
// Authort: Harris Vaegan-Lloyd
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: import.php 

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

$factory = $helper->getHtmlComponentFactory("base-import", "/base/import/importHandler.php?group=$group");

$page = $factory->getPage();
$i18n = $helper->getI18n("base-import");

list($site_oid) = $cceClient->find('Vsite', array('name' => $group)); 
$vsite = $cceClient->get($site_oid);
 
$block = $factory->getPagedBlock("userImport");
$block->setLabel($factory->getLabel('importTitle', false, array('fqdn' => $vsite['fqdn'])));
$block->processErrors($helper->getErrors());

$location = $factory->getMultiChoice("locationField");
$url = $factory->getOption("url", true);
$url->addFormField($factory->getUrl("urlField"));
$location->addOption($url);

$upload = $factory->getOption("upload");
$upload->addFormField($factory->getFileUpload("dataUpload"));
$location->addOption($upload);

$block->addFormField(
	$location,
	$factory->getLabel('dataUpload')
);

$mypage = $factory->getPage();
$myform = $mypage->getForm();
$myformId = $myform->getId();

// use our own submit handler so that spinny clock doesn't show
// otherwise, it never disappears
$block->addButton($factory->getButton("javascript: if (document.$myformId.onsubmit()) { document.$myformId.submit(); }", "importNow"));

if ( in_array("LdapImport", $cceClient->names('System')) )
	$ldapAvailable = 1;

if ( $ldapAvailable ) {
	$pageChanger = $factory->getMultiButton("[[base-import.importChanger]]",
		array("/base/import/import.php?valset=1", 
		"/base/ldap/import.php?valset=1"),
		array("[[base-import.importChangerFile]]",
		"[[base-ldap.importChangerLdap]]"));
	
	if ($valset) {
		$pageChanger->setSelectedIndex(0);
	}
}

print($page->toHeaderHtml());

if ( $ldapAvailable ) {
	print $pageChanger->toHtml();
	print("<BR><BR>");
}

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
