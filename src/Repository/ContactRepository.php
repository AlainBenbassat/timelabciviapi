<?php

namespace Drupal\timelabciviapi\Repository;

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
      ->addSelect('contact_id', 'contact_id.first_name', 'contact_id.last_name')
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
      'email' => $email,
    ];

    return $contact;
  }

  public function findContactById(int $id): ?array {
    $contactApi = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('id', 'first_name', 'last_name', 'email_primary.email')
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
      'email' => $contactApi['email_primary.email'],
    ];

    return $contact;
  }
}
