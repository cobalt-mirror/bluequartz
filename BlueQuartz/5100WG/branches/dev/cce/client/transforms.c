/* $Id: transforms.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright (c) 2001-2002 Sun Microsystems, Inc.  All rights reserved. */
#include <stdio.h>
#include <unistd.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>

#include "cce.h"
#include "stresc.h"

/* helper functions */
static void array_free(char **array);
static int resize_str(char **str, int size);


/*
 * Escape a string for CCE-safe transmission
 * NOTE: allocates memory which must be free()ed
 */
char *
cce_stresc(const char *source)
{
	return escape_str(source);
}

/*
 * Unescape a (presumably) CCE-safe string
 * NOTE: allocates memory which must be free()ed
 */
char *
cce_strunesc(const char *source)
{
	return unescape_str(source);
}

/* 
 * Convert a CCE boolean ("0" or "1") to a native boolean.
 */
int 
cce_bool_to_native(const char *source)
{
	if (!source || source[1] != '\0') {
		return -1;
	}

	if (source[0] == '0') {
		return 0;
	} else if (source[0] == '1') {
		return 1;
	}

	return -1;
}

/* 
 * Convert a native boolean into a CCE boolean.
 * NOTE: allocates memory which must be free()ed
 */ 
char *
cce_native_to_bool(int source)
{
	char buf[2] = { '\0', '\0' };

	buf[0] = source ? '1' : '0';

	/* if strdup fails, NULL will be returned */
	return strdup(buf);
}

/* 
 * Convert a CCE integer to a native integer.
 */
int 
cce_int_to_native(const char *source)
{
	if (!source) {
		return 0;
	}

	return atoi(source);
}

/* 
 * Convert a native integer into a CCE integer.
 * NOTE: allocates memory which must be free()ed
 */ 
char *
cce_native_to_int(int source)
{
	char buf[32];

	snprintf(buf, sizeof(buf), "%d", source);

	/* if strdup fails, NULL will be returned */
	return strdup(buf);
}

/* 
 * Convert a CCE array (URL encoded) to a native (NULL termintaed) array 
 * of CCE values (strings).
 * NOTE: the returned array and contents thereof must be free()ed
 */
char **
cce_array_to_native(const char *source)
{
	const char *p;
	char c;
	char **ar = NULL;
	int ar_len = -1;
	int el_len = -1;

	/* URL encoded arrays start and end with '&', blank arrays are "&" */
	if (!source || source[0] != '&' || source[strlen(source)-1] != '&') {
		return NULL;
	}

	/* for each character in the source string... */
	for (p = source; *p != '\0'; p++) {
		c = *p;

		switch (c) {
		case '&': { /* start a new array element */
			char **tmp;

			ar_len++;
			tmp = realloc(ar, (ar_len+1) * sizeof(*ar));
			if (!tmp) {
				array_free(ar);
				return NULL;
			}
			ar = tmp;

			/* init elements to "", except if we're done */
			if (*(p+1) != '\0') {
				el_len = 0;
				ar[ar_len] = strdup("");
				if (!ar[ar_len]) {
					array_free(ar);
					return NULL;
				}
			} else {
				ar[ar_len] = NULL;
			}
			break;
		}
		/* an escaped character */
		case '%': {
			char esc[3];

			if (*(p+1) == '\0' || !isxdigit(*(p+1)) || 
			    *(p+2) == '\0' || !isxdigit(*(p+2))) {
				/* not a proper escape - punt */
				break;
			}
			esc[0] = *(++p);
			esc[1] = *(++p);
			esc[2] = '\0';

			/* convert to the literal representation */
			c = (char)strtol(esc, NULL, 16);
			/* fall through */
		}
		/* a non-escaped character */
		default: {
			if (resize_str(&ar[ar_len], el_len+1)) {
				array_free(ar);
				return NULL;
			}
			ar[ar_len][el_len++] = c; /* note: use 'c' here */
			break;
		}
		}
	}

	return ar;
}

/*
 * Convert a native array (NULL terminated) of (presumably) strings to a CCE
 * array (URL encoded).
 * NOTE: allocates memory which must be free()ed
 */
char *
cce_native_to_array(char *const *source)
{
	char *const *p;
	char *str;
	int str_len = 0;

	if (!source) {
		return NULL;
	}

	/* start with a blank string */
	str = strdup("");

	/* for each array element... */
	for (p = source; *p; p++) {
		const char *q;

		/* start a new element */
		if (resize_str(&str, str_len+1)) {
			free(str);
			return NULL;
		}
		str[str_len++] = '&';

		/* for each character of the element... */
		for (q = *p; *q != '\0'; q++) {
			/* URL encoding allows only these characters */
			if (isalnum(*q) || strchr("$-_.+!*'(),", *q)) {
				/* a safe character */
				if (resize_str(&str, str_len+1)) {
					free(str);
					return NULL;
				}
				str[str_len++] = *q;
			} else {
				/* escape it */
				char esc[3];

				snprintf(esc, 3, "%02X", (unsigned char)*q);
				if (resize_str(&str, str_len+3)) {
					free(str);
					return NULL;
				}
				str[str_len++] = '%';
				str[str_len++] = esc[0];
				str[str_len++] = esc[1];
			}
		}
	}

	/* terminate the encoding */
	if (resize_str(&str, str_len+1)) {
		free(str);
		return NULL;
	}
	str[str_len++] = '&';

	return str;
}

/* free an array of strings (as from cce_array_to_native) */
static void
array_free(char **array)
{
	char **p;

	for (p = array; p && *p; p++) {
		free(*p);
	}
	free(array);
}

/* safely re-alloc and terminate a string */
static int
resize_str(char **str, int size)
{
	char *tmp;

	tmp = realloc(*str, size+1);
	if (!tmp) {
		return -1;
	}
	tmp[size] = '\0';
	*str = tmp;

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
