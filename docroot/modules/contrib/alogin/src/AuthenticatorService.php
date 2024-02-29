<?php

namespace Drupal\alogin;

use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Connection;
use PragmaRX\Google2FAQRCode\Google2FA;

/**
 * Class AuthenticatorService.
 */
class AuthenticatorService
{

    protected $secret, $issuer, $currentUser, $currentUid = 0, $database, $configFactory, $tempstorePrivate, $twofa;
    protected $table = 'alogin_user_settings';

    /**
     * Constructs a new AuthenticatorService object.
     */
    public function __construct(ConfigFactory $configFactory, PrivateTempStoreFactory $tempstorePrivate, AccountInterface $account, Connection $database)
    {
        $this->twofa = new Google2FA();
        $this->tempstorePrivate = $tempstorePrivate;
        $this->configFactory    = $configFactory;
        $this->issuer           = $this->configFactory->get('system.site')->get('name');
        $this->currentUser      = $account;
        $this->database         = $database;
        $this->currentUid       = $this->currentUser->isAuthenticated() ? $this->currentUser->id() : $this->tempstorePrivate->get('alogin')->get('uid');

        if (!$this->getSecret($this->currentUid) && !$this->tempstorePrivate->get('alogin')->get('secret')) {
            $this->tempstorePrivate->get('alogin')->set('secret', $this->twofa->generateSecretKey(64));
        }

        $this->secret           = $this->getSecret($this->currentUid) ? $this->getSecret($this->currentUid) : $this->tempstorePrivate->get('alogin')->get('secret');
    }

    public function getQr()
    {
        $qr = $this->twofa->getQRCodeInline(
          str_replace(' ', '', $this->issuer),
          $this->currentUser->getDisplayName(),
          $this->secret
        );
        return $qr;
    }

    public function check($code)
    {
        return $this->twofa->verifyKey($this->secret, $code);
    }

    public function store($enable)
    {
        if ($this->exists($this->currentUser->id())) {
            return $this->update($enable);
        }
        return $this->new($enable);
    }

    public function exists()
    {
        $exists = $this->database->select($this->table, 'a')
            ->fields('a')
            ->condition('uid', $this->currentUser->id(), '=')
            ->execute()
            ->fetchAssoc();
        return $exists;
    }

    public function new($enable = true)
    {
        $secret = $this->secret;
        $create = $this->database->insert($this->table)
            ->fields(
                [
                  'uid' => $this->currentUser->id(),
                  'secret' => $secret,
                  'enabled' => $enable
                ]
            )->execute();
        return $create;
    }

    public function update($enable)
    {
        $secret = $enable ? $this->secret : '';
        $update = $this->database->update($this->table)
            ->fields(
                [
                'secret' => $secret,
                'enabled' => $enable
                  ]
            )
            ->condition('uid', $this->currentUser->id(), '=')
            ->execute();
        return $update;
    }

    public function is_enabled($uid)
    {
        $enabled = $this->database->select($this->table, 'a')
            ->fields('a', ['enabled'])
            ->condition('uid', $uid, '=')
            ->execute()
            ->fetchAssoc();
        return $enabled ? $enabled['enabled'] : false;
    }

    protected function getSecret($uid)
    {
        $secret = $this->database->select($this->table, 'a')
            ->fields('a', ['secret'])
            ->condition('uid', $uid, '=')
            ->execute()
            ->fetchAssoc();
        return $secret ? $secret['secret'] : false;
    }

}
