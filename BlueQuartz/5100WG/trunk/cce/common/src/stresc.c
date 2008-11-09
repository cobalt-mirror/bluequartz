#include <cce_common.h>
#include <cce_debug.h>
#include <stresc.h>

#include <string.h>
#include <ctype.h>
#include <glib.h>

typedef unsigned char uchar;

#define isodigit(c)	((c) >= '0' && (c) <= '7')

struct encoding {
	char escaped;
	char literal;
};

static struct encoding escapes[] = {
	{ 'a', '\a' },
	{ 'b', '\b' },
	{ 'f', '\f' },
	{ 'n', '\n' },
	{ 'r', '\r' },
	{ 't', '\t' },
	{ 'v', '\v' },
	{ '\"', '\"' },
	{ '\\', '\\' },
	{ '\0', '\0' }
};

char *
stresc(char *str)
{
	int i = 0;
	GString *newstr;
	char *r;

	if (!str) {
		return NULL;
	}

	newstr = g_string_sized_new(strlen(str));

	/* march through the src string */
	while (str[i]) {
		struct encoding *e = escapes;
		char replacement = '\0';

		/* try to find a match in the table */
		while (e->escaped) {
			if (str[i] == e->literal) {
				/* found a match */
				replacement = e->escaped;
				break;
			}
			e++;
		}

		if (replacement) {
			g_string_append_c(newstr, '\\');
			g_string_append_c(newstr, replacement);
		} else if ((uchar)str[i] >= 128 || iscntrl(str[i])) {
			/* if it is not printable, escape it to an octal */
			char nbuf[4];
			char *p;
			uchar n = (uchar)str[i];

			nbuf[sizeof(nbuf) - 1] = '\0';
			p = &(nbuf[sizeof(nbuf) - 2]);

			while (p >= nbuf) {
				*p = (n % 8) + '0';
				n /= 8;
				p--;
			}

			g_string_append_c(newstr, '\\');
			g_string_append(newstr, nbuf);
		} else {
			/* nothing - just use the literal */
			g_string_append_c(newstr, str[i]);
		}

		i++;
	}

	r = strdup(newstr->str);
	g_string_free(newstr, 1);

	return r;
}

char *
strunesc(char *str)
{
	int i = 0;
	GString *newstr;
	char *r;

	if (!str) {
		return NULL;
	}

	newstr = g_string_sized_new(strlen(str));

	/* march through the src string */
	while (str[i]) {
		/* a \ signals we found an escape */
		if (str[i] == '\\') {
			struct encoding *e = escapes;
			char replacement = '\0';
			
			i++;

			/* find a match in the table */
			while (e->escaped) {
				if (str[i] == e->escaped) {
					replacement = e->literal;
					break;
				}
				e++;
			}

			if (!replacement) {
				/* check for \nnn style escape */
				if (isodigit(str[i])
				 && isodigit(str[i+1])
				 && isodigit(str[i+2])) {
					int n;

					n = (str[i+2] - '0') 
						+ ((str[i+1] - '0') * 8) 
						+ ((str[i] - '0') * 64);
					replacement = (char)n;
					i += 2;
				} else {
					/* nothing - use the literal */
					replacement = str[i];
				}
			}

			g_string_append_c(newstr, replacement);
		} else {
			/* not an escape - stuff it an move on */
			g_string_append_c(newstr, str[i]);
		}
		i++;
	}

	r = strdup(newstr->str);
	g_string_free(newstr, 1);

	return r;
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
