<?php
// Author: Michael Stauber
// $Id: Bar.php

global $isBarDefined;
if($isBarDefined)
  return;
$isBarDefined = true;

include_once("uifc/FormField.php");

class Bar extends FormField {
  //
  // private variables
  //

  var $label;

  //
  // public methods
  //

  function Bar(&$page, $id, $value, $i18n) {
    $this->page = $page;
    $this->id = $id;
    $this->value = $value;
    $this->i18n = $i18n;
  }

  function getLabel() {
    return $this->label;
  }

  // description: set label to replace the percentage shown by default
  // param: label: a label in string
  function setLabel($label) {
    $this->label = $label;
  }

  function setBarText($text) {
    $this->bartext = $text;
  }

  // description: set bar to type vertical
  // Deprecated for now.
  function setVertical() {
    $this->orientation = 'v';
  }

  // description: defines where the labels are placed on formfields:
  function setLabelType($labeltype) {
        $this->LabelType = $labeltype;
  }

  // Returns where the labels are placed on formfields:
  function getLabelType() {
    if (!isset($this->LabelType)) {
         $this->LabelType = "label_side top";
    }
    return $this->LabelType;
  }

  // The Helptext of the Bar can either be below the bar (default) or on the right side of it:
  function setHelpTextPosition($htpos) {
        $this->HelpTextPosition = $htpos;
  }

  // Returns HelpTextPosition. "bottom" is default. Alternative is "right":
  function getHelpTextPosition() {
    if (!isset($this->HelpTextPosition)) {
         $this->HelpTextPosition = "bottom";
    }
    return $this->HelpTextPosition;
  }

  function toHtml($style = "") {

    // Handle Label:
    $label_array = $this->page->getLabel($this->id);
    if (!is_array($label_array)) {
        $text = $this->i18n->getHtml($this->id);
        $h = $this->id . '_help';
        $helptext = $this->i18n->getWrapped("[[$h]]");
    }
    else {
      $key = array_keys($label_array);
      $val = array_values($label_array);
      if (strlen($key[0]) > "2") {
        // We have a Label. Use it:
        $text = $this->i18n->getHtml($key[0]);
        $helptext = $val[0];
      }
      else {
        // Use the ID instead:
        $text = $this->i18n->getHtml($this->id);
        $h = $this->id . '_help';
        $helptext = $this->i18n->getWrapped("[[$h]]");
      }
    }
    
    $percentage = $this->value;

    if (isset($this->bartext)) {
      $percentage_helptext = $this->bartext;
    }
    else {
      $percentage_helptext = $percentage."%";
    }

    if ($this->getHelpTextPosition() == "right") {
      $ht_right = $percentage_helptext;
      $ht_bottom = '';
    }
    elseif ($this->getHelpTextPosition() == "bottom") {
      $ht_right = '';
      $ht_bottom = '<p align="center">' . $percentage_helptext . '</p>';
    }
    else {
      $ht_right = '';
      $ht_bottom = '';
    }

    $out = '';
    if ($this->getLabelType() != "nolabel") {
      $out .= '
                                                          <fieldset class="label_side">
                                                                  <label title="' . $helptext . '" class="tooltip hover">' . $text . '</label>
                                                                  <div>' . "\n";
    }

    if ($this->getHelpTextPosition() == "right") {
      $out .= '
                                                                        <table width="100%" cellspacing="0" cellpadding="0" border="0">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td valign="middle"><div title="' . $percentage_helptext . '" id="progressbar' . $this->id .'" class="progressbar tooltip hover"></div></td>
                                                                                    <td width="30" valign="middle">' . $ht_right . '</td>
                                                                                </tr>
                                                                            </tbody>
                                                                        </table>' . "\n";
    }
    else {
      $out .= '
                                                                        <div title="' . $percentage_helptext . '" id="progressbar' . $this->id .'" class="progressbar tooltip hover"></div>' . $ht_right . '
                                                                          ' . $ht_bottom . "\n";
    }

    $out .= '
                                                                            <script>
                                                                              $( "#progressbar' . $this->id .'" ).progressbar ({
                                                                                value: ' . $percentage . '
                                                                              });
                                                                            </script>';
    if ($this->getLabelType() != "nolabel") {
      $out .= '
                                                                  </div>
                                                          </fieldset>';
    }
    return $out;

  }
}

/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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