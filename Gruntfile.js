/*
 * YOURLS Gruntfile
 * http://yourls.org
 */

module.exports = function (grunt) {
    'use strict';

    // Tools
    var path = require('path');

    // Configuration & Options
    // -----------------------

    grunt.initConfig({

        // Variables
        version: '2.0-alpha',
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
                dir: 'includes/YOURLS'
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
        phpunit: {
            yourls: {},
            options: {
                configuration: '../phpunit.xml.dist'
            }
        },

        // POT generation task
        pot: {
            options: {
                text_domain: '<%= pkg.authors[0].name %>',
                dest: 'user/languages/YOURLS.pot/',
                package_version: '<%= version %>',
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
            ' * YOURLS v<%= version %>\n' +
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
                options: {
                    banner: '<%= banner %>'
                },
                src: 'assets/js/yourls.js',
                dest: 'assets/js/yourls.min.js'
            }
        },
        less: {
            dev: {
                files: {
                    src: "assets/less/yourls.less",
                    dest: "assets/css/yourls.css"
                },
                options: {
                    sourceMap: true,
                    sourceMapFilename: 'assets/css/yourls.css.map',
                    sourceMapURL: 'yourls.css.map',
                    sourceMapRootpath: '../../'
                }
            },
            dist: {
                files: {
                    src: "assets/less/yourls.less",
                    dest: "assets/css/yourls.min.css"
                },
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
                options: {
                    position: 'top',
                    linebreak: false,
                    banner: '<%= banner %>',
                },
                files: {
                    src: ['assets/css/yourls*']
                }
            }
        },
        replace: {
            version: {
                src: ['includes/YOURLS/Loader.php'],
                overwrite: true,
                replacements: [{
                    from: /const VERSION = \'[0-9a-z\.-]+\';/,
                    to: 'const VERSION = \'<%= version %>\';'
                }]
            },
            composer: {
                src: ['composer.json'],
                overwrite: true,
                replacements: [{
                    from: /"dev-master": "[0-9\.]+x-dev"/,
                    to: '"dev-master": "<%= version.substr(0,3) %>.x-dev"'
                }]
            },
            requirements: {
                src: ['includes/YOURLS/Administration/Installer/Requirements.php'],
                overwrite: true,
                replacements: [{
                    from: /const PHP = \'[0-9a-z\.-]+\';/,
                    to: 'const PHP = \'<%= pkg.require.php.split(">=").pop() %>\';'
                }]
            },
            banner: {
                src: ['includes/+(YOURLS|admin)/**/*.php'],
                overwrite: true,
                replacements: [{
                    from: / \* @version [0-9a-z\.-]+[\n\r]+ \* @copyright 2009-[0-9]+ [a-zA-Z]+[\n\r]+ \* @license [a-zA-Z\s]+[\n\r]+ \*\//,
                    to: ' * @version <%= version %>\n' +
                        ' * @copyright 2009-<%= grunt.template.today("yyyy") %> <%= pkg.authors[0].name %>\n' +
                        ' * @license <%= pkg.license %>\n' +
                        ' */'
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
                files: 'includes/YOURLS/**/*.php',
                tasks: [
                    'phpcsfixer:src',
                    'phpunit'
                ]
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

    // Development Tasks
    // -----------------

    // Default task
    // -> LESS watch
    grunt.registerTask('default', [
        'less:dev',
        'watch:less'
    ]);
    // PHP task
    // -> Checks and fixes for PHP
    // -> Make it distributable
    // -> Generate translation file
    grunt.registerTask('php', [
        'replace',
        'phpcsfixer:src',
        'phpunit',
        'pot'
    ]);
    // Assets task
    // -> Compile JS/HTML
    // -> Make it distributable
    grunt.registerTask('assets', [
        'uglify',
        'less:dist',
        'usebanner'
    ]);
    // Dist task
    // -> Global distribution
    // -> PHP & Assets
    grunt.registerTask('dist', [
        'assets',
        'php'
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
        'update_submodules'
    ]);
};
