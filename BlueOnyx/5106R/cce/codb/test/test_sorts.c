/* $Id: test_sorts.c,v 1.3 2001/08/10 22:23:13 mpashniak Exp $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/* test the compare functions */

#include <cce_common.h>
#include <codb.h>
#include <cce_scalar.h>
#include <codb_classconf.h>
#include <compare.h>
#include <stdio.h>

void
test(sortfunc * func, GSList * args, const char *a, const char *b, int expect)
{
	sortstruct sa, sb;
	gint cmp0, cmp1;

	sa.args = args;
	sb.args = args;
	sa.value = cce_scalar_new_from_str(a);
	sb.value = cce_scalar_new_from_str(b);

	cmp0 = (*func) (&sa, &sb);
	cmp1 = (*func) (&sb, &sa);
	cmp1 *= -1;

	printf("  %s %c %s:\t",
	    a, (cmp0 < 0) ? '<' : ((cmp0 == 0) ? '=' : '>'), b);

	if (cmp0 != expect) {
		printf("a-b-fail ");
	}
	if (cmp1 != expect) {
		printf("b-a-fail ");
	}
	if (cmp0 == cmp1 && cmp1 == expect) {
		printf("ok.");
	}
	printf("\n");

	cce_scalar_destroy(sa.value);
	cce_scalar_destroy(sb.value);
}

int
main()
{
	test(old_numeric_compare, NULL, "0", "0", 0);
	test(old_numeric_compare, NULL, "1234", "1234", 0);
	test(old_numeric_compare, NULL, "1234", "1235", -1);
	test(old_numeric_compare, NULL, "1235", "12345", -1);
	test(old_numeric_compare, NULL, "4", "40000", -1);
	test(old_numeric_compare, NULL, "", "", 0);
	test(old_numeric_compare, NULL, "1", "", 1);
	test(old_numeric_compare, NULL, "0", "1", -1);

	// FIXME: compare currently things "0" > "", when they really
	// ought to be equal.  But, since this is just being used for
	// a sort, we can probably get away with this for now.
	//test(old_numeric_compare, NULL, "0","", 0);

	test(old_numeric_compare, NULL, "0.2", "0.15", 1);
	test(old_numeric_compare, NULL, "v0.2", "v0.15", -1);
	test(old_numeric_compare, NULL, "0.200", "0.15", 1);
	test(old_numeric_compare, NULL, "v0.200", "v0.15", 1);
	test(old_numeric_compare, NULL, "v0.200", "v0.15000", -1);

	test(old_numeric_compare, NULL, "abc", "Def", 0);
	test(old_numeric_compare, NULL, "vabc", "vDef", 0);

	test(ip_compare, NULL, "1.2.3.4", "1.2.3.4", 0);
	test(ip_compare, NULL, "1.2.3.4", "1.2.3.5", -1);
	test(ip_compare, NULL, "1.2.3.4", "1.2.4.4", -1);
	test(ip_compare, NULL, "1.2.3.4", "1.3.3.4", -1);
	test(ip_compare, NULL, "1.2.3.4", "2.2.3.4", -1);

	test(hostname_compare, NULL, "foo.com", "foo.com", 0);
	test(hostname_compare, NULL, "foo.com", "bar.com", 1);
	test(hostname_compare, NULL, "foobar.com", "bar.com", 1);
	test(hostname_compare, NULL, "barfoo.com", "bar.com", 1);
	test(hostname_compare, NULL, "foo.bar.com", "bar.bar.com", 1);
	test(hostname_compare, NULL, "foo.bar.x.com", "bar.x.com", 1);
	test(hostname_compare, NULL, "bar.foo.x.com", "foo.x.com", 1);
	test(hostname_compare, NULL, "foo.x.com", "foo.bar.x.com", 1);

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
