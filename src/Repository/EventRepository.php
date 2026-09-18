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
          e.title
        from
          civicrm_event e
        where
          e.start_date >= %1
        and
          e.start_date <= %2
        and
          e.title regexp %3
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
          'title' => $dao->title
        ];
      }
    }

    return NULL;
  }

}
