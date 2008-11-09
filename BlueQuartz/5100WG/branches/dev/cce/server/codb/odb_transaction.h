/* $Id: odb_transaction.h 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * Implements a transaction object.
 *
 * A transaction object:
 * 	- queues up an ordered list of changes to the object database
 *  - allows ODB queries, filtered through the transaction change list
 *	- can commit changes into the database
 *	- can be serialized / unserialized for transport via IPC.
 */

#ifndef CCE_ODB_TRANSACTION_H_
#define CCE_ODB_TRANSACTION_H_ 1

#include "odb_types.h"
#include "odb_errors.h"
#include "cce_scalar.h"
#include "odb_impl.h"
#include "codb_events.h"
#include <glib.h>

/* odb_txn handle, data hidden */
typedef struct odb_txn_struct *odb_txn;

/* constructor, destructor */
odb_txn odb_txn_new(odb_impl_handle *);
odb_txn odb_txn_new_meta(odb_impl_handle *, odb_txn);
codb_ret odb_txn_destroy(odb_txn);

/* manage oids */
odb_oid odb_txn_oid_grab(odb_txn);
void odb_txn_oid_release(odb_txn, odb_oid);

/* creating a new object */
codb_ret odb_txn_createobj(odb_txn, odb_oid *oid, const char *class);
codb_ret odb_txn_createobj_wo_index(odb_txn, odb_oid *, const char *);
int odb_txn_objexists(odb_txn, odb_oid *oid);
int odb_txn_objexists_old(odb_txn, odb_oid *oid);

/* destroy an object */
codb_ret odb_txn_destroyobj(odb_txn, odb_oid *oid, int fIndex);

/* operating on scalar properties */
codb_ret odb_txn_set(odb_txn, odb_oid *oid, char *prop, cce_scalar *val);
codb_ret odb_txn_get(odb_txn, odb_oid *oid, const char *prop,

    cce_scalar *val);
codb_ret odb_txn_get_old(odb_txn, odb_oid *oid, const char *prop,

    cce_scalar *val);
int odb_txn_propdefined(odb_txn, odb_oid *oid, char *prop);
int odb_txn_is_changed(odb_txn, odb_oid *oid, char *prop);

codb_ret odb_indexing_update(odb_txn, const char *, odb_oid *,
    const char *, int);
codb_ret odb_txn_index_get(odb_txn, char *value, const char *index,
    odb_oidlist * oidlist);

codb_ret odb_txn_get_properties(odb_txn, odb_oid *,
    GSList ** scalarsP, GSList ** listsP);
codb_ret odb_txn_get_properties_old(odb_txn, odb_oid *,
    GSList ** scalarsP, GSList ** listsP);
codb_ret odb_txn_proplist_free(GSList ** proplistP);

/* what to do with the transaction? */
codb_ret odb_txn_commit(odb_txn);
codb_ret odb_txn_flush(odb_txn);

/* get list of events in the current transaction */
codb_ret odb_txn_inspect_codbevents(odb_txn txn, GSList ** eventsP);
void odb_txn_free_codbevents(GSList ** eventsP);

/* serialize/unserialize via a file descriptor */
codb_ret odb_txn_serialize(odb_txn, FILE * write_fd);
codb_ret odb_txn_unserialize(odb_txn, FILE * read_fd);

#endif
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
