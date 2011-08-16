// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Password.js 3 2003-07-17 15:19:15Z will $

function Password_submitHandler(element) {
  if(!element.isOptional && element.value == "") {
    top.code.error_invalidElement(element, element.emptyMessage);
    return false;
  }

  var password = element.value;
  var repeat = element.repeatElement.value;

  if(password.length == 0 && repeat.length == 0)
    return true;

  if(password != repeat) {
    top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
    return false;
  }

  return true;
}