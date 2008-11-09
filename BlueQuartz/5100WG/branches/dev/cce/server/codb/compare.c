/* $Id: compare.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * various compare functions for use when sorting
 */

#include "cce_common.h"
#include "codb.h"
#include "cce_scalar.h"
#include "classconf.h"
#include "compare.h"
#include <ctype.h>
#include <string.h>
#include <locale.h>
#include <netinet/in.h>
#include <arpa/inet.h>

static gint numeric_compare(const char **, const char **, int);
static gint compare_float(gconstpointer, gconstpointer);
static gint compare_version(gconstpointer, gconstpointer);
static const char *revseek(const char *start, const char *end, int hunted);

gint locale_compare(gconstpointer a, gconstpointer b)
{
	const char *value_a, *value_b;
	char *oldlocale;
	gint ret;
	const char *localearg;

	value_a = cce_scalar_string(((const sortstruct *)a)->value);
	value_b = cce_scalar_string(((const sortstruct *)b)->value);
	if (!value_a && !value_b)
		return 0;
	if (!value_a && value_b)
		return -1;
	if (!value_b && value_a)
		return 1;

	if (!((const sortstruct *)a)->args) {
		/* error - no locale given - just do ascii sort */
		return strcmp(value_a, value_b);
	}
	localearg = ((const sortstruct *)a)->args->data;
	if (!localearg) {
		/* error - blank locale given - just do ascii sort */
		return strcmp(value_a, value_b);
	}
	oldlocale = strdup(setlocale(LC_COLLATE, NULL));
	if (!setlocale(LC_COLLATE, localearg)) {
		/* error - locale not found - just do ascii sort */
		free(oldlocale);
		return strcmp(value_a, value_b);
	}
	ret = strcoll(value_a, value_b);
	setlocale(LC_COLLATE, oldlocale);
	free(oldlocale);

	return ret;
}

gint old_ascii_compare(gconstpointer a, gconstpointer b)
{
	const char *value_a, *value_b;

	value_a = cce_scalar_string(((const sortstruct *)a)->value);
	value_b = cce_scalar_string(((const sortstruct *)b)->value);
	if (!value_a && !value_b)
		return 0;
	if (!value_a && value_b)
		return -1;
	if (!value_b && value_a)
		return 1;
	return strcmp(value_a, value_b);
}

gint old_numeric_compare(gconstpointer a, gconstpointer b)
{
	const char *value_a, *value_b;

	value_a = cce_scalar_string(((const sortstruct *)a)->value);
	value_b = cce_scalar_string(((const sortstruct *)b)->value);
	if (!value_a && !value_b)
		return 0;
	if (!value_a && value_b)
		return -1;
	if (!value_b && value_a)
		return 1;
	return compare_float(value_a, value_b);
}

gint ip_compare(gconstpointer a, gconstpointer b)
{
	const char *value_a, *value_b;
	struct in_addr ina;
	struct in_addr inb;
	gint diff;

	value_a = cce_scalar_string(((const sortstruct *)a)->value);
	value_b = cce_scalar_string(((const sortstruct *)b)->value);

	inet_aton(value_a, &ina);
	inet_aton(value_b, &inb);

	diff = ntohl(ina.s_addr) - ntohl(inb.s_addr);

	return (diff < 0) ? -1 : (diff > 0) ? 1 : 0;
}

/* 
 * This has the unfortunate side effect of putting foo.bar.co.com before
 * foo.co.com.  If this is a big deal, we can replace this with an algorithm
 * that comprehends hostnames vs domain names and number of fields.
 */
