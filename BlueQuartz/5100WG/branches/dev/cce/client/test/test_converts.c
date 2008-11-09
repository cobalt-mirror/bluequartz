/* $Id: test_converts.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright (c) 2002 Sun Microsystems, Inc. */
/* Test program for libcce data conversion routines */
#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include "cce.h"

int test_native_bool(void);
int test_native_int(void);
int test_native_array(void);

int
main(void)
{
	int r;

	r = test_native_bool() || test_native_int() || test_native_array();

	return r;
}

int
test_bool_to_native(char *value, int expect)
{
	int ret = 0;
	int r = cce_bool_to_native(value);

	if (r != expect) {
		printf("FAIL: cce_bool_to_native(\"%s\"): %d\n", value, r);
		ret = 1;
	}
	return ret;
}
		
int
test_native_to_bool(int value, char *expect)
{
	int ret = 0;
	char *c = cce_native_to_bool(value);

	if (!c || strcmp(c, expect)) {
		printf("FAIL: cce_native_to_bool(%d): %s\n", value, c);
		ret = 1;
	}
	free(c);
	return ret;
}
		
int
test_native_bool(void)
{
	int r;
	
	r = test_bool_to_native("0", 0);
	if (r) return r;
	r = test_bool_to_native("1", 1);
	if (r) return r;
	r = test_bool_to_native("2", -1);
	if (r) return r;
	r = test_bool_to_native("", -1);
	if (r) return r;
	r = test_bool_to_native(NULL, -1);
	if (r) return r;

	r = test_native_to_bool(0, "0");
	if (r) return r;
	r = test_native_to_bool(1, "1");
	if (r) return r;
	r = test_native_to_bool(-1, "1");
	if (r) return r;

	return 0;
}

int
test_int_to_native(char *value, int expect)
{
	int ret = 0;
	int r = cce_int_to_native(value);

	if (r != expect) {
		printf("FAIL: cce_int_to_native(\"%s\"): %d\n", value, r);
		ret = 1;
	}
	return ret;
}
		
int
test_native_to_int(int value, char *expect)
{
	int ret = 0;
	char *c = cce_native_to_int(value);

	if (!c || strcmp(c, expect)) {
		printf("FAIL: cce_native_to_int(%d): %s\n", value, c);
		ret = 1;
	}
	free(c);
	return ret;
}
		
int 
test_native_int(void)
{
	int r;

	r = test_int_to_native("0", 0);
	if (r) return r;
	r = test_int_to_native("1", 1);
	if (r) return r;
	r = test_int_to_native("12345678", 12345678);
	if (r) return r;
	r = test_int_to_native("-2345678", -2345678);
	if (r) return r;
	r = test_int_to_native("", 0);
	if (r) return r;
	r = test_int_to_native(NULL, 0);
	if (r) return r;

	r = test_native_to_int(0, "0");
	if (r) return r;
	r = test_native_to_int(1, "1");
	if (r) return r;
	r = test_native_to_int(12345678, "12345678");
	if (r) return r;
	r = test_native_to_int(-2345678, "-2345678");
	if (r) return r;

	return 0;
}

int
test_array_to_native(char *str, int len)
{
	char buf[4096];
	int bsz = 0;
	int i;
	char **ar;

	ar = cce_array_to_native(str);
	if (!ar) {
		if (len < 0) {
			/* expected failure */
			return 0;
		}
		printf("FAIL: cce_array_to_native(\"%s\"): NULL\n", str);
		return 1;
	}
	for (i = 0; i < len && bsz < (sizeof(buf)-1); i++) {
		bsz += snprintf(buf+bsz, sizeof(buf)-bsz, "&%s",
			ar[i]);
		free(ar[i]);
	}
	snprintf(buf+bsz, sizeof(buf)-bsz, "&");
	if (strcmp(buf, str)) {
		printf("FAIL: cce_array_to_native(\"%s\"): %s\n", str, buf);
		free(ar);
		return 1;
	}
	free(ar);

	return 0;
}
			
int
test_native_to_array(char **ar, char *expect)
{
	int ret = 0;
	char *str;

	str = cce_native_to_array(ar);
	if (!str || strcmp(str, expect)) {
		if (!str && !expect) {
			/* expected failure */
			return 0;
		}
		printf("FAIL: cce_native_to_array(\"%s\"): %s\n", expect, str);
		ret = 1;
	}
	if (str) free(str);
	return ret;
}

