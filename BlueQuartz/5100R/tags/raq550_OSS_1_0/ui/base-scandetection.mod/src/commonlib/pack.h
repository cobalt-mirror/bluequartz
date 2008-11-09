/*

$Header: /home/cvs/base-scandetection.mod/src/commonlib/pack.h,v 1.1 2001/09/18 16:59:37 jthrowe Exp $

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/pack.h,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  Sat Aug 22 13:22:28 EDT 1998
    Originating Author :  Ge' Weijers, MJ Hullhorst

      Last Modified by :  $Author: jthrowe $ 
    Date Last Modified :  $Date: 2001/09/18 16:59:37 $

   **********************************************************************

   Copyright (c) 1997-1998 Progressive Systems Inc.
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

   **********************************************************************

*/


#ifndef _PACK_H_
#define _PACK_H_

#include "port.h"

/* convert 4 bytes into longword */
#if !defined(SIGNED)

#define XB(P,N,S)  ((WORD32)(P)[(N)] << (S))

#else

#define XB(P,N,S)  ((WORD32)((P)[(N)] & 0xff) << (S))

#endif

#define PACK32(cp) (XB(cp, 0, 24) | XB(cp, 1, 16) | XB(cp, 2, 8) | XB(cp, 3, 0))

/* convert longword into 4 bytes */
#define UNPACK32(W,PB,T)   \
T = (W);                   \
(PB)[0] = (BYTE)(T >> 24); \
(PB)[1] = (BYTE)(T >> 16); \
(PB)[2] = (BYTE)(T >> 8);  \
(PB)[3] = (BYTE)(T);



#define PACK16(P) \
 (((WORD32)((P)[0] & 0xff) <<  8) | (WORD32)((P)[1] & 0xff) )

#define UNPACK16(W,P) \
     (P)[0] = (BYTE)(((W) >>  8) & 0xff); \
     (P)[1] = (BYTE)( (W)        & 0xff); 


/*
#define PACK32(P) \
(((WORD32)((P)[0] & 0xff) << 24) | ((WORD32)((P)[1] & 0xff) << 16) \
 | ((WORD32)((P)[2] & 0xff) << 8) | (WORD32)((P)[3] & 0xff))

#define UNPACK32(W,P) \
     (P)[0] = (BYTE)(((W) >> 24) & 0xff); \
     (P)[1] = (BYTE)(((W) >> 16) & 0xff); \
     (P)[2] = (BYTE)(((W) >> 8) & 0xff); \
     (P)[3] = (BYTE)((W) & 0xff);

*/

#endif /*_PACK_H_*/
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
