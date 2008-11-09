/* $Id: odb_types.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include "cce_common.h"
#include "codb.h"
#include "odb_types.h"
#include <glib.h>
#include <stdlib.h>
#include <stdio.h>
#include <string.h>

struct odb_oidlist_t {
	GSList *list;
	GSList *cursor;
};

#define dumplist(a,b)		/* _dumplist(a,b) */

void
_dumplist(char *op, odb_oidlist * olist)
{
	char *str = odb_oidlist_to_str(olist);

	DPRINTF(DBG_CODB, "-- olist(%s): %s\n", op, str);
	free(str);
}

odb_oidlist *
odb_oidlist_new()
{
	odb_oidlist *olist;

	olist = (odb_oidlist *) malloc(sizeof(odb_oidlist));

	if (olist) {
		olist->list = NULL;
		olist->cursor = NULL;
	}

	dumplist("new", olist);
	return olist;
}

void
odb_oidlist_destroy(odb_oidlist * olist)
{
	dumplist("destroy", olist);
	odb_oidlist_flush(olist);
	free(olist);
}

void
odb_oidlist_flush(odb_oidlist * olist)
{
	GSList *ptr;

	ptr = olist->list;
	while (ptr) {
		free(ptr->data);
		ptr = ptr->next;
	}
	g_slist_free(olist->list);
	olist->list = NULL;
	olist->cursor = NULL;

	dumplist("flush", olist);
}


int
odb_oidlist_add(odb_oidlist * olist, odb_oid *oid,
    int beforeAfter, odb_oid *other)
{
	odb_oid *oid_copy;
	GSList *ptr, *prev;
	GSList *elem;

	/* make copy */
	oid_copy = (odb_oid *)malloc(sizeof(odb_oid));

	if (!oid_copy)
		return -1;
	oid_copy->oid = oid->oid;

	/* make link element */
	elem = g_slist_alloc();
	if (!elem) {
		free(oid_copy);
		return -1;
	}
	elem->data = (gpointer) oid_copy;

	/* find insertion point */
	ptr = NULL;
	prev = NULL;
	if (other) {
		ptr = olist->list;
		while (ptr) {
			if (((odb_oid *)ptr->data)->oid == other->oid) {
				break;
			}
			prev = ptr;
			ptr = ptr->next;
		}
	}

	/* no insertion point found, pick the head or tail */
	if (!ptr) {
		if (beforeAfter) {
			ptr = olist->list;
			prev = NULL;
		} else {
			ptr = g_slist_last(olist->list);
			prev = NULL;
		}
	}

	/* insert into list */
	if (beforeAfter) {
		/* true -> "before" */
		elem->next = ptr;
		if (prev) {
			prev->next = elem;
		} else {
			olist->list = elem;
		}
	} else {
		/* false -> "after" */
		/* insert after current element */
		if (ptr) {
			elem->next = ptr->next;
			ptr->next = elem;
		} else {
			elem->next = ptr;
			olist->list = elem;
		}
	}

	dumplist("add", olist);
	return 0;
}

int
odb_oidlist_rm(odb_oidlist * olist, odb_oid *oid)
{
	GSList *ptr, *prev;

	ptr = olist->list;
	prev = NULL;

	/* seek */
	while (ptr) {
		if (((odb_oid *)ptr->data)->oid == oid->oid)
			break;
		prev = ptr;
		ptr = ptr->next;
	}
	if (!ptr) {
		return -1;
	}

	/* and destroy */
	free(ptr->data);
	if (prev) {
		prev->next = ptr->next;
	} else {
		olist->list = ptr->next;
	}
	g_slist_free_1(ptr);

	dumplist("rm", olist);
	return 0;
}

odb_oid *
odb_oidlist_first(odb_oidlist * olist)
{
	olist->cursor = olist->list;
	return odb_oidlist_next(olist);
}

odb_oid *
odb_oidlist_next(odb_oidlist * olist)
{
	odb_oid *ret = NULL;

	if (olist->cursor) {
		ret = (odb_oid *)olist->cursor->data;
		olist->cursor = olist->cursor->next;
	}
	return ret;
}

char *
odb_oidlist_to_str(odb_oidlist * olist)
{
	guint numelements;
	size_t bufsize;
	char *buf;
	char *sptr;
	odb_oid *oid;

	/* allocate buffer */
	numelements = g_slist_length(olist->list);
	bufsize = sizeof(char) * ((numelements * 9) + 1);

	buf = (char *)malloc(bufsize);
	memset(buf, 0, bufsize);
	if (!buf) {
		return NULL;
	}
	sptr = buf;

	for (oid = odb_oidlist_first(olist);
	    oid; oid = odb_oidlist_next(olist)) {
		if (oid) {
			sprintf(sptr, "%08lx,", oid->oid);
			sptr += 9;
		}
	}

	return buf;
}

int
odb_oidlist_append_from_str(odb_oidlist * olist, char *str)
{
	odb_oid oid;
	char *sptr;

	sptr = str;

	while (sptr && *sptr != '\0') {
		oid.oid = strtoul(sptr, &sptr, 16);
		odb_oidlist_add(olist, &oid, 0, NULL);	/* append */
		if (*sptr != '\0')
			sptr++;
	}

	dumplist("fromstr", olist);
	return 0;
}

gint compare_oids(gconstpointer a, gconstpointer b)
{
	/* returns 0 if there is a match (too bad glib isn't consistent *
	 * * here) */
	return (((const odb_oid *)a)->oid ==
	    ((const odb_oid *)b)->oid) ? 0 : !0;
}

int
odb_oidlist_has(odb_oidlist * olist, odb_oid *oid)
{
	GSList *cursor;

	cursor =
	    g_slist_find_custom(olist->list, (gpointer) oid, compare_oids);
	if (cursor) {
		return 1;
	} else {
		return 0;
	}
}

/* kind of odd - a helper function for external callers */
void
codb_free_list(GSList * list)
{
	GSList *p;

	p = list;
	while (p) {
		if (p->data) {
			free(p->data);
		}
		p = g_slist_next(p);
	}

	if (list) {
		g_slist_free(list);
	}
}

/* eof */
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
