/* $Id: codb_security.h 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
#ifndef ODB_SECURITY_H__
#define ODB_SECURITY_H__

#include "g_hashwrap.h"

codb_ret codb_security_can_create(codb_handle *, const char *, oid_t,

    GHashWrap *);
codb_ret codb_security_can_destroy(codb_handle *h, oid_t oid, GHashWrap *);
codb_ret codb_security_read_filter(codb_handle *h, oid_t target,
    const char *class, const char *nspace, GHashWrap *attribs);
codb_ret codb_security_write_filter(codb_handle *h, oid_t target,
    const char *class, const char *nspace, GHashWrap *attribs,

    GHashWrap *errs);
codb_ret codb_security_can_write_prop(codb_handle *h, oid_t tgt,

    codb_property *prop);
codb_ret codb_security_can_read_prop(codb_handle *h, oid_t tgt,

    codb_property *prop);

#endif
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
