// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Integer.js 201 2003-07-18 19:11:07Z will $

function Integer_changeHandler(element) {
  if(!Integer_isIntegerValid(element.value, element.min, element.max)) {
    top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
    return false;
  }

  return true;
}

function Integer_isIntegerValid(integer, min, max) {
  if(integer.length == 0)
    return true;

  var value = parseInt(integer);

  if(value.toString() != integer)
	return false;

  if(value < min)
    return false;

  if(value > max)
    return false;

  return true;
}
