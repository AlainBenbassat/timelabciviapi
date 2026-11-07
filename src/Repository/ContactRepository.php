<?php

namespace Drupal\timelabciviapi\Repository;

use CRM_Core_DAO;
use CRM_Utils_Rule;

class ContactRepository {
  public function __construct() {
    \Drupal::service('civicrm')->initialize();
  }

  public function findContactByEmail(string $email): ?array {
    // make sure the email is valid
    if (empty($email) || !\CRM_Utils_Rule::email($email)) {
      return NULL;
    }

    $emailApi = \Civi\Api4\Email::get(FALSE)
      ->addSelect(
        'contact_id',
        'contact_id.first_name',
        'contact_id.last_name',
        'contact_id.image_URL',
        'contact_id.GDPR.May_be_shown_on_site_',
        'contact_id.GDPR.What_is_publicly_visible_',
        'contact_id.GDPR.Where_is_it_shown_',
        'contact_id.GDPR.Display_name_as',
        'contact_id.public.BIO',
        'contact_id.public.Role'
      )
      ->addWhere('contact_id.is_deleted', '=', FALSE)
      ->addWhere('email', '=', $email)
      ->execute()
      ->first();

    if (empty($emailApi)) {
      return NULL;
    }

    $contact = [
      'contact_id' => $emailApi['contact_id'],
      'first_name' => $emailApi['contact_id.first_name'],
      'last_name' => $emailApi['contact_id.last_name'],
      'picture' => $emailApi['contact_id.image_URL'],
      'email' => $email,
      'telephone' => $this->getPhone($emailApi['contact_id']),
      'linkedin' => $this->getLinkedin($emailApi['contact_id']),
      'instagram' => $this->getInstagram($emailApi['contact_id']),
      'member_since' => $this->getMemberSince($emailApi['contact_id']),
      'role' => $emailApi['contact_id.public.Role'],
      'bio' => $emailApi['contact_id.public.Bio'],
      'gdpr' => [
        'show_on_site' => $emailApi['contact_id.GDPR.May_be_shown_on_site_'],
        'visible_data' => $emailApi['contact_id.GDPR.What_is_publicly_visible_'],
        'contexts' => $emailApi['contact_id.GDPR.Where_is_it_shown_'],
        'visible_name' => $emailApi['contact_id.GDPR.Display_name_as'],
      ],
    ];

    return $contact;
  }

  public function findContactById(int $id): ?array {
    $contactApi = \Civi\Api4\Contact::get(FALSE)
      ->addSelect(
        'id',
        'first_name',
        'last_name',
        'image_URL',
        'email_primary.email',
        'phone_primary.phone',
        'GDPR.May_be_shown_on_site_',
        'GDPR.What_is_publicly_visible_',
        'GDPR.Where_is_it_shown_',
        'GDPR.Display_name_as',
        'public.BIO',
        'public.Role'
      )
      ->addWhere('id', '=', $id)
      ->execute()
      ->first();

    if (empty($contactApi)) {
      return NULL;
    }

    $contact = [
      'contact_id' => $contactApi['id'],
      'first_name' => $contactApi['first_name'],
      'last_name' => $contactApi['last_name'],
      'picture' => $contactApi['image_URL'],
      'email' => $contactApi['email_primary.email'],
      'telephone' => $contactApi['phone_primary.phone'],
      'linkedin' => $this->getLinkedin($contactApi['id']),
      'instagram' => $this->getInstagram($contactApi['id']),
      'member_since' => $this->getMemberSince($contactApi['id']),
      'role' => $contactApi['public.Role'],
      'bio' => $contactApi['public.BIO'],
      'gdpr' => [
        'show_on_site' => $contactApi['GDPR.May_be_shown_on_site_'],
        'visible_data' => $contactApi['GDPR.What_is_publicly_visible_'],
        'contexts' => $contactApi['GDPR.Where_is_it_shown_'],
        'visible_name' => $contactApi['GDPR.Display_name_as'],
      ],
    ];

    return $contact;
  }

  public function update(int $id, array $contact): void {
    // updatable fields: role, bio, linkedin, instagram
    $contactFieldsToUpdate = [];

    if (isset($contact['role'])) {
      $contactFieldsToUpdate['public.Role'] = trim($contact['role']);
    }
    if (isset($contact['bio'])) {
      $contactFieldsToUpdate['public.BIO'] = trim($contact['bio']);
    }
    if (isset($contact['gdpr']['show_on_site'])) {
      $contactFieldsToUpdate['GDPR.May_be_shown_on_site_'] = $contact['gdpr']['show_on_site'];
    }
    if (isset($contact['gdpr']['visible_data'])) {
      $contactFieldsToUpdate['GDPR.What_is_publicly_visible_'] = $contact['gdpr']['visible_data'];
    }
    if (isset($contact['gdpr']['contexts'])) {
      $contactFieldsToUpdate['GDPR.Where_is_it_shown_'] = $contact['gdpr']['contexts'];
    }
    if (isset($contact['gdpr']['visible_name'])) {
      $contactFieldsToUpdate['GDPR.Display_name_as'] = $contact['gdpr']['visible_name'];
    }

    if ($contactFieldsToUpdate) {
      \Civi\Api4\Contact::update(FALSE)
        ->addWhere('id', '=', $id)
        ->setValues($contactFieldsToUpdate)
        ->execute();
    }

    if (isset($contact['linkedin'])) {
      $this->updateContactUrl($id, 9, $contact['linkedin']);
    }
    if (isset($contact['instagram'])) {
      $this->updateContactUrl($id, 8, $contact['instagram']);
    }
  }

