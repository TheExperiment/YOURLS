/*
 * YOURLS Gruntfile
 * http://yourls.org
 * Licensed under MIT
 */

module.exports = function (grunt) {
  'use strict';

  // Tools
  var path = require('path');

  // Configuration & Options
  // -----------------------

  grunt.initConfig({

    // Variables
    rel: {
      version: '2.0-alpha'
    },
    pkg: grunt.file.readJSON('composer.json'),

    // PHP tasks
    server: {
      php: {
        options: {
          keepalive: true,
          open: true
        }
      }
    },
    phpcsfixer: {
      src: {
        dir: '*/YOURLS'
      },
      admin: {
        dir: 'includes/admin'
      },
      options: {
        fixers: [
          'indentation',
          'linefeed',
          'trailing_spaces',
          'unused_use',
          'short_tag',
          'return',
          'visibility',
          'php_closing_tag',
          'extra_empty_lines',
          'include',
          'psr0',
          'elseif',
          'eof_ending'
        ]
      }
    },
    test: {
      yourls: {}
    },

    // POT generation task
    pot: {
      options: {
        text_domain: '<%= pkg.authors[0].name %>',
        dest: 'user/languages/YOURLS.pot/',
        package_version: '<%= rel.version %>',
        encoding: 'UTF-8',
        keywords: [
          '__',
          '_e',
          '_s',
          '_se',
          '_esc_attr__',
          '_esc_html__',
          '_x',
          '_ex',
          '_esc_attr_x',
          '_esc_html_x',
          '_n:1,2',
          '_nx:1,2',
          '_n_noop:1,2',
          '_nx_noop:1,2'
        ]
      },
      files: {
        src: ['includes/YOURLS/**/*.php'],
        expand: true
      },
    },

    // LESS and AJAX tasks
    banner: '/*!\n' +
            ' * YOURLS v<%= rel.version %>\n' +
            ' * <%= pkg.homepage %>\n' +
            ' * Copyright 2009-<%= grunt.template.today("yyyy") %> <%= pkg.authors[0].name %>\n' +
            ' * Licensed under <%= pkg.license %>\n' +
            ' */',
    bower: {
      install: {
        options: {
          targetDir: './assets',
          layout: function (type, component) {
            if (type == 'less') {
              return path.join(type, component);
            }
            return type;
          }
        }
      }
    },
    uglify: {
      options: {
        report: 'min'
      },
      javascript: {
        src: 'assets/js/yourls.js',
        dest: 'assets/js/yourls.min.js',
        options: {
          banner: '<%= banner %>'
        }
      }
    },
    less: {
      dev: {
        src: "assets/less/yourls.less",
        dest: "assets/css/yourls.css",
        options: {
          sourceMap: true,
          sourceMapFilename: 'assets/css/yourls.css.map',
          sourceMapURL: 'yourls.css.map',
          sourceMapRootpath: '../../'
        }
      },
      dist: {
        src: "assets/less/yourls.less",
        dest: "assets/css/yourls.min.css",
        options: {
          cleancss: true,
          report: 'min',
          strictUnits: true,
          strictMath: true,
          strictImports: true
        }
      }
    },

    // Banners Tasks
    usebanner: {
      dist: {
        src: 'assets/css/yourls*',
        options: {
          position: 'top',
          linebreak: false,
          banner: '<%= banner %>',
        },
      }
    },
    replace: {
      version: {
        src: 'includes/YOURLS/Loader.php',
        overwrite: true,
        replacements: [{
          from: /const VERSION = \'[0-9a-z\.-]+\';/,
          to: 'const VERSION = \'<%= rel.version %>\';'
        }]
      },
      composer: {
        src: 'composer.json',
        overwrite: true,
        replacements: [{
          from: /"dev-master": "[0-9\.]+x-dev"/,
          to: '"dev-master": "<%= rel.version.substr(0,3) %>.x-dev"'
        }]
      },
      requirements: {
        src: 'includes/YOURLS/Administration/Installer/Requirements.php',
        overwrite: true,
        replacements: [{
          from: /const PHP = \'[0-9a-z\.-]+\';/,
          to: 'const PHP = \'<%= pkg.require.php.split(">=").pop() %>\';'
        }]
      },
      banner: {
        src: 'includes/+(YOURLS|admin)/**/*.php',
        overwrite: true,
        replacements: [{
          from: / \* @version [0-9a-z\.-]+[\n\r]+ \* @copyright 2009-[0-9]+ [a-zA-Z]+[\n\r]+ \* @license [a-zA-Z\s]+[\n\r]+ \*\//,
          to: ' * @version <%= rel.version %>\n' +
              ' * @copyright 2009-<%= grunt.template.today("yyyy") %> <%= pkg.authors[0].name %>\n' +
              ' * @license <%= pkg.license %>\n' +
              ' */'
        }]
      },
      tests: {
        src: 'tests/**/*.php',
        overwrite: true,
        replacements: [{
          from: / \* @copyright 2009-[0-9]+ [a-zA-Z]+[\n\r]+ \* @license [a-zA-Z\s]+[\n\r]+ \*\//,
          to: ' * @copyright 2009-<%= grunt.template.today("yyyy") %> <%= pkg.authors[0].name %>\n' +
              ' * @license <%= pkg.license %>\n' +
              ' */'
        }]
      },
      bootstrap: {
        src: 'assets/less/bootstrap/bootstrap.less',
        overwrite: true,
        replacements: [{
          from: '@import "glyphicons.less";',
          to: '//@import "glyphicons.less";'
        }]
      }
    },

    // Development tasks
    watch: {
      less: {
        files: 'assets/less/**/*.less',
        tasks: 'less:dev'
      },
      php: {
        files: '*/YOURLS/**/*.php',
        tasks: [
          'phpcsfixer:src',
          'test'
        ]
      }
    },

    // Update submodules
    "update_submodules": {
      yourls: {}
    },

    // GeoIP tasks
    curl: {
      geoip: {
        src: 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz',
        dest: 'user/plugins/geoip/database/temp/GeoLite2-Country.mmdb.gz'
      }
    },
    gzip: {
      geoip: {
        src: 'user/plugins/geoip/database/temp/GeoLite2-Country.mmdb.gz',
        dest: 'user/plugins/geoip/database/GeoLite2-Country.mmdb'
      }
    }
  });

  // Register Tasks Engines
  // ----------------------

  // Create the Gzip task engine
  grunt.registerMultiTask('gzip', function () {
    var zlib = require('zlib'),
        done = this.async();

    this.files.forEach(function (file) {
      grunt.verbose.writeln("Uncompressing " + file.dest.cyan + "...");
      var content = grunt.file.read(file.src, { encoding: null });

      zlib.gunzip(content, function (error, result) {
        done();
        if (error) {
          grunt.fatal(error);
        }
        grunt.file.write(file.dest, result);
        grunt.log.ok("Uncompressed file written to " + file.dest.cyan);
      });
    });
  });

  // Load modules required
  require('load-grunt-tasks')(grunt);

  // Rename server task for understandability
  grunt.renameTask('php', 'server');
  grunt.renameTask('phpunit', 'test');

  // Development Tasks
  // -----------------

  // Default task
  // -> Build and compile
  grunt.registerTask('default', [
    'up-deps',
    'dist',
    'test',
    'pot'
  ]);
  // PHP task
  // -> Checks and fixes for PHP
  // -> Make it distributable
  // -> Generate translation file
  grunt.registerTask('dist-php', [
    'replace:version',
    'replace:requirements',
    'replace:test',
    'replace:banner',
    'phpcsfixer'
  ]);
  // Assets task
  // -> Compile JS/HTML
  // -> Make it distributable
  grunt.registerTask('dist-assets', [
    'uglify',
    'less:dist',
    'usebanner'
  ]);
  // Dist task
  // -> Global distribution
  // -> PHP & Assets
  grunt.registerTask('dist', [
    'dist-assets',
    'dist-php',
    'replace:composer'
  ]);
  // GeoIP task
  // -> Update dependencies
  // -> Update database
  grunt.registerTask('geoip', [
    'composer:update:no-dev:optimize-autoloader:working-dir=user/plugins/geoip/',
    'curl',
    'gzip'
  ]);
  // Up-Deps task
  // -> Update PHP dependencies
  // -> Update Assets dependencies
  // -> Update Git submodules
  grunt.registerTask('up-deps', [
    'composer:update:no-dev:optimize-autoloader',
    'bower',
    'replace:bootstrap',
    'update_submodules',
    'geoip'
  ]);
};
