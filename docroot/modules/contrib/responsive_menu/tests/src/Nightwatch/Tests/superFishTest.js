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
  'Confirm that superfish functionality works': browser => {
    browser
      .drupalRelativeURL('/node/1')
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
      .click('input[id="edit-superfish"]')
      .expect.element('input[id="edit-superfish"]').to.be.selected
    browser
      .submitForm('#responsive-menu-settings')
      .waitForElementVisible('body', 1000)
      .drupalRelativeURL('/node/1')
    browser
      .assert.cssClassPresent('#horizontal-menu', 'sf-js-enabled')
    browser
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'Change the superfish config and confirm the drupalSettings values are updated': browser => {
    browser
      .drupalLogin({ name: 'user', password: '123' })
      .resizeWindow(1200, 800)
      .drupalRelativeURL('/admin/config/user-interface/responsive-menu')
      .waitForElementVisible('body', 1000)
    browser
      .clearValue('input[id="edit-superfish-delay"]')
      .setValue('input[id="edit-superfish-delay"]', 500)
    browser
      .clearValue('input[id="edit-superfish-speed"]')
      .setValue('input[id="edit-superfish-speed"]', 600)
    browser
      .clearValue('input[id="edit-superfish-speed-out"]')
      .setValue('input[id="edit-superfish-speed-out"]', 700)
    browser
      .submitForm('#responsive-menu-settings')
      .waitForElementVisible('body', 1000)
      .drupalRelativeURL('/node/1')
      .waitForElementVisible('body', 1000)
    browser
      .execute(function() {
        return drupalSettings.responsive_menu.superfish.delay
      }, [], function(result) {
        browser.assert.strictEqual(result.value, 500, 'The delay is set at 500');
      })
    browser
      .execute(function() {
        return drupalSettings.responsive_menu.superfish.speed
      }, [], function(result) {
        browser.assert.strictEqual(result.value, 600, 'The speed is set at 600');
      })
    browser
      .execute(function() {
        return drupalSettings.responsive_menu.superfish.speedOut
      }, [], function(result) {
        browser.assert.strictEqual(result.value, 700, 'The speedout is set at 700');
      })
    browser
      .drupalLogAndEnd({ onlyOnError: false });
  }
};
