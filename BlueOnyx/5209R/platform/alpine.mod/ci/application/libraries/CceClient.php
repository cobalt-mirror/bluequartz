<?php

/**
 * CceClient.php
 *
 * BlueOnyx CceClient for Codeigniter
 *
 * @package   CceClient
 * @author    Michael Stauber
 * @copyright Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
 * @copyright Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
 * @copyright Copyright (c) 2003 Sun Microsystems, Inc.  All rights reserved.
 * @link      http://www.blueonyx.it
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.0
 */

global $isCceClientDefined;
if($isCceClientDefined)
  return;
$isCceClientDefined = true;

include_once("CceError.php");
include_once("System.php");

class CceClient {
  //
  // private variables
  //

  var $handle;
  var $isConnected;

  // Connection method: 
  // TRUE = PHP Class
  // FALSE = PHP Module
  var $NATIVE;

  // Username:
  var $Username;

  // SessionId:
  var $SessionId;

  // Password:
  var $Password;

  // Self:
  var $self;

  // ERRORS:
  var $ERRORS;

  // OID (for errors related to SET transactions):
  var $OID;

  //
  // public methods
  //

  function setNative($CM) {
    $this->NATIVE = $CM;
  }

  function getNative() {
    return $this->NATIVE;
  }

  function setUsername($Username = "") {
    $this->Username = $Username;
  }

  function getUsername() {
    $CI =& get_instance();
    if (!isset($this->Username)) {
      $this->Username = $CI->input->cookie('loginName');
    }
    return $this->Username;
  }

  function setSessionId($SessionId = "") {
    $this->SessionId = $SessionId;
  }

  function getSessionId() {
    $CI =& get_instance();
    if (!isset($this->SessionId)) {
      $this->SessionId = $CI->input->cookie('sessionId');
    }
    return $this->SessionId;
  }

  function setPassword($Password = "") {
    $this->Password = $Password;
  }

  function getPassword() {
    return $this->Password;
  }

  function setDebug($DEBUG = "") {
    $this->DEBUG = $DEBUG;
  }

  function getDebug() {
    return $this->DEBUG;
  }

  // description: constructor
  function CceClient() {

    // Check if cce.so is loaded:
    if (function_exists('ccephp_new')) {
      // It is. 
      $this->setNative(FALSE);
      // Use it:
      $this->handle = ccephp_new();
    }
    else {
      // It is not.
      $this->setNative(TRUE);
      // So we do it the hard way via CCE.php:
      $CI =& get_instance();
      $CI->load->library('CCE');
      $this->handle = CCE::ccephp_new();
    }

    if (is_file("/etc/DEBUG")) {
      $this->setDebug(TRUE);
    }
    else {
      $this->setDebug(FALSE);
    }

    // set default
    $this->isConnected = FALSE;
  }

  // description: authenticate to the server
  // param: userName: user name in string
  // param: password: password in string
  // returns: session ID if succeed, empty string if failed
  function auth($userName, $password) {
    if (!$this->getNative()) {
      if ($this->DEBUG) {
        error_log("Command: AUTH $userName XXXX");
      }
      return ccephp_auth($this->handle, $userName, $password);
    }
    else {
      return CCE::ccephp_auth($userName, $password);
    }
  }

  // description: set CCE read-only.
  // Requires: systemAdministrator access
  // param: reason: reason for CCE being read-only
  // returns: true is successful
  function suspend( $reason ) {
    if (!$this->getNative()) {
      if ($this->DEBUG) {
        error_log("Command: SUSPEND");
      }
      return ccephp_suspend( $this->handle, $reason );
    }
    else {
      return CCE::ccephp_suspend($reason);
    }
  }

  // description: set CCE read-write after a call to suspend().
  // Requires: systemAdministrator access
  // returns: true is successful
  function resume( ) {
    if (!$this->getNative()) {
      if ($this->DEBUG) {
        error_log("Command: RESUME");
      }
      return ccephp_resume( $this->handle );
    }
    else {
      return CCE::ccephp_resume();
    }
  }

