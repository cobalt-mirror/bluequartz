<?
include("ServerScriptHelper.php");
include("ArrayPacker.php");

$serverScriptHelper = new ServerScriptHelper($sessionId);
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-backup",
	"/base/backup/restoreHandler.php");
$page = $factory->getPage();
$i18n = $factory->i18n;

print($page->toHeaderHtml());

$history = stringToHash($args);

// get the pagedblock
$block = $factory->getPagedBlock("restoreHeader");

// Backup Name
$block->addFormField(
        $factory->getTextField("backupTag", $history["NAME"], "r"),
        $factory->getLabel("backupName"));

// Backup date/time
$backupTime = $history["START_TIME"];
if (!$backupTime || $backupTime == "???") {
        $idate = $unknown;
} else {
        $idate = $i18n->strftime("%c", $backupTime);
}
$block->addFormField(
        $factory->getTextField("backupDate", $idate, "r"),
        $factory->getLabel("backupDate"));

// Backup archive dir
$block->AddFormField(
	$factory->getTextField("backupLocation", $history["LOCATION"], "r"),
	$factory->getLabel("backupLocation")
);

// to where do we want to restore
$restorePlace = $factory->getMultiChoice("restorePlace", 
	array("restoreTemp","restoreOver"));
$block->addFormField($restorePlace, $factory->getLabel("restorePlace"));

// hidden field to pass args to next page
$block->addFormField($factory->getTextField("args", $args, ""));

// restore/cancel button
$block->addButton($factory->getButton($page->getSubmitAction(), 
	"restoreButton"));
$block->addButton($factory->getCancelButton("/base/backup/restoreList.php"));

print($block->toHtml());
print($page->toFooterHtml()); 

$serverScriptHelper->destructor();

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

