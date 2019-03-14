Docker
======

How to use this image
---------------------

To set up 411:
```
$ docker run -p 8080:80 kaiz/411:TAG
```
Where `TAG` depends on your version of Elasticsearch:
- ES 2.x: `kaiz/411:latest`
- ES 5.x: `kaiz/411:es5x`
- ES 6.x: `kaiz/411:es6x`

This assumes you already have an elasticsearch cluster set up with the hostname `es`.


To set up 411 and Elasticsearch (requires `docker-compose`):
```
$ docker-compose up -f FILE
```
Where `FILE` depends on your version of Elasticsearch:
- ES 2.x: `docker-compose-es2x.yml`
- ES 5.x: `docker-compose-es5x.yml`
- ES 6.x: `docker-compose-es6x.yml`

Where is data stored?
---------------------

All data is stored in `/data`, which is declared as a volume.

To customize the config, you'll need to copy the `data` directory:
```
$ DOCKER_IMAGE=$(docker create kaiz/411)
$ docker cp $DOCKER_IMAGE:/data data
$ docker rm -v $DOCKER_IMAGE
```

Make appropriate edits to the files in `data` and make sure to mount the directory:
```
$ docker run -p 8080:80 -v $(pwd)/data:/data kaiz/411
```
