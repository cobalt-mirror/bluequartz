/*
 * Author: Kevin K.M. Chiu
 * Copyright 2000, Cobalt Networks.  All rights reserved.
 * $Id: test3.c 201 2003-07-18 19:11:07Z will $
 *
 * performance and memory leak test
 */

#include <gettext.h>
#include <locale.h>
#include <stdio.h>
#include <stdlib.h>
#include <time.h>
#include "i18n.h"

int main(int argc, char **argv) {
  // test case
  char *domain = "palette";
  char *locale = "en";
  char *msgId = "save";
  char *tag = "[[palette.save]]";
  int loops = 500000;
  int runs = 3;

  time_t timeStart, timeStop;
  char *string;
  int i, j;
  i18n_handle *i18n;
  char * (*testFunc)();

  // check parameters
  if(argc < 2) {
    printf("Usage: %s [get|interpolate|get_html|get_js|interpolate_html|interpolate_js|gettext]\n", argv[0]);
    return 1;
  }

  if(strcmp(argv[1], "get") == 0) {
    testFunc = &i18n_get;
  } else if(strcmp(argv[1], "interpolate") == 0) {
    testFunc = &i18n_interpolate;
  } else if(strcmp(argv[1], "get_html") == 0) {
    testFunc = &i18n_get_html;
  } else if(strcmp(argv[1], "get_js") == 0) {
    testFunc = &i18n_get_js;
  } else if(strcmp(argv[1], "interpolate_html") == 0) {
    testFunc = &i18n_interpolate_html;
  } else if(strcmp(argv[1], "interpolate_js") == 0) {
    testFunc = &i18n_interpolate_js;
  } else {
    // bad
    testFunc = NULL;
  }

  if(testFunc != NULL) {
    for(j = 0; j < runs; j++) {
      i18n = i18n_new(domain, locale);

      // start timer
      time(&timeStart);

      if(testFunc == i18n_get || testFunc == i18n_get_html || testFunc == i18n_get_js)
	for(i = 0; i < loops; i++)
	  string = (*testFunc)(i18n, msgId, NULL, NULL);
      else
	for(i = 0; i < loops; i++)
	  string = (*testFunc)(i18n, tag, NULL);

      // stop timer
      time(&timeStop);

      // print result
      printf("Using %s to lookup \"%s\" returns %s: %i loops in %i seconds\n", argv[1], msgId, string, loops, (int)timeStop-(int)timeStart);

      i18n_destroy (i18n);
    }
  }

  //
  // gettext test
  //
  if(strcmp(argv[1], "gettext") == 0) {
    for(j = 0; j < runs; j++) {
      // set locale
      setenv("LANG", locale, 1);

      // start timer
      time(&timeStart);

      for(i = 0; i < loops; i++)
	string = (char *)dgettext(domain, msgId);

      // stop timer
      time(&timeStop);

      // print result
      printf("dgettext(\"%s\", \"%s\") returns %s: %i loops in %i seconds\n", msgId, domain, string, loops, (int)timeStop-(int)timeStart);
    }
  }

  return 0;
}
/* Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met: 
 * 
 * -Redistribution of source code must retain the above copyright notice, this
 * list of conditions and the following disclaimer.
 * 
 * -Redistribution in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution. 
 *
 * Neither the name of Sun Microsystems, Inc. or the names of contributors may
 * be used to endorse or promote products derived from this software without 
 * specific prior written permission.

 * This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
 * 
 * You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
 */
