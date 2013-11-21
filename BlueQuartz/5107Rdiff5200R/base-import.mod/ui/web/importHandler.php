<?
// Handles user import by accepting a TSV file of format:
// username<tab>fullname<tab>password<tab>email aliases	
//
// This script converts the input file to a format used by the more
// generic perl import script and then calls that script. An updating
// status page is shown to the user during the import.
//
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: importHandler.php 1259 2009-09-15 15:30:52Z shibuya $

include_once("ServerScriptHelper.php");
include_once("ArrayPacker.php");
include_once("uifc/PagedBlock.php");
include_once("Error.php");

$serverScriptHelper = new ServerScriptHelper();

// Only adminUser and siteAdmin should be here
if (!$serverScriptHelper->getAllowed('adminUser') &&
    !($serverScriptHelper->getAllowed('siteAdmin') &&
      $group == $serverScriptHelper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

$factory = $serverScriptHelper->getHtmlComponentFactory("base-import", 
				"/base/import/importHandler.php?count=$count&logfile=$logfile");
$i18n = $serverScriptHelper->getI18n("base-import");
$page = $factory->getPage();
$cce = $serverScriptHelper->getCceClient();

// check if cce is suspended
if ($cce->suspended() !== false)
{
	$msg = $cce->suspended() ? $cce->suspended() : '[[base-cce.suspended]]';
	print $serverScriptHelper->toHandlerHtml(
		"/base/import/import.php?group=$group", array(new Error($msg)));
	$serverScriptHelper->destructor();
	exit;
}

if ($locationField == 'upload' && $dataUpload == "none") {
	/*
	* No file was sent
	*/
	global $HTTP_REFERER;
	$scrollList = $factory->getScrollList("noFileSpecified",
						array("stats"));
	$scrollList->setEntryCountHidden(true);
	$scrollList->setHeaderRowHidden(true);
	$scrollList->addEntry(array($factory->getTextField("id",
					$i18n->interpolate("[[base-import.fileWasNoGood]]"), "r")));
	$cancelButton = $factory->getButton($HTTP_REFERER,
						"badFileButtonCancel");
	print $page->toHeaderHtml();
	print $scrollList->toHtml();
	print "<br>" . $cancelButton->toHtml();
	print $page->toFooterHtml();
	exit;
}

if (!$logfile) 
{
	if ( $locationField == 'url' )
		$fh = $serverScriptHelper->popen(
				"/usr/bin/wget -q -O - $urlField", "r");
	else
		$fh = @fopen($dataUpload, "r");

	// The popen() won't wait here... that happens later (if at all).
	if ( !$fh ) 
	{
		print($serverScriptHelper->toHandlerHtml(
					"/base/import/import.php?group=$group", 
					array(new Error("[[base-import.couldNotOpenFile]]")) ));
		$serverScriptHelper->destructor();
		exit;
	}

	// figure out the default quota size..
	if ($group) {
		$userDefaults = $cce->getObject('Vsite', 
						array('name' => $group), 'UserDefaults');
		$userShellDef = $cce->getObject('UserServices', array('site' => $group), 'Shell');
		$userFpxDef = $cce->getObject('UserServices', array('site' => $group), 'Frontpage');
		$vsiteShell = $cce->getObject('Vsite', array('name' => $group), 'Shell');
		$vsiteFpx = $cce->getObject('Vsite', array('name' => $group), 'Frontpage');
		$defaultShell = ($userShellDef['enabled'] && $vsiteShell['enabled']) ? 1 : 0;
		$defaultFpx = ($userFpxDef['enabled'] && $vsiteFpx['enabled']) ? 1 : 0;
	} else
		$userDefaults = $cce->getObject("System", array(), "UserDefaults");

	$defaultQuota = $userDefaults["quota"];
	
	$Piped = "User\tname\tfullName\tpassword\tEmail.aliases\tsortName\tDisk.quota";

	if ( $group )
	{
		$Piped .= "\tsite\tvolume\tShell.enabled";
		$vsite = $cce->getObject('Vsite', array('name' => $group));
	}

	$count = $i;
	while (!feof($fh)) 
	{
		$line = fgets($fh, 4096);
		if (trim($line) == "") 
		{
			$Piped .= "\nBLANK";
		} 
		else 
		{
			$line = rtrim($line);
			$peices = explode("\t", $line);
			$Piped .= "\nADD";
			# username
			$Piped .= "\t" . trim($peices[0]);	
			# full name
			$Piped .= "\t" . trim($peices[1]);	
			# password
			$Piped .= "\t" . trim($peices[2]);	
			# break up the Email.aliases
			if ($peices[3]) 
			{
				$peices[3] = ereg_replace(" ", ",", trim($peices[3]));
				$peices[3] = ereg_replace(",,", ",", $peices[3]);
				$aliases = explode(",", $peices[3]);
				$Piped .= "\t" . arrayToString($aliases);		
			} 
			else 
			{
				$Piped .= "\t";
			}
			#sortName
			if ($peices[4])
				$Piped .= "\t" . trim($peices[4]);
			else
				$Piped .= "\t" . "";
			#quota
			if ($peices[5])
				$Piped .= "\t" . trim($peices[5]);
			else
				$Piped .= "\t" . $defaultQuota;
			#Vsite group
			if ($group)
				$Piped .= "\t$group\t$vsite[volume]\t$defaultShell";
			$i++;
		}
	}

	// Wait 'til after we're done with $fh... then get it's return value
	// if we used the popen() call.
	$ret = 0;
	if ( $locationField == 'url' )
		$ret = pclose($fh);
	if ( $ret ) 
	{
		print($serverScriptHelper->toHandlerHtml(
					"/base/import/import.php?group=$group", 
					array(new Error("[[base-import.couldNotOpenFile]]")) ));
		$serverScriptHelper->destructor();
		exit;
	}
	
	$tmpFileName = tempnam("/tmp", "file");

	// FIXME?  race condition here?? notice that I create a file, then
	// I proceed to delete it..   this is done so that the logged in user
	// can create it (first copy is created by httpd)
	unlink($tmpFileName);
	$serverScriptHelper->putFile($tmpFileName, $Piped);

	$locales = $i18n->getLocales();

	$serverScriptHelper->shell(
		"/usr/sausalito/handlers/base/import/import.pl $tmpFileName locale=$locales[0]", 
		$fileName);

	$fileName = trim($fileName);
	print "
<SCRIPT LANGUAGE=\"javascript\">
location = \"/base/import/importHandler.php?count=$i&logfile=$fileName&group=$group\"
</SCRIPT>";
} 
else 
{
	# we have a log file.
	# show status

	# sanitize logfile name
	$logfile = ereg_replace("[^a-zA-Z0-9]", "_", $logfile);
	$fhData = $serverScriptHelper->getFile("/tmp/$logfile");
	$fhData = ereg_replace("\r", "", $fhData);
	$fhData = explode("\n", $fhData);
	array_pop($fhData); //get rid of blenk terminator
	$doneCount = trim(array_shift($fhData));
	$errorCount = trim(array_shift($fhData));
	$percent = ($count == 0) ? 100 : ( $doneCount / $count ) * 100;
	if ($percent < 100) {
		$block = new PagedBlock($page, "ImportStatus", $factory->getLabel("importStatus", false));
		#TODO: add message
		$block->addFormField(
			$factory->getTextField("none", $i18n->interpolate("[[base-import.creatingUsers]]", array("pos" => ($doneCount?$doneCount:"0"), "max" => $count)), "r"),
			$factory->getLabel("status")
		);
		$block->addFormField(
			$factory->getBar("progressField", round($percent,0)),
			$factory->getLabel("progressField")
		);
		print $page->toHeaderHtml();
		print "<SCRIPT LANGUAGE=\"javascript\">setTimeout(\"location.reload()\",5000);</SCRIPT>";
		print $block->toHtml();
		print $page->toFooterHtml();
		exit;
	} else {
		// done the import..
		print $page->toHeaderHtml();
		if ($errorCount) {
			// We have errors!
			$scrollList = $factory->getScrollList("importErrors", array("fullName", "name", "errorMessageHeader"), array(0,1));
			$scrollList->setEntryCountTags("[[base-import.errorCountSingular]]", "[[base-import.errorCountPlural]]");
			for ($i=0;$i<$errorCount;$i++) {
				# get the hash data
				$table = array();
				while (($line = array_shift($fhData))!="--") {
					ereg("^(.*)=(.*)$", $line, $regs);
					$table[$regs[1]] = $regs[2];
				}
				# get the messages
				$msg = "";
				while (($line = array_shift($fhData))!="--") {
					ereg("^(.*)$", $line, $regs);
					if($msg != "")
						$msg .= "\n";
					$msg .= trim($regs[1]);
				}
				
				$scrollList->addEntry( array(
					$factory->getTextField("fullName".$i,$table["fullName"],"r"),
					$factory->getTextField("name".$i,$table["name"], "r"),
					$factory->getTextField("errorMsg".$i,$msg, "r")
				));	
			}
			print $scrollList->toHtml()."<br>";
			$hidden = $factory->getTextField("count", $count, "");
			print $hidden->toHtml();
			$hidden = $factory->getTextField("logfile", $logfile, "");
			print $hidden->toHtml();
		} else {
			// we imported successfully
			$scrollList = $factory->getScrollList("importSucceeded", array("message"));
			$scrollList->setEntryCountHidden(true);
			$scrollList->setHeaderRowHidden(true);
			$scrollList->addEntry( array( $factory->getLabel("importSucceededMessage")));
			print $scrollList->toHtml()."<br>";
		}
		$backButton = $factory->getBackButton("/base/import/import.php?group=$group");
		print $backButton->toHtml();
		print $page->toFooterHtml();
	}
}

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
