<?php

namespace Drupal\timelabciviapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\timelabciviapi\Repository\ContactRepository;
use Drupal\timelabciviapi\Repository\EventRepository;
use Drupal\Core\Site\Settings;

class TimelabCiviApiController extends ControllerBase {
  protected ContactRepository $contactRepo;
  protected EventRepository $eventRepo;

  public function __construct(ContactRepository $contact_repo, EventRepository $event_repo) {
    $this->contactRepo = $contact_repo;
    $this->eventRepo = $event_repo;
  }

  /**
   * Factory method to create a new instance of TimelabCiviApiController.
   * Is automatically called by the service container.
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\timelabciviapi\Repository\ContactRepository $contactRepo */
    $contactRepo = $container->get('timelabciviapi.repository.contact');

    /** @var \Drupal\timelabciviapi\Repository\EventRepository $eventRepo */
    $eventRepo = $container->get('timelabciviapi.repository.event');

    return new static($contactRepo, $eventRepo);
  }

  public function searchIndividual(Request $request): JsonResponse {
    if ($authResponse = $this->validateApiToken($request)) {
      return $authResponse;
    }

    $email = $request->query->get('email');
    if (empty($email)) {
      return $this->missingParameterError('email');
    }

    $contact = $this->contactRepo->findContactByEmail($email);
    if (empty($contact)) {
      return $this->genericError(404, 'Contact not found');
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $contact,
    ], 200);
  }

  public function searchOrganization(Request $request): JsonResponse {
    if ($authResponse = $this->validateApiToken($request)) {
      return $authResponse;
    }

    /*
     * NOG UIT TE WERKEN
     */
    $vat = $request->query->get('vat_number');
    //$this->contactRepo->findOrgByVat($vat);
    return new JsonResponse(['type' => 'organization', 'vat' => $vat]);
  }

  public function searchEvent(Request $request): JsonResponse {
    if ($authResponse = $this->validateApiToken($request)) {
      return $authResponse;
    }

    $title = $request->query->get('title', '');
    $keywords = $request->query->get('keywords', '');

    if (empty($title) && empty($keywords)) {
      return $this->missingParameterError('title or keywords');
    }

    $date = $request->query->get('date');
    if (empty($date)) {
      return $this->missingParameterError('date');
    }

    $event = $this->eventRepo->findEvent($title, $keywords, $date);
    if (empty($event)) {
      return $this->genericError(404, 'Event not found');
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $event,
    ], 200);
  }

  public function createParticipant(Request $request): JsonResponse {
    if ($authResponse = $this->validateApiToken($request)) {
      return $authResponse;
    }

    $params = $this->getRequestBody($request);

    if (empty($params['contact_id'])) {
      return $this->missingParameterError('contact_id');
    }

    if (empty($params['event_id'])) {
      return $this->missingParameterError('event_id');
    }

    if (empty($params['note'])) {
      return $this->missingParameterError('note');
    }

    $id = $this->eventRepo->createParticipant($params);

    if (empty($id)) {
      return $this->genericError(500, 'Failed to create participant.');
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'participant_id' => $id,
      ]
    ], 201);
  }

  private function validateApiToken(Request $request): ?JsonResponse {
    $expectedToken = Settings::get('civiapi_api_token');

    if (empty($expectedToken)) {
      return $this->genericError(500, 'API token is not configured');
    }

    $providedToken = $request->headers->get('X-Civiapi-Token', '');
    if ($providedToken === '') {
      return $this->genericError(401, 'API token is required');
    }

    if (!hash_equals($expectedToken, trim($providedToken))) {
      return $this->genericError(401, 'Invalid API token');
    }

    return NULL;
  }

  private function getRequestBody(Request $request): ?array {
    return json_decode($request->getContent(), TRUE);
  }

  private function genericError(int $errorNumber, string $errorDescription): JsonResponse {
    return new JsonResponse([
      'success' => FALSE,
      'error' => [
        'message' => $errorDescription,
      ],
    ], $errorNumber);
  }

  private function missingParameterError(string $parameter): JsonResponse {
    return $this->genericError(400, "Missing required parameter: $parameter");
  }
}
