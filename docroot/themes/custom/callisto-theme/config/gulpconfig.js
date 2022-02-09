module.exports = {
  css: {
    autoprefixer: { // https://github.com/postcss/autoprefixer#options
      addPrefixes: true,
      removeOutdatedPrefixes: true,
      supports: true // @supports
    },
    cssnano: {
      removeAllComments: true,
      reduceTransforms: false, // Changes 3D transforms into 2D ones if possible
    },
    stylelint: { // See .stylelintrc for more config options and https://stylelint.io/user-guide
      failAfterError: true, // true | false
      reportOutputDir: 'reports', // Directory for reports
      debug: false, // Shows full stack trace
      autofix: true // Attempts to autofix violations
    }
  },
  icons: {
    cssSprite: {
      enable: true, // Enables the SASS sprite https://github.com/jkphl/svg-sprite/blob/master/docs/configuration.md
      cssLocation:  'components/01-foundation/00-sass-utilities/icons-sprite.scss', // Location of the SASS file relative to the generated svg in assets
      example: false, // Outputs an HTML file showing all the icons as an example
      dimensions: false, // Enable dimensions on the SASS sprite
      cacheBust: false, // Ideally you leave cachebusting to Drupal
    },
    transformers: { // Set of transformations to run on the SVG files
      svgo: { // SVG optimiser as sub-module of svgSprite https://github.com/svg/svgo configuration is 1:1
        plugins: [
          {
            removeTitle: false
          },
          {
            removeDesc: false
          },
        ]
      }
    },
    useYml: true, // Supports additional metadata based on the icon_data.yml inside source/icons/
    metadataFilename: 'icon_data.yml', // Name of the yml file with the file extension
  },
  js: {
    eslint: {
      enabled: true, // Turn off if the linter is posing problems
      autofix: true, // Attempts to fix issues detected silently, will throw error if it can't do that
    },
    closureCompile: {
      compilationLevel: 'ADVANCED', // 'ADVANCED' or 'SIMPLE'
      warningLevel: 'QUIET', // 'QUIET' or 'VERBOSE'
      outputWrapper: '(function(){\n%output%\n}).call(this)', // Using %output% for contents
      externs: [
        'source/js/externals.js', // Files containing declarations for external libraries
      ]
    },
    // Configuration specifically for custom JS libraries generated with &
    libraries: {
      enabled: true, // Disable if you don't need separate JS libraries compiled
      closureCompile: {
        compilationLevel: 'ADVANCED', // 'ADVANCED' or 'SIMPLE'
        warningLevel: 'QUIET', // 'QUIET' or 'VERBOSE'
        outputWrapper: '(function(){\n%output%\n}).call(this)', // Using %output% for contents
        externs: [
          'source/js/externals.js', // Files containing declarations for external libraries
        ]
      },
    }
  },
  // BrowserSync configuration https://www.browsersync.io/docs/options
  browserSync: {
    port: 3050,
    domain: 'iied-main.lndo.site', // local dev domain
    baseDir: './',
    startPath: '/', // default start path, can be changed to a specific page on the website
    // Sync behavior across all devices
    ghostMode: {
      clicks: true, // Sync clicks
      forms: true, // Sync and submit forms across instances
      scroll: true // Sync scroll across instances
    }
  },
};
