<?php

namespace Drupal\timelabciviapi\Repository;

use CRM_Utils_Rule;

class ProjectRepository {
  public function __construct() {
    \Drupal::service('civicrm')->initialize();
  }

  public function findProjectById(int $id): ?array {
    $projectApi = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('id', 'display_name', 'label_projects.Labels_projects')
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
      'stream' => $projectApi['label_projects.Labels_projects'],
    ];

    return $project;
  }

  public function findProjects(): ?array {
    $projects = [];

    $projectsApi = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('id', 'display_name', 'label_projects.Labels_projects')
      ->addWhere('contact_sub_type', '=', 'Project_timelab')
      ->addOrderBy('sort_name', 'ASC')
      ->execute();

    foreach ($projectsApi as $project) {
      $projects[] = [
        'id' => $project['id'],
        'name' => $project['display_name'],
        'stream' => $project['label_projects.Labels_projects'],
      ];
    }

    return $projects;
  }

  public function findStreams(): ?array {
    $streams = [];

    $optionValues = \Civi\Api4\OptionValue::get(FALSE)
      ->addSelect('value', 'label')
      ->addWhere('option_group_id', '=', 132)
      ->execute();
    foreach ($optionValues as $optionValue) {
      $streams[] = [
        'id' => $optionValue['value'],
        'name' => $optionValue['label'],
      ];
    }

    return $streams;
  }
}
