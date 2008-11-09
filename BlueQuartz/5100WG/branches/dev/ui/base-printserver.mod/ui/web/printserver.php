<?php
include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-printserver", "/base/printserver/printserverHandler.php");

function to_bool($v){

	if(!strcasecmp($v,"true")){
		return 1;
	}else if($v == 1){
		return 1;
	}else{
		return 0;
	}
}

$printserver = $cceClient->getObject("System", array(), "PrintServer");
$samba = $cceClient->getObject("System", array(), "WinShare");
$apple = $cceClient->getObject("System", array(), "AppleShare");

$page = $factory->getPage();
 
$block = $factory->getPagedBlock("printserverSettings", array("basic", "advanced"));

//basic
$block->addFormField(
	$factory->getBoolean("enablePrintServersField", $printserver["enable"]),
	$factory->getLabel("enableServersField"),
	"basic"
);


$block->addFormField(
        $factory->getBoolean("enableAppleshareField", to_bool($printserver["appleshare"]) && to_bool($apple["enabled"])),
        $factory->getLabel("enableAppleshareField"),
        "advanced"
);

$block->addFormField(
        $factory->getBoolean("enableSambaField", to_bool($printserver["samba"]) && to_bool($samba["enabled"])),
        $factory->getLabel("enableSambaField"),
        "advanced"
);

$block->addButton($factory->getSaveButton($page->getSubmitAction())); 

$printerList = $factory->getButton("/base/printserver/printerList.php","printerListButton");

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php print($printerList->toHtml()); ?>
<BR> 

<?php print($block->toHtml()); ?>
 
<?php print($page->toFooterHtml()); 
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

