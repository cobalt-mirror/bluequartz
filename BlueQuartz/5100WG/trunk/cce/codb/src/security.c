/* $Id: security.c 3 2003-07-17 15:19:15Z will $ */

#include <cce_common.h>
#include <codb_debug.h>

#include <codb.h>
#include "codb_handle.h"
#include <codb_security.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>

struct aclrule {
	char *acl_name;
	int (*acl_fn)(codb_handle *h, oid_t target, char *arg);
	char *arg;
};

static int rule_sysadmin(codb_handle *h, oid_t target, char *arg);
static int rule_user(codb_handle *h, oid_t target, char *arg);
static int rule_self(codb_handle *h, oid_t target, char *arg);
static int rule_capable(codb_handle *h, oid_t target, char *arg);
static GSList *acl_parse(char *acl);
static void acl_free(GSList *rules);
static int acl_access_ok(codb_handle *h, oid_t target, GSList *rules);
static int rule_access_ok(codb_handle *h, oid_t target, struct aclrule *r);
static codb_ret can_write_prop(codb_handle *h, oid_t tgt, codb_property *prop);
static codb_ret can_read_prop(codb_handle *h, oid_t tgt, codb_property *prop);
static codb_ret can_create_nspace(codb_handle *h, char *class, char *nspace);
static codb_ret can_destroy_nspace(codb_handle *h, oid_t target, char *class, char *nspace);

static struct aclrule rulenone = { "ruleNone", NULL, NULL };
static struct aclrule rule_list[] = {
	{ "ruleAll", NULL, NULL }, 	/* null fn passes as 'true' */
	{ "ruleUser", rule_user, NULL },
	{ "ruleSelf", rule_self, NULL  },
	{ "ruleAdmin", rule_sysadmin, NULL },
	{ "ruleCapable", rule_capable, NULL },
	{ NULL, NULL, NULL }
};

codb_ret
codb_security_can_create(codb_handle *h, char *class)
{
	GSList *namespaces, *names_i;
	codb_ret retval = CODB_RET_SUCCESS;
	char *name;

	/* See if we are god */
	if (rule_sysadmin(h, (oid_t)0, NULL)) {
		retval = CODB_RET_SUCCESS;
	} else {
		/* check the main namespace! */
		if (can_create_nspace(h, class, "") == CODB_RET_PERMDENIED) {
			retval = CODB_RET_PERMDENIED;
		}

		/* check all the createacls of the class' namespaces */
		namespaces = codb_classconf_getnamespaces(h->classconf, class);
		names_i = namespaces;

		while (names_i) {
			name = (char *)names_i->data;
			if (can_create_nspace(h, class, name) == CODB_RET_PERMDENIED) {
				retval = CODB_RET_PERMDENIED;
				break;
			}
			names_i = g_slist_next(names_i);
		}
		g_slist_free(namespaces);
	}

	if (retval == CODB_RET_PERMDENIED) {
		CCE_SYSLOG("access denied: create '%s'", class);
	}
	return retval;
}

codb_ret
codb_security_can_destroy(codb_handle *h, oid_t oid)
{
	GSList *namespaces, *names_i;
	codb_ret retval = CODB_RET_SUCCESS;
	char *name;
	char *class;


	if (rule_sysadmin(h, oid, NULL)) {
		return CODB_RET_SUCCESS;
	} else {
		/* get the classname of this oid) */
		class = codb_get_classname(h, oid);

		/* check the destroyacl of the main namespace */
		if (can_destroy_nspace(h, oid, class, "") == CODB_RET_PERMDENIED) {
			retval = CODB_RET_PERMDENIED;
		}

		/* check the same as above, but for each namespace */
		namespaces = codb_classconf_getnamespaces(h->classconf, class);
		names_i = namespaces;

		while (names_i) {
			name = (char *)names_i->data;
			if (can_destroy_nspace(h, oid, class, name) == CODB_RET_PERMDENIED) {
				retval = CODB_RET_PERMDENIED;
				break;
			}
			names_i = g_slist_next(names_i);
		}
		g_slist_free(namespaces);
		free(class);
	}

	if (retval == CODB_RET_PERMDENIED) {
		CCE_SYSLOG("access denied: destroy %ld", oid);
	}
	return retval;
}

