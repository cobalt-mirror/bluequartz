/* $Id: codb_events.h 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * codb_event object declaration
 */

#ifndef CCE_CODB_EVENTS_H_
#define CCE_CODB_EVENTS_H_ 1

#include "cce_types.h" /* for oid_t */

typedef enum {
	CREATE, MODIFY, DESTROY, READ
}codb_event_type;

typedef struct codb_event
{
  codb_event_type type;
  oid_t oid;
  char *data;
  char *namespace;
  char *property;
} codb_event;

codb_event *codb_event_new (codb_event_type type, oid_t oid, const char *data);
codb_event *codb_event_dup (codb_event *event);
void codb_event_destroy (codb_event *event);

int codb_event_is_create(codb_event *event);
int codb_event_is_modify(codb_event *event);
int codb_event_is_destroy(codb_event *event);
oid_t codb_event_get_oid(codb_event *event);
const char *codb_event_get_string(codb_event *event);
const char *codb_event_get_property(codb_event *event);
const char *codb_event_get_namespace(codb_event *event);

#endif /* cce/codb_events.h */
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
