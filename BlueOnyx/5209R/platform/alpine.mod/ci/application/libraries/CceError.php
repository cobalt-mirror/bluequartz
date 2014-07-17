<?php

/**
 * CceError.php
 *
 * BlueOnyx CceError for Codeigniter
 *
 * @package   CceError
 * @author    Michael Stauber, Kevin K.M. Chiu
 * @copyright Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
 * @copyright Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
 * @copyright Copyright (c) 2003 Sun Microsystems, Inc.  All rights reserved.
 * @copyright Copyright (c) 2000 Cobalt Networks.  All rights reserved.
 * @link      http://www.blueonyx.it
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.0
 */

global $isCceErrorDefined;
if($isCceErrorDefined)
  return;
$isCceErrorDefined = true;

include_once("Error.php");

class CceError extends Error {
  //
  // private variables
  //

  var $code;
  var $oid;
  var $key;

  //
  // public methods
  //

  function CceError($code, $oid, $key, $message, $vars = array()) {
    // superclass constructor
    $this->Error($message, $vars);

    $this->setCode($code);
    $this->setOid($oid);
    $this->setKey($key);

  }

  function getCode() {
    return $this->code;
  }

  function setCode($code) {
    $this->code = $code;
  }

  function getKey() {
    return $this->key;
  }

  function setKey($key) {
    $this->key = $key;
  }

  function getOid() {
    return $this->oid;
  }

  function setOid($oid) {
    $this->oid = $oid;
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