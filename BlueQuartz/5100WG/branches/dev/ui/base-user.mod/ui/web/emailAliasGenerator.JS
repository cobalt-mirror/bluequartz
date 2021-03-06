// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: emailAliasGenerator.JS 201 2003-07-18 19:11:07Z will $

function emailAliasGenerator_generate(fullName) {
  if(fullName.length == 0)
    return "";

  fullName = fullName.toLowerCase();

  var result = "";
  var breaked = false;

  for(var i = 0; i < fullName.length; i++) {
    var ch = fullName.charAt(i);
    if(!top.code.string_isLowercaseAlpha(ch) && !top.code.string_isUppercaseAlpha(ch) && !top.code.string_isNumeric(ch)
      && ch != '.' && ch != '-' && ch != '_') {

      if (ch == '�' || ch == '�' || ch == '�' || ch == '�'
       || ch == '�' || ch == '�' || ch == '�' || ch == '�') {
        result += 'a';
      } else if (ch == '�' || ch == '�') {
        result += 'aa';
      } else if (ch == '�' || ch == '�' || ch =='�' || ch =='�') {
        result += 'ae';
      } else if (ch == '�' || ch == '�') {
        result += 'c';
      } else if (ch == '�' || ch == '�') {
        result += 'dh';
      } else if (ch == '�' || ch == '�' || ch == '�' || ch == '�'
              || ch == '�' || ch == '�' || ch == '�' || ch == '�') {
        result += 'e';
      } else if (ch == '�' || ch == '�' || ch == '�' || ch == '�'
              || ch == '�' || ch == '�' || ch == '�' || ch == '�')  {
        result += 'i';
      // } else if (ch == 'ij' || ch == 'IJ') {
      //   result += 'ij';
      } else if (ch == '�' || ch == '�') {
        result += 'nh';
      } else if (ch == '�' || ch == '�' || ch == '�' || ch == '�'
              || ch == '�' || ch == '�' || ch == '�' || ch == '�') {
        result += 'o';
      } else if (ch == '�' || ch == '�' || ch == '�' || ch == '�') {
        // || ch == '?' || ch == '?' (dipthong oe, OE)
        result += 'oe';
      } else if (ch == '�') {
        result += 'ss';
      } else if (ch == '�' || ch == '�') {
        result += 'th';
      } else if (ch == '�' || ch == '�' || ch == '�' || ch == '�'
              || ch == '�' || ch == '�') {
        result += 'u';
      } else if (ch == '�' || ch == '�') {
        result += 'ue';
      } else if (ch == '�' || ch == '�' || ch == 'Y' || ch == '�') {
        result += 'y';
      } else if(!breaked) {
	result += '.';
	breaked = true;
      }
    }
    else {
      result += ch;
      breaked = false;
    }
  }

  if(breaked)
    result = result.substring(0, result.length-1);

  while (result.indexOf('.') == 0)
	result = result.substring(1, result.length);

  return result;
}
