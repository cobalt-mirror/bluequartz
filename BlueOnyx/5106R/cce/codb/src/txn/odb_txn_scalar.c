/* $Id: odb_txn_scalar.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <cce_common.h>
#include "odb_txn_internal.h"

codb_ret
odb_txn_set(odb_txn txn, odb_oid *oid, char *prop, cce_scalar *val)
{
  char *objprop_str;
  cce_scalar *val_copy;

  /* verify that the object exists */
  if (!odb_txn_objexists(txn, oid)) {
  	return CODB_RET_UNKOBJ;
  }

  /* check to see if we're _really_ changing the property: */
  {
	val_copy = cce_scalar_new_undef();
	odb_txn_get(txn, oid, prop, val_copy);
	if (cce_scalar_compare(val, val_copy) == 0) {
		cce_scalar_destroy(val_copy);
		return CODB_RET_SUCCESS;
	}
	cce_scalar_destroy(val_copy);
  }

  /* create copy of objprop_str */
  objprop_str = objprop_to_str (oid, prop);
  if (!objprop_str) {
    return CODB_RET_NOMEM;
  }

  /* create copy of value scalar */
  val_copy = cce_scalar_dup (val);
  if (!val_copy) {
    free (objprop_str);
    return CODB_RET_NOMEM;
  }
  // DPRINTF(DBG_TXN, "cce_scalar: 0x%lx --> %s\n", (unsigned long)val_copy, 
  	//objprop_str);

  /* check to see if we already have an entry. if so, free it. */
  {
    gpointer orig_key, orig_value;
    if (g_hash_table_lookup_extended (txn->scalars, objprop_str,
                                      &orig_key, &orig_value)) {
      /* entry already exists, destroy it. */
      // DPRINTF(DBG_TXN, "-- deallocated existing key\n");
      g_hash_table_remove(txn->scalars, orig_key);
      free ((char *) orig_key);
      cce_scalar_destroy ((cce_scalar *) orig_value);
    }
  }

  /* make / replace entry */
  g_hash_table_insert (txn->scalars, objprop_str, val_copy);

  return CODB_RET_SUCCESS;
}

codb_ret
odb_txn_get_old (odb_txn txn, odb_oid * oid, const char *prop, cce_scalar * val)
{
  if (txn->txn) {
    return odb_txn_get (txn->txn, oid, prop, val);
  } else {
    return impl_read_objprop (txn->impl, oid, prop, val);
  }
}	

codb_ret
odb_txn_get (odb_txn txn, odb_oid *oid, const char *prop, cce_scalar *val)
{
  char *objprop_str;
  gpointer orig_key, orig_value;
  gboolean found;

  /* verify that the object exists */
	if (!odb_txn_objexists(txn, oid)) {
  	return CODB_RET_UNKOBJ;
  }

  /* create obj.prop pair string representation */
  objprop_str = objprop_to_str (oid, prop);

  /* lookup key */
  found = g_hash_table_lookup_extended (txn->scalars, objprop_str,
                                        &orig_key, &orig_value);

  /* destroy string */
  free (objprop_str);

  if (found) {
    /* found! */
    cce_scalar_assign (val, orig_value);
    return CODB_RET_SUCCESS;
  } else {
    /* not found */
    return odb_txn_get_old(txn, oid, prop, val);
  }
}

int 
odb_txn_is_changed ( odb_txn txn, odb_oid *oid, char *prop)
{
  char *objprop_str;
  gpointer orig_key, orig_value;
  gboolean found;

  /* create obj.prop pair string representation */
  objprop_str = objprop_to_str (oid, prop);

  /* lookup key */
  found = g_hash_table_lookup_extended (txn->scalars, objprop_str,
                                        &orig_key, &orig_value);

  /* destroy string */
  free (objprop_str);

	return (found);
}

int
odb_txn_propdefined (odb_txn txn, odb_oid * oid, char *prop)
{
  char *objprop_str;
  gpointer orig_key, orig_value;
  gboolean found;

  /* verify that the object exists */
	if (!odb_txn_objexists(txn, oid)) {
  	return 0;
  }

  /* create obj.prop pair string representation */
  objprop_str = objprop_to_str (oid, prop);

  /* lookup key */
  found = g_hash_table_lookup_extended (txn->scalars, objprop_str,
                                        &orig_key, &orig_value);

  /* destroy string */
  free (objprop_str);

  if (found) {
    /* found! */
    return cce_scalar_isdefined (((cce_scalar *) orig_value));
  } else {
    /* not found */
    if (txn->txn) {
      return odb_txn_propdefined (txn->txn, oid, prop);
    } else {
      return impl_objprop_isdefined (txn->impl, oid, prop);
    }
  }
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
