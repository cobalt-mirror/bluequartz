#include <stdio.h>
#include "Socket.h"
#include "ServerSocket.h"
#include "ObjectSocket.h"
#include "DataUnit.h"

#define CLIENT "client"
#define SERVER "server"
#define PORT 7357

int main (int argc, char * argv[])
{
    try
    {
	if (strcmp (argv[1], CLIENT) == 0)
	{
	    Socket sock ("127.0.0.1", PORT);
	    ObjectSocket obj (&sock);
	    DataUnit pdu ("carpe diem!");
	    obj.writeObject (pdu);
	    sock.close ();
	}
	else if (strcmp (argv[1], SERVER) == 0)
	{
	    ServerSocket s(PORT);
	    
	    while (true)
	    {
		Socket * sock = s.accept ();

		ObjectSocket obj (sock);
		
		void * x = malloc (4);

		delete sock;
	    }
	}
    }
    catch (...)
    {
	assert (0);
    }

    return 0;
}
