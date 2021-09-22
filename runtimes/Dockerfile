FROM alpine:3.14
LABEL maintainer="Marc Theriault"

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apk --update add --no-cache openconnect openssh sshpass

COPY entrypoint /usr/local/bin/entrypoint

RUN mkdir /transfer \
  && mkdir /log \
  && mkdir /root/.ssh \
  && chmod +x /usr/local/bin/entrypoint

#VOLUME ["/transfer", "/log"]

RUN echo "root:root" | chpasswd
RUN sed -i s/#PermitRootLogin.*/PermitRootLogin\ yes/ /etc/ssh/sshd_config

EXPOSE 22

ENTRYPOINT ["entrypoint"]
#&& touch /root/.ssh/known_hosts \
