/* $Id: odb_txn_internal.h 3 2003-07-17 15:19:15Z will $
 * odb_txn_internal.h
 *
 */

#ifndef __ODB_TXN_INTERNAL_H__
#define __ODB_TXN_INTERNAL_H__

#include <cce_common.h>

#ifdef DEBUG_TXN
#	define CCE_ENABLE_DEBUG
#else
#	undef CCE_ENABLE_DEBUG
#endif /* ifdef DEBUG */
#include <cce_debug.h>

#include <odb_transaction.h>
#include <odb_txn_inspect.h>
#include <odb_txn_events.h>	/* old events: used for listadd/listrm events */
#include <codb_events.h> /* new events: used for everything else */
#include <odb_txn_lexer.h>
#include <odb_helpers.h>
#include <glib.h>
#include <cce_scalar.h>
#include <stdio.h>

/* special property names */
#define PROPNAME_SCALARS    "_SCALARS"
#define PROPNAME_LISTS      "_LISTS"

/* special OIDs */
#define ERROR_OID           (0)
#define ROOT_OID            (1)

/***************************************************************
 * odb transaction 
 ***************************************************************/

typedef enum
{
  ODB_TXN_INIT,
  ODB_TXN_COMMITED,
  ODB_TXN_ERROR
}
odb_txn_state_t;
 
struct odb_txn_struct
{
  odb_txn_state_t state;
  odb_impl_handle *impl;        /* handle to the underlying object store */
  odb_txn txn;                  /* handle to a potential sub-transaction */
  GHashTable *objects;          /* newly created objects */
  GHashTable *scalars;          /* manipulations of object scalar props */
  GHashTable *lists;            /* manipulations of object ref props */
  GHashTable *destroyed;	  /* hash of objects explicitly destroyed */
  GSList *released_oids;
  GSList *allocated_oids;
};

/*****************************
 * fns in odb_txn_properties.c:
 *****************************/

codb_ret odb_txn_propset_write(odb_txn txn,
      odb_oid *oid, char *prop, GSList *listP);
codb_ret odb_txn_propset_read(odb_txn txn, 
      odb_oid *oid, char *prop, GSList **listP, int oldflag);
int  odb_txn_propset_add(GSList **listP, char *value);
void odb_txn_propset_free(GSList **listP);

/*****************************
 * fns in odb_transaction.c
 *****************************/
codb_ret odb_txn_inner_flush(odb_txn txn);

/*****************************
 * fns for manipulating the oid pool
 *****************************/
void    odb_txn_oid_mark(odb_txn, odb_oid); /* mark oid as new */
void    odb_txn_oid_commit(odb_txn);
void    odb_txn_oid_flush(odb_txn);
// odb_txn_oid_grab and odb_txn_oid_release are part of the public
// interface defined in odb_transaction.h

/* for debugging only: */
void dbg_dump_events (GSList *);
void dbg_dump_hash(char *label, GHashTable *hash);

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