  // description: authenticate using a session id
  function authkey( $userName, $sessionId ) {
    if (!$this->getNative()) {
      if ($this->DEBUG) {
        error_log("Command: AUTHKEY $userName $sessionId");
      }
      return ccephp_authkey( $this->handle, $userName, $sessionId );
    }
    else {
      return CCE::ccephp_authkey($userName, $sessionId );
    }
  }

  // description: determine the currently authenticated user
  // returns: the oid of the user object
  function whoami( ) {
    if (!$this->getNative()) {
      if ($this->DEBUG) {
        error_log("Command: WHOAMI");
      }
      return ccephp_whoami( $this->handle );
    }
    else {
      return CCE::ccephp_whoami();
    }
  }

  // description: disconnect from cce
  function bye() {
    $this->isConnected = false;
    if (!$this->getNative()) {
      if ($this->DEBUG) {
        error_log("Command: BYE");
      }
      return ccephp_bye($this->handle);
    }
    else {
      return CCE::ccephp_bye();
    }
  }

  // description: begin delayed-handler mode
  function begin() {
    if (!$this->getNative()) {
      if ($this->DEBUG) {
        error_log("Command: BEGIN");
      }
      return ccephp_begin($this->handle);
    }
    else {
      return CCE::ccephp_begin();
    }
  }

  // description: trigger all handlers to run since begin() call
  // returns: a success code based on the success or failure of all
  //          operations since begin()
  function commit() {
    if (!$this->getNative()) {
      if ($this->DEBUG) {
        error_log("Command: COMMIT");
      }
      return ccephp_commit($this->handle);
    }
    else {
      return CCE::ccephp_commit();
    }
  }

  // end authenticated session to cce
  function endkey() {
    if (!$this->getNative()) {
      if ($this->DEBUG) {
        error_log("Command: ENDKEY");
      }
      return ccephp_endkey($this->handle);
    }
    else {
      return CCE::ccephp_endkey();
    }
  }

  // description: connect to CCE
  // param: socketPath: the path of the Unix domain socket to CCE
  // returns: true if succeed, false otherwise
  function connect($socketPath = "") {
    if($socketPath == "") {
      // get from config file
      $system = new System();
      $socketPath = $system->getConfig("ccedSocketPath");
    }

    if (!$this->getNative()) {
      $this->isConnected = ccephp_connect($this->handle, $socketPath);
    }
    else {
      $this->isConnected = CCE::ccephp_connect($socketPath);
    }

    return $this->isConnected;
  }

  // description: determine if CCE is suspended or not
  // returns: reason string if suspended, false otherwise
  function suspended() {
    if (!$this->getNative()) {
      return ccephp_suspended($this->handle);
    }
    else {
      return CCE::ccephp_suspended();
    }
  }

  // description: create a CCE object of type $class, with properties in $vars
  // returns: oid of created object, or 0 on failure
  // usage: $oid = $cce->create($class, array( 'property' => 'value' ));
  function create($class, $vars = array()) {
    if (!$this->getNative()) {
      if ($this->DEBUG) {
        $varline = " ";
        foreach ($vars as $key => $value) {
          $varline .= "$key = \"" . CceClient::_escape($value) . "\" ";
        }
        $varline = rtrim($varline);
        error_log("Command: CREATE $class $varline");
      }
      return ccephp_create($this->handle, $class, $vars);
    }
    else {
      return CCE::ccephp_create($class, $vars);
    }
  }

  // description: destroy the CCE object with oid $oid
  // returns: boolean true for success, false for failure
  // usage: $ok = $cce->destroy($oid);
  function destroy($oid) {
    if (!$this->getNative()) {
      if ($this->DEBUG) {
        error_log("Command: DESTROY $oid ");
      }
      return ccephp_destroy($this->handle, $oid);
    }
    else {
      return CCE::ccephp_destroy($oid);
    }
  }

