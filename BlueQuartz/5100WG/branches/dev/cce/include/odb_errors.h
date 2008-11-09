/* $Id: odb_errors.h 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
 
#ifndef CCE_ODB_ERRORS_H_
#define CCE_ODB_ERRORS_H_ 1
/*
 * codb_ret
 *
 * An enum specifying all possible return codes for all functions.
 */
typedef enum {
	CODB_RET_SUCCESS          = 0,
	CODB_RET_BAD_HANDLE       = -1,
	CODB_RET_UNKOBJ           = -2,
	CODB_RET_UNKCLASS         = -3,
	CODB_RET_UNKNSPACE        = -4,
	CODB_RET_BADDATA          = -5,
	CODB_RET_READONLY         = -6,
	CODB_RET_ALREADY          = -7,
	CODB_RET_OUTOFOIDS        = -8,
	CODB_RET_PERMDENIED       = -9,
	/*
	 * An error with an argument that was not detected during parsing.
	 * eg, a bad property for sorting.
	 */
	CODB_RET_BADARG           = -10,

	CODB_RET_ON_FIRE          = -253,
	CODB_RET_NOMEM            = -254,
	CODB_RET_OTHER            = -255,
} codb_ret;

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
