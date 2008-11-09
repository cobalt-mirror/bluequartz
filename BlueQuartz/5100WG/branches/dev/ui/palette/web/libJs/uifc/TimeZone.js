
// Author: Kenneth C.K. Leung
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: TimeZone.js 201 2003-07-18 19:11:07Z will $


function TimeZone_setRegion(regionElement) {
     regionElement.element.regn = 0;
     regionElement.options.length = regionElement.element.timezones.length;
     regionElement.selectedIndex = 0;
     for (i = 0; i < regionElement.element.timezones.length; i++) {
        regionElement.options[i].text = regionElement.element.timezones[i][1];
        regionElement.options[i].value = regionElement.element.timezones[i][0];
        if (regionElement.element.timezones[i][0] == regionElement.value) {
           regionElement.selectedIndex = i;
           regionElement.element.regn = i;
        }
     }
}
function TimeZone_setCountry(countryElement) {
     var ri = countryElement.element.regn;
     countryElement.element.cntry = 0;
     countryElement.options.length = countryElement.element.timezones[ri][2].length;
     countryElement.selectedIndex = 0;
     for (i = 0; i < countryElement.element.timezones[ri][2].length; i++) {
        countryElement.options[i].text = countryElement.element.timezones[ri][2][i][1];
        countryElement.options[i].value = countryElement.element.timezones[ri][2][i][0];
        if (countryElement.element.timezones[ri][2][i][0] == countryElement.value) {
           countryElement.selectedIndex = i;
           countryElement.element.cntry = i;
        }
     }
}
function TimeZone_setZone(zoneElement) {
     var ri = zoneElement.element.regn;
     var ci = zoneElement.element.cntry;
     zoneElement.element.tz = 0;
     zoneElement.options.length = zoneElement.element.timezones[ri][2][ci][2].length;
     zoneElement.selectedIndex = 0;
     for (i = 0; i < zoneElement.element.timezones[ri][2][ci][2].length; i++) {
        zoneElement.options[i].text = zoneElement.element.timezones[ri][2][ci][2][i][1];
        zoneElement.options[i].value = zoneElement.element.timezones[ri][2][ci][2][i][0];
        if (zoneElement.element.timezones[ri][2][ci][2][i][0] == zoneElement.value) {
           zoneElement.selectedIndex = i;
           zoneElement.element.tz = i;
        }
     }
}
function TimeZone_newRegion(element) {
   element.regn = 0;
   for (i = 0; i < element.timezones.length; i++) {
       if (element.regionElement.selectedIndex == i) {
          // set the country information up
          element.regn = i;
            TimeZone_setCountry(element.countryElement);
            TimeZone_setZone(element.zoneElement);
          break;
       }
    }
}
function TimeZone_newCountry(element) {
   // figure out the country index
   element.cntry = 0;
   for (i = 0; i < element.timezones[element.regn][2].length; i++) {
       if (element.countryElement.selectedIndex == i) {
          // set the timezone up
         element.cntry = i;
          TimeZone_setZone(element.zoneElement);
          break;
       }
   }
}
function TimeZone_init(element, initValue)
{
  for(i=0;i<element.timezones.length;i++)
   for(j=0;j<element.timezones[i][2].length;j++) 
    for(k=0;k<element.timezones[i][2][j][2].length;k++)
     if(element.timezones[i][2][j][2][k][0] == initValue){
      element.regionElement.options.length = element.timezones.length;
      element.countryElement.options.length = element.timezones[i][2].length;
      element.zoneElement.options.length = element.timezones[i][2][j][2].length;
      for (l = 0; l < element.timezones.length; l++) {
        element.regionElement.options[l].text = element.timezones[l][1];
        element.regionElement.options[l].value = element.timezones[l][0];
      }
      for (l = 0; l < element.timezones[i][2].length; l++) {
        element.countryElement.options[l].text = element.timezones[i][2][l][1];
        element.countryElement.options[l].value = element.timezones[i][2][l][0];
      }
      for (l = 0; l < element.timezones[i][2][j][2].length; l++) {
        element.zoneElement.options[l].text = element.timezones[i][2][j][2][l][1];
        element.zoneElement.options[l].value = element.timezones[i][2][j][2][l][0];
      }
      element.regionElement.selectedIndex = i;
      element.regn = i;
      element.countryElement.selectedIndex = j;
      element.cntry = j;
      element.zoneElement.selectedIndex = k;
      element.tz = k;
     }
          
        
}       
        

