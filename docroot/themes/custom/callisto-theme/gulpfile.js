'use strict';

/**
 * gulpfile.js requirements
 */

// Scaffold requirements
const gulp = require('gulp'), // This taskrunner,
      rename = require('gulp-rename'), // Allows to rename files for destination See https://yarnpkg.com/package/gulp-rename
      buffer = require('vinyl-buffer'), // Converts stream vinyl files into buffer https://yarnpkg.com/package/vinyl-buffer
      source = require('vinyl-source-stream'), // Makes it easier for us to work with certain types of streams https://www.npmjs.com/package/vinyl-source-stream
      glob = require('glob'), // Allows node.js to glob for files https://github.com/isaacs/node-glob
      path = require('path'), // NodeJS module for working with paths https://nodejs.org/api/path.html
      config = require('./config/gulpconfig.js'); // Import config

// CSS requirements
const sass = require('gulp-sass'), // Sass plugin for gulp See https://yarnpkg.com/package/gulp-sass
      sassGlob = require('gulp-sass-glob'), // Allows the import of patterns through '/**/*.scss' See https://yarnpkg.com/package/gulp-sass-glob
      postcss = require('gulp-postcss'), // PostCSS processor https://github.com/postcss/postcss
      browserSync = require('browser-sync').create(), // Create BrowserSync instance See https://www.browsersync.io/docs/gulp
      autoprefixer = require('autoprefixer'), // Automatically add vendor rules https://github.com/postcss/autoprefixer
      cssnano = require('cssnano'), // Minify CSS stylsheets https://cssnano.co/
      sourcemaps = require('gulp-sourcemaps'), // Enables sourcemap generation https://yarnpkg.com/package/gulp-sourcemaps
      tailwind = require('tailwindcss'), // Utility-First CSS Framework https://tailwindcss.com/docs/
      stylelint = require('stylelint'); // SASS and CSS style linting https://stylelint.io/

// JS requirements
const rollup = require('@rollup/stream'), // Rollup.js with streaming support https://github.com/rollup/stream
      closureCompiler = require('google-closure-compiler').gulp(), // Polyfill and compile https://yarnpkg.com/package/google-closure-compiler
      nodeResolve = require('@rollup/plugin-node-resolve'), // Allows rollup to resolve node_modules, all plugins https://github.com/rollup/plugins
      commonjs = require('@rollup/plugin-commonjs'), // Allows rollup to convert commonjs modules
      multi = require('@rollup/plugin-multi-entry'), // Provides glob functionality for rollup
      rollupEslint = require('@rbnlffl/rollup-plugin-eslint'); // Eslint plugin for Rollup https://github.com/robinloeffel/rollup-plugin-eslint


// Icons requirements
const svgSprite = require('gulp-svg-sprite'); // Creates sprites from a list of SVGs https://github.com/jkphl/gulp-svg-sprite


/**
 *
 * Base Utilities start
 * Here we set up some configuration and functions that we may reuse
 *
 */

/**
 * Custom PurgeCSS extractor for Tailwind that allows special characters in class names.
 *
 * https://github.com/FullHuman/purgecss#extractor
 */
class TailwindExtractor {
  static extract(content) {
    return content.match(/[A-Za-z0-9-_:\/]+/g) || [];
  }
}

/**
 * PostCSS plugins and configuration mapped to gulpconfig.js
 */
const postcssPlugins = [
  stylelint({
    failAfterError: config.css.stylelint.failAfterError,
    reportOutputDir: config.css.stylelint.reportOutputDir,
    extractors: [{extractor: TailwindExtractor, extensions: ["twig", "html"]}],
    debug: config.css.stylelint.debug,
    fix: config.css.stylelint.autofix,
    reporters: [
      { formatter: 'string',  console: true },
    ]
  }),
  tailwind(),
  autoprefixer({
    add: config.css.autoprefixer.addPrefixes,
    remove: config.css.autoprefixer.removeOutdatedPrefixes,
    supports: config.css.autoprefixer.supports
  }),
];

/**
 * CSSnano configuration mapped to gulpconfig.js
 */
const postCSSnano = [
  cssnano({
    preset: ['default', {
      discardComments: {
        removeAll: config.css.cssnano.removeAllComments,
      },
      reduceTransforms: config.css.cssnano.reduceTransforms,
    }]
  })
];

/**
 * SVG to sprite configuration
 */
