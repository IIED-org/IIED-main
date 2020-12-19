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
  'Confirm that enabling and disabling the viewport option changes the viewport meta tag': browser => {
    browser
      .drupalRelativeURL('/node/1')
    browser
      .drupalCreateUser({
        name: 'user',
        password: '123',
        permissions: ['administer site configuration'],
      })
      .drupalLogin({ name: 'user', password: '123' })
      .drupalRelativeURL('/node/2')
      .resizeWindow(400, 800)
    browser
      .expect.element('#off-canvas').to.not.be.visible
    browser
      .verify.attributeEquals("meta[name='viewport']", 'content', 'width=device-width, initial-scale=1.0')
    browser
      .click('.responsive-menu-toggle-icon')
      .expect.element('#off-canvas').to.be.visible
    browser
      .verify.attributeEquals("meta[name='viewport']", 'content', 'width=device-width, initial-scale=1.0, minimum-scale=1.0')
    // Change the modify_viewport settings so that it is unchecked.
    browser
      .drupalRelativeURL('/admin/config/user-interface/responsive-menu')
      .waitForElementVisible('body', 1000)
    browser
      .click('input[id="edit-modify-viewport"]')
      .expect.element('input[id="edit-modify-viewport"]').to.not.be.selected
    browser
      .submitForm('#responsive-menu-settings')
      .waitForElementVisible('body', 1000)
      .drupalRelativeURL('/node/2')
    // Confirm the metatag does not change.
    browser
      .expect.element('#off-canvas').to.not.be.visible
    browser
      .verify.attributeEquals("meta[name='viewport']", 'content', 'width=device-width, initial-scale=1.0')
    browser
      .click('.responsive-menu-toggle-icon')
      .expect.element('#off-canvas').to.be.visible
    browser
      .verify.attributeEquals("meta[name='viewport']", 'content', 'width=device-width, initial-scale=1.0')
    browser
      .drupalLogAndEnd({ onlyOnError: false });
  },
};
