<?php

namespace Drupal\acquia_connector_test;

use Drupal\Component\Serialization\Json;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Guzzle middleware for the Acquia Connector API.
 */
class AcquiaConnectorMiddleware {

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  private StateInterface $state;

  /**
   * Constructs a new AcquiaConnectorMiddleware object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Invoked method that returns a promise.
   */
  public function __invoke() {
    return function ($handler) {
      return function (RequestInterface $request, array $options) use ($handler) {
        $uri = $request->getUri();
        if ($uri->getHost() === 'accounts.acquia.com') {
          $oauth_content = Json::decode((string) $request->getBody());
          if ($oauth_content['grant_type'] === 'authorization_code' && $oauth_content['code'] === 'AUTHORIZATION_SUCCESSFUL') {
            return new FulfilledPromise(
              new Response(
                200,
                [],
                Json::encode([
                  'access_token' => 'ACCESS_TOKEN',
                  'refresh_token' => 'REFRESH_TOKEN',
                ])
              )
            );
          }
          if ($oauth_content['grant_type'] === 'authorization_code' && $oauth_content['code'] === 'AUTHORIZATION_ERROR') {
            return new FulfilledPromise(
              new Response(
                400,
                [],
                json_encode([
                  'error' => 'invalid_grant',
                  'error_description' => 'Authorization code doesn\'t exist or is invalid for the client',
                ])
              )
            );
          }
          if ($oauth_content['grant_type'] === 'refresh_token') {
            return new FulfilledPromise(
              new Response(
                200,
                [],
                Json::encode([
                  'access_token' => 'ACCESS_TOKEN_REFRESHED',
                  'refresh_token' => 'REFRESH_TOKEN_REFRESHED',
                ])
              )
            );
          }
        }

        if ($uri->getHost() === 'cloud.acquia.com') {
          $authorization = $request->getHeaderLine('Authorization');
          if ($authorization === '') {
            return new FulfilledPromise(
              new Response(
                403,
                [],
                ''
              )
            );
          }
          if ($uri->getPath() === '/api/applications') {
            if ($authorization === 'Bearer ACCESS_TOKEN_NO_APPLICATIONS') {
              return new FulfilledPromise(
                new Response(
                  200,
                  [],
                  Json::encode([
                    'total' => 0,
                    '_embedded' => [
                      'items' => [],
                    ],
                  ])
                )
              );
            }
            if ($authorization === 'Bearer ACCESS_TOKEN_ONE_APPLICATION') {
              return new FulfilledPromise(
                new Response(
                  200,
                  [],
                  Json::encode([
                    'total' => 0,
                    '_embedded' => [
                      'items' => [
                        [
                          'id' => 1234,
                          'uuid' => 'a47ac10b-58cc-4372-a567-0e02b2c3d470',
                          'name' => 'Sample application 1',
                          'subscription' => [
                            'uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d470',
                            'name' => 'Sample subscription',
                          ],
                        ],
                      ],
                    ],
                  ])
                )
              );
            }
            if ($authorization === 'Bearer ACCESS_TOKEN_ERROR_GETTING_APPLICATION_KEYS') {
              return new FulfilledPromise(
                new Response(
                  200,
                  [],
                  Json::encode([
                    'total' => 0,
                    '_embedded' => [
                      'items' => [
                        [
                          'id' => 1234,
                          'uuid' => '647061f7-9971-4b24-9ebb-59eea154d507',
                          'name' => 'Sample application 1',
                          'subscription' => [
                            'uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d470',
                            'name' => 'Sample subscription',
                          ],
                        ],
                      ],
                    ],
                  ])
                )
              );
            }
            if ($authorization === 'Bearer ACCESS_TOKEN_MULTIPLE_APPLICATIONS') {
              return new FulfilledPromise(
                new Response(
                  200,
                  [],
                  Json::encode([
                    'total' => 0,
                    '_embedded' => [
                      'items' => [
                        [
                          'id' => 1234,
                          'uuid' => 'a47ac10b-58cc-4372-a567-0e02b2c3d470',
                          'name' => 'Sample application 1',
                          'subscription' => [
                            'uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d470',
                            'name' => 'Sample subscription',
                          ],
                        ],
                        [
                          'id' => 5678,
                          'uuid' => 'a47ac10b-58cc-4372-a567-0e02b2c3d471',
                          'name' => 'Sample application 2',
                          'subscription' => [
                            'uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d470',
                            'name' => 'Sample subscription',
                          ],
                        ],
                      ],
                    ],
                  ])
                )
              );
            }
          }
          if ($uri->getPath() === '/api/applications/647061f7-9971-4b24-9ebb-59eea154d507/settings/keys') {
            return new FulfilledPromise(
              new Response(
                500,
                [],
                ''
              )
            );
          }
          if ($uri->getPath() === '/api/applications/a47ac10b-58cc-4372-a567-0e02b2c3d470/settings/keys') {
            return new FulfilledPromise(
              new Response(
                200,
                [],
                Json::encode([
                  'acquia_connector' => [
                    'identifier' => 'ABCD-12345',
                    'key' => '12345678f5325ea35d63a6c3debcd225',
                  ],
                ])
              )
            );
          }
          if ($uri->getPath() === '/api/applications/a47ac10b-58cc-4372-a567-0e02b2c3d470') {
            return new FulfilledPromise(
              new Response(
                200,
                [],
                Json::encode([
                  'id' => 1234,
                  'uuid' => 'a47ac10b-58cc-4372-a567-0e02b2c3d470',
                  'name' => 'Sample application 1',
                  'subscription' => [
                    'uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d470',
                    'name' => 'Sample subscription',
                  ],
                ])
              )
            );
          }
          if ($uri->getPath() === '/api/subscriptions/f47ac10b-58cc-4372-a567-0e02b2c3d470') {
            return new FulfilledPromise(
              new Response(
                200,
                [],
                Json::encode([
                  'id' => 329876,
                  'uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d470',
                  'name' => 'Sample subscription',
                  'expire_at' => '2030-05-12T00:00:00',
                  'flags' => [
                    'active' => TRUE,
                    'expired' => FALSE,
                  ],
                ])
              )
            );
          }
          if ($uri->getPath() === '/test-retry-middleware') {
            if ($authorization === 'Bearer ACCESS_TOKEN_RETRY_MIDDLEWARE') {
              return new FulfilledPromise(
                new Response(
                  401,
                  [],
                  ''
                )
              );
            }
            if ($authorization === 'Bearer ACCESS_TOKEN_REFRESHED') {
              return new FulfilledPromise(
                new Response(
                  200,
                  [],
                  ''
                )
              );
            }
          }
        }

        // Otherwise, no intervention. We defer to the handler stack.
        return $handler($request, $options);
      };
    };
  }

}
