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
  'Confirm that the preprocess code adds a unique class to menu items': browser => {
    browser
      .drupalRelativeURL('/node/2')
      .resizeWindow(400, 800)
    browser
      .expect.element('#off-canvas').to.not.be.visible
    browser
      .click('.responsive-menu-toggle-icon')
      .expect.element('#off-canvas').to.be.visible;
    browser
      .expect.element('.mm-listview li').to.have.attribute('class').which.matches(/menu-item--[^\s\\]+/);
    browser
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'Confirm that there is no warning error on pages due to an incorrectly defined menu name #3168579': browser => {
    browser
      .drupalCreateUser({
        name: 'user',
        password: '123',
        permissions: ['administer site configuration'],
      })
    browser
      .drupalLogin({ name: 'user', password: '123' })
      .resizeWindow(1200, 800)
      .drupalRelativeURL('/admin/config/user-interface/responsive-menu')
      .waitForElementVisible('body', 1000)
    browser
      .setValue('#edit-off-canvas-menus', 'test')
    browser
      .submitForm('#responsive-menu-settings')
      .waitForElementVisible('body', 1000)
    browser
      .expect.element('.layout-container').text.not.to.contain('Warning: Invalid argument supplied for foreach()')
    browser
      .drupalLogAndEnd({ onlyOnError: false });
  }
};
