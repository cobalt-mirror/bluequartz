/* $Id: odb_txn_commit.c 3 2003-07-17 15:19:15Z will $
 * odb_txn_commit.c
 */

#include "odb_txn_internal.h"

#define local_fprintf(a...) 
#define dbg_dump_events(a...) 

/************************************************************************
 * commit -- everything in this section deals with committing
 * transactions into the underlying DB
 ************************************************************************/

/* GHFunc_commit_object
 */
void
GHFunc_commit_object (gpointer key, gpointer val, gpointer data)
{
  odb_txn txn;
  char *oidstr;
  odb_oid oid;
  char *class;

  txn = (odb_txn) data;
  oidstr = (char *) key;
  class = (char *) val;

  DPRINTF(DBG_TXN, "GHFunc_commit_object: txn(0x%lx) oid(%s) class(%s)\n",
    (long)txn, oidstr, class);

  if (txn->state == ODB_TXN_INIT) {
    codb_ret ret;
    str_to_oid (oidstr, &oid);
    if (txn->txn) {
      local_fprintf(stderr, "Passed to txn(%lX): create %ld/%s\n", 
      	(long)txn, oid.oid, class);
      ret = odb_txn_createobj_wo_index (txn->txn, &oid, class);
    } else {
      local_fprintf(stderr, "Passed to impl(%lX): create %ld/%s\n", 
      	(long)txn, oid.oid, class);
      ret = impl_create_obj (txn->impl, &oid);
    }
    if (ret != CODB_RET_SUCCESS) {
      txn->state = ODB_TXN_ERROR;
	  DPRINTF(DBG_TXN, "could not create object %ld: %s\n", oid.oid, class);
    }
  }
}

/* odb_txn_commit_objects
 */
codb_ret odb_txn_commit_objects (odb_txn txn)
{
  /* commit each new object */
  g_hash_table_foreach (txn->objects, GHFunc_commit_object, txn);

  /* check transaction status */
  if (txn->state == ODB_TXN_INIT) {
    return CODB_RET_SUCCESS;
  } else {
    return CODB_RET_OTHER;
  }
}

/* GHFunc_commit_scalar
 */
void
GHFunc_commit_scalar (gpointer key, gpointer val, gpointer data)
{
  odb_txn txn;
  char *keystr;
  cce_scalar *sc;
  char *prop;
  odb_oid oid;

  txn = (odb_txn) data;
  keystr = (char *) key;
  sc = (cce_scalar *) val;

  if (txn->state == ODB_TXN_INIT) {
    codb_ret ret;
    str_to_objprop (keystr, &oid, &prop);
    if (txn->txn) {
      local_fprintf(stderr, "Passed to subtxn(%lX): set %ld.%s\n", 
      	(long)txn, oid.oid, prop);
      ret = odb_txn_set (txn->txn, &oid, prop, sc);
    } else {
      local_fprintf(stderr, "Passed to impl(%lX): set %ld.%s\n", 
      	(long)txn, oid.oid, prop);
      ret = impl_write_objprop (txn->impl, &oid, prop, sc);
    }
    if (ret != CODB_RET_SUCCESS) {
	  DPRINTF(DBG_TXN, "Could not commit scalar: %ld.%s = %s\n", oid.oid, 
			prop, (char*)sc->data);
      txn->state = ODB_TXN_ERROR;
    }
  }
}

/* odb_txn_commit_scalars
 */
codb_ret odb_txn_commit_scalars (odb_txn txn)
{
  /* commit each new object */
  g_hash_table_foreach (txn->scalars, GHFunc_commit_scalar, txn);

  /* check transaction status */
  if (txn->state == ODB_TXN_INIT) {
    return CODB_RET_SUCCESS;
  } else {
    return CODB_RET_OTHER;
  }
}

void GHFunc_commit_list (gpointer key, gpointer val, gpointer data);

/* odb_txn_commit_lists
 */
codb_ret odb_txn_commit_lists (odb_txn txn)
{
  /* commit each new object */
  g_hash_table_foreach (txn->lists, GHFunc_commit_list, txn);

  /* check transaction status */
  if (txn->state == ODB_TXN_INIT) {
    return CODB_RET_SUCCESS;
  } else {
    return CODB_RET_OTHER;
  }
}

