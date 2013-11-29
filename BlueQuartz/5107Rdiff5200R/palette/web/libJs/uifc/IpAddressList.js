// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: IpAddressList.js 3 2003-07-17 15:19:15Z will $

//
// public functions
//

function IpAddressList_changeHandler(element) {
  var textArea = element.textArea;

  var entries = top.code.textArea_getEntries(textArea);
  for(var i = 0; i < entries.length; i++)
    if(!top.code.IpAddress_isIpAddressValid(entries[i])) {
      top.code.error_invalidElement(textArea, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", entries[i]));
      return false;
    }

  return true;
}