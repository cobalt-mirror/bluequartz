<?php
/*
 * Copyright 2001, 2002 Sun Microsystems, Inc.  All rights reserved.
 * $Id: vsiteDel.php
 * 
 * tell cce to destroy the specified virtual site
 */

include_once("ServerScriptHelper.php");

$helper = new ServerScriptHelper();

// Only 'manageSite' should be here
if (!$helper->getAllowed('manageSite')) {
  header("location: /error/forbidden.html");
  return;
}

$cce = $helper->getCceClient();

// check if cce is suspended before trying anything
if ($cce->suspended() !== false) {
	if ($cce->suspended()) {
		$msg = $cce->suspended();
	} else {
		$msg = '[[base-cce.suspended,key=]]';
	}

	print $helper->toHandlerHtml("/base/vsite/$page",
				     array(new Error($msg)), false);
	$helper->destructor();
	exit;
}

//
//-----	Security checks:
//
//		We need to find out if the Vsite with that 'name' exists.
//		But we also need to make sure that it is under the ownership
//		of the currently logged in 'createdUser'. Of course user
//		'admin' has rights to delete all Vsites.
//

// Prep search array:
$exact = array('name' => $group);

// We're not admin, so we limit the search to 'createdUser' => $loginName:
if ($loginName != 'admin') {
		// If the user is not 'admin', then we only return Vsites that this user owns:
        $exact = array_merge($exact, array('createdUser' => $loginName));  
}

// Get a list of Vsite OID's:
$vsites = $cce->findx('Vsite', $exact, array(), "", "");

// At this point we should have one object. Not more and not less:
if (count($vsites) != "1") {
	// Don't play games with us!
	// Nice people say goodbye, or CCEd waits forever:
	$cce->bye();
	$helper->destructor();
	header("location: /error/forbidden.html");
	return;
}

// initialize status to avoid race conditions
fopen("http://localhost:444/status.php?statusId=remove$group&title=[[base-vsite.deletingSite]]&message=[[base-vsite.removingUsers]]&progress=0", "r");

$cmd = "/usr/sausalito/sbin/vsite_destroy.pl $group \"/base/vsite/$page\"";
$helper->fork($cmd, 'root');

print $helper->toHandlerHtml("/status.php?statusId=remove$group");

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
