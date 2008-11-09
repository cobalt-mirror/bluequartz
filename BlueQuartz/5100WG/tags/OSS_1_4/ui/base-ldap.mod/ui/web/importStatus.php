<?php
// Author: Mike Waychison <mwaychison@cobalt.com>
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: importStatus.php 3 2003-07-17 15:19:15Z will $

include("ServerScriptHelper.php");
include("uifc/PagedBlock.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-ldap");
$i18n = $serverScriptHelper->getI18n("base-ldap");

/* sanitize filename */
$status = ereg_replace("[^a-zA-Z0-9]", "_", $status);

/* open the log file */
$fh = fopen("/tmp/$status", "r");
$percent = trim(fgets($fh,8192));
$message = trim(fgets($fh,8192));


/* get message */
$message = $i18n->interpolate($message);

$page = $factory->getPage();


print($page->toHeaderHtml());

// reload if not completed
if($percent < 100) {
	$block = new PagedBlock($page, "importStatus", $factory->getLabel("importStatus", false));

	$block->addFormField(
		$factory->getTextField("statusField", $i18n->interpolate($message), "r"),
  		$factory->getLabel("statusField")
	);

	$block->addFormField(
		$factory->getBar("progressField", round($percent,2)),
		$factory->getLabel("progressField")
	);
	print($block->toHtml());
	print("<SCRIPT LANGUAGE=\"javascript\">setTimeout(\"location.reload()\", 3000);</SCRIPT>");
} else {
	/* show the errors! */
	$errorCount = fgets($fh, 8192);
	if ($errorCount) {
		$scrollList = $factory->getScrollList("importErrors", array(/*"distinctiveName",*/ "errorMessage"));
		$scrollList->setEntryCountTags("[[base-ldap.errorCountSingular]]", "[[base-ldap.errorCountPlural]]");
		$scrollList->setHeaderRowHidden(true);
		for ($i=0;$i<$errorCount;$i++) {
			$dn = trim(fgets($fh, 8192));
			$msg = trim(fgets($fh, 8192));
			if ( ereg("^\"(.*)\"$", $msg, $regs))
				$msg = $regs[1];
			$scrollList->addEntry( array(
//				$factory->getTextField("errorDn".$i,$dn,"r"),
				$factory->getTextField("errorMsg".$i,$i18n->interpolate(stripcslashes($msg)), "r")
			));
		}
		print $scrollList->toHtml()."<br>";
	} else {
		/* no errors */
		$scrollList = $factory->getScrollList("importSucceeded", array("message"));
		$scrollList->setEntryCountHidden(true);
		$scrollList->setHeaderRowHidden(true);
		$scrollList->addEntry( array( $factory->getLabel("importSucceededMessage")));
		print $scrollList->toHtml()."<br>";
	}	
	
	/* make a back button */
	$backButton = $factory->getBackButton("import.php");
	print $backButton->toHtml()."<br>";
}
print($page->toFooterHtml()); 

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

