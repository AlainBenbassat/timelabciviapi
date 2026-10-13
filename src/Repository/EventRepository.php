<?php

namespace Drupal\timelabciviapi\Repository;

class EventRepository {
  public function __construct() {
    \Drupal::service('civicrm')->initialize();
  }

  public function findEvent(string $dateFrom, string $dateTo): ?array {
    if (empty($dateFrom)) {
      $dateFrom = date('Y-m-d 00:00:00');
    }
    elseif (!$this->isValidDate($dateFrom)) {
      return NULL;
    }

    if (empty($dateTo)) {
      $dateTo = '3000-12-31 23:59:59';
    }
    elseif (!$this->isValidDate($dateTo)) {
      return NULL;
    }

    $events = [];

    $eventsApi = \Civi\Api4\Event::get(FALSE)
      ->addSelect('id', 'title', 'summary', 'description')
      ->addWhere('is_template', '=', FALSE)
      ->addWhere('is_public', '=', TRUE)
      ->addWhere('start_date', '>=', $dateFrom)
      ->addWhere('start_date', '<=', $dateTo)
      ->execute();
    foreach ($eventsApi as $event) {
      $events[] = [
        'event_id' => $event['id'],
        'start_date' => $event['start_date'],
        'title' => $event['title'],
        'summary' => $event['summary'],
        'description' => $event['description'],
      ];
    }

    return $events;
  }

  private function isValidDate(string $date) {
    $dt = DateTime::createFromFormat("Y-m-d", $date);
    return $dt !== false && $dt::getLastErrors() === false;
  }

}
