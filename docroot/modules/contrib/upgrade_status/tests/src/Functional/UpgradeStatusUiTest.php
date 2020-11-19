<?php

namespace Drupal\Tests\upgrade_status\Functional;

use Drupal\Core\Url;

/**
 * Tests the UI before and after running scans.
 *
 * @group upgrade_status
 */
class UpgradeStatusUiTest extends UpgradeStatusTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['administer software updates']));
  }

  /**
   * Test the user interface before running a scan.
   */
  public function testUiBeforeScan() {
    $this->drupalGet(Url::fromRoute('upgrade_status.report'));
    $assert_session = $this->assertSession();

    $assert_session->buttonExists('Scan selected');
    $assert_session->buttonExists('Export selected as HTML');

    // Scan result for every project should be 'N/A'.
    $status = $this->getSession()->getPage()->findAll('css', 'td.scan-result');
    $this->assertNotEmpty($status);
    foreach ($status as $project_status) {
      $this->assertSame('N/A', $project_status->getHtml());
    }
  }

  /**
   * Test the user interface after running a scan.
   */
  public function testUiAfterScan() {
    $this->runFullScan();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $assert_session->buttonExists('Scan selected');
    $assert_session->buttonExists('Export selected as HTML');

    // Error and no-error test module results should show.
    $this->assertSame('4 problems', strip_tags($page->find('css', 'tr.project-upgrade_status_test_error td.scan-result')->getHtml()));
    $this->assertSame('No problems found', strip_tags($page->find('css', 'tr.project-upgrade_status_test_no_error td.scan-result')->getHtml()));

    // Parent module should show up without errors and submodule should not appear.
    $this->assertSame('No problems found', strip_tags($page->find('css', 'tr.project-upgrade_status_test_submodules td.scan-result')->getHtml()));
    $this->assertEmpty($page->find('css', 'tr.upgrade_status_test_submodules_a'));

    // Contrib test modules should show with results.
    $this->assertSame('2 problems', strip_tags($page->find('css', 'tr.project-upgrade_status_test_contrib_error td.scan-result')->getHtml()));
    $this->assertSame('No problems found', strip_tags($page->find('css', 'tr.project-upgrade_status_test_contrib_no_error td.scan-result')->getHtml()));
    // This contrib module has a different project name. Ensure the drupal.org link used that.
    $this->assertSession()->linkByHrefExists('https://drupal.org/project/issues/upgrade_status_test_contributed_no_error?text=Drupal+9&status=All');

    // Click the first '4 problems' link. Should be the contrib project.
    $this->clickLink('4 problems');
    $this->assertText('Upgrade status test error ' . \Drupal::VERSION);
    $this->assertText('2 errors found. 2 warnings found.');
    $this->assertText('Syntax error, unexpected T_STRING on line 3');

    // Go forward to the export page and assert that still contains the results
    // as well as an export specific title.
    $this->clickLink('Export as HTML');
    $this->assertText('Upgrade Status report');
    $this->assertText('Upgrade status test error ' . \Drupal::VERSION);
    $this->assertText('Custom projects');
    $this->assertNoText('Contributed projects');
    $this->assertText('2 errors found. 2 warnings found.');
    $this->assertText('Syntax error, unexpected T_STRING on line 3');

    // Go back to the listing page and click over to exporting in single ASCII.
    $this->drupalGet(Url::fromRoute('upgrade_status.report'));
    $this->clickLink('4 problems');
    $this->clickLink('Export as text');
    $this->assertText('Upgrade status test error ' . \Drupal::VERSION);
    $this->assertText('CUSTOM PROJECTS');
    $this->assertNoText('CONTRIBUTED PROJECTS');
    $this->assertText('2 errors found. 2 warnings found.');
    $this->assertText('Syntax error, unexpected T_STRING on line 3');

    // Run partial export of multiple projects.
    $edit = [
      'manual[data][list][upgrade_status_test_error]' => TRUE,
      'relax[data][list][upgrade_status_test_no_error]' => TRUE,
      'collaborate[data][list][upgrade_status_test_contrib_error]' => TRUE,
    ];
    $expected = [
      'Export selected as HTML' => ['Contributed projects', 'Custom projects'],
      'Export selected as text' => ['CONTRIBUTED PROJECTS', 'CUSTOM PROJECTS'],
    ];
    foreach ($expected as $button => $assert) {
      $this->drupalPostForm('admin/reports/upgrade-status', $edit, $button);
      $this->assertText($assert[0]);
      $this->assertText($assert[1]);
      $this->assertText('Upgrade status test contrib error ' . \Drupal::VERSION);
      $this->assertText('Upgrade status test no error ' . \Drupal::VERSION);
      $this->assertText('Upgrade status test error ' . \Drupal::VERSION);
      $this->assertNoText('Upgrade status test root module');
      $this->assertNoText('Upgrade status test contrib no error');
      $this->assertText('2 errors found. 2 warnings found.');
      $this->assertText('Syntax error, unexpected T_STRING on line 3');
    }
  }
}
