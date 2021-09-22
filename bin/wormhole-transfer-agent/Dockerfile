FROM alpine:3.13

RUN apk --update add --no-cache \
  inotify-tools=3.20.11.0-r0 \
  openconnect=8.10-r0 \
  openssh-client=8.4_p1-r3 \
  openvpn=2.5.1-r0 \
  sshpass=1.06-r0 \
  supervisor=4.2.1-r0

RUN mkdir /transfer_queue \
  && mkdir /log \
  && touch /log/supervisord.log \
  && mkdir /root/.ssh \
  && touch /root/.ssh/known_hosts

COPY ./build/supervisord.conf /etc/supervisord.conf
COPY ./build/openconnect.sh /openconnect.sh
COPY ./build/inotifywait.sh /inotifywait.sh
COPY ./build/open-shell.sh /open-shell.sh

RUN chmod +x /openconnect.sh \
  && chmod +x /inotifywait.sh \
  && chmod +x /open-shell.sh

VOLUME ["/transfer_queue", "/log"]

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
