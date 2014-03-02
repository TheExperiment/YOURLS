module.exports = function (grunt) {
    'use strict';

    // Tools
    var path = require('path');

    // Configuration & option
    grunt.initConfig({

        // Variables
        version: '2.0-alpha',
        pkg: grunt.file.readJSON('composer.json'),

        // PHP tasks
        php: {
            server: {
                options: {
                    keepalive: true,
                    open: true,
                    port: 8085
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
                    'indentation', 'linefeed', 'trailing_spaces',
                    'unused_use', 'short_tag', 'return', 'visibility',
                    'php_closing_tag', 'extra_empty_lines', 'include',
                    'psr0', 'elseif', 'eof_ending'
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
                    "assets/css/yourls.css": "assets/less/yourls.less"
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
                    "assets/css/yourls.min.css": "assets/less/yourls.less"
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
                tasks: ['phpcsfixer:src', 'phpunit']
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

    // Create the gzip task engine
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
    grunt.loadNpmTasks('grunt-php');
    grunt.loadNpmTasks('grunt-composer');
    grunt.loadNpmTasks('grunt-php-cs-fixer');
    grunt.loadNpmTasks('grunt-phpunit');
    grunt.loadNpmTasks('grunt-pot');
    grunt.loadNpmTasks('grunt-bower-task');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-update-submodules');
    grunt.loadNpmTasks('grunt-banner');
    grunt.loadNpmTasks('grunt-text-replace');
    grunt.loadNpmTasks('grunt-curl');

    // Custom tasks
    grunt.registerTask('default', ['less:dev', 'watch:less']);
    grunt.registerTask('assets', ['uglify', 'less:dist', 'usebanner']);
    grunt.registerTask('phpdist', ['replace', 'phpcsfixer:src', 'phpunit']);
    grunt.registerTask('geoip', ['composer:update:no-dev:optimize-autoloader:working-dir=user/plugins/geoip/',
        'curl', 'gzip']);
    grunt.registerTask('update', ['composer:update:no-dev:optimize-autoloader',
        'bower', 'update_submodules']);
};
