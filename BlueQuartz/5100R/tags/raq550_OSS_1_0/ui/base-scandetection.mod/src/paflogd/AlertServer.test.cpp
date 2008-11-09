#include "Socket.h"
#include "CommandPacket.h"
#include "Exception.h"
#include "AlertServer.h"
#include "AlertSettings.h"
#include "ObjectSocket.h"
#include <typeinfo>
#include "Alert.h"
#include "AlertSettings.h"
#include "CommandPacket.h"
#include "DataUnit.h"
#include "ErrorPacket.h"

using namespace std;

typedef Externalizable * (*PF)();
map<string, PF> io_map;

static void init_io_map ()
{
    // Alert
    io_map[typeid(Alert).name()] = 
	&Alert::createExternalizable;

    // AlertSettings
    io_map[typeid(AlertSettings).name()] = 
	&AlertSettings::createExternalizable;

    // CommandPacket
    io_map[typeid(CommandPacket).name()] = 
	&CommandPacket::createExternalizable;

    // DataUnit
    io_map[typeid(DataUnit).name()] = 
	&DataUnit::createExternalizable;

    // ErrorPacket
    io_map[typeid(ErrorPacket).name()] = 
	&ErrorPacket::createExternalizable;
}

int main ()
{
    try
    {
	// Initialize the io_map
	init_io_map ();

	// Obtain an ObjectSocket to the Alert server
	Socket socket ("127.0.0.1", AlertServer::SERVER_PORT);
	ObjectSocket os (&socket);

	// Create a dummy AlertSettings object
	AlertSettings * settings = new AlertSettings ();
	settings->setEmailAddress ("brian@cobalt.com");
	settings->setLowDiskSpace (true);
	settings->setLowDiskSpaceValue (20000);
	settings->setSmtpServer ("129.154.100.11");
	settings->setScanAttack (true);
	settings->setRestrictedSiteAccess (true);

	// Create and send a SET_ALERT command packet
	CommandPacket setSettings (AlertServer::SET_ALERT, settings);
	os.writeObject (setSettings);

	// Receive a response
	DataUnit * response = dynamic_cast<DataUnit*>(os.readObject());
	assert (response != 0);
	
	CommandPacket * pkt = dynamic_cast<CommandPacket*>(response);

	if (pkt != 0)
	{
	    if (pkt->data() == CommandPacket::SUCCESS)
	    {
		cout << "SET_ALERT returned success\n";
	    }
	    else
	    {
		assert (0);
	    }
	}
	else
	{
	    // must be an ErrorPacket
	    assert (0);
	}

	delete response;

	// Create and send a GET_ALERT command packet
	CommandPacket cmd (AlertServer::GET_ALERT);
	os.writeObject (cmd);

	// Receive a response
	pkt = dynamic_cast<CommandPacket*>(os.readObject());
	
	if (pkt == 0)
	{
	    throw RuntimeException (
		"GET_ALERT: invalid response");
	}

	delete settings;
	settings = dynamic_cast<AlertSettings*>(pkt->getAttachment());

	if (settings == 0)
	{
	    throw RuntimeException (
		"pafdaemon.c:getalertsettings(): invalid attachment");
	}

	cout << settings->getEmailAddress() << endl;
	cout << settings->getLowDiskSpace() << endl;
	cout << settings->getLowDiskSpaceValue() << endl;
	cout << settings->getSmtpServer() << endl;
	cout << settings->getScanAttack() << endl;
	cout << settings->getRestrictedSiteAccess() << endl;
    }
    catch (Exception& ex)
    {
      cout << ex.message() << endl;
    }    
    catch (...)
    {
	cout << "unexpected exception caught" << endl;
    }

    return 0;
}
