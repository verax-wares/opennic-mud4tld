/*
	WHOIS server for opennicproject.org
	By Martin Coleman (C) 2012. All rights reserved.
	
	Version 0.1
	- Interfaces with sockets
	- Interfaces with SQLite3

	Version 0.2
	- Improved domain handling upon query
	
	WARNING!! WARNING!! WARNING!! WARNING!! WARNING!!
	This is a big hack. This really needs to be 
	cleaned up, but it is functional for now. There
	is some redundant code, and commented out code
	everywhere as I tried to get the damn thing to
	run. It seems socket programming really can be
	intricate.
	WARNING!! WARNING!! WARNING!! WARNING!! WARNING!!
*/
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sqlite3.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <netdb.h>
#include <stdio.h>
#include <unistd.h> /* close */

#define SUCCESS 0
#define ERROR 1

#define SERVER_PORT 4343
#define MAX_MSG 53
#define RB_LENGTH	1024

#define DEBUG 1
#define VERBOSE 1

struct {
	char dr_domain[51];
	char dr_registered[16];
	char dr_name[20];
	char dr_email[50];
} DOMAINRECORD;
// DOMAINRECORD *record;

sqlite3 *db;
sqlite3_stmt *res;
char *zErrMsg=0;
int rc;

void chomp(char *s)
{
    while(*s && *s != '\n' && *s != '\r' && *s != '_'  && *s != '"'  && *s != '\'') s++;

    *s = 0;
}

/*
 * Search and replace a string with another string , in a string
 * */
char *str_replace(char *search , char *replace , char *subject)
{
	char  *p = NULL , *old = NULL , *new_subject = NULL ;
	int c = 0 , search_size;

	search_size = strlen(search);

	//Count how many occurences
	for(p = strstr(subject , search) ; p != NULL ; p = strstr(p + search_size , search))
	{
		c++;
	}

	//Final size
	c = ( strlen(replace) - search_size )*c + strlen(subject);

	//New subject with new size
	new_subject = malloc( c );

	//Set it to blank
	strcpy(new_subject , "");

	//The start position
	old = subject;

	for(p = strstr(subject , search) ; p != NULL ; p = strstr(p + search_size , search))
	{
		//move ahead and copy some text from original subject , from a certain position
		strncpy(new_subject + strlen(new_subject) , old , p - old);

		//move ahead and copy the replacement text
		strcpy(new_subject + strlen(new_subject) , replace);

		//The new start position after this search match
		old = p + search_size;
	}

	//Copy the part after the last search match
	strcpy(new_subject + strlen(new_subject) , old);

	return new_subject;
}

int query_domain(char domainname[50])
{
	char sql_str[1024];
	int result=0;

	domainname[strlen(domainname)-1]=0;
	domainname[strlen(domainname)-1]=0;
	domainname[strlen(domainname)-1]=0;
	chomp(domainname);
	rc = sqlite3_open("OZ_tld.sq3", &db);
	if(rc)
	{
		fprintf(stderr, "Can't open package database.");
		sqlite3_close(db);
		return 0;
	}
	sprintf(sql_str, "SELECT domain, registered, name, email FROM domains WHERE domain='%s' LIMIT 1", domainname);

	#ifdef DEBUG
	printf("Query [%s]\n", sql_str);
	#endif

	rc = sqlite3_prepare_v2(db, sql_str, 1024, &res, 0);
	if(rc != SQLITE_OK)
	{
		fprintf(stderr, "The package database file is corrupt!");
		sqlite3_free(zErrMsg);
		sqlite3_close(db);
		return 0;
	}
	while(1)
	{
		result=sqlite3_step(res);
		if(result==SQLITE_ROW)
		{
			sprintf(DOMAINRECORD.dr_domain, "%s", sqlite3_column_text(res, 0));
			sprintf(DOMAINRECORD.dr_registered, "%s", sqlite3_column_text(res, 1));
			sprintf(DOMAINRECORD.dr_name, "%s", sqlite3_column_text(res, 2));
			sprintf(DOMAINRECORD.dr_email, "%s", sqlite3_column_text(res, 3));
		} else {
			break;
		}
	}
	sqlite3_finalize(res);
	sqlite3_close(db);
	return 0;
}

int main (int argc, char *argv[])
{
	int sd, newSd, cliLen;

	struct sockaddr_in cliAddr, servAddr;
	char line[MAX_MSG];
	char return_buffer[RB_LENGTH];
	char no_result[10]="NO RESULT";
	char ret_svr[10]="127.0.0.1";

	printf("WHOIS server for The OpenNIC Project. Rev.2 (C) 2012 Martin COLEMAN.\n");
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
		//while(read_line(newSd,line)!=ERROR)
		int rc=0;
		//while(1)
		//{
		if(rc=recv(newSd, line, MAX_MSG, 0))
		{
        		chomp(line);

        		#ifdef VERBOSE
        		printf("query received from %s for %s\n", inet_ntoa(cliAddr.sin_addr), line);
        		#endif

			query_domain(line);

			#ifdef DEBUG
			printf("[%s] [%s]\n", line, DOMAINRECORD.dr_domain);
			#endif

			if(strcmp(line, DOMAINRECORD.dr_domain))
			{
				send(newSd, no_result, 10, 0);
			} else {
				#ifdef DEBUG
					#ifdef VERBOSE
				printf("Domain: %s\nRegistered: %s\nName: %s\nEmail: %s\n\r\n", DOMAINRECORD.dr_domain, DOMAINRECORD.dr_registered, DOMAINRECORD.dr_name, DOMAINRECORD.dr_email);
					#endif
				#endif
				sprintf(return_buffer, "Welcome to the OpenNIC Registry!\nDomain: %s.oz\nRegistered: %s\nContact Name: %s\nContact Email: %s\r\n", DOMAINRECORD.dr_domain, DOMAINRECORD.dr_registered, DOMAINRECORD.dr_name, DOMAINRECORD.dr_email);
				send(newSd, return_buffer, RB_LENGTH, 0);
			}
			/* init line */
			memset(line, 0, MAX_MSG);
			
			DOMAINRECORD.dr_domain[0]='\0';
		} /* while(read_line) */
		close(newSd);
		memset(line, 0, MAX_MSG);
	} /* while (1) */
}
