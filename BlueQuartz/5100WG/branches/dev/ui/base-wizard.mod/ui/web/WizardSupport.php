<?php 
$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-wizard");

function WizShowError($preamble)
{
  global $WizError;
	$serverScriptHelper = new ServerScriptHelper();

	if ($WizError[0] != "")
	{
		echo <<<END_HTML
				<tr>
					<td width="64" background="/libImage/Left.gif"><img src="/libImage/empty.gif" alt="" width="10" height="10" border="0"></td>
					<td valign="top" nowrap bgcolor="#f2f7ff" width="562" background="/libImage/AdminPersonal-Fill.gif">
END_HTML;

		if ($preamble != "")
		{
			echo "<div class='Setting-Description'>" . $preamble . "</div>";
			echo "<BR>";
		}

		echo <<<END_HTML
						<table border="0" cellspacing="0" cellpadding="0">
							<tr height="10">
								<td width="63" height="10"><img src="/libImage/Error-Top-left.gif" alt="" width="63" height="10" border="0"></td>
								<td bgcolor="#f1bcc2" height="10" background="/libImage/Error-Fill.gif"><img src="/libImage/empty.gif" alt="" width="10" height="10" border="0"></td>
								<td width="15" height="10"><img src="/libImage/Error-Top-Right.gif" alt="" width="15" height="10" border="0"></td>
							</tr>
							<tr>
								<td align="right" valign="top" width="63" background="/libImage/Error-Left-Fill.gif"><img src="/libImage/Error-Left-Top.gif" alt="" width="63" height="35" border="0"></td>
								<td rowspan="2" valign="top" bgcolor="#f1bcc2" background="/libImage/Error-Fill.gif"><div class="Setting-Error-Label">{$WizError}</div></td>
								<td rowspan="2" bgcolor="#f1bcc2" width="15" background="/libImage/Error-Fill.gif"><img src="/libImage/empty.gif" alt="" width="10" height="10" border="0"></td>
							</tr>
							<tr>
								<td align="right" valign="bottom" width="63" background="/libImage/Error-Left-Fill.gif"><img src="/libImage/Error-Left-Bottom.gif" alt="" width="63" height="13" border="0"></td>
							</tr>
							<tr>
								<td width="63"><img src="/libImage/Error-Bottom-Left.gif" alt="" width="63" height="10" border="0"></td>
								<td bgcolor="#f1bcc2" background="/libImage/Error-Fill.gif"><img src="/libImage/empty.gif" alt="" width="10" height="10" border="0"></td>
								<td width="15"><img src="/libImage/Error-Bottom-Right.gif" alt="" width="15" height="10" border="0"></td>
							</tr>
						</table>
					</td>
					<td width="64" background="/libImage/Right.gif"><img src="/libImage/empty.gif" alt="" width="10" height="10" border="0"></td>
				</tr>

END_HTML;
	}
}

function WizDecodeErrors($errors)
{
	$serverScriptHelper = new ServerScriptHelper();
	$i18n = $serverScriptHelper->getI18n("base-wizard");

	$errorStr = "";

	for ($i = 0; $i < count($errors); $i++)
	{
		$error = $errors[$i];
		$errorStr .= $i18n->getHtml($error->getMessage(), "", $error->getVars());

		$errorStr .= "<BR>";
	}

	return $errorStr;

}

function WizDetermineLocale()
{
	global $WizError;
	global $WizLocale;
	global $HTTP_ACCEPT_LANGUAGE;

	$serverScriptHelper = new ServerScriptHelper();
	$i18n = $serverScriptHelper->getI18n("base-wizard");
	$cceClient = $serverScriptHelper->getCceClient();
	$system = $cceClient->getObject("System");
	$user = $cceClient->getObject("User", array("name"=>"admin"));

	if($user["localePreference"] != "browser"){
		return $user["localePreference"];
	}

	$installedLocales = stringToArray($system["locales"]);
	$browserLocales = split(',', str_replace( "-", "_", $HTTP_ACCEPT_LANGUAGE));

	for($i = 0; $i < count($browserLocales); $i++)
	{
		for($j = 0; $j < count($installedLocales); $j++)
		{
			if(strcasecmp($browserLocales[$i], $installedLocales[$j]) == 0)
			{
				$WizLocale = $installedLocales[$j];
				$i = $j = 999; // last both loops
			}
		}
	}

	if ($WizLocale == "")
	{
		// Didn't find an exact match for the user's locale ... try to
		// find a partial match (e.g. we should use "fr" for "fr-ca")
		for($i = 0; $i < count($browserLocales); $i++)
		{
			for($j = 0; $j < count($installedLocales); $j++)
			{
				if(strncasecmp($browserLocales[$i], $installedLocales[$j], strlen($installedLocales[$j])) == 0)
				{
					$WizLocale = $installedLocales[$j];
					$i = $j = 999; // last both loops
				}
			}
		}
	}

	if ($WizLocale == "")
	{
		$WizLocale = "en";
	}

	// $plstr = implode(":", $installedLocales);
	// $blstr = implode(":", $browserLocales);
//	 $sys = $system["locales"];
//	 $WizError = "sys: $sys<BR>WizLocale: $WizLocale<BR>installedLocales: $plstr;<BR>browserLocales: $blstr;<BR>accept: $HTTP_ACCEPT_LANGUAGE";

	return $WizLocale;

}

