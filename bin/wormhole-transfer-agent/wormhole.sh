#!/bin/bash
#-----------------------------------------------------------------------------------------------------------------------
# Script : wormhole.sh
# Usage : $0 <config_file>
# Date : 2021-04-17
# Maintainer: Marc Theriault <marc.theriault5@canada.ca>
#-----------------------------------------------------------------------------------------------------------------------

IMAGE_NAME="mtweb/wormhole-transfer-agent:v1.0.1"

VOLUME_MOUNT_LOG=$(pwd)/log
VOLUME_MOUNT_TRANSFER_QUEUE=$(pwd)/transfer_queue

SSH_KNOWN_HOSTS="/root/.ssh/known_hosts"

#-----------------------------------------------------------------------------------------------------------------------
# Ensure docker is installed.
#-----------------------------------------------------------------------------------------------------------------------
function check_dependencies() {
  COMMAND=$1

  if [ $(which docker) ]; then
    echo "Using $(docker --version)" && echo
    return 0
  else
    echo "Dependency not met: Docker is required but is not installed."
    exit
  fi
}

#-----------------------------------------------------------------------------------------------------------------------
#
#-----------------------------------------------------------------------------------------------------------------------
function validate_config_file_option() {
  COMMAND=$1
  CONFIG_FILE=$2

  if [ -z $CONFIG_FILE ]; then
    echo "Usage: ${0##*/} $COMMAND <config_file>"
    exit 1
  elif [ ! -f $CONFIG_FILE ]; then
    echo "Usage: ${0##*/} $COMMAND <config_file>"
    echo "The config_file '$CONFIG_FILE' does not exist or is not a regular file."
    exit 1
  elif [ ! ${CONFIG_FILE: -5} == ".conf" ]; then
    echo "Usage: ${0##*/} $COMMAND <config_file>"
    echo "The config_file '$CONFIG_FILE' must have a '.conf' extension."
    exit 1
  fi
}

#-----------------------------------------------------------------------------------------------------------------------
#
#-----------------------------------------------------------------------------------------------------------------------
function validate_is_container_running() {
  COMMAND=$1
  CONTAINER_NAME=$2

  if [ -z $CONTAINER_NAME ]; then
    echo "Usage: ${0##*/} $COMMAND <container_name>"
    exit 1
  elif [ ! $(docker ps -q --filter ancestor=$IMAGE_NAME --filter name=$CONTAINER_NAME) ]; then
    echo "There is no running container built from image '$IMAGE_NAME' with the name '$CONTAINER_NAME'."
    echo "Hint: issue '${0##*/} status' and make sure the container with name '$CONTAINER_NAME' is running"
    echo
    exit 1
  fi
}

#-----------------------------------------------------------------------------------------------------------------------
# @todo docker inspect $(docker ps -aq) --format '{{.Config.Name}}'
#-----------------------------------------------------------------------------------------------------------------------
function check_wormholes() {
  COMMAND=$1

  if [ $(docker ps -q --filter ancestor=$IMAGE_NAME --filter name=$CONTAINER_NAME) ]; then
    echo "Listing running containers built using docker image '$IMAGE_NAME'..."
    echo

    docker ps -s --filter name=$CONTAINER_NAME
  else
    echo "There is no running containers built using docker image '$IMAGE_NAME'."
    echo
  fi
}

#-----------------------------------------------------------------------------------------------------------------------
# Builds and runs a Wormhole Transfer Agent docker container using the supplied configuration file.
#-----------------------------------------------------------------------------------------------------------------------
function open_wormhole() {
  COMMAND=$1
  CONFIG_FILE=$2
  CONTAINER_NAME="$(basename -- $CONFIG_FILE .conf)-wormhole"
  TRANSFER_QUEUE="/transfer_queue"

  if [ $(docker ps -q --filter name=$CONTAINER_NAME) ]; then
    echo "There is already a running container with the name '$CONTAINER_NAME'."
    echo
    exit 1
  fi

  # Build the docker image
  docker build -t $IMAGE_NAME .

  # Ensure local volume mounts exist
  mkdir -p \
    $VOLUME_MOUNT_LOG/$CONTAINER_NAME \
    $VOLUME_MOUNT_TRANSFER_QUEUE/$CONTAINER_NAME

  # Run container
  docker run --rm -d \
      --privileged \
      --cap-add NET_ADMIN \
      --device /dev/net/tun \
      --volume $VOLUME_MOUNT_LOG/$CONTAINER_NAME:/log \
      --volume $VOLUME_MOUNT_TRANSFER_QUEUE/$CONTAINER_NAME:$TRANSFER_QUEUE \
      --env TRANSFER_QUEUE=$TRANSFER_QUEUE \
      --env SSH_KNOWN_HOSTS=$SSH_KNOWN_HOSTS \
      --env-file $CONFIG_FILE \
      --name $CONTAINER_NAME \
      $IMAGE_NAME
}

