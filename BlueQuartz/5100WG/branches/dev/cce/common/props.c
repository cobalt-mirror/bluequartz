/* $Id: props.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright (c) 2001-2002 Sun Microsystems, Inc.  All rights reserved. */
#include <stdio.h>
#include <unistd.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>
#include <glib.h>

#include "props.h"
#include "cce_common.h"
#include "cce_types.h"
#include "g_hashwrap.h"

struct props_t {
	GHashWrap *hash;
};

static guint hash_props(gconstpointer key);
static gint compare_props(gconstpointer a, gconstpointer b);
static void clone_props(gpointer key, gpointer val, gpointer *newkey, 
    gpointer *newval);
static void free_props(gpointer key, gpointer value);

props_t *
props_new(void)
{
	props_t *props;

	props = malloc(sizeof(*props));
	if (!props) {
		return NULL;
	}
	
	/* keep this in sync with props_renew */
	props->hash = g_hashwrap_new(hash_props, compare_props, 
		clone_props, free_props);
	if (!props->hash) {
		free(props);
		return NULL;
	}

	return props;
}

void 
props_destroy(props_t *props)
{
	if (!props) {
		return;
	}
	
	g_hashwrap_destroy(props->hash);
	free(props);
}

char *
props_get(props_t *props, const property_t *key)
{
	if (!props || !key) {
		return NULL;
	}
	return (char *)g_hashwrap_lookup(props->hash, key);
}

char *
props_get_str(props_t *props, const char *key)
{
	property_t *prop;
	char *r;

	if (!props || !key) {
		return NULL;
	}
	
	prop = property_from_str(key);
	r = props_get(props, prop);
	property_destroy(prop);

	return r;
}

int 
props_set(props_t *props, property_t *key, char *value)
{
	if (!props || !key) {
		return -EINVAL;
	}

	if (value) {
		g_hashwrap_insert(props->hash, key, value);
	} else {
		g_hashwrap_remove(props->hash, key);
	}

	return 0;
}

int
props_set_str(props_t *props, const char *key, char *value)
{
	property_t *prop;
	int r;

	if (!props || !key) {
		return -EINVAL;
	}
	
	prop = property_from_str(key);
	r = props_set(props, prop, value);
	/* lower level code will have made a copy of this struct */
	property_destroy(prop);

	return r;
}

int 
props_unset(props_t *props, property_t *key)
{
	return props_set(props, key, NULL);
}

int
props_unset_str(props_t *props, char *key)
{
	return props_set_str(props, key, NULL);
}

int 
props_renew(props_t *props)
{
	GHashWrap *newhash;

	if (!props) {
		return -EINVAL;
	}

	/* keep this in sync with props_new */
	newhash = g_hashwrap_new(hash_props, compare_props, 
		clone_props, free_props);
	if (!newhash) {
		return -ENOMEM;
	}
	g_hashwrap_destroy(props->hash);
	props->hash = newhash;

	return 0;
}

int
props_index(props_t *props, int idx, property_t **key, char **value)
{
	if (g_hashwrap_index(props->hash, idx, (gpointer *)key, 
	    (gpointer *)value) < 0) {
		return -EINVAL;
	}
	return 0;
}

props_t *
props_clone(props_t *props)
{
	props_t *newprops;
	int i;

	if (!props) {
		return NULL;
	}

	newprops = props_new();
	if (!newprops) {
		return NULL;
	}

	/* add each entry to the new props */
	for (i = 0; i < props_count(props); i++) {
		property_t *key;
		char *val;

		if (props_index(props, i, &key, &val) != 0 ||
		    props_set(newprops, key, val) != 0) {
			props_destroy(newprops);
			return NULL;
		}
	}

	return newprops;
}

props_t *
props_merge(props_t *base, props_t *mask)
{
	props_t *newprops;
	int i;

	if (!base || !mask) {
		return NULL;
	}

	newprops = props_clone(base);
	if (!newprops) {
		return NULL;
	}

	/* any existing entries will be handled correctly */
	for (i = 0; i < props_count(mask); i++) {
		property_t *key;
		char *val;

		if (props_index(mask, i, &key, &val) != 0 ||
		    props_set(newprops, key, val) != 0) {
			props_destroy(newprops);
			return NULL;
		}
	}

	return newprops;
}

