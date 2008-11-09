/* $Id: cce_props.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright (c) 2001-2002 Sun Microsystems, Inc.  All rights reserved. */
#include <stdio.h>
#include <unistd.h>
#include <stdlib.h>
#include <errno.h>
#include <glib.h>

#include "cce.h"
#include "g_hashwrap.h"

/*
 * FIXME:
 *
 * cce_props_* rely on glib's default behavior of never failing to allocate
 * memory.  By default glib will kill the app if it can't malloc().
 * This doesn't make for a very nice API to user-apps that don't expect it.
 *
 * Perhaps we really want cce_props_t to be our own hash library?
 */

struct cce_props_t {
	GHashWrap *hash;
};

/*
 * Allocate and initialize a new cce_props_t
 * NOTE: allocates memory which must be cce_props_destroy()ed
 */
cce_props_t *
cce_props_new(void)
{
	cce_props_t *props;

	props = malloc(sizeof(*props));
	if (!props) {
		return NULL;
	}
	
	props->hash = g_hashwrap_new(g_str_hash, g_str_equal,
		g_str_clone, g_str_free);
	if (!props->hash) {
		free(props);
		return NULL;
	}

	return props;
}

/*
 * Clean up and deallocate a cce_props_t
 */
void
cce_props_destroy(cce_props_t *props)
{
	if (!props) {
		return;
	}
	
	g_hashwrap_destroy(props->hash);
	free(props);
}

/*
 * Fetch the value of a property
 * NOTE: do not free() the returned value
 */
char *
cce_props_get(cce_props_t *props, const char *key)
{
	if (!props || !key) {
		return NULL;
	}
	return (char *)g_hashwrap_lookup(props->hash, key);
}

/*
 * Set the value of a property
 * NOTE: key and value are internally duplicated, the caller may free them
 */
cce_err_t
cce_props_set(cce_props_t *props, char *key, char *value)
{
	if (!props || !key) {
		return CCE_EINVAL;
	}

	if (value) {
		g_hashwrap_insert(props->hash, key, value);
	} else {
		g_hashwrap_remove(props->hash, key);
	}

	return CCE_OK;
}

/*
 * Unset (remove) a property
 */
cce_err_t
cce_props_unset(cce_props_t *props, char *key)
{
	return cce_props_set(props, key, NULL);
}

/*
 * Re-initialize a cce_props_t
 */
cce_err_t
cce_props_renew(cce_props_t *props)
{
	GHashWrap *newhash;

	if (!props) {
		return CCE_EINVAL;
	}

	newhash = g_hashwrap_new(g_str_hash, g_str_equal,
		g_str_clone, g_str_free);
	if (!newhash) {
		return CCE_ENOMEM;
	}
	g_hashwrap_destroy(props->hash);
	props->hash = newhash;

	return CCE_OK;
}

/*
 * Fetch the key/value pair at a specific index
 * NOTE: do not free() the returned pointers in key or value
 */
cce_err_t
cce_props_index(cce_props_t *props, int idx, char **key, char **value)
{
	if (g_hashwrap_index(props->hash, idx, (gpointer *)key,
	    (gpointer *)value) < 0) {
		return CCE_EINVAL;
	}
	return CCE_OK;
}
	
/*
 * Duplicate a cce_props_t
 * NOTE: allocates memory which must be cce_props_destroy()ed
 */
cce_props_t *
cce_props_clone(cce_props_t *props)
{
	cce_props_t *newprops;
	int i;

	if (!props) {
		return NULL;
	}

	newprops = cce_props_new();
	if (!newprops) {
		return NULL;
	}

	/* add each entry to the new props */
	for (i = 0; i < cce_props_count(props); i++) {
		char *key;
		char *val;

		if (cce_props_index(props, i, &key, &val) != CCE_OK ||
		    cce_props_set(newprops, key, val) != CCE_OK) {
			cce_props_destroy(newprops);
			return NULL;
		}
	}

	return newprops;
}

/*
 * Merge two cce_props_t into one new one
 * NOTE: allocates memory which must be cce_props_destroy()ed
 */
cce_props_t *
cce_props_merge(cce_props_t *base, cce_props_t *mask)
{
	cce_props_t *newprops;
	int i;

	if (!base || !mask) {
		return NULL;
	}

	newprops = cce_props_clone(base);
	if (!newprops) {
		return NULL;
	}

	/* any existing entries will be handled correctly */
	for (i = 0; i < cce_props_count(mask); i++) {
		char *key;
		char *val;

		if (cce_props_index(mask, i, &key, &val) != CCE_OK ||
		    cce_props_set(newprops, key, val) != CCE_OK) {
			cce_props_destroy(newprops);
			return NULL;
		}
	}

	return newprops;
}

/*
 * Return the number of properties in a cce_props_t
 */
int
cce_props_count(cce_props_t *props)
{
	if (!props) {
		return 0;
	}
	return (int)g_hashwrap_size(props->hash);
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
