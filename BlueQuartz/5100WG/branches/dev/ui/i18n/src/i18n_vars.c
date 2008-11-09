/* $Id: i18n_vars.c 201 2003-07-18 19:11:07Z will $ 
 * i18n_vars.c
 */

#include "i18n_internals.h"

/**
 *
 * Allocate a new i18n_vars data structure.
 *
 * I18n_vars is used to pass a 'list' of variables to i18n_get_* functions.
 *
 * @returns A pointer to a string formatted to the specified time
 */
i18n_vars *
i18n_vars_new (void)
{
  GHashTable *vars;
  vars = g_hash_table_new (g_str_hash, g_str_equal);
  return (i18n_vars *) vars;
}

/**
 *
 * Add a variable to an i18n_vars structure
 *
 * I18n_vars is used to pass a 'list' of variables to i18n_get_* functions.
 * Memory will be allocated internally for data, and freed with
 * i18n_vars_destroy
 *
 * @returns nothing
 *
 * @see i18n_vars_destroy
 */
void
i18n_vars_add (i18n_vars * vars, char *key, char *value)
{
  g_hash_table_insert ((GHashTable *) vars, strdup (key),
                       strdup (value));
  return;
}

/**
 *
 * Destroy and deallocate an i18n_vars structure.
 *
 * I18n_vars is used to pass a 'list' of variables to i18n_get_* functions.
 *
 * @returns nothing
 *
 * @see i18n_vars_new
 */
void
i18n_vars_destroy (i18n_vars * vars)
{
  if (vars) {
  	g_hash_table_foreach_remove ((GHashTable *) vars, hash_string_rm, NULL);
  	g_hash_table_destroy ((GHashTable *) vars);
  }
  return;
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
