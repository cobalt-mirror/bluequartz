// Author: Joshua Uziel
// Copyright 2001, Sun Microsystems, Inc.  All rights reserved.
// $Id: MacAddressList.js,v 1.1 2001/09/24 04:09:22 uzi Exp $

//
// public functions
//

function MacAddressList_changeHandler(element) {
  var textArea = element.textArea;

  var entries = top.code.textArea_getEntries(textArea);
  for(var i = 0; i < entries.length; i++)
    if(!top.code.MacAddress_isMacAddressValid(entries[i])) {
      top.code.error_invalidElement(textArea, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", entries[i]));
      return false;
    }

  return true;
}
