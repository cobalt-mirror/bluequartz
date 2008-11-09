/* $Id: classconf_global.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * implements the codb_classconf class defined in classconf.h
 */

#include "cce_common.h"
#include "classconf.h"
#include <stdlib.h>
#include <string.h>
#include "g_hashwrap.h"

struct codb_classconf_struct {
	GHashWrap *typehash;	/* hash{typename} -> typedef */
	GHashWrap *classhash;	/* hash{classname} -> hash{namespace} -> * 
				 * class */
	GHashWrap *rulehash;	/* hash{rulename} -> security rule */
	GHashWrap *matchhash;	/* hash{matchtypename} -> match/sort * * * 
				 * struct */
};

gboolean GHR_rm_typedef(gpointer key, gpointer val, gpointer userdata);
gboolean GHR_rm_class(gpointer key, gpointer val, gpointer userdata);
gboolean GHR_rm_namespacehash(gpointer key, gpointer val,

    gpointer userdata);
gboolean GHR_rm_rulehash(gpointer key, gpointer val, gpointer userdata);
gboolean GHR_rm_matchhash(gpointer key, gpointer val, gpointer userdata);

codb_classconf *
codb_classconf_new(void)
{
	codb_classconf *cc;

	cc = malloc(sizeof(codb_classconf));

	if (cc) {
		cc->typehash =
		    g_hashwrap_new(g_str_hash, g_str_equal, NULL, NULL);
		cc->classhash =
		    g_hashwrap_new(g_str_hash, g_str_equal, NULL, NULL);
		cc->rulehash =
		    g_hashwrap_new(g_str_hash, g_str_equal, NULL, NULL);
		cc->matchhash =
		    g_hashwrap_new(g_str_hash, g_str_equal, NULL, NULL);
		if (!cc->typehash || !cc->classhash || !cc->rulehash ||
		    !cc->matchhash) {
			codb_classconf_destroy(cc);
			return NULL;
		}
		codb_classconf_refresh(cc);
	}
	return (cc);
}

void
codb_classconf_refresh(codb_classconf *cc)
{
	/* FIXME. */
}

codb_class *
codb_classconf_getclass(codb_classconf *cc, const char *name,
    const char *namespace)
{
	GHashWrap *namespacehash;
	codb_class *class;

	/* deal with missing parameters */
	if (!name || !cc)
		return NULL;
	if (!namespace)
		namespace = "";

	/* look up the hash of namespaces */
	namespacehash = g_hashwrap_lookup(cc->classhash, name);
	if (!namespacehash)
		return NULL;

	class = g_hashwrap_lookup(namespacehash, namespace);
	return class;
}

/* return a list of strings of all namespaces */
GSList *
codb_classconf_getnamespaces(codb_classconf *cc, const char *class)
{
	GHashWrap *nshash;
	GSList *retlist = NULL;
	int i;
	int n;

	if (!class || !cc) {
		return NULL;
	}

	/* look up the hash of namespaces */
	nshash = g_hashwrap_lookup(cc->classhash, class);
	if (!nshash)
		return NULL;

	/* for each nspace, enlist it */
	n = g_hashwrap_size(nshash);
	for (i = 0; i < n; i++) {
		gpointer key, val;

		g_hashwrap_index(nshash, i, &key, &val);
		if (strcmp((char *)key, "")) {
			retlist = g_slist_append(retlist, strdup(key));
		}
	}

	return retlist;
}

/* return a list of strings of all classes */
GSList *
codb_classconf_getclasses(codb_classconf *cc)
{
	GSList *retlist = NULL;
	int i;
	int n;

	if (!cc) {
		return NULL;
	}

	/* for each class, enlist it */
	n = g_hashwrap_size(cc->classhash);
	for (i = 0; i < n; i++) {
		gpointer key, val;

		g_hashwrap_index(cc->classhash, i, &key, &val);
		if (strcmp((char *)key, "")) {
			retlist = g_slist_append(retlist, strdup(key));
		}
	}

	return retlist;
}

