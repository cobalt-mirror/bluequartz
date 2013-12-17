<?php
// Copyright 2001 Sun Microsystems, Inc.  All Rights Reserved.
// $Id: siteSSL.php
// siteSSL.php
// display the page to setup ssl info for a site

include_once('ServerScriptHelper.php');

$helper = new ServerScriptHelper();

// Only serverSSL and siteAdmin should be here
if (!$helper->getAllowed('serverSSL') &&
    !$helper->getAllowed('manageSite') &&
    !($helper->getAllowed('siteAdmin') &&
      $group == $helper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

$factory =& $helper->getHtmlComponentFactory('base-ssl', '/base/ssl/siteSSL.php');
$cce =& $helper->getCceClient();
$page = $factory->getPage();
$i18n =& $factory->getI18n();

// handle admin and vsite ssl with these pages, so pay attention
// to that
if ($group != '')
{
    $vsite =& $cce->getObject('Vsite', array('name' => $group), 'SSL');

    // check if we need to handle setting anything
    if (isset($enabled))
    {
        $errors = handle_ssl($helper, $group);
    }
}
else
{
    $vsite =& $cce->getObject('System', array(), 'SSL');
}

// add buttons to create/import/export a certificate
$create =& $factory->getButton(
                '/base/ssl/createCert.php?group=' . $group, 'createCert');
$request =& $factory->getButton(
                '/base/ssl/createCert.php?group=' . $group . '&type=csr', 
                'request');
$ca_certs =& $factory->getButton(
                '/base/ssl/caManager.php?group=' . $group,
                'manageCAs');

$import =& $factory->getButton(
                '/base/ssl/uploadCert.php?group=' . $group, 'import');

$exportButton =& $factory->getButton(
                '/base/ssl/exportCert.php?group=' . $group . '&type=cert', 
                'export');
// assume that if the expires field is blank there is no cert to export
if ($vsite['expires'] == '')
    $exportButton->setDisabled(true);
    
$buttons =& $factory->getCompositeFormField(
                array($create, $request, $ca_certs),
                '&nbsp;&nbsp;&nbsp;&nbsp;');

$buttons_too =& $factory->getCompositeFormField(
                array($import, $exportButton),
                '&nbsp;&nbsp;&nbsp;&nbsp;');

// display certificate information for current cert
// but only if there is certifcate information
$ssl_info =& $factory->getPagedBlock('sslCertInfo');

if ($group)
{
    list($oid) = $cce->find('Vsite', array('name' => $group));
    $vsite_info = $cce->get($oid);
    $fqdn = $vsite_info['fqdn'];
}
else
{
    $fqdn = '[[base-ssl.serverDesktop]]';
}

$ssl_info->setLabel(
    $factory->getLabel('sslCertInfo', false, array('fqdn' => $fqdn)));
$ssl_info->processErrors($errors);

$ssl_info->addFormField($factory->getTextField('group', $group, ''));

// add enabled/disabled checkbox if not on the admin server
// and the user is the adminUser
if ($group != '' && $helper->getAllowed('adminUser'))
{
    $ssl_info->addFormField(
        $factory->getBoolean('enabled', $vsite['enabled']),
        $factory->getLabel('enabled')
        );
}

if ($vsite['expires'])
{
    $cert_sections = array(
                    'location' => array('city', 'state', 'country'), 
                    'orgInfo' => array('orgName', 'orgUnit'),
                    'otherInfo' => array('email'));

    foreach ($cert_sections as $section => $fields)
    {
        $ssl_info->addDivider($factory->getLabel($section, false));

        foreach ($fields as $var)
        {
            $value = $vsite[$var];
            if ($var == 'country')
                $value = $i18n->get($vsite[$var]);
                
            $ssl_info->addFormField(
                $factory->getTextField($var, $value, 'r'),
                $factory->getLabel($var)
                );
        }
    }

    // special case expires field
    $ssl_info->addFormField(
        $factory->getTimeStamp('expires', strtotime($vsite['expires']), 
                            'date', 'r'),
        $factory->getLabel('expires')
        );

}

// again if it isn't the admin server they get a save button to
// save information if they are the admin user
if ($group != '' && $helper->getAllowed('adminUser'))
{
    $ssl_info->addButton($factory->getSaveButton($page->getSubmitAction()));
}

if ($export == 'csr')
{
    $page->setOnLoad('exportCert()');
}

print $page->toHeaderHtml();
print $buttons->toHtml();
print "<P></P>\n";
print $buttons_too->toHtml();
print "<P></P>\n";

if (!$vsite['expires'])
{
    print $i18n->interpolateHtml('[[base-ssl.noCertInfo]]');
}
else
{
    print $ssl_info->toHtml();
}
if ($export == 'csr')
{
    $action = "/base/ssl/exportCert.php?group=$group&type=csr";
?>
<SCRIPT LANGUAGE="javascript">
function exportCert()
{
    self.location = '<? print($action); ?>'; 
}
</SCRIPT>
<?
}

print $page->toFooterHtml();

$helper->destructor();

function handle_ssl(&$helper, &$group)
{
    global $enabled;

    $cce =& $helper->getCceClient();

    list($oid) = $cce->findx('Vsite', array('name' => $group));
    $cce->set($oid, 'SSL', array('enabled' => $enabled));
    $errors = $cce->errors();
    return $errors;
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
