<?php
include("ServerScriptHelper.php");
 
$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-ipsec", "/base/ipsec/profileAddHandler.php"); 
$cceClient = $serverScriptHelper->getCceClient();
$i18n = $serverScriptHelper->getI18n();

$page = $factory->getPage();

$block = $factory->getPagedBlock("addNewProfile");

$errors = $serverScriptHelper->getErrors();
$block->processErrors($errors);

$block->addFormField(
	$factory->getTextField("nameField"),
	$factory->getLabel("nameField")
);

$comment = $factory->getTextBlock("commentsField");
$comment->setOptional(true);
 
$block->addFormField(
        $comment,
        $factory->getLabel("commentsField")
);

$block->addDivider($factory->getLabel("autoSettings"));

$block->addFormField(
	$factory->getBoolean("enableAutoRsaField"),
	$factory->getLabel("enableAutoRsaField")
);

$block->addFormField(
        $factory->getBoolean("enableAutoSecretField"),
        $factory->getLabel("enableAutoSecretField")
);

$authField = $factory->getMultiChoice("authField");
$authField->addOption($factory->getOption("esp", "esp"));
$authField->addOption($factory->getOption("ah", "ah")); 

$block->addFormField(
        $authField,
        $factory->getLabel("authField")
); 

$block->addFormField(
	$factory->getBoolean("pfsField"),
	$factory->getLabel("pfsField")
);

$block->addFormField(
        $factory->getInteger("keylifeField"),
        $factory->getLabel("keylifeField")
); 

$block->addFormField(
        $factory->getInteger("rekeymarginField"),
        $factory->getLabel("rekeymarginField")
); 

$block->addFormField(
        $factory->getInteger("rekeyfuzzField"),
        $factory->getLabel("rekeyfuzzField")
); 

$block->addFormField(
        $factory->getInteger("keyingtriesField", "", 1, 1000),
        $factory->getLabel("keyingtriesField")
); 

$block->addFormField(
        $factory->getInteger("ikelifetimeField"),
        $factory->getLabel("ikelifetimeField")
); 

/**********************************************************

$block->addDivider($factory->getLabel("manualSettings"));

$block->addFormField(
        $factory->getBoolean("enableManualField"),
        $factory->getLabel("enableManualField")
); 

$manualAuthField = $factory->getMultiChoice("manualAuthField");
$esp=$factory->getOption("esp");
 
$espValueField=$factory->getMultiChoice("espAuthValueField");
$espValueField->addOption($factory->getOption("3des"));
$espValueField->addOption($factory->getOption("3des-md5-96"));
$espValueField->addOption($factory->getOption("3des-sha1-96"));
 
$esp->addFormField(
        $espValueField,
        $factory->getLabel("valueField")
);
 
 
$esp->addFormField(
        $factory->getInteger("espReplayField", "", 0, 64),
        $factory->getLabel("replay_windowField")
);
 
 
$ah=$factory->getOption("ah");
 
$ahType=$factory->getMultiChoice("ahAuthValueField");
$ahType->addOption($factory->getOption("hmac-md5-96"));
$ahType->addOption($factory->getOption("hmac-sha1-96"));
 
$ah->addFormField(
        $ahType,
        $factory->getLabel("valueField")
);
 
 
$ah->addFormField(
        $factory->getInteger("ahReplayField", "", 0, 64),
        $factory->getLabel("replay_windowField")
);

$manualAuthField->addOption($esp);
$manualAuthField->addOption($ah);

$manualAuthField->setSelected(0, "true");

$block->addFormField(
        $manualAuthField,
        $factory->getLabel("authField")
);

*********************************************************/

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton($factory->getCancelButton("/base/ipsec/profileList.php"));

$serverScriptHelper->destructor();

?>
<?php print($page->toHeaderHtml()); ?>

<?php print($block->toHtml()); ?>
<SCRIPT LANGUAGE="javascript">

function makeOptional(){
	var form=document.form;
	var mustSelectOne="<?php print($i18n->interpolate("[[base-ipsec.mustSelectKeying]]"))?>";

	if(form.enableAutoRsaField.value!=1 
	  && form.enableAutoSecretField.value!=1
	  && form.enableManualField.value!=1){
		top.code.error_invalidElement(form.enableAutoRsaField, mustSelectOne);
		return false;

	}

	if(form.enableAutoRsaField.value!=1 && form.enableAutoSecretField.value !=1){
		form.keylifeField.isOptional=true;
		form.rekeymarginField.isOptional=true;
		form.rekeyfuzzField.isOptional=true;
		form.keyingtriesField.isOptional=true;
		form.ikelifetimeField.isOptional=true;
	}
	

	//if(form.enableManualField.value!=1){
	//	form.manualAuthField.isOptional=true;
	//}


	return _Form_submitHandler_form();

}

document.form.onsubmit = makeOptional;
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

