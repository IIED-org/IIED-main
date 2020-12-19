module.exports = {
  '@tags': ['responsive_menu'],
  before(browser) {
    browser.drupalInstall({
      setupFile: __dirname + '/../SiteInstallSetupScript.php',
      installProfile: 'minimal',
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Confirm test menu items display in horizontal menu': browser => {
    browser
      .drupalRelativeURL('/')
      .waitForElementVisible('body', 1000)
      .waitForElementVisible('#block-responsive-menu-horizontal-menu', 1000)
      .resizeWindow(1200, 800)
      .expect.element('#horizontal-menu').to.be.present
    browser
      .expect.elements('#horizontal-menu a').count.to.equal(7)
    browser
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'Resize page and confirm toggle icon shows and horizontal menu hides': browser => {
    browser
      .drupalRelativeURL('/node/1')
      .resizeWindow(1200, 800)
      .waitForElementVisible('body', 1000)
      .expect.element('#block-responsive-menu-toggle').to.not.be.visible
    browser
      .resizeWindow(400, 800)
      .waitForElementVisible('#block-responsive-menu-toggle', 1000)
      .expect.element('.responsive-menu-block-wrapper').to.not.be.visible
    browser
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'Open off-canvas menu with toggle icon': browser => {
    browser
      .drupalRelativeURL('/node/1')
      .waitForElementVisible('body', 1000)
      .waitForElementVisible('#block-responsive-menu-toggle', 1000)
      .expect.element('#off-canvas').to.not.be.visible
    browser
      .click('.responsive-menu-toggle-icon')
      .expect.element('#off-canvas').to.be.visible
    browser
      .drupalLogAndEnd({ onlyOnError: false });
  },
};
