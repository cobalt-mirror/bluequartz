<?php
// Author: Kevin K.M. Chiu
// $Id: CompositeFormField.php

// description:
// This class is a container of FormField objects.
//
// applicability:
// This class is useful for putting in multiple form fields into places that
// accept one form field object.
//
// usage:
// Simple use addFormField() to add form fields into this object, then use
// toHtml() to get a HTML representation of it.
//
// Note: 
//
// setDelimiter() and setAlignment() are no longer supported.

global $isCompositeFormFieldDefined;
if($isCompositeFormFieldDefined)
  return;
$isCompositeFormFieldDefined = true;

include_once("uifc/FormField.php");

class CompositeFormField extends FormField {
  //
  // private variables
  //

  var $delimiter;
  var $formFields;
  var $alignment;
  var $columnWidths;

  //
  // public methods
  //

  // description: constructor
  function CompositeFormField() {
    $this->formFields = array();

    // set default
    $this->delimiter = " ";
  }

  function getCollatableValue() {
    return -count($this->formFields);
  }

  // description: get the delimiter to separate form fields
  // returns: a delimiter in string
  function getDelimiter() {
    return $this->delimiter;
  }

  // description: set the delimiter to separate form fields
  // param: delimiter: a delimiter in string
  function setDelimiter($delimiter) {
    $this->delimiter = $delimiter;
  }

  // description: get form fields added to this object
  // returns: an array of FormField object
  // see: addFormField()
  function getFormFields() {
    return $this->formFields;
  }

  // description: set vertical alignment of the horizontal row of form fields
  // param: "top", "middle", "bottom"
  function setAlignment($alignment) {
    $this->alignment = $alignment;
  }

  // description: add a form field to this object
  // param: formField: a FormField object
  // see: getFormFields()
  function addFormField($formField) {
    $this->formFields[] = $formField;
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

  function toHtml($style = "") {

    $ColumnWidths = $this->getColumnWidths();

    // Sadly we have to use "box grid_16" here. Without works fine in FireFox and Safari. 
    // But IE then craps out and misformats the next field that's below our Composite.
    $result = '<!-- Composite: Start -->' . "\n" . '<div class="box2 grid_16">' . "\n" . '   <div class="columns">' . "\n";

    $delimiter = $this->getDelimiter();
    $formFields = $this->getFormFields();

    $number_of_formfields = count($formFields);

    if (!is_array($this->getColumnWidths())) {
      // Check how many FormFields are in the Column.
      // Set the $column_width accordingly and evenly:
      if ($number_of_formfields == "0") {
        $colum_width = "col_100";
      }
      elseif ($number_of_formfields == "1") {
        $colum_width = "col_100";
      }
      elseif ($number_of_formfields == "2") {
        $colum_width = "col_50";
      }
      elseif ($number_of_formfields == "3") {
        $colum_width = "col_33";
      }
      elseif ($number_of_formfields == "4") {
        $colum_width = "col_25";
      }
      else {
        $colum_width = "col_25";
      }
      // Populate array $ColumnWidths:
      for($i = 0; $i < count($formFields); $i++) {
        $ColumnWidths[$i] = $colum_width;
      }

    }
    else {
      // Set column widths only if we have as many ColumnWidths as we have FormFields:
      if (count($formFields) == count($ColumnWidths)) {
        $ColumnWidths = $this->getColumnWidths();
      }
      else {
        // Not? Then do a safe fallback to a sane value:
        for($i = 0; $i < count($formFields); $i++) {
          $ColumnWidths[$i] = 'col_25';
        }        
      }
    }

    for($i = 0; $i < count($formFields); $i++) {
      $result .= "\n" . '        <div class="' . $ColumnWidths[$i] . '">' . $formFields[$i]->toHtml($style) . "\n" . '        </div>';
    }

    $result .= "\n" . '   </div>' . "\n" . '</div>';
    $result .= "\n" . '<!-- Composite: End -->' . "\n";

    return $result;
  }
}

/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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
