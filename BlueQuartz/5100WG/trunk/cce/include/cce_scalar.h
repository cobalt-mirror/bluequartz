/* $Id: cce_scalar.h 3 2003-07-17 15:19:15Z will $
 */

#ifndef _CCE_CCE_SCALAR_H_
#define _CCE_CCE_SCALAR_H_ 1

#include <stdio.h>

/* 
 * cce_scalar
 *
 * this is how scalar data is fed to / retreived from the ODB 
 */
typedef struct {
	int length; /* length (in bytes) of data pointed to */
	void *data; 
} cce_scalar;


/* macros */
#define cce_scalar_isdefined(sc)         (sc && sc->data)
#define cce_scalar_string(sc)		 ((char*)(((cce_scalar*)sc)->data))


/* constructors */
cce_scalar *cce_scalar_new_undef(void);
cce_scalar *cce_scalar_new(int size);
cce_scalar *cce_scalar_new_from_str(char *str);
cce_scalar *cce_scalar_new_from_qstr(char *str);
cce_scalar *cce_scalar_new_from_binstr(char *str);
cce_scalar *cce_scalar_new_from_bin(char *bindata, int len);
cce_scalar *cce_scalar_new_from_any(char *str);
cce_scalar *cce_scalar_dup(cce_scalar *orig);

/* other manipulators */
cce_scalar *cce_scalar_resize(cce_scalar *s, int size);
void cce_scalar_assign(cce_scalar *l, cce_scalar *r);
void cce_scalar_undefine(cce_scalar *s);
void cce_scalar_destroy(cce_scalar *s);
#define cce_scalar_free(s) cce_scalar_destroy(s)

/* compare */
int cce_scalar_compare(cce_scalar *s1, cce_scalar *s2);

/* export */
char *cce_scalar_to_binstr(cce_scalar *s);
char *cce_scalar_to_str(cce_scalar *s);

/* file access */
cce_scalar *cce_scalar_new_from_file(char *filename);
int cce_scalar_from_file(cce_scalar *s, char *filename);
int cce_scalar_to_file(cce_scalar *s , char *filename);

/* Debugging hooks */
void dbg_cce_scalar_dump(FILE *);
cce_scalar *dbg_cce_scalar_alloc(cce_scalar*, char*, int);
void dbg_cce_scalar_free(cce_scalar*,char*,int);

#ifdef CCE_SCALAR_DEBUG
#ifndef LIBCCE_SCALAR_INTERNAL

/* deal with cce_scalar objects */
#define cce_scalar_new_undef()		\
	dbg_cce_scalar_alloc(cce_scalar_new_undef(), __FILE__ , __LINE__)
#define cce_scalar_new(a)	\
	dbg_cce_scalar_alloc(cce_scalar_new(a), __FILE__ , __LINE__)
#define cce_scalar_new_from_str(a)	\
	dbg_cce_scalar_alloc(cce_scalar_new_from_str(a), __FILE__ , __LINE__)
#define cce_scalar_new_from_qstr(a)	\
	dbg_cce_scalar_alloc(cce_scalar_new_from_qstr(a), __FILE__ , __LINE__)
#define cce_scalar_new_from_binstr(a)	\
	dbg_cce_scalar_alloc(cce_scalar_new_from_binstr(a), __FILE__ , __LINE__)
#define cce_scalar_new_from_bin(a,b)	\
	dbg_cce_scalar_alloc(cce_scalar_new_from_bin(a, b), __FILE__ , __LINE__)
#define cce_scalar_new_from_any(a)	\
	dbg_cce_scalar_alloc(cce_scalar_new_from_any(a), __FILE__ , __LINE__)
#define cce_scalar_dup(a)	\
	dbg_cce_scalar_alloc(cce_scalar_dup(a), __FILE__ , __LINE__)
#define cce_scalar_new_from_file(a, b)	\
	dbg_cce_scalar_alloc(cce_scalar_new_from_file(a, b), __FILE__ , __LINE__)

#define cce_scalar_destroy(a) \
	{ dbg_cce_scalar_free(a , __FILE__ , __LINE__); cce_scalar_destroy(a); }
    
#undef cce_scalar_free
#define cce_scalar_free(a) \
	{ dbg_cce_scalar_free(a , __FILE__ , __LINE__); cce_scalar_destroy(a); }

#endif /* ifndef LIBCCE_SCALAR_INTERNAL */
#endif /* ifdef CCE_SCALAR_DEBUG */

#endif /* cce/cce_scalar.h */

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
