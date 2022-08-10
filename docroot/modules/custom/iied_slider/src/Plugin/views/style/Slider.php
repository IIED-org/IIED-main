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
    $form['breakpoint_sm'] = array(
      '#type' => 'number',
      '#title' => t('breakpoint sm.'),
      '#default_value' => (isset($this->options['breakpoint_sm'])) ? $this->options['breakpoint_sm'] : '640',
      '#description' => t('The breakpoint width, in pixels.'),
    );
    $form['breakpoint_md'] = array(
      '#type' => 'number',
      '#title' => t('breakpoint md.'),
      '#default_value' => (isset($this->options['breakpoint_md'])) ? $this->options['breakpoint_md'] : '768',
      '#description' => t('The breakpoint width, in pixels.'),
    );
    $form['breakpoint_lg'] = array(
      '#type' => 'number',
      '#title' => t('breakpoint lg.'),
      '#default_value' => (isset($this->options['breakpoint_lg'])) ? $this->options['breakpoint_lg'] : '1024',
      '#description' => t('The breakpoint width, in pixels.'),
    );
    $form['breakpoint_xl'] = array(
      '#type' => 'number',
      '#title' => t('breakpoint xl.'),
      '#default_value' => (isset($this->options['breakpoint_xl'])) ? $this->options['breakpoint_xl'] : '1280',
      '#description' => t('The breakpoint width, in pixels.'),
    );
    $form['breakpoint_2xl'] = array(
      '#type' => 'number',
      '#title' => t('breakpoint 2xl.'),
      '#default_value' => (isset($this->options['breakpoint_2xl'])) ? $this->options['breakpoint_2xl'] : '1536',
      '#description' => t('The breakpoint width, in pixels.'),
    );
    $form['slidesPerView_sm'] = array(
      '#type' => 'number',
      '#title' => t('Slides per view at breakpoint sm.'),
      '#default_value' => (isset($this->options['slidesPerView_sm'])) ? $this->options['slidesPerView_sm'] : '1',
      '#description' => t('The number of slides visisble inititally.'),
    );
    $form['spaceBetween_sm'] = array(
      '#type' => 'number',
      '#title' => t('Space between slides at breakpoint sm.'),
      '#default_value' => (isset($this->options['spaceBetween_sm'])) ? $this->options['spaceBetween_sm'] : '0',
      '#description' => t('The space between slides.'),
    );
    // slidesPerView.
    $form['slidesPerView_md'] = array(
      '#type' => 'number',
      '#title' => t('Slides per view at breakpoint md.'),
      '#default_value' => (isset($this->options['slidesPerView_md'])) ? $this->options['slidesPerView_md'] : '2',
      '#description' => t('The number of slides visisble inititally.'),
    );
    $form['spaceBetween_md'] = array(
      '#type' => 'number',
      '#title' => t('Space between slides at breakpoint md'),
      '#default_value' => (isset($this->options['spaceBetween_md'])) ? $this->options['spaceBetween_md'] : '0',
      '#description' => t('The space between slides.'),
    );
    // slidesPerView.
    $form['slidesPerView_lg'] = array(
      '#type' => 'number',
      '#title' => t('Slides per view at breakpoint lg.'),
      '#default_value' => (isset($this->options['slidesPerView_lg'])) ? $this->options['slidesPerView_lg'] : '3',
      '#description' => t('The number of slides visisble inititally.'),
    );
    $form['spaceBetween_lg'] = array(
      '#type' => 'number',
      '#title' => t('Space between slides at breakpoint lg.'),
      '#default_value' => (isset($this->options['spaceBetween_lg'])) ? $this->options['spaceBetween_lg'] : '0',
      '#description' => t('The space between slides.'),
    );
    // slidesPerView.
    $form['slidesPerView_xl'] = array(
      '#type' => 'number',
      '#title' => t('Slides per view at breakpoint xl.'),
      '#default_value' => (isset($this->options['slidesPerView_xl'])) ? $this->options['slidesPerView_xl'] : '3',
      '#description' => t('The number of slides visisble inititally.'),
    );
    $form['spaceBetween_xl'] = array(
      '#type' => 'number',
      '#title' => t('Space between slides at breakpoint xl.'),
      '#default_value' => (isset($this->options['spaceBetween_xl'])) ? $this->options['spaceBetween_xl'] : '0',
      '#description' => t('The space between slides.'),
    );
    // slidesPerView.
    $form['slidesPerView_2xl'] = array(
      '#type' => 'number',
      '#title' => t('Slides per view at breakpoint 2xl.'),
      '#default_value' => (isset($this->options['slidesPerView_2xl'])) ? $this->options['slidesPerView_2xl'] : '3',
      '#description' => t('The number of slides visisble inititally.'),
    );
    $form['spaceBetween_2xl'] = array(
      '#type' => 'number',
      '#title' => t('Space between slides at breakpoint 2xl.'),
      '#default_value' => (isset($this->options['spaceBetween_2xl'])) ? $this->options['spaceBetween_2xl'] : '0',
      '#description' => t('The space between slides.'),
    );
  }
}
