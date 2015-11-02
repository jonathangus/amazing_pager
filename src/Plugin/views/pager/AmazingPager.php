<?php

/**
 * @file
 * Contains Drupal\amazing_pager\Plugin\views\pager\AmazingPager.
 */

namespace Drupal\amazing_pager\Plugin\views\pager;

use Drupal\views\Plugin\views\pager\SqlBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Plugin to handle infinite scrolling and animations.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *  id = "amazing_pager",
 *  title = @Translation("Amazing pager"),
 *  short_title = @Translation("Infinite"),
 *  help = @Translation("A views plugin which provides infinte scroll and animations."),
 *  theme = "amazing_pager"
 * )
 */
class AmazingPager extends SqlBase {
  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['vis'] = array(
      'contains' => array(
        'fetch_option' => array(
          'default' => 'infinite_scroll'
        ),
        'manual_load' => array(
          'default' => FALSE,
        ),
        'manual_load_text' => array(
          'default' => $this->t('Load More'),
        ),
        'loading_text' => array(
          'default' => $this->t('Loading...'),
        ),
        'row_class' => array(
          'default' => $this->t('Class'),
        ),

      ),
    );

    $options['output'] = array(
      'contains' => array(
        'animate' => array(
          'default' => TRUE,
        ),
        'animate_effect' => array(
          'default' => TRUE,
        ),
      ),
    );

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    unset($form['tags']);
//    unset($form['expose']);
    unset($form['total_pages']);
    $form['tags']['#description'] = $this->t('While these links are not visible during infinite scrolling, they are used by search engines and browsers without JavaScript.');
    $form['options'] = array(
      '#title' => $this->t('Infinite Scroll Options'),
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#input' => TRUE,
      '#weight' => -100,
    );

    $form['options']['type'] = array(
      '#title' => $this->t('Fetch type'),
      '#type' => 'select',
      '#options' => array(
        'infinite_scroll' => t('Infinite scroll'),
        'manual_load' => t('Manual load'),
        'click_scroll' => t('Click scroll'),
      ),
      '#default_value' => $this->options['options']['type'],
      '#description' => $this->t('Choose what kind of fetch handler you want.')
    );

    $form['options']['loading_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Loading Text'),
      '#description' => $this->t('The text displayed to the user when the next page is loading.'),
      '#default_value' => $this->options['options']['loading_text'],
    );

    $form['options']['load_more_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Click to Load Button'),
      '#description' => $this->t('The text inside the manually load button.'),
      '#default_value' => $this->options['options']['load_more_text'],
      '#states' => array(
        'invisible' => array(
          ':input[name="pager_options[options][type]"]' => array('value' => 'infinite_scroll'),
        ),
      ),
    );

    $form['element_settings'] = array(
      '#title' => $this->t('Element settings'),
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#input' => TRUE,
      '#weight' => -100,
    );

    $form['element_settings']['row_class'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Row class'),
      '#description' => $this->t('Change the row class from the default one (views-row).'),
      '#default_value' => $this->options['element_settings']['row_class'],
    );

    $form['element_settings']['loader_class'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Container class'),
      '#description' => $this->t('Change the loader class from the default one (js-AmazingPager-spinner).'),
      '#default_value' => $this->options['element_settings']['loader_class'],
    );

    $form['element_settings']['trigger_class'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Trigger class'),
      '#description' => $this->t('Change the trigger class from the default one (js-AmazingPager-trigger).'),
      '#default_value' => $this->options['element_settings']['trigger_class'],
    );

    $form['output'] = array(
      '#title' => $this->t('Infinite Scroll Output Options'),
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#input' => TRUE,
      '#weight' => -100,
      'animate' => array(
        '#type' => 'checkbox',
        '#title' => $this->t('Animate'),
        '#description' => $this->t('Animate the output and make each row be displayed at diffrent times.'),
        '#default_value' => $this->options['output']['animate'],
      ),
      'animate_effect' => array(
        '#type' => 'select',
        '#title' => $this->t('Animate effect'),
        '#description' => $this->t('Choose effect on how the items will be displayed.'),
        '#options' => array(
          'fadeIn' => t('fadeIn'),
          'fadeInUp' => t('fadeInUp'),
          'bounceIn' => t('bounceIn'),
          'flipInX' => t('flipInX'),
        ),
        '#default_value' => $this->options['output']['animate_effect'],
        '#states' => array(
          'visible' => array(
            ':input[name="pager_options[output][animate]"]' => array('checked' => TRUE),
          ),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    if ($this->options['vis']['manual_load']) {
      return \Drupal::translation()->formatPlural($this->options['items_per_page'], 'Click to load, @count item', 'Click to load, @count items', array('@count' => $this->options['items_per_page']));
    }
    return \Drupal::translation()->formatPlural($this->options['items_per_page'], 'Infinite scroll, @count item', 'Infinite scroll, @count items', array('@count' => $this->options['items_per_page']));
  }

  /**
   * {@inheritdoc}
   */
  public function render($input) {
    $options = $this->options;
    $options['total_result'] = count($this->view->result);
    return array(
      '#theme' => $this->themeFunctions(),
      '#options' => $options,
      '#attached' => array(
        'drupalSettings' => array(
          'amazing_pager' => $this->buildJsSettings(),
        ),
        'library' => array('amazing_pager/amazing-pager'),
      ),
      '#element' => $this->options['id'],
      '#parameters' => $input,
    );

  }

  /**
   * Send javascript variables.
   *
   * @return array
   *   An array of variables to be sent to the browser.
   */
  protected function buildJsSettings() {
    $view_settings = array();
    $view_settings['view'] = array(
      'name' => $this->view->storage->id(),
      'args' => $this->view->args,
    );
    $view_settings['settings'] = array_filter($this->options['element_settings']);

    $view_settings['options'] = array(
      'type' => $this->options['options']['type'],
      'manualText' => $this->options['vis']['load_more_text'],
      'loadText' => $this->options['vis']['loading_text'],
    );

    $animate = $this->options['output']['animate'];
    if($animate) {
      $view_settings['animate'] = array(
        'animateEffect' => $this->options['output']['animate_effect'],
      );
    }

    return $view_settings;
  }
}
