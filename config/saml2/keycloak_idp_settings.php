<?php

return $settings = [
    'strict' => true,
    'debug' => env('APP_DEBUG', false),
    
    // Konfigurasi Service Provider (Laravel)
    'sp' => [
        'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
        'x509cert' => env('SAML2_SP_x509', ''),
        'privateKey' => env('SAML2_SP_privateKey', ''),
        'singleLogoutService' => [
            'url' => '',
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ],
    ],

    // Konfigurasi Identity Provider (Keycloak)
    'idp' => [
        'entityId' => env('SAML2_IDP_ENTITYID', ''),
        'singleSignOnService' => [
            'url' => env('SAML2_IDP_SSO_URL', ''),
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ],
        'singleLogoutService' => [
            'url' => env('SAML2_IDP_SLO_URL', ''),
            'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        ],
        'x509cert' => env('SAML2_IDP_x509', ''),
    ],
];
