module.exports = function(grunt) {
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    jshint: {
      app: {
        options: {
          shadow: true, //'inner'
          unused: false, //'vars'
          curly: false, //true
          eqeqeq: false, //true

          freeze: true,
          futurehostile: true,
          latedef: true,
          undef: true,
          nonbsp: true,
          sub: true,
          loopfunc: true,
          newcap: false,
          globalstrict: true,
          browser: true,
          devel: true,
          jquery: true,
          predef: ['require', 'define', 'requirejs', 'Notification']
        },
        src: [
          'htdocs/assets/js/**/*.js',
          '!htdocs/assets/js/templates.compiled.js',
          '!htdocs/assets/js/libs/**/*.js'
        ],
      },
    },
    requirejs: {
      js: {
        options: {
          baseUrl: 'htdocs/assets/js',
          removeCombined: true,
          dir: 'htdocs/dist/js',
          wrap: true,
          optimize: 'uglify2',
          paths: {
            jquery: 'libs/jquery',
            uri: 'libs/uri',
            underscore: 'libs/underscore',
            backbone: 'libs/backbone',
            bootstrap: 'libs/bootstrap',
            routefilter: 'libs/backbone.routefilter',
            handlebars: 'libs/handlebars',
            select2: 'libs/select2',
            tablesorter: 'libs/tablesorter',
            moment: 'libs/moment',
            autosize: 'libs/autosize',
            dragula: 'libs/dragula',
            mousetrap: 'libs/mousetrap',
            datetimepicker: 'libs/datetimepicker',
            chartjs: 'libs/chart',
            codemirror: 'libs/codemirror',
            text: 'libs/text',
            false: 'libs/false',

            templates: 'templates.compiled',
            data_json: 'empty:',
          },
          map: {
            uri: {
              punycode: 'libs/false',
              IPv6: 'libs/false',
              SecondLevelDomains: 'libs/false',
            }
          },
          modules: [
            {name: 'main'}
          ],
          stubModules: ['punycode', 'IPv6', 'SecondLevelDomains'],
          preserveLicenseComments: true,
          generateSourceMaps: false,
          useStrict: true
        }
      },
      css: {
        options: {
          keepBuildDir: false,
          optimizeCss: "standard",
          cssIn: "htdocs/assets/css/main.css",
          out: "htdocs/dist/css/main.css"
        }
      }
    },
    handlebars: {
      app: {
        files: {
          "htdocs/assets/js/templates.compiled.js": ["htdocs/assets/templates/**/*.html"]
        },
        options: {
          amd: true,
          processName: function(filePath) {
            return filePath.replace(/^htdocs\/assets\/templates\//, '').replace(/\.html$/, '');
          }
        }
      }
    },
    copy: {
      deps: {
        files: [
          // {nonull: true, src: 'bower_components/bootstrap/dist/css/bootstrap.css', dest: 'htdocs/assets/css/bootstrap.css'},
          {nonull: true, src: 'bower_components/dragula.js/dist/dragula.css', dest: 'htdocs/assets/css/dragula.css'},
          {nonull: true, src: 'bower_components/bootswatch/slate/bootstrap.css', dest: 'htdocs/assets/css/bootstrap.css'},
          {nonull: true, src: 'bower_components/select2/select2.css', dest: 'htdocs/assets/css/select2.css'},
          {nonull: true, src: 'bower_components/select2-bootstrap3-css/select2-bootstrap.css', dest: 'htdocs/assets/css/select2-bootstrap.css'},
          {nonull: true, src: 'bower_components/jquery.tablesorter/css/theme.bootstrap.css', dest: 'htdocs/assets/css/tablesorter-bootstrap.css' },
          {nonull: true, src: 'bower_components/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.css', dest: 'htdocs/assets/css/datetimepicker.css' },
          {nonull: true, src: 'bower_components/codemirror/lib/codemirror.css', dest: 'htdocs/assets/css/codemirror.css'},

          {expand: true, cwd: 'bower_components/bootstrap/dist/fonts/', nonull: true, src: '*', dest: 'htdocs/assets/fonts/' },

          {expand: true, cwd: 'bower_components/select2/', nonull: true, src: '*.{png,gif}', dest: 'htdocs/assets/imgs/' },

          {nonull: true, src: 'bower_components/dragula.js/dist/dragula.js', dest: 'htdocs/assets/js/libs/dragula.js'},
          {nonull: true, src: 'bower_components/autosize/dist/autosize.js', dest: 'htdocs/assets/js/libs/autosize.js' },
          {nonull: true, src: 'bower_components/bootstrap/dist/js/bootstrap.js', dest: 'htdocs/assets/js/libs/bootstrap.js'},
          {expand: true, cwd: 'bower_components/handlebars/', nonull: true, src: '{handlebars,handlebars.runtime}.js', dest: 'htdocs/assets/js/libs/'},
          {nonull: true, src: 'bower_components/codemirror/lib/codemirror.js', dest: 'htdocs/assets/js/libs/codemirror.js'},
          {nonull: true, src: 'bower_components/Chart.js/dist/Chart.js', dest: 'htdocs/assets/js/libs/chart.js'},
          {nonull: true, src: 'bower_components/jquery/dist/jquery.js', dest: 'htdocs/assets/js/libs/jquery.js'},
          {nonull: true, src: 'bower_components/eonasdan-bootstrap-datetimepicker/src/js/bootstrap-datetimepicker.js', dest: 'htdocs/assets/js/libs/datetimepicker.js'},
          {nonull: true, src: 'bower_components/select2/select2.js', dest: 'htdocs/assets/js/libs/select2.js'},
          {nonull: true, src: 'bower_components/jquery.tablesorter/dist/js/jquery.tablesorter.combined.js', dest: 'htdocs/assets/js/libs/tablesorter.js'},
          {nonull: true, src: 'bower_components/requirejs/require.js', dest: 'htdocs/assets/js/libs/require.js'},
          {nonull: true, src: 'bower_components/underscore/underscore.js', dest: 'htdocs/assets/js/libs/underscore.js'},
          {nonull: true, src: 'bower_components/requirejs-text/text.js', dest: 'htdocs/assets/js/libs/text.js'},
          {nonull: true, src: 'bower_components/mousetrap/mousetrap.js', dest: 'htdocs/assets/js/libs/mousetrap.js'},
          {nonull: true, src: 'bower_components/uri.js/src/URI.js', dest: 'htdocs/assets/js/libs/uri.js'},
          {nonull: true, src: 'bower_components/backbone/backbone.js', dest: 'htdocs/assets/js/libs/backbone.js'},
          {nonull: true, src: 'bower_components/routefilter/dist/backbone.routefilter.js', dest: 'htdocs/assets/js/libs/backbone.routefilter.js'},
          {nonull: true, src: 'bower_components/moment/moment.js', dest: 'htdocs/assets/js/libs/moment.js'},
          {nonull: true, src: 'bower_components/false/false.js', dest: 'htdocs/assets/js/libs/false.js'},
        ],
      },
      app: {
        files: [
          {expand: true, cwd: 'htdocs/assets', src: ['imgs/**', 'fonts/*'], dest: 'htdocs/dist', filter: 'isFile'},
          {expand: true, cwd: 'htdocs', src: ['index-src.html'], dest: 'htdocs'},
        ]
      },
      index: {
        files: [
          {src: 'htdocs/index-src.html', dest: 'htdocs/index.html'}
        ]
      },
    },
    sed: {
      app: {
        path: 'htdocs/dist/js/main.js',
        pattern: '/assets/',
        replacement: '/dist/',
      },
      select2: {
        path: 'htdocs/assets/css/select2.css',
        pattern: /url\('/g,
        replacement: "url('../imgs/",
      },
      dev: {
        path: 'htdocs/index.html',
        pattern: 'ASSET_DIR',
        replacement: 'assets',
      },
      prod: {
        path: 'htdocs/index.html',
        pattern: 'ASSET_DIR',
        replacement: 'dist',
      }
    },
    run: {
      docs: {
        cmd: 'vendor/bin/phpdoc',
        args: ['run', '-d', 'phplib', '-t', 'phpdoc']
      },
      tests: {
        cmd: 'vendor/bin/phpunit',
        args: ['-c', 'tests/phpunit.xml'],
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-contrib-handlebars');
  grunt.loadNpmTasks('grunt-contrib-requirejs');
  grunt.loadNpmTasks('grunt-run');
  grunt.loadNpmTasks('grunt-sed');

  grunt.registerTask('deps', ['copy:deps']);
  grunt.registerTask('default', ['prod']);

  // Public
  grunt.registerTask('dev', ['deps', 'sed:select2', 'copy:index', 'sed:dev']);
  grunt.registerTask('prod', ['deps', 'jshint:app', 'handlebars:app', 'copy:app', 'sed:select2', 'requirejs:js', 'requirejs:css', 'sed:app', 'copy:index', 'sed:prod']);
  grunt.registerTask('tests', ['run:tests']);
  grunt.registerTask('docs', ['run:docs']);
};
