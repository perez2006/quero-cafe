<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Core\LdapAuthenticator;

function ldap_authenticate(string $username, string $password, array $attrs = []): array
{
    return LdapAuthenticator::authenticate($username, $password, $attrs);
}
