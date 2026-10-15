<?php

namespace Drupal\timelabciviapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\timelabciviapi\Repository\ContactRepository;
use Drupal\timelabciviapi\Repository\EventRepository;
use Drupal\timelabciviapi\Repository\ProjectRepository;
use Drupal\Core\Site\Settings;

class TimelabCiviApiController extends ControllerBase {
  protected ContactRepository $contactRepo;
  protected EventRepository $eventRepo;
  protected ProjectRepository $projectRepo;

  public function __construct(ContactRepository $contact_repo, EventRepository $event_repo, ProjectRepository $project_repo) {
    $this->contactRepo = $contact_repo;
    $this->eventRepo = $event_repo;
    $this->projectRepo = $project_repo;
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

    /** @var \Drupal\timelabciviapi\Repository\ProjectRepository $projectRepo */
    $projectRepo = $container->get('timelabciviapi.repository.project');

    return new static($contactRepo, $eventRepo, $projectRepo);
  }

  /**************************************************************************************
   * Individuals
   */

  public function searchIndividual(Request $request): JsonResponse {
    if ($failedAuthResponse = $this->validateApiToken($request)) {
      return $failedAuthResponse;
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

  public function getIndividual(Request $request, int $id): JsonResponse {
    if ($failedAuthResponse = $this->validateApiToken($request)) {
      return $failedAuthResponse;
    }

    $contact = $this->contactRepo->findContactById($id);
    if (empty($contact)) {
      return $this->genericError(404, 'Contact not found');
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $contact,
    ], 200);
  }

  /**************************************************************************************
   * Projects and Streams
   */

  public function searchProject(Request $request): JsonResponse {
    if ($failedAuthResponse = $this->validateApiToken($request)) {
      return $failedAuthResponse;
    }

    $projects = $this->projectRepo->findProjects();
    if (empty($projects)) {
      return $this->genericError(404, 'Projects not found');
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $projects,
    ], 200);
  }

  public function getProject(Request $request, int $id): JsonResponse {
    if ($failedAuthResponse = $this->validateApiToken($request)) {
      return $failedAuthResponse;
    }

    $project = $this->projectRepo->findProjectById($id);
    if (empty($project)) {
      return $this->genericError(404, "Project $id not found");
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $project,
    ], 200);
  }

  public function searchStream(Request $request): JsonResponse {
    if ($failedAuthResponse = $this->validateApiToken($request)) {
      return $failedAuthResponse;
    }

    $streams = $this->projectRepo->findStreams();
    if (empty($streams)) {
      return $this->genericError(404, 'Streams not found');
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $streams,
    ], 200);
  }

  /**************************************************************************************
   * Events
   */

  public function searchEvent(Request $request): JsonResponse {
    if ($failedAuthResponse = $this->validateApiToken($request)) {
      return $failedAuthResponse;
    }

    $dateFrom = $request->query->get('dateFrom', '');
    $dateTo = $request->query->get('dateTo', '');

    $events = $this->eventRepo->findEvent($dateFrom, $dateTo);
    if (empty($events)) {
      return $this->genericError(404, 'Events not found');
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $events,
    ], 200);
  }

  public function getEvent(Request $request, int $id): JsonResponse {
    if ($failedAuthResponse = $this->validateApiToken($request)) {
      return $failedAuthResponse;
    }

    $event = $this->eventRepo->findEventById($id);
    if (empty($event)) {
      return $this->genericError(404, "Event $id not found");
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $event,
    ], 200);
  }

  /**************************************************************************************
   * Participants
   */

  public function searchEventParticipants(Request $request): JsonResponse {
    if ($failedAuthResponse = $this->validateApiToken($request)) {
      return $failedAuthResponse;
    }

    $eventId = $request->query->get('eventId');
    if (empty($eventId)) {
      return $this->missingParameterError('eventId');
    }

    $participants = $this->eventRepo->findEventParticipants($eventId);
    if (empty($participants)) {
      return $this->genericError(404, 'Participants not found');
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $participants,
    ], 200);

  }

  /**
   * Validates the API token retrieved from the request headers.
   *
   * Verifies that the provided token matches the expected token
   * configured in site settings.
   *
   * Returns a JSON response with an appropriate error message if the validation fails.
   * Returns NULL if the token is valid.
   *
   * @param Request $request
   *   The HTTP request object that contains headers, including the API token.
   *
   * @return JsonResponse|null
   *   A JSON response with an error message if validation fails, or NULL if the token is valid.
   */
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
