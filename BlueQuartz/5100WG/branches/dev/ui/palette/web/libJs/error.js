// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: error.js 201 2003-07-18 19:11:07Z will $

function error_invalidElement(element, errMsg) {
  if(element.focus != null)
    element.focus();

  if(element.select != null)
    element.select();

  while(errMsg.indexOf("&#092;")!=-1){
    errMsg=top.code.string_substitute(errMsg, "&#092;", "\\");
  }

  top.code.info_show(top.code.string_htmlEscape(errMsg), "error");
}