  // description: destroy all objects of type $class that matching the
  // properties in $vars.
  // the match is done using 'find', so no findx parameters can be used
  // returns: nothing
  // usage: $cce->destroyObjects($class, array( 'findkey' => 'findvalue'));
  // DEPRECATED
  function destroyObjects($class, $vars = array()) {
    $oids = $this->find($class, $vars);
    for($i = 0; $i < count($oids); $i++)
      $this->destroy($oids[$i]);
  }

  // description: get the last occured error
  // returns: an array of CceError objects
  // usage: $errors = $cce->errors();
  function errors() {
    $errorObjs = array();

    if (!$this->getNative()) {
      $errors = ccephp_errors($this->handle);
    }
    else {
     $errors = CCE::ccephp_errors(); 
    }

    for($i = 0; $i < count($errors); $i++) {
      $error = $errors[$i];
      if (isset($error["key"])){
        $ekey = $error["key"];
      }
      else {
        $ekey = "";
      }
      $errorObjs[] = new CceError($error["code"],
                                  $error["oid"],
                                  $ekey,
                                  $error["message"],
                                  array("code" => $error["code"],
                                  "oid"  => $error["oid"],
                                  "key"  => $ekey));
    }

    return $errorObjs;
  }

  // description: returns array of hashes.  Each hash contains
  // information about a particular error, include "code", "oid",
  // "key", and "message".
  function raw_errors() {
    if (!$this->getNative()) {
      return ccephp_errors($this->handle);
    }
    else {
     return CCE::ccephp_errors(); 
    }
  }

  // the legacy find command
  // returns: matching $oids
  // usage: $oids = $cce->find($class, array( 'property' => 'value'));
  function find($class, $vars = array()) {
    if (!$this->getNative()) {
      if ($this->DEBUG) {
        $varline = " ";
        foreach ($vars as $key => $value) {
          $varline .= "$key = \"" . CceClient::_escape($value) . "\" ";
        }
        $varline = rtrim($varline);
        error_log("Command: FIND $class $varline");
      }
      return ccephp_find($this->handle, $class, $vars, "", 0);
    }
    else {
      return CCE::ccephp_find($class, $vars, "", 0);
    }
  }

  // find objects, returning a list sorted by the values in
  // the $key attribute
  // sort is ascii sort
  // returns: matching $oids
  // usage: $oids = $cce->findSorted($class, $sortkey, array( 'property' => 'value'));
  // DEPRECATED - this just does a regular find()
  function findSorted($class, $key, $vars = array()) {
    if (!$this->getNative()) {
      return ccephp_find($this->handle, $class, $vars, $key, 0);
    }
    else {
      return CCE::ccephp_find($class, $vars, $key, 0);
    }
  }

  // like findSorted, but sort is numeric instead of ascii.
  // returns: matching $oids
  // usage: $oids = $cce->findSorted($class, $sortkey, array( 'property' => 'value'));
  // DEPRECATED - this just does a regular find()
  function findNSorted($class, $key, $vars = array()) {
    if (!$this->getNative()) {
      return ccephp_find($this->handle, $class, $vars, $key, 1);
    }
    else {
      return CCE::ccephp_find($class, $vars, $key, 1);
    }
  }

  // Description: advanced method of finding objects
  // $class : class to find
  // $vars : exact-match criteria
  // $revars : regex-match criteria
  // $sorttype : name of sorttype to use (optional)
  //           : listed in basetypes.schema, valid types are
  //           : ascii, old_numeric, locale, ip, hostname
  // $sortprop : name of property (key) on which to sort
  // returns: matching $oids
  // usage: $oids = $cce->findx($class, $vars, $regex_vars, $sorttype, $sortkey);
  function findx($class, $vars = array(), $revars = array(), $sorttype="", $sortprop = "") {
    if (!$this->getNative()) {
      if ($this->DEBUG) {
        $varline = " ";
        foreach ($vars as $key => $value) {
          $varline .= "$key = \"" . CceClient::_escape($value) . "\" ";
        }
        $varline = rtrim($varline);
        error_log("Command: FINDX $class $varline");
      }
      return ccephp_findx($this->handle, $class, $vars, $revars, $sorttype, $sortprop);
    }
    else {
      return CCE::ccephp_findx($class, $vars, $revars, $sorttype, $sortprop);
    }
  }

