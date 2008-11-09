/* $Id: odb_transaction.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/* 
 * construct, flush, destroy the odb_transaction object.
 */

#include <cce_common.h>
#include "odb_txn_internal.h"

/* fns to deallocate different parts of the txn object */
gboolean GHRFunc_free_str_str (gpointer key, gpointer value,
                               gpointer user_data);
gboolean GHRFunc_free_str_scalar (gpointer key, gpointer value,
                                  gpointer user_data);
gboolean GHRFunc_free_str_eventlist (gpointer key, gpointer value,
                                     gpointer user_data);

odb_txn odb_txn_new (odb_impl_handle * impl)
{
  odb_txn txn;

  txn = (odb_txn) malloc (sizeof (struct odb_txn_struct));
  if (txn) {
    /* initialize */
    txn->state = ODB_TXN_INIT;
    txn->impl = impl;
    txn->txn = NULL;
    txn->objects = g_hash_table_new (g_str_hash, g_str_equal);
    txn->scalars = g_hash_table_new (g_str_hash, g_str_equal);
    txn->lists = g_hash_table_new (g_str_hash, g_str_equal);
    txn->indexing = NULL;
    txn->destroyed = g_hash_table_new (g_str_hash, g_str_equal);
    txn->allocated_oids = NULL;
    txn->released_oids = NULL;

    /* clean up if anything failed */
    if (!txn->objects || !txn->scalars || !txn->lists || !txn->destroyed) {
      odb_txn_destroy ((odb_txn) txn);
      txn = NULL;
    }
    
  }
  return (txn);
}

/* odb_txn_new_meta
 *
 * creates a new transaction object, with another txn object nested
 * within it.  This allows the creation of multiple layers of 
 * transactions, any of which can be collapsed into a sub-layer.
 */
odb_txn odb_txn_new_meta (odb_impl_handle * impl, odb_txn subtxn)
{
  odb_txn txn;

  txn = odb_txn_new (impl);
  if (txn) {
    txn->txn = subtxn;
  }

  return txn;
}

codb_ret odb_txn_destroy (odb_txn txn)
{
  /* empty out all hash entries */
  odb_txn_inner_flush (txn);

  /* free the hashes */
  if (txn->objects)
    g_hash_table_destroy (txn->objects);
  if (txn->scalars)
    g_hash_table_destroy (txn->scalars);
  if (txn->lists)
    g_hash_table_destroy (txn->lists);
  if (txn->destroyed)
  	g_hash_table_destroy (txn->destroyed);

  /* free myself */
  free (txn);

  return CODB_RET_SUCCESS;
}

gboolean
GHRFunc_free_str_str (gpointer key, gpointer value, gpointer user_data)
{
  free (key);
  free (value);
  return TRUE;
}

gboolean
GHRFunc_free_str_scalar (gpointer key, gpointer value, gpointer user_data)
{
  // DPRINTF(DBG_TXN, "cce_scalar: freeing key %s\n", (char *)key);
  free(key);
  cce_scalar_destroy ((cce_scalar *) value);
  return TRUE;
}

gboolean
GHRFunc_free_str_eventlist (gpointer key, gpointer value, gpointer user_data)
{
  GSList *ptr;
  free (key);

  /* free elements of slist */
  ptr = (GSList *) value;
  while (ptr) {
    odb_event_destroy ((odb_event *) ptr->data);
    ptr = ptr->next;
  }

  /* free slist */
  if (value) {
    g_slist_free ((GSList *) value);
  }

  return TRUE;
}

codb_ret odb_txn_inner_flush (odb_txn txn)
{
  GSList *p;

  g_hash_table_freeze (txn->objects);
  g_hash_table_freeze (txn->scalars);
  g_hash_table_freeze (txn->lists);
  g_hash_table_freeze (txn->destroyed);

  g_hash_table_foreach_remove (txn->objects, GHRFunc_free_str_str, NULL);
  g_hash_table_foreach_remove (txn->scalars, GHRFunc_free_str_scalar, NULL);
  g_hash_table_foreach_remove (txn->lists, GHRFunc_free_str_eventlist, NULL);
  g_hash_table_foreach_remove (txn->destroyed, GHRFunc_free_str_str, NULL);

  g_hash_table_thaw (txn->objects);
  g_hash_table_thaw (txn->scalars);
  g_hash_table_thaw (txn->lists);
  g_hash_table_thaw (txn->destroyed);

  p = txn->indexing;
  while (p) {
    if (p->data)
      odb_event_destroy(p->data);
    p = g_slist_next(p);
  }
  if (txn->indexing) {
    g_slist_free(txn->indexing);
  }
  txn->indexing = NULL;

  /* release unneeded oids back into the oid pool: */
  odb_txn_oid_flush(txn);

  txn->state = ODB_TXN_INIT;
  
  return CODB_RET_SUCCESS;
}

codb_ret odb_txn_flush (odb_txn txn)
{
	codb_ret ret;
  ret = odb_txn_inner_flush(txn);
  if (ret != CODB_RET_SUCCESS) { return ret; }
  
  return CODB_RET_SUCCESS;
}


/* eof */
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
