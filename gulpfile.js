const gulp = require("gulp"),
    gulpLoadPlugins = require("gulp-load-plugins"),
    plugins = gulpLoadPlugins(),
    path = require("path");

const plugin_src = {
    js: [
        "public/js/*.js",
        "!public/js/*.min.js",
        "!public/js/vendor/**/*.js"
    ],
    css: [
        "public/css/*.less",
        "public/css/vendor/**/*.less"
    ],
    cssMaps: [
        "public/css/maps/*"
    ],
    images: [
        "public/images/**/*.svg",
        "public/images/**/*.png",
        "public/images/**/*.jpeg",
        "public/images/**/*.jpg"
    ],
    lang: {
        src: [
            "**/*.php",
            "!vendor/**/*.php"
        ],
        dest: "./languages/",
    }
};


gulp.task("js", function () {
    return gulp.src(plugin_src.js)
        .pipe(plugins.plumber())
        .pipe(plugins.uglify({
            compress: true
        }))
        .pipe(plugins.rename({
            extname: ".js",
            suffix: ".min"
        }))
        .pipe(gulp.dest(function (file) {
            return file.base;
        }))
        .pipe(plugins.notify({message: "Скрипты плагина собрались"}));
});

gulp.task("css", function () {
    return gulp.src(plugin_src.css)
        .pipe(plugins.plumber())
        .pipe(plugins.less())
        .pipe(plugins.autoprefixer(["ios_saf >= 6", "last 3 versions"]))
        .pipe(plugins.csso())
        .pipe(gulp.dest(function (file) {
            return file.base;
        }))
        .pipe(plugins.notify({message: "Стили плагина собрались"}));
});

gulp.task("i18n", function () {
    return gulp.src(plugin_src.lang.src)
        .pipe(plugins.sort())
        .pipe(plugins.wpPot({
            package: path.basename(__dirname)
        }))
        .pipe(plugins.rename({
            basename: path.basename(__dirname),
            extname: ".pot"
        }))
        .pipe(gulp.dest(plugin_src.lang.dest));
});


gulp.task("default", ["i18n"]);

