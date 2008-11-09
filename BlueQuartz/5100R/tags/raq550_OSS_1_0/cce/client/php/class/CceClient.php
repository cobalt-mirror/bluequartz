<?php
// $Id: CceClient.php 259 2004-01-03 06:28:40Z shibuya $
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

global $isCceClientDefined;
if($isCceClientDefined)
  return;
$isCceClientDefined = true;

include("CceError.php");
include("System.php");

class CceClient {
  //
  // private variables
  //

  var $handle;
  var $isConnected;

  //
  // public methods
  //

  // description: constructor
  function CceClient() {
    $this->handle = ccephp_new();

    // set default
    $this->isConnected = false;
  }

  // description: authenticate to the server
  // param: userName: user name in string
  // param: password: password in string
  // returns: session ID if succeed, empty string if failed
  function auth($userName, $password) {
    return ccephp_auth($this->handle, $userName, $password);
  }

  // description: set CCE read-only.
  // Requires: systemAdministrator access
  // param: reason: reason for CCE being read-only
  // returns: true is successful
  function suspend( $reason ) {
    return ccephp_suspend( $this->handle, $reason );
  }

  // description: set CCE read-write after a call to suspend().
  // Requires: systemAdministrator access
  // returns: true is successful
  function resume( ) {
    return ccephp_resume( $this->handle );
  }

  // description: authenticate using a session id
  function authkey( $userName, $sessionId ) {
    return ccephp_authkey( $this->handle, $userName, $sessionId );
  }

  // description: determine the currently authenticated user
  // returns: the oid of the user object
  function whoami( ) {
    return ccephp_whoami( $this->handle );
  }

  // description: disconnect from cce
  function bye() {
    $this->isConnected = false;

    return ccephp_bye($this->handle);
  }

  // description: begin delayed-handler mode
  function begin() {
    return ccephp_begin($this->handle);
  }

  // description: trigger all handlers to run since begin() call
  // returns: a success code based on the success or failure of all
  //          operations since begin()
  function commit() {
    return ccephp_commit($this->handle);
  }

  // end authenticated session to cce
  function endkey() {
    return ccephp_endkey($this->handle);
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

    $this->isConnected = ccephp_connect($this->handle, $socketPath);

    return $this->isConnected;
  }

  // description: determine if CCE is suspended or not
  // returns: reason string if suspended, false otherwise
  function suspended() {
    return ccephp_suspended($this->handle);
  }

  // description: create a CCE object of type $class, with properties in $vars
  // returns: oid of created object, or 0 on failure
  // usage: $oid = $cce->create($class, array( 'property' => 'value' ));
  function create($class, $vars = array()) {
    return ccephp_create($this->handle, $class, $vars);
  }

  // description: destroy the CCE object with oid $oid
  // returns: boolean true for success, false for failure
  // usage: $ok = $cce->destroy($oid);
  function destroy($oid) {
    return ccephp_destroy($this->handle, $oid);
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

    $errors = ccephp_errors($this->handle);
    for($i = 0; $i < count($errors); $i++) {
      $error = $errors[$i];
      $errorObjs[] = new CceError($error["code"],
                                  $error["oid"],
                                  $error["key"],
                                  $error["message"],
                                  array("code" => $error["code"],
                                  "oid"  => $error["oid"],
                                  "key"  => $error["key"]));
    }

    return $errorObjs;
  }

  // description: returns array of hashes.  Each hash contains
  // information about a particular error, include "code", "oid",
  // "key", and "message".
  function raw_errors() {
    return ccephp_errors($this->handle);
  }

  // the legacy find command
  // returns: matching $oids
  // usage: $oids = $cce->find($class, array( 'property' => 'value'));
  function find($class, $vars = array()) {
    return ccephp_find($this->handle, $class, $vars, "", 0);
  }

  // find objects, returning a list sorted by the values in
  // the $key attribute
  // sort is ascii sort
  // returns: matching $oids
  // usage: $oids = $cce->findSorted($class, $sortkey,
  //                                 array( 'property' => 'value'));
  // DEPRECATED
  function findSorted($class, $key, $vars = array()) {
    return ccephp_find($this->handle, $class, $vars, $key, 0);
  }

