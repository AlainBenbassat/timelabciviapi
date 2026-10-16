<?php

namespace Drupal\timelabciviapi\Repository;

use CRM_Core_DAO;

class EventRepository {
  public function __construct() {
    \Drupal::service('civicrm')->initialize();
  }

  public function findEvent(string $dateFrom, string $dateTo): ?array {
    if (empty($dateFrom)) {
      $dateFrom = date('Y-m-d 00:00:00');
    }
    elseif (!$this->isValidYmdDate($dateFrom)) {
      return NULL;
    }

    if (empty($dateTo)) {
      $dateTo = '3000-12-31 23:59:59';
    }
    elseif (!$this->isValidYmdDate($dateTo)) {
      return NULL;
    }

    $events = [];

    $eventsApi = \Civi\Api4\Event::get(FALSE)
      ->addSelect('id', 'title', 'summary', 'description', 'start_date', 'end_date', 'is_public', 'Img.Project')
      ->addWhere('is_template', '=', FALSE)
      ->addWhere('start_date', '<=', $dateTo)
      ->addWhere('end_date', '>=', $dateFrom)
      ->addOrderBy('start_date', 'ASC')
      ->execute();
    foreach ($eventsApi as $event) {
      $events[] = [
        'event_id' => $event['id'],
        'start_date' => $event['start_date'],
        'end_date' => $event['end_date'],
        'title' => $event['title'],
        'is_public' => $event['is_public'],
        'summary' => $event['summary'] ?: $event['description'],
        'project_id' => $event['Img.Project'],
        'prices' => $this->getEventPrices($event['id']),
      ];
    }

    return $events;
  }

  public function findEventById(int $id): ?array {
    $eventsApi = \Civi\Api4\Event::get(FALSE)
      ->addSelect('id', 'title', 'summary', 'description', 'start_date', 'end_date', 'is_public', 'Img.Project')
      ->addWhere('is_template', '=', FALSE)
      ->addWhere('id', '=', $id)
      ->execute()
      ->first();

    if (empty($eventsApi)) {
      return NULL;
    }

    return [
      'event_id' => $eventsApi['id'],
      'start_date' => $eventsApi['start_date'],
      'end_date' => $eventsApi['end_date'],
      'title' => $eventsApi['title'],
      'is_public' => $eventsApi['is_public'],
      'summary' => $eventsApi['summary'] ?: $eventsApi['description'],
      'project_id' => $eventsApi['Img.Project'],
      'prices' => $this->getEventPrices($eventsApi['id']),
    ];
  }

  public function findEventParticipants(int $eventId): array {
    $participants = [];

    $participantApi = \Civi\Api4\Participant::get(FALSE)
      ->addSelect('contact_id')
      ->addWhere('event_id', '=', $eventId)
      ->addWhere('status_id', '=', 1)
      ->execute();
    foreach ($participantApi as $participant) {
      $participants[] = $participant['contact_id'];
    }

    return $participants;
  }

  private function isValidYmdDate(string $date): bool {
    $dt = \DateTime::createFromFormat("Y-m-d", $date);
    return $dt !== false && $dt::getLastErrors() === false;
  }

  private function getEventPrices(int $eventId): array {
    $prices = [];

    $sql = "
      select
        pfv.id price_field_id,
        ft.name price_field_type,
        pfv.label price_field_label,
        pfv.amount price_field_price
      from
        civicrm_price_set_entity pse
      inner join
        civicrm_price_set ps on ps.id = pse.price_set_id
      inner join
        civicrm_price_field pf on pf.price_set_id = ps.id
      inner join
        civicrm_price_field_value pfv on pfv.price_field_id = pf.id
      inner join
        civicrm_financial_type ft on ft.id = pfv.financial_type_id
      where
        pse.entity_table = 'civicrm_event'
      and
        pse.entity_id = $eventId
      and
        ps.is_active = 1
      and
        pf.is_active  = 1
      and
        pfv.is_active  = 1
      order by
        pf.weight, pfv.weight
    ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $prices[] = [
        'price_field_id' => $dao->price_field_id,
        'price_field_type' => $dao->price_field_type,
        'price_field_label' => $dao->price_field_label,
        'price_field_price' => $dao->price_field_price,
      ];
    }

    return $prices;
  }

}
