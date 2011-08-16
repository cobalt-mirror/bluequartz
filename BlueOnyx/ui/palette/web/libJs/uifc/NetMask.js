// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: NetMask.js 706 2006-03-09 08:31:25Z shibuya $

function NetMask_changeHandler(element) {
  element.value = top.code.string_trim(element.value);
  if(!NetMask_isNetMaskValid(element.value)) {
    top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
    return false;
  }

  return true;
}

function NetMask_isNetMaskValid(netMask) {
  if(netMask.length == 0)
    return true;

  var dotCount = 0;
  var i;

  // only digits and '.' allowed
  for(i = 0; i < netMask.length; i++) {
    var ch = netMask.charAt(i);
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
    ptr = netMask.indexOf('.', lastPtr);
    numbers[i] = netMask.substring(lastPtr, ptr);
    lastPtr = ptr+1;
  }
  numbers[3] = netMask.substring(lastPtr);

  for(i = 0; i < 4; i++) {
    if(numbers[i].length == 0)
      return false;
    if(numbers[i] > 255)
      return false;
  }

  return true;
}
