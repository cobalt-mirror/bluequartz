<?php
// Author: Brenda Mula
// Copyright 2000 Cobalt Networks.  All rights reserved

include("ServerScriptHelper.php");
include("uifc/Button.php");

// Base setup
$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-backup", "/base/backup/restoreHandler.php
");
$i18n = $serverScriptHelper->getI18n("base-backup");
$page = $factory->getPage();
$restoreObj = $cceClient->getObject("Backup", array("backupDate" => $backupTime));

// Table Header
$block = $factory->getPagedBlock("backupHeader");


// Execute tar tvf of home

$LastLine = exec("/bin/tar tvf $backupArchiveDir/base.tar", $AllOutput, $ReturnValue);
$scrollList = $factory->getScrollList("SelectiveRestoreHeader", array("restore","permission", "fileowner", "filesize", "date", "File"));

for ($index = 0; $index < count($AllOutput); $index++)
{

 // Ugly, I am sure there is a more efficient way to do this,  Help!!
 if ( ereg("^([dsrwx-]+) ([A-Za-z0-9]+/[A-Za-z0-9]+)([ ]+[0-9]+) ([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9:]+)(.*)$",$AllOutput[$index], $parse))

$permissions = $parse[1];
$filesize    = $parse[2]; 
$fileowner   = $parse[3]; 
$date        = $parse[4];
$file        = $parse[5];

$scrollList->addEntry(array(
	$factory->getBoolean("restore".$header_msgno, false),
	$factory->getTextField("", $permissions, "r"),
	$factory->getTextField("", $filesize, "r"),
	$factory->getTextField("", $fileowner, "r"),
	$factory->getTextField("", $date, "r"),
	$factory->getTextField("", $file, "r")));

}

$block->AddFormField(
	$scrollList,
	$factory->getLabel("SelectiveRestoreHeader"));

// Add Standard buttons at bottom of page.
$savebutton = $factory->getButton($page->getSubmitAction(), "Restore");
$cancelbutton = $factory->getCancelButton("/base/backup/restoreFromHistory.php");

$serverScriptHelper->destructor();
?>

<?php print($page->toHeaderHtml()); ?>

<?php print($scrollList->toHtml()); ?>

<p>
<?php print($savebutton->toHtml()); ?>
<?php print($cancelbutton->toHtml()); ?>



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

