/* $Id: test4.c,v 1.2 2001/08/10 22:23:18 mpashniak Exp $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * Test the staging of handlers, (with rollback).
 * Especially the multi-level transaction operation.
 */

#include <cce_common.h>
#include <codb.h>
#include <cce_conf.h>
#include <cce_ed.h>

#define CREATE_ALL_HASHES \
	do { \
	attr = codb_attr_hash_new(); \
	attrerrs = codb_attr_hash_new(); \
	permerrs = codb_attr_hash_new(); \
	} while (0)

#define DESTROY_ALL_HASHES \
	do { \
	codb_attr_hash_destroy(attr); \
	codb_attr_hash_destroy(attrerrs); \
	codb_attr_hash_destroy(permerrs); \
	} while (0)

int main()
{
	cce_conf *conf;
	codb_handle *odb;
	cce_ed *ed;
	oid_t oid;
	GHashTable *attr, *attrerrs, *permerrs;

	nologflag = 1;
	vflag = 1;

	codb_init("test4.schema");
	conf = cce_conf_get_configuration("test4.conf", "test4.handlers/");
	odb = codb_handle_new("tmp", conf);
	codb_handle_addflags(odb, CODBF_ADMIN);
	ed = cce_ed_new(conf);

	fprintf(stderr, "Foo create begin\n");

	CREATE_ALL_HASHES;
	codb_attr_hash_assign(attr, "alpha", "a");
	codb_attr_hash_assign(attr, "beta", "a");
	codb_attr_hash_assign(attr, "gamma", "a");
	codb_create(odb, "Foo", attr, attrerrs, &oid);
	DESTROY_ALL_HASHES;
	cce_ed_dispatch(ed, odb);
	codb_commit(odb);

	fprintf(stderr, "Foo alpha modify begin\n");

	CREATE_ALL_HASHES;
	codb_attr_hash_assign(attr, "alpha", "b");
	codb_set(odb, oid, "", attr, attrerrs, permerrs);
	DESTROY_ALL_HASHES;
	cce_ed_dispatch(ed, odb);
	codb_commit(odb);

	return 0;
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
