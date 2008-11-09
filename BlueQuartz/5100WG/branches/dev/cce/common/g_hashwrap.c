/* $Id: g_hashwrap.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright (c) 2001-2002 Sun Microsystems, Inc.  All rights reserved. */
#include <stdio.h>
#include <unistd.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>
#include <ctype.h>

#include <glib.h>
#include "g_hashwrap.h"

#define HASHWRAP_DEBUG	0
#if HASHWRAP_DEBUG
#define HW_DPRINTF(...)	\
	do { \
		fprintf(stderr, "(%s:%d): ", __FILE__, __LINE__); \
		fprintf(stderr, __VA_ARGS__); \
	} while (0)
#else
#define HW_DPRINTF(...)
#endif

/* helper functions */
static int keys_grow(GHashWrap *hashwrap);
static int keys_rm(GHashWrap *hashwrap, gpointer key);
static int keys_find(GHashWrap *hashwrap, gpointer key);
static void call_free(gpointer key, gpointer value, gpointer wrap);

/*
 * Make a new GHashWrap
 */
GHashWrap *
g_hashwrap_new(GHashFunc hash_func, GCompareFunc compare_func,
    GHCloneFunc clone_func, GHFreeFunc free_func)
{
	GHashWrap *hashwrap;

	hashwrap = malloc(sizeof(*hashwrap));
	if (!hashwrap) {
		return NULL;
	}
	
	/* init all fields or fail - keep in sync with destroy() */
	hashwrap->hash = g_hash_table_new(hash_func, compare_func);
	if (!hashwrap->hash) {
		free(hashwrap);
		return NULL;
	}
	hashwrap->clone_func = clone_func;
	hashwrap->free_func = free_func;
	hashwrap->keys = NULL;
	hashwrap->keys_len = 0;

	HW_DPRINTF("g_hashwrap_new() = %p\n", hashwrap);
	return hashwrap;
}

/*
 * Clean up and free a new GHashWrap
 */
void 
g_hashwrap_destroy(GHashWrap *hashwrap)
{
	if (!hashwrap) {
		return;
	}
	
	HW_DPRINTF("g_hashwrap_destroy(%p)\n", hashwrap);

	/* free all hash entries */
	if (hashwrap->free_func) {
		g_hash_table_foreach(hashwrap->hash, call_free, hashwrap);
	}
	g_hash_table_destroy(hashwrap->hash);
	
	/* free keys array (not contents - they were pointers into the hash */
	if (hashwrap->keys) {
		free(hashwrap->keys);
	}

	/* free the struct itself */
	free(hashwrap);
}

void
g_hashwrap_insert(GHashWrap *hashwrap, gpointer key, gpointer value)
{
	gboolean oldval;
	gpointer k, v;
	int i;

	HW_DPRINTF("g_hashwrap_insert(%p, %p, %p)\n", hashwrap, key, value);
	
	/* check for old entry */
	oldval = g_hash_table_lookup_extended(hashwrap->hash, key, &k, &v);

	/* duplicate the new data, if needed */
	if (hashwrap->clone_func) {
		if (oldval) {
			hashwrap->clone_func(NULL, value, NULL, &value);
			key = k;
			HW_DPRINTF("\tkeeping oldkey    = %p\n", key);
			HW_DPRINTF("\tclone_func(value) = %p\n", value);
		} else {
			hashwrap->clone_func(key, value, &key, &value);
			HW_DPRINTF("\tclone_func(key)   = %p\n", key);
			HW_DPRINTF("\tclone_func(value) = %p\n", value);
		}
	}
		
	if (oldval) {
		/* it existed - use the old index */
		i = keys_find(hashwrap, k);
	} else {
		/* find or make room in the iterator array */
		i = keys_grow(hashwrap);
	}
	
	/* finally, insert the data */
	g_hash_table_insert(hashwrap->hash, key, value);
	hashwrap->keys[i] = key;
	HW_DPRINTF("\tkey index [%d]\n", i);

	/* release any old data AFTER we've finished with hashwrap->hash */
	if (oldval && hashwrap->free_func) {
		HW_DPRINTF("\tfree_func(%p)\n", v);
		hashwrap->free_func(NULL, v);
	}
}

