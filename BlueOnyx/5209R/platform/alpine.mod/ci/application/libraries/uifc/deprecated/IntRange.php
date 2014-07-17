<?php
// Author: jmayer@cobalt.com
// $Id: IntRange.php

global $isIntRangeDefined;
if($isIntRangeDefined)
  return;
$isIntRangeDefined = true;

include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");

class IntRange extends FormField {
  //
  // private variables
  //

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this form field lives in
  // param: id: the identifier of this object
  // param: value: the default value
  // param: invalidMessage: message to be shown upon invalid input. Optional
  // param: emptyMessage: message to be shown upon empty input
  //     if the field is not optional. This message is optional
  function IntRange(&$page, $id, $value, $invalidMessage, $emptyMessage = "") {
    // superclass constructor
    $this->FormField($page, $id, $value, $invalidMessage, $emptyMessage);

    $this->isConfirm = true;
  }

  function isConfirm() {
    return $this->isConfirm;
  }

  // description: set the config flag
  // param: isConfirm: if true, a confirm field is shown
  function setConfirm($isConfirm) {
    $this->isConfirm = $isConfirm;
  }

  function toHtml($style = "") {
    $id = $this->getId();
    $access = $this->getAccess();
    $value = $this->getValue();
    
    $regs = array();
    if (preg_match("/^([^:]+):(.+)/", $value, $regs)) {
      $low = $regs[1]; $high = $regs[2];
    } else {
      $low = $value; $high = $value;
    }
    
    $onchange = "if (document.form.high_$id.value - 0 < document.form.low_$id.value - 0) { "
      . " document.form.high_$id.value = document.form.low_$id.value; "
      . " };"
      . "document.form.$id.value = document.form.low_$id.value + ':' + document.form.high_$id.value; "
      . "if (!document.form.low_$id.value) { document.form.$id.value = ''; }; "
	. "return true;";

    $formField = "
<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\">
  <TR>
    <TD>
      <input type=\"hidden\" name=\"$id\" value=\"$value\">
      <input type=\"text\" name=\"low_$id\" value=\"$low\" 
      	size=\"5\"
	onChange=\"$onchange\">
    </td>
    <td>&nbsp;-&nbsp;</td>
    <td>
      <input type=\"text\" name=\"high_$id\" value=\"$high\"
      	size=\"5\"
	onChange=\"$onchange\">
    </td>
  </tr>
</table>
";
  
    return $formField;
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