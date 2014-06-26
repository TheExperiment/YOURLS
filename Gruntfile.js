/*
 * YOURLS Gruntfile
 * http://yourls.org
 * Licensed under MIT
 */

module.exports = function (grunt) {
  'use strict';

  // Tools
  require("time-grunt")(grunt);
  var path = require('path');
  var semver = require('semver');
  var currentVersion = '2.0.0-alpha';

  // Configuration & Options
  // -----------------------

  grunt.initConfig({

    // Variables
    pkg: grunt.file.readJSON('composer.json'),
    pac: {
      version: currentVersion
    },

    // PHP tasks
    server: {
      php: {
        options: {
          keepalive: true,
          open: true
        }
      },
      watch: {}
    },
    phpcsfixer: {
      src: {
        dir: 'includes/YOURLS'
      },
      tests: {
        dir: 'tests'
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
        package_version: '<%= pac.version %>',
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
            ' * YOURLS v<%= pac.version %>\n' +
            ' * <%= pkg.homepage %>\n' +
            ' * Copyright 2009-<%= grunt.template.today("yyyy") %> <%= pkg.authors[0].name %>\n' +
            ' * Licensed under <%= pkg.license %>\n' +
            ' */',
    bower: {
      install: {
        options: {
          targetDir: './assets',
          layout: function (type, component) {
            if (type === 'less') {
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
    jshint: {
      options: {
        curly: true,
        eqeqeq: true,
        immed: true,
        latedef: true,
        newcap: true,
        noarg: true,
        undef: true,
        strict: true,
        trailing: true,
        reporter: require("jshint-stylish")
      },
      yourls: {
        options: {
          devel: true, //!
          strict: false, //!
          latedef: false, //!
          browser: true,
          jquery: true,
          globals: {
            ZeroClipboard: true
          }
        },
        src: [
          "assets/js/yourls.js"
        ]
      },
      grunt: {
        options: {
          node: true
        },
        src: [
          "Gruntfile.js"
        ]
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
          to: 'const VERSION = \'<%= pac.version %>\';'
        }]
      },
      gruntfile: {
        src: 'Gruntfile.js',
        overwrite: true,
        replacements: [{
          from: /var currentVersion = \'[0-9a-z\.-]+\';/,
          to: 'var currentVersion = \'<%= pac.version %>\';'
        }]
      },
      composer: {
        src: 'composer.json',
        overwrite: true,
        replacements: [{
          from: /"dev-master": "[0-9\.]+x-dev"/,
          to: '"dev-master": "<%= pac.version.substr(0,3) %>.x-dev"'
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
          to: ' * @version <%= pac.version %>\n' +
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
      options: {
        livereload: true
      },
      less: {
        files: 'assets/less/**/*.less',
        tasks: [
          'less:dev',
          'jshint'
        ]
      },
      php: {
        files: '**/*.php',
        tasks: [
          'phpcsfixer:src',
          'phpcsfixer:tests',
          'test'
        ]
      }
    },

    // Update Dependencies tasks
    devUpdate: {
      yourls: {
        options: {
          updateType: 'force'
        }
      }
    },
    "update_submodules": {
      yourls: {}
    },
    composer: {
      options: {
        flags: [
          "no-dev",
          "optimize-autoloader"
        ]
      },
      yourls: {},
      geoip: {
        options: {
          cwd: 'user/plugins/geoip/'
        }
      }
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
    },

    prompt: {
      bump: {
        options: {
          questions: [
            {
              config: 'bump.increment',
              type: 'list',
              message: 'Bump version from ' + '<%= pac.version %>'.cyan + ' to:',
              choices: [
                {
                  value: 'git',
                  name: 'Build:\t'.yellow + (currentVersion + '-#').yellow +
                    '\tUnstable, betas, and release candidates.'
                },
                {
                  value: 'patch',
                  name: 'Patch:\t'.yellow + semver.inc(currentVersion, 'patch').yellow +
                    '\tBackwards-compatible bug fixes.'
                },
                {
                  value: 'minor',
                  name: 'Minor:\t'.yellow + semver.inc(currentVersion, 'minor').yellow +
                    '\tAdd functionality in a backwards-compatible manner.'
                },
                {
                  value: 'major',
                  name: 'Major:\t'.yellow + semver.inc(currentVersion, 'major').yellow +
                    '\tIncompatible API changes.'
                },
                {
                  value: 'custom',
                  name: 'Custom:\t?.?.?'.yellow +
                    '\tSpecify version...'
                }
              ]
            },
            {
              config: 'bump.version',
              type: 'input',
              message: 'What specific version would you like',
              when: function (answers) {
                return answers['bump.increment'] === 'custom';
              },
              validate: function (value) {
                var valid = semver.valid(value) && true;
                return valid || 'Must be a valid semver, such as 1.2.3-rc1. See ' +
                  'http://semver.org/'.blue.underline + ' for more details.';
              }
            },
            {
              config: 'bump.tag',
              type: 'confirm',
              message: 'Do we create a new tag for this release?',
              default: true
            },
            {
              config: 'bump.push',
              type: 'confirm',
              message: 'Do you want to push the new release?',
              default: true,
              when: function (answers) {
                return answers['bump.tag'];
              }
            }
          ]
        }
      }
    },
    bump: {
      options: {
        files: [],
        updateConfigs: ['pac'],
        type: grunt.config('bump.increment'),
        version: grunt.config('bump.version'),
        commitFiles: ['-a'],
        createTag: grunt.config('bump.tag'),
        push: grunt.config('bump.push'),
      }
    },
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
  // -> Developpement environnement
  grunt.registerTask('default', [
    'server:watch',
    'watch'
  ]);
  // PHP task
  // -> Checks and fixes for PHP
  // -> Make it distributable
  // -> Generate translation file
  grunt.registerTask('dist-php', [
    'replace:version',
    'replace:requirements',
    'replace:tests',
    'replace:banner',
    'phpcsfixer'
  ]);
  // Assets task
  // -> Compile JS/HTML
  // -> Make it distributable
  grunt.registerTask('dist-assets', [
    'jshint',
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
    'composer:geoip:update',
    'curl',
    'gzip'
  ]);
  // Up-Deps task
  // -> Update PHP dependencies
  // -> Update Assets dependencies
  // -> Update Git submodules
  grunt.registerTask('up-deps', [
    'composer:yourls:update',
    'devUpdate',
    'bower',
    'replace:bootstrap',
    'update_submodules',
    'geoip'
  ]);
  // Release task
  // -> Update dependencies
  // -> Run distrib task
  // -> Bump version
  grunt.registerTask('release', [
    'prompt:bump',
    'up-deps',
    'test',
    'bump-only:' + grunt.config('bump.increment'),
    'dist',
    'replace:gruntfile',
    'bump-commit',
    'pot'
  ]);
};
