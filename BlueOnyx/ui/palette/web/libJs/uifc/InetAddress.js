// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: InetAddress.js 831 2006-07-20 13:50:04Z shibuya $

function InetAddress_changeHandler(element) {
  if(!InetAddress_isInetAddressValid(element.value)) {
    top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
    return false;
  }

  return true;
}

function InetAddress_isInetAddressValid(netAddr) {
  if(netAddr.length == 0)
    return true;

  var ip = new Object();
  var netmask = new Object();
  var ptr;

  ptr = netAddr.indexOf('/', 0);
  if(ptr > 0) {
    ip = netAddr.substring(0,ptr);
    var retIp = IpAddress_isIpAddressValid(ip);
    netmask = netAddr.substring(ptr+1);
    var retNetmask = Integer_isIntegerValid(netmask, 0, 32)
    if(retIp == false || retNetmask == false || netmask.length < 1)
      return false;
  } else {
    return false;
  }
  return true;
}
