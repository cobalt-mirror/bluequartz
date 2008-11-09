// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: error.js 259 2004-01-03 06:28:40Z shibuya $

function error_invalidElement(element, errMsg) {
  if(element.focus != null)
    element.focus();

  if(element.select != null)
    element.select();

  top.code.info_show(top.code.string_htmlEscape(errMsg), "error");
}
