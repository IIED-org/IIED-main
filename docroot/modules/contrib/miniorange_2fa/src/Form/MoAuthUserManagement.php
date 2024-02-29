<?php
/**
 * @file
 * Contains support form for miniOrange 2FA Login
 *     Module.
 */

namespace Drupal\miniorange_2fa\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\miniorange_2fa\MoAuthUtilities;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Database\Connection;

class MoAuthUserManagement extends FormBase
{
    private ImmutableConfig $config;
    private Config $config_factory;
    private Request $request;
    private Connection $connection;

  public function __construct() {
    $this->config_factory = \Drupal::configFactory()->getEditable('miniorange_2fa.settings');
    $this->config = \Drupal::config('miniorange_2fa.settings');
    $this->request = \Drupal::request();
    $this->connection = \Drupal::database();
  }

    public function getFormId()
    {
        return 'miniorange_2fa_user_management';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        global $base_url;
        $utilities = new MoAuthUtilities();

        $form['markup_top_2'] = array(
            '#markup' => '<div class="mo_2fa_table_layout_1"><div class="mo_2fa_table_layout mo_container_second_factor">'
        );

        $disabled = False;
        if (!$utilities::isCustomerRegistered()) {
            $form['header'] = array(
                '#markup' => t('<div class="mo_2fa_register_message"><p>' . t('You need to') . ' <a href="' . $base_url . '/admin/config/people/miniorange_2fa/customer_setup">' . t('Register/Login') . '</a> ' . t('with miniOrange before using this module.') . '</p></div>'),
            );
            $disabled = True;
        }

        $form['markup_library'] = array(
            '#attached' => array(
                'library' => array(
                    "miniorange_2fa/miniorange_2fa.admin",
                    "miniorange_2fa/miniorange_2fa.license",
                    "core/drupal.dialog.ajax",
                )
            ),
        );

        /**
         * Create container to hold all the form elements.
         */
        $form['mo_user_management'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('User Management'),
            '#attributes' => array('style' => 'padding:0% 2% 17%'),
            '#disabled' => $disabled,
        );

        $form['mo_user_management']['starting_markup'] = array(
          '#markup' => '<br><div class="mo_2fa_highlight_background_note"><strong>' . t('Note:') . ' </strong>' . t('If you want to reset or disable the 2FA for any user, you can do it from this section.') . ' <strong>' . t('If you reset the 2FA for any user, then that user has to go through the inline registration process to setup the 2FA again.') . '</strong></div><br>',
        );

        $form['mo_user_management']['filter_fieldset'] = array(
          '#type' => 'fieldset',
        );

        $form['mo_user_management']['filter_fieldset'] ['username'] = array(
          '#type' => 'search',
          '#title' => $this->t('Name or email contains'),
          '#size' => 30,
          '#default_value' => $this->getUsername(),
          '#attributes' => array('class' => ['mo_2fa_horizontal_form']),
          '#prefix' => '<div class="container-inline">',
          '#suffix' => '&nbsp;',
        );

        $form['mo_user_management']['filter_fieldset'] ['no_of_rows'] = array(
          '#title' => $this->t('Items per page'),
          '#type' => 'number',
          '#min' => 5,
          '#default_value' =>  $this->config->get('mo_user_management_pages') ?? 10,
          '#attributes' => array('class' => ['mo_2fa_horizontal_form']),
           '#suffix' => '&nbsp;',
        );



        $form['mo_user_management']['filter_fieldset'] ['role'] = array(
          '#type' => 'select',
          '#title' => $this->t('Roles'),
          '#options' => $this->getUserRoles(),
          '#default_value' => $this->getDefaultRole(),
          '#attributes' => array('class' => ['mo_2fa_horizontal_form']),
          '#suffix' => '&nbsp;',
        );

        $form['mo_user_management']['filter_fieldset'] ['status'] = array(
          '#type' => 'select',
          '#title' => $this->t('2FA Status'),
          '#options' => [
            'any' => $this->t('- Any -'),
            'disabled' => $this->t('Disabled'),
            'enabled' => $this->t('Enabled'),
          ],
          '#default_value' => $this->getDefaultStatus(),
          '#attributes' => array('class' => ['mo_2fa_horizontal_form']),
          '#suffix' => '&nbsp;',
        );

        $form['mo_user_management']['filter_fieldset'] ['filter_button'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Filter'),
        );

        if($this->showResetButton()) {
          $form['mo_user_management']['filter_fieldset'] ['reset_button'] = [
            '#type' => 'submit',
            '#value' => $this->t('Reset'),
            '#submit' => ['::resetFilter'],
            '#suffix' => '</div>',
          ];
        }

        if(!$disabled) {
          $result = $this->getUserList($this->getUsername()) ?? new stdClass();
          $empty_table = 'No people available.';
        }
        else {
          $result = new stdClass();
          $empty_table = 'Register/Login with miniOrange to use this feature';
        }
        $header = [
          $this->t('User ID'),
          $this->t('Username'),
          $this->t('User Email'),
          $this->t('Phone No'),
          $this->t('User Roles'),
          $this->t('2FA Method'),
          $this->t('2FA Status'),
          $this->t('Action'),
        ];

        if(!empty(json_decode(json_encode($result), true))) {
            $form['mo_user_management']['total_users'] = array(
                '#markup' => t('Total Users: ') . count($result),
            );
        }

        $form['mo_user_management']['user_management_table'] = array(
          '#type' => 'table',
          '#header' => $header,
          '#empty' => $this->t($empty_table),
          );

        $row_number=0;
        foreach ($result as $row) {
          $user = User::load($row->uid);
          $form['mo_user_management']['user_management_table'][$row_number]['user_id'] = ['#markup' => $row->uid];
          $form['mo_user_management']['user_management_table'][$row_number]['username'] = ['#markup' => $user->getAccountName()];
          $form['mo_user_management']['user_management_table'][$row_number]['user_email'] = ['#markup' =>$row->miniorange_registered_email];
          $form['mo_user_management']['user_management_table'][$row_number]['phone'] = ['#markup' =>empty($row->phone_number) ? '-' : $row->phone_number];
          $form['mo_user_management']['user_management_table'][$row_number]['roles'] = ['#markup' => $this->getUserRoles($user->getRoles())];
          $form['mo_user_management']['user_management_table'][$row_number]['2fa_method'] = ['#markup' =>$row->activated_auth_methods];
          $form['mo_user_management']['user_management_table'][$row_number]['2fa_status'] = ['#markup' =>$row->enabled ? 'Enabled' : 'Disabled'];
          $form['mo_user_management']['user_management_table'][$row_number]['2fa_action'] = [
            '#type' => 'dropbutton',
            '#dropbutton_type' => 'small',
            '#links' => array(
              'disable' => array(
                'title' => !$row->enabled ? $this->t('Enable' ): $this->t('Disable'),
                'url'  => Url::fromRoute('miniorange_2fa.changes_2fa_status', array('user' => $row->uid)),
              ),
              'reset' => array(
                'title' => $this->t('Reset'),
                'url' => Url::fromRoute('miniorange_2fa.rest_2fa', array('user' => $row->uid )),
              ),
            ),
          ];
          $row_number++;
        }

      $form['mo_user_management']['pager'] = array(
        '#type' => 'pager',
      );

      return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
      global $base_url;
      $number_of_rows = $form_state->getValue('no_of_rows');
      $this->config_factory->set('mo_user_management_pages', $number_of_rows)->save();

      $username  = trim($form_state->getValue('username'));
      $role      = $form_state->getValue('role');
      $status    = $form_state->getValue('status');

      $url = $base_url .'/admin/config/people/miniorange_2fa/user_management'
        . '?username=' . $username
        . '&role=' . $role
        . '&status=' . $status ;

      $response = new RedirectResponse($url);
      $response->send();
    }

