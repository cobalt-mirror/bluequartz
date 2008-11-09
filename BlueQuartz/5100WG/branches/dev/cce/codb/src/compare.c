/* $Id: compare.c 3 2003-07-17 15:19:15Z will $
 *
 * various compare functions for use when sorting
 *
 */

#include "compares.h"
#include <ctype.h>

gint 
numeric_compare (char **app, char **bpp, int mode)
{
  gint cmp = 0;
  
  while (1) {
    int isdigit_a, isdigit_b;
    char a = **app;
    char b = **bpp;
    isdigit_a = isdigit(a);
    isdigit_b = isdigit(b);
    
    if (isdigit_a) {
      if (isdigit_b) {
      	// isdigit_a and isdigit_b
      	if (cmp == 0) { 
	  if (a < b) cmp = -1;
	  if (a > b) cmp = +1;
	}
	(*app)++;
	(*bpp)++;
      } else {
      	// isdigit_a and !isdigit_b
	if (mode) {
	  do {
	    if ((**app) > '0') { if (cmp==0) cmp = +1; }
	    (*app)++;
	  } while (isdigit(**app));
      	} else {
      	  cmp = +1;
	  do { (*app)++; } while (isdigit(**app));
	  break;
	}
      }
    } else {
      if (isdigit_b) {
      	// !isdigit_a and isdigit_b
	if (mode) {
	  do {
	    if ((**bpp) > '0') { if (cmp==0) cmp = -1; }
	    (*bpp)++;
	  } while (isdigit(**bpp));
	} else {
      	  cmp = -1;
	  do { (*bpp)++; } while (isdigit(**bpp));
	  break;
	}
      } else {
      	// !isdigit_a and !isdigit_b
	break;
      }
    }
  }
  
  return (cmp);
}  

gint 
compare_float (gconstpointer gpa, gconstpointer gpb)
{
  gint cmp;
  char *a = (char*)gpa;
  char *b = (char*)gpb;

  {
    int version = 0;
    if (*a =='v' || *a == 'V') { version = 1; a++; }
    if (*b =='v' || *b == 'V') { version = 1; b++; }
    if (version) return compare_version(gpa+1,gpb+1);
  };
  
  cmp = numeric_compare(&a, &b, 0); // integer compare
  
  if (cmp == 0) {
    if (*a == '.') a++;
    if (*b == '.') b++;
    cmp = numeric_compare(&a, &b, 1); // fractional compare
  }
  
  return cmp;
}

gint 
compare_version(gconstpointer gpa, gconstpointer gpb)
{
  gint cmp = 0;
  char *a = (char*)gpa;
  char *b = (char*)gpb;
  
  while (cmp == 0) {
    if (*a == '.') a++;
    if (*b == '.') b++;
    if (!isdigit(*a)) {
      if (isdigit(*b)) {
      	cmp = -1;
      }
      break;
    } else {
      if (!isdigit(*b)) {
      	cmp = +1;
      } else {
      	cmp = numeric_compare(&a, &b, 0);
      }
    }
  }
  
  return cmp;
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