void
GHFunc_commit_list (gpointer key, gpointer val, gpointer data)
{
  odb_txn txn;
  char *keystr;
  odb_oidlist *olist;
  codb_ret ret;
  char *olist_str;
  cce_scalar *sc;
  odb_oid oid;
  char *prop;

  txn = (odb_txn) data;
  keystr = (char *) key;

  DPRINTF(DBG_TXN, "GHFunc_commit_list: txn(0x%lx) key(%s)\n",
    (long)txn, keystr);
  odb_eventlist_dump( (GSList*)val, stderr);

  if (txn->state == ODB_TXN_INIT) {
    if (txn->txn) {
      /* write events to the next TXN object */
      GSList *eventlist, *sub_eventlist;

      local_fprintf(stderr, "Passed to subtxn(%lX): set list \"%s\"\n", 
      	(long)txn, keystr);

      /* get eventlist for txn and sub-txn */
      eventlist = (GSList *) val;
      local_fprintf(stderr, "new events:\n");
      dbg_dump_events(eventlist);
      sub_eventlist = g_hash_table_lookup (txn->txn->lists, key);
      local_fprintf(stderr, "old events:\n");
      dbg_dump_events(sub_eventlist);

      /* append eventlist to sub_eventlist */
      sub_eventlist = g_slist_concat (sub_eventlist, eventlist);
      local_fprintf(stderr, "aggregate events:\n");
      dbg_dump_events(sub_eventlist);

      /* store eventlist in sub-transaction */
      {
      	gpointer orig_key, orig_data;
	if (!g_hash_table_lookup_extended(txn->txn->lists, key,
	  &orig_key, &orig_data)) 
	{
	  orig_key = strdup((char*)key);
	} else {
	  /* not necessary to free orig_data, since it's already part
	     of the new event list sub_eventlist.  I know, this part
	     of the code is needlessly complicated.  Next round in
	     C++, okay? */
	}
	g_hash_table_insert(txn->txn->lists, orig_key, sub_eventlist);
      }

      /* delete original list */
      g_hash_table_insert (txn->lists, key, NULL);

    } else {
      local_fprintf(stderr, "Passed to impl(%lX): set list \"%s\"\n", 
      	(long)txn, keystr);
      dbg_dump_events((GSList*)val);
      /* Write directly to the IMPL layer */
      str_to_objprop (keystr, &oid, &prop);

      /* get modified oidlist */
      olist = odb_oidlist_new ();  /* alloc olist */
      ret = odb_txn_list (txn, &oid, prop, olist);
      if (ret != CODB_RET_SUCCESS) {
		DPRINTF(DBG_TXN, "could not commit list: %ld.%s\n", oid.oid, prop);
        txn->state = ODB_TXN_ERROR;
        odb_oidlist_destroy (olist);
        free (prop);
        return;
      }

      /* freeze olist */
      olist_str = odb_oidlist_to_str (olist);  /* alloc str */
      odb_oidlist_destroy (olist);
      sc = cce_scalar_new_from_str (olist_str);  /* alloc scalar */
      free (olist_str);

      /* save olist */
      ret = impl_write_objprop (txn->impl, &oid, prop, sc);
      cce_scalar_destroy (sc);
      if (ret != CODB_RET_SUCCESS) {
		DPRINTF(DBG_TXN, "could not commit list(2): %ld.%s\n", oid.oid, prop);
        txn->state = ODB_TXN_ERROR;
      }
      /* free(prop) -- not needed, prop points to something inside of keystr */
    }
  }
}

codb_ret
odb_txn_commit_destructs (odb_txn txn)
{
  GHashIter *it;
  gpointer key, val;
  odb_oid oid;
  int errors;
  codb_ret ret;
  
  errors = 0;
  

  /* iterate through the destroyed objects */
  it = g_hash_iter_new(txn->destroyed);
  for (
    g_hash_iter_first(it, &key, &val);
    key;
    g_hash_iter_next(it, &key, &val))
  {
    str_to_oid((char*)key, &oid);
    if (txn->txn) {
      ret = odb_txn_destroyobj(txn->txn, &oid);
    } else {
      ret = impl_destroy_obj(txn->impl, &oid);
    }
    if (ret != CODB_RET_SUCCESS) errors++;
  }
  g_hash_iter_destroy(it);
  
  if (errors) return CODB_RET_OTHER;
  else return CODB_RET_SUCCESS;
}

/* odb_txn_commit
 */
codb_ret odb_txn_commit (odb_txn txn)
{
  codb_ret ret;

  DPRINTF(DBG_TXN, "COMMITTING txn(0x%lx)\n", (long)txn);

  if (txn->state != ODB_TXN_INIT) {
    DPRINTF(DBG_TXN, "** attempt to commit already commited txn(0x%lx)\n",
      (long)txn);
    return CODB_RET_ALREADY;
  }

  /* 1. commit all object creation operations */
  ret = odb_txn_commit_objects (txn);
  if (ret != CODB_RET_SUCCESS) {
	DPRINTF(DBG_TXN, "Could not commit objects\n");
    txn->state = ODB_TXN_ERROR;
    return ret;
  }

  /* 2. commit all scalar property sets */
  ret = odb_txn_commit_scalars (txn);
  if (ret != CODB_RET_SUCCESS) {
	DPRINTF(DBG_TXN, "Could not commit scalars\n");
    txn->state = ODB_TXN_ERROR;
    return ret;
  }

  /* 3. commit all ref property changes, in order */
  ret = odb_txn_commit_lists (txn);
  if (ret != CODB_RET_SUCCESS) {
	DPRINTF(DBG_TXN, "Could not commit lists\n");
    txn->state = ODB_TXN_ERROR;
    return ret;
  }

	/* 4. delete all explicitly destroyed objects */
  ret = odb_txn_commit_destructs(txn);
  if (ret != CODB_RET_SUCCESS) {
	DPRINTF(DBG_TXN, "Could not commit destructs\n");
  	txn->state = ODB_TXN_ERROR;
    return ret;
  }

  /* 5. clean up the oid pool */
  odb_txn_oid_commit(txn);

  txn->state = ODB_TXN_COMMITED;
  
  return CODB_RET_SUCCESS;
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
