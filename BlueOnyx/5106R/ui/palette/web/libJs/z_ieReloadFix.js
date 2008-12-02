// this is a patch that fixes the problem that IE 4.5/5 on Mac loses every document Javascript
// wrote to afer reload

var name = navigator.appName;
var version = navigator.appVersion;

// top.siteMap is null the first time because top.init() is not called
// after reload, top.siteMap should be an object
if(name.indexOf("Microsoft") != -1 && version.indexOf("Mac") != -1 &&
  (version.indexOf("4.") != -1 || version.indexOf("5.") != -1) &&
  top.siteMap != null)
  top.init();
