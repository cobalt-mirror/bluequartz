/* $Id: security.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <cce_common.h>
#include <codb.h>
#include "codb_handle.h"
#include <codb_security.h>
#include <codb_events.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>
#include <argparse.h>
#include <bool_parse.h>
#include <odb_helpers.h>

static codb_ret can_write_prop(codb_handle *h, oid_t tgt, codb_property *prop);
static codb_ret can_read_prop(codb_handle *h, oid_t tgt, codb_property *prop);

codb_ret
codb_security_can_create(codb_handle *h, const char *classname, oid_t tgt, GHashTable *errs)
{
	const char *acl;
	codb_class *class;
	codb_ret r = CODB_RET_SUCCESS;
	GSList *namespaces, *name;
	codb_event *e;

	/* sysadmin can do anything */
	if (rule_sysadmin(h, tgt, NULL)) {
		return CODB_RET_SUCCESS;
	}

	e = codb_event_new(CREATE, tgt, classname);

	namespaces = codb_classconf_getnamespaces(h->classconf, classname);
	namespaces = g_slist_append(namespaces, "");
	name = namespaces;
	while(name)
	{
		class = codb_classconf_getclass(h->classconf,
			classname, name->data);
		/* read the createacl and run it */
		acl = codb_class_get_createacl(class);

		/* can we create this class? */
		if (acl_run(h, e, acl) != CODB_RET_SUCCESS) {
			g_hash_table_insert(errs, strdup("CREATE"),
				strdup("PERMISSION DENIED"));
			r = CODB_RET_PERMDENIED;
		}
		name = g_slist_next(name);
	}

	codb_event_destroy(e);

	g_slist_free(namespaces);

	return r;
}

codb_ret
codb_security_can_destroy(codb_handle *h, oid_t tgt, GHashTable *errs)
{
	const char *acl;
	codb_class *class;
	codb_ret r = CODB_RET_SUCCESS;
	GSList *namespaces, *name;
	const char *classname;
	codb_event *e;

	/* sysadmin can do anything */
	if (rule_sysadmin(h, tgt, NULL)) {
		return CODB_RET_SUCCESS;
	}

	classname = codb_get_classname(h, tgt);

	e = codb_event_new(DESTROY, tgt, classname);

	namespaces = codb_classconf_getnamespaces(h->classconf, classname);
	namespaces = g_slist_append(namespaces, "");
	name = namespaces;
	while(name)
	{
		class = codb_classconf_getclass(h->classconf,
			classname, name->data);
		/* read the destroyacl and run it */
		acl = codb_class_get_destroyacl(class);

		/* can we create this class? */
		if (acl_run(h, e, acl) != CODB_RET_SUCCESS) {
			g_hash_table_insert(errs, strdup("DESTROY"),
				strdup("PERMISSION DENIED"));
			r = CODB_RET_PERMDENIED;
		}
		name = g_slist_next(name);
	}

	codb_event_destroy(e);

	g_slist_free(namespaces);

	return r;
}

codb_ret
codb_security_read_filter(codb_handle *h, oid_t target, const char *class, 
	const char *nspace, GHashTable *attribs)
{
	codb_class *cc_class;
	GHashTable *cc_props;
	GHashIter *it;
	gpointer key, val;
	GSList *rmlist = NULL;
	GSList *p;

	/* sysadmin can do anything */
	if (rule_sysadmin(h, target, NULL)) {
		return CODB_RET_SUCCESS;
	}

	if (!nspace) {
		nspace = "";
	}

	/* get the classconf class */
	cc_class = codb_classconf_getclass(h->classconf, class, nspace);
	
	/* get the properties */
	cc_props = codb_class_getproperties(cc_class);

	it = g_hash_iter_new(attribs);
	/* for each property in attribs */
	g_hash_iter_first(it, &key, &val);
	while (key) {
		codb_property *p;
		
		if (!codb_is_magic_prop(key)) {
			p = g_hash_table_lookup(cc_props, key);
			if (!p || can_read_prop(h, target, p) 
			 != CODB_RET_SUCCESS) {
				rmlist = g_slist_append(rmlist, key);
			}
		}
		g_hash_iter_next(it, &key, &val);
	}

	p = rmlist;
	while (p) {
		free(g_hash_table_lookup(attribs, p->data));
		g_hash_table_remove(attribs, p->data);
		free(p->data);
		p = g_slist_next(p);
	}
	g_slist_free(rmlist);

	return CODB_RET_SUCCESS;
}

