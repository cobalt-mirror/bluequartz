<?php
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: exportCert.php
// pipe either the certificate signing reques or the private key 
// and certificate out to be saved by the user

include_once('ServerScriptHelper.php');
include_once("utils/browser.php");

// make sure the pipe to read from can be opened first
$helper = new ServerScriptHelper();

// Only serverSSL and siteAdmin should be here
if (!$helper->getAllowed('serverSSL') &&
    !$helper->getAllowed('manageSite') &&
    !($helper->getAllowed('siteAdmin') &&
      $group == $helper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

$cert = '';
$runas = ($helper->getAllowed('adminUser') ? 'root' : $helper->getLoginName());
if ($helper->shell("/usr/sausalito/sbin/ssl_get.pl $type $group", $cert, $runas)
	!= 0)
{
    // command failed return an error
    $error = new Error('[[base-ssl.sslGetFailed]]');
    print $helper->toHandlerHtml('/base/ssl/siteSSL.php?group=' . $group, 
                array($error), 0);
    exit;
}        
else
{
    // set header information based on whether they want the whole cert
    // or just a csr
    if ($type == 'cert')
    {
	$filename = 'ssl-certificate.txt';
        header('Content-Description: SSL Certificate and Private Key');
    }
    else if ($type == 'csr')
    {
	$filename = 'signing-request.txt';
        header('Content-Description: SSL Certificate Signing Request');
    }

    browser_headers_for_download($filename, 'text');

    // spew out everything ssl_get gives us
    print $cert;
}

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
