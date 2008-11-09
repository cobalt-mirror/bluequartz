/* $Id: propstest.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright (c) 2002 Sun Microsystems, Inc. */
/* Test program for libcce props_t routines */
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>
#include "props.h"

int test_props_helpers(void);
int test_props_alloc(void);
int test_props_set(void);
int test_props_count(void);
int test_props_renew(void);
int test_props_index(void);
int test_props_clone(void);
int test_props_merge(void);

int
main(void)
{
	test_props_helpers();
	test_props_alloc();
	test_props_set();
	test_props_count();
	test_props_renew();
	test_props_index();
	test_props_clone();
	test_props_merge();

	return EXIT_SUCCESS;
}

#define PROPERTY_FROM_STR(s, n, p) test_from_str(s, n, p, __func__, __LINE__)
static void
test_from_str(char *str, char *ns, char *prop, char *func, int line)
{
	property_t *p;

	p = property_from_str(str);
	if (!p || 
	    ((ns && !p->namespace) || (!ns && p->namespace)) || 
	    (ns && p->namespace && strcmp(ns, p->namespace)) ||
	    ((prop && !p->property) || (!prop && p->property)) || 
	    (prop && p->property && strcmp(prop, p->property))) {
		printf("FAIL @%s(%d): property_from_str(%s)\n", 
		    func, line, str);
		exit(EXIT_FAILURE);
	}
	property_destroy(p);
}

#define PROPERTY_TO_STR(n, p, s) test_to_str(n, p, s, __func__, __LINE__)
static void
test_to_str(char *ns, char *prop, char *str, char *func, int line)
{
	property_t p;
	char *r;

	p.namespace = ns;
	p.property = prop;

	r = property_to_str(&p);
	if (!r || strcmp(r, str)) {
		printf("FAIL @%s(%d): property_to_str(%s)\n", 
		    func, line, str);
		exit(EXIT_FAILURE);
	}
}

#define PROPS_NEW(p) test_new(&p, __func__, __LINE__)
static void
test_new(props_t **p, char *func, int line)
{
	*p = props_new();
	if (!*p) {
		printf("FAIL @%s(%d): props_new()\n", func, line);
		exit(EXIT_FAILURE);
	}
}

#define PROPS_DESTROY(p) props_destroy(p)

#define PROPS_SET(p, k, v, r) test_set(p, k, v, r, __func__, __LINE__)
static void
test_set(props_t *p, char *k, char *v, int r, char *func, int line)
{
	int ret = props_set_str(p, k, v);
	if (ret != r) {
		printf("FAIL @%s(%d): props_set_str(" "\"%s\", \"%s\")\n", 
			func, line, (char *)k, (char *)v);
		exit(EXIT_FAILURE);
	}
}

#define PROPS_GET(p, k, v) test_get(p, k, v, __func__, __LINE__)
static void
test_get(props_t *p, char *k, char *v, char *func, int line)
{
	char *c = props_get_str(p, k);
	if (!c || strcmp(c, v)) {
		if (!c && !v) 
			return;
		printf("FAIL @%s(%d): props_get_str(p, \"%s\")\n",
			func, line, k);
		exit(EXIT_FAILURE);
	}
}

#define PROPS_UNSET(p, k, r) test_unset(p, k, r, __func__, __LINE__)
static void
test_unset(props_t *p, char *k, int r, char *func, int line)
{
	int ret = props_unset_str(p, k);
	if (ret != r) {
		printf("FAIL @%s(%d): props_unset_str(p, \"%s\")\n",
			func, line, k);
		exit(EXIT_SUCCESS);
	}
}

#define PROPS_COUNT(p, v) test_count(p, v, __func__, __LINE__)
static void
test_count(props_t *p, int v, char *func, int line)
{
	if (props_count(p) != v) {
		printf("FAIL @%s(%d): props_count(p)\n", func, line);
		exit(EXIT_SUCCESS);
	}
}

