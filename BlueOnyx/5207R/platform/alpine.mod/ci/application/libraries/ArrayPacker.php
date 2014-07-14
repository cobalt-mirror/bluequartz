<?php 

/**
 * ArrayPacker.php
 *
 * BlueOnyx ArrayPacker for Codeigniter
 *
 * Description:
 * This is a library of functions for packing and unpacking arrays or hashes
 * into/from strings. The functions use CCE preferred array packing format
 * which is URL-encoded elements delimited by ampersands (&). For example,
 * an array of "first", "seco&d" and "_third" is packed into
 * "&first&seco%26d&_third&".
 *
 * Applicability:
 * Anywhere where arrays or hashes need to be get from or put into CCE.
 *
 *
 * @package   ArrayPacker
 * @author    Michael Stauber, jmayer, Kevin K.M. Chiu
 * @copyright Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
 * @copyright Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
 * @copyright Copyright (c) 2003 Sun Microsystems, Inc.  All rights reserved.
 * @link      http://www.blueonyx.it
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.0
 */


global $isArrayPackerDefined;
if    ($isArrayPackerDefined) return;
else   $isArrayPackerDefined = true;

// description: convert an array to a string
// param: array: an array of strings
// returns: the packed array in string
function arrayToString($array) {
  if(count($array) == 0)
    return "";

  $string = "&";
  for ($i = 0; $i < count($array); $i++) {
    $string .= urlencode($array[$i]) . "&";
  }

  return $string;
}

// description: convert a string to an array
// param: string: a packed array in string
// returns: an array of strings
function stringToArray($string) {
  $enc = explode("&", $string);
  $array = array();
  for ($i = 0; $i < count($enc); $i++) {
    // this test keeps the leading and trailing blank elements out
    if ($enc[$i] != "") {
      array_push($array, urldecode($enc[$i]));
    }
  }
  return $array;
}

// description: convert a multiline-string to an array
// param: string: a packed array in string
// returns: an array of strings
function stringNToArray($string) {
  $enc = explode("\n", $string);
  $array = array();
  for ($i = 0; $i < count($enc); $i++) {
    // this test keeps the leading and trailing blank elements out
    if ($enc[$i] != "") {
      array_push($array, urldecode($enc[$i]));
    }
  }
  return $array;
}

// description: to see if a string is in an array
// param: needle: the string to find
// param: hayStack: a packed array in string
// returns: true if string found, false otherwise
function isInArrayString($needle, $hayStack) {
  $array = stringToArray($hayStack);
  if(in_array($needle, $array)) {
    return true;
  }
  return false;
}

// description: convert a hash (associative array) to a string
//     e.g. ["foo"] = "bar", [1] = "one"  =>  "&foo=bar&1=one&"
// param: array: a hash
// returns: a packed hash in string
function hashToString($array) {
  if(count($array) == 0) {
    return "";
  }

  $keys = array_keys($array);

  $string = "&";
  for ($i = 0; $i < count($keys); $i++) {
    $k = $keys[$i];
    $string .= urlencode($k) . "=" . urlencode($array[$k]) . "&";
  }

  return $string;
}

// description: convert a string to a hash (associative array)
//     e.g. "&foo=bar&1=one&"  =>  ["foo"] = "bar", [1] = "one"
// param: string: a packed hash in string
// returns: a hash
function stringToHash($string) {
  $pairs = explode("&", $string);
  $array = array();

  for ($i = 0; $i < count($pairs); $i++) {
    // this test keeps the leading and trailing blank elements out
    if ($pairs[$i]) {
      $kv = explode("=", $pairs[$i]);
      $array[urldecode($kv[0])] = urldecode($kv[1]);
    }
  }
  return $array;
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