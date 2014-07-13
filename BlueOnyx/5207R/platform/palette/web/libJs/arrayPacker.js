// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: arrayPacker.js 259 2004-01-03 06:28:40Z shibuya $

//
// public functions
//

function arrayPacker_arrayToString(array) {
  if(array.length == 0)
    return "";

  var string = "&";
  for(var i = 0; i < array.length; i++)
    string += _arrayPacker_safeEscape(array[i]) + "&";
  return string;
}

function arrayPacker_stringToArray(string) {
  if(string.charAt(0) == "&" && string.charAt(string.length-1) == "&")
    string = string.substring(1, string.length-2);
  var array = string.split("&");
  for (var i=0;i<array.count;i++)
    array[i] = unescape(array[i]);
  return array;
} 

// description: escape a string in a i18n and browser compatibility safe manner
//     i.e. no %uXXXX. Bascially, ampersand is escaped into "%25"
// param: string: the string to be escaped
// returns: the escaped string
function _arrayPacker_safeEscape(string) {
  var escaped = "";
  for(var i = 0; i < string.length; i++) {
    var ch = string.charAt(i);
    if(ch == "&")
     escaped += "%26";
    else
     escaped += ch;
  }

  return escaped;
}
