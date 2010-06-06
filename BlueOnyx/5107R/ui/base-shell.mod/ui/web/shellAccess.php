<?php
// $Id: shellAccess.php,v 1.2 2001/10/21 01:24:09 jcheng Exp $
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// display services available for shell access

include_once('ServerScriptHelper.php');
include_once('AutoFeatures.php');

$helper = new ServerScriptHelper();

// Only adminUser should be here
if (!$helper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$factory =& $helper->getHtmlComponentFactory('base-shell', 
                                    '/base/shell/shellAccess.php');
$cce =& $helper->getCceClient();

list($sys_oid) = $cce->find('System');

$auto_features = new AutoFeatures($helper);

$errors = array();

if ($HTTP_POST_VARS['saving'])
{
    $errors = $auto_features->handle('shell.System',
                                        array(
                                            'CCE_SERVICES_OID' => $sys_oid,
                                            'CCE_OID' => $sys_oid
                                            ));
}

$defaultPage = 'pageOne';
$block = $factory->getPagedBlock('shell', array($defaultPage));
$block->processErrors($errors);

$auto_features->display($block, 'shell.System',
                array(
		    'CCE_SERVICES_OID' => $sys_oid,
		    'CCE_OID' => $sys_oid,
                    'PAGED_BLOCK_DEFAULT_PAGE' => $defaultPage
                    ));

// add buttons
$page =& $factory->getPage();
$form =& $page->getForm();
$block->addButton($factory->getSaveButton($form->getSubmitAction()));
$block->addFormField($factory->getTextField('saving', 1, ''));

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
