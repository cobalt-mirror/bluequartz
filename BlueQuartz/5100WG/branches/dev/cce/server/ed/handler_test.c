/* $Id: handler_test.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include "cce_common.h"
#include "cce_ed_internal.h"
#include "cce_ed.h"
#include "cce_conf.h"
#include "codb.h"

int
handler_test (codb_handle *odb, cce_ed *ed, ed_handler_event *he,
	cscp_ctxt_t context)
{
	char *h_prog;
	cce_conf_handler *handler;
	GSList *p;
	
	handler = he->handler;
	h_prog = cce_conf_handler_data(handler);

	fprintf(stderr, "*** test handler: %s:%s in context %s:",
		cce_conf_handler_type(handler),
		cce_conf_handler_data(handler),
		ctxt_name(context));

	p = he->events;
	while (p)
	{
		codb_event *e = (codb_event *)p->data;

		fprintf(stderr, " %d.", codb_event_get_oid(e));
		if (codb_event_is_create(e))
		{
			fprintf(stderr, "_CREATE");
		} else if (codb_event_is_destroy(e)) {
			fprintf(stderr, "_DESTROY");
		} else {
			fprintf(stderr, "%s", codb_event_get_string(e));
		}

		p = g_slist_next(p);
	}
	fprintf(stderr, "\n");

	return 0; /* success */
}

/* eof */
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
