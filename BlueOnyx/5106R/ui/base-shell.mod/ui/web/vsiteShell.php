<?php
// $Id: vsiteShell.php,v 1.1 2001/11/05 08:16:42 pbose Exp $
// Copyright 2001 Sun Microsystems, Inc.  All Rights Reserved.

include_once('ServerScriptHelper.php');

$helper =& new ServerScriptHelper();
$factory =& $helper->getHtmlComponentFactory('base-shell', '/base/shell/vsiteShell.php');
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
        
if ( $save ) {
	$cce->setObject('Vsite', array('enabled' => $Shell_enabled), 'Shell', array('name' => $group));
	$errors = $cce->errors();
}

$site = $cce->getObject('Vsite', array('name' => $group));
$siteShell = $cce->getObject('Vsite', array('name' => $group), 'Shell');

$page = $factory->getPage();
$block =& $factory->getPagedBlock('siteShellSettings');
$block->setLabel($factory->getLabel('siteShellSettings', false, array('fqdn' => $site['fqdn'])));
$block->processErrors($errors);

$block->addFormField($factory->getTextField('group', $group, ''));
$block->addFormField($factory->getTextField('save', '1', ''));

$shellEnable = $factory->getBoolean('Shell_enabled', 
	$siteShell['enabled'], $access);
        
$block->addFormField($shellEnable, 
	$factory->getLabel('enableShell'));

// Don't ask why, but somehow with PHP5 we need to add a blank FormField or nothing shows on this page:
$hidden_block = $factory->getTextBlock("Nothing", "");
$hidden_block->setOptional(true);
$block->addFormField(
    $hidden_block,
    $factory->getLabel("Nothing"),
    "Hidden"
    );


if (!$is_site_admin)
	$block->addButton($factory->getSaveButton($page->getSubmitAction()));

print $page->toHeaderHtml();
print $block->toHtml();
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
