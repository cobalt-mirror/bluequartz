<?php

/**
 * Error.php
 *
 * BlueOnyx Error for Codeigniter
 *
 * @package   Error
 * @author    Michael Stauber, Kevin K.M. Chiu
 * @link      http://www.blueonyx.it
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.0
 */

// description:
// This class represents an error.

global $isErrorDefined;
if($isErrorDefined)
  return;
$isErrorDefined = true;

class Error {
  //
  // private variables
  //

  var $message;
  var $vars;

  //
  // public methods
  //

  // description: constructor
  // param: message: an internationalizable string (i.e. can have [[domain.id]] tags)
  // param: vars: a hash of variable names to values for localizing the string
  function Error($message, $vars = array()) {
    $this->setMessage($message, $vars);
  }

  // decsription: get the error message
  // returns: an internationalizable string
  // see: setMessage()
  function getMessage() {
    return $this->message;
  }

  // description: set the error message
  // param: message: an internationalizable string (i.e. can have [[domain.id]] tags)
  // param: vars: a hash of variable names to values for localizing the string
  //     Optional
  // see: getMessage(), getVars()
  function setMessage($message, $vars = array()) {
    $this->message = $message;
    $this->vars = $vars;
  }

  // description: get the hash for string localization
  // returns: vars: a hash of variable names to values for localizing the
  //     message string. Optional
  // see: setMessage()
  function getVars() {
    return $this->vars;
  }

  // description: adding a variable to the string localization hash
  // param: key: the key of the variable in string
  // param: val: the value of the variable in string
  // see: getVars()
  function setVar($key, $val) {
    $this->vars[$key] = $val;
    return true;
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