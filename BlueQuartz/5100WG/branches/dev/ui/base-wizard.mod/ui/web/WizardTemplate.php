<?php

function WizLoadPage($pagename, &$page)
{
	WizDebug("WizLoadPage: $pagename\n");

	$code = array();

	# load common code
	WizLoadCodeFile("WizCommon.code", $code);

	# load page specific code
	WizLoadCodeFile("$pagename.code", $code);

	# load page itself
	WizLoadTemplateFile("$pagename.html", $page, $code);
}

function WizLoadCodeFile($filename, &$code)
{
	WizDebug("WizLoadCodeFile: $filename\n");

	if (!file_exists($filename))
	{
		WizDebug("WizLoadCodeFile: cannot find '$filename'\n");
		return;
	}

	$fileArray = file($filename);

	# Loop through file loading fragments into the array
	$lineno = 0;
	while ($lineno < count($fileArray))
	{
		if (substr($fileArray[$lineno], 0, 2) == "##")
		{
			$label = trim($fileArray[$lineno]);
			$lineno += 1;

			$code[$label] = "";
			while ($lineno < count($fileArray) && 
				     substr($fileArray[$lineno], 0, 2) != "##")
			{
				$code[$label] .= $fileArray[$lineno];
				$lineno += 1;
			}
			$code[$label] = trim($code[$label]);
		}
	}
}

function WizLoadTemplateFile($filename, &$page, $code)
{
	WizDebug("WizLoadTemplateFile: $filename\n");

	if (!file_exists($filename))
	{
		WizDebug("WizLoadTemplateFile: cannot find '$filename'\n");
		return;
	}

	$fileArray = file($filename);
	$page = implode("", $fileArray);

	$codeFragments = array_keys($code);

	for ($c = 0; $c < count($codeFragments); $c++)
	{
		$page = str_replace($codeFragments[$c], $code[$codeFragments[$c]], $page);
		// print $codeFragments[$c] . " - ".  $code[$codeFragments[$c]] . "\n"  ;
	}
}



function WizEvalString($string)
{
	global $WizGlobalVars;
	$pos = 0;

	WizDebug("WizEvalString:\n");
	// Find PHP fragments in the string
	while ( ($pos = strpos($string, "<?")) !== FALSE )
	{
		// Locate the end of the PHP fragment
		$pos2 = strpos( $string, "?>", $pos + 2);

		// Evaluate the PHP and capture the output
		ob_start();
		eval( "global $WizGlobalVars; " . substr( $string, $pos + 5, $pos2 - $pos - 5) );
		$value = ob_get_contents();
		ob_end_clean();

		// Replace the PHP with the result of running it
		$string = substr($string, 0, $pos) . $value . substr($string, $pos2 + 2);
	}

	return $string;
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