    public function getUsername() {
      $username = $this->request->get('username');
      return empty($username) ? null : $username;
    }

    public function getDefaultStatus() {
      $status = $this->request->get('status');
      return is_null($status) ? 'any' : $status;
    }

    public function getDefaultRole() {
      $role = $this->request->get('role');
      return is_null($role) ? 'any' : $role;
    }

    public function resetFilter() {
      global $base_url;
      $this->config_factory->set('mo_user_management_pages', 10)->save();
      $response = new RedirectResponse($base_url.'/admin/config/people/miniorange_2fa/user_management');
      $response->send();
    }

   /**
    * Important function which actually filter the data
    */
    public function getUserList($username) {
      $role          = $this->request->get('role');
      $status        = $this->request->get('status');
      $filter_role   = true;
      $filter_status = true;

      if (is_null($role) || $role == 'any') {
        $filter_role = false;
      }

      if (is_null($status) || $status == 'any') {
        $filter_status = false;
      }

      $status = $status == 'enabled' ? 1 :0;

      if($filter_status && $filter_role) {
        return $this->filterBasedOnStatusAndRoles($status, $role, $username);
      } elseif($filter_status) {
        return $this->filterBasedOnStatus($status,$username);
      } elseif($filter_role) {
        return $this->filterBasedOnRoles($role, $username);
      }
      return $this->filterBasedOnUsername($username);
    }

    public function filterBasedOnStatus(string $status, $username) {
      try {
        $query = $this->getNameOrEmailFilter($username);
        $query->condition('enabled' ,$status, '=');
        return $query->orderBy('created', 'ASC')
          ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
          ->limit($this->config->get('mo_user_management_pages') ?? 5)
          ->execute()
          ->fetchAll();
      }
      catch (\Exception $exception) {
        $this->handleException($exception);
      }
    }

