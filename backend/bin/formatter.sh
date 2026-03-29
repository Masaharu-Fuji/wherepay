#!/usr/bin/env bash

./vendor/bin/pint && \
composer run twig:format && \
composer run lint:lines