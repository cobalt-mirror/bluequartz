/* $Id: hashwraptest.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright (c) 2002 Sun Microsystems, Inc. */
/* Test program for cce GHashWrap routines */
#include <stdio.h>
#include <string.h>
#include "g_hashwrap.h"

int test_hashwrap_alloc(void);
int test_hashwrap_set(void);
int test_hashwrap_index(void);

int
main(void)
{
	int r;

	r = test_hashwrap_alloc()
	    || test_hashwrap_set() 
	    || test_hashwrap_index() 
	    ;

	return r;
}


static int
hw_get_and_check(GHashWrap *hw, gpointer key, gpointer expect)
{
	char *c = g_hashwrap_lookup(hw, key);
	if ((expect && !c) || (c && strcmp(c, expect))) {
		return 1;
	}
	return 0;
}

static int
hw_index_all_and_check(GHashWrap *hw, char *expect)
{
	char *key;
	int ret;
	int i;
	char buf[4096];
	int bsz = 0;

	for (i = 0; i < g_hashwrap_size(hw); i++) {
		ret = g_hashwrap_index(hw, i, (gpointer)&key, NULL);
		if (ret < 0 || !key) {
			return 1;
		}
		bsz += snprintf(buf+bsz, sizeof(buf)-bsz, "%s", key);
	}
	if (strcmp(buf, expect)) {
		return 1;
	}
	return 0;
}

static int
hw_index_and_check(GHashWrap *hw, int idx, int keyp, int valp, int ret)
{
	char *key, *val;
	int r;
	r = g_hashwrap_index(hw, idx, keyp?(gpointer)&key:NULL, 
	    valp?(gpointer)&val:NULL);
	if (r != ret || (keyp && !key) || (valp && !val)) {
		return 1;
	} 
	return 0;
}

int
test_hashwrap_alloc(void)
{
	GHashWrap *hw;
	
	hw = g_hashwrap_new(g_str_hash, g_str_equal, g_str_clone, g_str_free);
	if (!hw) {
		printf("FAIL @%s(%d): g_hashwrap_new()\n", __func__, __LINE__);
		return 1;
	}
	g_hashwrap_destroy(hw);

	return 0;
}

int
test_hashwrap_set(void)
{
	GHashWrap *hw;
	int i;
	
	hw = g_hashwrap_new(g_str_hash, g_str_equal, g_str_clone, g_str_free);
	if (!hw) {
		printf("FAIL @%s(%d): g_hashwrap_new()\n", __func__, __LINE__);
		return 1;
	}

	/* check that an insert and subsequent lookup works */
	g_hashwrap_insert(hw, "foo", "bar");
	if (hw_get_and_check(hw, "foo", "bar")) {
		printf("FAIL @%s(%d): g_hashwrap_get(hw, \"foo\")\n",
			__func__, __LINE__);
		return 1;
	}

	/* check a lookup of a removed item */
	g_hashwrap_remove(hw, "foo");
	if (hw_get_and_check(hw, "foo", NULL)) {
		printf("FAIL @%s(%d): g_hashwrap_get(hw, \"foo\")\n",
			__func__, __LINE__);
		return 1;
	}

	/* try removing a non-existant item */
	g_hashwrap_remove(hw, "foobar");

	/* try repeatedly inserting the same key */
	for (i = 0; i < 1024; i++) {
		g_hashwrap_insert(hw, "key", "val");
		if (hw_get_and_check(hw, "key", "val")) {
			printf("FAIL @%s(%d): g_hashwrap_get(hw, \"key\")\n",
				__func__, __LINE__);
			return 1;
		}
	}
			
	g_hashwrap_destroy(hw);

	return 0;
}

int
test_hashwrap_index(void)
{
	GHashWrap *hw;
	
	hw = g_hashwrap_new(g_str_hash, g_str_equal, g_str_clone, g_str_free);
	if (!hw) {
		printf("FAIL @%s(%d): g_hashwrap_new()\n", __func__, __LINE__);
		return 1;
	}

	g_hashwrap_insert(hw, "foo", "bar");
	g_hashwrap_insert(hw, "bar", "foo");
	g_hashwrap_insert(hw, "bat", "baz");
	g_hashwrap_insert(hw, "baz", "bat");
	if (hw_index_all_and_check(hw, "foobarbatbaz")) {
		printf("FAIL @%s(%d): g_hashwrap_index()\n", 
		    __func__, __LINE__);
	}
	
	g_hashwrap_remove(hw, "bat");
	if (hw_index_all_and_check(hw, "foobarbaz")) {
		printf("FAIL @%s(%d): g_hashwrap_index()\n", 
		    __func__, __LINE__);
	}

	g_hashwrap_remove(hw, "foo");
	if (hw_index_all_and_check(hw, "barbaz")) {
		printf("FAIL @%s(%d): g_hashwrap_index()\n", 
		    __func__, __LINE__);
	}

	if (hw_index_and_check(hw, 0, 1, 1, 0)) {
		printf("FAIL @%s(%d): g_hashwrap_index()\n", 
			__func__, __LINE__);
	}
	if (hw_index_and_check(hw, 0, 1, 0, 0)) {
		printf("FAIL @%s(%d): g_hashwrap_index()\n", 
			__func__, __LINE__);
	}
	if (hw_index_and_check(hw, 0, 0, 1, 0)) {
		printf("FAIL @%s(%d): g_hashwrap_index()\n",
			__func__, __LINE__); \
	}
	if (hw_index_and_check(hw, 0, 0, 0, 0)) {
		printf("FAIL @%s(%d): g_hashwrap_index()\n",
			__func__, __LINE__); \
	}
	if (hw_index_and_check(hw, -1, 0, 0, -1)) {
		printf("FAIL @%s(%d): g_hashwrap_index()\n",
			__func__, __LINE__); \
	}
	if (hw_index_and_check(hw, 3, 0, 0, -1)) {
		printf("FAIL @%s(%d): g_hashwrap_index()\n",
			__func__, __LINE__); \
	}
	if (hw_index_and_check(hw, 1024, 0, 0, -1)) {
		printf("FAIL @%s(%d): g_hashwrap_index()\n",
			__func__, __LINE__); \
	}

	g_hashwrap_destroy(hw);

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