  // like findSorted, but sort is numeric instead of ascii.
  // returns: matching $oids
  // usage: $oids = $cce->findSorted($class, $sortkey,
  //                                 array( 'property' => 'value'));
  // DEPRECATED
  function findNSorted($class, $key, $vars = array()) {
    return ccephp_find($this->handle, $class, $vars, $key, 1);
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
  // usage: $oids = $cce->findx($class, $vars, $regex_vars,
  //                            $sorttype, $sortkey);
  function findx($class, $vars = array(), $revars = array(),
   $sorttype="", $sortprop = "") {
    return ccephp_findx($this->handle, $class, $vars, $revars,
        $sorttype, $sortprop);
  }

  // description: gets the $namespace of the CCE object with given $oid
  // returns: a property hash or null if the object cannot be found
  // usage: $object = $cce->get($oid, $namespace);
  function get($oid, $namespace = "") {
    return ccephp_get($this->handle, $oid, $namespace);
  }

  // description: get a CCE object matching given properties
  //              the match is done using 'find', so no findx parameters
  //              can be used
  // returns: a property hash or null if the object cannot be found
  // usage: $object = $cce->getObject($class,
  //                                  array( 'property' => 'value'),
  //                                  $namespace);
  // DEPRECATED
  function getObject($class, $vars = array(), $namespace = "") {
    $oids = $this->find($class, $vars);
    if(count($oids) > 0)
      return $this->get($oids[0], $namespace);
    else
      return null;
  }

  // description: get an array of CCE objects matching given properties
  //              the match is done using 'find', so no findx parameters
  //              can be used
  // returns: an array of property hashes, or null if no objects can be found
  // usage: $objects = $cce->getObjects($class,
  //                                    array( 'property' => 'value'),
  //                                    $namespace);
  // DEPRECATED
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
    return ccephp_names($this->handle, $arg);
  }

  // description: set object properties in CCE
  // returns: boolean true for success, boolean false for failure
  // usage: $ok = $cce->set($oid, $namespace, array( 'property' => 'value'));
  function set($oid, $namespace = "", $vars = array()) {
    return ccephp_set($this->handle, $oid, $namespace, $vars);
  }

  // description: set a CCE object
  //              the match is done using 'find', so no findx parameters
  //              can be used
  // returns: true on success, false otherwise
  // usage: $ok = $cce->set($class, array( 'property' => 'value'),
  //                        $namespace, array( 'findkey' => 'findvalue'));
  // DEPRECATED
  function setObject($class, $setVars = array(), $namespace = "",
                     $findVars = array()) {
    $oids = $this->find($class, $findVars);
    if(count($oids) > 0)
      return $this->set($oids[0], $namespace, $setVars);
    else
      return 0;
  }

  // description: set a CCE object. If it does not exist, create it
  //              the match is done using 'find', so no findx parameters
  //              can be used
  // returns: true on success, false otherwise
  // usage: $ok = $cce->set($class, array( 'property' => 'value'),
  //                        $namespace, array( 'findkey' => 'findvalue'));
  // DEPRECATED
  function setObjectForce($class, $setVars = array(),
                          $namespace = "", $findVars = array()) {
    $oids = $this->find($class, $findVars);

    // create if necessary
    if(count($oids) == 0)
      if(!$this->create($class))
        return 0;

    return $this->setObject($class, $setVars, $namespace, $findVars);
  }

  // description: determines if the current session is a handler in
  //              rollback mode.
  function isRollback() {
    return ccephp_is_rollback($this->handle);
  }

  // description: converts a array into a CCE-encoded scalar
  function array_to_scalar( $array ) {
    $result = "&";
    foreach($array as $value)
    {
      $value = preg_replace("/([^A-Za-z0-9_\. -])/e",
                            "sprintf('%%%02X', ord('\\1'))", $value);
      $value = preg_replace("/ /", "+", $value);

      $result .= $value . "&";
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
    $array = explode("&", $scalar);
    for($i = 0; $i < count($array); $i++)
    {
      $array[$i] = preg_replace("/\+/", " ", $array[$i]);
      $array[$i] = preg_replace("/%([0-9a-fA-F]{2})/e",
                                "chr(hexdec('\\1'))", $array[$i]);
    }

    return $array;
    }
}
/*
Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

-Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.

-Redistribution in binary form must reproduce the above copyright notice, 
this list of conditions and the following disclaimer in the documentation and/or 
other materials provided with the distribution.

Neither the name of Sun Microsystems, Inc. or the names of contributors may 
be used to endorse or promote products derived from this software without 
specific prior written permission.

This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
*/
?>
