<?php
/*
 * $Id: downloadlogs.php,v 1.6.2.2 2001/12/22 01:00:07 pbaltz Exp $
 * Copyright 2001 Sun Microsystems, Inc. All rights reserved.
 * Logfile collection and HTTP transfer to client browser
 */

include_once("ServerScriptHelper.php");
include_once("base/sitestats/ReportHelper.php");
include_once("utils/browser.php");

$type = $HTTP_GET_VARS['type'];
$group = $HTTP_GET_VARS['group'];

$ssh = new ServerScriptHelper();
$cce = $ssh->getCceClient();
$reporter = new ReportHelper($type, $group, $HTTP_ACCEPT_LANGUAGE,
			     $ssh->getStylePreference());

$data = $reporter->getData("/^x\s+(FR|LR)\s+(\S+)(\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+))?/");

foreach ($data as $line) {
	if ($line[1] == 'FR') {
		$begin = "$line[5]/$line[4]/$line[2]";
	} else if ($line[1] == 'LR') {
		$end = "$line[5]/$line[4]/$line[2]";
	}
}

if ($group != 'server') { 
	$oids = $cce->find('Vsite', array('name' => $group)); 
	$site = $cce->get($oids[0]);

	if($site['fqdn'] == '') {
		$site['fqdn'] = $group;
	}

	$pretty_filename = $site['fqdn'] . '.' . $type . '.log';
} else {
	$pretty_filename = 'server.' . $type . '.log';
}

browser_headers_for_download($pretty_filename, 'text');

$get_group = ($group == 'server') ? '' : $group;
$runas = $ssh->getAllowed('adminUser') ? 'root' : '';
$read_handle = $ssh->popen("/usr/local/sbin/grab_logs.pl --type=$type " .
	"--group=$get_group --begin=$begin --end=$end", "r", $runas);
while (!feof($read_handle)) {
	print fread($read_handle, 12288);
}

pclose($read_handle);

$ssh->destructor();
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
