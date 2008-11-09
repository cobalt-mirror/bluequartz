/* $Id: stresctest.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright (c) 2002 Sun Microsystems, Inc. */
#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include "stresc.h"

int
main(void)
{
	char *raw1 = "The quick brown fox";
	char *cook1 = raw1;
	char *raw2 = "\a\b\f\n\r\t\v\"\\";
	char *cook2 = "\\a\\b\\f\\n\\r\\t\\v\\\"\\\\";
	char *raw3 = "a\a b\b f\f n\n r\r t\t v\v qt\" bs\\";
	char *cook3 = "a\\a b\\b f\\f n\\n r\\r t\\t v\\v qt\\\" bs\\\\";
	char *raw4 = "\377 \200 \300 \343";
	char *cook4 = "\\377 \\200 \\300 \\343";
	char *raw5 = "hello";
	char *cook5 = "\\h\\e\\l\\l\\o";
	char *tmp;

	/* forwards */
	tmp = escape_str(raw1);
	if (strcmp(tmp, cook1)) {
		printf("FAIL @%s:%d: escape_str(\"%s\")\n", 
		    __FILE__, __LINE__, cook1);
		exit(EXIT_FAILURE);
	}
	free(tmp);
	
	tmp = escape_str(raw2);
	if (strcmp(tmp, cook2)) {
		printf("FAIL @%s:%d: escape_str(\"%s\")\n", 
		    __FILE__, __LINE__, cook2);
		exit(EXIT_FAILURE);
	}
	free(tmp);
	
	tmp = escape_str(raw3);
	if (strcmp(tmp, cook3)) {
		printf("FAIL @%s:%d: escape_str(\"%s\")\n", 
		    __FILE__, __LINE__, cook3);
		exit(EXIT_FAILURE);
	}
	free(tmp);

	tmp = escape_str(raw4);
	if (strcmp(tmp, cook4)) {
		printf("FAIL @%s:%d: escape_str(\"%s\")\n", 
		    __FILE__, __LINE__, cook4);
		exit(EXIT_FAILURE);
	}
	free(tmp);

	/* skip raw5 - it tests fallback on literals */


	/* backwards */
	tmp = unescape_str(cook1);
	if (strcmp(tmp, raw1)) {
		printf("FAIL @%s:%d: unescape_str(\"%s\")\n", 
		    __FILE__, __LINE__, cook1);
		exit(EXIT_FAILURE);
	}
	free(tmp);
	
	tmp = unescape_str(cook2);
	if (strcmp(tmp, raw2)) {
		printf("FAIL @%s:%d: unescape_str(\"%s\")\n", 
		    __FILE__, __LINE__, cook2);
		exit(EXIT_FAILURE);
	}
	free(tmp);
	
	tmp = unescape_str(cook3);
	if (strcmp(tmp, raw3)) {
		printf("FAIL @%s:%d: unescape_str(\"%s\")\n", 
		    __FILE__, __LINE__, cook3);
		exit(EXIT_FAILURE);
	}
	free(tmp);

	tmp = unescape_str(cook4);
	if (strcmp(tmp, raw4)) {
		printf("FAIL @%s:%d: unescape_str(\"%s\")\n", 
		    __FILE__, __LINE__, cook4);
		exit(EXIT_FAILURE);
	}
	free(tmp);

	tmp = unescape_str(cook5);
	if (strcmp(tmp, raw5)) {
		printf("FAIL @%s:%d: unescape_str(\"%s\")\n", 
		    __FILE__, __LINE__, cook5);
		exit(EXIT_FAILURE);
	}
	free(tmp);

	return EXIT_SUCCESS;
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
