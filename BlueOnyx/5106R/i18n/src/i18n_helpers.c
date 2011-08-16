
/* $Id: i18n_helpers.c 3 2003-07-17 15:19:15Z will $
 * several helper/cleanup functions
 */

#include "i18n_internals.h"

gint slist_str_find (gconstpointer a, gconstpointer b)
{
  return (strcmp (a, b));
}

gboolean hash_slist_rm (gpointer key, gpointer value, gpointer data)
{
  free (key);
  free_whole_g_slist ((GSList *) value);

  return TRUE;
}

gboolean hash_int_rm (gpointer key, gpointer value, gpointer data)
{
  free (key);

  return TRUE;
}

/* this is used for cached encodings, which have static key/value strings */
gboolean hash_hash_rm (gpointer key, gpointer value, gpointer data)
{
  free (key);

  /* g_hash_table_foreach_remove(value, hash_string_rm, NULL); */

  g_hash_table_destroy (value);

  return TRUE;
}

gboolean hash_string_rm (gpointer key, gpointer value, gpointer data)
{
  free (key);
  free (value);

  return TRUE;
}

void
free_whole_g_slist (GSList * list)
{
  GSList *curr;

  curr = list;
  while (curr) {
    free (curr->data);
    curr = g_slist_next (curr);
  }
  g_slist_free (list);
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
