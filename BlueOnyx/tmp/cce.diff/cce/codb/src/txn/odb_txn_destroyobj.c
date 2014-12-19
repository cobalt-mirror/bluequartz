/* $Id: odb_txn_destroyobj.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <cce_common.h>
#include "odb_txn_internal.h"
#include <string.h>

/* fIndex is true if we should update the index for this operation.
 * This should be true everywhere except from the commit operation
 */
codb_ret odb_txn_destroyobj (odb_txn txn, odb_oid * oid, int fIndex)
{
  char *oidstr, *foo;
  cce_scalar *classname_sc;

  if (!odb_txn_objexists (txn, oid)) {
    return CODB_RET_UNKOBJ;
  }

  classname_sc = cce_scalar_new_undef ();
  if (odb_txn_get (txn, oid, ".CLASS", classname_sc) != CODB_RET_SUCCESS) {
    cce_scalar_destroy (classname_sc);
    return CODB_RET_OTHER;
  }

  /* add to explicit destroyed list */
  oidstr = oid_to_str (oid);
  foo = strdup (classname_sc->data);
  g_hash_table_insert (txn->destroyed, oidstr, foo);

  if (fIndex) {
    /* maintain the object class index */
    odb_indexing_update(txn, "classes", oid, classname_sc->data, 0);
  }

  cce_scalar_destroy (classname_sc);
  return CODB_RET_SUCCESS;
}

/*eof */
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