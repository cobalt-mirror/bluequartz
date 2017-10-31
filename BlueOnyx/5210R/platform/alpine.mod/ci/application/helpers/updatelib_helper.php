<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// $Id: updateLib.php

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
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. 
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.

*/
?>