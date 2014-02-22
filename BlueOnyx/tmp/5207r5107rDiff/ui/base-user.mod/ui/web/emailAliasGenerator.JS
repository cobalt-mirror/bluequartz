// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: emailAliasGenerator.JS 259 2004-01-03 06:28:40Z shibuya $

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

      if (ch == 'Á' || ch == 'á' || ch == 'À' || ch == 'à'
       || ch == 'Â' || ch == 'â' || ch == 'Ã' || ch == 'ã') {
        result += 'a';
      } else if (ch == 'Å' || ch == 'å') {
        result += 'aa';
      } else if (ch == 'Ä' || ch == 'ä' || ch =='Æ' || ch =='æ') {
        result += 'ae';
      } else if (ch == 'Ç' || ch == 'ç') {
        result += 'c';
      } else if (ch == 'Ð' || ch == 'ð') {
        result += 'dh';
      } else if (ch == 'É' || ch == 'é' || ch == 'È' || ch == 'è'
              || ch == 'Ê' || ch == 'ê' || ch == 'Ë' || ch == 'ë') {
        result += 'e';
      } else if (ch == 'Í' || ch == 'í' || ch == 'Ì' || ch == 'ì'
              || ch == 'Î' || ch == 'î' || ch == 'Ï' || ch == 'ï')  {
        result += 'i';
      // } else if (ch == 'ij' || ch == 'IJ') {
      //   result += 'ij';
      } else if (ch == 'Ñ' || ch == 'ñ') {
        result += 'nh';
      } else if (ch == 'Ó' || ch == 'ó' || ch == 'Ò' || ch == 'ò'
              || ch == 'Ô' || ch == 'ô' || ch == 'Õ' || ch == 'õ') {
        result += 'o';
      } else if (ch == 'Ö' || ch == 'ö' || ch == 'Ø' || ch == 'ø') {
        // || ch == '?' || ch == '?' (dipthong oe, OE)
        result += 'oe';
      } else if (ch == 'ß') {
        result += 'ss';
      } else if (ch == 'Þ' || ch == 'þ') {
        result += 'th';
      } else if (ch == 'Ú' || ch == 'ú' || ch == 'Ù' || ch == 'ù'
              || ch == 'Û' || ch == 'û') {
        result += 'u';
      } else if (ch == 'Ü' || ch == 'ü') {
        result += 'ue';
      } else if (ch == 'Ý' || ch == 'ý' || ch == 'Y' || ch == 'ÿ') {
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
