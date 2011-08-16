// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: DomainNameList.js 3 2003-07-17 15:19:15Z will $

//
// public functions
//

function DomainNameList_changeHandler(element) {
  var textArea = element.textArea;

  var entries = top.code.textArea_getEntries(textArea);
  for(var i = 0; i < entries.length; i++)
    if(!top.code.DomainName_isDomainNameValid(entries[i])) {
      top.code.error_invalidElement(textArea, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", entries[i]));
      return false;
    }

  return true;
}
