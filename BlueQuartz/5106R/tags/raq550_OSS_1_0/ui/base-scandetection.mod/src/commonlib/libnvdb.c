/*

$Header: /home/cvs/base-scandetection.mod/src/commonlib/libnvdb.c,v 1.1 2001/09/18 16:59:37 jthrowe Exp $

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/libnvdb.c,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  Sat Aug 22 13:22:28 EDT 1998
    Originating Author :  Ge' Weijers, MJ Hullhorst

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
# include <stdlib.h>
# include "libnvdb.h"

# define IN_LINE_MAX 80 

// **********************************************************************

NVDB*
nvdb_open( char *filename, char *delimiter, int mode )
{
   NVDB *dbptr ;

   FILE *fileptr ;

   char linein[ IN_LINE_MAX ] ;
   char readin[ IN_LINE_MAX ] ;

   int inc, cptr, msize, linesize, readsize ;

   // ****************************************

   if ( delimiter[0] == 0 ) return 0 ;

   LOG( LOG_SRVR, "NVDB: open: file %s w/delimiter %s\n", 
       filename, delimiter ) ;

   // Initialize 
   dbptr = (NVDB*) malloc( sizeof( NVDB ) ) ;

   if ( dbptr == NULL )
   {
      LOG( LOG_SRVR, "NVDB: open: malloc error\n" ) ;
      return dbptr ;
   }

   dbptr->mode = mode ;

   dbptr->delimiter[0]=delimiter[0] ;
   dbptr->delimiter[1]=0;

   dbptr->recindex = -1 ;
   for( inc = 0 ; inc < NVDB_ENTRYMAX; inc++ )
   {
      dbptr->name[inc]=0;
      dbptr->value[inc]=0;
   }

   // Save the filename 
   dbptr->filename = (char*) malloc( strlen( filename ) + 1 ) ;
   memcpy( dbptr->filename, filename, strlen( filename ) + 1 ) ;

   fileptr = fopen( dbptr->filename, "r" ) ;
   if ( NULL == fileptr )
   {
      LOG( LOG_SRVR, "NVDB: open: file %s not found\n", dbptr->filename ) ;
      return dbptr ;
   }

   while ( ( readsize = fgetline ( readin, IN_LINE_MAX, fileptr ) ) != EOF )
   {
      if ( readsize == 0 ) continue ;
      readin[ readsize - 1 ] = 0 ;

      LOG( LOG_SRVR, "NVDB: open: inpt: >%s<%d\n", readin, readsize ) ;

      // Ignore Comments
      if ( readin[ 0 ] == '#' || readin[ 0 ] == '!' ) continue ;

      linesize=nvdb_removeescapes( readin, readsize - 1, linein ) ;

      cptr = 0 ;
      while ( cptr < IN_LINE_MAX && linein[cptr] != '\n' && linein[cptr] != 0 )
      {
         if ( linein[cptr] == delimiter[0] ) 
         {
            if ( dbptr->recindex >= NVDB_ENTRYMAX ) break ;
            dbptr->recindex++ ;
            linein[cptr] = 0 ;

            dbptr->name[ dbptr->recindex ] = (char*) malloc( cptr + 1 ) ;
            memcpy( dbptr->name[ dbptr->recindex ], linein, cptr + 1 ) ;

            msize = linesize - cptr - 1 ;
            dbptr->value[ dbptr->recindex ] = (char*) malloc( msize ) ;
            memcpy( dbptr->value[ dbptr->recindex ], &linein[ cptr+1 ], msize ) ;
            dbptr->value[ dbptr->recindex + msize + 1 ] = 0 ;

            LOG( LOG_SRVR, "NVDB: open: appd: >%s<%d @ %d w/>%s<%d\n", 
                  dbptr->name[ dbptr->recindex ], cptr + 1, 
                  dbptr->recindex, dbptr->value[ dbptr->recindex ], msize ) ;

            break ;
         }
         cptr++ ;
      }
   }
   fclose( fileptr ) ;
   return dbptr ;
}

// **********************************************************************

int
nvdb_count( NVDB *dbptr ) 
{
   return ( dbptr->recindex + 1 ) ;
}

// **********************************************************************

int
nvdb_del( NVDB *dbptr, char *namestr ) 
{
   int inc ;

   if ( strlen( namestr ) == 0 ) return -1 ;

   for( inc = 0 ; inc <= dbptr->recindex ; inc++ )
   {
      if ( dbptr->name[ inc ] != 0 )
      {
         if ( strcmp( namestr, dbptr->name[ inc ] ) == 0 )
         {
            free( dbptr->value[ inc ] ) ;
            dbptr->value[ inc ] = 0 ;
            free( dbptr->name[ inc ] ) ;
            dbptr->name[ inc ] = 0 ;
            if ( inc == dbptr->recindex ) dbptr->recindex-- ;
            LOG( LOG_SRVR, "NVDB: del: %s @ %d index %d\n", namestr, inc, 
                dbptr->recindex ) ;
            return 1 ;
         }
      }
   }
   return 0 ;
}

// **********************************************************************

int
nvdb_exists( NVDB *dbptr, char *namestr ) 
{
   int inc ;

   if ( strlen( namestr ) == 0 ) return 0 ;

   for( inc = 0 ; inc <= dbptr->recindex ; inc++ )
      if ( dbptr->name[ inc ] != 0 )
         if ( strcmp( namestr, dbptr->name[ inc ] ) == 0 )
            return 1 ;

   return 0 ;
}

// **********************************************************************

char*
nvdb_get( NVDB *dbptr, char *namestr ) 
{
   int inc ;

   if ( strlen( namestr ) == 0 ) return "" ;

   for( inc = 0 ; inc <= dbptr->recindex ; inc++ )
      if ( dbptr->name[ inc ] != 0 )
         if ( strcmp( namestr, dbptr->name[ inc ] ) == 0 )
         {
            LOG( LOG_SRVR, "NVDB: get: %s >%s< @ %d\n", 
               namestr, dbptr->value[ inc ], inc ) ;
            return ( dbptr->value[ inc ] ) ;
         }

   return "" ;
}

// **********************************************************************

int 
nvdb_get_int( NVDB *dbptr, char *namestr ) 
{
   int inc ;

   if ( strlen( namestr ) == 0 ) return 0 ;

   for( inc = 0 ; inc <= dbptr->recindex ; inc++ )
      if ( dbptr->name[ inc ] != 0 )
         if ( strcmp( namestr, dbptr->name[ inc ] ) == 0 )
         {
            LOG( LOG_SRVR, "NVDB: get_int: %s >%s< @ %d\n", namestr, dbptr->value[ inc ], inc ) ;
            return ( atoi( dbptr->value[ inc ] ) ) ;
         }

   return 0 ;
}

// **********************************************************************

int   
nvdb_put_int( NVDB *dbptr, char *namestr, int valueint )
{
   char valuestr[ 20 ] ;
   snprintf( valuestr, 20, "%d", valueint ) ;
   return nvdb_put( dbptr, namestr, valuestr ) ;
}

// **********************************************************************

int   
nvdb_put( NVDB *dbptr, const char *namestr, const char *valuestr ) 
{
   int inc, namesize, valuesize ;
   int newInc = -1;
   char empty[] = "";

   if ( ( namesize  = strlen( namestr ) ) == 0 )  return -1 ;
   valuesize = strlen( valuestr ) ;

   // look for an existing name entry.  If we find it then we replace it.
   for( inc = 0 ; inc <= dbptr->recindex ; inc++ )
   {
      if ( dbptr->name[ inc ] != 0 )
      {
         if ( strcmp( namestr, dbptr->name[ inc ] ) == 0 )
         {
             LOG( LOG_SRVR, "NVDB: put: rplc @ %d >%s<\n", inc, 
                dbptr->value[ inc ] ) ;
             free( dbptr->value[ inc ] ) ;
             if ( valuestr == NULL )
             {
                LOG( LOG_SRVR, "NVDB: put: rplc @ %d w/NULL\n", inc ) ;
                dbptr->value[ inc ] = (char*) malloc( 1 ) ;
                (dbptr->value[inc])[0] = '\0';
             } else
             {
                dbptr->value[ inc ] = (char*) malloc( valuesize + 1 ) ;
                memcpy( dbptr->value[ inc ], valuestr, valuesize + 1 ) ;
             }

            return 0 ;
         }
      }
      else
      {
          // Store the index of the first unused entry
          if (newInc < 0)
          {
              newInc = inc;
          }
      }
   }

   // Append another entry
   if ( dbptr->recindex >= NVDB_ENTRYMAX ) return -2 ;

   // If we found an unused entry above, use that; otherwise,
   // append a new entry to the end.
   if (newInc < 0)
   {
       dbptr->recindex++ ;
       newInc = dbptr->recindex;
   }

   dbptr->name[ newInc ] = (char*) malloc( namesize + 1 ) ;
   memcpy( dbptr->name[ newInc ], namestr, namesize + 1 ) ;

   if ( valuestr == NULL )
   {
      LOG( LOG_SRVR, "NVDB: put: appd @ %d w/NULL\n", newInc ) ;
      dbptr->value[ newInc ] = (char*) malloc( 1 ) ;
      (dbptr->value[newInc])[0] = '\0';
   } else
   {
      dbptr->value[ newInc ] = (char*) malloc( valuesize + 1 ) ;
      memcpy( dbptr->value[ newInc ], valuestr, valuesize + 1 ) ;
      LOG( LOG_SRVR, "NVDB: put: appd: %s @ %d w/>%s<\n", 
         dbptr->name[ newInc ], 
         newInc, 
         dbptr->value[ newInc ] ) ;
   }
   return 0 ;
}

// **********************************************************************

int
nvdb_listdb( NVDB *dbptr, int logflag )
{
   int inc ;

   if ( logflag )
      LOG( LOG_SRVR, "NVDB: info: file >%s< w/ %d elements delimiter >%s<",
         dbptr->filename, dbptr->recindex, dbptr->delimiter ) ;
   else
      printf( "NVDB: info: file >%s< w/ %d elements delimiter >%s<\n",
         dbptr->filename, dbptr->recindex, dbptr->delimiter ) ;
      
   for( inc = 0 ; inc <= dbptr->recindex ; inc++ )
      if ( dbptr->name[inc] != 0 )
         if ( logflag ) 
             LOG( LOG_SRVR, "NVDB: info: %02d >%s<%d >%s<%d\n", 
                  inc, 
                  dbptr->name[inc], 
                  strlen( dbptr->name[inc] ),
                  dbptr->value[inc],
                  strlen( dbptr->value[inc] ) ) ;
         else
             printf( "NVDB: info: %02d >%s<%d >%s<%d\n", 
                  inc, 
                  dbptr->name[inc], 
                  strlen( dbptr->name[inc] ),
                  dbptr->value[inc],
                  strlen( dbptr->value[inc] ) ) ;
}

// **********************************************************************

int
nvdb_reset_db( NVDB *dbptr ) 
{
   return nvdb_free_elements( dbptr ) ;
}

// **********************************************************************

int   
nvdb_close_nowrite( NVDB *dbptr ) 
{
   return nvdb_free_all( dbptr ) ;
}

// **********************************************************************

int   
nvdb_free_all( NVDB *dbptr ) 
{
   nvdb_free_elements( dbptr ) ;
   LOG( LOG_SRVR, "NVDB: freeing db structs\n" ) ;
   free( dbptr->filename ) ;
   free( dbptr ) ;
}

// **********************************************************************

int   
nvdb_free_elements( NVDB *dbptr ) 
{
   int inc ;

   LOG( LOG_SRVR, "NVDB: freeing elements\n" ) ;
   for( inc = 0 ; inc <= dbptr->recindex ; inc++ )
   {
      if ( dbptr->name[inc] != 0 )
      {
         free( dbptr->name[inc] ) ;
         dbptr->name[inc] = 0 ;
         if (dbptr->value[inc] == 0)
         {
             LOG (LOG_SRVR, "nvdb_free_elements: value NULL for name=%s", 
                  dbptr->name[inc]);
         }
         else
         {
             free( dbptr->value[inc] ) ;
             dbptr->value[inc] = 0 ;
         }
      }
   }
   dbptr->recindex = -1 ;
}

// **********************************************************************

int   
nvdb_write( NVDB *dbptr ) 
{
   int inc ;

   FILE *fileptr ;
   char* outbuff ;

   LOG( LOG_SRVR, "NVDB: closing to file %s\n", dbptr->filename ) ;

   if ( ( fileptr = fopen( dbptr->filename, "w" ) ) == NULL )
   {
      LOG( LOG_SRVR, "NVDB: close: file open error %s\n", 
         dbptr->filename ) ;
      return nvdb_free_all( dbptr ) ;
   }

   fprintf( fileptr, "#\n" ) ;
   fprintf( fileptr, "# This file is managed by NVlib.  Manual additions and updates will be\n" ) ;
   fprintf( fileptr, "# maintained.  All comments, however,  will be lost at the next update.\n" ) ;
   fprintf( fileptr, "#\n" ) ;

   for( inc = 0 ; inc <= dbptr->recindex ; inc++ )
   {
      if ( dbptr->name[inc] != 0 )
      {
         outbuff = (char*) malloc( IN_LINE_MAX * 2 ) ;
         nvdb_insertescapes( dbptr->value[inc], 
             strlen( dbptr->value[inc] ), outbuff ) ;
         LOG( LOG_SRVR, "NVDB: writing %d %s%s%s\n", inc, 
            dbptr->name[inc], dbptr->delimiter, outbuff ) ;

         fprintf( fileptr, "%s%s%s\n", dbptr->name[inc], 
            dbptr->delimiter, outbuff ) ;
         free( outbuff ) ;
/*
         LOG( LOG_SRVR, "NVDB: writing %d %s%s%s\n", inc, 
            dbptr->name[inc], dbptr->delimiter, dbptr->value[inc] ) ;
         fprintf( fileptr, "%s%s%s\n", dbptr->name[inc], 
            dbptr->delimiter, dbptr->value[inc] ) ;
*/
      }
   }
   fclose( fileptr ) ;
   chmod( dbptr->filename, dbptr->mode ) ;
   return 0 ;
}

