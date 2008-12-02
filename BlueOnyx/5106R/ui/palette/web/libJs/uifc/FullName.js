
// Author: Kenneth C.K. Leung
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: FullName.js 3 2003-07-17 15:19:15Z will $

function FullName_changeHandler(element) {
  if(!FullName_isFullNameValid(element.value)) {
    top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
    return false;
  }

//  element.value = FullName_makeSurname(element.value);
  return true;
}

function FullName_isFullNameValid(FullName) {
  if(FullName.length == 0)
    return true;

  for(var i = 0; i < FullName.length; i++)
    if(FullName.charAt(i) == ":")
      return false;

  return true;
}

function FullName_makeSurname(FullName){
 var Lastname;
 var Firstname;
 for(i=0;i<FullName.length;i++){
  if(FullName.charAt(i) == ","){
   if(FullName.charAt(i + 1) == " ") return(FullName);
   else {
     Lastname  = FullName.substr(0,i);   
     Firstname = FullName.substr(i+1,(FullName.length - i - 1));
     Lastname = Lastname.concat(", ");
     Lastname = Lastname.concat(Firstname);
     return(Lastname);
   }
  }
  if(FullName.charAt(i) == " "){
   for(j=(FullName.length - 1); j >= 0; j--){
    if(FullName.charAt(j) == " "){
     Firstname = FullName.substr(0,j);
     Lastname  = FullName.substr(j+1,(FullName.length - j - 1));
     Lastname = Lastname.concat(", ");
     Lastname = Lastname.concat(Firstname);
     return(Lastname);}
   }
  } 
 }  
}  