int
codb_classconf_setclass(codb_classconf *cc, codb_class *class)
{
	const char *classname, *namespace;
	GHashWrap *nspacehash;
	gpointer key, val;

	classname = codb_class_get_name(class);
	namespace = codb_class_get_namespace(class);

	nspacehash = g_hashwrap_lookup(cc->classhash, classname);
	if (!nspacehash) {
		nspacehash =
		    g_hashwrap_new(g_str_hash, g_str_equal, NULL, NULL);
		g_hashwrap_insert(cc->classhash, strdup(classname),
		    nspacehash);
	}

	if (g_hashwrap_lookup_extended(nspacehash, namespace, &key, &val)) {
		if (val != class) {
			/* FIXME: hack for nasty const crap - who returns
			 * * * * * const?! */
			char *tmp = strdup(namespace);

			g_hashwrap_remove(nspacehash, tmp);
			free(tmp);
			codb_class_destroy((codb_class *)val);
			g_hashwrap_insert(nspacehash, strdup(namespace),
			    class);
		} else {
			/* leave it alone */
		}
	} else {
		g_hashwrap_insert(nspacehash, strdup(namespace), class);
	}

	return 0;		/* success */
}

int
codb_classconf_remclass(codb_classconf *cc, char *class, char *namespace)
{
	GHashWrap *nspacehash;
	gpointer nkey, nval, key, val;

	g_hashwrap_lookup_extended(cc->classhash, class, &nkey, &nval);
	nspacehash = (GHashWrap *)nval;
	if (!nspacehash) {
		/* already removed */
		return 0;
	}
	if (namespace && *namespace) {
		/* only remove the namespace specified */
		if (!g_hashwrap_lookup_extended(nspacehash, namespace,
			&key, &val)) {
			/* that namespace has already been removed */
			return 0;
		} else {
			g_hashwrap_remove(nspacehash, namespace);
			codb_class_destroy((codb_class *)val);
			/* FIXME: remove the nspacehash if it is empty */
		}
	} else {
		/* destroy the whole class */
		g_hashwrap_remove(cc->classhash, class);
		g_hashwrap_foreach_remove(nspacehash, GHR_rm_class, NULL);
		g_hashwrap_destroy(nspacehash);
		/* free the key - not part of any other object */
		free((char *)nkey);
	}

	return 0;
}

codb_typedef *
codb_classconf_gettype(codb_classconf *cc, char *name)
{
	return g_hashwrap_lookup(cc->typehash, name);
}

int
codb_classconf_settype(codb_classconf *cc, codb_typedef * type)
{
	gpointer key, val;
	char *typename = codb_typedef_get_name(type);

	if (g_hashwrap_lookup_extended(cc->typehash, typename, &key, &val)) {
		if (type != val) {
			g_hashwrap_remove(cc->typehash, key);
			codb_typedef_destroy((codb_typedef *) val);
		}
	}
	g_hashwrap_insert(cc->typehash, typename, type);
	return 0;		/* success */
}

int
codb_classconf_remtype(codb_classconf *cc, char *name)
{
	return -1;		/* FIXME */
}

codb_matchtype *
codb_classconf_getmatchtype(codb_classconf *cc, const char *name)
{
	return g_hashwrap_lookup(cc->matchhash, name);
}

int
codb_classconf_setmatchtype(codb_classconf *cc, codb_matchtype * mt)
{
	gpointer key, val;
	const char *matchname = codb_matchtype_getname(mt);

	if (g_hashwrap_lookup_extended(cc->matchhash, mt, &key, &val)) {
		if (mt != val) {
			g_hashwrap_remove(cc->matchhash, key);
			codb_matchtype_destroy((codb_matchtype *) val);
			free(key);
		}
	}
	g_hashwrap_insert(cc->matchhash, strdup(matchname), mt);
	return 0;
}

codb_rule *
codb_classconf_getrule(codb_classconf *cc, const char *name)
{
	return g_hashwrap_lookup(cc->rulehash, name);
}

