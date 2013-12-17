/* $Id: test_cscp_parse.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * test suite for cscp parser 
 */

#include <cce_common.h>
#include <stdio.h>
#include <cscp_parse.h>
#include <cscp_cmd_table.h>
#include <cce_scalar.h>

void dump_cscp_cmd ( cscp_parsed_cmd_t * cmdP )
{
	GSList *iter;
	cce_scalar *s;
	int i = 0;

	if (cmdP->cmd == TOK_EOF)
		return;

	/* All valid command indexes should be greater than 0 */
	if (cmdP->cmd >= 0) {
		/* cscp_cmd_table is a global variable from libcscp.a */
		printf ("Command: %s", cscp_cmd_table[cmdP->cmd].cmd);
		if (cmdP->parse_err) {
			printf(" (parse error)");
		}
		printf("\n");
	} else {
		printf ("Command: -1 (Invalid)\n");
	}

	iter = cmdP->params;
	while (iter)
	{
		printf (".");
		if (iter->data) {
			s = (cce_scalar *)iter->data;
			printf ("     %2d: (%d) ", i++, s->length);
			if (s->data) {
				printf ("%s", (char*)s->data);
			}
			printf ("\n");
		}
		iter = g_slist_next(iter);
	}
	printf ("---------------------------------------------------\n");
}

int main()
{
        char buf[512];
        cscp_parsed_cmd_t *cmdP;

        while (1)
        {
		buf[0] = '\0';
		fgets(buf, 512, stdin);
		if (buf[0] == '\0') 
			break;
		cmdP = cscp_parse(buf);
		printf ("::: %s", buf);
		dump_cscp_cmd(cmdP);
		cscp_cmd_free(cmdP);
        }
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