codb_ret
codb_security_read_filter(codb_handle *h, oid_t target, char *class, 
	char *nspace, GHashTable *attribs)
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
codb_security_write_filter(codb_handle *h, oid_t target, char *class,
	char *nspace, GHashTable *attribs, GHashTable *errs)
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

			if (!p || can_write_prop(h, target, p) 
			 != CODB_RET_SUCCESS) {
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
	char *acl;
	GSList *rules;
	codb_ret r = CODB_RET_SUCCESS;

	/* read the writeacl and break it into rules */
	acl = codb_property_get_writeacl(prop);
	rules = acl_parse(acl);

	/* can we write this property? defaults say only ruleAdmin */
	if ((rules->data == &rulenone) || (!acl_access_ok(h, tgt, rules))) {
		CCE_SYSLOG("access denied: write to %ld.some-property", tgt);
	 	r = CODB_RET_PERMDENIED;
	}

	acl_free(rules);

	return r;
}

static codb_ret
can_read_prop(codb_handle *h, oid_t tgt, codb_property *prop)
{
	char *acl;
	GSList *rules;
	codb_ret r = CODB_RET_PERMDENIED;

	/* read the readacl and break it into rules */
	acl = codb_property_get_readacl(prop);
	rules = acl_parse(acl);

	/* can we read this property? defaults say only ruleUser*/
	if (rules->data == &rulenone) {
		if (rule_user(h, tgt, NULL)) {
			r = CODB_RET_SUCCESS;
		}
	} else if (acl_access_ok(h, tgt, rules)) {
	 	r = CODB_RET_SUCCESS;
	}

	acl_free(rules);

	return r;
}

static codb_ret
can_destroy_nspace(codb_handle *h, oid_t target, char *class, char *nspace)
{
	char *acl;
	GSList *rules;
	codb_class *cc_class;
	codb_ret retval = CODB_RET_SUCCESS;

	/* get the class from classconf */
	cc_class = codb_classconf_getclass(h->classconf, class, nspace);

	acl = codb_class_get_destroyacl(cc_class);
	rules = acl_parse(acl);

	if ((rules->data == &rulenone) || (!acl_access_ok(h, target, rules))) {
		CCE_SYSLOG("User OID(%ld) denied when destroying CLASS(\"%s\") "
			"in NAMESPACE(\"%s\")\n", h->cur_oid, class, nspace);
		retval = CODB_RET_PERMDENIED;
	}

	acl_free(rules);

	return retval;
}

static codb_ret
can_create_nspace(codb_handle *h, char *class, char *nspace)
{
	char *acl;
	GSList *rules;
	codb_class *cc_class;
	codb_ret retval = CODB_RET_SUCCESS;

	/* get the class schema */
	cc_class = codb_classconf_getclass(h->classconf, class, nspace);

	acl = codb_class_get_createacl(cc_class);
	rules = acl_parse(acl);

	if ((rules->data == &rulenone) || (!acl_access_ok(h, (oid_t)0, rules))) {
		CCE_SYSLOG("User OID(%ld) denied when creating CLASS(\"%s\") "
			"in NAMESPACE(\"%s\")\n", h->cur_oid, class, nspace);
		retval = CODB_RET_PERMDENIED;
	}
	acl_free(rules);

	return retval;
}


