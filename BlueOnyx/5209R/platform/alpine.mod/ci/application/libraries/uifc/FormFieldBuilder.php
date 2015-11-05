<?php
// Author: Kevin K.M. Chiu
// $Id: FormFieldBuilder.php

// description:
// This class helps to build form field components.
//
// applicability:
// Any form field can use this class to build components.

global $isFormFieldBuilderDefined;
if($isFormFieldBuilderDefined)
  return;
$isFormFieldBuilderDefined = true;

include_once("System.php");
include_once("ArrayPacker.php");

class FormFieldBuilder {

  var $Label = "";
  var $Description = "";

  //
  // public methods
  //

  function setRangeMin($min) {
    $this->RangeMin = $min;
  }

  function setRangeMax($max) {
    $this->RangeMax = $max;
  }

  // Sets the current label
  function setCurrentLabel($label) {
    $this->Label = $label;
  }

  // Returns the current label
  function getCurrentLabel() {
    $label = $this->Label;
    return $label;
  }

  // Sets the current label-description:
  function setDescription($description) {
    $this->Description = $description;
  }

  // Returns the current label-description:
  function getDescription() {
    return $this->Description;
  }

  // description: define if output should be sorted:
  function setSorted($sort) {
        $this->setSorted = $sort;
  }

  // description: returns if sorting is enabled:
  function getSorted() {
    if (!isset($this->setSorted)) {
      $this->setSorted = FALSE;
    }
    return $this->setSorted;
  }

  // description: defines where the labels are placed on formfields:
  function setLabelType($type) {
        $this->LabelType = $type;
  }

  // Returns where the labels are placed on formfields:
  function getLabelType() {
    if (!isset($this->LabelType)) {
         $this->LabelType = "label_side top";
    }
    return $this->LabelType;
  }

  // description: Allows to define column widths
  // param: array with column widths. Not in pixels, 
  // but 'col_25', 'col_33', 'col_50', 'col_100' instead.
  function setColumnWidths($columnWidths) {
    $this->columnWidths = $columnWidths;
  }

  // description: get the column widths for items in entries
  // returns: an array of widths
  // see: setColumnWidths()
  function getColumnWidths() {
    return $this->columnWidths;
  }

  // description: make a Divider in the same style as we make FormFields. Replaces addDivider()
  // returns: a divider FormField object
  function makeBxDivider($id, $label, $i18n) {
    if (!$label) {
      $label =  $i18n->get($id);
      $tooltip = $i18n->getWrapped($id);
    }
    else {
      $label =  $i18n->get($label);
      $tooltip = $i18n->getWrapped($label);
    }
    $out = '
      <div class="alert" style="line-height: 18px;">
          <label for="' . $id . '" title="' . $tooltip . '" class="tooltip left">' . $label . '</label>
          <img width="24" height="24" src="/.adm/images/icons/small/white/tags_2.png"></img>
      </div>' ."\n";

    return $out;
  }

  // description: make a checkbox field
  // param: id: the identifier of the field
  // param: value: the value of the HTML input field
  // param: access: "" for hidden, "r" for read-only, "w" for write-only
  //     and "rw" for read and write
  // param: checked: if it has a value checked, false otherwise
  // param: onClick: the onClick attribute of the field
  // param: extraClasses: extra stylesheet classes to pass on
  // returns: HTML that represents the field
  function makeCheckboxField($id, $value, $access, $i18n, $onClick = "", $extraClasses = "") {

    // If a Label and Description are set, we use them. If not, then we
    // calculate these based on the ID of the FormObject:
    if ((isset($this->Label)) && (strlen($this->Label) > "0")) {
      $label = $this->Label;
    }
    else {
      $label = $i18n->getHtml($id);
    }
    if ((isset($this->Description)) && (strlen($this->Description) > "0")) {
      $helptext = $this->Description;
    }
    else {
        $h = $id . '_help';
        $helptext = $i18n->getWrapped("[[$h]]");
    }

    if ($access == "r") {
        // Single checkbox:
        if ((!is_array($value))) {
          if ($value == "1") {
            $helptext = $i18n->getWrapped("[[palette.enabled]]") . "<br>" . $i18n->getHtml("[[palette.notprivileged]]");
            $fields = ' <label for="' . $id . '" title="' . $helptext . '" class="tooltip hover uniform"><div class="ui-icon ui-icon-check"></div></label>';
            $fields .= "\n" . $this->makeHiddenField($id, $value);
          }
          else {
            $helptext = $i18n->getWrapped("[[palette.disabled]]") . "<br>" . $i18n->getHtml("[[palette.notprivileged]]");
            $fields = ' <label for="' . $id . '" title="' . $helptext . '" class="tooltip hover uniform"><div class="ui-icon ui-icon-cancel"></div></label>';
            $fields .= "\n" . $this->makeHiddenField($id, $value);
          }
        }
        else {
          // $value is an array for multiple checkboxes and contains array(id => value) for each instead:
          $fields = '';
          foreach ($value as $id_key => $setting) {
            if ($setting == "1") {
              $helptext = $i18n->get($id_key) . ": " . $i18n->getWrapped("[[palette.enabled]]") . "<br>" . $i18n->getWrapped("[[palette.notprivileged]]");
              $fields .= '<label for="' . $id_key . '" title="' . $helptext . '" class="tooltip hover uniform">
                <button class="light tiny div_icon has_text"><div class="ui-icon ui-icon-check"></div>
                  <span>
                    ' . $i18n->get($id_key) . '
                  </span>
                </button>
              </label>';
              $fields .= "\n" . $this->makeHiddenField($id_key, $value);
            }
            else {
                // At this time we only want to show the active selections, so the following lines are commented out:
//              $helptext = $i18n->getWrapped($id_key) . "<br>" . $i18n->getWrapped("[[palette.disabled]]") . "<br>" . $i18n->getWrapped("[[palette.notprivileged]]");
//              $fields .= '<label for="' . $id_key . '" title="' . $helptext . '" class="tooltip hover">
//                <button class="light tiny div_icon has_text"><div class="ui-icon ui-icon-cancel"></div>
//                  <span>
//                    ' . $i18n->get($id_key) . '
//                  </span>
//                </button>
//              </label>';
              $fields .= "\n" . $this->makeHiddenField($id_key, $value);
            }
          }
        }
    }
    elseif (($access == "rw") || ($access == "w")) {
        // Single checkbox:
        if ((!is_array($value))) {
          $checked = $value ? "CHECKED" : "";
          $value = ' VALUE="' . htmlspecialchars($value) . '"';
          $value = ' ';
          $fields = '<input type="checkbox" name="' . $id . '" class="mcb-' . $id . '" id="' . $id . '"' . $value . ' ' . $checked . '/>';
        }
        else {
          // $value is an array for multiple checkboxes and contains array(id => value) for each instead:
          $fields = '';
          foreach ($value as $id_key => $setting) {
            $checked = $setting ? "CHECKED" : "";
            $setting = ' VALUE="' . htmlspecialchars($setting) . '"';
            $helptext = $i18n->getWrapped($id_key . "_help");
            $fields .= '
            <label for="' . $id_key . '" title="' . $helptext . '" class="tooltip hover uniform">
              <input type="checkbox" name="' . $id_key . '" class="mcb-' . $id . '" id="' . $id_key . '"' . $setting . ' ' . $checked . '/>' . $i18n->get($id_key) . '
            </label>' . "\n";
          }
        }
    }
    else {
       return $this->makeHiddenField($id, $value);
    }

    // Handle extraClasses:
    if ($extraClasses != "") {
      $extraClasses = " " . $extraClasses;
      $section_start = "<div class=\"section\">\n";
      $section_end = "</div>\n";
    }
    else {
      $section_start = "";
      $section_end = "";
    }
    if ($extraClasses != " nolabel") {
      $out = '
                                      <fieldset class="label_side top uniform' . $extraClasses . '">
                                        ' . $section_start . '
                                              <label for="' . $id . '" title="' . $helptext . '" class="tooltip hover uniform">' . $label . '</label>
                                              <div>' .
                                                $fields . '
                                              </div>
                                        ' . $section_end . '
                                      </fieldset>';
    }
    else {
      $out = '
                                        ' . $section_start . '
                                              <div>' .
                                                $fields . '
                                              </div>
                                        ' . $section_end;
    }

    return $out;
  }

