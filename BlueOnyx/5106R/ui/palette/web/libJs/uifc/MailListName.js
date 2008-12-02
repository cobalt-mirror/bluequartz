
// Author: Kenneth C.K. Leung
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: MailListName.js 259 2004-01-03 06:28:40Z shibuya $

function MailListName_changeHandler(element) {
  if(!MailListName_isMailListNameValid(element.value)) {
    top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
    return false;
  }

  return true;
}

function MailListName_isMailListNameValid(mailListName) {
  if(mailListName.length == 0)
    return true;

  var ch = mailListName.charAt(0); 
  if(!top.code.string_isLowercaseAlpha(ch) && !top.code.string_isNumeric(ch))
    return false;

  for(var i = 1; i < mailListName.length; i++){ 
    var ch = mailListName.charAt(i); 
    if(!top.code.string_isLowercaseAlpha(ch) && !top.code.string_isNumeric(ch) && ch != '-' && ch != '_' && ch != '.')
      return false;
  }

  return true;
}











