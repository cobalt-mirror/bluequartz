/* $Id: odb_txn_destroyobj.c 3 2003-07-17 15:19:15Z will $
 */

#include "odb_txn_internal.h"

codb_ret odb_txn_destroyobj (odb_txn txn, odb_oid * oid)
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

  /* maintain the object index */
  if (oid->oid != 0) {
    char *indexname;
    char *class;
    odb_oid indexer;
    indexer.oid = 0;

    /* index by classname */
    class = (char *) classname_sc->data;
    indexname = malloc (6 + strlen (class) + 1);
    if (indexname) {
      strcpy (indexname, "CLASS=");
      strcat (indexname, class);
      odb_txn_listrm (txn, &indexer, indexname, oid);
      free (indexname);
    }
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
