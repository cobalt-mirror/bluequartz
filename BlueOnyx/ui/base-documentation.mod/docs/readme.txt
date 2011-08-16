$Id: readme.txt,v 1.1 2001/08/22 01:04:49 ebraswell Exp $

About viewManual.php

Usage:

1.	Legacy: no parameters
	
2.  Search directory: $docDir - no url(s)
    - $searchDir is set and is not false
	- $docDir is searched for files with the extension $ext.
    - $url should not be set.
    - $ext may be set. If it isn't, the default ".pdf" will be used.
	- Both $noLocale and $name will be ignored and only the files names will 
      be listed.
	  
3.  Single manual: $url
    - $url is set and is a scalar.
    - If $noLocale is set, $url will be treated literally. If $noLocale is not
      set or is false, $url will be constructed as $url . "-" . <locale> . $ext
    - If $name is set it must be a scalar. If $noLocale is not set or false, 
      $name will be treated as the base name.
    - If $noi18n is set $name will be treated as a literal string.
	 
4.  List of manuals: $url is set and is an array.
    - $url is required and must be an array.
    - If $name is specified it may be a scalar, to be treated as the base
      for an i18n tag (in this case $noLocale and $noi18n must not be set or must
      be false); or $name can be an array, in which case it will be treated as
      a literal string or i18n tag, as appropriate, for each $url.
      If $name is _not_ an array, this can lead to duplicate display names in 
      the event that the files in $url have the same locales.
      

      
Examples:


  viewManual.php?docDir=/base/documentation/manuals/&url=foo&name=base-mymodule.foo      
  
        Get a list of all manuals in the directory /base/documentation/manuals/ 
        (from the web root) for available locales having the base name "foo" and 
        display them using the i18n tag(s) "base-mymodule.foo<locale>".
   
  viewManual.php?noLocale=1
        Get the manual /base/documentation/manual.pdf with the default
        description of pdfDesc
        
  viewManual.php?noi18n=1
        Get the a list of manuals in /base/documentation with the extension ".pdf"
        and display the file names (eg manual-en.pdf) instead of the i18n name.

  viewManual.php?docDir=/testManual/&amp;url=doc&amp;ext=.txt&amp;name=base-user.mydoc  
        Get a list of the manuals in /testManual with the base url "doc" and the 
        extension ".txt" and display them with the i18n tag base-user.mydoc<locale>
        Eg Urls will be /testManual/doc-en.txt

  viewManual.php?docDir=/testManual/&amp;searchDir=1&amp;ext=
        List any files found in the directory /testManual. Specify ext to filter
        on an extension.        

  viewManual.php?docDir=/base/documentation/manuals/&amp;&amp;noLocale=1&amp;url[]=foo&amp;url[]=bar&amp;name[]=foo&amp;name[]=bar
        A list of manuals in the array $url, displayed with the names in the 
        array $name. 


