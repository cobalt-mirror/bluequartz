/*
 *
 * Copyright (c) Cobalt Networkw 1999/2000
 * Written by Harris Vaegan-Lloyd
 */
#include <stdio.h>
#include <unistd.h>
#include <stdlib.h>
#include <time.h>
#include <glib.h>
#include <libdebug.h>

#include "i18n.h"

#define TPRINTF(f, a...)		printf("TEST: " f, ##a)

#define ASSERT(v1,v2) \
	if( strcmp(v1,v2) != 0 ) { \
		printf("Failure: '''%s''' != '''%s'''\n", v1,v2); \
		return 0; \
	}

int do_test(void);

int main(int argc, char *argv[]) 
{
	int i;

	if (argc == 2) {
		i = atoi(argv[1]);
	} else {
		i = 1;
	}

	TPRINTF("Test Time.\n\n");
	while (i--) {
		TPRINTF("Testing external functions:\n");
		if( ! do_test() ) {
			TPRINTF("Testing failed.\n");
			exit(-1);
		}
		TPRINTF("\n\n");

		TPRINTF("Testing internal functions:\n");
#ifdef DEBUG
		if( !i18n_test() ) {
			TPRINTF("Testing failed.\n");
			exit(-1);
		}
#endif
		TPRINTF("DONE\n\n");
#ifdef USE_LIBDEBUG
		memdebug_dump();
#endif
		g_mem_profile();
	}
	TPRINTF("Testing successful.");
	return 0;
}

int
do_test(void)
{
	i18n_handle *i18n;
	i18n_vars *vars;
	char *text;
	time_t t;
	FILE *fd;


	TPRINTF("We are now creating a new i18n handle with a locale of "
		 "'en_US, en_AU, zh, de_DE' and a default domain of 'cobalt'\n");

	i18n = i18n_new("cobalt", "en_US, en_AU, zh, de_DE");

	if(! i18n ) {
		TPRINTF("Did not create i18n handle\n");
		return -1;
	}

	TPRINTF("Test translation and variable interpolation from a tag.\n");
	vars = i18n_vars_new();
	i18n_vars_add(vars, "two", "two");
	i18n_vars_add(vars, "one", "three");

	text = i18n_get(i18n, "includer", NULL, vars);
	i18n_vars_destroy(vars);

	
	ASSERT(text,	"'one two three' Help! I need somebody! Help! "
					"Everybody needs somebody! Yankee -> Yank -> "
					"Tank -> Septic Tank -> Septic -> Seppo. Madlove "
					"cockney slang.");

	TPRINTF("Testing location of file resources\n");
	TPRINTF("Creating /tmp/foo.en_AU, looking for a localised /tmp/foo\n");
	unlink("/tmp/foo.en_AU");
	fd = fopen("/tmp/foo.en_AU","w");
	fclose(fd);

	text = i18n_get_file(i18n, "/tmp/foo");

	ASSERT(text, "/tmp/foo.en_AU");

	unlink("/tmp/foo.en_AU");

	TPRINTF("Testing retrieval of properties\n");
	ASSERT(i18n_get_property(i18n, "superpower",NULL),"in their own mind");
	ASSERT(i18n_get_property(i18n, "health_care",NULL),"");
	ASSERT(i18n_get_property(i18n, "test_property",NULL),"1");

	TPRINTF("Testing encodings\n");
	TPRINTF("Testing html encoding\n");
	ASSERT(i18n_get_html(i18n, "encoder",NULL,NULL),"&lt;This&gt; is a 'test' of &lt;HTML&gt; and 'JavaScript' encoding");
	TPRINTF("Testing js encoding\n");
	ASSERT(i18n_get_js(i18n, "encoder",NULL,NULL),"<This> is a \\'test\\' of <HTML> and \\'JavaScript\\' encoding");

	/* My birthday in unix time */
	t = 955102740;
	TPRINTF("Testing localised time\n");
	ASSERT(i18n_strftime(i18n, "%X", t),"08:19:00 PM");
	/* Proud to be sorta non y2 compliant */
	ASSERT(i18n_strftime(i18n, "%x", t),"04/07/00");

	ASSERT(i18n_get(i18n,"[[time.955102740|%X]]",NULL,NULL),"08:19:00 PM");

	if (! i18n_get(i18n,"[[time.%X]]",NULL,NULL) ) {
		TPRINTF("Failure in fetching time string without epochal time\n");
		return 0;
	}

	i18n_destroy(i18n);

	TPRINTF("\n\n");

    return 1;
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
