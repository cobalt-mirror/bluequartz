/* $Id: classconf_rule.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * Implements the codb_rule object defined in classconf.h
 */


#include "cce_common.h"
#include "codb.h"
#include <stdlib.h>
#include <string.h>
#include "classconf.h"
#include "codb_events.h"

typedef enum {
	RULE_BUILTIN = 1,
	RULE_EXEC,
	RULE_PERL,
	RULE_ACL,
} rule_type;

struct codb_rule_struct {
	char *name;
	rule_type type;
	char *data;
	rulefunc *func;		/* for built-ins */
	char *file;		/* for exec/perl */
	int isvisited;		/* for loop detection in acl rules */
};

struct ruletabletype {
	char *name;
	rulefunc *func;
};
const struct ruletabletype ruletable[] = {
	{"rule_sysadmin", rule_sysadmin},
	{"rule_all", rule_all},
	{"rule_user", rule_user},
	{"rule_self", rule_self},
	{"rule_capable", rule_capable},
	{"rule_property_match_property", rule_propsmatch},
	{"rule_property_match_value", rule_propvalue},
	{NULL, NULL}
};

codb_rule *
codb_rule_new(char *name, char *type, char *data)
{
	codb_rule *rule;

	rule = (codb_rule *) malloc(sizeof(codb_rule));

	if (!rule)
		return NULL;	/* OOM. */

	rule->name = strdup(name);
	rule->data = strdup(data);
	rule->func = NULL;
	rule->file = NULL;
	rule->isvisited = 0;

	if (!strcasecmp(type, "builtin")) {
		const struct ruletabletype *p = ruletable;

		rule->type = RULE_BUILTIN;
		while (p->name) {
			if (!strcmp(data, p->name)) {
				rule->func = p->func;
				break;
			}
			p++;
		}
		if (!rule->func) {
			CCE_SYSLOG("Unknown builtin type %s", data);
			codb_rule_destroy(rule);
			return NULL;
		}
	} else if (!strcasecmp(type, "exec")) {
		rule->type = RULE_EXEC;
		rule->file = strdup(data);
	} else if (!strcasecmp(type, "perl")) {
		rule->type = RULE_PERL;
		rule->file = strdup(data);
	} else if (!strcasecmp(type, "acl")) {
		rule->type = RULE_ACL;
	} else {
		CCE_SYSLOG("Rule: unknown type \"%s\"", type);
		codb_rule_destroy(rule);
		return NULL;
	}

	return rule;
}

void
codb_rule_destroy(codb_rule * rule)
{
	if (!rule)
		return;
	free(rule->name);
	free(rule->data);
	if (rule->file)
		free(rule->file);
	free(rule);
}

const char *
codb_rule_getname(codb_rule * rule)
{
	return rule->name;
}

/* I apologize - this is DISGUSTING, but the schedule is tight and I don't
 * have time to analyze the requirements of making the handler_exec calls
 * more generalized for rules/handlers/etc 
 */
/* TODO: FIXME: mpashniak: break out handler_exec into something more
 * generalized for talking cscp to a program so that I don't have to call
 * it with ed internal data structures.
 */
#include "cce_ed.h"
#include "../ed/cce_ed_internal.h"

/* returns 1 on success, 0 on permission denied */
int
codb_rule_check(codb_rule * rule, codb_handle *h, codb_event* e,
    GSList * args)
{
	int ret;
	cce_conf_handler *handler;
	ed_handler_event *he;
	unsigned int oldflags = codb_handle_getflags(h);

	codb_handle_addflags(h, CODBF_ADMIN);

	if (rule->type == RULE_EXEC) {
		handler = cce_conf_handler_new("exec", rule->file,
		    "execute", NULL);
		he = handler_event_new(handler);
		he->events = g_slist_append(he->events, e);
		if (!handler_exec(h, NULL, he, CTXT_RULE))
			ret = 1;
		else
			ret = 0;
		handler_event_destroy(he);
		cce_conf_handler_destroy(handler);
	} else if (rule->type == RULE_PERL) {
		handler = cce_conf_handler_new("perl", rule->file,
		    "execute", NULL);
		he = handler_event_new(handler);
		he->events = g_slist_append(he->events, e);
		if (!handler_perl(h, NULL, he, CTXT_RULE))
			ret = 1;
		else
			ret = 0;
		handler_event_destroy(he);
		cce_conf_handler_destroy(handler);
	} else if (rule->type == RULE_BUILTIN) {
		ret = (*rule->func) (h, codb_event_get_oid(e), args);
	} else if (rule->type == RULE_ACL) {
		if (rule->isvisited) {
			CCE_SYSLOG
			    ("loop detected while checking rule \"%s\"",
			    rule->name);
			ret = 0;
		} else {
			rule->isvisited = 1;
			if (!acl_run(h, e, rule->data))
				ret = 1;
			else
				ret = 0;
			rule->isvisited = 0;
		}
	} else {
		CCE_SYSLOG("Unknown rule type %d", rule->type);
		ret = 0;
	}
	codb_handle_setflags(h, oldflags);
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