  public function findPartners(): array {
    $dao = CRM_Core_DAO::executeQuery($this->getPartnersSQL());
    return $dao->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function findActivePartners(): array {
    $dao = CRM_Core_DAO::executeQuery($this->getPartnersSQL(TRUE));
    return $dao->fetchAll(\PDO::FETCH_ASSOC);
  }

  private function getPartnersSQL(bool $activeOnly = FALSE): string {
    $isActiveProject = '';
    if ($activeOnly) {
      $isActiveProject = " and ifnull(pd.start_date_136, '1900-01-01') <= NOW() and ifnull(pd.end_date_137, '3000-01-01') >= NOW() ";
    }

    return "
      select
        prtnr.id,
        prtnr.display_name partner,
        w.url website,
        prtnr.image_URL picture
      from
        civicrm_contact proj
      inner join
        civicrm_relationship r on r.relationship_type_id = 20 and r.contact_id_a = proj.id and r.is_active = 1
      inner join
        civicrm_contact prtnr on prtnr.id = r.contact_id_b
      left outer join
        civicrm_website w on w.contact_id = prtnr.id and w.website_type_id = 6
      left outer join
        civicrm_value_project_detai_62 pd on pd.entity_id = proj.id
      where
        proj.contact_type = 'Organization'
      and
        proj.contact_sub_type like '%Project_timelab%'
      and
        proj.is_deleted = 0
      and
        prtnr.is_deleted = 0
      $isActiveProject
      union
      select
        prtnr.id,
        prtnr.display_name,
        w.url,
        prtnr.image_URL
      from
        civicrm_contact proj
      inner join
        civicrm_relationship r on r.relationship_type_id = 20 and r.contact_id_b = proj.id and r.is_active = 1
      inner join
        civicrm_contact prtnr on prtnr.id = r.contact_id_a
      left outer join
        civicrm_website w on w.contact_id = prtnr.id and w.website_type_id = 6
      left outer join
        civicrm_value_project_detai_62 pd on pd.entity_id = proj.id
      where
        proj.contact_type = 'Organization'
      and
        proj.contact_sub_type like '%Project_timelab%'
      and
        proj.is_deleted = 0
      and
        prtnr.is_deleted = 0
      $isActiveProject
    ";
  }

  private function getLinkedIn(int $contactId): string {
    return $this->getContactUrl($contactId, 9);
  }

  private function getInstagram(int $contactId): string {
    return $this->getContactUrl($contactId, 8);
  }

  private function getContactUrl(int $contactId, int $websiteTypeId): string {
    $website = \Civi\Api4\Website::get(FALSE)
      ->addSelect('url')
      ->addWhere('contact_id', '=', $contactId)
      ->addWhere('website_type_id', '=', $websiteTypeId)
      ->execute()
      ->first();

    if (empty($website)) {
      return '';
    }

    return $website['url'];
  }

  private function getMemberSince(int $contactId): string {
    return \CRM_Core_DAO::singleValueQuery("select ifnull(min(join_date), '') from civicrm_membership where contact_id = $contactId");
  }

  private function getPhone(int $contactId): string {
    return \CRM_Core_DAO::singleValueQuery("select ifnull(phone, '') from civicrm_phone where contact_id = $contactId and is_primary = 1");
  }

  private function updateContactUrl(int $contactId, int $websiteTypeId, string $url): void {
    // delete
    if (empty($url)) {
      \CRM_Core_DAO::executeQuery("delete from civicrm_website where contact_id = $contactId and website_type_id = $websiteTypeId");
      return;
    }

    // add or update?
    $website = \Civi\Api4\Website::get(FALSE)
      ->addWhere('contact_id', '=', $contactId)
      ->addWhere('website_type_id', '=', $websiteTypeId)
      ->execute()
      ->first();

    if (empty($website)) {
      // add
      \Civi\Api4\Website::create(FALSE)
        ->addValue('contact_id', $contactId)
        ->addValue('url', $url)
        ->addValue('website_type_id', $websiteTypeId)
        ->execute();
    }
    else {
      // update
      \Civi\Api4\Website::update(FALSE)
        ->addValue('url', $url)
        ->addWhere('id', '=', $website['id'])
        ->execute();
    }
  }

}
