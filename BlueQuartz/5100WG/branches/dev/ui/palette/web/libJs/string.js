// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: string.js 201 2003-07-18 19:11:07Z will $

// description: escape \ into \\, " into \" and ' into \'
// string: a string to escape
// returns: escaped string
function string_backslashEscape(string) {
  var result = "";

  for(var i = 0; i < string.length; i++) {
    var ch = string.charAt(i);
    if(ch == "\\")
      ch = "\\\\";
    else if(ch == '"')
      ch = '\\"';
    else if(ch == "'")
      ch = "\\'";
    else if(ch == "\n")
      ch = "\\n";

    result += ch;
  }

  return result;
}

// description: escape & into &amp;, < into &lt;, > into &gt; and " into &quot;
// string: a string to escape
// returns: escaped string
function string_htmlEscape(string) {
  var result = "";

  for(var i = 0; i < string.length; i++) {
    var ch = string.charAt(i);
    if(ch == "&")
      ch = "&amp;";
    else if(ch == "<")
      ch = "&lt;";
    else if(ch == ">")
      ch = "&gt;";
    else if(ch == '"')
      ch = "&quot;";

    result += ch;
  }

  return result;
}

function string_isLowercaseAlpha(string) {
  var alpha = "abcdefghijklmnopqrstuvwxyz";

  for(var i = 0; i < string.length; i++)
    if(alpha.indexOf(string.charAt(i)) == -1)
      return false;

  return true;
}

function string_isAlpha(string) {
  var foo = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
  for (var i = 0; i < string.length; i++)
    if(foo.indexOf(string.charAt(i)) == -1)
      return false;
  return true;
}

function string_isUppercaseAlpha(string) {
  var alpha = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

  for(var i = 0; i < string.length; i++)
    if(alpha.indexOf(string.charAt(i)) == -1)
      return false;

  return true;
}

function string_isNumeric(string) {
  var numeric = "0123456789";

  for(var i = 0; i < string.length; i++)
    if(numeric.indexOf(string.charAt(i)) == -1)
      return false;

  return true;
}

function string_trim(string) {
  while(string.length > 0 && string.charAt(0) == ' ')
    string = string.substring(1);

  while(string.length > 0 && string.charAt(string.length-1) == ' ')
    string = string.substring(0, string.length-1);

  return string;
}

// substiute keys in the string with values
// param: string: the original string
// param: ...: pairs of keys to values
// returns: a string
function string_substitute(string) {
  // for each pair in ...
  for(var i = 1; i < arguments.length; i+=2) {
    var key = arguments[i];
    var value = arguments[i+1];

    var index = string.indexOf(key);

    // key not found in string?
    if(index == -1)
      continue;

    // substitute
    string = string.substring(0, index)+value+string.substring(index+key.length, string.length);
  }

  return string;
}
