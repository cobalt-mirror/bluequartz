/* $Id: argparsetest.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <glib.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <assert.h>
#include <argparse.h>

static int checkfunc(char **func, const char *realfunc);
static int checkarg(GSList **list, const char *arg);

int main()
{
	int myargc;
	const char *next;
	GSList *list;
	char *func;

	/* test no args */
	assert(!arg_parse("test1", &func, &list, &myargc, &next));
	assert(!checkfunc(&func, "test1"));
	assert(!list);
	assert(myargc == 0);
	assert(!next);

	/* test one arg */
	assert(!arg_parse("test2(foo)", &func, &list, &myargc, &next));
	assert(!checkfunc(&func, "test2"));
	assert(!checkarg(&list, "foo"));
	assert(!list);
	assert(myargc == 1);
	assert(next && !*next);

	/* test whitespace */
	assert(!arg_parse("te st3(b ar , baz)", &func, &list, &myargc, &next));
	assert(!checkfunc(&func, "te st3"));
	assert(!checkarg(&list, "b ar "));
	assert(!checkarg(&list, " baz"));
	assert(!list);
	assert(myargc == 2);
	assert(next && !*next);

	/* test special case*/
	assert(!arg_parse("spec()", &func, &list, &myargc, &next));
	assert(!checkfunc(&func, "spec"));
	assert(!list);
	assert(myargc == 0);
	assert(next && !*next);

	/* test error catching */
	assert(arg_parse("fail2(", &func, &list, &myargc, &next));
	assert(!checkfunc(&func, "fail2"));
	assert(!list);
	assert(myargc == 0);
	assert(!next);

	/* test error catching */
	assert(arg_parse("fail3(foo3,)", &func, &list, &myargc, &next));
	assert(!checkfunc(&func, "fail3"));
	assert(!checkarg(&list, "foo3"));
	assert(!list);
	assert(myargc == 1);
	assert(!*next);

	/* test error catching */
	assert(arg_parse("fail4(foo4,,bar)", &func, &list, &myargc, &next));
	assert(!checkfunc(&func, "fail4"));
	assert(!checkarg(&list, "foo4"));
	assert(!list);
	assert(myargc == 1);
	assert(!*next);

	/* test too long string */
	assert(!arg_parse("fail4(foo4,bar),f", &func, &list, &myargc, &next));
	assert(!checkfunc(&func, "fail4"));
	assert(!checkarg(&list, "foo4"));
	assert(!checkarg(&list, "bar"));
	assert(!list);
	assert(myargc == 2);
	assert(next && !strcmp(next, ",f"));

	/* test func with no args, then another one */
	assert(!arg_parse("test5,test5a(foo,bar)", &func, &list, &myargc, &next));
	assert(!checkfunc(&func, "test5"));
	assert(!list);
	assert(myargc == 0);
	assert(next && !strcmp(next, ",test5a(foo,bar)"));

	return 0;
}

static int
checkfunc(char **func, const char *realfunc)
{
	if (!func) {
		return 1;
	}
	if (strcmp(*func, realfunc)) {
		return 1;
	}
	free(*func);
	*func = NULL;
	return 0;
}

static int
checkarg(GSList **list, const char *arg)
{
	GSList *first;
	char *data;

	if (!list || !*list) {
		return 1;
	}

	first = *list;
	*list = g_slist_remove_link(*list, first);
	data = first->data;
	g_slist_free(first);

	if (strcmp(data, arg)) {
		free(data);
		return 1;
	}
	free(data);
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
