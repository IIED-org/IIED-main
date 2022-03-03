'use strict';

const chai = require('chai'),
      assert = chai.assert;
const fs = require('fs'); // Node file system

/* Returns true or false depending if a directory exists or not */
function checkDirectoryExists(directory) {
  try {
    if (fs.existsSync(directory)) {
      return true
    }
  } catch(err) {
    return false
  }
}

/* Alias for checkDirectoryExists() so that code is more readable */
function checkFileExists(file) {
  return checkDirectoryExists(file)
}

/* Returns true if a file contains string */
function fileContains(file, text) {
  try {
    const data = fs.readFileSync(file, {encoding: 'utf8'})
    if(data.includes(text)) {
      return true
    } else {
      return false
    }
  } catch (error) {
    return false
  }
}


describe('Health check: General theme structure', function () {
  describe('Directory structure', function () {
    it('./assets', function(done) {
      assert(checkDirectoryExists('./assets'))
      done()
    });
    it('./config', function(done) {
      assert(checkDirectoryExists('./config'))
      done()
    });
    it('./config/optional', function(done) {
      assert(checkDirectoryExists('./config/optional'))
      done()
    });
    it('./css', function(done) {
      assert(checkDirectoryExists('./css'))
      done()
    });
    it('./js', function(done) {
      assert(checkDirectoryExists('./js'))
      done()
    });
    it('./js/libraries', function(done) {
      assert(checkDirectoryExists('./js/libraries'))
      done()
    });
    it('./source', function(done) {
      assert(checkDirectoryExists('./source'))
      done()
    });
    it('./source/components', function(done) {
      assert(checkDirectoryExists('./source/components'))
      done()
    });
    it('./source/css', function(done) {
      assert(checkDirectoryExists('./source/css'))
      done()
    });
    it('./source/icons', function(done) {
      assert(checkDirectoryExists('./source/icons'))
      done()
    });
    it('./source/js', function(done) {
      assert(checkDirectoryExists('./source/js'))
      done()
    });
    it('./templates', function(done) {
      assert(checkDirectoryExists('./templates'))
      done()
    });
  });

  describe('Checking compiled files exist', function () {
    it('./assets/favicon/favicon.ico', function(done) {
      assert(checkFileExists('./assets/favicon/favicon.ico'))
      done()
    });
    it('./assets/icons/svg-sprite-symbols.svg', function(done) {
      assert(checkFileExists('./assets/icons/svg-sprite-symbols.svg'))
      done()
    });
    it('./css/style.css', function(done) {
      assert(checkFileExists('./css/style.css'))
      done()
    });
    it('./css/style.css.map', function(done) {
      assert(checkFileExists('./css/style.css.map'))
      done()
    });
    it('./css/ck_style.css', function(done) {
      assert(checkFileExists('./css/ck_style.css'))
      done()
    });
    it('./css/ck_style.css.map', function(done) {
      assert(checkFileExists('./css/ck_style.css.map'))
      done()
    });
    it('./js/script.min.js', function(done) {
      assert(checkFileExists('./js/script.min.js'))
      done()
    });
    it('./js/script.min.js.map', function(done) {
      assert(checkFileExists('./js/script.min.js.map'))
      done()
    });
  });

  describe('Verifying source control and metadata', function () {
    it('.gitignore exists', function(done) {
      assert(checkFileExists('./.gitignore'))
      done()
    });
    it('node_modules/ is gitignored', function(done) {
      assert(fileContains('./.gitignore', 'node_modules'));
      done()
    });
    it('composer.json', function(done) {
      assert(checkFileExists('./composer.json'))
      done()
    });
    it('LICENSE', function(done) {
      assert(checkFileExists('./LICENSE'))
      done()
    });
    it('package.json', function(done) {
      assert(checkFileExists('./package.json'))
      done()
    });
    it('README.md', function(done) {
      assert(checkFileExists('./README.md'))
      done()
    });
  });

  describe('Verifying Drupal files', function () {
    it('breakpoints.yml', function(done) {
      assert(checkFileExists('./callisto_theme.breakpoints.yml'))
      done()
    });
    it('info.yml', function(done) {
      assert(checkFileExists('./callisto_theme.info.yml'))
      done()
    });
    it('libraries.yml', function(done) {
      assert(checkFileExists('./callisto_theme.libraries.yml'))
      done()
    });
    it('theme php file', function(done) {
      assert(checkFileExists('./callisto_theme.theme'))
      done()
    });
    it('Content block .yml config', function(done) {
      assert(checkFileExists('./config/optional/block.block.callisto_theme_content.yml'))
      done()
    });
    it('Admin messages block .yml config', function(done) {
      assert(checkFileExists('./config/optional/block.block.callisto_theme_messages.yml'))
      done()
    });
  });

  describe('Making sure config files exist', function () {
    it('config/gulpconfig.js', function(done) {
      assert(checkFileExists('./config/gulpconfig.js'))
      done()
    });
    it('.browserslistrc', function(done) {
      assert(checkFileExists('./.browserslistrc'))
      done()
    });
    it('.eslintrc.json', function(done) {
      assert(checkFileExists('./.eslintrc.json'))
      done()
    });
    it('.stylelintrc', function(done) {
      assert(checkFileExists('./.stylelintrc'))
      done()
    });
    it('tailwind.config.js', function(done) {
      assert(checkFileExists('./tailwind.config.js'))
      done()
    });
  });
});