codb_ret
codb_security_write_filter(codb_handle *h, oid_t target, const char *class,
	const char *nspace, GHashTable *attribs, GHashTable *errs)
{
	codb_class *cc_class;
	GHashTable *cc_props;
	GHashIter *it;
	gpointer key, val;
	codb_ret ret = CODB_RET_SUCCESS;
	GSList *rmlist = NULL;
	GSList *p;

	/* sysadmin can do anything */
	if (rule_sysadmin(h, target, NULL)) {
		return CODB_RET_SUCCESS;
	}

	if (!nspace) {
		nspace = "";
	}

	/* get the classconf class */
	cc_class = codb_classconf_getclass(h->classconf, class, nspace);
	
	/* get the properties */
	cc_props = codb_class_getproperties(cc_class);

	it = g_hash_iter_new(attribs);
	/* for each property in attribs */
	g_hash_iter_first(it, &key, &val);
	while (key) {
		codb_property *p;

		if (!codb_is_magic_prop(key)) {
			p = g_hash_table_lookup(cc_props, key);

			if (!p || can_write_prop(h, target, p) != CODB_RET_SUCCESS)
			{
				rmlist = g_slist_append(rmlist, key);
			}
		}
		g_hash_iter_next(it, &key, &val);
	}

	p = rmlist;
	while (p) {
		/* move bad data to bad data list */
		free(g_hash_table_lookup(attribs, p->data));
		g_hash_table_remove(attribs, p->data);
		g_hash_table_insert(errs, p->data, strdup("PERMISSION DENIED"));
		p = g_slist_next(p);
		ret = CODB_RET_PERMDENIED;
	}
	g_slist_free(rmlist);

	return ret;
}

codb_ret
codb_security_can_write_prop(codb_handle *h, oid_t tgt, codb_property *prop)
{
	/* sysadmin can do anything */
	if (rule_sysadmin(h, tgt, NULL)) {
		return CODB_RET_SUCCESS;
	}

	return can_write_prop(h, tgt, prop);
}

codb_ret
codb_security_can_read_prop(codb_handle *h, oid_t tgt, codb_property *prop)
{
	/* sysadmin can do anything */
	if (rule_sysadmin(h, tgt, NULL)) {
		return CODB_RET_SUCCESS;
	}

	return can_read_prop(h, tgt, prop);
}

static codb_ret
can_write_prop(codb_handle *h, oid_t tgt, codb_property *prop)
{
	const char *acl;
	codb_ret r = CODB_RET_PERMDENIED;
	codb_event *e;

	/* read the writeacl and run it */
	acl = codb_property_get_writeacl(prop);
	e = codb_event_new(MODIFY, tgt, codb_property_get_name(prop));

	/* can we write this property? */
	if (acl_run(h, e, acl) == CODB_RET_SUCCESS) {
		r = CODB_RET_SUCCESS;
	}
	codb_event_destroy(e);

	return r;
}

static codb_ret
can_read_prop(codb_handle *h, oid_t tgt, codb_property *prop)
{
	const char *acl;
	codb_ret r = CODB_RET_PERMDENIED;

	/* read the readacl and run it */
	acl = codb_property_get_readacl(prop);

	/* can we read this property? defaults say only ruleUser*/
	if (!acl || !*acl) {
		if (rule_user(h, tgt, NULL)) {
			r = CODB_RET_SUCCESS;
		}
	} else {
		codb_event *e;
		e = codb_event_new(READ, tgt, codb_property_get_name(prop));
		if (acl_run(h, e, acl) == CODB_RET_SUCCESS) {
			r = CODB_RET_SUCCESS;
		}
		codb_event_destroy(e);
	}

	return r;
}