int
codb_classconf_setrule(codb_classconf *cc, codb_rule * rule)
{
	gpointer key, val;
	const char *rulename = codb_rule_getname(rule);

	if (g_hashwrap_lookup_extended(cc->rulehash, rulename, &key, &val)) {
		if (rule != val) {
			g_hashwrap_remove(cc->rulehash, key);
			codb_rule_destroy((codb_rule *) val);
			free(key);
		}
	}
	g_hashwrap_insert(cc->rulehash, strdup(rulename), rule);
	return 0;
}

gboolean
GHR_rm_typedef(gpointer key, gpointer val, gpointer userdata)
{
	codb_typedef_destroy((codb_typedef *) val);
	return TRUE;
}

gboolean
GHR_rm_class(gpointer key, gpointer val, gpointer userdata)
{
	codb_class_destroy((codb_class *)val);
	free((char *)key);
	return TRUE;
}

gboolean
GHR_rm_namespacehash(gpointer key, gpointer val, gpointer userdata)
{
	if (val) {
		g_hashwrap_foreach_remove((GHashWrap *)val, GHR_rm_class,
		    NULL);
		g_hashwrap_destroy((GHashWrap *)val);
	}
	free((char *)key);	/* not part of any other object */
	return TRUE;
}

gboolean
GHR_rm_rulehash(gpointer key, gpointer val, gpointer userdata)
{
	codb_rule_destroy((codb_rule *) val);
	free((char *)key);	/* not part of any other object */
	return TRUE;
}

void
codb_classconf_destroy(codb_classconf *cc)
{
	g_hashwrap_foreach_remove(cc->typehash, GHR_rm_typedef, NULL);
	g_hashwrap_foreach_remove(cc->classhash, GHR_rm_namespacehash,
	    NULL);
	g_hashwrap_foreach_remove(cc->rulehash, GHR_rm_rulehash, NULL);
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
	int i;
	int n;
	GHashWrap *hw;

	hw = codb_class_getproperties(class);
	n = g_hashwrap_size(hw);
	for (i = 0; i < n; i++) {
		const char *typename;
		codb_typedef *td;
		gpointer prop_key, prop_val;

		g_hashwrap_index(hw, i, &prop_key, &prop_val);
		typename =
		    codb_property_get_type((codb_property *)prop_val);
		td = g_hashwrap_lookup(cc->typehash, typename);
		if (td) {
			codb_property_bindtype((codb_property *)prop_val,
			    td);
		} else {
			CCE_SYSLOG("Invalid type \"%s\" for \"%s.%s.%s\"",
			    typename, classname, spacename,
			    (char *)prop_key);
			errors++;
		}
	}

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
	int i, j;
	int n, m;

	n = g_hashwrap_size(cc->classhash);
	for (i = 0; i < n; i++) {
		gpointer class_key, class_val;

		g_hashwrap_index(cc->classhash, i, &class_key, &class_val);

		m = g_hashwrap_size((GHashWrap *)class_val);
		for (j = 0; j < m; j++) {
			int classerrors;
			gpointer space_key, space_val;

			g_hashwrap_index((GHashWrap *)class_val, j,
			    &space_key, &space_val);

			classerrors = bind_types_to_class(cc, class_key,
			    space_key, space_val);
			if (classerrors != 0) {
				errors += classerrors;
				if (!*(char *)space_key) {
					CCE_SYSLOG("Removing class \"%s\"",
					    (char *)class_key);
					codb_classconf_remclass(cc,
					    class_key, space_key);
					break;
				} else {
					CCE_SYSLOG
					    ("Removing class \"%s\", namespace \"%s\"",
					    (char *)class_key,
					    (char *)space_key);
					codb_classconf_remclass(cc,
					    class_key, space_key);
				}
			}
		}
	}
	return errors;
}

/* FIXME: nix this ?? */
codb_ret
codb_classconf_validate(codb_classconf *cc, const char *classname,
    const char *spacename, GHashWrap *attribs, GHashWrap *errs)
{
	codb_class *class;

	class = codb_classconf_getclass(cc, classname, spacename);
	if (!class) {
		/* crap - which part failed */
		if (codb_classconf_getclass(cc, classname, NULL)) {
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
