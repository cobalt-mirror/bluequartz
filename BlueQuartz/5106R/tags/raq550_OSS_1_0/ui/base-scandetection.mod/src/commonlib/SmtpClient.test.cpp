#include "SmtpClient.h"

int debug = 0;

int main (int argc, char * argv[])
{
    SmtpClient client ("smtp.progressive-systems.com");

    string str = client.getSmtpServer ();

    cout << "SMTP Server = " << str << endl;

    EmailMessage msg ("brian@progressive-systems.com",
		      "brian@lojic.com",
		      "howdy!",
		      "This is my message.");
    client.sendMessage (msg);

    return 0;
}
