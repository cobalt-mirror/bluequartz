/* $Id: odb_txn_properties.c 3 2003-07-17 15:19:15Z will $
 *
 * odb_txn_properties.c
 *
 * maintains list of properties within each object.  Necessary, since
 * we want the strongly-typed classes to be a modular layer on top of
 * this system.
 */

#include "odb_txn_internal.h"

codb_ret odb_txn_proplist_free (GSList ** listP)
{
  GSList *cursor;
  for (cursor = *listP; cursor; cursor = g_slist_next (cursor)) {
    free (cursor->data);
  }
  g_slist_free (*listP);
  *listP = NULL;
  return CODB_RET_SUCCESS;
}

codb_ret
odb_txn_propset_read (odb_txn txn, odb_oid * oid, char *prop, GSList ** listP,
	int oldflag)
{
  cce_scalar *scalar;
  char *token;
  char *cursor;
  scalar = cce_scalar_new_undef ();

  if (oldflag) {
    odb_txn_get_old(txn,oid,prop,scalar);
  }else{
    odb_txn_get (txn, oid, prop, scalar);
  }

  token = scalar->data;
  cursor = token;
  while (cursor && *cursor) {
    if (*cursor == ',') {
      *cursor = '\0';
      *listP = g_slist_append (*listP, strdup (token));
      cursor++;
      token = cursor;
    } else {
      cursor++;
    }
  }
  if (token != cursor) {
    *listP = g_slist_append (*listP, strdup (token));
  }

  cce_scalar_destroy (scalar);

  return CODB_RET_SUCCESS;
}

codb_ret
odb_txn_propset_write (odb_txn txn, odb_oid * oid, char *prop, GSList * listP)
{
  GSList *p;
  cce_scalar *scalar;
  char *cursor;
  int size;

  p = listP;
  size = 0;
  while (p) {
    size += 1 + strlen ((char *) p->data);
    p = g_slist_next (p);
  }

  scalar = cce_scalar_new (size);
  cursor = scalar->data;
  *cursor = '\0';

  p = listP;
  while (p) {
    strncat (cursor, (char *) p->data, 80);
    strcat (cursor, ",");
    p = g_slist_next (p);
  }

  odb_txn_set (txn, oid, prop, scalar);
  cce_scalar_destroy (scalar);

  return CODB_RET_SUCCESS;
}

void
odb_txn_propset_free (GSList ** listP)
{
  GSList *p;
  p = *listP;
  while (p) {
    free (p->data);              /* free data */
    p = g_slist_next (p);
  }
  g_slist_free (*listP);        /* free linkages */
  *listP = NULL;
}

gint
my_strcmp (gconstpointer a, gconstpointer b)
{
  return strcmp ((char *) a, (char *) b);
}

int
odb_txn_propset_add (GSList ** listP, char *value)
{
  GSList *member;

  if (strcmp (value, PROPNAME_LISTS) == 0) {
    return 0;
  }
  if (strcmp (value, PROPNAME_SCALARS) == 0) {
    return 0;
  }

  member = g_slist_find_custom (*listP, value, my_strcmp);

  if (!member) {
    *listP = g_slist_append (*listP, strdup (value));
  }

  return !member;
}

/**
 * odb_txn_get_properties
 *
 **/
codb_ret
odb_txn_get_properties (odb_txn txn, odb_oid * oid,
                        GSList ** scalarsP, GSList ** listsP)
{
  codb_ret ret1, ret2, ret;

	if (!odb_txn_objexists(txn, oid)) {
  	return CODB_RET_UNKOBJ;
  }

  ret1 = odb_txn_propset_read (txn, oid, PROPNAME_SCALARS, scalarsP, 0);
  ret2 = odb_txn_propset_read (txn, oid, PROPNAME_LISTS, listsP, 0);

  ret = (ret1 == CODB_RET_SUCCESS) ? ret2 : ret1;
  return ret;
}

/**
 * odb_txn_get_properties_old
 *
 **/
codb_ret
odb_txn_get_properties_old (odb_txn txn, odb_oid * oid,
                        GSList ** scalarsP, GSList ** listsP)
{
  codb_ret ret1, ret2, ret;

	if (!odb_txn_objexists_old(txn, oid)) {
  	return CODB_RET_UNKOBJ;
  }

  ret1 = odb_txn_propset_read (txn, oid, PROPNAME_SCALARS, scalarsP, 1);
  ret2 = odb_txn_propset_read (txn, oid, PROPNAME_LISTS, listsP, 1);

  ret = (ret1 == CODB_RET_SUCCESS) ? ret2 : ret1;
  return ret;
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
