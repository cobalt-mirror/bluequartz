// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: IpAddress.js 3 2003-07-17 15:19:15Z will $

function IpAddress_changeHandler(element) {
  element.value = top.code.string_trim(element.value);
  if(!IpAddress_isIpAddressValid(element.value)) {
    top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
    return false;
  }

  return true;
}

function IpAddress_isIpAddressValid(ipAddr) {
  if(ipAddr.length == 0)
    return true;

  var dotCount = 0;
  var i;

  // only digits and '.' allowed
  for(i = 0; i < ipAddr.length; i++) {
    var ch = ipAddr.charAt(i);
    if(!top.code.string_isNumeric(ch) && ch != '.')
      return false;

    if(ch == '.')
      dotCount++;
  }

  // only 3 dots
  if(dotCount != 3)
    return false;

  var numbers = new Object();
  var lastPtr = 0;
  var ptr;
  for(i = 0; i < 3; i++) {
    ptr = ipAddr.indexOf('.', lastPtr);
    numbers[i] = ipAddr.substring(lastPtr, ptr);
    lastPtr = ptr+1;
  }
  numbers[3] = ipAddr.substring(lastPtr);

  for(i = 0; i < 4; i++) {
    if(numbers[i].length == 0)
      return false;
    if(numbers[i] > 255)
      return false;
  }

  // no 0.0.0.0. or 255.255.255.255
//  if(numbers[0] == 0 && numbers[1] == 0 && numbers[2] == 0 && numbers[3] == 0)
//    return false;
  if(numbers[0] == 255 && numbers[1] == 255 && numbers[2] == 255 && numbers[3] == 255)
    return false;

  return true;
}
