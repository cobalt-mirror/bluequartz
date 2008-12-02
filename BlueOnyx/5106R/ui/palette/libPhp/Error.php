<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Error.php 259 2004-01-03 06:28:40Z shibuya $

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
