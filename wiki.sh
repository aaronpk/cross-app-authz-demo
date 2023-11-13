#!/bin/bash

ENV=.wiki.env php -d variables_order=EGPCS -S 127.0.0.1:8080 -t public
