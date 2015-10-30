<?php

/**
 * @file
 * Contains Drupal\amazing_pager\Controller\DefaultController.
 */

namespace Drupal\amazing_pager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\views\Views;

class DefaultController extends ControllerBase {

  /**
   * Hello.
   *
   * @return string
   *   Return serialized html
   */
  public function items($name) {

    // This works fine
    $view = Views::getView($name);

    if($view) {

      // Get offset from query
      $offset = (int)($_GET['offset']) ? (int)($_GET['offset']) : 0;

      // Append offset to selected view
      $view->setOffset($offset);

      // Get args
      $args = (int)($_GET['args']) ? (int)($_GET['args']) : null;

      // Apply arguments
      if($args) {
        $view->setArguments(array($args));
      }

      // Get the new view with new offset
      $new = $view->preview(NULL, array());

      // Settings from our pager
      $pager_options = $new['#rows'][0]['#view']->pager->options;

      // Amount of items per page
      $items_per_page =  $pager_options['items_per_page'];

      // The amount of items we got back
      $items_length = count($new['#rows'][0]['#view']->result);

      // Animate load
      $animate_load = ($pager_options['output']['animate'] == 1 ? true : false );

      if($animate_load) {
        $items = array();

        foreach($new['#rows'][0]['#rows'] as $key => $row) {
          $rendered_row = \Drupal::service('renderer')->render($row, FALSE);
          $items[$key] = $rendered_row->__toString();
        }
      }
      else {
        // Render all the items to serliazed string
        $rendered_items = \Drupal::service('renderer')->render($new['#rows'][0]['#rows'], FALSE);
        $items = $rendered_items->__toString();
      }

      if($items == "") {
        return new JsonResponse(array('items' => '', 'empty' => true));
      } else {
        // Let client side know that we won't have any items next request
        return new JsonResponse(array('items' => $items, 'empty' => $items_per_page > $items_length));
      }
    }

    else {
      return new JsonResponse(NULL);
    }
  }
}