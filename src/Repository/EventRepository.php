<?php

namespace Drupal\timelabciviapi\Repository;

class EventRepository {
  public function __construct() {
    \Drupal::service('civicrm')->initialize();
  }

  public function findEvent(string $title, string $keywords, string $date): ?array {
    if ($title !== '') {
      $keywordList = [$title];
    }
    else {
      $keywordList = explode(',', $keywords);
    }

    foreach ($keywordList as $keyword) {
      $trimmedKeyword = trim($keyword);
      if (empty($trimmedKeyword)) {
        continue;
      }

      // regexp fails with API v4, so we use SQL
      $startDate = "$date 00:00:00";
      $endDate = "$date 23:59:59";

      $sql = "
        select
          e.id,
          e.start_date,
          e.title,
          b.titel_evenement_externe_naam__172 external_title
        from
          civicrm_event e
        left outer join
          civicrm_value_bijkomende_info_6 b on e.id = b.entity_id
        where
          e.start_date >= %1
        and
          e.start_date <= %2
        and
          (e.title regexp %3 or b.titel_evenement_externe_naam__172 regexp %3)
      ";
      $sqlParams = [
        1 => [$startDate, 'String'],
        2 => [$endDate, 'String'],
        3 => ['[[:<:]]' . $trimmedKeyword . '[[:>:]]', 'String'],
      ];
      $dao = \CRM_Core_DAO::executeQuery($sql, $sqlParams);

      if ($dao->fetch()) {
        return [
          'id' => $dao->id,
          'start_date' => $dao->start_date,
          'title' => $dao->title,
          'external_title' => $dao->external_title,
        ];
      }
    }

    return null;
  }

  public function createParticipant(array $params): ?int {
    $contactId = (int)$params['contact_id'];
    $eventId = (int)$params['event_id'];

    if ($contactId === 0 || $eventId === 0) {
      return NULL;
    }

    $participant = \Civi\Api4\Participant::get(FALSE)
      ->addWhere('contact_id', '=', $contactId)
      ->addWhere('event_id', '=', $eventId)
      ->execute()
      ->first();

    if ($participant) {
      // already registered
      return $participant['id'];
    }

    /***************88
     * TODO
     *
     * check if contactId = person
     * check if event is in the future
     * check if event is open for registration
     * check if event is not full
     * What with registration date? Submit date or via parameter?
     */

    $participant = \Civi\Api4\Participant::create(FALSE)
      ->addValue('contact_id', $contactId)
      ->addValue('event_id', $eventId)
      ->addValue('status_id', 8) // wachtrij
      ->addValue('source', 'LedenAIssistent event registratie')
      ->addValue('register_date', date('Y-m-d H:i:s'))
      ->execute()
      ->first();

    if (!empty($params['note'])) {
      \Civi\Api4\Note::create(FALSE)
        ->addValue('entity_table', 'civicrm_participant')
        ->addValue('entity_id', $participant['id'])
        ->addValue('note', $params['note'])
        ->addValue('contact_id', $contactId)
        ->execute();
    }

    return $participant['id'];
  }
}
