/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/ObjectInput.cpp,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  26-Sep-2000
    Originating Author :  Brian Adkins

      Last Modified by :  $Author: jthrowe $ 
    Date Last Modified :  $Date: 2001/09/18 16:59:37 $

   **********************************************************************

   Copyright (c) 2000 Progressive Systems Inc.
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

#include <typeinfo>
#include "ObjectInput.h"
#include "Exception.h"

#include "DataUnit.h" // temporary

typedef Externalizable * (*PF)();

extern map<string, PF> io_map;

Externalizable * ObjectInput::readObject ()
{
    if (io_map.size() < 1)
    {
	throw IOException ("Error: empty io_map");
    }

    string className = readString();

    PF foo = io_map[className];

    if (foo == 0)
    {
	throw IOException ("Invalid class name: " + className);
    }

    Externalizable * obj = foo ();

    obj->readExternal (*this);

    return obj;
}
