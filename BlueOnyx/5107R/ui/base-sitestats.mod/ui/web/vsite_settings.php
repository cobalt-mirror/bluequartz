<?php
// $Id: vsite_settings.php,v 1.2.2.1 2002/01/09 03:14:48 pbaltz Exp $
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
//
// Display settings for site usage stats.

include_once('ServerScriptHelper.php');

global $purgeMap;
$purgeMap = array(
        'never' =>      0,
        'month' =>      32,
        '2month' =>     62,
        '3month' =>     93,
        '6month' =>     181,
        'year' =>       366,
        '2year' =>      731,
        '3year' =>      1096,
        '4year' =>      1462,
        '5year' =>      1802,
        );
$detailMap = array(
	'sitestatsConsolidateDaily' =>		0,
	'sitestatsConsolidateMonthly' =>	1,
	);

$helper = new ServerScriptHelper();

// Only adminUser and siteAdmin should be here
if (!$helper->getAllowed('adminUser') &&
    !($helper->getAllowed('siteAdmin') &&
      $group == $helper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

$cce =& $helper->getCceClient();
$factory =& $helper->getHtmlComponentFactory('base-sitestats',
                '/base/sitestats/vsite_settings.php');

// Session is read-only for non-server administrators
if($helper->getAllowed('adminUser'))
	$sitestats_access = 'rw';
else
	$sitestats_access = 'r';

// handle saves
if ($save)
{
    $errors = handle_sitestats($helper);
}

// get data for site
$sitestats =& $cce->getObject('Vsite', array('name' => $group), 'SiteStats');
list($vsite) = $cce->find('Vsite', array('name' => $group));
$vsiteObj = $cce->get($vsite);
$container =& $factory->getPagedBlock('sitestatsSettings');
$container->setLabel($factory->getLabel('sitestatsSettings', false, array('fqdn' => $vsiteObj['fqdn'])));
$container->processErrors($errors);

// construct all the form fields needed, note that only simple
// form fields are allowd.  no composite form fields
$statsEnable = $factory->getBoolean('Sitestats_enabled', $sitestats['enabled'], $sitestats_access);

// simple array setup
$detailLabels = array_keys($detailMap);
$detailDays = array_values($detailMap);
$detailrevMap = array_flip($detailMap);

$statsConsolidate = $factory->getMultiChoice('Sitestats_consolidate', $detailLabels, array($detailrevMap[$sitestats['consolidate']]), $sitestats_access);

// again
$purgeLabels = array_keys($purgeMap);
$purgeDays = array_values($purgeMap);
$revMap = array_flip($purgeMap);

$purgeSelect = $factory->getMultiChoice('Sitestats_purge', $purgeLabels, array($revMap[$sitestats['purge']]), $sitestats_access);

$container->addFormField($statsEnable, $factory->getLabel("sitestatsEnable"));
$container->addFormField($statsConsolidate, $factory->getLabel("sitestatsConsolidate"));
$container->addFormField($purgeSelect, $factory->getLabel("sitestatsPurge"));

$container->addFormField($factory->getTextField('save', 1, ''));

$page =& $factory->getPage();
$form =& $page->getForm();

$container->addButton($factory->getSaveButton($form->getSubmitAction()));

print $page->toHeaderHtml();
$group = $factory->getTextField('group', $group, '');
print $group->toHtml();
print $container->toHtml();
print $page->toFooterHtml();

$helper->destructor();

function handle_sitestats(&$helper)
{
	global $cce, $group, 
		$Sitestats_enabled, 
		$Sitestats_consolidate, $Sitestats_purge,
		$detailMap, $purgeMap;

	if(!$Sitestats_enabled)
		$Sitestats_enabled = "0";
	if(!$Sitestats_consolidate)
		$Sitestats_consolidate = "0";
	if(!$Sitestats_purge)
		$Sitestats_consolidate = "never";

	$settings = array();
	$settings["enabled"] = $Sitestats_enabled;
	$settings["consolidate"] = $detailMap[$Sitestats_consolidate];
	$settings["purge"] = $purgeMap[$Sitestats_purge];

	list($vsite) = $cce->find('Vsite', array('name' => $group));
	$cce->set($vsite, 'SiteStats', $settings);

	return $cce->errors();
}
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
