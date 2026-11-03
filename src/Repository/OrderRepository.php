<?php

namespace Drupal\timelabciviapi\Repository;

use CRM_Utils_Rule;

class OrderRepository {
  public function __construct() {
    \Drupal::service('civicrm')->initialize();
  }

  public function create(array $order): ?array {
    // make sure the email is valid
    if (empty($email) || !\CRM_Utils_Rule::email($email)) {
      return NULL;
    }

    return NULL;
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
