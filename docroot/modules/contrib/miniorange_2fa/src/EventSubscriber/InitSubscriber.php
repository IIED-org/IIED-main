<?php /**
 * @file
 * Contains
 *     \Drupal\miniorange_2fa\EventSubscriber\InitSubscriber.
 */

namespace Drupal\miniorange_2fa\EventSubscriber;

use Drupal\user\Entity\User;
use Drupal\miniorange_2fa\MoAuthUtilities;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InitSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => ['onEvent', 0]];
    }

    public function onEvent()
    {
        $utilities = new MoAuthUtilities();
        $variables_and_values = array(
            'mo_auth_enable_two_factor',
            'mo_auth_enable_2fa_for_password_reset',
            'mo_auth_enforce_inline_registration',
        );
        $mo_db_values = $utilities->miniOrange_set_get_configurations($variables_and_values, 'GET');
        /**
         * Check if 2fa module is active and 2fa over password reset feature is enabled
         */
        if ($mo_db_values['mo_auth_enable_two_factor'] && $mo_db_values['mo_auth_enable_2fa_for_password_reset']) {
            global $base_url;
            $previousUrl = $base_url . \Drupal::service('path.current')->getPath();
            $session = MoAuthUtilities::getSession();
            $moMfaSession = $session->get("mo_2fa_invoked_for_password_reset", null);
            //$session->remove('mo_2fa_invoked_for_password_reset'); //ignore for now
            $moUrlParts = $utilities->mo_auth_get_url_parts();
            /**
             * Check if 2fa is already invoked if yes skip 2fa.
             * Check if flow came from the password reset link.
             */
            if ((!isset($moMfaSession['is_2fa_invoked']) || $moMfaSession['is_2fa_invoked'] !== TRUE) && isset($moUrlParts[2]) && $moUrlParts[2] === 'reset' && isset($moUrlParts[3]) && isset($moUrlParts[4]) && isset($moUrlParts[5])) {
                $moUserId = $moUrlParts[3];
                $account = User::load($moUserId);
                $userName = $account->getAccountName();
                $tmpDestination = array('moResetPass', $previousUrl);

                $custom_attribute = MoAuthUtilities::get_users_custom_attribute($moUserId);
                /**
                 * Check if user has configured the 2fa and it is enabled
                 */
                if (count($custom_attribute) > 0 && isset($custom_attribute[0]->miniorange_registered_email) && $custom_attribute[0]->enabled == 1) {
                    $moTimesTamp = $moUrlParts[4];
                    $moUrlHash = $moUrlParts[5];
                    $moGeneratedHash = user_pass_rehash($account, $moTimesTamp);
                    /**
                     * Check if the hash available in the URL and the generated matches
                     */
                    if (hash_equals($moUrlHash, $moGeneratedHash)) {
                        $utilities->invoke2fa_OR_inlineRegistration($userName, $tmpDestination);
                    } else {
                        self::moRedirectToPasswordResetLink($previousUrl);
                    }
                } elseif($mo_db_values['mo_auth_enforce_inline_registration']) {
                    \Drupal::service('page_cache_kill_switch')->trigger();
                    $utilities->invoke2fa_OR_inlineRegistration($userName, $tmpDestination);
                }
            }
        }
    }

    public static function moRedirectToPasswordResetLink($url)
    {
        $session = MoAuthUtilities::getSession();
        $session->set('mo_2fa_invoked_for_password_reset', array('is_2fa_invoked' => TRUE));
        $session->save();

        $response = new RedirectResponse($url);
        $response->send();
        exit;
    }
}