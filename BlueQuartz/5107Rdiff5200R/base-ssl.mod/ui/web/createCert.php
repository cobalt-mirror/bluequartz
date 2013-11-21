<?php
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: createCert.php,v 1.17.2.1 2002/02/05 21:21:54 pbaltz Exp $

include_once('ServerScriptHelper.php');

$helper =& new ServerScriptHelper();

// Only serverSSL and siteAdmin should be here
if (!$helper->getAllowed('serverSSL') &&
    !$helper->getAllowed('manageSite') &&
    !($helper->getAllowed('siteAdmin') &&
      $group == $helper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

$factory =& $helper->getHtmlComponentFactory('base-ssl', 
                '/base/ssl/createCert.php');
$cce = $helper->getCceClient();

if ($save)
{
    // actually save the information

    // use the same ui for admin server and vhosts, so assume System
    // if $group is empty
    if ($group != '')
    {
        list($vsite) = $cce->find('Vsite', array('name' => $group));
    }
    else
    {
        list($vsite) = $cce->find('System');
    }

    $settings = array(
                'country' => $country,
                'state' => $state,
                'city' => $city,
                'orgName' => $orgName,
                'orgUnit' => $orgUnit,
                'email' => $email,
                'daysValid' => ($daysValid * $multiplier)
                );

    if ($type != 'csr' || $genCert)
        $settings['createCert'] = time();

    // gen csr if necessary
    if ($type == 'csr')
        $settings['createCsr'] = time();

    $ok = $cce->set($vsite, 'SSL', $settings);

    $errors = $cce->errors();

    // check for fqdn to long baddata message and remove if necessary
    if($type == 'csr' && $genCert)
    {
        $new_errors = array();
        // check for and remove baddata about fqdn if necessary
        for($i = 0; $i < count($errors); $i++)
        {
            if (!method_exists($errors[$i], 'getKey') ||
                $errors[$i]->getKey() != 'fqdn')
            {
                $new_errors[] = $errors[$i];
            }
        }
        $errors = $new_errors;
    }
    
    if ($ok)
    {
        // redirect the web browser
        if ($type == 'csr')
        {
            print $helper->toHandlerHtml(
                    '/base/ssl/siteSSL.php?group=' . $group . '&export=csr',
                    $errors, false);
        }
        else
        {
            print $helper->toHandlerHtml(
                '/base/ssl/siteSSL.php?group=' . $group, $errors, false);
        }

        $helper->destructor();
        exit;
    }
}

// get the current info to pre-populate if this is the request page
if ($type == 'csr')
{
    if ($group)
        list($site_oid) = $cce->find('Vsite', array('name' => $group));
    else
        list($site_oid) = $cce->find('System');

    $current = $cce->get($site_oid, 'SSL');
    // check for original admin server cert
    if (!$group && $current['country'] == 'SS')
        $current = array();
}

if ($type == 'csr')
    $header = 'requestInformation';
else
    $header = 'sslCertInfo';
    
$cert_info =& $factory->getPagedBlock($header);
if ($group)
{
    list($vsite) = $cce->find("Vsite", array("name" => $group));
    $vsiteObj = $cce->get($vsite);
    $fqdn = $vsiteObj['fqdn'];
}
else
{
    $fqdn = '[[base-ssl.serverDesktop]]';
}

$cert_info->setLabel(
    $factory->getLabel($header, false, array('fqdn' => $fqdn)));
$cert_info->processErrors($errors);

if ($type == 'csr')
    $cert_info->addFormField(
        $factory->getBoolean('genCert', 1),
        $factory->getLabel('genSSCert'));

$cert_info->addDivider($factory->getLabel('location', false));
$cert_info->addFormField(
    $factory->getTextField('city', $current['city']),
    $factory->getLabel('city')
    );

$stateOrProvince =& $factory->getTextField('state', $current['state']);
$stateOrProvince->setOptional('silent');
$cert_info->addFormField(
	$stateOrProvince,
	$factory->getLabel('state'));

// ISO country codes
$countries = array(
                'foocountry', // blank value so Afghanistan isn't default
                'AF', 'AL', 'DZ', 'AS', 'AD', 'AO', 'AI', 'AQ', 'AG', 'AR',
                'AM', 'AW', 'AU', 'AT', 'AZ', 'BS', 'BH', 'BD', 'BB', 'BY',
                'BE', 'BZ', 'BJ', 'BM', 'BT', 'BO', 'BA', 'BW', 'BV', 'BR',
                'IO', 'BN', 'BG', 'BF', 'BI', 'KH', 'CM', 'CA', 'CV', 'KY',
                'CF', 'TD', 'CL', 'CN', 'CX', 'CC', 'CO', 'KM', 'CG', 'CK',
                'CR', 'CI', 'HR', 'CU', 'CY', 'CZ', 'DK', 'DJ', 'DM', 'DO',
                'TP', 'EC', 'EG', 'SV', 'GQ', 'ER', 'EE', 'ET', 'FK', 'FO',
                'FJ', 'FI', 'FR', 'GF', 'PF', 'TF', 'GA', 'GM', 'GE', 'DE', 
                'GH', 'GI', 'GR', 'GL', 'GD', 'GP', 'GU', 'GT', 'GN', 'GW',
                'GY', 'HT', 'HM', 'HN', 'HK', 'HU', 'IS', 'IN', 'ID', 'IR',
                'IQ', 'IE', 'IL', 'IT', 'JM', 'JP', 'JO', 'KZ', 'KE', 'KI',
                'KP', 'KR', 'KW', 'KG', 'LA', 'LV', 'LB', 'LS', 'LR', 'LY',
                'LI', 'LT', 'LU', 'MO', 'MK', 'MG', 'MW', 'MY', 'MV', 'ML',
                'MT', 'MH', 'MQ', 'MR', 'MU', 'YT', 'MX', 'FM', 'MD', 'MC',
                'MN', 'MS', 'MA', 'MZ', 'MM', 'NA', 'NR', 'NP', 'NL', 'AN',
                'NC', 'NZ', 'NI', 'NE', 'NG', 'NU', 'NF', 'MP', 'NO', 'OM',
                'PK', 'PW', 'PS', 'PA', 'PG', 'PY', 'PE', 'PH', 'PN', 'PL',
                'PT', 'PR', 'QA', 'RE', 'RO', 'RU', 'RW', 'SH', 'KN', 'LC',
                'PM', 'VC', 'WS', 'SM', 'ST', 'SA', 'SN', 'SC', 'SL', 'SG',
                'SK', 'SI', 'SB', 'SO', 'ZA', 'GS', 'ES', 'LK', 'SD', 'SR',
                'SJ', 'SZ', 'SE', 'CH', 'SY', 'TW', 'TJ', 'TZ', 'TH', 'TG',
                'TK', 'TO', 'TT', 'TN', 'TR', 'TM', 'TC', 'TV', 'UG', 'UA',
                'AE', 'GB', 'US', 'UM', 'UY', 'UZ', 'VU', 'VA', 'VE', 'VN',
                'VG', 'VI', 'WF', 'EH', 'YE', 'YU', 'ZM', 'ZW'
                );
$country_list =& $factory->getMultiChoice('country', $countries);
if ($current['country'])
{
    foreach ($countries as $index => $code)
    {
        if ($current['country'] == $code)
        {
            $country_list->setSelected($index);
            break;
        }
    }
}

$cert_info->addFormField(
    $country_list,
    $factory->getLabel('country')
    );

$cert_info->addDivider($factory->getLabel('orgInfo', false));
$cert_info->addFormField(
    $factory->getTextField('orgName', $current['orgName']),
    $factory->getLabel('orgName')
    );

$org_unit =& $factory->getTextField('orgUnit', $current['orgUnit']);
$org_unit->setOptional(true);

$cert_info->addFormField(
    $org_unit,
    $factory->getLabel('orgUnit')
    );

$cert_info->addDivider($factory->getLabel('otherInfo', false));
$email_field =& $factory->getEmailAddress('email', $current['email']);
$email_field->setOptional(true);

$cert_info->addFormField(
    $email_field,
    $factory->getLabel('email')
    );

$time_frame =& $factory->getMultiChoice('multiplier', array(365, 30, 7, 1));
                    
$days =& $factory->getInteger('daysValid', 1, 1);

$exp_date =& $factory->getCompositeFormField(array($days, $time_frame));

$cert_info->addFormField(
    $exp_date,
    $factory->getLabel('daysValid')
    );

$cert_info->addFormField($factory->getTextField('type', $type, ''));
$cert_info->addFormField($factory->getTextField('save', 1, ''));
$cert_info->addFormField($factory->getTextField('group', $group, ''));

$page =& $factory->getPage();
$form =&  $page->getForm();

$submit_string = $type == 'csr' ? 'request' : 'createCert';
$cert_info->addButton(
    $factory->getButton($page->getSubmitAction(), $submit_string));

$cert_info->addButton(
    $factory->getCancelButton('/base/ssl/siteSSL.php?group=' . $group));

print $page->toHeaderHtml();
print $cert_info->toHtml();
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
