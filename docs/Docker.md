Docker
======

How to use this image
---------------------

Make sure you're using the right branch! (This branch is for ES 2.0)

To set up just 411:
```
$ docker run -p 8080:80 kaiz/411
```
This assumes you already have an elasticsearch cluster set up with the hostname `es`.


To set up 411 and Elasticsearch (requires `docker-compose`):
```
$ docker-compose up
```


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
