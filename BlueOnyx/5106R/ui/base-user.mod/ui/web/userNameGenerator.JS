// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: userNameGenerator.JS 3 2003-07-17 15:19:15Z will $

//
// private variables
//

var _userNameGenerator_MAX_NAME_LEN = 12;

//
// public functions
//

function userNameGenerator_generate(fullName, mode) {
  if(fullName.length == 0)
    return "";

  var userName = "";
  if(mode == "first")
    userName = _userNameGenerator_getFirstName(fullName);
  else if(mode == "last")
    userName = _userNameGenerator_getLastName(fullName);
  else if(mode == "firstInitLast")
    userName = _userNameGenerator_getFirstInitLastName(fullName);
  else if(mode == "firstLastInit")
    userName = _userNameGenerator_getFirstNameLastInit(fullName);

  var u = userName.toLowerCase();
  if(top.code.UserName_isUserNameValid(u))
    return u.substring(0, _userNameGenerator_MAX_NAME_LEN);
  else return "";
}

//
// private functions
//

function _userNameGenerator_getFirstName(fullName) {
  fullName = top.code.string_trim(fullName);

  if(fullName == "")
    return "";

  var commaIndex = fullName.indexOf(",");
  var spaceIndex = fullName.indexOf(" ");

  // comma at the end?
  if(commaIndex >= 0 && fullName.length == commaIndex+1)
    return "";

  var firstName;

  // no comma or space
  if(commaIndex < 0 && spaceIndex < 0)
    firstName = fullName;
  // space only
  else if(commaIndex < 0 && spaceIndex >= 0)
    firstName = fullName.substring(0, spaceIndex);
  // comma only
  else if(commaIndex >= 0 && spaceIndex < 0)
      firstName = fullName.substring(commaIndex+1);
  // comma and space
  else {
    var firstMiddleName = fullName.substring(commaIndex+1);
    firstMiddleName = top.code.string_trim(firstMiddleName);
    var spaceIndex = firstMiddleName.indexOf(" ");
    if(spaceIndex < 0)
      firstName = firstMiddleName;
    else
      firstName = firstMiddleName.substring(0, spaceIndex);
  }

  return top.code.string_trim(firstName);
}

function _userNameGenerator_getLastName(fullName) {
  fullName = top.code.string_trim(fullName);

  if(fullName == "")
    return "";

  var commaIndex = fullName.indexOf(",");
  var spaceIndex = fullName.lastIndexOf(" ");

  // comma at the start?
  if(commaIndex == 0)
    return "";

  var lastName;

  // no comma or space
  if(commaIndex < 0 && spaceIndex < 0)
    lastName = fullName;
  // space only
  else if(commaIndex < 0 && spaceIndex >= 0)
    lastName = fullName.substring(spaceIndex+1);
  // comma only
  else if(commaIndex >= 0 && spaceIndex < 0)
    lastName = fullName.substring(0, commaIndex);
  // comma and space
  else
    lastName = fullName.substring(0, commaIndex);

  return top.code.string_trim(lastName);
}

function _userNameGenerator_getFirstInitLastName(fullName) {
  fullName = top.code.string_trim(fullName);

  if(fullName == "")
    return "";

  // one word?
  var commaIndex = fullName.indexOf(",");
  var spaceIndex = fullName.lastIndexOf(" ");
  if(commaIndex < 0 && spaceIndex < 0)
    return fullName;

  var firstName = _userNameGenerator_getFirstName(fullName);
  var lastName = _userNameGenerator_getLastName(fullName);

  if(firstName == "")
    return lastName;

  var firstInit = firstName.charAt(0);
  if(!top.code.string_isLowercaseAlpha(firstInit) && !top.code.string_isUppercaseAlpha(firstInit))
    firstInit = "";

  return firstInit+lastName;
}

function _userNameGenerator_getFirstNameLastInit(fullName) {
  fullName = top.code.string_trim(fullName);

  if(fullName == "")
    return "";

  // one word?
  var commaIndex = fullName.indexOf(",");
  var spaceIndex = fullName.lastIndexOf(" ");
  if(commaIndex < 0 && spaceIndex < 0)
    return fullName;

  var firstName = _userNameGenerator_getFirstName(fullName);
  var lastName = _userNameGenerator_getLastName(fullName);

  if(lastName == "")
    return firstName;

  var lastInit = lastName.charAt(0);
  if(!top.code.string_isLowercaseAlpha(lastInit) && !top.code.string_isUppercaseAlpha(lastInit))
    lastInit = "";

  return firstName+lastInit;
}