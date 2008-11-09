/* $Id: cscp_msgs.h 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * This file holds the messages used by CSCP apps
 */

#ifndef CCE_CSCP_MSGS_H_
#define CCE_CSCP_MSGS_H_ 1

/* for ease */
extern char *eol;

/* info msgs */
extern char *cscp_id;
extern char *handler_msg;
extern char *data_msg;
extern char *cdata_msg;
extern char *object_msg;
extern char *namespace_msg;
extern char *info_msg;
extern char *created_msg;
extern char *destroyed_msg;
extern char *sessionid_msg;
extern char *class_msg;
extern char *rollback_msg;

/* success msgs */
extern char *ready_msg;
extern char *success_msg;
extern char *bye_msg;

/* warning msgs */
extern char *unknownobj_msg;
extern char *unknownclass_msg;
extern char *baddata_msg;
extern char *unknownns_msg;
extern char *permdenied_msg;
extern char *warn_msg;
extern char *error_msg;
extern char *nomem_msg;
extern char *regex_msg;
extern char *suspended_msg;

/* failure msgs */
extern char *notready_msg;
extern char *fail_msg;
extern char *badcmd_msg;
extern char *badparams_msg;

/* system-issued messages */
extern char *shutdown_msg;
extern char *onfire_msg;

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
