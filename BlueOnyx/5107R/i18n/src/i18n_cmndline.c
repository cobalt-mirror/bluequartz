#include "i18n.h"
#include <stdio.h>

int
main (int argc, char *argv[])
{
  int i;

  i18n_handle *i18n;
  i18n_vars *vars = NULL;

  i18n = i18n_new (NULL, NULL);
  vars = i18n_vars_new ();

  if (!i18n) {
    fprintf (stderr, "can't create i18n object!\n");
    return -1;
  }

  /* Need at least a string to parse */
  if (argc == 1) {
    fprintf (stderr, i18n_get (i18n, "invalid_command_line", NULL, NULL));
    printf ("\n");
    exit (1);
  }

  if (argc > 2 && argc % 2) {
    fprintf (stderr, i18n_get (i18n, "invalid_command_line", NULL, NULL));
    printf ("\n");
    exit (1);
  }

  for (i = 2; i < argc; i += 2) {
    i18n_vars_add (vars, argv[i], argv[i + 1]);
  }

  printf ("%s", i18n_get (i18n, argv[1], NULL, vars));
  i18n_destroy (i18n);
  i18n_vars_destroy (vars);
  exit (0);
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
