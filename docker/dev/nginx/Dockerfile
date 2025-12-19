# the different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/compose/compose-file/#target

ARG NGINX_VERSION=1.15

FROM nginx:${NGINX_VERSION}-alpine AS nginx

COPY conf.d/default.conf /etc/nginx/conf.d/default.conf

WORKDIR /app