function WizGetDateHTML($currDateTime)
{
	$serverScriptHelper = new ServerScriptHelper();
	$i18n = $serverScriptHelper->getI18n("palette");

	error_log("WizGetDateHTML: currDateTime $currDateTime\n", 3, "/tmp/wiz.log");

	if ($currDateTime != "")
	{
		// [0]: year, [1]: month, [2]: day, [3]: hour, [4]: minute
		$timeParts = explode(":", $currDateTime);
	}

	$html .= "<SELECT name='WizMonth' size='1'>";
	for ($i = 1; $i <= 12; $i++)
	{
		$selected = ($timeParts[1] == $i) ? "SELECTED" : "";
		$html .= "<OPTION value='$i' $selected>";
		$monthId = sprintf("%02d", $i) . "month";
		$html .= $i18n->get($monthId);
		$html .= "</OPTION>";
	}
	$html .= "</SELECT>";

	$html .= "<SELECT name='WizDay' size='1'>";
	for ($i = 1; $i <= 31; $i++)
	{
		$selected = ($timeParts[2] == $i) ? "SELECTED" : "";
		$html .= "<OPTION value='$i' $selected>";
		$html .= $i;
		$html .= "</OPTION>";
	}
	$html .= "</SELECT>";

	$html .= "<SELECT name='WizYear' size='1'>";
	for ($i = 2001; $i <= 2101; $i++)
	{
		$selected = ($timeParts[0] == $i) ? "SELECTED" : "";
		$html .= "<OPTION value='$i' $selected>";
		$html .= $i;
		$html .= "</OPTION>";
	}
	$html .= "</SELECT>";

	return $html;
}

function WizGetTimeHTML($currDateTime)
{
	$serverScriptHelper = new ServerScriptHelper();
	$i18n = $serverScriptHelper->getI18n("palette");

	error_log("WizGetTimeHTML: currDateTime $currDateTime\n", 3, "/tmp/wiz.log");

	if ($currDateTime != "")
	{
		// [0]: year, [1]: month, [2]: day, [3]: hour, [4]: minute
		$timeParts = explode(":", $currDateTime);
	}

	$clock24 = ($i18n->get("1400Hour") == "14");

	$html .= "<SELECT name='WizHour' size='1'>";
	for ($i = ($clock24 ? 0 : 1); $i <= ($clock24 ? 23 : 12); $i++)
	{
		$selected = ($timeParts[3] == $i + ($clock24 ? 0 : 12)) ? "SELECTED" : "";
		$html .= "<OPTION value='$i' $selected>";
		$html .= ($clock24) ? sprintf("%02d", $i) : $i;
		$html .= "</OPTION>";
	}
	$html .= "</SELECT>";

	$html .= "<SELECT name='WizMinute' size='1'>";
	for ($i = 0; $i <= 59; $i++)
	{
		$selected = ($timeParts[4] == $i) ? "SELECTED" : "";
		$html .= "<OPTION value='$i' $selected>";
		$html .= sprintf("%02d", $i);
		$html .= "</OPTION>";
	}
	$html .= "</SELECT>";

	if (! $clock24)
	{
		$html .= "<SELECT name='WizAMPM' size='1'>";
		$selected = ($timeParts[3] < 12) ? "SELECTED" : "";
		$html .= "<OPTION value='am' $selected>";
		$html .= $i18n->get("am");
		$html .= "</OPTION>";
		$selected = ($timeParts[3] >= 12) ? "SELECTED" : "";
		$html .= "<OPTION value='pm' $selected>";
		$html .= $i18n->get("pm");
		$html .= "</OPTION>";
		$html .= "</SELECT>";
	}

	return $html;
}

function WizGetCharSetHTML($locale = "")
{
	$serverScriptHelper = new ServerScriptHelper();
	$i18n = $serverScriptHelper->getI18n("palette");

	if ($locale == "")
	{
		$locales = $i18n->getLocales();
		$locale = $locales[0];
	}

	error_log("WizGetCharSetHTML: locale $locale\n", 3, "/tmp/wiz.log");

	switch ($locale)
	{
	case "ja":
		$html = "<meta http-equiv=\"content-type\" content=\"text/html; charset=Shift_JIS\">";
		break;

	case "zh_TW":
		$html = "<meta http-equiv=\"content-type\" content=\"text/html; charset=big5\">";
		break;

	case "zh_CN":
		$html = "<meta http-equiv=\"content-type\" content=\"text/html; charset=gb2312\">";
		break;

	default:
		$html = "<meta http-equiv=\"content-type\" content=\"text/html;charset=ISO-8859-1\">";
		break;
	}

	return $html;
}

function WizDebug($str)
{
	error_log($str, 3, "/tmp/wiz.log");
}

function WizDebugVar($varname)
{
	eval( "global $" . $varname . "; \$value = $" . $varname . ";");
	WizDebug("$varname: $value\n");
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

