<?php
// Author: Kevin K.M. Chiu
// $Id: ModifyButton.php 

global $isModifyButtonDefined;
if($isModifyButtonDefined)
  return;
$isModifyButtonDefined = true;

include_once("uifc/Button.php");
include_once("uifc/ImageLabel.php");

class ModifyButton extends Button {
  //
  // public methods
  //

  function &getDefaultStyle(&$stylist) {
    return $stylist->getStyle("ModifyButton");
  }

  // description: constructor
  // param: page: the Page object this object lives in
  // param: action: the string used within HREF attribute of the A tag
  function ModifyButton(&$page, $action, $demo_override = FALSE) {
    $type = 'modify';
    $i18n =& $page->getI18n();
    //$stylist =& $page->getStylist();
    //$style =& $stylist->getStyle("ModifyButton");

    $label = $i18n->get("modify", "palette");
    $description = $i18n->get("modify_help", "palette");
    $disabled_help = $i18n->get("modify_disabled_help", "palette");

    $this->Button($page, $action, $type, new Label($page, $label, $description), "", $demo_override);
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