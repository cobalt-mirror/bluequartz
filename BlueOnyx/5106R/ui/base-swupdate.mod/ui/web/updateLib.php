<?php
// Author: asun@cobalt.com
// Copyright (c) 2000 Cobalt Networks. All rights reserved.
// $Id: updateLib.php 259 2004-01-03 06:28:40Z shibuya $

// get swupdate splash directory
function updates_splashname(&$vendor, &$name, &$version, &$stage)
{
	return ".swupdate/$vendor/$name-$version/$stage";
}

function updates_splashdir()
{
	return "/usr/sausalito/ui/web";
}

function updates_prependdomain(&$string, &$vendor, &$name, &$version)
{
	$domain = "_swupdate:$vendor-$name-$version";
	$domain = str_replace('.', '_', $domain);
	return preg_replace("/\[\[(^\.)\]\]/", "[[$domain.\\1]]", $string);
}

function updates_prependsrc(&$string, &$vendor, &$name, &$version, &$stage)
{
	$location = updates_splashname($vendor, $name, $version, $stage) . '/';
	$string = preg_replace("/SRC=\"/i", "SRC=\"$location", $string);
	$string = preg_replace("/SRC=([^\"])/i", "SRC=$location\\1", $string);
}

function updates_geturloptions(&$cce, &$options)
{
	$system = $cce->getObject('System');
	$string = "";

	if (strstr($options, 'serialnum')) {
		$i = $system["serialNumber"];
		$string .= "serialnum=$i";
	}

	if (strstr($options, 'product')) {
		$i = $system["productIdentity"];
		$append = '';
		if ($string)
			$append = '&';
		$string .= $append . "product=$i";
	}

	if (strstr($options, 'vendor')) {
		$i = $system["productVendor"];
		$append = '';
		if ($string)
			$append = '&';
		$string .= $append . "vendor=$i";
	}

	return $string ? '?' . $string : '';
}

// provides functions for update light checking.
function updates_check(&$client) 
{
	// get SWUpdate settings
	$swUpdate = $client->getObject("System", array(), "SWUpdate");

	// see if there are any new packages
	$hasUpdates = "false";
	$packages = $client->getObjects("Package");
	for($i = 0; $i < count($packages); $i++)
	  if($packages[$i]["new"] && $packages[$i]["isVisible"] &&
	     $packages[$i]["installState"] != "Installed") {
	    $hasUpdates = "true";
	    break;
	  }

	return $hasUpdates;
}

function updates_getJS(&$hasUpdates)
{
return "<SCRIPT LANGUAGE=\"javascript\">
if(top.code != null && top.code.updateLight_repaintLight != null &&
   top.code._updateLight_hasUpdates != null && 
   top.code._updateLight_hasUpdates != $hasUpdates) {
  top.code._updateLight_hasUpdates = $hasUpdates;
  top.code.updateLight_repaintLight();
}
</SCRIPT>
";	
}
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
