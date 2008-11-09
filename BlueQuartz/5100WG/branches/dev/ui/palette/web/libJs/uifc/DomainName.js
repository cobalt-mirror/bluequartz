// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: DomainName.js 201 2003-07-18 19:11:07Z will $

function DomainName_changeHandler(element) {
  if(!DomainName_isDomainNameValid(element.value)) {
    top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
    return false;
  }

  return true;
}

function DomainName_isDomainNameValid(domainName) {
  if(domainName.length == 0)
    return true;

  if(domainName.charAt(domainName.length-1) == '.')
    return false;

  var i;

  var letterExist = false;
  for(i = 0; i < domainName.length; i++) {
    var ch = domainName.charAt(i);
    if(top.code.string_isAlpha(ch)) {
      letterExist = true;
      break;
    }
  }
  if(!letterExist)
    return false;

  var ptr = 0;
  while(ptr < domainName.length) {
    var dotPtr = domainName.indexOf('.', ptr);
    if(dotPtr == -1)
      dotPtr = domainName.length;

    // JPNIC worships 1 character subdomain
    if(dotPtr == ptr)
      return false;
//    if(dotPtr == ptr+1 || dotPtr == ptr)
//      return false;

    var subdomain = domainName.substring(ptr, dotPtr);

    var ch = subdomain.charAt(0);
    if(!top.code.string_isAlpha(ch) && !top.code.string_isNumeric(ch))
      return false;

    for(i = 0; i < subdomain.length; i++) {
      var ch = subdomain.charAt(i);
      if(!top.code.string_isAlpha(ch) && !top.code.string_isNumeric(ch) && ch != "-")
	return false;
    }

    ptr=dotPtr+1;
  }

  return true;
}

function DomainName_isBlanketDomainValid(domainName) {
  if(domainName.length == 0)
    return true;

  var i;

  var letterExist = false;
  for(i = 0; i < domainName.length; i++) {
    var ch = domainName.charAt(i);
    if(ch == '.') {
      continue;
    }
    if(top.code.string_isAlpha(ch)) {
      letterExist = true;
      break;
    }
  }

  // '.' can't be last character
  if (domainName.charAt(domainName.length-1) == ".")
    return false;

  if(!letterExist)
    return false;

  var ptr = 0;
  while(ptr < domainName.length) {
    var dotPtr = domainName.indexOf('.', ptr);
    if(dotPtr == -1)
      dotPtr = domainName.length;

    // JPNIC worships 1 character subdomain
//    if(dotPtr == ptr)
//      return false;
//    if(dotPtr == ptr+1 || dotPtr == ptr)
//      return false;

    var subdomain = domainName.substring(ptr, dotPtr);

    var ch = subdomain.charAt(0);
    if(!top.code.string_isAlpha(ch) && !top.code.string_isNumeric(ch))
      return false;

    for(i = 0; i < subdomain.length; i++) {
      var ch = subdomain.charAt(i);
      if(!top.code.string_isAlpha(ch) && !top.code.string_isNumeric(ch) && ch != "-")
	return false;
    }

    ptr=dotPtr+1;
  }

  return true;
}
