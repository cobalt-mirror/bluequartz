<?php
// Author: Michael Stauber
// Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
// Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
// $Id: ButtonContainer.php

global $isButtonContainerDefined;
if($isButtonContainerDefined)
  return;
$isButtonContainerDefined = true;

include_once("uifc/ButtonContainer.php");

class ButtonContainer extends HtmlComponentFactory {
  //
  // private variables
  //

  //
  // public methods
  //

  // description: constructor
  function ButtonContainer($page, $id, $button, $i18n, $InvalidMessage, $EmptyMessage) {
    $this->BxPage = $page;
    $this->setId($id);
    $this->Button = $button;
    $this->i18n = $i18n;
  }

  // description: get the ID of the list
  // returns: an ID string
  // see: setId()
  function getId() {
    return $this->id;
  }

  // description: set the ID of the list
  // param: id: an ID string
  // see: getId()
  function setId($id) {
    $this->id = $id;
  }  

  function setAccess($access) {
    $this->access = $access;
  }

  function getValue() {
        return($this->id);
  }

  function isOptional() {
        return(FALSE);
  }

  // description: get all the form fields of the block
  // returns: an array of FormField objects
  // see: addFormField()
  public function &getFormFields() {
    return $this->formFields;
  }

  // description: add a form field to this block
  // param: formField: a FormField object
  // param: label: a label object. Optional
  //     hidden form fields are not shown and therefore do not need labels
  // param: pageId: the ID of the page the form field is in
  //     Optional if there is only one page
  // see: getFormFields()
  public function addFormField(&$formField, $label = "", $pageId = "", $errormsg = false) {
    $this->formFields[] =& $formField;
    $this->formFieldLabels[$formField->getId()] = $label;

    // Pass the information from the LabelObject's labels to the class BxPage() so that it can store them for us
    // until we need to pull that information:
    if (isset($label->page->Label)) {
      $this->BxPage->setLabel($formField->getId(), $label->page->Label['label'], $label->page->Label['description']);
    }
    else {
      $this->BxPage->setLabel($formField->getId(), "", "");
    }
  }

  function toHtml($style = "") {
    $id = $this->getId();
    $hidemyass = "display_none";
    $result = "<!-- ButtonContainer: Start -->\n";
    $result .= '<div class="button_bar clearfix">';
    $result .= $this->Button->toHtml();

//    $result .= '
//                <label title="addmx" class="tooltip right">
//                                    <button id="bx_toggle_add_button" class="bx_toggle_add_button no_margin_bottom div_icon has_text">
//                                      <div class="ui-icon ui-icon-plus"></div>
//                                        <span>Add</span>
//                                    </button>
//                                </label>
//                <label title="canceladdmx" class="tooltip right">
//                                    <button id="bx_toggle_cancel_button" class="light send_right bx_toggle_cancel_button no_margin_bottom div_icon has_text">
//                                      <div class="ui-icon ui-icon-closethick"></div>
//                                        <span>Cancel</span>
//                                    </button>
//                                </label>                                
//          ';

    $result .=  "\n</div>";

//    $button_header = "
//            <script type=\"text/javascript\">
//                        $(document).ready(function () {
//                              $('#bx_toggle_add_button').click(function(){
//                                  $('.hide-$id').removeClass(\"display_none\");
//                              });
//                              $('#bx_toggle_cancel_button').click(function(){
//                                  $('.hide-$id').addClass(\"display_none\");
//                              });
//                        });
//            </script>
//                      ";
//    $this->BxPage->setExtraHeaders($button_header);

    $formFields = $this->getFormFields();

    $result .= '
          <div class="' . $hidemyass . ' hide-' . $id . '">
            <h2 class="box_head">' . $this->i18n->getHtml($id) . '</h2>' . "\n";

    for($i = 0; $i < count($formFields); $i++) {
      $childField =& $formFields[$i];
      $this_fields_classes = $childField->getLabelType();
      $childId = $childField->id;
//      $childField->setLabelType("display_none hide-$childId $this_fields_classes");

                    // Now a bit of jQuery magic: We set an extra-header in BxPage which - on ticking
                    // the checkbox of this multichoice - makes the hidden elements visible. It hides
                    // them again when the checkbox is unticked:
//                    $extraheader = "
//                        <script type=\"text/javascript\">
//                          $(document).ready(function () {
//                              $('#bx_toggle_add_button').click(function(){
//                                  $('#$childId').addClass(\"required\");
//                              });
//                              $('#bx_toggle_cancel_button').click(function(){
//                                  $('#$childId').removeClass(\"required\");
//                              });
//                          });
//                        </script>
//                      ";
//                    $this->BxPage->ff_extra_headers[$childId] = $extraheader;

      $result .= $childField->toHtml();
    }

    $result .= "\n          </div>";
    $result .= "\n<!-- ButtonContainer: End -->\n";

    return $result;
  }
}
/*
Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT

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