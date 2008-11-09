<?php
// Author: Phil Ploquin, Tim Hockin
// Copyright 2000, Cobalt Networks.  All rights reserved.

include("ServerScriptHelper.php");
include("ArrayPacker.php");

$servhelp = new ServerScriptHelper($sessionId);
$factory = $servhelp->getHtmlComponentFactory("base-am");
$cce = $servhelp->getCceClient();
$i18n = $factory->i18n;

// collect the errors as we go
$errors = array();

$amobj = $cce->getObject("ActiveMonitor");
if ($amobj == null) {
        $msg = $factory->getTextBlock("", 
		$i18n->interpolate("[[base-am.amObjNotFound]]"), "r");
        print($msg->toHtml());
} else {
	$cce->set($amobj["OID"], "", array(
		"alertEmailList" => $alertEmailList,
		"enabled" => $enableAMField));
	$errors = array_merge($errors, $cce->errors());


	$items = stringToArray($itemsToMonitor);
	$names = $cce->names($amobj["OID"]);

//	print "$itemsToMonitor<br>";

	// for each namespace on ActiveMonitor
	for ($i=0; $i < count($names); ++$i) {
		$namespace = $cce->get($amobj["OID"], $names[$i]);
		if ($namespace["hideUI"]) {
			continue;
		}
		$val = 0;
		// try see if the nameTag for this namespace is in the list
		for ($j=0; $j < count($items); ++$j) {
//			print($i18n->get($namespace["nameTag"]) 
//				. "?=" . $items[$j] . "<br>");
			if ($namespace["NAMESPACE"] == $items[$j]) {
//				print "match<br>";
				$val = 1;
				break;
			}
		}
		/* only set it if it is a boolean change */
		if (($val && !$namespace["monitor"]) 
		 || (!$val && $namespace["monitor"])) {
//			print("<br>setting $names[$i] monitor = $val<hr>");
			/*
			// If we are changing an "aggregate" service, then
			// also enable/disable the typeData fields too.
			// (ie. if Email, then do SMTP, POP3, IMAP too)
			*/
			if ($namespace["type"] == "aggregate") {
				$amServices = split(" ",$namespace["typeData"]);
				foreach($amServices as $agServ) {
//					print "CAUGHT: " . $agServ . "<br>";
					$cce->set($amobj["OID"],$agServ,
						array("monitor" => $val)
					);
					$errors = array_merge(
							$errors, $cce->errors()
					);
				}
			}

			$cce->set($amobj["OID"], $names[$i], 
				array("monitor" => $val));
			$errors = array_merge($errors, $cce->errors());
		}
	}

	print($servhelp->toHandlerHtml("/base/am/amSettings.php", 
		$errors));
}

$servhelp->destructor();


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

