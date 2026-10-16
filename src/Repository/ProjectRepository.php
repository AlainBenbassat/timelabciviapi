<?php

namespace Drupal\timelabciviapi\Repository;

use CRM_Utils_Rule;

class ProjectRepository {
  public function __construct() {
    \Drupal::service('civicrm')->initialize();
  }

  public function findProjectById(int $id): ?array {
    $projectApi = $this->projectQuery()
      ->addWhere('id', '=', $id)
      ->execute()
      ->first();

    if (empty($projectApi)) {
      return NULL;
    }

    $project = $this->mapProject($projectApi);
    $project['members'] = $this->getProjectMembers($project['id']);
    return $project;
  }

  public function findProjects(): array {
    $projectsApi = $this->projectQuery()
      ->addOrderBy('sort_name', 'ASC')
      ->execute();

    return array_map([$this, 'mapProject'], $projectsApi->getArrayCopy());
  }

  private function projectQuery(): \Civi\Api4\Generic\DAOGetAction {
    return \Civi\Api4\Contact::get(FALSE)
      ->addSelect('id', 'display_name', 'label_projects.Labels_projects')
      ->addWhere('contact_sub_type', '=', 'Project_timelab');
  }

  private function mapProject(array $project): array {
    return [
      'id' => $project['id'],
      'name' => $project['display_name'],
      'stream' => $project['label_projects.Labels_projects'],
      'start_date' => '2000-01-01',
      'end_date' => '3000-01-01',
    ];
  }

  private function getProjectMembers(int $id) {
    $projectMembers = [];

    $relationshipsApi = \Civi\Api4\Relationship::get(FALSE)
      ->addSelect('contact_id_a', 'start_date', 'end_date', 'is_active')
      ->addWhere('contact_id_b', '=', $id)
      ->addWhere('contact_id_a.is_deleted', '=', FALSE)
      ->execute();
    foreach ($relationshipsApi as $relationship) {
      $projectMembers[] = [
        'contact_id' => $relationship['contact_id_a'],
        'is_active' => $this->isActiveProjectMember($relationship['is_active'], $relationship['end_date']),
      ];
    }

    return $projectMembers;
  }

  private function isActiveProjectMember(int $isActive, ?string $endDate): bool {
    if ($isActive == 1) {
      return TRUE;
    }

    if (!empty($endDate) && strtotime($endDate) > time()) {
      return TRUE;
    }

    return FALSE;
  }

  public function findStreams(): array {
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
