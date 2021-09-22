#!/bin/ash

#echo $VPN_PASSWORD | /usr/bin/openconnect -u $VPN_USERNAME $VPN_SERVER --no-dtls -v  --passwd-on-stdin
echo $VPN_PASSWORD | /usr/bin/openconnect -u $VPN_USERNAME $VPN_SERVER --passwd-on-stdin