  // description: gets the $namespace of the CCE object with given $oid
  // returns: a property hash or null if the object cannot be found
  // usage: $object = $cce->get($oid, $namespace);
  function get($oid, $namespace = "") {
    if (!$this->getNative()) {
      if ($this->DEBUG) {
        if ($namespace != "") {
          error_log("Command: GET $oid . $namespace");
        }
        else {
          error_log("Command: GET $oid");
        }
      }
      return ccephp_get($this->handle, $oid, $namespace);
    }
    else {
     return CCE::ccephp_get($oid, $namespace); 
    }
  }

  // description: get a CCE object matching given properties
  //              the match is done using 'find', so no findx parameters
  //              can be used
  // returns: a property hash or null if the object cannot be found
  // usage: $object = $cce->getObject($class, array( 'property' => 'value'), $namespace);
  function getObject($class, $vars = array(), $namespace = "") {
    $oids = $this->find($class, $vars);
    if ((count($oids) > 0) && (isset($oids[0]))) {
      return $this->get($oids[0], $namespace);
    }
    else {
      return NULL;
    }
  }

  // description: get an array of CCE objects matching given properties
  //              the match is done using 'find', so no findx parameters
  //              can be used
  // returns: an array of property hashes, or null if no objects can be found
  // usage: $objects = $cce->getObjects($class, array( 'property' => 'value'), $namespace);
  function getObjects($class, $vars = array(), $namespace = "") {
    $oids = $this->find($class, $vars);

    $objects = array();
    for($i = 0; $i < count($oids); $i++)
      $objects[] = $this->get($oids[$i], $namespace);

    return $objects;
  }

  // returns: true if the client is connected to the server, false otherwise
  function isConnected() {
    return $this->isConnected;
  }

  // gets the names of the namespaces of an object or class
  // returns: array of strings, the names of the namespaces of the object
  // usage: $namespaces = $cce->names($oid); OR
  // usage: $namespaces = $cce->names($classname);
  function names($arg) {
    if (!$this->getNative()) {
      if ($this->DEBUG) {
        error_log("Command: NAMES $arg");
      }
      return ccephp_names($this->handle, $arg);
    }
    else {
      return CCE::ccephp_names($arg);
    }
  }

  // description: set object properties in CCE
  // returns: boolean true for success, boolean false for failure
  // usage: $ok = $cce->set($oid, $namespace, array( 'property' => 'value'));
  function set($oid, $namespace = "", $vars = array()) {
    if (!$this->getNative()) {
      if ($this->DEBUG) {
        $varline = " ";
        foreach ($vars as $key => $value) {
          $varline .= "$key = \"" . CceClient::_escape($value) . "\" ";
        }
        $varline = rtrim($varline);
        if ($namespace == "") {
          error_log("Command: SET $oid $varline");
        }
        else {
          error_log("Command: SET $oid . $namespace  $varline");
        }
      }
      return ccephp_set($this->handle, $oid, $namespace, $vars);
    }
    else {
      return CCE::ccephp_set($oid, $namespace, $vars);
    }
  }

  // description: set a CCE object
  //              the match is done using 'find', so no findx parameters
  //              can be used
  // returns: true on success, false otherwise
  // usage: $ok = $cce->set($class, array( 'property' => 'value'), $namespace, array( 'findkey' => 'findvalue'));
  function setObject($class, $setVars = array(), $namespace = "", $findVars = array()) {
    $oids = $this->find($class, $findVars);
    if (count($oids) > 0) {
      return $this->set($oids[0], $namespace, $setVars);
    }
    else {
      return 0;
    }
  }

