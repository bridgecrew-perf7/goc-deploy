#!/bin/ash

INGEST_QURANTINE_PATH="~/ingest_quarantine"

while RECEIVED_FILENAME=$(inotifywait -e create $TRANSFER_QUEUE --format %f .); do
  SOURCE=$TRANSFER_QUEUE/$RECEIVED_FILENAME
  TARGET=$INGEST_QURANTINE_PATH/$RECEIVED_FILENAME

  echo "$(date): Discovered $SOURCE."
  echo "Copying $SOURCE to $SSH_SERVER:$TARGET..."

  sshpass -p "$SSH_PASSWORD" ssh \
    -o StrictHostKeyChecking=no \
    -o UserKnownHostsFile=$SSH_KNOWN_HOSTS \
    $SSH_USERNAME@$SSH_SERVER \
    "mkdir -p $INGEST_QURANTINE_PATH;"

  sshpass -p "$SSH_PASSWORD" ssh \
    -o StrictHostKeyChecking=no \
    -o UserKnownHostsFile=$SSH_KNOWN_HOSTS \
    $SSH_USERNAME@$SSH_SERVER \
    "chmod 774 $INGEST_QURANTINE_PATH"

  sshpass -p "$SSH_PASSWORD" scp \
    -o StrictHostKeyChecking=no \
    -o UserKnownHostsFile=$SSH_KNOWN_HOSTS \
    $SOURCE \
    $SSH_USERNAME@$SSH_SERVER:$TARGET

  sshpass -p "$SSH_PASSWORD" ssh \
    -o StrictHostKeyChecking=no \
    -o UserKnownHostsFile=$SSH_KNOWN_HOSTS \
    $SSH_USERNAME@$SSH_SERVER \
    "chmod 664 $INGEST_QURANTINE_PATH/$RECEIVED_FILENAME"

  echo "chmod 664 $INGEST_QURANTINE_PATH/$RECEIVED_FILENAME"
  echo "Done."
  echo
done

#User's Home Director:
#sshpass -p "$SSH_PASSWORD" ssh -t -o StrictHostKeyChecking=no -o UserKnownHostsFile=$SSH_KNOWN_HOSTS $SSH_USERNAME@$SSH_SERVER "grep $SSH_USERNAME /etc/passwd | cut -d ":" -f6"