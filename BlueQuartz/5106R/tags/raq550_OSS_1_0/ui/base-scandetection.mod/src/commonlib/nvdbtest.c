/*

$Header: /home/cvs/base-scandetection.mod/src/commonlib/nvdbtest.c,v 1.1 2001/09/18 16:59:37 jthrowe Exp $

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/nvdbtest.c,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  Sat Aug 22 13:22:28 EDT 1998
    Originating Author :  MJ Hullhorst

      Last Modified by :  $Author: jthrowe $ 
    Date Last Modified :  $Date: 2001/09/18 16:59:37 $

   **********************************************************************

   Copyright (c) 1999 Progressive Systems Inc.
   All rights reserved.

   This code is confidential property of Progressive Systems Inc.  The
   algorithms, methods and software used herein may not be duplicated or
   disclosed to any party without the express written consent from
   Progressive Systems Inc.

   Progressive Systems Inc. makes no representations concerning either
   the merchantability of this software or the suitability of this
   software for any particular purpose.

   These notices must be retained in any copies of any part of this
   documentation and/or software.

   ********************************************************************** */

# include <stdio.h>
# include <sys/types.h>
# include <sys/stat.h>

# include "libnvdb.h"

int debug = 9 ;

main()
{
   int inc ;

   NVDB *testdb ;

   for( inc=1; inc<3; inc++ )
   {
       printf( "\n*** Pass %d ***********************************\n", inc  ) ;

       testdb = nvdb_open( "junk.nvdb", "=", S_IRUSR|S_IWUSR ) ;

       nvdb_put( testdb, "a", "1" ) ;
       nvdb_put( testdb, "b", "2" ) ;
       nvdb_put( testdb, "c", "3 !\"#$%&'()*+,-./09:;<=>?@AZ[\\]^_`az{|}~" ) ;
       nvdb_put( testdb, "e", "5 $a ` ' \\ " ) ;
       nvdb_put( testdb, "f", "6" ) ;
       nvdb_put( testdb, "g", "7 ' ' ' '     ' ' ' '" ) ;
       nvdb_put( testdb, "h", "8" ) ;
       nvdb_put( testdb, "i", "9" ) ;
       nvdb_put( testdb, "j", "0" ) ;

       nvdb_del( testdb, "b" ) ;
       nvdb_del( testdb, "d" ) ;
       nvdb_del( testdb, "k" ) ;
       nvdb_del( testdb, "q" ) ;

       //printf( "g:eth0Device:%s\n", nvdb_get( testdb, "eth0Device" ) ) ;
       //printf( "g:portFlag:%d\n", nvdb_get_int( testdb, "portFlag" ) ) ;

       //nvdb_del( testdb, "eth0Device" ) ;

       printf( "== C %s\n", nvdb_get( testdb, "c" ) ) ;
       printf( "== G %s\n", nvdb_get( testdb, "g" ) ) ;

       nvdb_listdb( testdb, 0 ) ;

       printf( "\n\n" ) ;
       nvdb_del( testdb, "aa" ) ;
       printf( "aa should not exist: %d\n", nvdb_exists( testdb, "aa" ) ) ;
       nvdb_put( testdb, "aa", "0" ) ;
       printf( "aa should     exist: %d\n", nvdb_exists( testdb, "aa" ) ) ;
       nvdb_del( testdb, "aa" ) ;
       printf( "aa should not exist: %d\n", nvdb_exists( testdb, "aa" ) ) ;

       nvdb_close( testdb ) ;
   }
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
