/* $Id: argparse.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * Parses a string for function and argument style.
 */

/*
 * Parses a single function of the style "function(arg1,arg2)"
 *
 * return: 0 on success, nonzero on error
 * IN: string - the string to be parsed
 * OUT: function - allocated string that is the "function"
 * OUT: args - GSList of allocated strings
 * OUT: argc - the count of arguments - if pointer is NULL, not used
 *
 * notes:
 * - whitespace is counted as part of the argument or function
 * - there is no current method for escaping a comma in an argument
 * - it does not correctly handle parens in arguments
 * - an empty string is an error
 * - out parameters are not free()d on error but are NULL if not yet
 *   allocated
 */

#include <glib.h>
#include <string.h>
#include <stdlib.h>
#include "argparse.h"

int
arg_parse(const char *string, char **function, GSList **args,
	int *argc, const char **next)
{
	const char *tok, *rest, *end;

	if (!function || !args) {
		return 1;
	}
	*args = NULL;
	if (next) {
		*next = NULL;
	}
	if (argc) {
		*argc = 0;
	}
	if (!string || !*string) {
		*function = NULL;
		return 1;
	}

	tok = strchr(string, ',');
	rest = strchr(string, '(');
	if (rest && (!tok || rest < tok)) {
		*function = malloc(rest - string + 1);
		if (!*function) {
			return -1;
		}
		strncpy(*function, string, rest - string);
		(*function)[rest - string] = '\0';

		/* step over the '(' */
		tok = ++(rest);

		end = strchr(tok, ')');
		if (!end) {
			/* unmatched '(' */
			return 1;
		}
		/* we already know where it will end */
		if (next) {
			*next = end+1;
		}

		while (tok && *tok) {
			char *arg;

			rest = strchr(tok, ',');
			if (rest && rest < end) {
				if (rest == tok) {
					/* error.  either "(," or ",," */
					return 1;
				}
				/* another arg found */
				arg = malloc(rest - tok + 1);
				if (!arg) {
					return -1;
				}
				strncpy(arg, tok, rest - tok);
				arg[rest - tok] = '\0';
				*args = g_slist_append(*args, arg);
				if (argc) {
					++(*argc);
				}
				tok = ++(rest);
				continue;
			}
			if (end == tok) {
				if (*(end-1) == '(') {
					/* no argument. no error */
					return 0;
				} else {
					/* empty ",)" error */
					return 1;
				}
			}
			/* last arg found */
			arg = malloc(end - tok + 1);
			if (!arg) {
				return -1;
			}
			strncpy(arg, tok, end - tok);
			arg[end - tok] = '\0';
			*args = g_slist_append(*args, arg);
			if (argc) {
				++(*argc);
			}
			return 0;
		}
		/* we CAN'T get out of that loop...
		 * but just in case
		 */
		return 1;
	} else {
		/* plain old function, no arguments */

		/* look for a ',' */
		rest = strchr(string, ',');
		if (rest) {
			*function = malloc(rest - string + 1);
			if (!*function) {
				return -1;
			}
			strncpy(*function, string, rest - string);
			(*function)[rest - string] = '\0';
			if (next) {
				*next = rest;
			}
			return 0;
		} else {
			size_t len;

			/* whole thing is the function name */
			len = strlen(string);
			*function = malloc(len + 1);
			if (!*function) {
				return -1;
			}
			strncpy(*function, string, len);
			(*function)[len] = '\0';
			if (next) {
				*next = NULL;
			}
		}
	}
	return 0;
}


void
free_arglist(GSList *l)
{
	GSList *tmp = l;
	while (tmp) {
		free(tmp->data);
		tmp = tmp->next;
	}
	g_slist_free(l);
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
