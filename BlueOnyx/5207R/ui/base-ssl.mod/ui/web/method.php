<?php
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: method.php,v 1.2 2001/06/09 05:12:28 pbaltz Exp $

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

$factory =& $helper->getHtmlComponentFactory('base-ssl',
                                    '/base/ssl/method.php');

if (isset($certMethod) && $certMethod == 'create')
{
    print $helper->toHandlerHtml('/base/ssl/getLocation.php?group=' . $group);
    $helper->destructor();
    exit;
}
else if (isset($certMethod))
{
    print $helper->toHandlerHtml('/base/ssl/uploadCert.php?group=' . $group);
    $helper->destructor();
    exit;
}

$method =& $factory->getPagedBlock('method');

$choice =& $factory->getMultiChoice('certMethod', array('create', 'upload'));
$choice->setFullSize(true);
$method->addFormField($choice, $factory->getLabel('method'));

$method->addFormField($factory->getTextField('group', $group, ''));

$page =& $factory->getPage();
$form =& $page->getForm();

$method->addButton(
    $factory->getButton($form->getSubmitAction(), 'next'));

print $page->toHeaderHtml();
print $method->toHtml();
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
