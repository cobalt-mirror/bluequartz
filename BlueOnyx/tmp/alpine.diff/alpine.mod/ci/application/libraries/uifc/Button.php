<?php
// Author: Kevin K.M. Chiu
// $Id: Button.php

global $isButtonDefined;
if($isButtonDefined)
  return;
$isButtonDefined = true;

include_once("uifc/HtmlComponent.php");

class Button extends HtmlComponent {
  //
  // private variables
  //

  var $action;
  var $isDisabled;
  var $isHeader;
  var $label;
  var $labelDisabled;
  var $targetFrame;
  var $Icon;
  var $this_demo_override;
  var $waiter;

  //
  // public methods
  //

  // description: get the access property
  // returns: a string
  // see: setAccess()
  function getAccess() {
    return $this->access;
  }

  // description: set the access property
  // param: access can be "" for hidden, "r" for read-only,
  //     "w" for write-only and "rw" for both read and write
  // returns: true if succeed, false if failed
  // see: getAccess()
  function setAccess($access) {
    if($access != "" &&
      $access != "r" &&
      $access != "w" &&
      $access != "R" &&
      $access != "rw")
      return false;

    $this->access = $access;

    return true;
  }

  // Get the status of the waiting overlay:
  function getWaiter() {
    if (!isset($this->waiter)) {
      $this->waiter = '';
    }
    return $this->waiter;
  }
  // Set status of waiting overlay
  function setWaiter($waiter) {
    if ($waiter == TRUE) {
      $this->waiter = ' waiter';
    }
    else {
      $this->waiter = '';
    }
  }

  // description: constructor
  // param: page: the Page object this object lives in
  // param: action: the string used within HREF attribute of the A tag
  // param: label: a Label object for the normal state
  // param: labelDisabled: a Label object for the disabled state. Optional
  //     If not supplied, it is the same as the label parameter
  function Button(&$page, $action, $type, &$label, $labelDisabled = "", $this_demo_override = FALSE) {
    // superclass constructor
    $this->HtmlComponent($page);

    $this->setAction($action);
    $this->setLabel($label, $labelDisabled);

    $this->type = $type;

    // set defaults
    $this->setDisabled(false);
    if (is_file("/etc/DEMO")) {
      $this->setDemo(TRUE);
    }
    else {
      $this->setDemo(FALSE);
    }

    // Allow for the setDemo to be overridden:
    if ($this_demo_override == "DEMO-OVERRIDE") {
      $this->setDemo(FALSE);
    }

    // Set our Label and Description in cleartext, too:
    if (isset($label->page->Label)) {
      $this->ButtonLabel = $label->page->Label['label'];
      $this->ButtonDescription = $label->page->Label['description'];
    }

  }

  // description: get the action to perform when the button is pressed
  // return: an URL
  // see: setAction()
  function getAction() {
    return $this->action;
  }

  // description: set the action to perform when the button is pressed
  // param: action: an URL
  // see: getAction()
  function setAction($action) {
    $this->action = $action;
  }

  // description: set the target frame of the action
  // param: target: a string
  // see: getTarget()
  function setTarget($target) {
    $this->targetFrame = $target;
  }

  // description: get the target frame of the action
  // returns: a string
  // see: setTarget()
  function getTarget() {
    if (!isset($this->targetFrame)) {
      $this->targetFrame = "_self";
    }
    return $this->targetFrame;
  }

  function setDescription($description)
  {
    $this->label->setDescription($description);
  }
  
  function setDisabledDescription($description)
  {
    $this->labelDisabled->setDescription($description);
  }

  function setImageOnly($image_only)
  {
    $this->labelDisabled->ImageOnly = $image_only;
  }

  function &getDefaultStyle($stylist) {
    $style = "none";
    return $style;
  }

  // description: see if the button is disabled
  // returns: true if the button is disabled, false otherwise
  // see: setDisabled()
  function isDisabled() {
    return $this->isDisabled;
  }

  // description: set the disabled flag
  // param: disabled: true if the button is disabled, false otherwise
  // see: isDisabled()
  function setDisabled($isDisabled) {
    $this->isDisabled = $isDisabled;
  }

  function setIcon($Icon) {
    $this->Icon = " " . $Icon;
  }

  function getIcon() {
    if (isset($this->Icon)) {
      return $this->Icon;
    }
    else {
      return NULL;
    }
  }

  // Demo mode:
  // description: see if the button is disabled
  // returns: true if the button is disabled, false otherwise
  // see: setDisabled()
  function isDemo() {
    return $this->isDemo;
  }