const svgConfig = {
  shape: {
    transform: [
      {
        svgo: config.icons.transformers.svgo
      }
    ],
    meta: './source/icons/' + config.icons.metadataFilename
  },
  mode: {
    ...(config.icons.cssSprite.enable && {
      css: { // Activate the «css» mode
        mode: 'css', // Sprite with «css» mode
        dest: 'assets/icons',
        sprite: 'svg-sprite-css.svg',
        render: {
          scss: {
            dest: '../../source/' + config.icons.cssSprite.cssLocation
          }
        },
        mixin: '',
        dimensions: config.icons.cssSprite.dimensions,
        example: config.icons.cssSprite.example,
        bust: config.icons.cssSprite.cacheBust,
      },
    }),
    symbol: {
      mode: 'symbol',
      dest: 'assets/icons',
      sprite: 'svg-sprite-symbols.svg',
      bust: false,
      inline: true,
    }
  }
};


/**
 * Rollup errors we want to ignore
 */
const rollupErrors = [
  'MISSING_NAME_OPTION_FOR_IIFE_EXPORT',
  'SOURCEMAP_BROKEN'
]

/**
 * Eslint plugin for rollup so we can conditionally include it
 */
const rollEslint = [
  rollupEslint({fix: process.env.NODE_ENV === 'production' ? true : false})
]

let cache; // declare the cache variable for rollup

/**
 * General rollup config
 */
const rollupConfig = {
  input: {
    include: ['source/components/**/*.js'],
    exclude: ['source/components/**/_*.js', 'source/components/**/&*.js']
  },
  cache,
  plugins: [
    multi(),
    ...(config.js.eslint.enabled ? rollEslint: []),
    nodeResolve.default({
      browser: true,
    }),
    commonjs({
      transformMixedEsModules: false
    })
  ],
  output: {
    format: 'iife',
    sourcemap: true
  },
  onwarn: ( warning, next ) => {
    if (rollupErrors.includes(warning.code)) return;
    next( warning );
  },
}

/**
 * General closure-compiler config
 */
const closureConfig = {
  compilation_level: config.js.closureCompile.compilationLevel,
  module_resolution: 'NODE',
  language_out: 'ECMASCRIPT5',
  warning_level: config.js.closureCompile.warningLevel,
  hide_warnings_for: 'node_modules/*',
  output_wrapper: config.js.closureCompile.outputWrapper,
  js_output_file: 'script.min.js',
  externs: config.js.closureCompile.externs,
}

/**
 * Start browserync with gulpconfig.js configuration
 */
function browserSyncStart() {
  browserSync.init({
    proxy: config.browserSync.domain,
    startPath: config.browserSync.startPath,
    port: config.browserSync.port,
    ghostMode: {
      clicks: config.browserSync.ghostMode.clicks,
      forms: config.browserSync.ghostMode.forms,
      scroll: config.browserSync.ghostMode.scroll
    },
    open: false
  });
}

/**
 * Base Utilities end
 */



/**
 *
 * Functions start
 *
 */

/**
 * Generates the CK editor stylesheet
 */
function generateCkStyle() {
  return (
    gulp
      .src('source/css/ck_style.scss', { base: 'source' })
      .pipe(sourcemaps.init())
      .pipe(sassGlob({
        ignorePaths: [
            '**/_*.scss'
        ]
      }))
      .pipe(sass())
      .on('error', sass.logError)
      .pipe(rename('ck_style.css')) // Rename the file to style.css
      .pipe(postcss(postcssPlugins)) // Run postCSS
      .pipe(postcss(postCSSnano)) // Run postCSS
      .pipe(sourcemaps.mapSources(function(sourcePath, file) {
        return '../source/' + sourcePath;
      }))
      .pipe(sourcemaps.write('.'))
      .pipe(gulp.dest('css/'))
  );
}

/**
 * Generates the drupal stylesheet from patterns
 */
function generateStyle() {
  return (
    gulp
      .src('source/css/style.scss', { base: 'source' })
      .pipe(sourcemaps.init())
      .pipe(sassGlob({
        ignorePaths: [
            '_ck-editor/**/*',
            '**/ck-*.scss',
            '**/_*.scss'
        ]
      }))
      .pipe(sass())
      .on('error', sass.logError)
      .pipe(rename('style.css')) // Rename the file to style.css
      .pipe(postcss(postcssPlugins)) // Run postCSS
      .pipe(postcss(postCSSnano)) // Run postCSS
      .pipe(sourcemaps.mapSources(function(sourcePath, file) {
        return '../source/' + sourcePath;
      }))
      .pipe(sourcemaps.write('.'))
      .pipe(gulp.dest('css/'))
  );
}

