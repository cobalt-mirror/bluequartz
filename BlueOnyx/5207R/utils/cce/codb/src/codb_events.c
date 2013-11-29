/* $Id: codb_events.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <cce_common.h>
#include <stdlib.h>
#include <string.h>
#include <codb_events.h>

codb_event *
codb_event_new (codb_event_type type, oid_t oid, const char *data)
{
	codb_event *event;
	char *tmp;
	char *p;

	event = malloc(sizeof(codb_event));
	if (!event) {
		return NULL;
	}
	event->type = type;
	event->oid = oid;
	
	tmp = strdup(data);
	p = strchr(tmp, '.');
	if (!p) {
		event->namespace = NULL;
		event->property = tmp;
		event->data = strdup(tmp);
	} else {
		*p = '\0';
		event->namespace = strdup(tmp);
		event->property = strdup(p+1);
		*p = '.';
		event->data = tmp;
	}

	return event;
}

codb_event *
codb_event_dup (codb_event *event)
{
	codb_event * event_copy;

	if (!event) return NULL;
	
	event_copy = malloc(sizeof(codb_event));
	if (!event_copy) return NULL;

	event_copy->type = event->type;
	event_copy->oid = event->oid;

	event_copy->property = strdup(event->property);
	if (event->namespace) {
		event_copy->namespace = strdup(event->namespace);
	} else {
		event_copy->namespace = NULL;
	}
	event_copy->data = strdup(event->data);

	return event_copy;
}

void
codb_event_destroy (codb_event *event)
{
	free(event->data);
	free(event->property);
	if (event->namespace) {
		free(event->namespace);
	}
	free(event);
}

int
codb_event_is_create(codb_event *event)
{
	return (event->type == CREATE);
}

int
codb_event_is_modify(codb_event *event)
{
	return (event->type == MODIFY);
}

int
codb_event_is_destroy(codb_event *event)
{
	return (event->type == DESTROY);
}

oid_t
codb_event_get_oid(codb_event *event)
{
	return event->oid;
}

const char *
codb_event_get_string(codb_event *event)
{
	return event->data;
}

const char *
codb_event_get_namespace(codb_event *event)
{
	return event->namespace;
}

const char *
codb_event_get_property(codb_event *event)
{
	return event->property;
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
