<?php
include_once("ServerScriptHelper.php");
include_once("base/wizard/WizardSupport.php");
include_once("base/wizard/WizardTemplate.php");

global $WizError;
global $WizPage;
global $WizPrevious;
global $WizLocalTime;
global $WizNextPageOverride;

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient(); 
$i18n = $serverScriptHelper->getI18n("base-wizard");

$WizGlobalVars = "\$i18n, \$cceClient";

WizDebug("-------------------------\n");
WizDebug("WizPage: $WizPage\n");

if ($WizPage == "")
{
	WizDebug("WizPage not set; showing page 0\n");
	ShowWizardPage( 0 );
}
else
{
	if ($WizPrevious == "1")
	{
		if ($WizPreviousPage != "")
		{
			$WizPage = $WizPreviousPage + 1;
		}

		WizDebug("WizPrevious set; showing page ($WizPage - 1)\n");
		$WizPage -= 2;
	}
  else
	{
		ProcessWizardPage( $WizPage );
	}

	if ($WizError != "")
	{

		WizDebug("Got error: $WizError\n");

		ShowWizardPage( $WizPage );
	}
	else if ($WizNextPageOverride != "")
	{
		WizDebug("Got next page override: $WizNextPageOverride\n");
		ShowWizardPage( $WizNextPageOverride );
		$WizNextPageOverride = "";
	}
	else
	{
		ShowWizardPage( $WizPage + 1 );
	}
}

function ShowWizardPage( $pageno )
{
	global $PHP_SELF; # The path and name of this file

	$filename = "WizPage" . $pageno;

	// if ( file_exists( $filename ) )
	{
		WizDebug("Showing Page $pageno ($filename)\n");
		WizLoadPage($filename, $page);
		print WizEvalString($page);
	}
}

function ProcessWizardPage( $pageno )
{
	global $HTTP_USER_AGENT;
	$filename = "ProcessWizPage" . $pageno . ".php";

	if ( file_exists( $filename ) )
	{
		WizDebug("Processing Page $pageno ($filename)\n");
		include( "base/wizard/" . $filename );
	}
	else
	{
		echo "Error: Missing processing page '$filename'";
	}
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