    public function filterBasedOnRoles(string $role, $username) {
      try {
        $uid   = $this->userIdForRoleFilter($role, $username );
        $query = $this->connection->select('UserAuthenticationType', 'u')
          ->fields('u')
          ->condition('uid', $uid, 'IN');

        return $query->orderBy('uid', 'ASC')
          ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
          ->limit($this->config->get('mo_user_management_pages') ?? 10)
          ->execute()
          ->fetchAll();
      }
      catch (\Exception $exception) {
        $this->handleException($exception);
      }

    }

    public function filterBasedOnStatusAndRoles(string $status, string $role, $username) {
      try {
        $uid = $this->userIdForRoleFilter($role, $username);
        $query = $this->connection->select('UserAuthenticationType', 'u')
          ->fields('u');
        $statusAndRoles = $query->andConditionGroup()
          ->condition('uid', $uid, 'IN')
          ->condition('enabled', $status, '=');
        $query->condition($statusAndRoles);
        return $query->orderBy('uid', 'ASC')
          ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
          ->limit($this->config->get('mo_user_management_pages') ?? 10)
          ->execute()
          ->fetchAll();
      }
      catch (\Exception $exception) {
        $this->handleException($exception);
      }
    }

    public function filterBasedOnUsername($username) {
      try {
        $query = $this->getNameOrEmailFilter($username);
        return $query->orderBy('created', 'ASC')
          ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
          ->limit($this->config->get('mo_user_management_pages') ?? 10)
          ->execute()
          ->fetchAll();
      }
      catch (\Exception $exception) {
        $this->handleException($exception);
      }
    }

    public function getNameOrEmailFilter($username) {
      try {
        $query = $this->connection->select('UserAuthenticationType', 'u');
        $query->Join('users_field_data','udata','u.uid = udata.uid');
        $query->fields('u')->fields('udata');

        if (!is_null($username)){
          $emailOrUsername = $query->orConditionGroup()
            ->condition('name', '%' . $username . '%', 'LIKE' )
            ->condition('miniorange_registered_email', '%' . $username . '%', 'LIKE');
          $query->condition($emailOrUsername);
        }
        return $query;
      }
      catch (\Exception $exception) {
        $this->handleException($exception);
      }
    }

    public function getUserRoles(array $row_numberd=null) {
      $roles = Role::loadMultiple($row_numberd);
      $roles_array = [
        'any' => '- Any -',
      ];
      foreach ($roles as $key => $value) {
        $roles_array[$key] = $value->label();
      }

      if(isset($roles_array['authenticated'])) {
        unset($roles_array['authenticated']);
      }

      if(isset($roles_array['anonymous'])) {
        unset($roles_array['anonymous']);
      }

      if($row_numberd!=null) {
        unset($roles_array['any']);
        $string = '<ul>';
        foreach ($roles_array as $roles) {
          $string .= '<li>'.$roles.'</li>';
        }
        $string .= '</ul>';
        return Markup::create($string);
      }

      return $roles_array;
    }

    public function userIdForRoleFilter(string $role,$username) {
      $role_uid = [0];
      $user_uid = [0];
      try {
        $roles = $this->connection->select('user__roles', 'role')
          ->fields('role', ['entity_id', 'roles_target_id'])
          ->condition('roles_target_id',$role,'=')
          ->execute()
          ->fetchAll();

        foreach ($roles as $role) {
          $role_uid[] = $role->entity_id;
        }

        $role_uid = array_unique($role_uid);

        if(!is_null($username)) {
          $users = $this->connection->select('users_field_data', 'udata')->fields('udata');
          $emailOrUsername = $users->orConditionGroup()
            ->condition('name', '%' . $username . '%', 'LIKE' )
            ->condition('mail', '%' . $username . '%', 'LIKE');
          $result = $users->condition($emailOrUsername)->execute()->fetchAll();

          foreach ($result as $user) {
            $user_uid[] = $user->uid;
          }

          $role_uid =  array_intersect($user_uid, $role_uid);
        }
          return $role_uid;
      }
      catch (\Exception $exception) {
        $this->handleException($exception);
      }
    }

    public function handleException($exception) {
      MoAuthUtilities::mo_add_loggers_for_failures($exception, 'error');
      \Drupal::messenger()->addError('Something went wrong while filtering your data. Please see recent log for details.');
      return null;
    }

    public function showResetButton() {
      $username       = $this->getUsername();
      $status         = $this->getDefaultStatus();
      $role           = $this->getDefaultRole();
      $number_of_rows = $this->config->get('mo_user_management_pages') ;

      if(is_null($username) && ($status=='any') && ($role=='any') && ($number_of_rows==10)) {
        return false;
      }
      else {
        return true;
      }
    }

}
