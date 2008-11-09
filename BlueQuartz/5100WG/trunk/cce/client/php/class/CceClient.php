<?php
// Author: Harris Vargon-Lloyd, Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: CceClient.php 233 2003-08-07 07:39:51Z shibuya $

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
    $this->handle = cce_new();

    // set default
    $this->isConnected = false;
  }

  // description: authenticate to the server
  // param: userName: user name in string
  // param: password: password in string
  // returns: session ID if succeed, empty string if failed
  function auth($userName, $password) {
    return cce_auth($this->handle, $userName, $password);
  }

  function authkey( $userName, $sessionId ) {
    return cce_authkey( $this->handle, $userName, $sessionId );
  }

  function whoami( ) {
    return cce_whoami( $this->handle );
  }

  function bye() {
    $this->isConnected = false;

    return cce_bye($this->handle);
  }

  function commit() {
    return cce_commit($this->handle);
  }

  function endkey() {
    return cce_endkey($this->handle);
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

    $this->isConnected = cce_connect($this->handle, $socketPath);

    return $this->isConnected;
  }

  function create($class, $vars = array()) {
    return cce_create($this->handle, $class, $vars);
  }

  function destroy($oid) {
    return cce_destroy($this->handle, $oid);
  }

  function destroyObjects($class, $vars = array()) {
    $oids = $this->find($class, $vars);
    for($i = 0; $i < count($oids); $i++)
      $this->destroy($oids[$i]);
  }

  // description: get the last occured error
  // returns: an array of CceError objects
  function errors() {
    $errorObjs = array();

    $errors = cce_errors($this->handle);
    for($i = 0; $i < count($errors); $i++) {
      $error = $errors[$i];
      $errorObjs[] = new CceError($error["code"], $error["oid"], $error["key"], $error["message"], array("code" => $error["code"], "oid"  => $error["oid"], "key"  => $error["key"]));
    }

    return $errorObjs;
  }

  // description: returns array of hashes.  Each hash contains
  // information about a particular error, include "code", "oid",
  // "key", and "message".  
  function raw_errors() {
	return cce_errors($this->handle);
  }

  // the legacy find command
  function find($class, $vars = array()) {
    return cce_find($this->handle, $class, $vars, "", 0);
  }

  // find objects, returning a list sorted by the values in
  // the $key attribute
  function findSorted($class, $key, $vars = array()) {
	return cce_find($this->handle, $class, $vars, $key, 0);
  }

  // like findSorted, but sort is numeric instead of
  // alphabetic.
  function findNSorted($class, $key, $vars = array()) {
	return cce_find($this->handle, $class, $vars, $key, 1);
  }

  function get($oid, $namespace = "") {
    return cce_get($this->handle, $oid, $namespace);
  }

  // description: get a CCE object
  // returns: a property hash or null if the object cannot be found
  function getObject($class, $vars = array(), $namespace = "") {
    $oids = $this->find($class, $vars);
    if(count($oids) > 0)
      return $this->get($oids[0], $namespace);
    else
      return null;
  }

  // description: get an array of CCE objects
  // returns: an array of property hashes
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

  function names($arg) {
    return cce_names($this->handle, $arg);
  }

  function set($oid, $namespace = "", $vars = array()) {
    return cce_set($this->handle, $oid, $namespace, $vars);
  }

  // description: set a CCE object
  // returns: true on success, false otherwise
  function setObject($class, $setVars = array(), $namespace = "", $findVars = array()) {
    $oids = $this->find($class, $findVars);
    if(count($oids) > 0)
      return $this->set($oids[0], $namespace, $setVars);
    else
      return 0;
  }

  // description: set a CCE object. If it does not exist, create it
  function setObjectForce($class, $setVars = array(), $namespace = "", $findVars = array()) {
    $oids = $this->find($class, $findVars);

    // create if necessary
    if(count($oids) == 0)
      if(!$this->create($class))
	return 0;

    return $this->setObject($class, $setVars, $namespace, $findVars);
  }

  function array_to_scalar( $array ) {
		$result = "";
  	while( list($index,$val) = each($array) ) {
			# First quote quotes.
			$val = strtr($val,"\\","\\\\");
			# Then quote breaks,
			$val = strtr($val,"","\\:");
			$result .= $val . ':';
		}
		if( $val ) {
			chop($val);
		}
		return $val;
  }

	function scalar_to_array( $scalar ) {
		$ret_array = array();
		$array = explode(":",$scalar);

		while( list($index,$val) = each($array) ) {

			$elem .= $curr_elem;
	
			if( substr($elem, -1) != "\\" ) {
				$elem = strstr($elem,"\\\\","\\");
				array_push($ret_array, $elem);
			} else {
				chop($elem);
				$elem .= ":";
			}
		}

		if( $elem ) {
			array_push($ret_array, $elem);
		}

		return $ret_array;
	}

  // Description: find out whether or not this user has the capability $capName
  // returns: true if the user has this cce capability, false otherwise
  function getCapable( $capName) {
    if (!($cap = $this->getObject("Capability", $capName))) {
      print("Capability Object with namespace=$capName not found!");
      exit();
    }
    return $cap["capable"];
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
