/* $Id: test2.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <cce_common.h>
#include <stdio.h>
#include <cce_scalar.h>
#include <sys/stat.h>
#include <stdlib.h>
#include <unistd.h>


int errors = 0;
int tests = 0;

#define TRY(expr, f, a...)                                  \
  { tests++;                                                \
    fprintf(stderr, "\ntest%03d: %-60.60s  -> ", tests, #expr);    \
    if (!(expr)) { errors++;                                \
               fprintf(stderr, "FAILED " f , ##a ); }   \
    else { fprintf(stderr, "ok."); }                      \
  }


int test1()
{
  cce_scalar *s1, *s2, *s3, *s4;
  char *str1, *str2, *str3, *str4;
  char binptr[] = {0, 1, 2, 3, 4};

  /* initialize */  
  s1 = cce_scalar_new_from_str("alpha4num");
  s2 = cce_scalar_new_from_str("another \"string\"\nwith quotes");
  s3 = cce_scalar_new_from_bin(binptr, 5);
  s4 = cce_scalar_new_from_binstr("#7#aabbcc================");
  TRY ( s1 && s2 && s3 && s4, "couldn't create scalars" );
  str1 = cce_scalar_to_str(s1);
  str2 = cce_scalar_to_str(s2);
  str3 = cce_scalar_to_str(s3);
  str4 = cce_scalar_to_str(s4);
  TRY ( str1 && str2 && str3 && str4, "couldn't create strings" );
  
  /* compare */
  TRY(strcmp(str1, "\"alpha4num\"") == 0, "");
  TRY(strcmp(str2, "\"another \\\"string\\\"\\nwith quotes\"") == 0, "");
  TRY(strcmp(str3, "#5#AAECAwQA") == 0, "broken binstr");
  TRY(strcmp(str4, "#7#aabbccAAAAAA") == 0, "broken binstr");

  /* compare with each other */
  TRY(cce_scalar_compare(s1, s1) == 0, "compare 1 failed");
  TRY(cce_scalar_compare(s1, s2) <  0, "compare 2 failed");
  TRY(cce_scalar_compare(s1, s3) >  0, "compare 3 failed");

  /* cleanup */  
  free(str1);
  free(str2);
  free(str3);
  free(str4);
  cce_scalar_destroy(s1);
  cce_scalar_destroy(s2);
  cce_scalar_destroy(s3);
  cce_scalar_destroy(s4);

  return 0;
}  

int main(int argc, char *argv[0])
{

  test1();

  fprintf(stderr, "\n%d errors, %d tests.\n", errors, tests);
  return errors;
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
