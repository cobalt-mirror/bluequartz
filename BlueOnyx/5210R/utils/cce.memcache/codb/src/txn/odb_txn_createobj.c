/* $Id: odb_txn_createobj.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <cce_common.h>
#include "odb_txn_internal.h"
#include <string.h>

#define local_printf(a...) 
#define local_fprintf(a...) 

/* odb_txn_createobj_wo_index:
 * creates an object in the txn without updating the index.
 * This function is called from the commit operator, and the indexing
 * is commited seperately
 */
/* FIXME: use the same model for both destroy and create */
/* cf. odb_txn_destroyobj */
codb_ret odb_txn_createobj_wo_index ( odb_txn txn, odb_oid *oid, 
  const char *class)
{
  char *oid_copy;
  char *class_copy;

  local_printf ("odb_txn_createobj_wo_index: txn=%ld, oid=%ld, class=%s\n",
    (unsigned long)txn, (unsigned long)oid->oid, class);

  /* verify that object doesn't already exist */
  if (odb_txn_objexists(txn, oid)) {
      	local_printf("\tobject already exists\n");
  	return CODB_RET_ALREADY;
  }
  
  oid_copy = oid_to_str (oid);
  class_copy = strdup (class);
  local_printf ("class_copy(0x%lx)=%s class(0x%lx)=%s\n",
	(long)class_copy, class_copy,
	(long)class, class);

  g_hash_table_insert (txn->objects, oid_copy, class_copy);
  
  dbg_dump_hash("txn->objects", txn->objects);
  
  return CODB_RET_SUCCESS;
}

codb_ret odb_txn_createobj (odb_txn txn, odb_oid * oid, const char *class)
{
  codb_ret ret;

  local_fprintf (stderr, "ODB_TXN_CREATEOBJ: txn=%ld, oid=%ld, class=%s\n", 
    (unsigned long)txn, oid->oid, class);
  
  ret = odb_txn_createobj_wo_index (txn, oid, class);
  if (ret != CODB_RET_SUCCESS) {
    local_printf("\tobject already exists.\n");
    return ret;
  }
  
  /* maintain the object class index */
  odb_indexing_update(txn, "classes", oid, class, 1);

  return CODB_RET_SUCCESS;
}

int odb_txn_objexists_old (odb_txn txn, odb_oid * oid)
{
  if (txn->txn) {
    return odb_txn_objexists (txn->txn, oid);
  } else {
    return impl_obj_exists (txn->impl, oid);
  }
}

int odb_txn_objexists (odb_txn txn, odb_oid * oid)
{
  char *oidstr;
  gpointer found;

  oidstr = oid_to_str (oid);
  
  found = g_hash_table_lookup (txn->destroyed, (gconstpointer) oidstr);
  if (found) {
  	free (oidstr);
    return 0; /* destroyed */
  }
  
  found = g_hash_table_lookup (txn->objects, (gconstpointer) oidstr);
  free (oidstr);

  if (found) {
    /* found object! */
    return 1;
  } else {
    /* check in impl layer */
    if (txn->txn) {
      return odb_txn_objexists (txn->txn, oid);
    } else {
      return impl_obj_exists (txn->impl, oid);
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
