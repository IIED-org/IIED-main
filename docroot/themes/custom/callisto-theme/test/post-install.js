/* Contains tests to check that the gulpconfig has been updated, if not remind the user */

const themeConfig = require('../config/gulpconfig'),
      chai = require('chai'),
      assert = chai.assert;


let siteName;
let sitePort;

if (themeConfig) {
  siteName = themeConfig.browserSync.domain;
  sitePort = themeConfig.browserSync.port;
}

let nodeVersion = process.version;

if (nodeVersion) {
  nodeVersion = nodeVersion.replace('v', '').match(/^(\d+)/g)
  nodeVersion = parseInt(nodeVersion)
}

/* Checks theme configuration */
describe('Post installation: Theme configuration', function() {
  let failed = false

  // This function allows us to mark some tests as 'allow to pass' otherwise mocha throws errors
  it.allowFail = (title, callback) => {
    it(title, function() {
      return Promise.resolve().then(() => {
        return callback.apply(this, arguments)
      }).catch(() => {
        this.skip()
      })
    })
  }

  // If browsersync port has changed from the default
  it.allowFail('Browsersync should have different port', function() {
    assert.notEqual(sitePort, 8080, 'these should not be equal')
  })

  // If browsersync sitename has changed from the default
  it.allowFail('Browsersync should have different sitename', function() {
    assert.notEqual(siteName, 'mysite.lndo.site', 'these should not be equal')
  })

  // If any test doesn't pass then we assume it failed or is pending
  afterEach(function() {
    if (this.currentTest.state != 'passed') {
      failed = true
    }
  })

  // If any tests don't pass, output a helpful message
  after(function() {
    if (failed) {
      console.log('\n   You can change these settings in config/gulpconfig.js')
      console.log('   Check the README.md for more information or go to https://gitlab.agile.coop/agile-public/callisto-theme.')
    }
  })
})

/* Checks node environment */
describe('Post installation: Node environment', function() {
  it('Environment has supported node version', function() {
    if (nodeVersion < 10) {
      assert.operator(nodeVersion, '<', 10, 'Node version is unsupported')
    } else if (nodeVersion && nodeVersion >= 10 < 12) {
      assert.operator(nodeVersion, '>=', 10, 'Node version should be updated')
    } else if (nodeVersion >= 12) {
      assert.operator(nodeVersion, '>=', 12, 'Node version is supported')
    }
  })
})
