<?php

namespace Drupal\iied_slider\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render .. something
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "iied_slider",
 *   title = @Translation("IIED Slider"),
 *   help = @Translation("Render a thing... "),
 *   theme = "views_view_iied_slider",
 *   display_types = { "normal" }
 * )
 */
class Slider extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['path'] = array('default' => 'iied_silder');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['containerWidth'] = array(
      '#type' => 'number',
      '#title' => t('Container width.'),
      '#default_value' => (isset($this->options['containerWidth'])) ? $this->options['containerWidth'] : '700',
      '#description' => t('The width of the container.'),
    );
    $form['containerHeight'] = array(
      '#type' => 'number',
      '#title' => t('Container height.'),
      '#default_value' => (isset($this->options['containerHeight'])) ? $this->options['containerHeight'] : '450',
      '#description' => t('The height of the container.'),
    );
    $form['loop'] = array(
      '#type' => 'textfield',
      '#title' => t('Loop'),
      '#default_value' => (isset($this->options['loop'])) ? $this->options['loop'] : 'true',
      '#description' => t('Whether to loop or not'),
    );
    $form['breakpoint1'] = array(
      '#type' => 'number',
      '#title' => t('breakpoint 1.'),
      '#default_value' => (isset($this->options['breakpoint1'])) ? $this->options['breakpoint1'] : '640',
      '#description' => t('The breakpoint width, in pixels.'),
    );
    $form['breakpoint2'] = array(
      '#type' => 'number',
      '#title' => t('breakpoint 2.'),
      '#default_value' => (isset($this->options['breakpoint2'])) ? $this->options['breakpoint2'] : '768',
      '#description' => t('The breakpoint width, in pixels.'),
    );
    $form['breakpoint3'] = array(
      '#type' => 'number',
      '#title' => t('breakpoint 3.'),
      '#default_value' => (isset($this->options['breakpoint3'])) ? $this->options['breakpoint3'] : '1024',
      '#description' => t('The breakpoint width, in pixels.'),
    );
    $form['slidesPerView1'] = array(
      '#type' => 'number',
      '#title' => t('Slides per view at breakpoint 1.'),
      '#default_value' => (isset($this->options['slidesPerView1'])) ? $this->options['slidesPerView1'] : '1',
      '#description' => t('The number of slides visisble inititally.'),
    );
    $form['spaceBetween1'] = array(
      '#type' => 'number',
      '#title' => t('Space between slides at breakpoint 1.'),
      '#default_value' => (isset($this->options['spaceBetween1'])) ? $this->options['spaceBetween1'] : '0',
      '#description' => t('The space between slides.'),
    );
    // slidesPerView.
    $form['slidesPerView2'] = array(
      '#type' => 'number',
      '#title' => t('Slides per view at breakpoint 2.'),
      '#default_value' => (isset($this->options['slidesPerView2'])) ? $this->options['slidesPerView2'] : '2',
      '#description' => t('The number of slides visisble inititally.'),
    );
    $form['spaceBetween2'] = array(
      '#type' => 'number',
      '#title' => t('Space between slides at breakpoint 2'),
      '#default_value' => (isset($this->options['spaceBetween2'])) ? $this->options['spaceBetween2'] : '0',
      '#description' => t('The space between slides.'),
    );
    // slidesPerView.
    $form['slidesPerView3'] = array(
      '#type' => 'number',
      '#title' => t('Slides per view at breakpoint 3.'),
      '#default_value' => (isset($this->options['slidesPerView3'])) ? $this->options['slidesPerView3'] : '3',
      '#description' => t('The number of slides visisble inititally.'),
    );
    $form['spaceBetween3'] = array(
      '#type' => 'number',
      '#title' => t('Space between slides at breakpoint 3.'),
      '#default_value' => (isset($this->options['spaceBetween3'])) ? $this->options['spaceBetween3'] : '0',
      '#description' => t('The space between slides.'),
    );

  }
}