// **********************************************************************

int   
nvdb_close( NVDB *dbptr ) 
{
   nvdb_write( dbptr ) ;
   chmod( dbptr->filename, dbptr->mode ) ;
   return nvdb_free_all( dbptr )  ;
}

// **********************************************************************

int
nvdb_insertescapes( char *inbuff, int incnt, char *outbuff )
{
   int i, o ;
   o=0 ;
   for( i=0; i<=incnt  ; i++ )
   {
      switch( inbuff[i] )
      {
         case ' ' :
         case '\t' :
         case '\"' :
         case '$' :
         case '\'' :
         case '*' :
         case '\\' :
         case '|' :
         case '&' :
         case ';' :
         case '(' :
         case ')' :
         case '<' :
         case '>' :
         case '[' :
         case ']' :
         case '!' :
         case '`' :
            outbuff[o++]='\\' ;
            outbuff[o++]=inbuff[i] ;
            break ;

         default :
            outbuff[o++]=inbuff[i] ;
            break ;
      }
   }
   outbuff[o++]=0 ;
   return o ;
}

//* *********************************************************************

int
nvdb_removeescapes( char *inbuff, int incnt, char *outbuff )
{
   int i, o, flag ;
   o=0 ;
   flag=0 ;
   for( i=0; i<=incnt ; i++ )
   {
      switch( inbuff[i] )
      {
         case '\\' :
            if ( flag==0 )
            {
               flag=1 ;
               break ;
            }

         default :
            outbuff[o++]=inbuff[i] ;
            flag=0 ;
            break ;
      }
   }
   return o ;
}

//* *********************************************************************
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