  // description: set a CCE object. If it does not exist, create it
  //              the match is done using 'find', so no findx parameters
  //              can be used
  // returns: true on success, false otherwise
  // usage: $ok = $cce->set($class, array( 'property' => 'value'), $namespace, array( 'findkey' => 'findvalue'));
  function setObjectForce($class, $setVars = array(), $namespace = "", $findVars = array()) {
    $oids = $this->find($class, $findVars);

    // create if necessary
    if(count($oids) == 0)
      if(!$this->create($class))
        return 0;

    return $this->setObject($class, $setVars, $namespace, $findVars);
  }

  // description: determines if the current session is a handler in rollback mode.
  function isRollback() {
    if (!$this->getNative()) {
      return ccephp_is_rollback($this->handle);
    }
    else {
      return CCE::ccephp_is_rollback();
    }
  }

  // description: converts a array into a CCE-encoded scalar
  function array_to_scalar( $array ) {
    $result = "&";
    if (is_array($array)) {
          $result = "&";
          foreach($array as $value) {
              $value = preg_replace("/([^A-Za-z0-9_\. -])/e", "sprintf('%%%02X', ord('\\1'))", $value);
              $value = preg_replace("/ /", "+", $value);

              $result .= $value . "&";
          }
    }
      if ($result == "&") $result = "";
        return $result;
  }

  // description: converts a CCE-encoded scalar into an array
  function scalar_to_array( $scalar ) {
    // just in case trim off whitespace
    $scalar = trim($scalar);

    $scalar = preg_replace("/^&/", "", $scalar);
    $scalar = preg_replace("/&$/", "", $scalar);
    $scalar = preg_replace("/;/", "", $scalar);

    $array = explode("&", $scalar);
    for($i = 0; $i < count($array); $i++) {
      $array[$i] = preg_replace("/\+/", " ", $array[$i]);
      $array[$i] = preg_replace("/%([0-9a-fA-F]{2})/e",
                                "chr(hexdec('\\1'))", $array[$i]);
    }
    $array = array_filter($array);
    return $array;
  }

  // description: converts a string to a CCE-encoded scalar. 
  // This is new as of 5200R and is a necessity due to CodeIgniters
  // XSS cleaning of our form data:
  function string_to_scalar ($string) {
    // Just in case trim off whitespace:
    $string = trim($string);

    // Strip leading and trailing "&" - just in case as well:
    $string = preg_replace("/^&/", "", $string);
    $string = preg_replace("/&$/", "", $string);

    // Strip excess whitespaces:
    $string = preg_replace("/\s\s+/", " ", $string);

    // Replace ", " with "&":
    $string = preg_replace("/,[\s+]{0,999}/i", "&", $string);

    // Replace "\n" with "&":
    $string = preg_replace("/\n/i", "&", $string);

    // Build scalar:
    if ($string) {
      $scalar = "&" . $string . "&";
    }
    else {
      $scalar = "";
    }

    return $scalar;
  }

  // description: converts a CCE-encoded scalar into a string:
  function scalar_to_string($scalar) {
    if (preg_match("/^\&(.*)\&$/", $scalar, $regs)) {
      $value = implode("\n", stringToArray($scalar));
    }
    else {
      $value = $scalar;
    }
    return $value;
  }    

  // _escape: This function is used to clean up data comming
  // from the GUI in a fashion so that it can be stored into CODB.
  function _escape($text) {
    if (is_array($text)) {
      // We have an array. Transform it into a scalar for easier processing:
      $text = array_to_scalar($text);
    }

    // Check if this is a simple matter. If so, return right away:
    if (preg_match('/^[a-zA-Z0-9_]+$/', $text)) {
      return $text;
    }

    // Replace unwanted chars with their double escaped counterparts or another safe replacement:
    $out = str_replace(array("\\", "\a", "\b", "\f", "\n", "\t", '"', '$', '&quot;', '&amp;', '&#39;', '&lt;', '&gt;'), array( "\\\\", "\\a", "\\b", "\\f", "\\n", "\\t", "\\\"", "\\$", '\"', '\&', "'", '<', '>'), $text); 
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