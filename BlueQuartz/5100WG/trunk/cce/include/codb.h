/****************************************************************************
 * $Id: codb.h 3 2003-07-17 15:19:15Z will $
 ****************************************************************************
 * Provides a consistent API for dealing with a persistent object storage
 * system (the ODB), insulating the user from the details of the
 * implementation of the DB (postgres, berkeleyDB, v-memory, text files,
 * XML, punched tape, etc.)
 ****************************************************************************/

#ifndef _CCE_CODB2_H_
#define _CCE_CODB2_H_

#include <glib.h>
#include <odb_errors.h>
#include <odb_attribs.h>
#include <cce_conf.h>

#define HH codb_handle *h

typedef struct codb_handle_struct codb_handle;
typedef unsigned long oid_t;

/*
 * These are the routine primitives provided by the ODB.
 */

void codb_dump(codb_handle *h);

int codb_init(void);
int codb_set_ro(int val);

/* connection related funcs */
codb_handle *codb_handle_new(cce_conf *conf);
void codb_handle_destroy(codb_handle *h);
codb_handle *codb_handle_branch(codb_handle *h); /* new handle */
int         codb_handle_branch_level(codb_handle *h);
codb_handle *codb_handle_rootref(codb_handle *h); /* ref to existing handle */
oid_t codb_handle_setoid(codb_handle *h, oid_t oid);
oid_t codb_handle_getoid(codb_handle *h);

/* connection flags */
#define CODBF_ADMIN	1
void codb_handle_setflags(codb_handle *h, unsigned int flags);
void codb_handle_addflags(codb_handle *h, unsigned int flags);
void codb_handle_rmflags(codb_handle *h, unsigned int flags);
unsigned int codb_handle_getflags(codb_handle *h);

/* create a new object, */
codb_ret codb_create(codb_handle *h, const char *class, 
	GHashTable *attribs, GHashTable *attriberrs, oid_t *oid);
codb_ret codb_destroy(codb_handle *h, oid_t oid );
int     codb_objexists(codb_handle *h, oid_t oid);

codb_ret codb_get(codb_handle *h, oid_t oid, const char *namespace, 
	GHashTable *attribs);
codb_ret codb_set(codb_handle *h, oid_t oid, const char *namespace, 
	GHashTable *attribs, GHashTable *data_errs, GHashTable *perm_errs);

codb_ret codb_get_old(codb_handle *h, oid_t oid, const char *namespace,
	GHashTable *attribs);
codb_ret codb_get_changed(codb_handle *h, oid_t oid, const char *namespace,
	GHashTable *attribs);

char *codb_get_classname(codb_handle *h, oid_t oid);

/* get all namespaces */
codb_ret codb_names(codb_handle *h, const char *class, GSList **namespaces);
/* get all classes */
codb_ret codb_classlist(codb_handle *h, GSList **namespaces);
/* find all objects that match some criteria */
codb_ret codb_find(codb_handle *h, const char *class, 
	const GHashTable *criteria, GSList **oids, 
	const char *sort_by, int sorttype);
/* find 'num' objects that match some criteria */
codb_ret codb_find_n(codb_handle *h, const char *class, 
	const GHashTable *criteria, int goalnum, GSList **oids,
	const char *sort_by, int sorttype);

codb_ret codb_commit(codb_handle *h);
codb_ret codb_flush(codb_handle *h);

int codb_is_magic_prop(char *str);

codb_ret codb_list_events(codb_handle *h, GSList **events);
void     codb_free_events(GSList **events);

/* cleanup functions */
void codb_free_list(GSList *oids);

void codb_dump_events(codb_handle *h);

/* FIXME: MAYBE serialize / unserialize */
#if 0
codb_ret codb_serialize (codb_handle *h, FILE *write_fd);
codb_ret codb_unserialize(codb_handle *h, FILE *read_fd);
#endif

#endif /* cce/codb.h */
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