#define PROPS_RENEW(p, r) test_renew(p, r, __func__, __LINE__)
static void
test_renew(props_t *p, int r, char *func, int line)
{
	int ret = props_renew(p);
	if (ret != r) {
		printf("FAIL @%s(%d): props_renew(p)\n", func, line);
		exit(EXIT_FAILURE);
	}
}

#define PROPS_INDEX_ALL(p, r) test_index_all(p, r, __func__, __LINE__)
static void
test_index_all(props_t *p, char *str, char *func, int line)
{
	char *key; 
	int ret;
	int i;
	char buf[4096];
	int bsz = 0;

	for (i = 0; i < props_count(p); i++) {
		property_t *tmpk;
		ret = props_index(p, i, &tmpk, NULL);
		if (ret != 0 || !key) {
			printf("FAIL @%s(%d): props_index(p, %d)\n", 
			    func, line, i);
			exit(EXIT_FAILURE);
		}
		key = property_to_str(tmpk);
		bsz += snprintf(buf+bsz, sizeof(buf)-bsz, "%s", key);
		free(key);
	}
	if (strcmp(buf, str)) {
		printf("FAIL @%s(%d): props_index(): %s\n",
		    func, line, buf);
		exit(EXIT_FAILURE);
	}
}

#define PROPS_INDEX(p, i, k, v, r) test_index(p,i,k,v,r, __func__, __LINE__)
static void
test_index(props_t *p, int i, int k, int v, int r, char *func, int line)
{
	property_t *key;
	char *val;
	int ret;

	ret = props_index(p, i, k ? &key:NULL, v ? &val:NULL);
	if (ret != r || (k && !key) || (v && !val)) {
		printf("FAIL @%s(%d): props_index(p, %d)\n", func, line, i);
		exit(EXIT_FAILURE);
	}
}

#define PROPS_CLONE(p, q) test_clone(p, &q, __func__, __LINE__)
static void
test_clone(props_t *p, props_t **q, char *func, int line)
{
	*q = props_clone(p);
	if (!*q) {
		printf("FAIL @%s(%d): props_clone(p)\n", func, line);
		exit(EXIT_FAILURE);
	}
}

#define PROPS_MERGE(p, q, r, x) test_merge(p, q, &r, x, __func__, __LINE__)
static void
test_merge(props_t *p, props_t *q, props_t **r, int x, 
    char *func, int line)
{
	*r = props_merge(p, q);
	if (!*r && x) {
		printf("FAIL @%s(%d): props_merge(p, q)\n", func, line);
		exit(EXIT_FAILURE);
	}
}

int
test_props_helpers(void)
{
	PROPERTY_FROM_STR("prop", NULL, "prop");
	PROPERTY_FROM_STR("ns.prop", "ns", "prop");
	PROPERTY_FROM_STR("ns.", "ns", "");

	PROPERTY_TO_STR(NULL, "prop", "prop");
	PROPERTY_TO_STR("ns", "prop", "ns.prop");
	PROPERTY_TO_STR("ns", "", "ns.");

	return 0;
}

int
test_props_alloc(void)
{
	props_t *p;
	
	PROPS_NEW(p);
	PROPS_DESTROY(p);

	return 0;
}

int
test_props_set(void)
{
	props_t *p;
	int i;
	
	PROPS_NEW(p);

	PROPS_SET(p, "foo", "bar", 0);
	PROPS_GET(p, "foo", "bar");
	PROPS_UNSET(p, "foo", 0);
	PROPS_GET(p, "foo", NULL);
	PROPS_UNSET(p, "foobar", 0);

	PROPS_SET(p, NULL, "foobar", -EINVAL);

	for (i = 0; i < 1024; i++) {
		PROPS_SET(p, "k", "v", 0);
		PROPS_GET(p, "k", "v");
	}

	PROPS_DESTROY(p);

	return 0;
}