static GSList *
acl_parse(char *acl)
{
	GSList *list = NULL;
	struct aclrule *rule;
	struct aclrule *rule_with_args;
	char *lacl;
	char *p;
	char *tok;
	char *arg_start;
	char *arg_end;

	if (!acl || !strcmp(acl, "")) {
		list = g_slist_append(list, &rulenone);
		return list;
	}

	lacl = strdup(acl);
	p = lacl;

	while (p) {
		/* spin initial whitespace */
		while (isspace(*p)) {
			p++;
		}

		/* get a token */
		tok = strsep(&p, ",");
		
		/* lookup tok in table */
		rule = &rule_list[0];
		while (rule->acl_name) {
			if (!strcasecmp(tok, rule->acl_name)) {
				list = g_slist_append(list, rule);
				break;
			} else if (((arg_start = strchr(tok, '('))!=NULL)
				&& (!strncasecmp(tok,rule->acl_name,arg_start-tok))
				&& ((arg_end = strchr(arg_start, ')'))!=NULL)) {

				/* we have something that may be a function.. */

				arg_start++; // skip the first paren

				/* create the rule */
				rule_with_args = malloc(sizeof(struct aclrule));
				rule_with_args->acl_fn = rule->acl_fn;
				rule_with_args->acl_name = rule->acl_name;
				rule_with_args->arg = malloc(1 + arg_end - arg_start);
				strncpy(rule_with_args->arg, arg_start, arg_end - arg_start);
				rule_with_args->arg[arg_end - arg_start] = '\0';

				list = g_slist_append(list, rule_with_args);
				break;

			} 
			rule++;
		}
		if (!rule->acl_name) {
			CCE_SYSLOG("acl rule \"%s\" is unknown", tok);
		}
	}
	
	free(lacl);

	return list;
}	

static void
acl_free(GSList *rules)
{
	GSList *step;
	/* step through and free custom rules with args.. */
	step = rules;
	while (step && step->data) {
		if (((struct aclrule *)step->data)->arg) {
			free(((struct aclrule *)step->data)->arg);
			free(step->data);
		}
		step = g_slist_next(step);
	}
	g_slist_free(rules);
}

/* return 1 for OK, 0 for NOK */
static int
acl_access_ok(codb_handle *h, oid_t target, GSList *rules)
{
	struct aclrule *r;
	GSList *p;

	p = rules;

	/* test against each rule */
	while (p) {
		r = (struct aclrule *)p->data;

		if (rule_access_ok(h, target, r)) {
			return 1;
		}

		p = g_slist_next(p);
	}

	return 0;
}

static int
rule_access_ok(codb_handle *h, oid_t target, struct aclrule *r)
{
	if (!r->acl_fn || r->acl_fn(h, target, r->arg)) {
		return 1;
	}

	return 0;
}

static int
rule_sysadmin(codb_handle *h, oid_t target, char *arg)
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

static int
rule_user(codb_handle *h, oid_t target, char *arg)
{
	if (h->cur_oid != 0) {
		return 1;
	}

	return 0;
}

static int
rule_capable(codb_handle *h, oid_t target, char *arg)
{
	cce_scalar *sc;
	codb_ret ret;
	odb_oid myoid;
	char *buf;
	int retval = 0;

	/* am I god? */
	if (rule_sysadmin(h, target, arg)) {
		return 1;
	}

	/* am I a nobody? */
	if (h->cur_oid == 0) {
		return 0;
	}

	myoid.oid = h->cur_oid;
	sc = cce_scalar_new_undef();

	ret = odb_txn_get(h->txn, &myoid, ".capabilities", sc);

	if (ret == CODB_RET_SUCCESS) {
		/* this is a cheap way of seeing if the cap is in the list */
		buf = malloc(strlen(arg)+3);
		sprintf(buf, "&%s&", arg);
		if (cce_scalar_isdefined(sc)
		    && strstr((char *)sc->data, buf))
			retval = 1;
		free(buf);
	}

	cce_scalar_destroy(sc);

	return retval;
}

static int
rule_self(codb_handle *h, oid_t target, char *arg)
{
	if (h->cur_oid != 0 && h->cur_oid == target) {
		return 1;
	}

	return 0;
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
