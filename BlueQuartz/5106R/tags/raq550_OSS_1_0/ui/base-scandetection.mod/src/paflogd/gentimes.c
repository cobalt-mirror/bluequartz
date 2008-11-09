/*
 * $Id: gentimes.c,v 1.1 2001/09/18 16:59:38 jthrowe Exp $
 */

#include <stdio.h>

#include <stdlib.h>

#include <unistd.h>
#include <sys/types.h>
#include <time.h>

// **********************************************************************

void deadline() 
{
   printf("/* Automatically generated */\n\n");
   printf("#define DEADLINE 0x%08lxlu\n", 
      (unsigned long)(time(NULL) + 50 * 24 * 60 * 60));
}

// **********************************************************************

void versions() 
{

   struct tm *t;
   time_t     thetime;
   char      *letter = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

   thetime = time( NULL );
   t = localtime( &thetime );

/* "V9222.38M5" */
   printf("/* Automatically generated */\n\n");
   printf( "char\t*version = \"V%02d%02d.%02d%c%01d\";\n",
         t->tm_year-(t->tm_year/100)*100,
         t->tm_mon+1,
         t->tm_mday,
         letter[t->tm_hour],
         t->tm_min/10 );
}

// **********************************************************************

int main ( int argc, char *argv[] )
{
     int opt;

     while( ( opt = getopt( argc, argv, "dv" ) ) != EOF )
     {
         switch(opt)
         {
            case 'd':
               deadline() ;
               exit( 0 );
            case 'v':
               versions() ;
               exit( 0 ) ;
         }
     }
     return 0 ;
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
