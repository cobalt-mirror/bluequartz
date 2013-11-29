<?php
// $Id: vsiteWeb.php,v 1.1 2001/11/05 08:24:52 pbose Exp $
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
//
// This page brings in things related to web services
// such as web scripting languages, frontpage, and web server aliases

include_once('ServerScriptHelper.php');
include_once('AutoFeatures.php');
include_once('Capabilities.php');

$helper = new ServerScriptHelper();

// Only adminUser and siteAdmin should be here
if (!$helper->getAllowed('adminUser') &&
    !($helper->getAllowed('siteAdmin') &&
      $group == $helper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

$factory =& $helper->getHtmlComponentFactory('base-vsite',
                '/base/vsite/vsiteWeb.php');
$cce =& $helper->getCceClient();

// Only adminUser can modify things on this page.  
// Site admins can view it for informational purposes.
if ($helper->getAllowed('adminUser')){
    $is_site_admin = 0;
    $access = 'rw';
} else {
    $access = 'r';
    $is_site_admin = 1;
}

$site = $cce->getObject('Vsite', array('name' => $group));

if ( $save ) {
	$autoFeaturesSave = new AutoFeatures($helper);
	$cce_info = array('CCE_OID' => $site['OID']);
	list($cce_info['CCE_SERVICES_OID']) = $cce->find('VsiteServices');
	$errors = $autoFeaturesSave->handle('modifyWeb.Vsite', $cce_info);

	// Set webAliases & webAliasRedirects in 'Vsite':
	$cce->set($site['OID'], '', array("webAliases" => $webAliases, "webAliasRedirects" => $webAliasRedirects));
	$errors = array_merge($errors, $cce->errors());

}

$site = $cce->getObject('Vsite', array('name' => $group));

$pageId = 'basicSettings';
$settings =& $factory->getPagedBlock('siteWebSettings', array($pageId));
$settings->processErrors($errors);

$settings->setLabel(
    $factory->getLabel('siteWebSettings', false, 
                    array('fqdn' => $site['fqdn'])));

// add auto-detected features
$autoFeatures = new AutoFeatures($helper);
$cce_info = array('CCE_OID' => $site['OID'], 'FIELD_ACCESS' => $access, 'IS_SITE_ADMIN' => $is_site_admin);
list($cce_info['CCE_SERVICES_OID']) = $cce->find('VsiteServices');
$cce_info['PAGED_BLOCK_DEFAULT_PAGE'] = $pageId;
$autoFeatures->display($settings, 'modifyWeb.Vsite', $cce_info);

// web server aliases
$webAliasesField = $factory->getDomainNameList("webAliases", 
                       $site["webAliases"], $access);
$webAliasesField->setOptional(true);

$settings->addFormField(
       $webAliasesField,
       $factory->getLabel("webAliases"), $pageId
       );

# webAliasRedirects:
if ( $site['webAliasRedirects'] ) {
	$settings->addFormField(
		$factory->getBoolean('webAliasRedirects', $site['webAliasRedirects'], $access),
		$factory->getLabel('webAliasRedirects'), $pageId
		);
} else {
	$settings->addFormField(
		$factory->getBoolean('webAliasRedirects', $site['webAliasRedirects'], $access),
		$factory->getLabel('webAliasRedirects'), $pageId
		);
}

$settings->addFormField($factory->getTextField('group', $group, ''));
$settings->addFormField($factory->getTextField('save', '1', ''));

$page =& $factory->getPage();
$form =& $page->getForm();
// add the buttons
if ($helper->getAllowed('adminUser'))
    $settings->addButton($factory->getSaveButton($page->getSubmitAction()));

print $page->toHeaderHtml();
print $settings->toHtml();
print $page->toFooterHtml();

$helper->destructor();
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