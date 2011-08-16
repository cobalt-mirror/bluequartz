<?php

// Author: 
//		Michael Stauber - Stauber Multimedia Design - http://www.solarspeed.net
// Copyright 2006-2009, Stauber Multimedia Design. All rights reserved.
// Copyright 2009 Team BlueOnyx. All rights reserved.
// Fri 03 Jul 2009 02:25:29 PM CEST
//

//
print("<FORM NAME=\"form\" ID=\"form\" ACTION=\"/base/console/console_logfilesHandler.php\" METHOD=\"POST\" ENCTYPE=\"multipart/form-data\" >\n");
print("<INPUT TYPE=\"HIDDEN\" NAME=\"_save\" VALUE=\"\">\n");
?>
<SCRIPT LANGUAGE="javascript">


if (!document.form && document.getElementById)
 document.form = document.getElementById("form");

document._form_form_wait = 'Please wait for the changes to complete.';

document.form.isActionAvailable = true;

function _Form_submitHandler_form() {
  var form = document.form;

  // call all handlers
  for(var i = 0; i < form.elements.length; i++) {
    element = form.elements[i];
    if(element.changeHandler != null && !element.changeHandler(element))
      return false;
    if(element.submitHandler != null && !element.submitHandler(element))
      return false;
  }

  return true;
}

document.form.onsubmit = _Form_submitHandler_form;
</SCRIPT>
<?
print("<p align=\"center\">\n");
$block = $factory->getPagedBlock("Logfile Viewer");
$block->processErrors($serverScriptHelper->getErrors());

$logfile_choices = array
            (
                "1" => "/var/log/cron",
                "2" => "/var/log/maillog",
                "3" => "/var/log/messages",
                "4" => "/var/log/secure",
                "5" => "/var/log/httpd/access_log",
                "6" => "/var/log/httpd/error_log",
                "7" => "/var/log/admserv/adm_access",
                "8" => "/var/log/admserv/adm_error"
            );

$logfile_choices_select = $factory->getMultiChoice("sol_view",array_values($logfile_choices));
$logfile_choices_select->setSelected("$selected", true);
$block->addFormField($logfile_choices_select,$factory->getLabel("sol_view"));

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

// Print Content:
print($block->toHtml());
print("</p>\n");
print("</FORM>\n");

//

?>
