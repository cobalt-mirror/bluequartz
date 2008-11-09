// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: EmailAddress.js 3 2003-07-17 15:19:15Z will $

function EmailAddress_changeHandler(element) {
  if(!EmailAddress_isEmailAddressValid(element.value)) {
    top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
    return false;
  }
  return true;
}

// used when email address must be fully-qualified (user@domain.com)
function EmailAddress_changeHandlerRemote(element) {
  if(!EmailAddress_isEmailAddressValidRemote(element.value)) {
    top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
    return false;
  }
  return true;
}

// confirms an email address is valid, but can be local ('admin') or
// remote (user@domain.com)
function EmailAddress_isEmailAddressValid(emailAddr) {
  if(emailAddr.length == 0)
    return true;

  var name = emailAddr;
  var domain = "";
  var ampersandIndex = emailAddr.indexOf("@");
  if(ampersandIndex != -1) {
    name = emailAddr.substring(0, ampersandIndex);
    domain = emailAddr.substring(ampersandIndex+1, emailAddr.length);

    if(name == "" || domain == "")
      return false;
  }

  if(!top.code.DomainName_isDomainNameValid(domain))
    return false;

  for(var i = 0; i < name.length; i++) {
    var ch = name.charAt(i);

    // first character cannot be a dot for email address 
    if(i == 0 && ch == '.')
	return false;

    if(!top.code.string_isAlpha(ch) && !top.code.string_isNumeric(ch)
      && ch != '-' && ch != '_' && ch != '.')
      return false;
  }
  return true;
}

// makes sure a email address is fully qualified.  (ie user@domain.com)
// code copied from EmailAddress_isEmailAddressValied
function EmailAddress_isEmailAddressValidRemote(emailAddr) {
  if(emailAddr.length == 0)
    return true;

  var name = emailAddr;
  var domain = "";
  var ampersandIndex = emailAddr.indexOf("@");
  if(ampersandIndex != -1) {
    name = emailAddr.substring(0, ampersandIndex);
    domain = emailAddr.substring(ampersandIndex+1, emailAddr.length);
  }

  if(name == "" || domain == "")
    return false;

  if(!top.code.DomainName_isDomainNameValid(domain))
    return false;

  for(var i = 0; i < name.length; i++) {
    var ch = name.charAt(i);

    // first character cannot be a dot for email address 
    if(i == 0 && ch == '.')
	return false;

    if(!top.code.string_isAlpha(ch) && !top.code.string_isNumeric(ch)
      && ch != '-' && ch != '_' && ch != '.')
      return false;
  }

  return true;
}


// description: extract the email address part from the full format (e.g. "John Smith <jsmith@smith.org>")
// param: full: address in full format string
// returns: the email address string or "" if email address cannot be extracted
function EmailAddress_getAddressFromFull(full) {
  // remove spaces first
  full = top.code.string_trim(full);

  firstBracket = full.indexOf("<");

  // no brackets?
  if(firstBracket == -1)
    return "";

  // must be bracket at the end
  if(full.charAt(full.length-1) != ">")
    return "";

  return full.substring(firstBracket+1, full.length-2);
}
