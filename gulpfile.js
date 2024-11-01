// including plugins
var gulp = require('gulp')
    , gulpLoadPlugins = require('gulp-load-plugins')
    , plugins = gulpLoadPlugins()
    , minifyCss = require("gulp-minify-css")
    , concat = require('gulp-concat-sourcemap')
    , uglify = require("gulp-uglify");

// task
gulp.task('minify-css', function () {
    gulp.src('./css/user-switching-front.css') // path to your file
        .pipe(plugins.concat('user-switching-front.min.css'))
        .pipe(minifyCss())
        .pipe(gulp.dest('./css/'));
});

// task
gulp.task('minify-js', function () {
    gulp.src('./js/s_users.js') // path to your files
        .pipe(uglify())
        .pipe(plugins.concat('s_users.min.js'))
        .pipe(gulp.dest('./js/'));
});

// On default task, just compile on demand
gulp.task('default', ['minify-css', 'minify-js'], function() {
    gulp.watch('assets/js/*.js', [ 'minify-js' ]);
    gulp.watch('assets/css/*.css', [ 'minify-css' ]);
});