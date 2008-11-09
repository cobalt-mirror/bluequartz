<?php
include("ServerScriptHelper.php");
 
$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-ipsec", "/base/ipsec/tunnelAddHandler.php"); 
$cceClient = $serverScriptHelper->getCceClient();
$i18n = $serverScriptHelper->getI18n();

$table=""; //for the javascript table

$page = $factory->getPage();

$block = $factory->getPagedBlock("addNewTunnel");

$enable=$factory->getMultiChoice("tunnelEnabledField");
$enable->addOption($factory->getOption("ignore"));
$enable->addOption($factory->getOption("start"));
$enable->addOption($factory->getOption("add"));

$block->addFormField(
        $enable,
        $factory->getLabel("tunnelEnabledField")
);

$block->addFormField(
	$factory->getTextField("tunnelNameField"),
	$factory->getLabel("tunnelNameField")
);

$profilesDropDown = $factory->getMultiChoice("rightProfileField");
$profiles=$cceClient->find("IpsecProfile");
$selectProf=true;

foreach($profiles as $profOid){
	$prof=$cceClient->get($profOid);
	$profilesDropDown->addOption($factory->getOption($i18n->interpolate($prof["name"]), $selectProf));
	$selectProf=false;
        $str="";
        if($prof["enableManual"])
                $str.="m";
        if($prof["enableAutoRsa"])
                $str.="r";
        if($prof["enableAutoSecret"])
                $str.="s";
        $table .='table["' . $prof["name"] . '"' . "]='$str';\n";	

	//addToJscriptTable($prof);
}

$block->addFormField(
        $profilesDropDown,
        $factory->getLabel("rightProfileField")
); 

$block->addFormField(
        $factory->getIpAddress("rightField"),
        $factory->getLabel("rightField")
); 

$next=$factory->getIpAddress("rightNextField");
$block->addFormField(
        $next,
        $factory->getLabel("rightNextField")
);

$sub=$factory->getNetAddress("rightSubnetField","");
$sub->setOptional(true);

$block->addFormField(
        $sub,
        $factory->getLabel("rightSubnetField")
); 

$net=$factory->getIpAddress("rightNetmaskField");
$net->setOptional(true);

$block->addFormField(
        $net,
        $factory->getLabel("rightNetmaskField")
); 


$keying = $factory->getMultiChoice("keyingField", array(), array(), "rw", "testKeyingType");
$auto  = $factory->getOption("autoRsa");
$autoSecret = $factory->getOption("autoSecret");
$manual = $factory->getOption("manual");

$auto->addFormField(
	$factory->getTextField("authPubKeyField"),
	$factory->getLabel("authPubKeyField")
);

$keying->addOption($auto);

$autoSecret->addFormField(
        $factory->getTextField("authSecretKeyField"),
        $factory->getLabel("authSecretKeyField")
);

$keying->addOption($autoSecret);

$manual->addFormField(
        $factory->getTextField("authKeyField"),
        $factory->getLabel("authKeyField")
); 

$manual->addFormField(
        $factory->getTextField("encKeyField"),
        $factory->getLabel("encKeyField")
); 

$manual->addFormField(
	$factory->getTextField("spi"),
	$factory->getLabel("spi")
);

//manual keying sucks.
//$keying->addOption($manual);

$keying->setSelected(0, "true");

$block->addFormField(
        $keying,
        $factory->getLabel("keyingField")
); 

$comments = $factory->getTextBlock("tunnelCommentField");
$comments->setOptional(true);

$block->addFormField(
        $comments,
        $factory->getLabel("tunnelCommentField")
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton($factory->getCancelButton("/base/ipsec/tunnelList.php"));

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml());?>

<SCRIPT language="Javascript">

table=new Array;
<?php print($table);?>

msgTable=new Array;
msgTable["autoRsa"]="<?php print($i18n->interpolate("[[base-ipsec.autoRsa]]"))?>";
msgTable["autoSecret"]="<?php print($i18n->interpolate("[[base-ipsec.autoSecret]]"))?>";
msgTable["manual"]="<?php print($i18n->interpolate("[[base-ipsec.manual]]"))?>";

function lookupSymbol(str){
	if(str == "manual")
		return "m";
	if(str == "autoRsa")
		return "r";
	if(str == "autoSecret")
		return "s";
	return "";
}

function testKeyingType(element){
	var childFields = element.childFields;
	var prof=document.form.rightProfileField;
	var profName=prof[prof.selectedIndex].value;
	var allowed=table[prof[prof.selectedIndex].value];
	var invalidMessage="<?php print($i18n->interpolate("[[base-ipsec.invalidKeying]]"));?>";

	if(element.checked){
		if(allowed.indexOf(lookupSymbol(element.value)) == -1){
			//alert(element.value);
			top.code.error_invalidElement(element, top.code.string_substitute(top.code.string_substitute(invalidMessage, "[[VAR.profile]]", profName), "[[VAR.keying]]", msgTable[element.value]));
			return false;
		}
	}
	return top.code.MultiChoice_submitHandlerOption(element);
}

function testTunnelName(element){
	var foo = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789.-_";
	var invalid = "<?php print($i18n->interpolate("[[base-ipsec.tunnelNameField_invalid]]"));?>";


	if(!top.code.string_isAlpha(element.value.charAt(0))){
		top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
		return false;
	}

	for (var i = 0; i < element.value.length; i++){
		if(foo.indexOf(element.value.charAt(i)) == -1){
			top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
			return false;
		}
	}
	return true;
}

function testHex(element){
	var foo="";
	if(element.value.indexOf("0x") == 0){
		foo="abcdefABCDEF0123456789_"; //hex
        }else if(element.value.indexOf("0s") == 0){
		foo="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+/="; //base64
	}

	for (var i = 2; i < element.value.length; i++){
        	if(foo.indexOf(element.value.charAt(i)) == -1){
        		top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
        		return false;
		}
        }

	return true;
}

</SCRIPT>

<?php print($block->toHtml()); ?>

<SCRIPT language="Javascript">
document.form.tunnelNameField.changeHandler=testTunnelName;

document.form.authPubKeyField.changeHandler=testHex;
//document.form.authKeyField.changeHandler=testHex;
//document.form.encKeyField.changeHandler=testHex;
//document.form.spi.changeHandler=testHex;
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