gint hostname_compare(gconstpointer a, gconstpointer b)
{
	const char *p1;
	const char *p2;
	int r = 0;
	const char *value_a, *value_b;

	value_a = cce_scalar_string(((const sortstruct *)a)->value);
	value_b = cce_scalar_string(((const sortstruct *)b)->value);

	p1 = value_a + strlen(value_a);
	p2 = value_b + strlen(value_b);
	do {
		const char *c1;
		const char *c2;

		p1 = revseek(value_a, p1 - 1, '.');
		if (p1 == value_a) {
			c1 = p1;
		} else {
			c1 = p1 + 1;
		}
		p2 = revseek(value_b, p2 - 1, '.');
		if (p2 == value_b) {
			c2 = p2;
		} else {
			c2 = p2 + 1;
		}

		r = strcmp(c1, c2);
	} while (!r && p1 != value_a && p2 != value_b);

	/* 
	 * we've either hit a difference or beginning 
	 */

	/* did we find a difference? */
	if (r < 0) {
		return -1;
	} else if (r > 0) {
		return 1;
	}

	/* hit both beginnings? */
	if (p1 == value_a && p2 == value_b) {
		/* identical */
		return 0;
	}

	/* hit a beginning of one - select the shorter */
	if (p1 == value_a) {
		return -1;
	} else if (p2 == value_b) {
		return 1;
	} else {
		/* aieee! */
		return 0;
	}
}

/* helper functions */

static gint
numeric_compare(const char **app, const char **bpp, int mode)
{
	gint cmp = 0;

	while (1) {
		int isdigit_a, isdigit_b;
		char a = **app;
		char b = **bpp;

		isdigit_a = isdigit(a);
		isdigit_b = isdigit(b);

		if (isdigit_a) {
			if (isdigit_b) {
				// isdigit_a and isdigit_b
				if (cmp == 0) {
					if (a < b)
						cmp = -1;
					if (a > b)
						cmp = +1;
				}
				(*app)++;
				(*bpp)++;
			} else {
				// isdigit_a and !isdigit_b
				if (mode) {
					do {
						if ((**app) > '0') {
							if (cmp == 0)
								cmp = +1;
						}
						(*app)++;
					} while (isdigit(**app));
				} else {
					cmp = +1;
					do {
						(*app)++;
					} while (isdigit(**app));
					break;
				}
			}
		} else {
			if (isdigit_b) {
				// !isdigit_a and isdigit_b
				if (mode) {
					do {
						if ((**bpp) > '0') {
							if (cmp == 0)
								cmp = -1;
						}
						(*bpp)++;
					} while (isdigit(**bpp));
				} else {
					cmp = -1;
					do {
						(*bpp)++;
					} while (isdigit(**bpp));
					break;
				}
			} else {
				// !isdigit_a and !isdigit_b
				break;
			}
		}
	}

	return (cmp);
}

static gint
compare_float(gconstpointer gpa, gconstpointer gpb)
{
	gint cmp;
	const char *a = gpa;
	const char *b = gpb;

	{
		int version = 0;

		if (*a == 'v' || *a == 'V') {
			version = 1;
			a++;
		}
		if (*b == 'v' || *b == 'V') {
			version = 1;
			b++;
		}
		if (version)
			return compare_version(a, b);
	};

	cmp = numeric_compare(&a, &b, 0);	// integer compare

	if (cmp == 0) {
		if (*a == '.')
			a++;
		if (*b == '.')
			b++;
		cmp = numeric_compare(&a, &b, 1);	// fractional
		// compare
	}

	return cmp;
}

static gint
compare_version(gconstpointer gpa, gconstpointer gpb)
{
	gint cmp = 0;
	const char *a = gpa;
	const char *b = gpb;

	while (cmp == 0) {
		if (*a == '.')
			a++;
		if (*b == '.')
			b++;
		if (!isdigit(*a)) {
			if (isdigit(*b)) {
				cmp = -1;
			}
			break;
		} else {
			if (!isdigit(*b)) {
				cmp = +1;
			} else {
				cmp = numeric_compare(&a, &b, 0);
			}
		}
	}

	return cmp;
}

static const char *
revseek(const char *start, const char *end, int hunted)
{
	while (end > start && *end != hunted) {
		end--;
	}
	return end;
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
