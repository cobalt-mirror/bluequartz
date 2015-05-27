<?php
// Author: Kevin K.M. Chiu
// $Id: FreeSaveButton.php

global $isFreeSaveButtonDefined;
if($isFreeSaveButtonDefined)
  return;
$isFreeSaveButtonDefined = true;

include_once("uifc/Button.php");
include_once("uifc/Label.php");

class FreeSaveButton extends Button {
  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this object lives in
  // param: action: the string used within HREF attribute of the A tag
  function FreeSaveButton(&$page, $action, $labelId="", $demo_override = FALSE) {
    $i18n =& $page->getI18n();
    $type = 'save';

    // Slightly complicated routine to determine the helptext of a Button:
    $pattern = '/\[\[[a-zA-Z0-9\-\_\.]{1,99}\]\]/';
    if (preg_match($pattern, $labelId, $matches)) {
      // Submitted Button-Label is fully qualified (i.e.: [[palette.done]]). 
      // Which means we need to look up the helptext for it. For that we need
      // to extract the label identifier from between the double square brackets:
      $patterns[0] = '/\[\[/';
      $patterns[1] = '/\]\]/';
      $LabelValue = preg_replace($patterns, "", $labelId);
      $sanity = preg_split('/\./', $LabelValue);
      // $LabelValue now contains the raw identifier. Assume the corresponding
      // help text to be "identifier_help" and see what we get:
      $labelIdHelptext = $i18n->getWrapped("[[" . $LabelValue ."_help]]");
      if (isset($sanity[1])) {
        if ($sanity[1] . "_help<br>" == $labelIdHelptext) {
          // If we get here, the Button Label had no dedicated helptext.
          // In that case we set the helptext to the Label ID instead:
          $labelIdHelptext = $i18n->getWrapped($labelId);
        }
      }
    }
    else {
      // Label is not fully qualified. In that case we have it easy:
      $labelIdHelptext = $i18n->getWrapped($labelId."_help");
    }

    if ($labelId != "") {
      $label = $i18n->get($labelId);
    }
    else {
      $label = $i18n->get("detail", "palette");
      $labelIdHelptext = $i18n->getWrapped("detail_help", "palette");
    }
    $this->Button($page, $action, $type, new Label($page, $label, $labelIdHelptext), "", $demo_override);
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