/* 1 for success, 0 on permission denied */
/* can handle p containing ','s - treats them like ORs just like before,
 * but in the current codeflow should never be called like that
 * - the boolean parser should get them
 */
static int
acl_expr_node_run(codb_handle *h, codb_event *e, const char *p)
{
	int ret = 1;
	codb_rule *rule;
	char *function = NULL;
	GSList *args = NULL;
	int argcount;
	const char *next;

	while (p && *p) {
		/* spin initial whitespace and commas */
		while (isspace(*p) || *p == ',') {
			p++;
		}

		if (arg_parse(p, &function, &args, &argcount, &next)) {
			CCE_SYSLOG("failed to parse acl rule \"%s\"", p);
		}

		/* lookup function in classconf */
		rule = codb_classconf_getrule(h->classconf, function);
		if (!rule) {
			/* TODO: catch this error at runtime */
			CCE_SYSLOG("acl rule \"%s\" is unknown", function);
			ret = 0;
		} else {
			if (!codb_rule_check(rule, h, e, args)) {
				DPRINTF(DBG_CODB, "acl rule \"%s\" failed\n", function);
				ret = 0;
			}
		}
		/* cleanup after parser */
		if (function) {
			free(function);
		}
		if (args) {
			free_arglist(args);
		}
		p = next;
	}
	return ret;
}

/* 1 for success, 0 on permission denied */
static int
acl_node_run(codb_handle *h, codb_event *e, struct bool_node *node)
{
	int ret;

	switch(node->op) {
		case BOOL_EXPR:
			ret = acl_expr_node_run(h, e, node->data);
			break;
		case BOOL_OR:
			ret = acl_node_run(h, e, node->left)
			    || acl_node_run(h, e, node->right);
			break;
		case BOOL_AND:
			ret = acl_node_run(h, e, node->left)
			    && acl_node_run(h, e, node->right);
			break;
		case BOOL_NONE:
		default:
			ret = 0;
	}

	if (node->not) {
		ret = !ret;
	}

	return ret;
}

static void
acl_node_free(struct bool_node *node)
{
	if (node->left) {
		acl_node_free(node->left);
	}
	if (node->right) {
		acl_node_free(node->right);
	}
	if (node->data) {
		free(node->data);
	}
	free(node);
}

codb_ret
acl_run(codb_handle *h, codb_event *e, const char *acl)
{
	codb_ret ret = CODB_RET_SUCCESS;
	struct bool_node *root;

	if (!acl || !*acl) {
		/* shouldn't really get here, but if it does, deny */
		return CODB_RET_PERMDENIED;
	}

	bool_scan(acl);
	root = bool_parse();
	bool_terminate();
	if (acl_node_run(h, e, root)) {
		ret = CODB_RET_SUCCESS;
	} else {
		ret = CODB_RET_PERMDENIED;
	}
	/* cleanup the tree */
	acl_node_free(root);
	return ret;
}

/**************************************************************************
 * Builtin rule functions                                                 *
 **************************************************************************/
int
rule_sysadmin(codb_handle *h, oid_t target, GSList *args)
{
	cce_scalar *sc;
	codb_ret ret;
	odb_oid myoid;
	int retval = 0;

	if (codb_handle_getflags(h) & CODBF_ADMIN) {
		return 1;
	}

	if (h->cur_oid == 0) {
		return 0;
	}

	myoid.oid = h->cur_oid;
	sc = cce_scalar_new_undef();

	ret = odb_txn_get(h->txn, &myoid, ".systemAdministrator", sc);
	if (ret == CODB_RET_SUCCESS && !cce_scalar_isdefined(sc)) {
		codb_getdefval(h, &myoid, ".systemAdministrator", &sc);
	}

	/* check the boolean: "" or "0" are false (Perl is braindead) */
	if (ret == CODB_RET_SUCCESS) {
		if (cce_scalar_isdefined(sc) 
		 && strcmp("", (char *)sc->data)
		 && strcmp("0", (char *)sc->data)) {
			retval = 1;
		}
	}

	cce_scalar_destroy(sc);

	return retval;
}

int
rule_all(codb_handle *h, oid_t target, GSList *args)
{
	return 1;
}

