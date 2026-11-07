<?php

namespace Drupal\timelabciviapi\Repository;

use CRM_Core_DAO;

class GroupRepository {
  private const TIMELAB_CONTACT_ID = 2402;
  private const REL_IS_MEDEWERKER_VAN = 62;
  private const REL_IS_REDIDENT_VAN = 37;
  private const REL_IS_HERO_VAN = 63;
  private const REL_IS_BESTUURSLID_VAN = 39;
  private const REL_IS_LID_VAN_AV_VAN = 38;
  private const REL_IS_TEAM_LID_VAN = 4;

  public function __construct() {
    \Drupal::service('civicrm')->initialize();
  }

  public function findGroups(): array {
    return [
      [
        'id' => 1,
        'name' => 'Team',
        'members' => $this->getTeamMembers(),
      ],
      [
        'id' => 2,
        'name' => 'Residenten',
        'members' => $this->getResidentMembers(),
      ],
      [
        'id' => 3,
        'name' => 'Heroes',
        'members' => $this->getHeroesMembers(),
      ],
      [
        'id' => 4,
        'name' => 'Family',
        'members' => $this->getFamilyMembers(),
      ],
      [
        'id' => 5,
        'name' => 'Village',
        'members' => $this->getVillageMembers(),
      ],
      [
        'id' => 6,
        'name' => 'Bestuur',
        'members' => $this->getBestuurMembers(),
      ],
      [
        'id' => 7,
        'name' => 'Algemene Vergadering',
        'members' => $this->getAlgemeneVergaderingMembers(),
      ],
    ];
  }

  private function getRelationshipContacts(int $relationshipTypeId): array {
    $members = [];

    $relationships = \Civi\Api4\Relationship::get(FALSE)
      ->addSelect('contact_id_a', 'description')
      ->addWhere('contact_id_b', '=', self::TIMELAB_CONTACT_ID)
      ->addWhere('relationship_type_id', '=', $relationshipTypeId)
      ->addWhere('is_active', '=', TRUE)
      ->execute();
    foreach ($relationships as $relationship) {
      $members[] = [
        'id' => $relationship['contact_id_a'],
        'role' => $relationship['description'],
      ];
    }

    return $members;
  }

  public function getTeamMembers(): array {
    return $this->getRelationshipContacts(self::REL_IS_MEDEWERKER_VAN);
  }

  public function getResidentMembers(): array {
    return $this->getRelationshipContacts(self::REL_IS_REDIDENT_VAN);
  }

  public function getHeroesMembers(): array {
    return $this->getRelationshipContacts(self::REL_IS_HERO_VAN);
  }

  public function getBestuurMembers(): array {
    return $this->getRelationshipContacts(self::REL_IS_BESTUURSLID_VAN);
  }

  public function getAlgemeneVergaderingMembers(): array {
    return $this->getRelationshipContacts(self::REL_IS_LID_VAN_AV_VAN);
  }

  public function getFamilyMembers(): array {
    $members = [];

    // Type contact individual, ID, foto, naam, voornaam - selectie relationship is team van tot actief project (actief project is einddatum niet verstreken)
    $sql = "
      select
        distinct member.id
      from
        civicrm_contact proj
      inner join
        civicrm_relationship r on r.contact_id_b = proj.id and r.relationship_type_id = 4 and r.is_active = 1
      inner join
        civicrm_contact member on member.id = r.contact_id_a
      left outer join
        civicrm_value_project_detai_62 pd on pd.entity_id = proj.id
      where
        proj.contact_type = 'Organization'
      and
        proj.contact_sub_type like '%Project_timelab%'
      and
        proj.is_deleted = 0
      and
        ifnull(pd.start_date_136, '1900-01-01') <= NOW() and ifnull(pd.end_date_137, '3000-01-01') >= NOW()
      and
        member.contact_type = 'Individual'
      and
        member.is_deleted = 0
    ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $members[] = $dao->id;
    }

    return $members;
  }

  public function getVillageMembers(): array {
    // Type contact organisation, ID, foto, organisation name - selectie relationship 'Partner' tot actief project (actief project is einddatum niet verstreken)
    $c = new ContactRepository();
    return $c->findActivePartners();
  }
}
