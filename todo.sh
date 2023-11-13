#!/bin/bash

ENV=.todo.env php -d variables_order=EGPCS -S 127.0.0.1:7070 -t public
