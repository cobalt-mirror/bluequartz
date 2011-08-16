/* $Id: list.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <cce_common.h>
#include <impl.h>
#include <cce_scalar.h>
#include <odb_types.h>
#include <string.h>
#include <odb_errors.h>

#define LISTPROPNAMELEN   128
#define LISTPROP_HEAD     ""

codb_ret
impl_listget (odb_impl_handle * impl, odb_oid *oid, char *prop,
  odb_oidlist *oidlist )
{
  codb_ret ret;
  cce_scalar *scalar;

  char listprop[LISTPROPNAMELEN] = LISTPROP_HEAD;
  strncat(listprop, prop, LISTPROPNAMELEN - 6);

  scalar = cce_scalar_new_undef();
  
  /* read serialized list */
  ret = impl_read_objprop(impl, oid, listprop, scalar);
  if (ret != CODB_RET_SUCCESS) { 
    cce_scalar_destroy(scalar);
    return ret; 
  }

  /* thaw list */  
  odb_oidlist_flush(oidlist);
  if (cce_scalar_isdefined(scalar)) {
    odb_oidlist_append_from_str(oidlist, scalar->data);
  }
  cce_scalar_destroy(scalar);

  return CODB_RET_SUCCESS;  
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