/**
 * Gathers all of the component JS files and runs lint and compiles them into one
 */
function compileJS() {
  return rollup(rollupConfig)
    .on('bundle', (bundle) => {
      cache = bundle; // update the cache after every new bundle is created
    })
    .pipe(source('script.js'))
    .pipe(buffer())
    .pipe(sourcemaps.init({ loadMaps: true }))
    .pipe(closureCompiler(closureConfig))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('js'));
}


let globCache = []; // declare our glob cache

/**
 * Compiles JS files without combining them into the main script.js file
 * Useful for large scripts that need to be individually added as libraries in Drupal
 */
function compileJSLibraries() {
  function doTheRollup(index, file) {
    const fileName = path.basename(file).replace('&', '')
    return rollup({
      input: {
        include: file,
        preserveModules: true,
      },
      plugins: [
        multi(),
        ...(config.js.eslint.enabled ? rollEslint: []),
        nodeResolve.default({
          browser: true,
        }),
        commonjs({
          transformMixedEsModules: false
        })
      ],
      output: {
        format: 'iife',
        sourcemap: true
      },
      onwarn: ( warning, next ) => {
        if (rollupErrors.includes(warning.code)) return;
        console.log(warning.code)
        next( warning );
      },
    })
    .pipe(source(fileName))
    .pipe(buffer())
    .pipe(sourcemaps.init({ loadMaps: true }))
    .pipe(closureCompiler({
      compilation_level: config.js.libraries.closureCompile.compilationLevel,
      module_resolution: 'NODE',
      language_out: 'ECMASCRIPT5',
      warning_level: config.js.libraries.closureCompile.warningLevel,
      hide_warnings_for: 'node_modules/*',
      output_wrapper: config.js.libraries.closureCompile.outputWrapper,
      js_output_file: fileName.replace('.js', '.min.js'),
      externs: config.js.libraries.closureCompile.externs,
    }))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('js/libraries'));
  }
  return glob('source/components/**/&*.js', {cache: globCache}, function (er, files) {
    const promises = []

    for (let index = 0; index < files.length; index++) {
      const file = files[index]
      globCache.push(file)
      promises.push(doTheRollup(index, file))
    }
    return Promise.all(promises)
  })
}

async function enableJSLibraries() {
  return config.js.libraries.enabled ? compileJSLibraries() : null;
}

/**
 * Generates an SVG sprite from a list of SVGs
 */
function generateSvgs() {
  return (
    gulp
      .src('source/icons/*.svg')
      .pipe(svgSprite(svgConfig))
      .pipe(gulp.dest('./'))
  );
}

/**
 * Watches JS files in source/components and re-compiles them on change
 */
function watchJS() {
  return (
    gulp
      .watch('./source/components/**/*.js')
      .on('change', function() {
        compileJS(),
        enableJSLibraries()
      })
  );
}

/**
 * Watches SASS files in source/components and re-compiles them on change
 */
function watchStyle() {
  return (
    gulp
      .watch('./source/components/**/*.scss')
      .on('change', generateStyle)
  );
}

/**
 * Watches SVG files in source/icons and re-compiles them on change
 */
function watchSvgs() {
  return (
    gulp
      .watch('./source/icons/*.svg')
      .on('change', generateSvgs)
  );
}


/**
 * Serve files by streaming them into browserSync
 */
function serve() {
  return (
    gulp
      .watch(['./js/*.js', './css/*.css', './**/*.twig'])
      .on('change', browserSync.reload)
  );
}
/**
 * Functions end
 */



/**
 * Gulp tasks exports start
 */

/** Default development task */
const dev = gulp.series(
  generateStyle,
  compileJS,
  enableJSLibraries,
  generateSvgs,
  gulp.parallel(browserSyncStart, watchStyle, watchJS, watchSvgs, serve)
);

/** Generate CK Editor stylesheet task */
const ck = gulp.series(
  generateCkStyle
);

/** Generate stylesheets and javascript task */
const generate = gulp.series(
  generateStyle,
  compileJS,
  enableJSLibraries,
  generateCkStyle,
  generateSvgs
);

/** Generate svg sprite */
const generateSprite = gulp.series(
  generateSvgs
);


// Expose the task by exporting it, this allows you to run it from the commandline
exports.dev = dev;
exports.ck = ck;
exports.gen = generate;
exports.generatesvg = generateSprite;

/*
* Gulp tasks exports end
*/