  // description: make a checkbox field
  // param: id: the identifier of the field
  // param: value: the value of the HTML input field
  // param: access: "" for hidden, "r" for read-only, "w" for write-only
  //     and "rw" for read and write
  // param: checked: if it has a value checked, false otherwise
  // param: onClick: the onClick attribute of the field
  // param: extraClasses: extra stylesheet classes to pass on
  // returns: HTML that represents the field
  function makeRadioField($id, $value, $access, $i18n, $onClick = "", $extraClasses = "") {

    // If a Label and Description are set, we use them. If not, then we
    // calculate these based on the ID of the FormObject:
    if ((isset($this->Label)) && (strlen($this->Label) > "0")) {
      $label = $this->Label;
    }
    else {
      $label = $i18n->getHtml($id);
    }
    if ((isset($this->Description)) && (strlen($this->Description) > "0")) {
      $helptext = $this->Description;
    }
    else {
        $h = $id . '_help';
        $helptext = $i18n->getWrapped("[[$h]]");
    }

    if ($access == "r") {
        // Single radio:
        if ((!is_array($value))) {
          if ($value == "1") {
            $helptext = $i18n->getWrapped("[[palette.enabled]]") . "<br>" . $i18n->getWrapped("[[palette.notprivileged]]");
            $fields = ' <label for="' . $id . '" title="' . $helptext . '" class="tooltip hover uniform"><div class="ui-icon ui-icon-check"></div></label>';
            $fields .= "\n" . $this->makeHiddenField($id, $value);
          }
          else {
            $helptext = $i18n->getWrapped("[[palette.disabled]]") . "<br>" . $i18n->getWrapped("[[palette.notprivileged]]");
            $fields = ' <label for="' . $id . '" title="' . $helptext . '" class="tooltip hover uniform"><div class="ui-icon ui-icon-cancel"></div></label>';
            $fields .= "\n" . $this->makeHiddenField($id, $value);
          }
        }
        else {
          // $value is an array for multiple radios and contains array(id => value) for each instead:
          $fields = '';
          foreach ($value as $id_key => $setting) {
            if ($setting == "1") {
              $helptext = $i18n->get($id_key) . ": " . $i18n->getWrapped("[[palette.enabled]]") . "<br>" . $i18n->getWrapped("[[palette.notprivileged]]");
              $fields .= '<label for="' . $id_key . '" title="' . $helptext . '" class="tooltip hover uniform">
                <button class="light tiny div_icon has_text"><div class="ui-icon ui-icon-check"></div>
                  <span>
                    ' . $i18n->get($id_key) . '
                  </span>
                </button>
              </label>';
              $fields .= "\n" . $this->makeHiddenField($id_key, $value);
            }
            else {
                // At this time we only want to show the active selections, so the following lines are commented out:
//              $helptext = $i18n->getWrapped($id_key) . "<br>" . $i18n->getWrapped("[[palette.disabled]]") . "<br>" . $i18n->getWrapped("[[palette.notprivileged]]");
//              $fields .= '<label for="' . $id_key . '" title="' . $helptext . '" class="tooltip hover">
//                <button class="light tiny div_icon has_text"><div class="ui-icon ui-icon-cancel"></div>
//                  <span>
//                    ' . $i18n->get($id_key) . '
//                  </span>
//                </button>
//              </label>';
              $fields .= "\n" . $this->makeHiddenField($id_key, $value);
            }
          }
        }
    }
    elseif (($access == "rw") || ($access == "w")) {
        // Single radio:
        if ((!is_array($value))) {
          $checked = $value ? "CHECKED" : "";
            $helptext = $i18n->getWrapped($id . "_help");
            $fields = '<label for="' . $id . '" title="' . $helptext . '" class="tooltip hover uniform">' . "\n";
            $fields .= '<input type="radio" name="' . $id . '" id="' . $id . '" value="' . htmlspecialchars($value) . '" ' . $checked . '/>' . $i18n->get($id) . '</label>' . "\n";

        }
        else {
          // $value is an array for multiple radios and contains array(id => value) for each instead:
          $fields = '';
          foreach ($value as $id_key => $setting) {
            $checked = $setting ? "CHECKED" : "";
            $setting = ' VALUE="' . htmlspecialchars($setting) . '"';
            $setting = '';
            $helptext = $i18n->getWrapped($id_key . "_help");
            $fields .= '<label for="' . $id_key . '" title="' . $helptext . '" class="tooltip hover uniform">' . "\n";
            $fields .= '<input type="radio" name="' . $id . '" id="' . $id . '" value="' . $id_key . '"' . $setting . ' ' . $checked . '/>' . $i18n->get($id_key) . '</label>' . "\n";
          }
        }
    }
    else {
       return $this->makeHiddenField($id, $value);
    }

    // Handle extraClasses:
    if ($extraClasses != "") {
      $extraClasses = " " . $extraClasses;
      $section_start = "<div class=\"section\">\n";
      $section_end = "</div>\n";
    }
    else {
      $section_start = "";
      $section_end = "";
    }

    $out = '
                                    <fieldset class="label_side top' . $extraClasses . '">
                                      ' . $section_start . '
                                            <label for="' . $id . '" title="' . $helptext . '" class="tooltip hover uniform">' . $label . '</label>
                                            <div>' .
                                              $fields . '
                                            </div>
                                      ' . $section_end . '
                                    </fieldset>';
    return $out;
  }