#-----------------------------------------------------------------------------------------------------------------------
# Opens a command interpreter (shell) in the docker container with the specified container name.
#-----------------------------------------------------------------------------------------------------------------------
function enter_wormhole() {
  COMMAND=$1
  CONTAINER_NAME=$2

  echo "Opening a command interpreter (shell) to SSH Server though docker container '$CONTAINER_NAME'..."
  echo "Hint: issue the 'exit' command to quit."
  echo

  docker exec -it $CONTAINER_NAME /open-shell.sh
}

#-----------------------------------------------------------------------------------------------------------------------
# Opens a command interpreter (shell) in the docker container with the specified container name.
#-----------------------------------------------------------------------------------------------------------------------
function explore_wormhole() {
  COMMAND=$1
  CONTAINER_NAME=$2

  echo "Opening command interpreter (shell) for docker container named '$CONTAINER_NAME'..."
  echo "Hint: issue the 'exit' command to quit."
  echo

  docker exec -it $CONTAINER_NAME /bin/sh
}

#-----------------------------------------------------------------------------------------------------------------------
# Stops and removes docker containers using the docker image defined in $IMAGE_NAME and the container name defined in
# $CONTAINER_NAME, if necessary.
#-----------------------------------------------------------------------------------------------------------------------
function close_wormhole() {
  COMMAND=$1
  CONTAINER_NAME=$2

  if [ -z $CONTAINER_NAME ]; then
    echo "Usage: ${0##*/} $COMMAND <container_name>"
    exit 1
  elif [ ! $(docker ps -q --filter ancestor=$IMAGE_NAME --filter name=$CONTAINER_NAME) ]; then
    echo "There is no running container built from image '$IMAGE_NAME' with the name '$CONTAINER_NAME'."
    echo "Hint: issue '${0##*/} status' and make sure the container with name '$CONTAINER_NAME' is running"
    echo
    exit 1
  fi

  # Stop running the running container, if necessary
  if [ $(docker ps -q --filter ancestor=$IMAGE_NAME --filter name=$CONTAINER_NAME) ]; then
    echo "Stopping container named '$CONTAINER_NAME' with id..."
    docker stop $(docker ps -a -q --filter ancestor=$IMAGE_NAME --filter name=$CONTAINER_NAME)
    echo
  else
    echo "There is no running container built from image '$IMAGE_NAME' with the name '$CONTAINER_NAME'."
    echo
  fi

  # Remove running the running container, if necessary
  if [ $(docker ps -a -q --filter ancestor=$IMAGE_NAME --filter name=$CONTAINER_NAME) ]; then
    echo "Removing container '$CONTAINER_NAME' with id..."
    docker rm $(docker ps -a -q --filter ancestor=$IMAGE_NAME --filter name=$CONTAINER_NAME)
    echo
  fi

  while true; do
    read -p "Do you wish to remove $VOLUME_MOUNT_TRANSFER_QUEUE/$CONTAINER_NAME? (Y/N) " ANSWER

    case ${ANSWER:0:1} in
      y|Y)
        rm -fr $VOLUME_MOUNT_TRANSFER_QUEUE/$CONTAINER_NAME
        break
        ;;
      n|Y)
        break;
        ;;
      *)
        echo "Please answer Yes or No."
        ;;
    esac
  done

  while true; do
    read -p "Do you wish to remove $VOLUME_MOUNT_LOG/$CONTAINER_NAME? (Y/N) " ANSWER

    case ${ANSWER:0:1} in
      y|Y)
        rm -fr $VOLUME_MOUNT_LOG/$CONTAINER_NAME
        break
        ;;
      n|Y)
        break;
        ;;
      *)
        echo "Please answer Yes or No."
        ;;
    esac
  done
}

#-----------------------------------------------------------------------------------------------------------------------
# Main option switch.
#-----------------------------------------------------------------------------------------------------------------------
case "$1" in
  open)
    check_dependencies $1
    validate_config_file_option $1 $2
    open_wormhole $1 $2
    ;;
  close)
    check_dependencies $1
    close_wormhole $1 $2
    ;;
  enter)
    check_dependencies $1
    validate_is_container_running $1 $2
    enter_wormhole $1 $2
    ;;
  explore)
    check_dependencies $1
    validate_is_container_running $1 $2
    explore_wormhole $1 $2
    ;;
  status)
    check_dependencies $1
    check_wormholes $1
    ;;
  *)
    echo "Usage: ${0##*/} {open|close|enter|explore|status}"
    exit 1;
    ;;
esac
