/* $Id: odb_txn_indexes.c 262 2004-01-10 15:09:57Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <cce_common.h>
#include "odb_txn_internal.h"
#include <odb_transaction.h>
#include <string.h>

codb_ret
odb_txn_index_get(odb_txn txn, char *value, const char *index, odb_oidlist *oidlist)
{
	codb_ret ret;
	GSList *indexlist;

	/* get the "lower level" */
	if (txn->txn) {
		ret = odb_txn_index_get(txn->txn, value, index, oidlist);
	} else {
		ret = impl_index_get(txn->impl, value, index, oidlist);
	}

	/* now add our own changes */
	indexlist = txn->indexing;
	while(indexlist)
	{
		odb_event_index *elem = (odb_event_index *)indexlist->data;
		if (elem->type == ODB_EVENT_INDEXADD
				&& !strcmp(elem->indexname, index)
				&& !strcmp(elem->key, value))
		{
			if (odb_oidlist_add(oidlist, &elem->oid, 1, NULL))
				CCE_SYSLOG("Indexing failed to add oid");
		}
		if (elem->type == ODB_EVENT_INDEXRM
				&& !strcmp(elem->indexname, index)
				&& !strcmp(elem->key, value))
		{
			if (odb_oidlist_rm(oidlist, &elem->oid))
				CCE_SYSLOG("Indexing failed to rm oid");
		}
		indexlist = indexlist->next;
	}

	return ret;
}

codb_ret
odb_indexing_update( odb_txn txn, const char *indexname, odb_oid *oid,
	const char *val, int fAdd)
{
	txn->indexing = g_slist_append(txn->indexing,
		new_odb_event_index(oid, val, indexname, fAdd));
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
