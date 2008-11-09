<?php
// Author: Mike Waychison, Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: MultiFileUpload.php 259 2004-01-03 06:28:40Z shibuya $

include("ServerScriptHelper.php");
$serverScriptHelper = new ServerScriptHelper();

$product = $serverScriptHelper->getProductCode();
$isMonterey = ereg("35[0-9][0-9]R", $product);

// find form action depending on product
if ((!$isMonterey) && $serverScriptHelper->hasCCE())
  $formAction = "MultiFileUploadHandler.php";
else
  $formAction = "/base/webmail/MultiFileUploadHandler.php";

$factory = $serverScriptHelper->getHtmlComponentFactory("palette", $formAction);

$page = $factory->getPage();
$form = $page->getForm();
$formId = $form->getId();

$block = $factory->getPagedBlock("addAttachment");

$fileUpload = $factory->getFileUpload("fileUpload", "", 3);
$block->addFormField ($fileUpload, $factory->getLabel("fileToAttach"));

// we need a separate field to pass file name because the $fileUpload_name
// variable that PHP produce is wrong if the file name contains 0x5C characters
$block->addFormField ($factory->getTextField("fileUpload_name", "", ""));

$block->addButton($factory->getButton("javascript: upload()", "addAttachmentButton"));
$block->addButton($factory->getCancelButton("javascript: top.close()"));

/* print the page */
print $page->toHeaderHtml();
?>

<SCRIPT LANGUAGE="javascript">
function upload() {
  var form = document.<?php print($formId); ?>;
  var fileName = form.fileUpload.value;

  var appName = navigator.appName;
  var appVersion = navigator.appVersion;

  // NN on Mac URL encode the file name. so we need to decode it
  if(appName.indexOf("Netscape") != -1 && appVersion.indexOf("Mac") != -1)
    fileName = unescape(fileName);

  // find out file delimiter
  // Mac is "/" as well
  var delimiter = "/";
  if(appVersion.indexOf("Win") != -1)
    delimiter = "\\";

  // figure out the base name of the file
  var baseName = fileName.substring(fileName.lastIndexOf(delimiter)+1);

  // use form field to pass file name
  form.fileUpload_name.value = baseName;

  form.submit();
}
</SCRIPT>

<?php print $block->toHtml(); ?>

<SCRIPT LANGUAGE="javascript">
/* change the Max File Size to reflect the actual upload size */
document.<?php print $formId ?>.MAX_FILE_SIZE.value = top.maxFileSize;
</SCRIPT>

<?php print $page->toFooterHtml(); ?>/*
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