  // description: make a file upload field
  // param: id: the identifier of the field
  // param: access: "" for hidden, "r" for read-only, "w" for write-only
  //     and "rw" for read and write
  // param: size: the length of the field
  // param: maxLength: maximum number of characters
  //     that can be entered into the field
  // param: onChange: the onChange attribute of the field
  // returns: HTML that represents the field

  function makeFileUploadField($id, $access, $i18n, $size, $maxLength, $onChange) {
    if($access == "" || $access == "r")
      return $this->makeHiddenField($id, "");

    // find size
    $size = ($size > 0) ? "SIZE=\"$size\"" : "";

    // find max size
    $maxLength = ($maxLength > 0) ? "MAXLENGTH=\"$maxLength\"" : "";

    // log activity if necessary
    $system = new System();
    $logChange = ($system->getConfig("logPath") != "") ? "top.code.uiLog_log('change', 'FormField', '$id', this.value);" : "";

    // find onChange handler
    if($onChange != "" || $logChange != "")
      $onChange = "onChange=\"$logChange $onChange\"";

    if ($this->getLabelType() == "nolabel") {
      return "<INPUT TYPE=\"FILE\" NAME=\"$id\" $size $maxLength $onChange>\n";
    }
    else {

      // If a Label and Description are set, we use them. If not, then we
      // calculate these based on the ID of the FormObject:
      if ((isset($this->Label)) && (strlen($this->Label) > "0")) {
        $label = $this->Label;
      }
      else {
        $label = $i18n->getHtml($id);
      }
      if ((isset($this->Description)) && (strlen($this->Description) > "0")) {
        $helptext = $this->Description;
      }
      else {
          $h = $id . '_help';
          $helptext = $i18n->getWrapped("[[$h]]");
      }

      $out = '
                                      <fieldset class="label_side top">
                                              <label for="daysValid" title="' . $helptext . '" class="tooltip right uniform">' . $label . '<span></span></label>
                                              <div>
                                                <INPUT ID="' . $id . '" TYPE="FILE" NAME="' . $id . '" ' . $size . ' ' . $maxLength . ' ' . $onChange . '>
                                              </div>
                                      </fieldset>';
      return $out;
    }
  }

  // description: make a hidden field
  // param: id: the identifier of the field
  // param: value: the value of the HTML input field
  // returns: HTML that represents the field
  function makeHiddenField($id, $value = "", $useFormspecialchars=TRUE) {

    // HTML safe
    if ($useFormspecialchars == TRUE) {
      $value = formspecialchars($value);
    }

    return "<INPUT TYPE=\"HIDDEN\" NAME=\"$id\" VALUE=\"$value\">\n";
  }

  // description: make javascript for form fields
  // param: formField: the form field to generate javascript for
  // param: changeHandler: the Javascript function
  //     that is called when the form field change
  // param: submitHandler: the Javascript function
  //     that is called when the form field submits
  // returns: HTML that represents the field
  function makeJavaScript($formField, $changeHandler, $submitHandler) {
    $access = $formField->getAccess();
    if($access != "w" && $access != "rw")
      return "";

    $emptyMessage = $formField->getEmptyMessage();
    $invalidMessage = $formField->getInvalidMessage();
    $id = $formField->getId();
    $page = $formField->getPage();
    $form = $page->getForm();
    $formId = $form->getId();
    $isOptional = $formField->isOptional() ? "true" : "false";

    $javascript = "";

    return $javascript;
  }

