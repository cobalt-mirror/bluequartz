/* $Id: classconf.c 3 2003-07-17 15:19:15Z will $
 *
 * implements the codb_classconf class defined in codb_classconf.h
 */

#include <cce_common.h>
#include <codb_debug.h>

#include <codb_classconf.h>
#include <stdlib.h>
#include <string.h>

struct codb_classconf_struct {
	GHashTable *typehash; /* hash{typename} -> typedef */
	GHashTable *classhash; /* hash{classname} -> hash{namespace} -> class */
};

gboolean GHR_rm_typedef(gpointer key, gpointer val, gpointer userdata);
gboolean GHR_rm_class(gpointer key, gpointer val, gpointer userdata);
gboolean GHR_rm_namespacehash(gpointer key, gpointer val, gpointer userdata);

codb_classconf *
codb_classconf_new (void)
{
	codb_classconf *cc;
	cc = malloc(sizeof(codb_classconf));
	if (cc) {
		cc->typehash = g_hash_table_new(g_str_hash, g_str_equal);
		cc->classhash = g_hash_table_new(g_str_hash, g_str_equal);
		if (!cc->typehash || !cc->classhash) {
			codb_classconf_destroy(cc);
			return NULL;
		}
		codb_classconf_refresh(cc);
	}
	return(cc);
}

void
codb_classconf_refresh(codb_classconf *cc)
{
	/* FIXME. */
}

codb_class *
codb_classconf_getclass(codb_classconf *cc, char *name, char *namespace)
{
	GHashTable *namespacehash;
	codb_class *class;

	/* deal with missing parameters */
	if (!name || !cc) 
		return NULL;
	if (!namespace) 
		namespace = "";
	
	/* look up the hash of namespaces */
	namespacehash = g_hash_table_lookup(cc->classhash, name);
	if (!namespacehash) 
		return NULL;
	
	class = g_hash_table_lookup(namespacehash, namespace);
	return class;
}

/* return a list of strings of all namespaces */
GSList *
codb_classconf_getnamespaces(codb_classconf *cc, char *class)
{
	GHashTable *nshash;
	GSList *retlist = NULL;
	gpointer key, val;
	GHashIter *it;

	if (!class || !cc) {
		return NULL;
	}

	/* look up the hash of namespaces */
	nshash = g_hash_table_lookup(cc->classhash, class);
	if (!nshash) 
		return NULL;
	
	it = g_hash_iter_new(nshash);
	if (!it) {
		return NULL;
	}

	/* for each nspace, enlist it */
	g_hash_iter_first(it, &key, &val);
	while (key) {
		if (strcmp((char *)key, "")) {
			retlist = g_slist_append(retlist, strdup(key));
		}
		g_hash_iter_next(it, &key, &val);
	}

	g_hash_iter_destroy(it);

	return retlist;
}

/* return a list of strings of all classes */
GSList *
codb_classconf_getclasses(codb_classconf *cc)
{
	GSList *retlist = NULL;
	gpointer key, val;
	GHashIter *it;

	if (!cc) {
		return NULL;
	}

	it = g_hash_iter_new(cc->classhash);
	if (!it) {
		return NULL;
	}

	/* for each class, enlist it */
	g_hash_iter_first(it, &key, &val);
	while (key) {
		if (strcmp((char *)key, "")) {
			retlist = g_slist_append(retlist, strdup(key));
		}
		g_hash_iter_next(it, &key, &val);
	}

	g_hash_iter_destroy(it);

	return retlist;
}
	
int
codb_classconf_setclass(codb_classconf *cc, codb_class *class)
{
	char *classname, *namespace;
	GHashTable *nspacehash;
	gpointer key, val;
	
	classname = codb_class_get_name(class);
	namespace = codb_class_get_namespace(class);
	
	nspacehash = g_hash_table_lookup(cc->classhash, classname);
	if (!nspacehash) {
		nspacehash = g_hash_table_new(g_str_hash, g_str_equal);
		g_hash_table_insert(cc->classhash, strdup(classname), nspacehash);
	}
	
	if (g_hash_table_lookup_extended(nspacehash, namespace, &key, &val)) {
		if (val != class) {
			g_hash_table_remove(nspacehash, namespace);
			codb_class_destroy((codb_class*)val);
			g_hash_table_insert(nspacehash, namespace, class);
		} else {
			/* leave it alone */
		}
	} else {
		 g_hash_table_insert(nspacehash, namespace, class);
	}
	
	return 0; /* success */
} 

int codb_classconf_remclass(codb_classconf *cc, char *class, char *namespace)
{
	GHashTable *nspacehash;
	gpointer nkey, nval, key, val;

	g_hash_table_lookup_extended(cc->classhash, class, &nkey, &nval);
	nspacehash = (GHashTable*)nval;
	if (!nspacehash) {
		/* already removed */
		return 0;
	}
	if (namespace && *namespace) {
		/* only remove the namespace specified */
		if (!g_hash_table_lookup_extended(nspacehash, namespace, &key, &val)) {
			/* that namespace has already been removed */
			return 0;
		} else {
			g_hash_table_remove(nspacehash, namespace);
			codb_class_destroy((codb_class*)val);
			/* FIXME: remove the nspacehash if it is empty */
		}
	} else {
		/* destroy the whole class */
		g_hash_table_remove(cc->classhash, class);
		g_hash_table_foreach_remove(nspacehash, GHR_rm_class, NULL);
		g_hash_table_destroy(nspacehash);
		/* free the key - not part of any other object */
		free((char*)nkey);
	}
			
	return 0;
}

