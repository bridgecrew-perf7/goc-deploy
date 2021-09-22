#!/bin/ash

sshpass -p "$SSH_PASSWORD" ssh -t $SSH_USERNAME@$SSH_SERVER -o StrictHostKeyChecking=no -o UserKnownHostsFile=$SSH_KNOWN_HOSTS