  // description: set the disabled flag
  // param: disabled: true if the button is disabled, false otherwise
  // see: isDisabled()
  function setDemo($isDemo) {
    $this->isDemo = $isDemo;
  }

  // description: see if the button uses the header style
  // returns: true if the button is a header button, false otherwise
  // see: setHeader()
  function isHeader() {
    return $this->isHeader;
  }

  // description: set the header style for a button
  // param: disabled: true if the button is a header button, false otherwise
  // see: isHeader()
  function setHeader($isHeader) {
    $this->isHeader = $isHeader;
  }

  // description: get the label for normal state of the button
  // returns: a Label object
  // see: setLabel()
  function &getLabel() {
    return $this->label;
  }

  // description: get the label for disabled state of the button
  // returns: a Label object
  // see: setLabel()
  function &getLabelDisabled() {
    return $this->labelDisabled;
  }

  // description: set the label for the button
  // param: label: a Label object for the normal state
  // param: labelDisabled: a Label object for the disabled state. Optional
  //     If not supplied, it is the same as the label parameter
  function setLabel(&$label, $labelDisabled = "") {
    $this->label =& $label;
    $this->labelDisabled = ($labelDisabled != "") ? $labelDisabled : $label;
  }

  function setButtonSite($size) {
    $this->ButtonSize = $size;
  }