int
rule_user(codb_handle *h, oid_t target, GSList *args)
{
	if (h->cur_oid != 0) {
		return 1;
	}

	return 0;
}

int
rule_self(codb_handle *h, oid_t target, GSList *args)
{
	if (h->cur_oid != 0 && h->cur_oid == target) {
		return 1;
	}

	return 0;
}

int
rule_capable(codb_handle *h, oid_t target, GSList *args)
{
	cce_scalar *sc;
	int ret = 0;
	odb_oid myoid;
	const char *capname;

	if (h->cur_oid == 0 || !args) {
		return 0;
	}

	capname = args->data;
	myoid.oid = h->cur_oid;

	sc = cce_scalar_new_undef();
	ret = odb_txn_get(h->txn, &myoid, ".capabilities", sc);
	if (ret == CODB_RET_SUCCESS && !cce_scalar_isdefined(sc)) {
		codb_getdefval(h, &myoid, ".capabilities", &sc);
	}

	if (ret == CODB_RET_SUCCESS) {
		size_t capnamelen = strlen(capname);
		char *buf = malloc(capnamelen+3);
		sprintf(buf, "&%s&", capname);

		/* check the array */
		if (cce_scalar_isdefined(sc)
		    && strstr(cce_scalar_string(sc), buf)) {
			ret = 1;
		}
		free(buf);
	}
	cce_scalar_destroy(sc);

	return ret;
}

int
rule_propsmatch(codb_handle *h, oid_t target, GSList *args)
{
	cce_scalar *sc;
	cce_scalar *sc2;
	int ret = 0;
	odb_oid myoid;
	odb_oid targetoid;
	const char *propname;
	const char *propname2;

	if (h->cur_oid == 0 || !args || !(args->next)) {
		return 0;
	}

	propname = args->data;
	propname2 = args->next->data;
	myoid.oid = h->cur_oid;
	targetoid.oid = target;

	/* get property one */
	sc = cce_scalar_new_undef();
	ret = odb_txn_get(h->txn, &myoid, propname, sc);
	if (ret == CODB_RET_SUCCESS && !cce_scalar_isdefined(sc)) {
		codb_getdefval(h, &myoid, propname, &sc);
	}

	if (ret != CODB_RET_SUCCESS) {
		cce_scalar_destroy(sc);
		return 0;
	}

	/* get property two */
	sc2 = cce_scalar_new_undef();
	ret = odb_txn_get(h->txn, &targetoid, propname2, sc2);
	if (ret == CODB_RET_SUCCESS && !cce_scalar_isdefined(sc2)) {
		codb_getdefval(h, &targetoid, propname2, &sc2);
	}

	if (ret != CODB_RET_SUCCESS) {
		cce_scalar_destroy(sc);
		cce_scalar_destroy(sc2);
		return 0;
	}

	/* check the properties */
	if (cce_scalar_isdefined(sc) && cce_scalar_isdefined(sc2)
		&& cce_scalar_compare(sc, sc2) == 0) {
		ret = 1;
	}

	cce_scalar_destroy(sc);
	cce_scalar_destroy(sc2);

	return ret;
}

int
rule_propvalue(codb_handle *h, oid_t target, GSList *args)
{
	cce_scalar *sc;
	int ret = 0;
	odb_oid targetoid;
	const char *propname;
	const char *value;

	if (h->cur_oid == 0 || !args || !args->next) {
		return 0;
	}

	propname = args->data;
	value = args->next->data;
	targetoid.oid = target;

	/* get property */
	sc = cce_scalar_new_undef();
	ret = odb_txn_get(h->txn, &targetoid, propname, sc);
	if (ret == CODB_RET_SUCCESS && !cce_scalar_isdefined(sc)) {
		codb_getdefval(h, &targetoid, propname, &sc);
	}

	if (ret != CODB_RET_SUCCESS) {
		cce_scalar_destroy(sc);
		return 0;
	}

	/* check if string 1 */
	if (cce_scalar_isdefined(sc)
		&& !strcmp(cce_scalar_string(sc), value)) {
		ret = 1;
	}

	cce_scalar_destroy(sc);

	return ret;
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
