<?php
// Copyright Sun Microsystems, Inc. 2001
// $Id: vsiteDefaults.php,v 1.11 2001/12/07 01:48:44 pbaltz Exp $
// vsiteDefaults.php
// display a form so admin can specify default settings for new virtual sites

include_once("ServerScriptHelper.php");
include_once("AutoFeatures.php");

$helper = new ServerScriptHelper($sessionId);

// Only adminUser should be here
if (!$helper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$factory = $helper->getHtmlComponentFactory("base-vsite", 
                "/base/vsite/vsiteDefaultsSave.php");
$cce = $helper->getCceClient();

// read the current defaults from cce, so they can be substituted
list($sysoid) = $cce->find("System");
$vsiteDefaults = $cce->get($sysoid, "VsiteDefaults");

$pageId = "siteDefaultsTab";
$defaultsBlock = $factory->getPagedBlock('vsiteDefaults', 
                    array($pageId, 'otherServices'));

$defaultsBlock->processErrors($helper->getErrors());

//default ip address
$ipAddrField = $factory->getIpAddress("ipAddr", $vsiteDefaults["ipaddr"]);
$ipAddrField->setOptional('silent');
$defaultsBlock->addFormField(
            $ipAddrField,
            $factory->getLabel("defaultIpAddr"),
            $pageId
            );

// default domain
$domainField = $factory->getDomainName("domain", $vsiteDefaults["domain"]);
$domainField->setOptional('silent');
$defaultsBlock->addFormField(
            $domainField,
            $factory->getLabel("defaultDomain"),
            $pageId
            );

// default site quota
$defaultsBlock->addFormField(
            $factory->getInteger("quota", $vsiteDefaults["quota"], 0),
            $factory->getLabel("quota"),
            $pageId
            );

// default maxusers
$defaultsBlock->addFormField(
        $factory->getInteger("maxusers", $vsiteDefaults["maxusers"], 1),
        $factory->getLabel("maxUsers"),
        $pageId
        );

// enable & disable Email
$defaultsBlock->addFormField(
        $factory->getBoolean("emailDisabled", $vsiteDefaults["emailDisabled"]),
        $factory->getLabel("emailDisabled"),
        $pageId
        );

// default email catch-all
$mailCatchAllField = $factory->getEmailAddress("mailCatchAll",
	$vsiteDefaults["mailCatchAll"], 1);
$mailCatchAllField->setOptional('silent');
$defaultsBlock->addFormField(
        $mailCatchAllField,
        $factory->getLabel("mailCatchAll"),
        $pageId
        );

// auto dns option
$defaultsBlock->addFormField(
        $factory->getBoolean("dns_auto", $vsiteDefaults["dns_auto"]),
        $factory->getLabel("dns_auto"),
        $pageId
            );

// preview site option
$defaultsBlock->addFormField(
        $factory->getBoolean("site_preview", $vsiteDefaults["site_preview"]),
        $factory->getLabel("site_preview"),
        $pageId
        );

// webAliasRedirects (to main site) option
$defaultsBlock->addFormField(
        $factory->getBoolean("webAliasRedirects", $vsiteDefaults["webAliasRedirects"]),
        $factory->getLabel("webAliasRedirects"),
        $pageId
        );

// add automatically detected features
$autoFeatures = new AutoFeatures($helper);

$cce_info = array();
list($cce_info['CCE_SERVICES_OID']) = $cce->find('VsiteServices');
$cce_info['PAGED_BLOCK_DEFAULT_PAGE'] = 'otherServices';

$autoFeatures->display($defaultsBlock, 'defaults.Vsite', $cce_info);

$page = $factory->getPage();
$defaultsBlock->addButton($factory->getSaveButton($page->getSubmitAction()));
$defaultsBlock->addButton($factory->getCancelButton('/base/vsite/vsiteList.php'));

print $page->toHeaderHtml();
print $defaultsBlock->toHtml();
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
