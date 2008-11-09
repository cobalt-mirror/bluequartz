// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: UserName.js 491 2005-08-10 05:17:00Z shibuya $

function UserName_changeHandler(element) {
  if(!UserName_isUserNameValid(element.value)) {
    top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
    return false;
  }

  return true;
}

function UserName_isUserNameValid(name) {
  if(name.length == 0)
    return true;

  if(name.length > 31)
    return false;

  var ch = name.charAt(0);
  if(!top.code.string_isLowercaseAlpha(ch))
    return false;

  for(i = 1; i < name.length; i++) {
    var ch = name.charAt(i);
    if(!top.code.string_isLowercaseAlpha(ch) && !top.code.string_isNumeric(ch)
      && ch != '.' && ch != '-' && ch != '_')
      return false;
  }
  return true;
}
