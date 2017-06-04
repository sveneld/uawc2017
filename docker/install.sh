#!/usr/bin/env bash

docker-compose down && docker-compose -p uawc up -d --build && docker exec -it uawc_webserver-phpfpm_1 composer install