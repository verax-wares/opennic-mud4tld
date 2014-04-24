/*
	WHOIS - Prototype server for opennicproject.org
	Written 2012 By Martin Coleman.
    Edited 2014.04.24 for public domain release.
    This program, the WHOIS Prototype server aka wit_pc.c, is hereby released into the public domain.

	Version 0.1
	- Interfaces with sockets
	- Interfaces with SQLite3

	Version 0.2
	- Improved domain handling upon query

	Version 0.3
	- Added nameservers information to WHOIS output.
	- Added compiler define flag for testing.

	Version 0.4
	- Complies a little better with http://www.ietf.org/rfc/rfc3912.txt
	- Improved string and memory management.

	Version 0.5
	- Reads records from plain text files only.

    Version 0.5a - 2014.04.24
    - Dedicated to the public domain.

	ADMIN NOTES:
	* Compile with -DWHOIS_TEST for it to use port 4343 instead of 43 to try it out.
	* Now takes -DVERBOSE as a compile time option for debugging and more verbose output

	DEV NOTES:
	* I recommend a tab-width of 4.
	* Needs more templating work.
*/
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <netdb.h>
#include <stdio.h>
#include <unistd.h> /* close */

#define SUCCESS 0
#define ERROR 1

#ifdef WHOIS_TEST
#define SERVER_PORT 4343
#else
#define SERVER_PORT 43
#endif
#define MAX_MSG 64
#define RB_LENGTH	2048

char *domain_record;

void chomp(char *s)
{
    while(*s && *s != '\n' && *s != '\r' && *s != '_'  && *s != '"'  && *s != '\'') s++;

    *s = 0;
}

int ReadFile(char *name)
{
	FILE *file;
	char *buffer;
	unsigned long fileLen;

	//Open file
	file = fopen(name, "rb");
	if (!file)
	{
		fprintf(stderr, "Unable to open file %s\n", name);
		return 0;
	}
	
	//Get file length
	fseek(file, 0, SEEK_END);
	fileLen=ftell(file);
	fseek(file, 0, SEEK_SET);

	//Allocate memory
	buffer=(char *)malloc(fileLen+1);
	if (!buffer)
	{
		fprintf(stderr, "Memory error!");
        fclose(file);
		return 0;
	}

	//Read file contents into buffer
	fread(buffer, fileLen, 1, file);
	fclose(file);

	domain_record=strdup(buffer);

	free(buffer);
	return 1;
}

int query_domain(char domainname[56])
{
	char domain_file[60];
	char *tld;
	char *domain;
	char *tmp;
	//tld[0]='\0';
	
	chomp(domainname);
	tmp=strdup(domainname);

	tld=strchr(domainname, '.');
	domain=strtok(tmp, ".");
	printf("%s_%s.txt\n", tld+1, domain);
	sprintf(domain_file, "%s_%s.txt", tld+1, domain);

	if(!ReadFile(domain_file))
	{
		return 0;
	}
	return 1;
}

int main (int argc, char *argv[])
{
	int sd, newSd, cliLen;

	struct sockaddr_in cliAddr, servAddr;
	char line[MAX_MSG];
	char return_buffer[RB_LENGTH];
	char no_result[10]="NO RESULT";
	char ret_svr[10]="127.0.0.1";

	memset(return_buffer, 0, 2048);
	printf("WIT/WHOIS Prototype server for The OpenNIC Project. By Martin Coleman. Public Domain.\n");
	/* create socket */
	sd = socket(AF_INET, SOCK_STREAM, 0);
	if(sd<0)
	{
		fprintf(stderr, "cannot open socket\n");
		return ERROR;
	}

	/* bind server port */
	servAddr.sin_family = AF_INET;
	servAddr.sin_addr.s_addr = htonl(INADDR_ANY);
	servAddr.sin_port = htons(SERVER_PORT);

	if(bind(sd, (struct sockaddr *) &servAddr, sizeof(servAddr))<0)
	{
		fprintf(stderr, "cannot bind port\n");
		return ERROR;
	}

	listen(sd,5);
	while(1)
	{
		#ifdef VERBOSE
		printf("%s: waiting for data on port TCP %u\n",argv[0],SERVER_PORT);
		#endif
		cliLen = sizeof(cliAddr);
		newSd = accept(sd, (struct sockaddr *) &cliAddr, &cliLen);
		if(newSd<0)
		{
			fprintf(stderr, "cannot accept connection\n");
			return ERROR;
		}

		/* init line */
		memset(line, 0, MAX_MSG);

		/* receive segments */
		int rc=0;
		if(rc=recv(newSd, line, MAX_MSG, 0))
		{
			chomp(line);

			#ifdef VERBOSE
			printf("query received from %s for %s\n", inet_ntoa(cliAddr.sin_addr), line);
			#endif

			if(!query_domain(line))
			{
				send(newSd, no_result, 10, 0);
			} else {
				sprintf(return_buffer, "%s\r\n", domain_record);
				send(newSd, return_buffer, RB_LENGTH, 0);
			}
			memset(line, 0, MAX_MSG);
			memset(return_buffer, 0, 2048);
		}
		close(newSd);
		memset(line, 0, MAX_MSG);
	}
}
