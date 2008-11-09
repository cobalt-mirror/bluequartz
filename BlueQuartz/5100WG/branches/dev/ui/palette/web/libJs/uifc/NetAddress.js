// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: NetAddress.js 201 2003-07-18 19:11:07Z will $

function NetAddress_changeHandler(element) {
  if(!NetAddress_isNetAddressValid(element.value)) {
    top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
    return false;
  }

  return true;
}

function NetAddress_isNetAddressValid(netAddr) {
  for(var i = 0; i < netAddr.length; i++) {
    var ch = netAddr.charAt(i);
    if(!top.code.string_isNumeric(ch) && ch != '.')
      return DomainName_isDomainNameValid(netAddr);
  }

  return IpAddress_isIpAddressValid(netAddr);
}
