<?php
// Author: Tim Hockin
// $Id: ImageButton.php

global $isImageButtonDefined;
if($isImageButtonDefined)
    return;
$isImageButtonDefined = true;

include_once("uifc/Button.php");
include_once("uifc/ImageLabel.php");

class ImageButton extends Button {

    var $label;
    //
    // public methods
    //

    // description: constructor
    // param: page: the Page object this object lives in
    // param: action: the string used within HREF attribute of the A tag
    function ImageButton($page, $action, $image, $lbl, $desc) {
        $i18n = $page->getI18n();
        $label = $i18n->get($lbl);
        $description = $i18n->getWrapped($desc);

        // Set our Label and Description in cleartext, too:
        if (isset($page->Label)) {
          $page->Label['label'] = $label;
          $page->Label['description'] = $description;
        }

        $this->Button($page, $action, 
                new ImageLabel($page, $image, $label, $description), 
                new ImageLabel($page, $image, $label, $description)
            );
    }

    function toHtml($style = "") {
        $page =& $this->getPage();
        $i18n = $page->getI18n();

        if($style == null || $style->getPropertyNumber() == 0)
                $style = $this->getDefaultStyle($page->getStylist());

        $isDisabled = $this->isDisabled();

        // find the right style target
        $target = $isDisabled ? "disabled" : "normal";

        // find out style properties
        if ($isDisabled) {

            $i18n =& $page->getI18n();
            $langs = $i18n->getLocales();

            $labelvars = get_object_vars($this->label);

            if (isset($page->Label['label'])) {
                $htmlLabel = '<span>' . $page->Label['label'] . '</span>';
            }
            else {
                $htmlLabel = '';
            }

            if (isset($page->Label['description'])) {
                $LabelDesc = $page->Label['description'];
                $fooltip = ' tooltip';
            }
            else {
                $LabelDesc = '';
                $fooltip = '';
            }

            $targetFrame = $this->targetFrame;
            if ($targetFrame) {
                $targetString = " TARGET=\"$targetFrame\" ";
            }
            else {
                $targetString = "";
            }

            $URL = $this->getAction();
            if ($URL != "") {
                $data_link = ' data-link="' . $URL . '"';
                $linkable = ' link_button';
            }
            else {
                $data_link = '';
                $linkable = '';
            }

            $out = '
                            <button title="' . $LabelDesc . '" class="light close_dialog ' . $fooltip . ' right img_icon' . $targetString . $linkable . '" disabled="disabled"' . $data_link . '>
                              <img src="/.adm/images/icons/small/grey/' . $labelvars['image'] . '.png"></img>
                                ' . $htmlLabel . '
                            </button>'; 
            return $out;      

        } // end if ($isDisabled)
        
        // button not disabled
        $action = $this->getAction();
        $label =& $this->getLabel();
        $description = $label->getDescription(); 

        // log activity if necessary
        $system = new System();
        if ($system->getConfig("logPath") != "") {
            $labelText = $label->getLabel(); 
        }  // end if ($system->getConfig("logPath")...

        $labelvars = get_object_vars($this->label);

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

        // restore description if necessary
        if($description) {
            $label->setDescription($description);
        }

        $targetFrame = $this->targetFrame;
        if ($targetFrame) {
            $targetString = " TARGET=\"$targetFrame\" ";
        }
        else {
            $targetString = "";
        }

        $URL = $this->getAction();
        if ($URL != "") {
            $data_link = ' data-link="' . $URL . '"';
            $linkable = ' link_button';
        }
        else {
            $data_link = '';
            $linkable = '';
        }

        $out = '
                        <button title="' . $LabelDesc . '" class="close_dialog tooltip right img_icon' . $targetString . $linkable . '"' . $data_link . '>
                          <img src="/.adm/images/icons/small/white/' . $labelvars['image'] . '.png"></img>
                            ' . $htmlLabel . '
                        </button>'; 
        return $out;      
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