#include <stdlib.h>
#include <stdio.h>
#include <glib.h>

#include "ccewrap_program.h"
#include "ccewrap_capability.h"

/* Struct that maintains the program specific data */
struct ccewrapconf_program_t {
	char *name;
	int freename;
	GList *capabilities;
};

struct ccewrapconf_program_t *
mk_program (struct xml_element *e)
{
	struct ccewrapconf_program_t *program;
	struct xml_element *child;
	GList *p;
	char *name;

	program = ccewrapconf_program_new();

	name = (char *)g_hash_table_lookup(e->el_props, "name");

	if (!name) {
		ccewrapconf_program_free(program);
		return NULL;
	}

	ccewrapconf_program_setname(program, name);

	/* iterate and add the children elements */
	p = e->el_children;
	while (p) {
		child = (struct xml_element *)p->data;
		if (!strcmp(child->el_name->str, "capability")) {
			/* new capability assigned */
			ccewrapconf_capability cap;
			cap = mk_capability(child);
			ccewrapconf_program_addcapability(program, cap);
		} 
		p = g_list_next(p);
	}

	return program;
}

struct ccewrapconf_program_t *
ccewrapconf_program_new (void)
{
	struct ccewrapconf_program_t *program;
	program = malloc(sizeof(struct ccewrapconf_program_t));
	program->name = NULL;
	program->capabilities = NULL;
	program->freename = 0;
	return program;
}

void
ccewrapconf_program_free (struct ccewrapconf_program_t *program)
{
	if (program->freename) {
		free(program->name);
	}
	free (program);
}

void
ccewrapconf_program_setname (struct ccewrapconf_program_t *program, char *name)
{
	program->name = name;
}

char *
ccewrapconf_program_getname (struct ccewrapconf_program_t *program) 
{
	return program->name;
}

void
ccewrapconf_program_addcapability(struct ccewrapconf_program_t *program, struct ccewrapconf_capability_t *cap) {
	program->capabilities = g_list_append(program->capabilities, cap);
}

GList *
ccewrapconf_program_getcapabilities(struct ccewrapconf_program_t *program)
{
	/* if we don't have any set capabilities, then we default
	 * to a *, self-requesting for this program. */
	if (program->capabilities == NULL) {
		ccewrapconf_capability cap;
		cap = ccewrapconf_capability_new();
		ccewrapconf_capability_setrequires(cap, "");
		ccewrapconf_capability_setuser(cap, "");
		ccewrapconf_program_addcapability(program, cap);
	}
	return program->capabilities;
}

void 
ccewrapconf_program_setfree_name(struct ccewrapconf_program_t *program, int val)
{
	program->freename = val;
}

int
ccewrapconf_program_allowed(struct ccewrapconf_program_t *program, ccewrapconf conf, GList **validUsers)
{
	GList *caps;
	int ret = 0;

	if (ccewrapconf_issystemadministrator(conf))
		return 1;

	caps = ccewrapconf_program_getcapabilities(program);

	while (caps) {
		ccewrapconf_capability cap = (ccewrapconf_capability)caps->data;
		if (ccewrapconf_capability_allowed(cap, conf, validUsers)) {
			ret++;
		}
		caps = g_list_next(caps);
	}
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
