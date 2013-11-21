<?php
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: caManager.php,v 1.3.2.2 2002/04/16 23:37:16 naroori Exp $

include_once('ServerScriptHelper.php');
include_once('ArrayPacker.php');

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
                                            '/base/ssl/caManager.php');
$cce = $helper->getCceClient();

if ($group)
{
    list($oid) = $cce->find('Vsite', array('name' => $group));
}
else
{
    list($oid) = $cce->find('System');
}

$ssl = $cce->get($oid, 'SSL');

if ($save)
{
    if (ereg('addCA', $caAction))
    {
	if ($caCert == "none") 
	{
		//no file supplied
		$error =& new CceError('huh', 0, 'cert', "[[base-ssl.sslImportError4]]");
		$errors = array($error);
	}
	else 
	{
        	// import the uploaded information for the specified site
        	$fh = fopen($caCert, 'r');
		if (!$fh) 
		{
			//file opening problems
			$error =& new CceError('huh', 0, 'cert', "[[base-ssl.sslImportError4]]");
			$errors = array($error);
		}
                else 
                {

        	        $lines = '';
        	        while (!feof($fh))
        	        {
        	            $lines .= fread($fh, 4096);
        	        }
        	        fclose($fh);
    
        	        $tmp_cert = tempnam('/tmp', 'file');
        	        unlink($tmp_cert);
        	        $helper->putFile($tmp_cert, $lines);
    
        	        $runas = ($helper->getAllowed('adminUser') ? 
                                        'root' : $helper->getLoginName());
        	        $ret = $helper->shell("/usr/sausalito/sbin/ssl_import.pl $tmp_cert --group=$group --type=caCert --ca-ident='$addCaIdent'", 
        	                    $output, $runas);
        	        if ($ret != 0)
        	        {
        	            // deal with error
        	            $error =& new CceError('huh', 0, 'cert', "[[base-ssl.sslImportError$ret]]");
        	            $errors = array($error);
        	        }

                }
	}
    }
    else // removing a ca cert
    {
        $current_cas = $cce->scalar_to_array($ssl['caCerts']);
        $removed_cas = stringToArray($removeCaIdent);
        
        $length = count($current_cas);
        for ($i = 0; $i < $length; $i++)
        {
            if (in_array($current_cas[$i], $removed_cas))
                unset($current_cas[$i]);
        }

        $set_value = $cce->array_to_scalar($current_cas);
        $ok = $cce->set($oid, 'SSL', array('caCerts' => $set_value));
        $errors = array_merge($errors, $cce->errors());
    }

    // did the operation succeed
    if (count($errors) == 0)
    {
        print $helper->toHandlerHtml("/base/ssl/siteSSL.php?group=$group",
				     array(), false);
        
        $helper->destructor();
        exit;
    }
}

$manager =& $factory->getPagedBlock('caManager');
if ($group)
{
    $vsite = $cce->get($oid);
    $fqdn = $vsite['fqdn'];
}
else
{
    $fqdn = '[[base-ssl.serverDesktop]]';
}

$manager->setLabel(
    $factory->getLabel('caManager', false, array('fqdn' => $fqdn)));

$manager->processErrors($errors);

// add hidden field, so we know we were here before
$manager->addFormField($factory->getBoolean('save', 1, ''));
$manager->addFormField($factory->getTextField('group', $group, ''));

$action =& $factory->getMultiChoice('caAction');
$add =& $factory->getOption('addCA', true);
$add->addFormField(
    $factory->getTextField('addCaIdent'),
    $factory->getLabel('caIdent'));

$upload =& $factory->getFileUpload('caCert');
$upload->setEmptyMessage($factory->i18n->get('[[base-ssl.caCert_empty]]'));
$add->addFormField(
    $upload,
    $factory->getLabel('caCert'));
    
$action->addOption($add);

$cas = $cce->scalar_to_array($ssl['caCerts']);
if (count($cas) && $cas[0] != '')
{
    $remove =& $factory->getOption('removeCA');
    $ca_list =& $factory->getMultiChoice('removeCaIdent', $cas);
    $ca_list->setMultiple(true);
    
    $remove->addFormField(
        $ca_list,
        $factory->getLabel('removeCAIdent'));

    $action->addOption($remove);
}

$manager->addFormField($action, $factory->getLabel('caAction'));

$page =& $factory->getPage();
$form =& $page->getForm();

$manager->addButton($factory->getSaveButton($form->getSubmitAction()));
$manager->addButton(
    $factory->getCancelButton("/base/ssl/siteSSL.php?group=$group"));

print $page->toHeaderHtml();
print $manager->toHtml();
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
