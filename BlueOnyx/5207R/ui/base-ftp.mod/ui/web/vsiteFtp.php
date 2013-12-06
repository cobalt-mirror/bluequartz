<?
// Copyright Sun Microsystems, Inc. 2001
// $Id: vsiteFtp.php
// display the form to modify anonymous ftp settings for a vsite

include_once('ServerScriptHelper.php');
include_once('AutoFeatures.php');
include_once('Capabilities.php');

$helper = new ServerScriptHelper();
$factory =& $helper->getHtmlComponentFactory('base-ftp', 
                    '/base/ftp/vsiteFtp.php');
$cce =& $helper->getCceClient();

// Only 'serverFTP' can modify things on this page.  
// Site admins can view it for informational purposes.
if ($helper->getAllowed('serverFTP')){
    $is_site_admin = 0;
    $access = 'rw';
} elseif ($helper->getAllowed('siteAnonFTP')) {
    $access = 'rw';
    $is_site_admin = 1;
} elseif ($helper->getAllowed('siteAdmin') &&
          $group == $helper->loginUser['site']) {
    $access = 'r';
    $is_site_admin = 1;
} else {
    header("location: /error/forbidden.html");
    return;
}

if ($ftp_submit)
{
    $cce->setObject('Vsite', 
            array(
                    'enabled' => ($ftp ? 1 : 0), 
                    'quota' => $ftpquota, 
                    'maxConnections' => $ftpusers
            ), 
            'AnonFtp', 
            array('name' => $group));

    $errors = $cce->errors();

    // handle auto features saves here:
    $vsite_oid = $cce->find('Vsite', array('name' => $group));
    $autoFeatures = new AutoFeatures($helper);
    $cce_info = array("CCE_OID" => $vsite_oid[0]);
    list($cce_info["CCE_SERVICES_OID"]) = $cce->find("VsiteServices");
    $af_errors = $autoFeatures->handle("modify.FTP", $cce_info);
    $errors = array_merge($errors, $af_errors);

}

$page = $factory->getPage();

list($vsite_oid) = $cce->find('Vsite', array('name' => $group));
$vsite = $cce->get($vsite_oid);

if (!$ftp_submit)
{
    // get vsite information
    $vsiteServices =& $cce->get($vsite_oid, 'AnonFtp');
}

// get disk settings since quota shouldn't be able to exceed Vsite
// quota
$vsite_disk =& $cce->get($vsite_oid, 'Disk');

// start the paged block
$settings =& $factory->getPagedBlock('modAnonFtp');
$settings->processErrors($errors);
$settings->setLabel(
    $factory->getLabel('modAnonFtp', false, array('fqdn' => $vsite['fqdn'])));


// add auto-detected features
$autoFeatures = new AutoFeatures($helper);
$cce_info = array('CCE_OID' => $vsite['OID']);
list($cce_info['CCE_SERVICES_OID']) = $cce->find('VsiteServices');

$autoFeatures->display($settings, 'modify.FTP', $cce_info);

// need to embed this or things get confused
$settings->addFormField($factory->getTextField('group', $group, ''));
$settings->addFormField($factory->getTextField('ftp_submit', 1, ''));

// add the anonymous ftp option
$ftpField =& $factory->getMultiChoice('ftp');
$enable =& $factory->getOption('enable', $vsiteServices['enabled'], $access);
$enable->setLabel($factory->getLabel('anonEnable', false));

$shown_quota = $vsiteServices['quota'];
// if the site quota is now less than the anon ftp quota drop anon quota
// to whatever the site quota is
if (($vsite_disk['quota'] >= 0) && 
    ($vsite_disk['quota'] < $vsiteServices['quota']))
{
    $shown_quota = $vsite_disk['quota'];
}

$quota =& $factory->getInteger('ftpquota', $shown_quota, 
                    1, "", $access);
$quota->setWidth(10);
if ($vsite_disk['quota'] >= 0)
{
    $quota->setMax($vsite_disk['quota']);
    $quota->showBounds(1);
}

$enable->addFormField($quota, $factory->getLabel('ftpQuota'));

$connections =& $factory->getInteger('ftpusers', 
                                $vsiteServices['maxConnections'], 
                                1, "", $access);

$connections->setWidth(10);

$enable->addFormField($connections, $factory->getLabel('ftpUsers'));

$ftpField->addOption($enable);

if ($is_site_admin) {
	$settings->addFormField(
		$factory->getInteger('ftpquota', $shown_quota, 1, "", $access),
		$factory->getLabel('ftpQuota'));
	$settings->addFormField(
		$factory->getInteger('ftpusers', $vsiteServices['maxConnections'], 1, "", $access),
		$factory->getLabel('ftpUsers'));
} else { 
	$settings->addFormField(
    $ftpField,
    $factory->getLabel('anonFtp')
    );
	$settings->setColumnWidths(array('20%', '80%'));
}

// add the buttons
if (!$is_site_admin)
	$settings->addButton($factory->getSaveButton($page->getSubmitAction()));

if (count($errors))
{
    $settings->setSelectedId('errors');
    $settings->processErrors($errors, 
    		array('quota' => 'ftpquota', 'maxConnections' => 'ftpusers'));
}


print $page->toHeaderHtml();
print $settings->toHtml();
print $page->toFooterHtml();

// nice people say goodbye
$helper->destructor();

/*
Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
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