codb_typedef *
codb_classconf_gettype(codb_classconf *cc, char *name)
{
	return g_hash_table_lookup(cc->typehash, name);
}

int
codb_classconf_settype(codb_classconf *cc, codb_typedef *type)
{
	gpointer key, val;
	char *typename = codb_typedef_get_name(type);

	if (g_hash_table_lookup_extended(cc->typehash, type, &key, &val)) {
		if (type != val) {
			g_hash_table_remove(cc->typehash, key);
			codb_typedef_destroy((codb_typedef*)val);
		}
	}
	g_hash_table_insert(cc->typehash, typename, type);
	return 0; /* success */
}

int
codb_classconf_remtype(codb_classconf *cc, char *name)
{
	return -1; /* FIXME */
}

gboolean
GHR_rm_typedef(gpointer key, gpointer val, gpointer userdata)
{
	codb_typedef_destroy((codb_typedef*)val);
	return TRUE;
}

gboolean
GHR_rm_class(gpointer key, gpointer val, gpointer userdata)
{
	codb_class_destroy((codb_class*)val);
	/* they key doesn't need to be destroyed, it's part of the class obj. */
	return TRUE;
}

gboolean
GHR_rm_namespacehash(gpointer key, gpointer val, gpointer userdata)
{
	if (val) {
		g_hash_table_foreach_remove((GHashTable*)val, GHR_rm_class, NULL);
		g_hash_table_destroy((GHashTable*)val);
	}
	free((char*)key); /* not part of any other object */
	return TRUE;
}

void
codb_classconf_destroy(codb_classconf *cc)
{
	g_hash_table_foreach_remove(cc->typehash, GHR_rm_typedef, NULL);
	g_hash_table_foreach_remove(cc->classhash, GHR_rm_namespacehash, NULL);
	free(cc);
}

/* scans a class for properties, checks the type for each property, and
 * updates the internal pointers accordingly.
 */
int
bind_types_to_class(codb_classconf *cc, char *classname, char *spacename,
	codb_class *class)
{
	int errors = 0;
	GHashIter *prop_it;
	gpointer prop_key, prop_val;
	
	prop_it = g_hash_iter_new(codb_class_getproperties(class));
	for (g_hash_iter_first(prop_it, &prop_key, &prop_val);
		prop_key;
		g_hash_iter_next(prop_it, &prop_key, &prop_val))
	{
		char *typename;
		codb_typedef *td;
		typename = codb_property_get_type((codb_property*)prop_val);
		td = g_hash_table_lookup(cc->typehash, typename);
		if (td) {
			codb_property_bindtype((codb_property*)prop_val, td);
		} else {
			CCE_SYSLOG("Invalid type \"%s\" for \"%s.%s.%s\"",
				typename, classname, spacename, (char*)prop_key);
			errors++;
		}
	}
	g_hash_iter_destroy(prop_it);

	return errors;
}

/* bindtypes: verifies that every class property has a valid type, and
 * updates the internal pointers appropriately. Returns # of errors
 * encountered. 
 *
 * This approach is preferable to on-demand binding because all errors get
 * detected at initialization time, rather than well into runtime.
 */
int
codb_classconf_bindtypes(codb_classconf *cc)
{
	int errors = 0;
	GHashIter *class_it, *space_it;
	gpointer class_key, class_val, space_key, space_val;
	
	class_it = g_hash_iter_new (cc->classhash);
	for (g_hash_iter_first(class_it, &class_key, &class_val);
	 class_key;
	 g_hash_iter_next(class_it, &class_key, &class_val)) {
		space_it = g_hash_iter_new((GHashTable*)class_val);
		for (g_hash_iter_first(space_it, &space_key, &space_val);
		 space_key;
		 g_hash_iter_next(space_it, &space_key, &space_val)) {
			int classerrors;
			classerrors = bind_types_to_class(cc, class_key, 
				space_key, space_val);
			if (classerrors != 0) {
				errors += classerrors;
				if (!*(char*)space_key) {
					CCE_SYSLOG("Removing class \"%s\"",
						(char *)class_key);
					codb_classconf_remclass(cc, class_key,
						space_key);
					break;
				} else {
					CCE_SYSLOG("Removing class \"%s\", namespace \"%s\"",
						(char *)class_key, 
						(char *)space_key);
					codb_classconf_remclass(cc, class_key,
						space_key);
				}
			}
		}
		g_hash_iter_destroy(space_it);
	}
	g_hash_iter_destroy(class_it);
	return errors;
}

#include <stdio.h>

codb_ret
codb_classconf_validate(codb_classconf *cc, char *classname, char *spacename,
	GHashTable *attribs, GHashTable *errs)
{
	codb_class *class;

	class = codb_classconf_getclass(cc, classname, spacename);
	if (!class) { 
		/* crap - which part failed */
		if (codb_classconf_getclass(cc, classname,  NULL)) {
			/* class exists, but not namespace */
			return CODB_RET_UNKNSPACE;
		}
		return CODB_RET_UNKCLASS; 
	}
	
	return codb_class_validate(class, attribs, errs);
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
