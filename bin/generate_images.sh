#!/bin/bash

docker build -t kaiz/411:latest . --build-arg COMPOSER=composer-es2x.json
docker build -t kaiz/411:es2x . --build-arg COMPOSER=composer-es2x.json
docker build -t kaiz/411:es5x . --build-arg COMPOSER=composer-es5x.json
docker build -t kaiz/411:es6x . --build-arg COMPOSER=composer-es6x.json
docker push kaiz/411:latest
docker push kaiz/411:es2x
docker push kaiz/411:es5x
docker push kaiz/411:es6x
