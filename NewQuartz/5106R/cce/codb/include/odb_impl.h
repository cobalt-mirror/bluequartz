/* $Id: odb_impl.h 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 *
 * this is the impl interface, as shown to the odb
 * any implementation needs to conform to this API.
 * This file need not change between implementations
 *
 * This is just a storage/retreival mechanism.  Nothing else happens
 * at this level.
 */

#ifndef _CCE_ODB_IMPL_H_
#define _CCE_ODB_IMPL_H_ 1

#include <odb_types.h>
#include <odb_errors.h>
#include <cce_scalar.h>
#include <glib.h>

/* create and destroy obj_impl_handle */
odb_impl_handle *impl_handle_new(const char *);
void impl_handle_destroy(odb_impl_handle *h);

/* instance store/retr */
codb_ret impl_create_obj(odb_impl_handle *h, odb_oid *oid);
codb_ret impl_destroy_obj(odb_impl_handle *h, odb_oid *oid);
int impl_obj_exists(odb_impl_handle *h, odb_oid *oid);

/* instance data store/retr */
codb_ret impl_write_objprop(odb_impl_handle *h, odb_oid *oid, char *prop, 
	cce_scalar *val);
codb_ret impl_read_objprop(odb_impl_handle *h, odb_oid *oid, const char *prop, 
	cce_scalar *result);
int impl_objprop_isdefined(odb_impl_handle *h, odb_oid *oid, char *prop);

/* get a list */
codb_ret impl_listget (odb_impl_handle *impl, odb_oid *oid, char *prop,
  odb_oidlist *oidlist);

/* indexing */
codb_ret impl_index_add(odb_impl_handle *impl, odb_oid *oid, char *key,
	char *indexname);
codb_ret impl_index_rm(odb_impl_handle *impl, odb_oid *oid, char *key,
	char *indexname);
codb_ret impl_index_get(odb_impl_handle *impl, char *key, const char *indexname, odb_oidlist *oidlist);

/* oid stuff */
codb_ret impl_release_an_oid(odb_impl_handle *impl, odb_oid *oid);
codb_ret impl_grab_an_oid(odb_impl_handle *impl, odb_oid *oid);

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
