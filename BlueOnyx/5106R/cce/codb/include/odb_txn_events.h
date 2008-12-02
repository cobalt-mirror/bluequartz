/* $Id: odb_txn_events.h 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * odb_txn_events
 *
 * used by the odb_transaction object to keep an ordered list of
 * transaction events.
 */

#ifndef _CCE_ODB_TXN_EVENTS_H_
#define _CCE_ODB_TXN_EVENTS_H_ 1

#include <cce_scalar.h>
#include <odb_types.h>

/***************************************************************
 * odb transaction events 
 ***************************************************************/

typedef enum {
	ODB_EVENT_NEW,
	ODB_EVENT_SET,
	ODB_EVENT_INDEXADD,
	ODB_EVENT_INDEXRM,
	ODB_EVENT_LISTADD,
	ODB_EVENT_LISTRM,
	ODB_EVENT_DESTROYED,
} odb_event_type;

/* this could have been done with a union, but it's more
 * efficient w/o.  Here is a place where inheritance and
 * virtual function pointers would have been very useful.
 */
typedef struct {
	odb_event_type type;
} odb_event;

typedef struct {
	odb_event_type type;
	odb_oid oid;
  char *class;
} odb_event_new;

typedef struct {
	odb_event_type type;
	odb_oid oid;
	char *prop;
	cce_scalar *val;
} odb_event_set;

typedef struct {
	odb_event_type type;
	odb_oid oid;
	char *key;
	char *indexname;
} odb_event_index;

typedef struct {
	odb_event_type type;
	odb_oid oid;
	char *prop;
	odb_oid oidval;
  int before;
	odb_oid other;
} odb_event_listadd;

typedef struct {
	odb_event_type type;
	odb_oid oid;
	char *prop;
	odb_oid oidval;
} odb_event_listrm;

typedef struct {
  odb_event_type type;
  odb_oid oidval;
} odb_event_destroyed;

/* constructors for different kinds of odb_events */
odb_event * new_odb_event_new(odb_oid *oid, char *class);
odb_event * new_odb_event_set(odb_oid *oid, char *prop, cce_scalar *val);
odb_event * dup_odb_event_index(odb_event *old);
odb_event * new_odb_event_index(odb_oid *oid, const char *key,
	const char *index, int add);
odb_event * new_odb_event_listadd(odb_oid *oid, char *prop, 
	odb_oid *oidval, int before, odb_oid *other );
odb_event * new_odb_event_listrm(odb_oid *oid, char *prop,
	odb_oid *oidval);
odb_event * new_odb_event_destroyed(odb_oid *oid);
odb_event * odb_event_dup(odb_event *);

odb_event_type odb_event_type_read(odb_event *);

/* specific destructors (prefer the general destructor below) */
void free_odb_event_new ( odb_event * );
void free_odb_event_set ( odb_event * );
void free_odb_event_listadd ( odb_event * );
void free_odb_event_listrm ( odb_event * );
void free_odb_event_destroyed ( odb_event * );

/* destructor for any odb_event */
void odb_event_destroy(odb_event *); /* destructs any odb_event object */

#include <stdio.h>
void odb_event_dump(odb_event *, FILE *fh);
void odb_eventlist_dump(GSList *, FILE *fh);

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
