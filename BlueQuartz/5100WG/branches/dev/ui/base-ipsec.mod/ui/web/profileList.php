<?php

include("ServerScriptHelper.php");
 
$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$i18n = $serverScriptHelper->getI18n();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-ipsec");

$page = $factory->getPage();

$back = $factory->getBackButton("/base/ipsec/tunnelList.php");

$scrollList = $factory->getScrollList("profileList", array("pname", "comment",
"listAction"), array());
$scrollList->setAlignments(array("left", "left", "center"));
$scrollList->setColumnWidths(array("", "30%", "1%"));
$scrollList->addButton(
        $factory->getAddButton(
                "javascript: location='/base/ipsec/profileAdd.php';"
                . " top.code.flow_showNavigation(false)",
                "[[base-ipsec.addProfile_help]]"));

$profiles = $cceClient->find("IpsecProfile");

for($i=0; $i<count($profiles); $i++){
	$profile=$cceClient->get($profiles[$i]);

$modify=$factory->getModifyButton("javascript: location='/base/ipsec/profileMod.php?profileName=".urlencode($profile["name"])."'; top.code.flow_showNavigation(false)");

$remove=$factory->getRemoveButton("javascript: confirmRemove('".$profile["name"]."', '".urlencode($profile["name"])."')");

if($profile["name"] == "Qube3"){
	$modify->setDisabled(true);
	$remove->setDisabled(true);
}

	$scrollList->addEntry(array(
		$factory->getTextField("", $i18n->interpolate($profile["name"]), "r"),
		$factory->getTextField("", $i18n->interpolate($profile["comments"]), "r"),
		$factory->getCompositeFormField(array($modify, $remove))
	), "", false, $i);
}

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>
 
<SCRIPT LANGUAGE="javascript">
function confirmRemove(displayName, sendName) {
  var message = "<?php print($i18n->interpolate("[[base-ipsec.removeProfileConfirm]]"))?>";
  message = top.code.string_substitute(message, "[[VAR.profileName]]", displayName);
 
  if(confirm(message))
    location = "/base/ipsec/profileDelete.php?profileName="+sendName;
}
</SCRIPT>
<?php print($scrollList->toHtml()); ?>
<BR>
<?php print($back->toHtml()); ?>
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

