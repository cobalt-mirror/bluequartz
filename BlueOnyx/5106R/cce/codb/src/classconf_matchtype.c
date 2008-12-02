/* $Id: classconf_matchtype.c,v 1.4 2001/08/10 22:23:10 mpashniak Exp $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * Implements the codb_matchtype object defined in codb_classconf.h
 */

#include <cce_common.h>
#include <codb.h>
#include <stdlib.h>
#include <string.h>
#include <codb_classconf.h>
#include <cce_scalar.h>
#include <compare.h>

typedef enum {
	RULE_BUILTIN = 1,
	RULE_EXEC,
	RULE_PERL,
} matchtype_type;

struct codb_matchtype_struct {
	char *name;
	matchtype_type type;
	char *data;
	sortfunc *func;		/* for built-ins */
};

struct sortfunctabletype
{
	char *name;
	sortfunc *func;
};
const struct sortfunctabletype sortfunctable[] =
{
	{ "ascii_compare", old_ascii_compare },
	{ "locale_compare", locale_compare },
	{ "old_numeric_compare", old_numeric_compare },
	{ "ip_compare", ip_compare },
	{ "hostname_compare", hostname_compare },
	{ NULL, NULL }
};

codb_matchtype *
codb_matchtype_new(char *name, char *type, char *data)
{
	codb_matchtype *matchtype;
	
	matchtype = (codb_matchtype *)malloc(sizeof(codb_matchtype));

	if (!matchtype)
		return NULL;	/* OOM. */

	matchtype->name = strdup(name);
	matchtype->data = strdup(data);
	matchtype->func = NULL;

	if (!strcasecmp(type, "builtin")) {
		const struct sortfunctabletype *p = sortfunctable;
		matchtype->type = RULE_BUILTIN;
		while (p->name) {
			if (!strcmp(data, p->name)) {
				matchtype->func = p->func;
				break;
			}
			p++;
		}
		if (!matchtype->func) {
			CCE_SYSLOG("Unknown builtin type %s", data);
			codb_matchtype_destroy(matchtype);
			return NULL;
		}
	} else if (!strcasecmp(type, "exec")) {
		matchtype->type = RULE_EXEC;
		CCE_SYSLOG("matchtypes of type EXEC are not implemented yet");
	} else if (!strcasecmp(type, "perl")) {
		matchtype->type = RULE_PERL;
		CCE_SYSLOG("matchtypes of type EXEC are not implemented yet");
	} else {
		codb_matchtype_destroy(matchtype);
		return NULL;
	}

	return matchtype;
}

void
codb_matchtype_destroy(codb_matchtype *matchtype)
{
	if (!matchtype)
		return;
	free(matchtype->name);
	free(matchtype->data);
	free(matchtype);
}

const char *
codb_matchtype_getname(codb_matchtype *matchtype)
{
	return matchtype->name;
}

sortfunc *
codb_matchtype_getcompfunc(codb_matchtype *matchtype)
{
	return matchtype->func;
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
