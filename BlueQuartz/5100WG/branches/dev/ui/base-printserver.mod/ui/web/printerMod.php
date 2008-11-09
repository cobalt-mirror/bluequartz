<?php
include("ServerScriptHelper.php");
 
$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-printserver", "/base/printserver/printerModHandler.php?oldPrinterName=".$printerName); 
$cceClient = $serverScriptHelper->getCceClient();
$i18n = $serverScriptHelper->getI18n();

$printer=$cceClient->getObject("Printer", array("name"=>$printerName));

$page = $factory->getPage();

$block = $factory->getPagedBlock("modPrinter");

$block->addFormField(
	$factory->getTextField("printerNameField", $printer["name"]),
	$factory->getLabel("printerNameField")
);

$usb = $factory->getOption("usb");
$network = $factory->getOption("network");

$network->addFormField(
        $factory->getNetAddress("printerHostNameField", $printer["hostname"]),
        $factory->getLabel("printerHostNameField")
);

$network->addFormField(
        $factory->getTextField("printerSpoolField", $printer["spool"]),
        $factory->getLabel("printerSpoolField")
);

$location = $factory->getMultiChoice("locationField");
 
$location->addOption($usb);
$location->addOption($network);

if($printer["location"] == "usb"){
	$location->setSelected(0, true);
}else{
	$location->setSelected(1, true);
}

$block->addFormField(
        $location,
        $factory->getLabel("locationField")
);


$up=$factory->getFileUpload("ppdFileField");
$up->setOptional(true);
$block->addFormField(
	$up,
	$factory->getLabel("ppdFileField")
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton($factory->getCancelButton("/base/printserver/printerList.php"));

$serverScriptHelper->destructor();

?>
<?php print($page->toHeaderHtml()); ?>

<?php print($block->toHtml()); ?>
<SCRIPT>
 
function checkName(element){
        var foo = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        for (var i = 0; i < element.value.length; i++){
                if(foo.indexOf(element.value.charAt(i)) == -1){
                        top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
                        return false;
                }
        }
        return true;
}
 
document.form.printerNameField.changeHandler=checkName;
 
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