void 
g_hashwrap_remove(GHashWrap *hashwrap, gpointer key)
{
	gboolean oldval;
	gpointer k;
	gpointer v;

	HW_DPRINTF("g_hashwrap_remove(%p, %p)\n", hashwrap, key);

	oldval = g_hash_table_lookup_extended(hashwrap->hash, key, &k, &v);
	if (oldval) {
		keys_rm(hashwrap, k);
		g_hash_table_remove(hashwrap->hash, key);
		/* free the data if requested */
		if (hashwrap->free_func) {
			HW_DPRINTF("\tfree_func(%p)\n", k);
			HW_DPRINTF("\tfree_func(%p)\n", v);
			hashwrap->free_func(k, v);
		}
	}
}

gint
g_hashwrap_index(GHashWrap *hashwrap, gint idx, gpointer *key, gpointer *value)
{
	if (!hashwrap) {
		return -1;
	}

	HW_DPRINTF("g_hashwrap_index(%p, %d)\n", hashwrap, idx);

	/* out-of-bounds check */
	if (idx >= g_hashwrap_size(hashwrap) || idx < 0) {
		if (key) {
			*key = NULL;
		}
		if (value) {
			*value = NULL;
		}
		return -1;
	}

	/* populate requested data */
	if (key) {
		*key = hashwrap->keys[idx];
	}
	if (value) {
		*value = g_hashwrap_lookup(hashwrap, hashwrap->keys[idx]);
	}

	return 0;
}

/* 
 * A generic free_func routine for a hashwrap struct 
 */
void 
g_str_free(gpointer key, gpointer value)
{
	/* g_free() is safe about free(NULL) */
	g_free(key);
	g_free(value);
}

/*
 * A string clone_func routine for a hashwrap_struct
 */
void
g_str_clone(gpointer key, gpointer val, gpointer *newkey, gpointer *newval)
{
	if (newkey) {
		*newkey = key ? g_strdup(key) : NULL;
	}
	if (newval) {
		*newval = val ? g_strdup(val) : NULL;
	}
}


/* make room for a key in a hashwrap struct's keys array */
static int
keys_grow(GHashWrap *hashwrap)
{
	int newsize;
	int count;

	count = g_hashwrap_size(hashwrap);
	if (hashwrap->keys_len <= count) {
		/* we need to make more room */
		if (count == 0) {
			newsize = 4;
		} else {
			newsize = count*2;
		}
		hashwrap->keys = g_realloc(hashwrap->keys, 
		    newsize * sizeof(*hashwrap->keys));
		hashwrap->keys_len = newsize;
	}

	return count;
}

/* remove one and shuffle the iterator array */
static int
keys_rm(GHashWrap *hashwrap, gpointer key)
{
	int i;
	int n;

	i = keys_find(hashwrap, key);
	if (i < 0) {
		return -1;
	}
	n = (hashwrap->keys_len - i) - 1;
	g_memmove(&hashwrap->keys[i], &hashwrap->keys[i+1], 
	    n * sizeof(*hashwrap->keys));
	hashwrap->keys[hashwrap->keys_len - 1] = NULL;

	return 0;
}

static int
keys_find(GHashWrap *hashwrap, gpointer key)
{
	int i;
	int count;

	count = g_hashwrap_size(hashwrap);
	for (i = 0; i < count; i++) {
		if (hashwrap->keys[i] == key) {
			return i;
		}
	}
	return -1;
}

static void
call_free(gpointer key, gpointer value, gpointer wrap)
{
	GHashWrap *hashwrap = (GHashWrap *)wrap;
	HW_DPRINTF("\tfree_func(%p)\n", key);
	HW_DPRINTF("\tfree_func(%p)\n", value);
	hashwrap->free_func(key, value);
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
