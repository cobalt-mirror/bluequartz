<?php 
// Author: jmayer, Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: ArrayPacker.php 201 2003-07-18 19:11:07Z will $
//
// description:
// This is a library of functions for packing and unpacking arrays or hashes
// into/from strings. The functions use CCE preferred array packing format
// which is URL-encoded elements delimited by ampersands (&). For example,
// an array of "first", "seco&d" and "_third" is packed into
// "&first&seco%26d&_third&".
//
// applicability:
// Anywhere where arrays or hashes need to be get from or put into CCE.

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

