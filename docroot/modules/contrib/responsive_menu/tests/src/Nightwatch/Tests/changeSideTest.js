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
  'Confirm that the default settings open the mmenu on the left side of the browser': browser => {
    browser
      .drupalRelativeURL('/node/1')
      .resizeWindow(400, 800)
    browser
      .expect.element('#off-canvas').to.not.be.visible
    browser
      .click('.responsive-menu-toggle-icon')
      .expect.element('#off-canvas').to.be.visible
    browser
      .getLocation('#off-canvas', function (result) {
        this.assert.ok(result.value.x <= 0, 'The x position of the open mmenu is 0 or less')
      })
    browser
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'Confirm that changing the setting to the right side makes the opened mmenu appear on the right': browser => {
    browser
      .drupalCreateUser({
        name: 'user',
        password: '123',
        permissions: ['administer site configuration'],
      })
      .drupalLogin({ name: 'user', password: '123' })
      .resizeWindow(1200, 800)
      .drupalRelativeURL('/admin/config/user-interface/responsive-menu')
      .waitForElementVisible('body', 1000)
    browser
      .click('select[id="edit-position"] option[value="right"]')
    browser
      .submitForm('#responsive-menu-settings')
      .waitForElementVisible('body', 1000)
      .drupalRelativeURL('/node/1')
      .resizeWindow(400, 800)
    browser
      .expect.element('#off-canvas').to.not.be.visible
    browser
      .click('.responsive-menu-toggle-icon')
      .expect.element('#off-canvas').to.be.visible
    browser
      .getLocation('#off-canvas', function (result) {
        this.assert.ok(result.value.x > 10, 'The x position of the open mmenu is greater than 10')
      })
    browser
      .drupalLogAndEnd({ onlyOnError: false });
  },
};
