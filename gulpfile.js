var elixir = require('laravel-elixir');
var Promise = require('es6-promise').Promise;
var fs = require('fs');
var path = require('path');

function getFolders(dir) {
    return fs.readdirSync(dir)
      .filter(function(file) {
        return fs.statSync(path.join(dir, file)).isDirectory();
      });
}

var folders = getFolders('integer');    
var version = [
    './build/rialtotender/css/styles.css',
    './build/rialtotender/js/app.js',
    './build/app/css/styles.css',
    './build/app/js/app.js'
];

folders.map(function(folder) {

    elixir(function(mix) {
        mix.sass([
            './integer/'+folder+'/theme/resources/scss/styles.scss',
        ], './integer/'+folder+'/theme/assets/css/styles.css');

        mix.styles([
            './integer/'+folder+'/theme/assets/css/styles.css',
        ],'./build/'+folder+'/css/styles.css');

        mix.scripts([
            //'./integer/'+folder+'/theme/resources/js/blocks/**/*.js',
            './integer/'+folder+'/theme/resources/js/filter.js',
            './integer/'+folder+'/theme/resources/js/app.js',
        ], './build/'+folder+'/js/app.js');

        version.push('./build/'+folder+'/js/app.js');
        version.push('./build/'+folder+'/css/styles.css');
    });
});

elixir(function(mix) {

    mix.styles([
        './bower_components/select2/dist/css/select2.min.css',
        './bower_components/datetimepicker/jquery.datetimepicker.css',
        './bower_components/ilyabirman-likely/release/likely.css'
    ],'./build/app/css/styles.css');

    mix.scripts([
        './bower_components/jquery/dist/jquery.js',
        './bower_components/slick-carousel/slick/slick.min.js',
        './bower_components/jquery-sticky/jquery.sticky.js',
        './bower_components/history.js/scripts/bundled-uncompressed/html4+html5/jquery.history.js',
        './bower_components/spin.js/spin.js',
        './bower_components/spin.js/jquery.spin.js',
        //'./bower_components/select2/dist/js/select2.min.js',
        './themes/rialtotender/resources/js/vendor/bootstrap-datepicker.js',//?
        './themes/rialtotender/resources/js/vendor/jquery.timepicker.js',//?
        './bower_components/datetimepicker/build/jquery.datetimepicker.full.min.js',//?
        './bower_components/noty/js/noty/packaged/jquery.noty.packaged.js',
        './themes/rialtotender/resources/js/localization_dates.js',
        './themes/rialtotender/resources/js/error.js',
        './modules/system/assets/js/framework.js',
        //'./themes/rialtotender/resources/js/list_cpv.js',
        //'./themes/rialtotender/resources/js/search_cpv.js',
        './bower_components/ilyabirman-likely/release/likely.js',
        './themes/rialtotender/resources/js/cpv-tree.js',
    ], './build/app/js/app.js');
    
    
    mix.sass([
        './themes/rialtotender/resources/scss/styles.scss'
    ], './themes/rialtotender/assets/css/styles.css');

    mix.styles([
        './themes/rialtotender/assets/css/styles.css',
    ],'./build/rialtotender/css/styles.css');

    mix.scripts([
        //'./themes/rialtotender/resources/js/blocks/**/*.js',
        './themes/rialtotender/resources/js/filter.js',
        './themes/rialtotender/resources/js/app.js',
    ], './build/rialtotender/js/app.js');

    mix.version(version, './build');    
});