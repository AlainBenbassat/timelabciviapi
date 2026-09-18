<?php

namespace Drupal\timelabciviapi\Repository;

use CRM_Utils_Rule;

class ProjectRepository {
  public function __construct() {
    \Drupal::service('civicrm')->initialize();
  }

  public function findProjectById(int $id): ?array {
    $projectApi = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('id', 'display_name')
      ->addWhere('id', '=', $id)
      ->addWhere('contact_sub_type', '=', 'Project_timelab')
      ->execute()
      ->first();

    if (empty($projectApi)) {
      return NULL;
    }

    $project = [
      'id' => $projectApi['id'],
      'name' => $projectApi['display_name'],
    ];

    return $project;
  }

  public function findProjects(): ?array {
    $projects = [];

    $projectsApi = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('id', 'display_name')
      ->addWhere('contact_sub_type', '=', 'Project_timelab')
      ->addOrderBy('sort_name', 'ASC')
      ->execute();

    foreach ($projectsApi as $project) {
      $projects[] = [
        'id' => $project['id'],
        'name' => $project['display_name'],
      ];
    }

    return $projects;
  }
}
