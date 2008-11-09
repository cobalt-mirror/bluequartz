
// Author: Kenneth C.K. Leung
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: SnmpCommunity.js 201 2003-07-18 19:11:07Z will $

function SnmpCommunity_changeHandler(element) {
  if(!SnmpCommunity_isSnmpCommunityValid(element.value)) {
    top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
    return false;
  }

  return true;
}

function SnmpCommunity_isSnmpCommunityValid(SnmpCommunity) {
  if(SnmpCommunity.length == 0)
    return true;

  for(var i = 0; i < SnmpCommunity.length; i++)
    if(SnmpCommunity.charAt(i) == " ")
      return false;

  return true;
}