int
props_count(props_t *props)
{
	if (!props) {
		return 0;
	}
	return (int)g_hashwrap_size(props->hash);
}

/* make sure to free the results of this! */
property_t *
property_from_str(const char *str)
{
	property_t *prop;
	char *dup;
	char *p;

	if (!str) {
		return NULL;
	}
	
	prop = malloc(sizeof(*prop));
	dup = strdup(str);
	if (!prop || !dup) {
		CCE_OOM();
	}

	p = strchr(dup, '.');
	if (p) {
		*p++ = '\0';
		prop->namespace = dup;
		prop->property = strdup(p);
		if (!prop->property) {
			CCE_OOM();
		}
	} else {
		prop->namespace = NULL;
		prop->property = dup;
	}
	
	return prop;
}

/* make sure to free the results of this! */
char *
property_to_str(const property_t *prop)
{
	char *str;
	int len = 0;

	if (!prop) {
		return NULL;
	}

	if (prop->namespace) {
		len += strlen(prop->namespace) + 1;
	}
	if (prop->property) {
		len += strlen(prop->property);
	}
	str = malloc(len + 1);
	if (!str) {
		CCE_OOM();
	}
	if (prop->namespace) {
		sprintf(str, "%s.%s", prop->namespace, prop->property);
	} else if (prop->property) {
		sprintf(str, "%s", prop->property);
	} else {
		str[0] = '\0';
	}

	return str;
}

void 
property_destroy(property_t *prop)
{
	if (prop) {
		if (prop->namespace) {
			free(prop->namespace);
		}
		if (prop->property) {
			free(prop->property);
		}
		free(prop);
	}
}

/* helper functions called from g_hashwrap_* */
static guint 
hash_props(gconstpointer key)
{
	const property_t *prop = key;
	guint r = 0;

	if (prop->namespace) {
		r = g_str_hash(prop->namespace);
	}
	if (prop->property) {
		r ^= g_str_hash(prop->property);
	}

	return r;
}

static gint 
compare_props(gconstpointer a, gconstpointer b)
{
	const property_t *propa = a;
	const property_t *propb = b;

	if (!a || !b) {
		return 0;
	}
	
	if (propa->namespace && propb->namespace) {
		/* both namespaces are defined, but they don't match */
		if (strcmp(propa->namespace, propb->namespace)) {
			return 0;
		}
	} else if ((propa->namespace || propb->namespace) && 
	    (!propa->namespace || !propb->namespace)) {
		/* one namespace is defined, the other is not */
		return 0;
	}

	if (propa->property && propb->property) {
		/* both properties are defined, but they don't match */
		if (strcmp(propa->property, propb->property)) {
			return 0;
		}
	} else if ((propa->property || propb->property) && 
	    (!propa->property || !propb->property)) {
		/* one properties is defined, the other is not */
		return 0;
	}

	return 1;
}

static void 
clone_props(gpointer key, gpointer val, gpointer *newkey, gpointer *newval)
{
	if (newkey) {
		property_t *prop = key;
		property_t *newprop;

		newprop = malloc(sizeof(*newprop));
		if (!newprop) {
			CCE_OOM();
		}
		if (prop->namespace) {
			newprop->namespace = strdup(prop->namespace);
			if (!newprop->namespace) {
				CCE_OOM();
			}
		} else {
			newprop->namespace = NULL;
		}
		if (prop->property) {
			newprop->property = strdup(prop->property);
			if (!newprop->property) {
				CCE_OOM();
			}
		} else {
			newprop->property = NULL;
		}
		*newkey = newprop;
	}
	if (newval && val) {
		*newval = strdup(val);
		if (!*newval) {
			CCE_OOM();
		}
	}
}

static void 
free_props(gpointer key, gpointer value)
{
	if (key) {
		property_t *prop = key;
		if (prop->namespace) {
			free(prop->namespace);
		}
		if (prop->property) {
			free(prop->property);
		}
		free(prop);
	}
	if (value) {
		free(value);
	}
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
