module.exports = function (grunt) {
    'use strict';

    grunt.loadNpmTasks('grunt-curl');

    grunt.initConfig({
        curl: {
            'database/temp/GeoLite2-Country.mmdb.gz': 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz'
        },
        gzip: {
            'database/GeoLite2-Country.mmdb': 'database/temp/GeoLite2-Country.mmdb.gz'
        }
    });

    grunt.registerMultiTask('gzip', function () {
        var zlib = require('zlib'),
            done = this.async(),
            file = this.file;

        function process() {
            grunt.log.writeln("Uncompressing " + file.src + "...");
            var content = grunt.file.read(file.src, { encoding: null });

            zlib.gunzip(content, function (err, compressed) {
                grunt.file.write(file.dest, compressed);
                grunt.log.ok("Uncompressed file written to " + file.dest);
                done();
            });
        }

        process();
    });

    grunt.registerTask('default', ['curl', 'gzip']);
};
