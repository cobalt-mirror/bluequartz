/* $Id: classconf_class.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * implements the codb_class object defined in codb_classconf.h
 */

#include <cce_common.h>
#include <codb_classconf.h>
#include <stdlib.h>
#include <string.h>

#define ERRTAG_INVALID_PROPERTY		"[[base-cce.unknownAttr]]"
#define ERRTAG_INVALID_TYPE			"[[base-cce.unknownType]]"
#define ERRTAG_INVALID_DATA			"[[base-cce.invalidData]]"

struct codb_class_struct {
	char *name;
	char *namespace;
	char *version;
	char *createacl;
	char *destroyacl;
	GHashTable *properties;
};

codb_class *
codb_class_new(char *name, char *namespace, char *version, char *createacl,
	char *destroyacl)
{
	codb_class *class;
	if (!namespace) { namespace = ""; }
	if (!version) { version = ""; }
	class = malloc(sizeof(codb_class));
	if (class) {
		int fail = 0;
		#define ASSIGN(x)		\
			if (x) { class->x = strdup(x); if (!class->x) fail++; } \
			else { class->x = NULL; }
		ASSIGN(name);
		ASSIGN(namespace);
		ASSIGN(version);
		ASSIGN(createacl);
		ASSIGN(destroyacl);
		#undef ASSIGN
		class->properties = g_hash_table_new(g_str_hash, g_str_equal);
		if (!class->name || !class->properties || fail) {
			codb_class_destroy(class);
			class = NULL;
		}
	}
	return (class);
}

const char *
codb_class_get_name (codb_class *class)
{
	if (!class) {
		return NULL;
	}
	return class->name;
}

const char *
codb_class_get_namespace (codb_class *class)
{
	if (!class) {
		return NULL;
	}
	return class->namespace;
}

const char *
codb_class_get_version(codb_class *class)
{
	if (!class) {
		return NULL;
	}
	return class->version;
}

const char *
codb_class_get_createacl(codb_class *class)
{
	if (!class) {
		return NULL;
	}
	return class->createacl;
}

const char *
codb_class_get_destroyacl(codb_class *class)
{
	if (!class) {
		return NULL;
	}
	return class->destroyacl;
}

int
codb_class_addindex(codb_class *class, codb_index *index)
{
	gpointer key, val;
	codb_property *prop;

	if (!g_hash_table_lookup_extended(class->properties,
		codb_index_get_property(index), &key, &val))
	{
		CCE_SYSLOG("index %s attached to invalid property",
			codb_index_get_name(index));
		codb_index_destroy(index);
		return CODB_RET_BAD_HANDLE;
	}
	prop = (codb_property *)val;

	codb_property_addindex(prop, index);
	return CODB_RET_SUCCESS;
}

int
codb_class_addproperty(codb_class *class, codb_property *prop)
{
	gpointer key, val;
	
	/* check for existing entry */
	if (g_hash_table_lookup_extended(
		class->properties, 
		codb_property_get_name(prop),
		&key, &val))
	{
		/* remove and free existing entry */
		g_hash_table_remove(class->properties, key); 
		free(key);
		codb_property_destroy((codb_property*)val);
	}
	
	/* insert new property */
	g_hash_table_insert( class->properties,
		strdup(codb_property_get_name(prop)), prop);
	
	return 0; /* success */
}

GHashTable *
codb_class_getproperties(codb_class *class)
{
	if (!class) {
		return NULL;
	}
	return class->properties;
}

gboolean
GHR_remove_property(gpointer key, gpointer val, gpointer user_data)
{
	codb_property_destroy((codb_property*)val);
	free(key);
	return TRUE; /* remove from GHashTable */
}

/* destructor: destroys class and all properties that have been added
 * to class as well. */
void
codb_class_destroy(codb_class *class)
{
	/* destroy all properties */
	g_hash_table_freeze(class->properties);
	g_hash_table_foreach_remove(class->properties, GHR_remove_property, NULL);
	g_hash_table_thaw(class->properties);
	g_hash_table_destroy(class->properties);

	/* destroy all attributes */
	#define MYFREE(x) if (class->x) free(class->x)
	MYFREE(name);
	MYFREE(namespace);
	MYFREE(version);
	MYFREE(createacl);
	MYFREE(destroyacl);
	#undef MYFREE

	/* free object */
	free(class);
}

#include <stdio.h>

codb_ret
codb_class_validate(codb_class *class, GHashTable *attribs, GHashTable *errs)
{
	int errors = 0;
	GHashIter *it;
	gpointer key, val;
	codb_typedef *td;
	codb_property *prop;
	codb_ret ret;

	/* no attribs must pass validation */
	if (!attribs) {
		return CODB_RET_SUCCESS;
	}

	it = g_hash_iter_new(attribs);
	for (g_hash_iter_first(it, &key, &val); 
		key;
		g_hash_iter_next(it, &key, &val))
	{
		/* skip metadata */
		if (codb_is_magic_prop(key)) continue;
		
		/* look up the property */
		prop = g_hash_table_lookup(class->properties, key);
		if (!prop) {
			errors++;
			if (errs) {
				g_hash_table_insert(errs, strdup(key), 
					strdup(ERRTAG_INVALID_PROPERTY));
			}
			continue;
		}
		
		/* see if a blank string is OK */
		if (codb_property_get_optional(prop) && !strcmp("", val)) {
			continue;
		}

		/* look up the type */
		td = codb_property_get_typedef(prop);
		if (!td) {
			errors++;
			if (errs) {
				g_hash_table_insert(errs, strdup(key), 
					strdup(ERRTAG_INVALID_TYPE));
			}
			continue;
		}
		
		/* validate the data according to type */
		if (codb_property_get_array(prop)) {
			ret = codb_typedef_validate_array(td, (char *)val);
		} else {
			ret = codb_typedef_validate(td, (char *)val);
		}
		if (ret != CODB_RET_SUCCESS) {
			if (errs) {
				/* did not match */
				char *errstr;

				errors++;
				errstr = codb_typedef_get_errmsg(td);
				if (!errstr || !errstr[0]) {
					errstr = ERRTAG_INVALID_DATA;
				}
				g_hash_table_insert(errs, strdup(key), strdup(errstr));
			}
		}
	}
	
	if (errors) {
		return CODB_RET_BADDATA;
	} else {
		return CODB_RET_SUCCESS;
	}
}

codb_property *
codb_class_get_property(codb_classconf *cc, const char *classname,
	const char *propstr)
{
	char *keybuf;
	char *namespace;
	char *propname;
	GHashTable *props;
	codb_class *class;
	codb_property *prop;

	/* working copy */
	keybuf = strdup(propstr);

	/* pre-process the key */
	if ((propname = strchr(keybuf, '.'))) {
		*propname = '\0';
		namespace = keybuf;
		propname++;
	} else {
		namespace = "";
		propname = keybuf;
	}

	class = codb_classconf_getclass(cc, classname, namespace);
	if (!class) {
		free(keybuf);
		return NULL;
	}
	props = codb_class_getproperties(class);
	if (!props) {
		free(keybuf);
		return NULL;
	}
	prop = g_hash_table_lookup(props, propname);
	free(keybuf);
	return prop;
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