  function toHtml($style = "") {
    $page =& $this->getPage();

    $isDisabled = $this->isDisabled();
    $isHeader = $this->isHeader();

    // find the right style target
    $useHeaderButton = "useHeaderButton";
    if ($isHeader && $useHeaderButton) {
        $target = $isDisabled ? "headerDisabled" : "header";
    }
    else {
      $target = $isDisabled ? "disabled" : "normal";
    }

    // Based on the button type we set various things like which icon we use and more:
    $i18n =& $page->getI18n();

    if ($this->type == "save") {
      if ($this->getIcon() == NULL) {
        $icon = " ui-icon-check";
      }
      else {
        $icon = $this->getIcon();
      }
      $class = "no_margin_bottom div_icon has_text waiter";

      $URL = $this->getAction();
      if ($URL != "") {
        $data_link = ' data-link="' . $URL . '"';
        $linkable = ' link_button';
      }
      else {
        $data_link = '';
        $linkable = '';
      }      
    }
    elseif ($this->type == "add") {
      if ($this->getIcon() == NULL) {
        $icon = " ui-icon-plus";
      }
      else {
        $icon = $this->getIcon();
      }
      $class = "no_margin_bottom div_icon has_text";
    }
    elseif ($this->type == "back") {
      if ($this->getIcon() == NULL) {
        $icon = " ui-icon-arrowthick-1-w";
      }
      else {
        $icon = $this->getIcon();
      }
      $class = "no_margin_bottom div_icon has_text";
    }
    elseif ($this->type == "modify") {
      if ($this->getIcon() == NULL) {
        $icon = " ui-icon-pencil";
      }
      else {
        $icon = $this->getIcon();
      }
      $class = "no_margin_bottom div_icon has_text";

      $URL = $this->getAction();
      if ($URL != "") {
        $data_link = ' data-link="' . $URL . '"';
        $linkable = ' link_button';
      }
      else {
        $data_link = '';
        $linkable = '';
      }

      $labelvars = get_object_vars($this->label);
      if ($labelvars['label']) {
        $htmlLabel = '<span>' . $i18n->get($labelvars['label']) . '</span>';
      }
      else {
        $htmlLabel = '';
      }

      if ($this->ButtonLabel) {
        $htmlLabel = '<span>' . $this->ButtonLabel . '</span>';
      }
      else {
        $htmlLabel = '';
      }

      if ($description = $this->ButtonDescription) {
        $description = $this->ButtonDescription;
      }
      else {
        $description = $i18n->getWrapped($labelvars['description']);
      }

      if (isset($this->labelDisabled->ImageOnly)) {
        if ($this->labelDisabled->ImageOnly == TRUE) {
          $htmlLabel = '';
        }
      }

      $linktarget = $this->getTarget();
      $targetLine = ' target="' . $linktarget . '"' . ' formtarget="' . $linktarget . '"';

      $out = '
                        <button title="' . $description . '" class="close_dialog tooltip right' . $this->getWaiter() . $linkable . '"' . $data_link . $targetLine . '>
                          <div class="ui-icon' . $icon. '"></div>
                            ' . $htmlLabel . '
                        </button>'; 
      return $out;

    }
    elseif ($this->type == "uninstall") {
      if ($this->getIcon() == NULL) {
        $icon = " ui-icon-circle-close";
      }
      else {
        $icon = $this->getIcon();
      }
      $class = "no_margin_bottom div_icon has_text";

      $URL = $this->getAction();
      if ($URL != "") {
        $data_link = ' data-link="' . $URL . '"';
        $linkable = ' link_button';
      }
      else {
        $data_link = '';
        $linkable = '';
      }

      if (isset($page->Label['label'])) {
        $htmlLabel = '<span>' . $page->Label['label'] . '</span>';
      }
      else {
        $htmlLabel = '';
      }

      if (isset($page->Label['description'])) {
        $LabelDesc = $page->Label['description'];
      }
      else {
        $LabelDesc = '';
      }

      if (isset($this->labelDisabled->ImageOnly)) {
        if ($this->labelDisabled->ImageOnly == TRUE) {
          $htmlLabel = '';
        }
      }

      $linktarget = $this->getTarget();
      $targetLine = ' target="' . $linktarget . '"' . ' formtarget="' . $linktarget . '"';

      $out = '
                        <button title="' . $LabelDesc . '" class="close_dialog tooltip right' . $this->getWaiter() . $linkable . '"' . $data_link . $targetLine . '>
                          <div class="ui-icon' . $icon. '"></div>
                            ' . $htmlLabel . '
                        </button>'; 
      return $out;

    }    
    elseif ($this->type == "cancel") {

      $URL = $this->getAction();
      if ($URL != "") {
        $data_link = ' data-link="' . $URL . '"';
        $linkable = ' link_button';
      }
      else {
        $data_link = '';
        $linkable = '';
      }

      $linktarget = $this->getTarget();
      $targetLine = ' target="' . $linktarget . '"' . ' formtarget="' . $linktarget . '"';

      $out = '
                        <button title="' . $i18n->getWrapped("[[palette.cancel_help]]") . '" class="light send_right close_dialog tooltip right' . $this->getWaiter() . $linkable . '"' . $data_link .  $targetLine . '>
                          <div class="ui-icon ui-icon-closethick"></div>
                            <span>' . $i18n->getHtml("[[palette.cancel]]") . '</span>
                        </button>'; 
      return $out;
    }
    elseif ($this->type == "detail") {
      $URL = $this->getAction();

      if (($URL != "") && (!$isDisabled)) {
        $data_link = ' data-link="' . $URL . '"';
        $linkable = ' link_button';
      }
      else {
        $data_link = '';
        $linkable = '';
      }

      $action = $this->getAction();

      $i18n =& $page->getI18n();
      $langs = $i18n->getLocales();

      if (isset($page->Label['label'])) {
        $htmlLabel = '<span>' . $page->Label['label'] . '</span>';
      }
      else {
        $htmlLabel = '';
      }

      if (isset($page->Label['description'])) {
        $LabelDesc = $page->Label['description'];
      }
      else {
        $LabelDesc = '';
      }

      // Set disabled lable:
      if ($isDisabled) {
        $htmlLabel = '<span>' . $page->Label['label'] . ' (' . $i18n->get("[[palette.disabled_short]]") . ')</span>';
        if (!isset($this->labelDisabled->description)) {
          $LabelDesc = $i18n->getWrapped("[[palette.disabled_short]]") . '<br>' . $i18n->getWrapped("[[palette.notprivileged]]");
        }
      }

      if (isset($this->labelDisabled->ImageOnly)) {
        if ($this->labelDisabled->ImageOnly == TRUE) {
          $htmlLabel = '';
        }
      }

      if (isset($this->ButtonSize)) {
        $button_size = ' ' . $this->ButtonSize;
      }
      else {
        $button_size = '';
      }

      $labelvars = get_object_vars($this->label);

      $linktarget = $this->getTarget();
      $targetLine = ' target="' . $linktarget . '"' . ' formtarget="' . $linktarget . '"';

      $out = '
                        <button title="' . $LabelDesc . '" class="close_dialog tooltip right img_icon' . $this->getWaiter() . $button_size . $linkable . '"' . $data_link . $targetLine . '>
                          <img src="/.adm/images/icons/small/white/' . $labelvars['image'] . '.png"></img>
                            ' . $htmlLabel . '
                        </button>'; 
      return $out;      
    }
    elseif ($this->type == "urlbutton") {
      $URL = $this->getAction();

      $i18n =& $page->getI18n();
      $langs = $i18n->getLocales();

      if (isset($this->ButtonSize)) {
        $button_size = ' ' . $this->ButtonSize;
      }
      else {
        $button_size = '';
      }

      $out = '';
      if (!$isDisabled) {
        $out .= '           <a href="' . $URL . '" onclick="window.open(this.href,' . "'child','scrollbars,width=800,height=600'" . '); return false">';
      }
      $out .= '             <button class="icon_only img_icon tooltip hover' . $this->getWaiter() . $button_size . '" title="' . $i18n->getWrapped("[[palette.icon_window]]") .'"><img src="/.adm/images/icons/small/white/bended_arrow_right.png"></button>';
      if (!$isDisabled) {
        $out .= '           </a>';
      }

      return $out;      
    }
    elseif ($this->type == "linkbutton") {
      $URL = $this->getAction();

      $isDisabled = $this->isDisabled();
      if ($isDisabled) {
        $linkDis = '';
      }
      else {
        $linkDis = 'link_button';
      }

      $out = '                  <button title="' . $i18n->getWrapped("[[palette.detail_help]]") .'" class="icon_only img_icon tooltip hover ' . $this->getWaiter() . $linkDis . '" data-link="' . $URL . '" target="_self">
                          <img src="/.adm/images/icons/small/white/bended_arrow_right.png">
                        </button>' . "\n";
      return $out;      
    }    
    elseif ($this->type == "fancybutton") {
      $URL = $this->getAction();

      $i18n =& $page->getI18n();
      $langs = $i18n->getLocales();

      // Extraheaders for FancyBox:
      if (!$isDisabled) {
        $page->setExtraHeaders('
            <script>
              $(document).ready(function() {
                $(".various").fancybox({
                  overlayColor: "#000",
                  fitToView : false,
                  width   : "80%",
                  height    : "80%",
                  autoSize  : false,
                  fixed   : false,
                  closeClick  : false,
                  openEffect  : "none",
                  closeEffect : "none"
                });
              });
            </script>');
      }

      if (isset($this->ButtonSize)) {
        $button_size = ' ' . $this->ButtonSize;
      }
      else {
        $button_size = '';
      }

      if ($this->isDemo() == TRUE) {
        $btn_disabled = ' disabled="disabled"';
        $demoLabel = ' (' . $i18n->get("[[palette.demo_mode_short]]") . ')';
      }
      else {
        $btn_disabled = '';
        $demoLabel = '';
      }

      $out = '';
      if ((!$isDisabled) && ($this->isDemo() == FALSE)) {
        $out .= "\n" . '           <a class="various" target="' . $this->getTarget() . '" href="' . $URL . '" data-fancybox-type="iframe">';
      }
      $out .= "\n" . '             <button class="fancybox icon_only img_icon tooltip hover' . $this->getWaiter() . $button_size . '"' . $btn_disabled . ' title="' . $this->ButtonDescription .'"><img src="/.adm/images/icons/small/white/magnifying_glass.png">';
      if (isset($this->ButtonLabel)) {
        $htmlLabel = $this->ButtonLabel;
        if (isset($this->labelDisabled->ImageOnly)) {
          if ($this->labelDisabled->ImageOnly == TRUE) {
              $out .= "\n";
          }
        }
        else {
          $out .= "\n" . '                                        <span>' . $htmlLabel . $demoLabel . '</span>';
        }
      }
      $out .= "\n" . '             </button>';

      if ((!$isDisabled) && ($this->isDemo() == FALSE)) {
        $out .= "\n" . '           </a>';
      }

      return $out;

    }    
    else {
      $icon = "";
      $class = "no_margin_bottom div_icon has_text";
    }

    if($isDisabled) {
      $label =& $this->getLabelDisabled();
  
      $action = $this->getAction();

      // Javascript definitions were bypassing toHtml..  
      $i18n =& $page->getI18n();

      if (isset($page->Label['label'])) {
        $htmlLabel = '<span>' . $this->ButtonLabel . ' (' . $i18n->get("[[palette.disabled_short]]") . ')</span>';
      }
      else {
        $htmlLabel = '';
      }

      // As this button is disabled, we ignore the label description and just give a canned reply:
      $LabelDesc = $i18n->getWrapped("[[palette.disabled_short]]") . '<br>' . $i18n->getWrapped("[[palette.notprivileged]]");

      $linktarget = $this->getTarget();
      $targetLine = ' target="' . $linktarget . '"' . ' formtarget="' . $linktarget . '"';

      $out = '
              <label title="' . $LabelDesc . '" class="tooltip right">
                                  <button class="'. $class . '" type="button" disabled="disabled" formmethod="post"' . $targetLine . '>
                                    <div class="ui-icon' . $icon . '"></div>
                                      ' . $htmlLabel . '
                                  </button>
              </label>';

      return $out;
    }
    else {
      $action = $this->getAction();
      $label =& $this->getLabel();

      $description = $label->getDescription(); 
      global $_BUTTON_ID; 
      $_BUTTON_ID++;
      $id = $_BUTTON_ID;

      // log activity if necessary
      $system = new System();
      if($system->getConfig("logPath") != "") {
        $labelText = $label->getLabel(); 
      }

      if($description) {

         // Javascript definitions were bypassing toHtml..  
        $i18n =& $page->getI18n();
        $langs = $i18n->getLocales();
        // The HTML_ENTITIES translation table is only valid for the
        // UTF-8 character set. Japanese is the only supported
        // language which does not use the UTF-8 charset, so we
        // do a special case for that.
        $encoding = $i18n->getProperty("encoding", "palette");
        if ($encoding == "none" || !strpos($encoding, "UTF-8") === false ) {
          $specialChars = array_merge(get_html_translation_table(HTML_SPECIALCHARS), get_html_translation_table(HTML_ENTITIES));
          $escaped_description = strtr(strtr($description, array_flip($specialChars)), $specialChars);
        } else {
          $description = htmlspecialchars($description);
        }
        // using interpolateJs this way is not very clean, but this works for now
        $escaped_description = $i18n->interpolateJs("[[VAR.string]]", array("string" => $description));

        // clear up description temporarily because the rollover help of the
        // label prevents button click-through
        $label->setDescription("");
      }
      else {
        $escaped_description = "";
      }

      // restore description if necessary
      if($description) {
        $label->setDescription($description);
      }

      if (get_class($label) == "imagelabel" || is_subclass_of($label, "imagelabel")) {

        $linktarget = $this->getTarget();
        $targetLine = ' target="' . $linktarget . '"' . ' formtarget="' . $linktarget . '"';

        $out = '
                <label title="' . $this->ButtonDescription . '" class="tooltip right">
                                    <button class="no_margin_bottom div_icon' . $this->getWaiter() . $send_right . '" type="submit" formmethod="post"' . $linktarget . '>
                                      <div class="ui-icon' . $icon . '"></div>
                                    </button>
                                </label>';

        return $out;
      }
      else {

        // Handle Demo-Mode:
        if ($this->isDemo() == TRUE) {

          if ($this->type == "save") {
            $htmlLabel = $this->ButtonLabel . ' (' . $i18n->get("[[palette.demo_mode_short]]") . ')</span>';
            $LabelDesc = $i18n->getWrapped("[[palette.Demo]]") . '<br>' . $i18n->getWrapped("[[palette.demo_mode]]");
          }
          else {
            $htmlLabel = $this->ButtonLabel;
            $LabelDesc = $this->ButtonDescription;
          }
          $btn_disabled = ' disabled="disabled"';
        }
        else {
          $htmlLabel = $this->ButtonLabel;
          $LabelDesc = $this->ButtonDescription;
          $btn_disabled = '';
        }

        if (isset($this->ButtonSize)) {
          $button_size = ' ' . $this->ButtonSize;
        }
        else {
          $button_size = '';
        }

        $URL = $this->getAction();
        if (($URL != "") && ($this->type != "save")) {
          $data_link = ' data-link="' . $URL . '"';
          $linkable = ' link_button';
        }
        else {
          $data_link = '';
          $linkable = '';
        }

        if (($data_link != "") && ($linkable != "")) {
          $class_add = " " . $button_size . $linkable;
          $filler = '" ';
          $method = '';

        }
        else {
          $class_add = '';
          $filler = '" type="submit"';
          $method = ' formmethod="post"';
          }

        if ($this->type == "remove") {
          if ($this->getIcon() == NULL) {
            $icon = " ui-icon-trash";
          }
          else {
            $icon = $this->getIcon();
          }
        }

        if (isset($this->labelDisabled->ImageOnly)) {
          if ($this->labelDisabled->ImageOnly == TRUE) {
            $htmlLabel = '';
            $icon = $icon . " icon_only";
          }
        }
        else {
          $htmlLabel = '<span>' . $htmlLabel . '</span>';
        }

        $linktarget = $this->getTarget();
        $targetLine = ' target="' . $linktarget . '"' . ' formtarget="' . $linktarget . '"';

        if ($this->getIcon() != NULL) {
          $icon = $this->getIcon();
        }

        $out = '
                <label title="' . $LabelDesc . '" class="tooltip right">
                                    <button class="'. $class . $this->getWaiter() . $class_add . $filler . $btn_disabled . $method . $data_link . $targetLine . '>
                                      <div class="ui-icon' . $icon . '"></div>'
                                        . $htmlLabel . '
                                    </button>
                                </label>';
        return $out;
      }
    }
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