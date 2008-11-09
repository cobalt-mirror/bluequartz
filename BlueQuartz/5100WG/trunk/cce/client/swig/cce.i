/* File : cce.i */

%module cce

%include perl_maps.i
%include python_maps.i

/* Tell swig to handel cscp_oids like unsigned longs in python and typedef 'em
 * to cscp_oid_ts when they get to C. In and out. */

typedef unsigned long cscp_oid_t;

/* Do not accept null pointers for cce_handle_t args */
%apply Pointer NONNULL { cce_handle_t * };

/* All OIDS must be positive */
%apply Number POSITIVE { cscp_oid_t };

%{
#include <signal.h>
#include <cce.h>
%}

%include cce.h

/* Now for telling SWIG how we did OO programming in C but not to hate us 
 * for it and write us some shadow classes anyway.
 * All commented out for now until I learn how to deal with obfuscated
 * structs in SWIG.

typedef struct cce_handle_struct {
	cce_handle_t *handle;
} cce_handle_struct:

%addmethods cce_handle_t * {
};

*/
