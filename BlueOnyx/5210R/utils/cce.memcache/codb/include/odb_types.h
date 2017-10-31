/* $Id: odb_types.h 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#ifndef _CCE_ODB_TYPES_H_
#define _CCE_ODB_TYPES_H_ 1

/*
 * odb_impl_handle
 *
 * this is the internal handle - only truly known to stuff in impl/
 */
typedef struct odb_impl_handle odb_impl_handle;

/*
 * odb_oid
 *
 * the abstract type for an oid
 */
typedef struct {
	unsigned long oid;
} odb_oid;


/*
 * odb_oidlist
 *
 * the type for a list of oids - a linked list, ending in NULL
 */
typedef struct odb_oidlist_t odb_oidlist;

odb_oidlist * odb_oidlist_new();
void odb_oidlist_destroy(odb_oidlist *);
int  odb_oidlist_add(odb_oidlist *, odb_oid *oid,
    int beforeAfter, odb_oid *other);
int  odb_oidlist_rm(odb_oidlist *, odb_oid *oid);
void odb_oidlist_flush(odb_oidlist *);
odb_oid * odb_oidlist_first(odb_oidlist *);
odb_oid * odb_oidlist_next(odb_oidlist *);
char * odb_oidlist_to_str(odb_oidlist *);
int odb_oidlist_append_from_str(odb_oidlist *, char *);
int odb_oidlist_has(odb_oidlist *, odb_oid *oid);

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
