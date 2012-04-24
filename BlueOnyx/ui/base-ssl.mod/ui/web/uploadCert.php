<?php
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: uploadCert.php,v 1.10.2.1 2002/04/16 23:37:16 naroori Exp $

include_once('ServerScriptHelper.php');

$helper = new ServerScriptHelper();

// Only adminUser and siteAdmin should be here
if (!$helper->getAllowed('adminUser') &&
    !($helper->getAllowed('siteAdmin') &&
      $group == $helper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

$factory = $helper->getHtmlComponentFactory('base-ssl', '/base/ssl/uploadCert.php');

if ($save)
{
    if ($cert == "none") 
    {
	//no file supplied
	$error = new CceError('huh', 0, 'cert', "[[base-ssl.sslImportError4a]]");
	$errors = array($error);
    }
    else 
    {

    if (is_uploaded_file($cert)) {
	$tmp_cert = tempnam('/tmp', 'file');
	move_uploaded_file($cert, $tmp_cert);
    }
    else {
	//file opening problems
        $error = new CceError('huh', 0, 'cert', "[[base-ssl.sslImportError4]]");
	$errors = array($error);

    }
	    if (!is_file($tmp_cert)) 
	    {
		//file opening problems
		$error = new CceError('huh', 0, 'cert', "[[base-ssl.sslImportError4]]");
		$errors = array($error);
	    }
	    else
	    {
		    $runas = ($helper->getAllowed('adminUser') ? 'root' : $helper->getLoginName());
		    $ret = $helper->shell("/usr/sausalito/sbin/ssl_import.pl $tmp_cert --group=$group --type=serverCert", $output, $runas);
		    if ($ret != 0)
		    {
		        // deal with error
		        $error = new CceError('huh', 0, 'cert', "[[base-ssl.sslImportError$ret]]");
		        $errors = array($error);
			unlink($tmp_cert);
		    }
		    else
		    {
		        header("Location: /base/ssl/siteSSL.php?group=$group");
        		unlink($tmp_cert);
		        $helper->destructor();
		        exit;
		    }

	    }
    }
}

$upload = $factory->getPagedBlock('importCert');
$cce = $helper->getCceClient();

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

$upload->setLabel(
    $factory->getLabel('importCert', false, array('fqdn' => $fqdn)));
$upload->processErrors($errors);

// add hidden field, so we know we were here before
$upload->addFormField($factory->getBoolean('save', 1, ''));

$upload->addFormField(
    $factory->getFileUpload('cert', $cert),
    $factory->getLabel('certUpload')
    );

$upload->addFormField($factory->getTextField('group', $group, ''));

$page = $factory->getPage();
$form = $page->getForm();
$formId = $form->getId();

// use our own submit handler so that spinny clock doesn't show
// otherwise, it never disappears
$upload->addButton($factory->getButton("javascript: if (document.$formId.onsubmit()) { document.$formId.submit(); }", "import"));
$upload->addButton($factory->getCancelButton("/base/ssl/siteSSL.php?group=$group"));

print $page->toHeaderHtml();
print $upload->toHtml();
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
