<?php

namespace Drupal\timelabciviapi\Repository;

use CRM_Utils_Rule;

class ContactRepository {
  public function __construct() {
    \Drupal::service('civicrm')->initialize();
  }

  public function findOrgByVat(string $vat): ?array {
    return [];
  }

  public function findContactByEmail(string $email): ?array {
    // make sure the email is valid
    if (empty($email) || !\CRM_Utils_Rule::email($email)) {
      return null;
    }

    $emailApi = \Civi\Api4\Email::get(FALSE)
      ->addSelect('contact_id')
      ->addWhere('contact_id.is_deleted', '=', FALSE)
      ->addWhere('email', '=', $email)
      ->execute()
      ->first();

    if ($emailApi) {
      $contact = [
        'contact_id' => $emailApi['contact_id'],
        'email' => $email,
      ];

      $result = \civicrm_api3('etionevent', 'Getpersoninvoicingdetails', ['contact_id' => $emailApi['contact_id']]);
      if ($result['is_error'] == 0 && $result['count'] == 1) {
        $contact['job_title'] = $result['values'][0]['jobTitle'];
        $contact['company_id'] = $result['values'][0]['orgParticipantID'];
        $contact['company_name'] = $result['values'][0]['orgParticipant'];
        $contact['invoicing_company_id'] = $result['values'][0]['orgInvoiceID'];
        $contact['invoicing_company_name'] = $result['values'][0]['orgInvoice'];
        $contact['num_unpaid_invoices'] = (int)$result['values'][0]['orgInvoiceUnpaid'];
      }

      $result = \civicrm_api3('etionevent', 'Getmembershipdetails', ['contact_id' => $emailApi['contact_id']]);
      if ($result['is_error'] == 0 && $result['count'] == 1) {
        $contact['is_etion_member'] = ($result['values'][0]['isVKWMember'] == 1 ? TRUE : FALSE);
        $contact['membership_details'] = $result['values'][0]['membershipDetails'];
      }

      return $contact;
    }

    return NULL;
  }
}
