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
  'Confirm that changing the setting to contextual makes the opened mmenu appear on the left with a LTR language': browser => {
    browser
      .drupalRelativeURL('/node/1')
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
      .click('select[id="edit-position"] option[value="contextual"]')
    browser
      .submitForm('#responsive-menu-settings')
      .waitForElementVisible('body', 1000)
      .drupalRelativeURL('/node/2')
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
  'Confirm that changing the setting to contextual makes the opened mmenu appear on the right with an RTL language': browser => {
    browser
      .drupalRelativeURL('/node/1')
    browser
      .resizeWindow(1200, 800)
      .drupalRelativeURL('/admin/config/user-interface/responsive-menu')
      .waitForElementVisible('body', 1000)
    browser
      .click('select[id="edit-position"] option[value="contextual"]')
    browser
      .submitForm('#responsive-menu-settings')
      .waitForElementVisible('body', 1000)
      .drupalRelativeURL('/ar/node/2')
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
