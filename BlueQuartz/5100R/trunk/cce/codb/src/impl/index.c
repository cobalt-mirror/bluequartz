/* $Id: index.c,v 1.6 2001/08/10 22:23:10 mpashniak Exp $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <cce_common.h>
#include <impl.h>
#include <string.h>
#include <db.h>

static codb_ret
impl_open_index_db(odb_impl_handle *h, DB **dbpp, const char *indexname)
{
	char path[512];
	BTREEINFO binf;

	if (snprintf(path, sizeof(path), "%s/db.%s", h->db_path, indexname)
		> (sizeof(path) - 1))
	{
		CCE_SYSLOG("index path too long for index %s", indexname);
		return CODB_RET_OTHER;
	}

	memset(&binf, 0, sizeof(binf));
	binf.flags = R_DUP;

	*dbpp = dbopen(path, O_CREAT|O_RDWR, S_IRUSR|S_IWUSR, DB_BTREE, &binf);
	if (!*dbpp)
		return CODB_RET_OTHER;

	return CODB_RET_SUCCESS;
}

codb_ret
impl_index_add(odb_impl_handle *h, odb_oid *oid, char *dbkey, char *indexname)
{
	DB *dbp = NULL;
	DBT key, data;

	impl_open_index_db(h, &dbp, indexname);
	memset(&key, 0, sizeof(key));
	memset(&data, 0, sizeof(data));
	key.data = dbkey;
	key.size = strlen(dbkey) + 1;

	data.data = &(oid->oid);
	data.size = sizeof(oid->oid);

	dbp->put(dbp, &key, &data, 0);
	dbp->close(dbp);

	return CODB_RET_SUCCESS;
}

codb_ret
impl_index_rm(odb_impl_handle *h, odb_oid *oid, char *dbkey, char *indexname)
{
	int ret;
	DB *dbp = NULL;
	DBT key, data;

	impl_open_index_db(h, &dbp, indexname);

	memset(&key, 0, sizeof(key));
	memset(&data, 0, sizeof(data));
	key.data = dbkey;
	key.size = strlen(dbkey) + 1;

	data.data = &(oid->oid);
	data.size = sizeof(oid->oid);

	ret = dbp->seq(dbp, &key, &data, R_CURSOR);
	while (!ret)
	{
		if (oid->oid == *(unsigned long*)data.data)
		{
			dbp->del(dbp, &key, R_CURSOR);
		}
		ret = dbp->seq(dbp, &key, &data, R_NEXT);
	}

	dbp->close(dbp);

	return CODB_RET_SUCCESS;
}


codb_ret
impl_index_get (odb_impl_handle *h, char *dbkey, const char *indexname, odb_oidlist *oidlist )
{
	int ret;
	DB *dbp = NULL;
	DBT key, data;
	odb_oid oid;

	impl_open_index_db(h, &dbp, indexname);

	memset(&key, 0, sizeof(key));
	memset(&data, 0, sizeof(data));
	key.data = dbkey;
	key.size = strlen(dbkey) + 1;
	ret = dbp->seq(dbp, &key, &data, R_CURSOR);
	while (!ret)
	{
		if (key.size != strlen(dbkey) + 1
				|| strncmp(key.data, dbkey, key.size))
		{
			break;
		}
		oid.oid = *(unsigned long*)data.data;
		odb_oidlist_add(oidlist, &oid, 0, NULL);
		ret = dbp->seq(dbp, &key, &data, R_NEXT);
	}

	dbp->close(dbp);
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
