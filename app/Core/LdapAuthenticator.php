<?php
declare(strict_types=1);

namespace App\Core;

final class LdapAuthenticator
{
    public static function authenticate(string $username, string $password, array $attrs = []): array
    {
        if ($username === '' || $password === '') {
            return [false, 'Usuario/senha vazios'];
        }

        if (!function_exists('ldap_connect')) {
            error_log('Extensao LDAP indisponivel no PHP.');
            return [false, 'Autenticacao LDAP indisponivel.'];
        }

        $host = (string) app_config('ldap.host');
        $baseDn = (string) app_config('ldap.base_dn');
        $upnSuffix = (string) app_config('ldap.upn_suffix');
        $searchAttrs = $attrs === [] ? (array) app_config('ldap.search_attrs', []) : $attrs;
        $bindRdn = (strpos($username, '@') === false && stripos($username, 'DC=') === false)
            ? $username . '@' . $upnSuffix
            : $username;

        try {
            $link = @ldap_connect('ldap://' . $host);
        } catch (\Throwable $exception) {
            error_log('Falha ao inicializar LDAP: ' . $exception->getMessage());
            return [false, 'Falha de conexao no LDAP'];
        }

        if (!$link) {
            return [false, 'Falha de conexao no LDAP'];
        }

        ldap_set_option($link, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($link, LDAP_OPT_REFERRALS, 0);

        if (!@ldap_bind($link, $bindRdn, $password)) {
            $message = 'Bind LDAP falhou: ' . ldap_error($link);
            @ldap_unbind($link);

            return [false, $message];
        }

        if ($searchAttrs !== []) {
            $searchUser = explode('@', $username)[0];
            $filter = "(|(userPrincipalName=$bindRdn)(sAMAccountName=$searchUser))";
            @ldap_search($link, $baseDn, $filter, $searchAttrs, 0, 1);
        }

        @ldap_unbind($link);

        return [true, 'Autenticado via LDAP'];
    }
}
