<?
// This script exports users to a TSV
// format compatible with the import function:
//
// username<tab>fullname<tab>password<tab>email aliases	
// Multiple email aliases are separated with a space.
//
// If a site is specified, only users from that site are exported, and
// the user must be a site administrator for the given site.  If no
// site is given, all users on the system are exported and the user
// must be a server administrator.  For total user export, we ignore
// the site attribute entirely. If one needs to export/import multiple
// sites at the same time, the migration utility should be used.
// 
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: exportHandler.php 259 2004-01-03 06:28:40Z shibuya $

# FIXME: 
#  - put some security in here to restrict access
#  - handle sorting for other languages, sortname

include_once("ServerScriptHelper.php");
include_once("ArrayPacker.php");
include("utils/browser.php");

$helper = new ServerScriptHelper();
$cceClient = $helper->getCceClient();
$i18n = $helper->getI18n();

list($locale) = explode(",", $helper->getLocalePreference());

// FIND the user OIDS
if ( $group ) {
	$findvars = array("site" => $group);
} else {
 	$findvars = array();
}

$oids = $cceClient->findSorted("User", "name", $findvars);

$filename = "userlist.tsv";

browser_headers_for_download($filename, 'text');

// GET the objects
mt_srand(time());
for ($i = 0; $i < count($oids); $i++) {
	$user = $cceClient->get($oids[$i]);
	$userEmail = $cceClient->get($oids[$i], "Email");

	$aliases = implode(" ", stringToArray($userEmail["aliases"]));
	if ( $pwFormat == 'namePw')
		$password = $user['name'];
	else {
		// generate random 8 char password with a-zA-Z
		unset($password);
		while (strlen($password) < 8) {
			$char = 65 + mt_rand(0,57);
			if ( (90 < $char) && ($char < 97) )
				continue;
			$password .= chr($char);
		}
	}
	$output = $user['name'] . "\t" . $user['fullName'] .
		"\t$password\t$aliases\t" . $user['sortName'];
	if (preg_match("/^ja/i", $locale)) {
		print $i18n->encodeString($output, 'sjis', '', $locale) . "\n";
	} else {
		print $output . "\n";
	}
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
