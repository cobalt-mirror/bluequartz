// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Url.js 3 2003-07-17 15:19:15Z will $

function Url_changeHandler(element) {
  if(!Url_isUrlValid(element.value)) {
    top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
    return false;
  }

  return true;
}

function Url_isUrlValid(url) {
  if(url.length == 0)
    return true;

  for(var i = 0; i < url.length; url++) {
    var ch = url.charAt(i);

    // for more info, read URL BNF at
    // http://www.w3.org/Addressing/URL/5_URI_BNF.html
    if(!top.code.string_isLowercaseAlpha(ch) &&
	!top.code.string_isUppercaseAlpha(ch) &&
	!top.code.string_isNumeric(ch) &&
	ch.indexOf("#?/+$-_@.&!*\"'(),%") == -1)
      return false;
  }

  return true;
}