<?php
// $Id: vsiteMod.php,v 1.26 2001/11/27 21:30:10 jcheng Exp $
// Copyright 2001 Sun Microsystems, Inc.  All Rights Reserved.
// vsiteMod.php
// display the form for modifying the "general" (everything but FTP) 
// settings for a virtual site

include_once('ServerScriptHelper.php');
include_once('AutoFeatures.php');
include_once('Capabilities.php');

$helper =& new ServerScriptHelper();
$factory =& $helper->getHtmlComponentFactory('base-vsite', 
                '/base/vsite/vsiteModSave.php');
$cce =& $helper->getCceClient();

// determine current user's access rights to view or edit information
// here.  Only adminUser can modify things on this page.  Site admins
// can view it for informational purposes.
if ($helper->getAllowed('adminUser'))
{   
    $is_site_admin = false;
    $access = 'rw';
}
elseif ($helper->getAllowed('siteAdmin') &&
        $group == $helper->loginUser['site'])
{
    $access = 'r';
    $is_site_admin = true;
}
else
{
    header("location: /error/forbidden.html");
    return;
}

$page = $factory->getPage();

$errors =& $helper->getErrors();
$vsite = $cce->getObject('Vsite', array('name' => $group));

$pageId = "basicSettingsTab";
$settings = $factory->getPagedBlock("modVsiteSettings", 
                array($pageId, 'otherServices'));
$settings->setLabel(
    $factory->getLabel('modVsiteSettings', false, 
                    array('site' => $vsite['fqdn'])));
                    
$settings->setHideEmptyPages(array('otherServices'));

$settings->processErrors($errors);

// With IP Pooling enabled, display the IP field with a 
// range of possible choices
list($sysoid) = $cce->find("System");
$net_opts = $cce->get($sysoid, "Network");
if ($net_opts["pooling"] && $helper->getAllowed('adminUser') ) {
	$range_strings = array();
	$oids = $cce->findx('IPPoolingRange', array(), array(), 'old_numeric', 'creation_time');
	foreach ($oids as $oid) {
		$range = $cce->get($oid);
		$range_strings[] = $range['min'] . ' - ' . $range['max'];
	}
	$string = arrayToString($range_strings);
	$ip = $factory->getIpAddress("ipAddr", $vsite["ipaddr"]);
	$mylabel = $factory->getLabel("[[base-network.valid_ranges]]");
	$mylabel->setDescription($factory->i18n->get('[[base-network.valid_ranges_help]]'));
	$range_list = $factory->getCompositeFormField(
		      array($mylabel,
			    $factory->getTextList("valid_ranges", $string, "r")
			    ),
		      "&nbsp;"
		      );
	$range_list->setAlignment("TOP");
	$ip_address = $factory->getVerticalCompositeFormField(array($ip, $range_list));
	$ip_address->setId("ipAddr");
	$ip_address->setAlignment("LEFT");
} else {
	// IP address, without ranges
	$ip_address = $factory->getIpAddress("ipAddr", $vsite["ipaddr"], $access);
}

$settings->addFormField(
        $ip_address,
        $factory->getLabel("ipAddr"),
        $pageId
        );

// host and domain names
$hostfield = $factory->getVerticalCompositeFormField(array(
			   $factory->getDomainName("hostname", $vsite["hostname"], $access),
			   ));
$domainfield = $factory->getVerticalCompositeFormField(array(
			     $factory->getDomainName("domain", $vsite["domain"], $access),
			     ));
if ($helper->getAllowed('adminUser')) {
  // only add these labels if the field is editable
  // i.e. if user is an adminUser
  $hostfield->addFormField($factory->getLabel("hostName"));
  $domainfield->addFormField($factory->getLabel("domain"));
}
  
$fqdn =& $factory->getCompositeFormField(array($hostfield, $domainfield), '&nbsp;.&nbsp;');

$settings->addFormField(
        $fqdn,
        $factory->getLabel('enterFqdn'),
        $pageId
        );


// vsite disk info
$disk = $cce->get($vsite['OID'], 'Disk');

$disk_dev = $cce->getObject('Disk', 
	array('mountPoint' => $vsite['volume']), '');

if($disk_dev['total']) {
	$disk_dev['total'] = sprintf("%.0f", ($disk_dev['total'] / 1024));
}

// site quota
$site_quota = $factory->getInteger('quota', 
	$disk['quota'], 1, $disk_dev['total'], $access);

// don't show bounds if it is read-only
if ($access == 'rw')
    $site_quota->showBounds(1);

$settings->addFormField(
        $site_quota,
        $factory->getLabel('quota'),
        $pageId
        );

// max number of users site can have
$settings->addFormField(
        $factory->getInteger("maxusers", $vsite["maxusers"], 1, '', $access),
        $factory->getLabel("maxUsers"),
        $pageId
        );

// auto dns option
$settings->addFormField(
        $factory->getBoolean("dns_auto", $vsite["dns_auto"], $access),
        $factory->getLabel("dns_auto"),
        $pageId
        );

// preview site option
$settings->addFormField(
        $factory->getBoolean("site_preview", $vsite["site_preview"]),
        $factory->getLabel("site_preview"),
        $pageId
        );

if (!$is_site_admin)
{
    // Suspend Vsite        
    $settings->addFormField(
        $factory->getBoolean("suspend", $vsite["suspend"]),
        $factory->getLabel("suspend"),
        $pageId
    );
}

// PHP5 related fix:
$settings->addFormField(
    $factory->getTextField("debug_1", "", 'r'),
    $factory->getLabel("debug_1"),
    Hidden
);

// add auto-detected features
$autoFeatures = new AutoFeatures($helper);
$cce_info = array('CCE_OID' => $vsite['OID']);
list($cce_info['CCE_SERVICES_OID']) = $cce->find('VsiteServices');
$cce_info['PAGED_BLOCK_DEFAULT_PAGE'] = 'otherServices';

$autoFeatures->display($settings, 'modify.Vsite', $cce_info);

// add the buttons
if ($helper->getAllowed('adminUser'))
    $settings->addButton($factory->getSaveButton($page->getSubmitAction()));

print $page->toHeaderHtml();
$hiddenName = $factory->getTextField('group', $group, '');
print $hiddenName->toHtml();
print $settings->toHtml();
print $page->toFooterHtml();

// nice people say goodbye
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