int
test_url_encode(char *val, char *expect)
{
	int ret = 0;
	char *str;
	char *ar[] = { val, NULL };

	str = cce_native_to_array(ar);
	if (!str || strcmp(str, expect)) {
		printf("FAIL: url encode(\"%s\"): %s\n", val, str);
		ret = 1;
	}
	if (str) free(str);
	return ret;
}

int
test_url_decode(char *str, char *expect)
{
	int ret = 0;
	char **ar;

	ar = cce_array_to_native(str);
	if (!ar || strcmp(ar[0], expect)) {
		printf("FAIL: url decode(\"%s\"): %s\n", str, ar[0]);
		ret = 1;
	}
	free(ar[0]); free(ar);
	return ret;
}

int 
test_native_array(void)
{
	int r;

	r = test_array_to_native("&", 0);
	if (r) return r;
	r = test_array_to_native("&&", 1);
	if (r) return r;
	r = test_array_to_native("&foo&bar&", 2);
	if (r) return r;
	r = test_array_to_native("&foo&bar&bat&baz&bonk&bank&bink&bunk&", 8);
	if (r) return r;
	r = test_array_to_native("", -1);
	if (r) return r;
	r = test_array_to_native(NULL, -1);
	if (r) return r;
	r = test_array_to_native("foo&bar", -1);
	if (r) return r;

	{
		char *ar[] = {NULL};
		r = test_native_to_array(ar, "&");
		if (r) return r;
	}
	{
		char *ar[] = {"", NULL};
		r = test_native_to_array(ar, "&&");
		if (r) return r;
	}
	{
		char *ar[] = {"foo", "bar", NULL};
		r = test_native_to_array(ar, "&foo&bar&");
		if (r) return r;
	}
	{
		char *ar[] = {"foo", "bar", "bat", "baz", NULL};
		r = test_native_to_array(ar, "&foo&bar&bat&baz&");
		if (r) return r;
	}
	r = test_native_to_array(NULL, NULL);
	if (r) return r;

	r = test_url_encode("SPACE ", "&SPACE%20&");
	if (r) return r;
	r = test_url_encode("QUOTE\"", "&QUOTE%22&");
	if (r) return r;
	r = test_url_encode("LT<GT>", "&LT%3CGT%3E&");
	if (r) return r;
	r = test_url_encode("POUND#", "&POUND%23&");
	if (r) return r;
	r = test_url_encode("PCNT%", "&PCNT%25&");
	if (r) return r;
	r = test_url_encode("CURLY{}", "&CURLY%7B%7D&");
	if (r) return r;
	r = test_url_encode("PIPE|", "&PIPE%7C&");
	if (r) return r;
	r = test_url_encode("BACK\\", "&BACK%5C&");
	if (r) return r;
	r = test_url_encode("CARET^", "&CARET%5E&");
	if (r) return r;
	r = test_url_encode("TILDE~", "&TILDE%7E&");
	if (r) return r;
	r = test_url_encode("SQUARE[]", "&SQUARE%5B%5D&");
	if (r) return r;
	r = test_url_encode("RESVD;/?:@&", "&RESVD%3B%2F%3F%3A%40%26&");
	if (r) return r;
	r = test_url_encode("HIGH\377\336\255\276\357", "&HIGH%FF%DE%AD%BE%EF&");
	if (r) return r;

	r = test_url_decode("&SPACE%20&", "SPACE ");
	if (r) return r;
	r = test_url_decode("&QUOTE%22&", "QUOTE\"");
	if (r) return r;
	r = test_url_decode("&LT%3CGT%3E&", "LT<GT>");
	if (r) return r;
	r = test_url_decode("&POUND%23&", "POUND#");
	if (r) return r;
	r = test_url_decode("&PCNT%25&", "PCNT%");
	if (r) return r;
	r = test_url_decode("&CURLY%7B%7D&", "CURLY{}");
	if (r) return r;
	r = test_url_decode("&PIPE%7C&", "PIPE|");
	if (r) return r;
	r = test_url_decode("&BACK%5C&", "BACK\\");
	if (r) return r;
	r = test_url_decode("&CARET%5E&", "CARET^");
	if (r) return r;
	r = test_url_decode("&TILDE%7E&", "TILDE~");
	if (r) return r;
	r = test_url_decode("&SQUARE%5B%5D&", "SQUARE[]");
	if (r) return r;
	r = test_url_decode("&RESVD%3B%2F%3F%3A%40%26&", "RESVD;/?:@&");
	if (r) return r;
	r = test_url_decode("&HIGH%FF%DE%AD%BE%EF&", "HIGH\377\336\255\276\357");
	if (r) return r;

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
