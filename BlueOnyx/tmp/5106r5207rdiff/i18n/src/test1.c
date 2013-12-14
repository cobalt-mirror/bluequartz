/* $Id: test1.c 3 2003-07-17 15:19:15Z will $ 
 * test i18n
 */

#include <stdio.h>
#include "i18n.h"

char *data[] = {
  "abcdef 12345", "abcdef 12345",
  "[[VAR.foo]]", "bar",
  "[[VAR.foo,blah=bleh]]", "bar",
  "[[VAR.fee,fee=fie]]", "fie",
  "[[VAR.foo]] [[VAR.foo]]", "bar bar",
  "abc\\nd\\\\ef\\tged", "abc\nd\\ef\tged",
  "[[FILE./etc/passwd]]", "/etc/passwd",
  "[[FILE./etc/bogus]]", "/etc/bogus",
  "[[FILE./tmp/bogus]]", "/tmp/bogus.en",
  "[[domain.tag]]", "tag",
  "[[test.foo-tag]]",
    "fee fie foo fum embedding another tag into a string: Why, hello there, o world!  did it work? how far does it go?",
  "hello [[test.test-var]]", "hello foo = 'bar' and bar = '[[VAR.bar]]'",
  NULL, NULL,
};

int
main ()
{
  i18n_vars *vars;
  i18n_handle *i18n;
  int errors, tests;
  char **dpp;

  system ("touch /tmp/bogus.en");

  errors = tests = 0;

  vars = i18n_vars_new ();
  i18n_vars_add (vars, "foo", "bar");

  i18n = i18n_new ("cobalt", "en_US, en_AU, en, zh");

  dpp = data;
  while (*dpp != NULL) {
    char *src, *expected, *got;
    src = *dpp++;
    expected = *dpp++;
    got = i18n_interpolate (i18n, src, vars);
    printf ("     src: '%s'\n", src);
    printf ("expected: '%s'\n", expected);
    printf ("     got: '%s'\n", got);
    tests++;
    if (!strcmp (expected, got)) {
      printf ("ok.\n");
    } else {
      char *p1, *p2;
      printf ("failed.  ");
      p1 = expected;
      p2 = got;
      while ((*p1) != '\0') {
        if ((*p1) == (*p2)) {
          printf (" ");
        } else {
          printf ("^");
          break;
        }
        p1++;
        p2++;
      }
      printf ("\n");
      errors++;
    }
  }

  i18n_destroy (i18n);

  printf ("%d errors out of %d tests.\n", errors, tests);
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
