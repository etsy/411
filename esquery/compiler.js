#!/usr/bin/node
var pegjs = require("pegjs");
var phppegjs = require("php-pegjs");
var fs = require('fs')

fs.readFile('grammar.pegjs', 'utf8', function (err,data) {
  if (err) {
    return console.log(err);
  }

  var parser = pegjs.buildParser(data, {
    phppegjs: {parserNamespace: 'ESQuery'},
    plugins: [phppegjs]
  });

  console.log(parser);
});
