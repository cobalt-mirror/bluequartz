/* $Id: odb_helpers.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/* odb_helpers.c
 *
 * Assorted helper functions
 */

#include "cce_common.h"
#include "codb.h"
#include "odb_helpers.h"
#include <string.h>

/*********************  helper functions ***************/
char *
oid_to_str(odb_oid *oid)
{
	char *buf = (char *)malloc(sizeof(char) * 9);

	snprintf(buf, 9, "%08lx", oid->oid);
	return buf;
}

void
str_to_oid(char *s, odb_oid *oid)
{
	oid->oid = strtoul(s, NULL, 16);
}

char *
objprop_to_str(odb_oid *oid, const char *prop)
{
	char *buf;
	int len = strlen(prop);

	len += 10;		/* 8 for object id, 1 for dot, 1 for * *
				 * terminating null */
	buf = (char *)malloc(sizeof(char) * len);

	snprintf(buf, len, "%08lx.%s", oid->oid, prop);
	return buf;
}

void
str_to_objprop(char *str, odb_oid *oid, char **prop)
{
	oid->oid = strtoul(str, prop, 16);
	if (**prop == '.')
		(*prop)++;
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
