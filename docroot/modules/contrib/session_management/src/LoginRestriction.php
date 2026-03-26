<?php
namespace Drupal\session_management;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;

class LoginRestriction {

    protected ImmutableConfig $configFactory;

    public function __construct(ConfigFactory $configFactory)
    {
        $this->configFactory = $configFactory->get('session_management.settings');
    }

    /**
     * @param $user_ip String IP address of the user
     * @return bool TRUE if user IP is present in configured IP list else FALSE
     */
      public function isIpAllowedForLogin(string $user_ip): bool {
          $ip_ranges = $this->configFactory->get('ip_range_list') ?? [];

          foreach ($ip_ranges as $range) {
              $range = trim($range);

              if (strpos($range, '/') !== false) {
                  // CIDR format
                  if ($this->ipInCidr($user_ip, $range)) {
                      return true;
                  }
              } else {
                  // IP or IP range
                  $range = strpos($range, '-') !== false ? $range : "$range-$range";
                  if ($this->ipInRange($user_ip, $range)) {
                      return true;
                  }
              }
          }

          return false;
      }

      protected function ipInCidr(string $ip, string $cidr): bool {
          list($subnet, $mask) = explode('/', $cidr);
          $ipLong = ip2long($ip);
          $subnetLong = ip2long($subnet);
          $mask = ~((1 << (32 - (int)$mask)) - 1);

          return ($ipLong & $mask) === ($subnetLong & $mask);
      }

    protected function ipInRange(String $ip, String $range): bool
    {
        [$start, $end] = explode('-', $range);

        // Convert IP addresses to long integers
        $ipLong = ip2long($ip);
        $startIpLong = ip2long($start);
        $endIpLong = ip2long($end);

        // Check if the IP is within the range
        return ($ipLong >= $startIpLong && $ipLong <= $endIpLong);
    }
}