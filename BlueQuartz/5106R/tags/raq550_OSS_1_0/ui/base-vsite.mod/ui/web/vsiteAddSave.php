<?
/*
 * Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
 * $Id: vsiteAddSave.php,v 1.23 2001/12/18 05:02:38 pbaltz Exp $
 *
 * processes input from vsiteAdd.php page and redirects to the site list page
 */

include_once("ServerScriptHelper.php");
include_once("AutoFeatures.php");

$helper = new ServerScriptHelper($sessionId);
$cce = $helper->getCceClient();

$vsiteOID = $cce->create("Vsite", 
			 array(
				'hostname' => $hostname,
				'domain' => $domain,
				'fqdn' => ($hostname . '.' . $domain),
				'ipaddr' => $ipAddr,
				'webAliases' => $webAliases,
				'mailAliases' => $mailAliases,
				"mailCatchAll" => $mailCatchAll,
				'volume' => $volume,
				'maxusers' => $maxusers,
				'dns_auto' => $dns_auto,
			 )
			);

$errors = $cce->errors();

// setup disk information
if ($vsiteOID) {
	$cce->set($vsiteOID, 'Disk', array('quota' => $quota));
	$errors = array_merge($errors, $cce->errors());
}

/*
 * setup services only if the site was created successfully
 * any errors after site creation above are non-fatal
 */
if ($vsiteOID) {
	// handle automatically detected services
	list($servicesoid) = $cce->find("VsiteServices");
	$autoFeatures = new AutoFeatures($helper);
	$af_errors = $autoFeatures->handle("create.Vsite", 
					   array(
					   	"CCE_SERVICES_OID" => 
						    $servicesoid, 
						"CCE_OID" => $vsiteOID));

	$errors = array_merge($errors, $af_errors);

	/*
	 *  DATA PRESERVATION
	 *  This actually shows both examples of when and when not to use data
	 *  preservation.  In this instance, no fatal errors occurred, 
	 *  so data preservation is completely turned off.  Turning off data 
	 *  preservation in ServerScriptHelper only affects the toHandlerHtml 
	 *  method.  Basically, it causes toHanderHtml to act like it did 
	 *  previously.  This is not necessary and will actually happen
	 *  automatically if the $errors array is empty.
	 */
	print $helper->toHandlerHtml("/base/vsite/vsiteList.php", $errors,
				     false);
} else {
	/*
	 *  DATA PRESERVATION
	 *  In this case, fatal errors occured and the user will be asked 
	 *  to re-enter valid values.  This is accomplished by calling 
	 *  toHandlerHtml with the returnUrl argument set to the path to the 
	 *  script which called this handler script, so it can
	 *  display user entered data and give the user a chance to correct 
	 *  any invalid or non-unique field values.  Data preservation is 
	 *  on by default, so there is no need to do anything special before 
	 *  calling toHandlerHtml.
	 */
	print $helper->toHandlerHtml("/base/vsite/vsiteAdd.php", $errors);
}

// nice people say aufwiedersehen
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
