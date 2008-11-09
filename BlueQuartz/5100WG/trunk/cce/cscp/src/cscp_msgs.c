/*
 * CSCP messages
 */

#include <cscp_internal.h>
#include <cscp.h>
#include <cscp_msgs.h>

/* for ease */
char *eol               = "\n";

/* info msgs - need to be completed */
char *data_msg          = "102 DATA ";
char *cdata_msg         = "103 DATA ";
char *object_msg        = "104 OBJECT ";
char *namespace_msg     = "105 NAMESPACE ";
char *info_msg          = "106 INFO ";
char *created_msg       = "107 CREATED ";
char *destroyed_msg     = "108 DESTROYED ";
char *sessionid_msg	= "109 SESSIONID ";
char *class_msg         = "110 CLASS ";

/* success msgs - need to be completed */
char *success_msg       = "201 OK ";

/* warning msgs - need to be completed */
char *unknownobj_msg    = "300 UNKNOWN OBJECT ";
char *unknownclass_msg  = "301 UNKNOWN CLASS ";
char *baddata_msg       = "302 BAD DATA ";  
char *unknownns_msg     = "303 UNKNOWN NAMESPACE ";
char *permdenied_msg    = "304 PERMISSION DENIED ";
char *warn_msg          = "305 WARN ";
char *error_msg         = "306 ERROR ";
char *nomem_msg         = "307 OUT OF MEMORY ";

/* failur msgs - some need to be completed */
char *fail_msg          = "401 FAIL ";
char *badcmd_msg        = "402 BAD COMMAND ";
char *badparams_msg     = "403 BAD PARAMETERS ";

/* system-issued messages */
char *shutdown_msg      = "998 SHUTTING DOWN ";
char *onfire_msg        = "999 ENGINE ON FIRE ";
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
