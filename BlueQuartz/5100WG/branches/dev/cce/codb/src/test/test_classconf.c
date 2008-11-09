/* $Id: test_classconf.c 3 2003-07-17 15:19:15Z will $ */

#include <codb_classconf.h>
#include "debug.h"
#include <stdio.h>

enum {T1,T2,T3,T4,NUMOFTYPES};
codb_typedef *t[NUMOFTYPES];

void TESTSTRING(char * string, int mask) 
{ 
	int ok = 0; int i; 
  for (i = 0; i < NUMOFTYPES; i++) { 
  	tests++;
  	ok = codb_typedef_validate(t[i], string); 
    if ( ( mask & (1<<i) ) && !ok ) {
    	errors++; 
      fprintf(stderr,"test%03d: \"%s\" did not match %s (%s:%d)\n", 
      	tests, string, codb_typedef_get_name(t[i]), 
        __FILE__ , __LINE__ ); 
    } else if ( !(mask & (1<<i)) && ok) {
    	errors++;
      fprintf(stderr,"test%03d: \"%s\" erroneously matched %s (%s:%d)\n", 
      	tests, string, codb_typedef_get_name(t[i]), 
        __FILE__ , __LINE__ ); 
    } else {
      fprintf(stderr,"test%03d: ok.\n", tests); 
    } 
  } 
}
    

int main()
{
	codb_classconf *cc;
  codb_class *classA;
  
  /* init syslog */
  openlog("test_classconf", LOG_PERROR, LOG_USER);
  
  fprintf(stderr,"Creating classconf:\n");
  TRY(cc = codb_classconf_new(), "");
  
  fprintf(stderr,"Creating types:\n");
  t[T1] = codb_typedef_new_re("string", ".*");
  t[T2] = codb_typedef_new_re("integer", "^[0-9]*$");
  t[T3] = codb_typedef_new_re("alphanum", "^[a-zA-Z0-9_\\-]*$");
  t[T4] = codb_typedef_new_re("shite", "shite?");

	fprintf(stderr, "Testing types:\n");
  TESTSTRING("foo", (1<<T1) | (1<<T3) );
	TESTSTRING("foo bar", (1<<T1) );
  TESTSTRING("", (1<<T1)|(1<<T2)|(1<<T3));
  TESTSTRING("1", (1<<T1)|(1<<T2)|(1<<T3));
  TESTSTRING("0", (1<<T1)|(1<<T2)|(1<<T3));
  TESTSTRING("342305", (1<<T1)|(1<<T2)|(1<<T3));
  TESTSTRING("crashiterator",(1<<T1)|(1<<T3)|(1<<T4));

  fprintf(stderr, "Adding types:\n");
	TRY(codb_classconf_settype(cc, t[T1])==0,"");
	TRY(codb_classconf_settype(cc, t[T2])==0,"");
	TRY(codb_classconf_settype(cc, t[T3])==0,"");
	TRY(codb_classconf_settype(cc, t[T4])==0,"");

	fprintf(stderr, "Creating ClassA:\n");
  TRY(classA = codb_class_new("ClassA", "", "1.0"), ""); 

	fprintf(stderr, "Creating and adding properties to ClassA:\n");
  TRY (codb_class_addproperty(classA, 
    codb_property_new("foo","string","","",""))==0, "");
  TRY (codb_class_addproperty(classA, 
    codb_property_new("num","integer","","",""))==0, "");
  TRY (codb_class_addproperty(classA, 
    codb_property_new("bar","string","","",""))==0, "");
  TRY (codb_class_addproperty(classA, 
    codb_property_new("toilet","shite","","",""))==0, "");

  fprintf(stderr, "Adding ClassA to classconf:\n");
  TRY(codb_classconf_setclass(cc, classA) == 0, "");

  fprintf(stderr, "Binding:\n");
  TRY(codb_classconf_bindtypes(cc) == 0, "");
  
  {
  	GHashTable *attribs, *errs;
    attribs = g_hash_table_new(g_str_hash, g_str_equal);
    errs = g_hash_table_new(g_str_hash, g_str_equal);
    g_hash_table_insert(attribs, "foo", "the foo property");
    g_hash_table_insert(attribs, "num", "42");
    TRY(codb_class_validate(classA, attribs, errs), "");
    g_hash_table_insert(attribs, "num", "not-a-number");
    TRY(!codb_class_validate(classA, attribs, errs), "");
    g_hash_table_remove(attribs, "num");
    TRY(codb_class_validate(classA, attribs, errs), "");
    g_hash_table_insert(attribs, "fubar", "not-a-valid-property");
    TRY(!codb_class_validate(classA, attribs, errs), "");
    g_hash_table_remove(attribs, "fubar");
    
    TRY(codb_classconf_validate(cc, "ClassA", "", attribs, errs), "");
    g_hash_table_insert(attribs, "num", "not-a-number");
    TRY(!codb_classconf_validate(cc, "ClassA", "", attribs, errs), "");
    
	}    
  
  END_MAIN ;
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
