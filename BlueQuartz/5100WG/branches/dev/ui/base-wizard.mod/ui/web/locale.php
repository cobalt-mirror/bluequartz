<?php
// Author: Kevin K.M. Chiu, Will DeHaan
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: locale.php 201 2003-07-18 19:11:07Z will $

include("ArrayPacker.php");
include("ServerScriptHelper.php");
include("uifc/PagedBlock.php");
include("uifc/Label.php");
include("uifc/Option.php");

$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-wizard", "/base/wizard/localeHandler.php");
$cceClient = $serverScriptHelper->getCceClient();
$i18n = $serverScriptHelper->getI18n("base-wizard");

$system = $cceClient->getObject("System");

// Installed locales:
$possibleLocales = stringToArray($system["locales"]);
// $possibleLocales = array_merge("browser", $possibleLocales);

// Skip this page if we have less than one installed locales
if(count($possibleLocales) < 1) {
	print "
<SCRIPT LANGUAGE=\"javascript\">
setTimeout(\"top.location.replace('/nav/flow.php?root=base_wizardLocale&goto=base_wizardLicense');\", 2);
</SCRIPT>
</BODY>
</HTML>
";
	exit;
}
else if (count($possibleLocales) == 1)
{
    // still run the handler page so admin's locale and the system locale
    // get set

     header("Location: /base/wizard/localeHandler.php?languageField=$possibleLocales[0]");
     exit;
}

$page = $factory->getPage();

$block = $factory->getPagedBlock("localeSettings");

$browser_locales = array();
$browser_locales = split(',', $serverScriptHelper->getLocalePreference($HTTP_ACCEPT_LANGUAGE));
for($i = 0; $i < count($browser_locales); $i++) {

  for($j = 0; $j < count($possibleLocales); $j++) {

    if($browser_locales[$i] == $possibleLocales[$j]) {
      $locale_match = $possibleLocales[$j];
      $i = $j = 999; // last both loops
    }

  }
} 

// $locale = $factory->getLocale("languageField", $localePreference);
$locale = $factory->getLocale("languageField", $locale_match);

$locale->setPossibleLocales($possibleLocales);
$block->addFormField(
  $locale,
  $factory->getLabel("localeField")
);

?>
<?php print($page->toHeaderHtml()); ?>

<?php print($i18n->getHtml("localeMessage")); ?>
<BR><BR>

<?php print($block->toHtml()); ?>

<INPUT TYPE="HIDDEN" NAME="localguess" VALUE="<?php print($locale_match); ?>">

<SCRIPT LANGUAGE="javascript">
// setup wizard will not expire for a whole day
top.code.session_keepAlive(24*60);
top.code._flow_showNavigation=1;
top.code._flow_showForwardNavigation=1;
top.code._flow_repaintNavigation();

bar = new Object(); //hack, hack, hack...
bar.focus = null;
bar.select = null;
</SCRIPT>

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

