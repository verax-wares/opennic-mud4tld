#!/bin/sh
# OpenNIC MUD4TLD operations script.
# By Martin COLEMAN (C) 2012-2014.
# To install change the following to appropriate settings for your TLD and configuration,
# especially the OpenNIC suite, and run. A cron job could be handy too.

# change these to suit. they should be self-explanatory.
TLD_DB=/home/martin/public_html/opennic.oz/oz_tld.sq3
ZONE_DIR=/var/cache/bind/opennic/master/
OPENNIC_SUITE=/home/martin/opennic_tmp/
PRIMARY_IP=103.4.16.80
SECONDARY_IP=103.4.16.80
MY_EMAIL=hostmaster.opennic.oz
MY_HOST=ns10.opennic.glue
MY_TLD=oz
BIND_USR=bind
BIND_GRP=bind
# end of modifications

# detect if flag file exists indicating a change
if [ ! -f /tmp/inittld.flag ]; then
exit 0
fi

# get to the directory
cd $OPENNIC_SUITE

echo -n "Copying TLD database..."
cp $TLD_DB $OPENNIC_SUITE
echo "Done"

echo -n "Generating TLD zone file..."
cd $OPENNIC_SUITE
./init_tld $MY_TLD $MY_HOST $MY_EMAIL $PRIMARY_IP $SECONDARY_IP > opennic.$MY_TLD
echo "Done"

echo -n "Installing new zone file..."
cp opennic.$MY_TLD $ZONE_DIR
echo "Done"

chgrp $BIND_GRP $ZONE_DIR/opennic.$MY_TLD
chmod g+rw $ZONE_DIR/opennic.$MY_TLD

echo -n "Restarting BIND..."
/etc/init.d/bind9 reload
echo "Done"

# remove flag file
rm /tmp/inittld.flag

exit 0