int
test_props_count(void)
{
	props_t *p;
	
	PROPS_NEW(p);
	PROPS_COUNT(p, 0);
	
	PROPS_SET(p, "foo", "bar", 0);
	PROPS_SET(p, "bar", "foo", 0);
	PROPS_SET(p, "bat", "baz", 0);
	PROPS_SET(p, "baz", "bat", 0);
	PROPS_COUNT(p, 4);

	PROPS_UNSET(p, "foo", 0);
	PROPS_COUNT(p, 3);

	PROPS_UNSET(p, "bar", 0);
	PROPS_COUNT(p, 2);

	PROPS_UNSET(p, "bat", 0);
	PROPS_COUNT(p, 1);

	PROPS_UNSET(p, "baz", 0);
	PROPS_COUNT(p, 0);

	PROPS_DESTROY(p);

	return 0;
}

int
test_props_renew(void)
{
	props_t *p;
	
	PROPS_NEW(p);

	PROPS_SET(p, "foo", "bar", 0);
	PROPS_GET(p, "foo", "bar");
	PROPS_COUNT(p, 1);
	PROPS_RENEW(p, 0);
	PROPS_GET(p, "foo", NULL);
	PROPS_COUNT(p, 0);

	PROPS_DESTROY(p);

	return 0;
}

int
test_props_index(void)
{
	props_t *p;
	
	PROPS_NEW(p);

	PROPS_SET(p, "foo", "bar", 0);
	PROPS_SET(p, "bar", "foo", 0);
	PROPS_SET(p, "bat", "baz", 0);
	PROPS_SET(p, "baz", "bat", 0);
	PROPS_INDEX_ALL(p, "foobarbatbaz");
	
	PROPS_UNSET(p, "bat", 0);
	PROPS_INDEX_ALL(p, "foobarbaz");

	PROPS_UNSET(p, "foo", 0);
	PROPS_INDEX_ALL(p, "barbaz");

	PROPS_INDEX(p, 0, 1, 1, 0);
	PROPS_INDEX(p, 0, 1, 0, 0);
	PROPS_INDEX(p, 0, 0, 1, 0);
	PROPS_INDEX(p, 0, 0, 0, 0);
	PROPS_INDEX(p, -1, 0, 0, -EINVAL);
	PROPS_INDEX(p, 3, 0, 0, -EINVAL);
	PROPS_INDEX(p, 1024, 0, 0, -EINVAL);

	PROPS_DESTROY(p);

	return 0;
}

int
test_props_clone(void)
{
	props_t *p, *q;
	
	PROPS_NEW(p);

	PROPS_SET(p, "foo", "bar", 0);
	PROPS_SET(p, "bar", "foo", 0);
	PROPS_SET(p, "bat", "baz", 0);
	PROPS_SET(p, "baz", "bat", 0);
	PROPS_INDEX_ALL(p, "foobarbatbaz");

	PROPS_CLONE(p, q);
	PROPS_INDEX_ALL(q, "foobarbatbaz");

	PROPS_DESTROY(p);

	return 0;
}

int
test_props_merge(void)
{
	props_t *p, *q, *r;
	
	PROPS_NEW(p);
	PROPS_NEW(q);

	PROPS_SET(p, "foo", "bar", 0);
	PROPS_SET(p, "bar", "foo", 0);
	PROPS_SET(p, "bat", "baz", 0);
	PROPS_SET(p, "baz", "bat", 0);
	PROPS_INDEX_ALL(p, "foobarbatbaz");

	PROPS_SET(q, "foo", "BAR", 0);
	PROPS_SET(q, "bar", "FOO", 0);
	PROPS_SET(q, "BAT", "baz", 0);
	PROPS_SET(q, "BAZ", "bat", 0);
	PROPS_INDEX_ALL(q, "foobarBATBAZ");

	PROPS_MERGE(p, q, r, 1);
	PROPS_INDEX_ALL(r, "foobarbatbazBATBAZ");

	PROPS_MERGE(p, NULL, r, 0);
	PROPS_MERGE(NULL, q, r, 0);
	PROPS_MERGE(NULL, NULL, r, 0);

	PROPS_DESTROY(p);

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
