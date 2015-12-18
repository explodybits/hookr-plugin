module.exports = function (grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        watch: {
            js: {
                files: ['assets/js/*.js'],
                tasks: ['uglify', 'concat', 'copy', 'clean'],
                options: {
                    spawn: false
                }
            },                        
            css: {
                files: ['./assets/less/*.less']
               ,tasks: ['less', 'concat', 'cssmin', 'copy']
            },
            remove: {
                files: []
            }            
        },        
        less: {
            development: {
                options: {
                    paths: ['./assets/'],
                    yuicompress: false
                },
                files: {
                    './assets/css/screen.css': './assets/less/screen.less',
                    './assets/css/screen-admin.css': './assets/less/screen-admin.less'
                }
            }
        },
        cssmin: {
            options: {
                shorthandCompacting: false,
                roundingPrecision: -1
            },
            target: {
                files: {
                    './assets/css/screen.min.css': ['./assets/css/screen.css'],
                    './assets/css/screen-admin.min.css': ['./assets/css/screen-admin.css']                    
                }
            }
        },
        uglify: {
            options: {
                mangle: true,
                sourceMap: true,
                compress: {
                    sequences: true,
                    dead_code: true,
                    conditionals: true,
                    booleans: true,
                    unused: true,
                    if_return: true,
                    join_vars: true,
                    drop_console: false
                }
            },
            build: {
                files: {
                    'assets/js/hookr.min.js': 'assets/js/hookr.js'
                }
            }
        },
        concat: {
            js: {
                src: [
                    './assets/vendor/jquery.qtip.custom/jquery.qtip.min.js',
                    './assets/vendor/jquery-scrollLock-master/jquery-scrollLock.min.js',
                    './assets/vendor/lunr.js-master/lunr.min.js',
                    './assets/js/hookr.min.js'
                    
                ],
                dest: './assets/js/hookr.min.js',
            }/*,
            css: {
                options: {
                    separator: ';\n'
                },
                files: {
                    './assets/css/screen.css': ['./assets/css/screen.less.css'],
                    './assets/css/screen-admin.css': ['./assets/css/screen-admin.less.css']
                }
            }*/
        },
        copy:{
            js: {
                files: [
                    {
                        dest: './assets/js/bs-loophole.min.js',
                        src: ['./assets/vendor/bs-loophole-master/dist/bs-loophole.min.js']
                    }/*,
                    {
                        dest: './assets/js/hookr.min.js',
                        src: ['./assets/js/_hookr.min.js']                        
                    }*/
                ]
            },
            css: {
                files: [
                    {
                        dest: './assets/css/jquery.qtip.min.css',
                        src: ['./assets/vendor/jquery.qtip.custom/jquery.qtip.min.css']
                    }
                ]
            }
        },
        clean: {
            css: ['./assets/css/*.*', '!./assets/css/*.min.css']
        }                
    });
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-less');  
    grunt.loadNpmTasks('grunt-contrib-cssmin');  
    grunt.loadNpmTasks('grunt-contrib-uglify');  
    grunt.loadNpmTasks('grunt-contrib-concat'); 
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.registerTask('default', ['watch']);    
    grunt.registerTask(
        'build',
        'Compiles all the assets and copies the files to the build directory.',
       ['uglify', 'concat', 'less', 'concat', 'cssmin', 'copy', 'clean']
    );
};