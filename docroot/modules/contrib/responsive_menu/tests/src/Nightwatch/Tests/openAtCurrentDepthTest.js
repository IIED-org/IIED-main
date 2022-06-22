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
  'Confirm that when on a page of depth 3 in the navigation that mmenu also opens at that level': browser => {
    browser
      .drupalRelativeURL('/node/6')
      .resizeWindow(400, 800)
    browser
      .expect.element('#off-canvas').to.not.be.visible
    browser
      .click('.responsive-menu-toggle-icon')
      .expect.element('#off-canvas').to.be.visible;
    browser
      .expect.element('.mm-panel_opened .mm-listview li')
      .to.have.attribute('class')
      .which.matches(/mm-listitem_selected/);
    browser
      .expect.element('.mm-panel_opened .mm-listview li a')
      .text.to.equal('Child of third item')
    browser
      .drupalLogAndEnd({ onlyOnError: false });
  }
};
