<?php
// Author: Kevin K.M. Chiu
// $Id: MultiButton.php

// description:
// This class represents a button with multiple actions. Users can perform one
// of those actions by selecting it.
//
// applicability:
// Anywhere a related set of actions are provided for the users to select and
// the selected one is being performed.
//
// usage:
// Instantiate a MultiButton by specifying a text. This text is like the label
// of the button. Use addAction() to add actions to the button. Finally, use
// toHtml() to get a HTML representation of the button to present.

// NOTE BY MSTAUBER: This is not yet ported! Have to see where it is actually used first.

global $isMultiButtonDefined;
if($isMultiButtonDefined)
  return;
$isMultiButtonDefined = true;

include_once("uifc/HtmlComponent.php");

class MultiButton extends HtmlComponent {
  //
  // private variables
  //

  var $actionToText;
  var $selectedIndex;
  var $text;
  var $id;
  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this object lives in
  // param: text: a label text in string. Optional
  function MultiButton($page, $text = "") {
    // superclass constructor
    $this->HtmlComponent($page);

    $this->setText($text);
    $this->id = "";
  }

  function getId()
  {
    return $this->id;
  }

  function setId($id = "foo")
  {
    $this->id = $id;
  }

  function getAccess()
  {
    return 'rw';
  }
 
  function isOptional()
  {
    return false;
  }

  // description: get all the text of the button
  // returns: an array of text strings
  // see: addAction(), getActions()
  function getActionText($action) {
    return $this->actionToText[$action];
  }

  // description: get the actions of the button
  // returns: an array of action strings
  // see: addAction(), getActionText()
  function getActions() {
    return array_keys($this->actionToText);
  }

  // description: add an action to the button
  // param: action: the string used within HREF attribute of the A tag
  // param: text: a label text in string
  function addAction($action, $text) {
    $this->actionToText[$action] = $text;
  }

  // description: get the index of the selected action
  // returns: an integer
  // see: setSelectedIndex()
  function getSelectedIndex() {
    return $this->selectedIndex;
  }

  // description: set the index of the selected action
  // param: selectedIndex: an integer
  // see: getSelectedIndex()
  function setSelectedIndex($selectedIndex) {
    $this->selectedIndex = $selectedIndex;
  }

  // description: get the default text of the button
  // returns: a string
  // see: setText()
  function getText() {
    return $this->text;
  }

  // description: set the default text of the button
  // param: text: a string
  // see: getText()
  function setText($text) {
    $this->text = $text;
  }

  function toHtml($style = "") {
    $text = $this->getText();

    $selectedIndex = $this->getSelectedIndex();
    // add text label to options list if necessary
    if($text != "") {
      $selected = (!isset($selectedIndex) ? " SELECTED" : "");
      // HTML safe
      $text = htmlspecialchars($text);
      $options = "  <OPTION $selected>$text\n";
      //$this->setSelectedIndex(-1); // select nothing else
    }

    // add all the actions
    $actions = $this->getActions();
    for($i = 0; $i < count($actions); $i++) {
      $action = $actions[$i];
      $actionText = $this->getActionText($action);
      // HTML safe
      $actionText = htmlspecialchars($actionText);

      $selected = ($i == $selectedIndex && isset($selectedIndex)) ? "SELECTED" : "";

      $options .= "  <OPTION $selected VALUE=\"$action\">$actionText\n";
    }

    return "<SELECT onChange=\"if (this.options[this.selectedIndex].value) location = this.options[this.selectedIndex].value\">\n$options</SELECT>";
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