  // description: make a password field
  // param: id: the identifier of the field
  // param: access: "" for hidden, "r" for read-only, "w" for write-only
  //     and "rw" for read and write
  // param: size: the length of the field
  // param: onChange: the onChange attribute of the field
  // returns: HTML that represents the field
  function makePasswordField($id, $value, $access, $i18n, $checktype="password", $isOptional, $size="", $maxLength="", $onChange="", $confirm="", $page, $checkPass=TRUE) {
    if($access == "" || $access == "r") {
      return $this->makeHiddenField($id, "");
    }

    // If a Label and Description are set, we use them. If not, then we
    // calculate these based on the ID of the FormObject:
    if ((isset($this->Label)) && (strlen($this->Label) > "0")) {
      $label = $this->Label;
    }
    else {
      $label = $i18n->getHtml($id);
    }
    if ((isset($this->Description)) && (strlen($this->Description) > "0")) {
      $helptext = $this->Description;
    }
    else {
        $h = $id . '_help';
        $helptext = $i18n->getWrapped("[[$h]]");
    }

    if ($isOptional == TRUE) {
      $optional_text = "(" . $i18n->get("[[palette.optional]]") . ")";
      $optional_line = '';
      $optional_class = '';
    }
    else {
      $optional_text = "";
      $optional_line = '<div class="required_tag tooltip hover left" title="' . get_i18n_error_for_inputvalidation($checktype, $i18n) . '"></div>';
      $optional_class = ' class="required password"';
    } 

    if ($checkPass == TRUE) {
      $page->setExtraHeaders('
          <script language="Javascript" type="text/javascript" src="/libJs/ajax_lib.js"></script>
          <script language="Javascript">
            <!--
              checkpassOBJ = function() {
                this.onFailure = function() {
                  alert("Unable to validate password");
                }
                this.OnSuccess = function() {
                  var response = this.GetResponseText();
                  document.getElementById("results").innerHTML = response;
                }
              }


              function validate_password ( word ) {
                checkpassOBJ.prototype = new ajax_lib();
                checkpass = new checkpassOBJ();
                var URL = "/gui/check_password";
                var PARAM = "password=" + word;
                checkpass.post(URL, PARAM);
              }

            //-->
          </script>
        ');
      $key_up = ' onKeyUp="validate_password(this.value)"';
      $check_results = '<div id="results">'. $i18n->get("pwCheckStr", "palette") . '</div>';
    }
    else {
      $key_up = '';
      $check_results = '';
    }
    
    $out = '
                            <fieldset class="label_side top">
                    <label for="' . $id . '" title="' . $helptext . '" class="tooltip right">' . $label . '<span>' . $optional_text . '</span></label>
                    <div>
                      <INPUT id="' . $id . '" TYPE="PASSWORD" NAME="' . $id . '" VALUE="" SIZE="20" ' . $key_up . $optional_class . '>
                      '. $check_results . "\n";

    if ($confirm == TRUE) {

      $out .= '
                      <INPUT id="_' . $id . '_repeat" TYPE="PASSWORD" NAME="_' . $id . '_repeat" VALUE="" SIZE="20"' . $key_up . $optional_class .'>' . $i18n->get("repeat", "palette");
    }
    $out .= '
                    ' . $optional_line . '
                    </div>
                  </fieldset>';
    return $out;

  }

  // description: make a text field
  // param: id: the identifier of the field
  // param: value: the value of the HTML input field
  // param: access: "" for hidden, "r" for read-only, "w" for write-only and "rw" for read and write
  // param: i18n: i18n Object for translations
  // param: checktype: Defines which routine from /gui/validation we're using to verify the form input with. Like 'ipaddr', 'email_address' and so on.
  // param: isOptional: Defines if this form field requires input or is optional (TRUE/FALSE)
  // param: size: the length of the field
  // param: maxLength: maximum number of characters that can be entered into the field
  // param: onChange: the onChange attribute of the field
  // returns: HTML that represents the field
  function makeTextField($id, $value, $access, $i18n, $checktype="", $isOptional, $size="", $maxLength="", $onChange="", $range="") {
    // If a Label and Description are set, we use them. If not, then we
    // calculate these based on the ID of the FormObject:
    if ((isset($this->Label)) && (strlen($this->Label) > "0")) {
      $label = $this->Label;
    }
    else {
      $label = $i18n->getHtml($id);
    }
    if ((isset($this->Description)) && (strlen($this->Description) > "0")) {
      $helptext = $this->Description;
    }
    else {
        $h = $id . '_help';
        $helptext = $i18n->getWrapped("[[$h]]");
    }
    $min_max = ' ';
    $shortval = $value;
    if (($maxLength > 0) && (strlen($value) > $maxLength)) {
     $shortval = substr($value, 0, $maxLength) . ' ...';
    }

    // Handle the range of the allowed values:
    $this->range = $range;
    if (isset($this->range)) {
      if ($this->range != '') {
        $this->range = '<span class="range_text">' . $this->range . '</span>';
      }
    }
    else {
      $this->range = '';
    }

    // Handle size of input fields:
    $this->size = $size;
    if ($this->size) {
      $size_tag = ' SIZE="' . $this->size . '"';
    }
    else {
      $size_tag = '';
    }

    // Handle maxLength of input fields:
    $this->maxLength = $maxLength;
    if ($this->maxLength) {
      $maxLength_tag = '  maxlength="' . $this->maxLength . '"';
    }
    else {
      $maxLength_tag = '';
    }

    switch($access) {
      case "":
        return $this->makeHiddenField($id, $value);

      case "html":
        // HTML safe
        $shortval = $shortval;
        $value = $value;
        $HTMLaccess = 'r';
        break;

      case "r":
        // HTML safe
        $shortval = htmlspecialchars($shortval);
        $value = htmlspecialchars($value);
        break;

      case "R":
        // assume $shortval is already html-safe
        return $shortval . $this->makeHiddenField($id, $value);

      case "w":
        $value = "";
        break;

      case "rw":
        // HTML safe
        $value = htmlspecialchars($value);
        break;
    }

    if (isset($HTMLaccess)) {
      $access = 'r';
    }

    // log activity if necessary
    $system = new System();
    $logChange = ($system->getConfig("logPath") != "") ? "top.code.uiLog_log('change', 'FormField', '$id', this.value);" : "";

    // find size
    $size = ($size > 0) ? "SIZE=\"$size\"" : "";

    // find max size
    $maxLength = ($maxLength > 0) ? "MAXLENGTH=\"$maxLength\"" : "";

    // find onChange handler
    if($onChange != "" || $logChange != "")
      $onChange = "onChange=\"$logChange $onChange\"";

    // Assemble HTML:
    if ($isOptional == TRUE) {
      $optional_text = "(" . $i18n->get("[[palette.optional]]") . ")";
      $optional_class = ' ';
      $optional_line = '';
    }
    else {
      $optional_text = '';
      $optional_class = 'required ';
      $optional_line = '<div class="required_tag tooltip hover left uniform" title="' . get_i18n_error_for_inputvalidation($checktype, $i18n) . '"></div>';
    }

    if ($access == "hidden") {
      $input_type = "hidden";
      $show_only = '';
      // Need to reset any existing 'required' stuff:
      $optional_text = '';
      $optional_class = '';
      $optional_line = '';
    }
    elseif ($access == "rw") {
      $input_type = "text"; 
      $show_only = '';
      // When checktype is "range" we need to set the minimum and maximum allowed vals into the input type, too:
      if ($checktype == "range") {
        $input_type = "text";
        // But only if we really have a min and max value:
        if ((isset($this->RangeMin)) && (isset($this->RangeMax))) {
          $min_max = ' min="' . $this->RangeMin . '" max="' . $this->RangeMax . '"';
        }
      }
    }
    else {
      //if (!isset($HTMLaccess)) {
        // Covers 'r' and anything else:
        $input_type = "hidden";
        $show_only = '<p>' . $value . '</p>';
        // Need to reset any existing 'required' stuff:
        $optional_text = '';
        $optional_class = '';
        $optional_line = '';
    }

    $out = '';
    if ($access != "hidden") {
      $out .= '
                                    <fieldset class="' . $this->getLabelType() . '">';
      if ($this->getLabelType() == "nolabel no_lines") {
        $out .= '<label></label>';
      }
      elseif ($this->getLabelType() != "nolabel") {
        $out .= '
                                            <label for="' . $id . '" title="' . $helptext . '" class="tooltip right uniform">' . $label . '<span class="info_text">' . $optional_text . '</span></label>';
      }
      else {
        $out .= "";
      }
      $out .= '
                                            <div>';
    }
    if (isset($HTMLaccess)) {
      $out .= $show_only;
    }
    else {
//      $out .= '
//                                                  <input type="' . $input_type . '"' . $size_tag . $maxLength_tag . ' name="' . $id . '" VALUE="' . $value . '" id="' . $id . '" class="' . $optional_class . $checktype . ' error"' . $min_max . '>
//                                                  ' . $show_only . $optional_line . $this->range;
      $out .= '
                                                  <input type="' . $input_type . '"' . $size_tag . $maxLength_tag . ' name="' . $id . '" VALUE="' . $value . '" id="' . $id . '" class="' . $optional_class . $checktype . ' "' . $min_max . '>
                                                  ' . $show_only . $optional_line . $this->range;

    }
    if ($access != "hidden") {
      $out .= '
                                            </div>
                                    </fieldset>';
    }
    return $out;
  }

  // description: make a select field
  // param: id: the identifier of the field
  // param: access: "" for hidden, "r" for read-only, "w" for write-only
  //     and "rw" for read and write
  // param: size: the SIZE attribute of the HTML SELECT tag
  // param: width: the minimum width
  //     Select field width is static in Netscape, dynamic in IE 
  // param: isMultiple: true if multiple items can be selected, false otherwise
  // param: formId: the ID of the form this field lives in
  // param: onChange: the onChange attribute of the field. Optional.
  // param: labels: an array of labels in string. Optional.
  //     Must have same length with values
  // param: values: an array of values in string. Optional.
  //     Must have same length with labels
  // param: selectedIndexes: an array of indexes of labels for the selected
  // returns: HTML that represents the field
  function makeSelectField($id, $access, $i18n, $size, $width, $isMultiple, $formId, $onChange = "", $labels = array(), $values = array(), $selectedIndexes = array(), $isOptional = FALSE) {

    switch($access) {
      case "":
        if(!$isMultiple)
          return $this->makeHiddenField($id, $values[$selectedIndexes[0]]);

        $result = "";
        for($i = 0; $i < count($selectedIndexes); $i++)
          $result .= $this->makeHiddenField($id, $values[$selectedIndexes[$i]]);
        return $result;

      case "r":
        if(!$isMultiple)
          // HTML safe
          return htmlspecialchars($labels[$selectedIndexes[0]]).$this->makeHiddenField($id, $values[$selectedIndexes[0]]);

        $result = "";
        for($i = 0; $i < count($selectedIndexes); $i++)
          // HTML safe
          $result .= htmlspecialchars($labels[$selectedIndexes[$i]]).$this->makeHiddenField($id, $values[$selectedIndexes[$i]]);
        return $result;

      // impossible case
      case "w":

      case "rw":
  $multiple = ($isMultiple) ? "MULTIPLE" : "";

  // log activity if necessary
  $system = new System();
  // log value if only one option can be selected
  $value = !$isMultiple ? ", this.options[this.selectedIndex].value" : "";
  $logChange = ($system->getConfig("logPath") != "") ? "top.code.uiLog_log('change', 'FormField', '$id' $value);" : "";

  $onChange = ($onChange != "" || $logChange != "") ? "onChange=\"$logChange $onChange\"" : "";

  // Uniform dropdown selectors look like the crap:
  //$result = "<SELECT $multiple NAME=\"$id\" ID=\"$id\" $onChange SIZE=\"$size\" class=\"uniform full_width\" style=\"opacity: 0;\">\n";
  // So we don't use them for now:
  $result = "  <SELECT $multiple NAME=\"$id\" ID=\"$id\" $onChange SIZE=\"$size\" class=\"selector\">\n";

  $selector_pairs = array();

  for($i = 0; $i < count($labels); $i++) {
    $label = $labels[$i];
    $value = $values[$i];

    // HTML safe
    $value = htmlspecialchars($value);
    $selector_pairs[$value] = $label;
  }

  // Do we need to sort?
  if ($this->getSorted()) {
    // Sort the array, but maintain key => value association:
    natsort($selector_pairs);
  }

  // Render output of the option values:
  $i = "0";
  foreach ($selector_pairs as $key => $value) {
    $selected = (in_array($key, $selectedIndexes)) ? "SELECTED" : "";
    $result .= "<OPTION VALUE=\"$key\" $selected>$value\n";
    $i++;
  }

  // do not put any new lines here because fields that use this code may
  // want no line breaks to be shown on screen
  $result .= "</SELECT>";

  // If a Label and Description are set, we use them. If not, then we
  // calculate these based on the ID of the FormObject:
  if ((isset($this->Label)) && (strlen($this->Label) > "0")) {
    $label = $this->Label;
  }
  else {
    $label = $i18n->getHtml($id);
  }
  if ((isset($this->Description)) && (strlen($this->Description) > "0")) {
    $helptext = $this->Description;
  }
  else {
      $h = $id . '_help';
      $helptext = $i18n->getWrapped("[[$h]]");
  }

  $optional_text = "(" . $i18n->get("[[palette.optional]]") . ")";

  if ($access == "hidden") {
    $input_type = "hidden";
    // Need to reset any existing 'required' stuff:
    $optional_text = '';
  }
  elseif ($access == "rw") {
    $input_type = "text"; 
  }
  else {
    // Covers 'r' and anything else:
    $input_type = "hidden";
    // Need to reset any existing 'required' stuff:
    $optional_text = '';
  }

  if ($isOptional == TRUE) {
    $optional_blurb = '<span>' . $optional_text . '</span>';
  }
  else {
    $optional_blurb = '';
  }

  $out = '';
  if (($access != "hidden") && ($this->getLabelType() != "nolabel")) {
    $out .= '
                                  <fieldset class="' . $this->getLabelType() . '">
                                          <label for="' . $id . '" title="' . $helptext . '" class="tooltip right uniform">' . $label . $optional_blurb . '</label>
                                          <div>';
  }
  $out .= '
                                                ' . $result;

  if (($access != "hidden") && ($this->getLabelType() != "nolabel")) {
    $out .= '
                                          </div>
                                  </fieldset>';
  }

  return $out;

    }
  }

  // description: make a select field but without the surrounding HTML-baggage:
  // param: id: the identifier of the field
  // param: access: "" for hidden, "r" for read-only, "w" for write-only
  //     and "rw" for read and write
  // param: size: the SIZE attribute of the HTML SELECT tag
  // param: width: the minimum width
  //     Select field width is static in Netscape, dynamic in IE 
  // param: isMultiple: true if multiple items can be selected, false otherwise
  // param: formId: the ID of the form this field lives in
  // param: onChange: the onChange attribute of the field. Optional.
  // param: labels: an array of labels in string. Optional.
  //     Must have same length with values
  // param: values: an array of values in string. Optional.
  //     Must have same length with labels
  // param: selectedIndexes: an array of indexes of labels for the selected
  // returns: HTML that represents the field
  function makeNakedSelectField($id, $access, $i18n, $size, $width, $isMultiple, $formId, $onChange = "", $labels = array(), $values = array(), $selectedIndexes = array()) {
    switch($access) {
      case "":
  if(!$isMultiple)
    return $this->makeHiddenField($id, $values[$selectedIndexes[0]]);

  $result = "";
  for($i = 0; $i < count($selectedIndexes); $i++)
    $result .= $this->makeHiddenField($id, $values[$selectedIndexes[$i]]);
  return $result;

      case "r":
  if(!$isMultiple)
    // HTML safe
    return htmlspecialchars($labels[$selectedIndexes[0]]).$this->makeHiddenField($id, $values[$selectedIndexes[0]]);

  $result = "";
  for($i = 0; $i < count($selectedIndexes); $i++)
    // HTML safe
    $result .= htmlspecialchars($labels[$selectedIndexes[$i]]).$this->makeHiddenField($id, $values[$selectedIndexes[$i]]);
  return $result;

      // impossible case
      case "w":

      case "rw":
  $multiple = ($isMultiple) ? "MULTIPLE" : "";

  // log activity if necessary
  $system = new System();
  // log value if only one option can be selected
  $value = !$isMultiple ? ", this.options[this.selectedIndex].value" : "";
  $logChange = ($system->getConfig("logPath") != "") ? "top.code.uiLog_log('change', 'FormField', '$id' $value);" : "";

  $onChange = ($onChange != "" || $logChange != "") ? "onChange=\"$logChange $onChange\"" : "";

  // Uniform dropdown selectors look like the crap:
  //$result = "<SELECT $multiple NAME=\"$id\" ID=\"$id\" $onChange SIZE=\"$size\" class=\"uniform full_width\" style=\"opacity: 0;\">\n";
  // So we don't use them for now:
  $result = "  <SELECT $multiple NAME=\"$id\" ID=\"$id\" $onChange SIZE=\"$size\" class=\"selector\">\n";

  $selector_pairs = array();

  for($i = 0; $i < count($labels); $i++) {
    $label = $labels[$i];
    $value = $values[$i];

    // HTML safe
    $label = htmlspecialchars($label);
    $value = htmlspecialchars($value);
    $selector_pairs[$value] = $label;
  }

  // Do we need to sort?
  if ($this->getSorted()) {
    // Sort the array, but maintain key => value association:
    natsort($selector_pairs);
  }

  // Render output of the option values:
  $i = "0";
  foreach ($selector_pairs as $key => $value) {
    $selected = (in_array($key, $selectedIndexes)) ? "SELECTED" : "";
    $result .= "<OPTION VALUE=\"$key\" $selected>$value</OPTION>\n";
    $i++;
  }

  // do not put any new lines here because fields that use this code may
  // want no line breaks to be shown on screen
  $result .= "</SELECT>";

  // If a Label and Description are set, we use them. If not, then we
  // calculate these based on the ID of the FormObject:
  if ((isset($this->Label)) && (strlen($this->Label) > "0")) {
    $label = $this->Label;
  }
  else {
    $label = $i18n->getHtml($id);
  }
  if ((isset($this->Description)) && (strlen($this->Description) > "0")) {
    $helptext = $this->Description;
  }
  else {
      $h = $id . '_help';
      $helptext = $i18n->getWrapped("[[$h]]");
  }

  $optional_text = "(" . $i18n->get("[[palette.optional]]") . ")";

  if ($access == "hidden") {
    $input_type = "hidden";
    // Need to reset any existing 'required' stuff:
    $optional_text = '';
  }
  elseif ($access == "rw") {
    $input_type = "text"; 
  }
  else {
    // Covers 'r' and anything else:
    $input_type = "hidden";
    // Need to reset any existing 'required' stuff:
    $optional_text = '';
  }

  $out = '';
  $out .= '
                                                ' . $result;
  return $out;

    }
  }


  // description: make a select field but without the surrounding HTML-baggage, but make it a multiselect:
  // This is used for the new getSetSelector() UIFC Class.
  // param: id: the identifier of the field
  // param: access: "" for hidden, "r" for read-only, "w" for write-only
  //     and "rw" for read and write
  // param: size: the SIZE attribute of the HTML SELECT tag
  // param: width: the minimum width
  //     Select field width is static in Netscape, dynamic in IE 
  // param: isMultiple: true if multiple items can be selected, false otherwise
  // param: formId: the ID of the form this field lives in
  // param: onChange: the onChange attribute of the field. Optional.
  // param: labels: an array of labels in string. Optional.
  //     Must have same length with values
  // param: values: an array of values in string. Optional.
  //     Must have same length with labels
  // param: selectedIndexes: an array of indexes of labels for the selected
  // returns: HTML that represents the field
  function makeMultiSelectField($id, $access, $i18n, $size, $width, $isMultiple, $formId, $onChange = "", $labels = array(), $values = array(), $selectedIndexes = array()) {
    switch($access) {
      case "":
      if(!$isMultiple) {
        return $this->makeHiddenField($id, $values[$selectedIndexes[0]]);
      }

      $result = "";
      for($i = 0; $i < count($selectedIndexes); $i++) {
        $result .= $this->makeHiddenField($id, $values[$selectedIndexes[$i]]);
      }
      return $result;

      case "r":
        if(!$isMultiple) {
          // HTML safe
          return htmlspecialchars($labels[$selectedIndexes[0]]).$this->makeHiddenField($id, $values[$selectedIndexes[0]]);
        }
        $result = "";
        $result = $this->makeHiddenField($id, '&' . implode('&', array_values($selectedIndexes)) . '&', FALSE);

        $result .= '
                                    <fieldset class="no_lines nolabel">
                                            <div>';
        foreach ($selectedIndexes as $SIDkey => $SIDvalue) {
          $IOIkey = array_search($SIDvalue, $values);
          $result .= '          
                                              <p>' . htmlspecialchars($labels[$IOIkey]) . '</p>' . "\n";
        }
        $result .= '
                                            </div>
                                    </fieldset>' . "\n";
        return $result;

      // impossible case
      case "w":

      case "rw":
      $multiple = ($isMultiple) ? "multiple=\"multiple\"" : "";

  // log activity if necessary
  $system = new System();
  // log value if only one option can be selected
  $value = !$isMultiple ? ", this.options[this.selectedIndex].value" : "";
  $logChange = ($system->getConfig("logPath") != "") ? "top.code.uiLog_log('change', 'FormField', '$id' $value);" : "";

  $onChange = "";

  // Uniform dropdown selectors look like the crap:
  // So we don't use them for now:
  $brackets = "[]";
  $result = "  <SELECT $multiple NAME=\"$id$brackets\" ID=\"$id\" $onChange SIZE=\"$size\" class=\"multiselect multisorter indent\" style=\"height:230px;\">\n";

  $selector_pairs = array();

  for($i = 0; $i < count($labels); $i++) {
    $label = $labels[$i];
    if (isset($values[$i])) {
      $value = $values[$i];
    }

    // HTML safe
    $label = htmlspecialchars($label);
    $value = htmlspecialchars($value);
    $selector_pairs[$value] = $label;
  }

  // Do we need to sort?
  if ($this->getSorted()) {
    // Sort the array, but maintain key => value association:
    natsort($selector_pairs);
  }

  // Render output of the option values:
  $i = "0";
  foreach ($selector_pairs as $key => $value) {
    if (!is_array($selectedIndexes)) {
      $selectedIndexes = scalar_to_array($selectedIndexes);
    }

    if (in_array($key, $selectedIndexes)) {
      $result .= "<OPTION VALUE=\"$key\" selected=\"selected\">$value</OPTION>\n";
    }
    else {
      $result .= "<OPTION VALUE=\"$key\">$value</OPTION>\n";
    }
    $i++;
  }

  // do not put any new lines here because fields that use this code may
  // want no line breaks to be shown on screen
  $result .= "</SELECT>";

  // If a Label and Description are set, we use them. If not, then we
  // calculate these based on the ID of the FormObject:
  if ((isset($this->Label)) && (strlen($this->Label) > "0")) {
    $label = $this->Label;
  }
  else {
    $label = $i18n->getHtml($id);
  }
  if ((isset($this->Description)) && (strlen($this->Description) > "0")) {
    $helptext = $this->Description;
  }
  else {
      $h = $id . '_help';
      $helptext = $i18n->getWrapped("[[$h]]");
  }

  $optional_text = "(" . $i18n->get("[[palette.optional]]") . ")";

  if ($access == "hidden") {
    $input_type = "hidden";
    // Need to reset any existing 'required' stuff:
    $optional_text = '';
  }
  elseif ($access == "rw") {
    $input_type = "text"; 
  }
  else {
    // Covers 'r' and anything else:
    $input_type = "hidden";
    // Need to reset any existing 'required' stuff:
    $optional_text = '';
  }

  $out = '';
  $out .= '
                                                ' . $result;
  return $out;

    }
  }

  // description: make a HTML field
  // param: id: the identifier of the field
  // param: value: the value of the HTML input field
  // param: access: "" for hidden, "r" for read-only, "w" for write-only
  //     and "rw" for read and write
  // param: size: the length of the field
  // param: maxLength: maximum number of characters
  //     that can be entered into the field
  // param: onChange: the onChange attribute of the field
  // returns: HTML that represents the field
//  function makeHtmlField($id, $value, $access, $size, $maxLength, $onChange) {
//    $shortval = $value;
//    if (($maxLength > 0) && (strlen($value) > $maxLength)) {
//     //$shortval = substr($value, 0, $maxLength) . ' ...';
//    }
//
//    switch($access) {
//      case "":
//  return $this->makeHiddenField($id, $value);
//
//      case "r":
//  return $shortval;
//
//      case "R":
//  return $shortval;
//
//      case "w":
//  $value = "";
//  break;
//
//      case "rw":
//  $value = "VALUE=\"$value\"";
//  break;
//    }
//
//    // log activity if necessary
//    $system = new System();
//    $logChange = ($system->getConfig("logPath") != "") ? "top.code.uiLog_log('change', 'FormField', '$id', this.value);" : "";
//
//    // find size
//    $size = ($size > 0) ? "SIZE=\"$size\"" : "";
//
//    // find max size
//    $maxLength = ($maxLength > 0) ? "MAXLENGTH=\"$maxLength\"" : "";
//
//    // find onChange handler
//    if($onChange != "" || $logChange != "")
//      $onChange = "onChange=\"$logChange $onChange\"";
//
//    return "<INPUT TYPE=\"TEXT\" NAME=\"$id\" $value $size $maxLength $onChange>\n";
//  }


  // description: make a text area field
  // param: id: the identifier of the field
  // param: value: the value of the HTML input field
  // param: access: "" for hidden, "r" for read-only, "w" for write-only
  //     and "rw" for read and write
  // param: rows: the number of rows
  // param: columns: the number of columns
  // param: onChange: the onChange attribute of the field
  // param: wrap: "on", "hard" or "off". "on" means word wrapping occurs on
  //     boundaries. "hard" means wrapping points are converted to CR-LF in
  //     the value submitted. "off" means no wrapping. Optional and "off" by
  //     default.
  // returns: HTML that represents the field
  function makeTextAreaField($id, $value, $access, $i18n, $type="", $isOptional, $rows, $columns, $onChange, $wrap = "off") {
    $this->i18n = $i18n;
    $this->type = $type;
    $logChange = "";

    // find onChange handler
    if($onChange != "" || $logChange != "") {
      //$onChange = "onChange=\"$logChange $onChange\"";
      $onChange = "";
    }

    // If a Label and Description are set, we use them. If not, then we
    // calculate these based on the ID of the FormObject:
    if ((isset($this->Label)) && (strlen($this->Label) > "0")) {
      $label = $this->Label;
    }
    else {
      $label = $i18n->getHtml($id);
    }
    if ((isset($this->Description)) && (strlen($this->Description) > "0")) {
      $helptext = $this->Description;
    }
    else {
        $h = $id . '_help';
        $helptext = $i18n->getWrapped("[[$h]]");
    }

    $textarea_name = $id;

    if ($isOptional == TRUE) {
      $optional_text = "(" . $i18n->get("[[palette.optional]]") . ")";
      $optional_class = ' ';
      $optional_line = '';
    }
    else {
      $optional_text = '';
      $optional_class = ' required ';
      $optional_line = '<div class="required_tag tooltip hover left" title="' . get_i18n_error_for_inputvalidation($this->type, $i18n) . '"></div>';
    }

    switch($access) {
      case "":
        return $this->makeHiddenField($id, $value);

      case "r":
        // HTML safe
        $value = htmlspecialchars($value);

        // preserve line breaks
        $value = preg_replace("/\r?\n/", "<BR>", $value);
        $textarea = "<p>" . $value . "</p>";
        $optional_text = '';
        $optional_line = '';

      case "w":
        $value = "";
        break;

      case "rw":
        $textarea = '<textarea name="'. $textarea_name . '" title="' . $i18n->get("[[palette.autogrow_expanding]]") . '" class="tooltip autogrow ' . $this->type . $optional_class . '" placeholder="' . $i18n->get("[[palette.autogrow_prefill]]") . '">' . $value . '</textarea>' . "\n";
        break;
    }

    $out = '
                                    <fieldset class="label_side uniform">
                                      <label for="' . $id . '" title="' . $helptext . '" class="tooltip hover uniform">' . $label . '<span>' . $optional_text . '</span></label>
                                      <div class="clearfix">
                                        ' . $textarea 
                                        . $optional_line . '
                                      </div>
                                    </fieldset>';

      return $out;

  }

  // description: make a text list field
  // param: id: the identifier of the field
  // param: values: an array of values in string
  // param: access: "" for hidden, "r" for read-only, "w" for write-only and "rw" for read and write
  // param: $i18n: Parent objects i18n object
  // param: $type: type to validate against. As per Schema and /gui/validation
  // param: formId: the identifier of the form this field lives in
  // param: rows: the number of rows
  // param: columns: the number of columns
  // returns: HTML that represents the field
  function makeTextListField($id, $values, $access, $i18n, $type="", $isOptional, $formId, $rows, $columns) {
    $valueString = arrayToString($values);
    $this->i18n  = $i18n;
    $this->type  = $type;
    $this->isOptional = $isOptional;
    $result = "";

    switch($access) {
      case "":
        return $this->makeHiddenField($id, $valueString);

      case "r":
        for($i = 0; $i < count($values); $i++) {
          if($i > 0) {
            $result .= "<BR>";
          }
          // HTML safe
          $result .= htmlspecialchars($values[$i]);
        }
        $result .= $this->makeHiddenField($id, $valueString);

//
        $valueText = implode("\n", $values);
        $result = $this->makeTextAreaField($id, $valueText, "r", $this->i18n, $this->type, $this->isOptional, $rows, $columns, "");
//

        return $result;

      case "w":
        // clear off values
        $values = array();
        break;

      case "rw":
        break;
    }

    $valueText = implode("\n", $values);

    // make text area field
    $text = $this->makeTextAreaField($id, $valueText, $access, $this->i18n, $this->type, $this->isOptional, $rows, $columns, "");

    // make hidden field
    $hidden = $this->makeHiddenField("textarea-".$id, $valueString);

    return "
$hidden
$text
";
  }

  // This is for the laaaaaazy way to get native HTML code into your pages.
  // It simply returns whatever "values" (your HTML code) you stuffed into it.
  // This is done via uifc/RawHTML.php and by adding a FormField via something
  // like $factory->getRawHTML("applet", $applet)
  function makeRawHTMLField($id, $values) {
    return $values;
  }

  function makeHTMLField($id, $value, $i18n) {

    // If a Label and Description are set, we use them. If not, then we
    // calculate these based on the ID of the FormObject:
    if ((isset($this->Label)) && (strlen($this->Label) > "0")) {
      $label = $this->Label;
    }
    else {
      $label = $i18n->getHtml($id);
    }
    if ((isset($this->Description)) && (strlen($this->Description) > "0")) {
      $helptext = $this->Description;
    }
    else {
        $h = $id . '_help';
        $helptext = $i18n->getWrapped("[[$h]]");
    }
    $out = '';

    $poss_lables = array('label_side', 'nolabel', 'labeel_top', 'top');
    if (!in_array($this->getLabelType(), $poss_lables)) {
      $this->setLabelType($this->getLabelType() . ' label_side');
    }
    $out .= '
                                  <fieldset class="' . $this->getLabelType() . '">';
    if ($this->getLabelType() != "nolabel") {
      $out .= '
                                          <label for="' . $id . '" title="' . $helptext . '" class="tooltip right uniform">' . $label . '</label>';
    }
    else {
      $out .= "";
    }
    $out .= '
                                          <div>';
    $out .= $value . "\n";

    $out .= '
                                          </div>
                                  </fieldset>';
    return $out;
  }

}

/*
Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. 
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.

*/
?>