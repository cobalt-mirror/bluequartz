/* $Id: classconf_types.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/* 
 * implements the codb_typedef object defined in classconf.h
 */

#include "cce_common.h"
#include <stresc.h>

#include "classconf.h"
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <regex.h>
#define _USE_BSD
#include <sys/types.h>
#include <sys/resource.h>
#include <sys/wait.h>

#define REGCOMP_FLAGS	(REG_EXTENDED | REG_NOSUB)	/* | REG_NEWLINE) */

/* dt_domain - determines what type of type we've got */
typedef enum {
	DT_EXTERN = 1,
	DT_RE = 2,		/* regex */
} dt_domain;

/* member data */
struct codb_typedef_struct {
	char *name;
	dt_domain domain;
	char *strdata;
	char *errmsg;
	void *data;		/* used for compiled regexes, etc. */
};

/* constructor */
static codb_typedef *
typedef_new_of_dom(char *name, dt_domain domain, char *strdata,
    char *errmsg)
{
	codb_typedef *td;

	td = malloc(sizeof(codb_typedef));
	if (td) {
		td->name = name ? strdup(name) : NULL;
		td->domain = domain;
		td->strdata = strdata ? strdup(strdata) : NULL;
		td->errmsg = errmsg ? strdup(errmsg) : NULL;
		td->data = NULL;
		if (!td->name || !td->strdata) {
			codb_typedef_destroy(td);
			return NULL;
		}
		if (td->domain == DT_RE) {
			/* precompile regex */
			regex_t *r;
			char *expanded_strdata;

			/* unescape the string */
			expanded_strdata = unescape_str(td->strdata);
			if (!expanded_strdata) {
				CCE_SYSLOG("Out of memory");
				codb_typedef_destroy(td);
				return NULL;
			}

			/* allocate a regex_t */
			r = malloc(sizeof(regex_t));
			if (!r) {
				CCE_SYSLOG("Out of memory");
				codb_typedef_destroy(td);
				free(expanded_strdata);
				return NULL;
			}

			/* compile it */
			if (regcomp(r, expanded_strdata, REGCOMP_FLAGS)) {
				/* regcomp failed */
				CCE_SYSLOG("Could not compile regex: /%s/",
				    td->strdata);
				regfree(r);
				free(r);
				free(expanded_strdata);
				codb_typedef_destroy(td);
				return NULL;
			}
			free(expanded_strdata);
			td->data = r;
		}
	}
	return (td);
}

codb_typedef *
codb_typedef_new(char *type, char *name, char *data, char *errmsg)
{
	dt_domain dt;

	if (!strcasecmp(type, "re")) {
		dt = DT_RE;
	} else if (!strcasecmp(type, "extern")) {
		dt = DT_EXTERN;
	} else {
		return NULL;
	}

	return typedef_new_of_dom(name, DT_RE, data, errmsg);
}

/* destructor */
void
codb_typedef_destroy(codb_typedef * td)
{
	if (td->name)
		free(td->name);
	if (td->strdata)
		free(td->strdata);
	if (td->errmsg)
		free(td->errmsg);
	if (td->data) {
		if (td->domain == DT_RE) {
			regfree(td->data);
		}
		free(td->data);
	}
	free(td);
}

/* validate 
 * returns:
 *	CODB_RET_SUCCESS on success
 *	CODB_RET_BADDATA on failure
 */
int
codb_typedef_validate(codb_typedef * td, char *str)
{
	/* check for and handle regex test: */
	if ((td->domain == DT_RE) && (td->data)) {
		if (regexec((regex_t *) td->data, str, 0, NULL, 0)) {
			return CODB_RET_BADDATA;
		} else {
			return CODB_RET_SUCCESS;
		}
	}

	/* check for and handle external test: */
	if (td->domain == DT_EXTERN) {
		FILE *fp;
		int status;

		fp = popen(td->strdata, "w");
		if (!fp) {
			return 0;	/* bad type */
		}
		fwrite(str, strlen(str), 1, fp);
		fflush(fp);
		status = pclose(fp);
		if ((WIFEXITED(status) && !WEXITSTATUS(status))) {
			return CODB_RET_SUCCESS;
		} else {
			return CODB_RET_BADDATA;
		}
	}

	CCE_SYSLOG("Bad type domain: %d", td->domain);
	return CODB_RET_BADDATA;	/* failure is always an option */
}

static int
hex2int(char c)
{
	if (c >= '0' && c <= '9') {
		return (int)(c - '0');
	}
	if (c >= 'a' && c <= 'f') {
		return (int)(10 + c - 'a');
	}
	if (c >= 'A' && c <= 'F') {
		return (int)(10 + c - 'A');
	}
	return -1;
}

/* validate as an array
 * returns:
 *	CODB_RET_SUCCESS on success
 *	CODB_RET_BADDATA on failure
 */
int
codb_typedef_validate_array(codb_typedef * td, char *str)
{
	int errors;
	GString *buffer;

	errors = 0;
	buffer = g_string_new("");

	// skip the leading &: this shouldn't be optional but it is.
	if (*str == '&')
		str++;

	// walk the array
	while (*str) {
		if ((*str == '%')
		    && (hex2int(*(str + 1)) >= 0)
		    && (hex2int(*(str + 2)) >= 0)) {
			// is an escaped special character:
			int i;

			i = 16 * hex2int(*(str + 1)) + hex2int(*(str + 2));
			g_string_append_c(buffer, (gchar) i);
			str += 3;
		} else if (*str == '&') {
			// separates the men from the boys:
			if (codb_typedef_validate(td,
				buffer->str) != CODB_RET_SUCCESS) errors++;
			g_string_assign(buffer, "");
			str += 1;
		} else {
			// is an ordinary character:
			g_string_append_c(buffer, (gchar) (*str));
			str += 1;
		}
	}

	if (*(buffer->str) != '\0') {
		if (codb_typedef_validate(td,
			buffer->str) != CODB_RET_SUCCESS) errors++;
	}
	g_string_free(buffer, 1);

	return (errors ? CODB_RET_BADDATA : CODB_RET_SUCCESS);
}

char *
codb_typedef_get_name(codb_typedef * td)
{
	if (!td) {
		return NULL;
	}
	return td->name;
}

char *
codb_typedef_get_data(codb_typedef * td)
{
	if (!td) {
		return NULL;
	}
	return td->strdata;
}

char *
codb_typedef_get_errmsg(codb_typedef * td)
{
	if (!td) {
		return NULL;
	}
	return td->errmsg;
}

/* eof */
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
