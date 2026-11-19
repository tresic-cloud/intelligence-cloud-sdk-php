<?php

declare(strict_types=1);

namespace IntelligenceCloud\Internal;

/**
 * Generated from openapi.yaml. Do not edit by hand.
 *
 * Per operation: HTTP method, path, allowed query filters, allowed body keys and the
 * response projection tree (fields not in the published schema are dropped).
 */
final class SpecTables
{
    public const OPERATIONS = [
        'login' => [
            'tag' => 'Authentication',
            'summary' => 'Sign in',
            'pathParams' => [],
            'method' => 'POST',
            'path' => '/auth/login',
            'query' => [],
            'body' => ['email', 'password'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'access_token' => 1,
                    'refresh_token' => 1,
                    'token_type' => 1,
                    'expires_in' => 1,
                    'user' => [
                        'o' => [
                            'id' => 1,
                            'user_type' => 1,
                            'reseller_id' => 1,
                            'email' => 1,
                            'first_name' => 1,
                            'last_name' => 1,
                            'display_name' => 1,
                            'role' => 1,
                            'scopes' => [
                                'a' => 1,
                            ],
                        ],
                    ],
                    'password_expired' => 1,
                    'status' => 1,
                ],
            ],
        ],
        'refresh' => [
            'tag' => 'Authentication',
            'summary' => 'Renew the access token',
            'pathParams' => [],
            'method' => 'POST',
            'path' => '/auth/refresh',
            'query' => [],
            'body' => ['refresh_token'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'access_token' => 1,
                    'refresh_token' => 1,
                    'token_type' => 1,
                    'expires_in' => 1,
                    'user' => [
                        'o' => [
                            'id' => 1,
                            'user_type' => 1,
                            'reseller_id' => 1,
                            'email' => 1,
                            'first_name' => 1,
                            'last_name' => 1,
                            'display_name' => 1,
                            'role' => 1,
                            'scopes' => [
                                'a' => 1,
                            ],
                        ],
                    ],
                    'password_expired' => 1,
                ],
            ],
        ],
        'logout' => [
            'tag' => 'Authentication',
            'summary' => 'Sign out',
            'pathParams' => [],
            'method' => 'POST',
            'path' => '/auth/logout',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'message' => 1,
                ],
            ],
        ],
        'getResellerBranding' => [
            'tag' => 'Resellers',
            'summary' => 'Read reseller branding',
            'pathParams' => ['resellerId'],
            'method' => 'GET',
            'path' => '/resellers/{resellerId}/branding',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'reseller_id' => 1,
                                    'display_name' => 1,
                                    'primary_accent_color' => 1,
                                    'secondary_accent_color' => 1,
                                    'support_email' => 1,
                                    'support_portal_url' => 1,
                                    'privacy_policy_url' => 1,
                                    'terms_of_service_url' => 1,
                                    'copyright_text' => 1,
                                    'main_logo_url' => 1,
                                    'favicon_url' => 1,
                                    'email_logo_url' => 1,
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'updateResellerBranding' => [
            'tag' => 'Resellers',
            'summary' => 'Update reseller branding',
            'pathParams' => ['resellerId'],
            'method' => 'PATCH',
            'path' => '/resellers/{resellerId}/branding',
            'query' => [],
            'body' => ['copyright_text', 'display_name', 'primary_accent_color', 'privacy_policy_url', 'secondary_accent_color', 'support_email', 'support_portal_url', 'terms_of_service_url'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'reseller_id' => 1,
                                    'display_name' => 1,
                                    'primary_accent_color' => 1,
                                    'secondary_accent_color' => 1,
                                    'support_email' => 1,
                                    'support_portal_url' => 1,
                                    'privacy_policy_url' => 1,
                                    'terms_of_service_url' => 1,
                                    'copyright_text' => 1,
                                    'main_logo_url' => 1,
                                    'favicon_url' => 1,
                                    'email_logo_url' => 1,
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'getResellerSmtpSettings' => [
            'tag' => 'Resellers',
            'summary' => 'Read outbound email (SMTP) settings',
            'pathParams' => ['resellerId'],
            'method' => 'GET',
            'path' => '/resellers/{resellerId}/smtp-settings',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'reseller_id' => 1,
                                    'smtp_host' => 1,
                                    'smtp_port' => 1,
                                    'smtp_username' => 1,
                                    'smtp_password' => 1,
                                    'sender_name' => 1,
                                    'sender_email' => 1,
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'saveResellerSmtpSettings' => [
            'tag' => 'Resellers',
            'summary' => 'Save outbound email (SMTP) settings',
            'pathParams' => ['resellerId'],
            'method' => 'PATCH',
            'path' => '/resellers/{resellerId}/smtp-settings',
            'query' => [],
            'body' => ['sender_email', 'sender_name', 'smtp_host', 'smtp_password', 'smtp_port', 'smtp_username'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'reseller_id' => 1,
                                    'smtp_host' => 1,
                                    'smtp_port' => 1,
                                    'smtp_username' => 1,
                                    'smtp_password' => 1,
                                    'sender_name' => 1,
                                    'sender_email' => 1,
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'deleteResellerSmtpSettings' => [
            'tag' => 'Resellers',
            'summary' => 'Remove outbound email (SMTP) settings',
            'pathParams' => ['resellerId'],
            'method' => 'DELETE',
            'path' => '/resellers/{resellerId}/smtp-settings',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => 1,
        ],
        'listCompanies' => [
            'tag' => 'Companies',
            'summary' => 'List customer companies',
            'pathParams' => ['resellerId'],
            'method' => 'GET',
            'path' => '/resellers/{resellerId}/companies',
            'query' => [
                'search' => [
                    'type' => 'string',
                ],
                'is_active' => [
                    'type' => 'boolean',
                ],
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                ],
                'cursor' => [
                    'type' => 'string',
                ],
            ],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'type' => 1,
                                'attributes' => [
                                    'o' => [
                                        'id' => 1,
                                        'reseller_id' => 1,
                                        'reseller_name' => 1,
                                        'company_name' => 1,
                                        'company_code' => 1,
                                        'vertical' => 1,
                                        'address' => 1,
                                        'primary_admin_contact_name' => 1,
                                        'primary_admin_contact_email' => 1,
                                        'primary_admin_contact_phone' => 1,
                                        'enabled_products' => [
                                            'a' => 1,
                                        ],
                                        'is_active' => 1,
                                        'mfa_enforced' => 1,
                                        'mfa_available' => 1,
                                        'location_count' => 1,
                                        'user_count' => 1,
                                        'created_at' => 1,
                                        'updated_at' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'has_more' => 1,
                    'next_cursor' => 1,
                    'total_count' => 1,
                ],
            ],
        ],
        'createCompany' => [
            'tag' => 'Companies',
            'summary' => 'Create a customer company',
            'pathParams' => ['resellerId'],
            'method' => 'POST',
            'path' => '/resellers/{resellerId}/companies',
            'query' => [],
            'body' => ['company_code', 'company_name', 'enabled_products', 'primary_admin_contact_email', 'primary_admin_contact_name', 'primary_admin_contact_phone'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'reseller_id' => 1,
                                    'reseller_name' => 1,
                                    'company_name' => 1,
                                    'company_code' => 1,
                                    'vertical' => 1,
                                    'address' => 1,
                                    'primary_admin_contact_name' => 1,
                                    'primary_admin_contact_email' => 1,
                                    'primary_admin_contact_phone' => 1,
                                    'enabled_products' => [
                                        'a' => 1,
                                    ],
                                    'is_active' => 1,
                                    'mfa_enforced' => 1,
                                    'mfa_available' => 1,
                                    'location_count' => 1,
                                    'user_count' => 1,
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                    'invited_user' => [
                        'o' => [
                            'id' => 1,
                            'email' => 1,
                            'display_name' => 1,
                            'status' => 1,
                            'role' => 1,
                            'invited_at' => 1,
                            'invite_expires_at' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'getCompaniesMfaSummary' => [
            'tag' => 'Companies',
            'summary' => 'Read the MFA default for new companies',
            'pathParams' => ['resellerId'],
            'method' => 'GET',
            'path' => '/resellers/{resellerId}/companies/mfa',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'total_active' => 1,
                            'with_mfa_required' => 1,
                            'companies_mfa_default' => 1,
                            'mfa_available' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'getCompany' => [
            'tag' => 'Companies',
            'summary' => 'Read a company',
            'pathParams' => ['companyId'],
            'method' => 'GET',
            'path' => '/companies/{companyId}',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'reseller_id' => 1,
                                    'reseller_name' => 1,
                                    'company_name' => 1,
                                    'company_code' => 1,
                                    'vertical' => 1,
                                    'address' => 1,
                                    'primary_admin_contact_name' => 1,
                                    'primary_admin_contact_email' => 1,
                                    'primary_admin_contact_phone' => 1,
                                    'enabled_products' => [
                                        'a' => 1,
                                    ],
                                    'is_active' => 1,
                                    'mfa_enforced' => 1,
                                    'mfa_available' => 1,
                                    'location_count' => 1,
                                    'user_count' => 1,
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'updateCompany' => [
            'tag' => 'Companies',
            'summary' => 'Update a company',
            'pathParams' => ['companyId'],
            'method' => 'PATCH',
            'path' => '/companies/{companyId}',
            'query' => [],
            'body' => ['address', 'company_code', 'company_name', 'enabled_products', 'mfa_enforced', 'primary_admin_contact_email', 'primary_admin_contact_name', 'primary_admin_contact_phone', 'vertical'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'reseller_id' => 1,
                                    'reseller_name' => 1,
                                    'company_name' => 1,
                                    'company_code' => 1,
                                    'vertical' => 1,
                                    'address' => 1,
                                    'primary_admin_contact_name' => 1,
                                    'primary_admin_contact_email' => 1,
                                    'primary_admin_contact_phone' => 1,
                                    'enabled_products' => [
                                        'a' => 1,
                                    ],
                                    'is_active' => 1,
                                    'mfa_enforced' => 1,
                                    'mfa_available' => 1,
                                    'location_count' => 1,
                                    'user_count' => 1,
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'deactivateCompany' => [
            'tag' => 'Companies',
            'summary' => 'Deactivate a company',
            'pathParams' => ['companyId'],
            'method' => 'POST',
            'path' => '/companies/{companyId}/deactivate',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'reseller_id' => 1,
                                    'reseller_name' => 1,
                                    'company_name' => 1,
                                    'company_code' => 1,
                                    'vertical' => 1,
                                    'address' => 1,
                                    'primary_admin_contact_name' => 1,
                                    'primary_admin_contact_email' => 1,
                                    'primary_admin_contact_phone' => 1,
                                    'enabled_products' => [
                                        'a' => 1,
                                    ],
                                    'is_active' => 1,
                                    'mfa_enforced' => 1,
                                    'mfa_available' => 1,
                                    'location_count' => 1,
                                    'user_count' => 1,
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'reactivateCompany' => [
            'tag' => 'Companies',
            'summary' => 'Reactivate a company',
            'pathParams' => ['companyId'],
            'method' => 'POST',
            'path' => '/companies/{companyId}/reactivate',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'reseller_id' => 1,
                                    'reseller_name' => 1,
                                    'company_name' => 1,
                                    'company_code' => 1,
                                    'vertical' => 1,
                                    'address' => 1,
                                    'primary_admin_contact_name' => 1,
                                    'primary_admin_contact_email' => 1,
                                    'primary_admin_contact_phone' => 1,
                                    'enabled_products' => [
                                        'a' => 1,
                                    ],
                                    'is_active' => 1,
                                    'mfa_enforced' => 1,
                                    'mfa_available' => 1,
                                    'location_count' => 1,
                                    'user_count' => 1,
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'getCompanySecuritySettings' => [
            'tag' => 'Companies',
            'summary' => 'Read a company\'s security settings',
            'pathParams' => ['companyId'],
            'method' => 'GET',
            'path' => '/companies/{companyId}/security-settings',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'min_password_length' => 1,
                            'require_uppercase' => 1,
                            'require_numbers' => 1,
                            'require_special_chars' => 1,
                            'password_expiry_days' => 1,
                            'session_timeout_minutes' => 1,
                            'allowed_ip_ranges' => [
                                'a' => 1,
                            ],
                            'password_policy_source' => 1,
                            'password_policy_inherited_fields' => [
                                'a' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'getCompanyBranding' => [
            'tag' => 'Companies',
            'summary' => 'Read company branding overrides',
            'pathParams' => ['companyId'],
            'method' => 'GET',
            'path' => '/companies/{companyId}/branding',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'company_id' => 1,
                                    'display_name' => 1,
                                    'primary_accent_color' => 1,
                                    'secondary_accent_color' => 1,
                                    'support_email' => 1,
                                    'privacy_policy_url' => 1,
                                    'terms_of_service_url' => 1,
                                    'copyright_text' => 1,
                                    'company_logo_url' => 1,
                                    'favicon_url' => 1,
                                    'email_header_logo_url' => 1,
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'updateCompanyBranding' => [
            'tag' => 'Companies',
            'summary' => 'Update company branding overrides',
            'pathParams' => ['companyId'],
            'method' => 'PATCH',
            'path' => '/companies/{companyId}/branding',
            'query' => [],
            'body' => ['copyright_text', 'display_name', 'primary_accent_color', 'privacy_policy_url', 'secondary_accent_color', 'support_email', 'terms_of_service_url'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'company_id' => 1,
                                    'display_name' => 1,
                                    'primary_accent_color' => 1,
                                    'secondary_accent_color' => 1,
                                    'support_email' => 1,
                                    'privacy_policy_url' => 1,
                                    'terms_of_service_url' => 1,
                                    'copyright_text' => 1,
                                    'company_logo_url' => 1,
                                    'favicon_url' => 1,
                                    'email_header_logo_url' => 1,
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'listCompanyDomains' => [
            'tag' => 'Single sign-on',
            'summary' => 'List claimed domains',
            'pathParams' => ['companyId'],
            'method' => 'GET',
            'path' => '/companies/{companyId}/domains',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'id' => 1,
                                'domain' => 1,
                                'status' => 1,
                                'verification' => [
                                    'o' => [
                                        'type' => 1,
                                        'name' => 1,
                                        'value' => 1,
                                    ],
                                ],
                                'verified_at' => 1,
                                'last_checked_at' => 1,
                                'last_failure_reason' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'claimCompanyDomain' => [
            'tag' => 'Single sign-on',
            'summary' => 'Claim a domain',
            'pathParams' => ['companyId'],
            'method' => 'POST',
            'path' => '/companies/{companyId}/domains',
            'query' => [],
            'body' => ['domain'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'id' => 1,
                            'domain' => 1,
                            'status' => 1,
                            'verification' => [
                                'o' => [
                                    'type' => 1,
                                    'name' => 1,
                                    'value' => 1,
                                ],
                            ],
                            'verified_at' => 1,
                            'last_checked_at' => 1,
                            'last_failure_reason' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'removeCompanyDomain' => [
            'tag' => 'Single sign-on',
            'summary' => 'Remove a domain claim',
            'pathParams' => ['companyId', 'domainId'],
            'method' => 'DELETE',
            'path' => '/companies/{companyId}/domains/{domainId}',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => 1,
        ],
        'listDomainVerifications' => [
            'tag' => 'Single sign-on',
            'summary' => 'List verification attempts',
            'pathParams' => ['companyId', 'domainId'],
            'method' => 'GET',
            'path' => '/companies/{companyId}/domains/{domainId}/verifications',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'checked_at' => 1,
                                'status' => 1,
                                'failure_reason' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'verifyDomain' => [
            'tag' => 'Single sign-on',
            'summary' => 'Check the TXT record now',
            'pathParams' => ['companyId', 'domainId'],
            'method' => 'POST',
            'path' => '/companies/{companyId}/domains/{domainId}/verifications',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'checked_at' => 1,
                            'status' => 1,
                            'failure_reason' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'getIdentityProvider' => [
            'tag' => 'Single sign-on',
            'summary' => 'Read the identity provider connection',
            'pathParams' => ['companyId'],
            'method' => 'GET',
            'path' => '/companies/{companyId}/identity-provider',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'id' => 1,
                            'display_name' => 1,
                            'type' => 1,
                            'is_active' => 1,
                            'issuer' => 1,
                            'well_known_endpoint' => 1,
                            'client_id' => 1,
                            'client_auth_method' => 1,
                            'supported_client_auth_methods' => [
                                'a' => 1,
                            ],
                            'secret_held' => 1,
                            'link_callback_uri' => 1,
                            'instructions' => [
                                'a' => 1,
                            ],
                            'created_at' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'connectIdentityProvider' => [
            'tag' => 'Single sign-on',
            'summary' => 'Connect an OpenID Connect provider',
            'pathParams' => ['companyId'],
            'method' => 'POST',
            'path' => '/companies/{companyId}/identity-provider',
            'query' => [],
            'body' => ['acknowledgement', 'client_auth_method', 'client_id', 'client_secret', 'display_name', 'issuer', 'secret_expires_at', 'type', 'well_known_endpoint'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'id' => 1,
                            'display_name' => 1,
                            'type' => 1,
                            'is_active' => 1,
                            'issuer' => 1,
                            'well_known_endpoint' => 1,
                            'client_id' => 1,
                            'client_auth_method' => 1,
                            'supported_client_auth_methods' => [
                                'a' => 1,
                            ],
                            'secret_held' => 1,
                            'link_callback_uri' => 1,
                            'instructions' => [
                                'a' => 1,
                            ],
                            'created_at' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'disconnectIdentityProvider' => [
            'tag' => 'Single sign-on',
            'summary' => 'Disconnect the identity provider',
            'pathParams' => ['companyId'],
            'method' => 'DELETE',
            'path' => '/companies/{companyId}/identity-provider',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => 1,
        ],
        'testIdentityProvider' => [
            'tag' => 'Single sign-on',
            'summary' => 'Test the identity provider connection',
            'pathParams' => ['companyId'],
            'method' => 'POST',
            'path' => '/companies/{companyId}/identity-provider/test',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'reachable' => 1,
                            'checks' => [
                                'a' => [
                                    'o' => [
                                        'name' => 1,
                                        'outcome' => 1,
                                        'detail' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'listProducts' => [
            'tag' => 'Products',
            'summary' => 'List products',
            'pathParams' => [],
            'method' => 'GET',
            'path' => '/products',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'key' => 1,
                                'name' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'listCompanyProducts' => [
            'tag' => 'Products',
            'summary' => 'Company products',
            'pathParams' => ['companyId'],
            'method' => 'GET',
            'path' => '/companies/{companyId}/products',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'product_key' => 1,
                                'name' => 1,
                                'enabled' => 1,
                                'enabled_at' => 1,
                                'enabled_by' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'listUserProducts' => [
            'tag' => 'Products',
            'summary' => 'A user\'s products',
            'pathParams' => ['companyId', 'userId'],
            'method' => 'GET',
            'path' => '/companies/{companyId}/users/{userId}/products',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'product_key' => 1,
                                'name' => 1,
                                'enabled' => 1,
                                'enabled_at' => 1,
                                'enabled_by' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'enableUserProduct' => [
            'tag' => 'Products',
            'summary' => 'Turn a product on for a user',
            'pathParams' => ['locationId', 'userId', 'productKey'],
            'method' => 'POST',
            'path' => '/locations/{locationId}/users/{userId}/products/{productKey}/enable',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'product_key' => 1,
                            'name' => 1,
                            'enabled' => 1,
                            'enabled_at' => 1,
                            'enabled_by' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'disableUserProduct' => [
            'tag' => 'Products',
            'summary' => 'Turn a product off for a user',
            'pathParams' => ['locationId', 'userId', 'productKey'],
            'method' => 'POST',
            'path' => '/locations/{locationId}/users/{userId}/products/{productKey}/disable',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'product_key' => 1,
                            'name' => 1,
                            'enabled' => 1,
                            'enabled_at' => 1,
                            'enabled_by' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'listVerticals' => [
            'tag' => 'Locations',
            'summary' => 'List industries',
            'pathParams' => [],
            'method' => 'GET',
            'path' => '/verticals',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'id' => 1,
                                'key' => 1,
                                'name' => 1,
                                'description' => 1,
                                'is_active' => 1,
                                'display_order' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'listCompanyLocations' => [
            'tag' => 'Locations',
            'summary' => 'List a company\'s locations',
            'pathParams' => ['companyId'],
            'method' => 'GET',
            'path' => '/companies/{companyId}/locations',
            'query' => [
                'search' => [
                    'type' => 'string',
                ],
                'is_active' => [
                    'type' => 'boolean',
                ],
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                ],
                'cursor' => [
                    'type' => 'string',
                ],
                'offset' => [
                    'type' => 'integer',
                    'minimum' => 0,
                ],
            ],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'type' => 1,
                                'attributes' => [
                                    'o' => [
                                        'id' => 1,
                                        'company_id' => 1,
                                        'company_name' => 1,
                                        'reseller_name' => 1,
                                        'location_name' => 1,
                                        'street' => 1,
                                        'city' => 1,
                                        'state' => 1,
                                        'country' => 1,
                                        'zipcode' => 1,
                                        'support_contact_name' => 1,
                                        'support_contact_email' => 1,
                                        'support_contact_phone' => 1,
                                        'vertical_id' => 1,
                                        'vertical_name' => 1,
                                        'enabled_products' => [
                                            'a' => 1,
                                        ],
                                        'is_active' => 1,
                                        'user_count' => 1,
                                        'created_at' => 1,
                                        'updated_at' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'pagination' => [
                        'o' => [
                            'has_more' => 1,
                            'next_cursor' => 1,
                            'total_count' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'createLocation' => [
            'tag' => 'Locations',
            'summary' => 'Create a location',
            'pathParams' => ['companyId'],
            'method' => 'POST',
            'path' => '/companies/{companyId}/locations',
            'query' => [],
            'body' => ['city', 'country', 'enabled_products', 'location_name', 'state', 'street', 'vertical_id', 'zipcode'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'company_id' => 1,
                                    'company_name' => 1,
                                    'reseller_name' => 1,
                                    'location_name' => 1,
                                    'street' => 1,
                                    'city' => 1,
                                    'state' => 1,
                                    'country' => 1,
                                    'zipcode' => 1,
                                    'support_contact_name' => 1,
                                    'support_contact_email' => 1,
                                    'support_contact_phone' => 1,
                                    'vertical_id' => 1,
                                    'vertical_name' => 1,
                                    'enabled_products' => [
                                        'a' => 1,
                                    ],
                                    'is_active' => 1,
                                    'user_count' => 1,
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'getLocation' => [
            'tag' => 'Locations',
            'summary' => 'Read a location',
            'pathParams' => ['companyId', 'locationId'],
            'method' => 'GET',
            'path' => '/companies/{companyId}/locations/{locationId}',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'company_id' => 1,
                                    'company_name' => 1,
                                    'reseller_name' => 1,
                                    'location_name' => 1,
                                    'street' => 1,
                                    'city' => 1,
                                    'state' => 1,
                                    'country' => 1,
                                    'zipcode' => 1,
                                    'support_contact_name' => 1,
                                    'support_contact_email' => 1,
                                    'support_contact_phone' => 1,
                                    'vertical_id' => 1,
                                    'vertical_name' => 1,
                                    'enabled_products' => [
                                        'a' => 1,
                                    ],
                                    'is_active' => 1,
                                    'user_count' => 1,
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'updateLocation' => [
            'tag' => 'Locations',
            'summary' => 'Update a location',
            'pathParams' => ['companyId', 'locationId'],
            'method' => 'PATCH',
            'path' => '/companies/{companyId}/locations/{locationId}',
            'query' => [],
            'body' => ['city', 'country', 'enabled_products', 'location_name', 'state', 'street', 'vertical_id', 'zipcode'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'company_id' => 1,
                                    'company_name' => 1,
                                    'reseller_name' => 1,
                                    'location_name' => 1,
                                    'street' => 1,
                                    'city' => 1,
                                    'state' => 1,
                                    'country' => 1,
                                    'zipcode' => 1,
                                    'support_contact_name' => 1,
                                    'support_contact_email' => 1,
                                    'support_contact_phone' => 1,
                                    'vertical_id' => 1,
                                    'vertical_name' => 1,
                                    'enabled_products' => [
                                        'a' => 1,
                                    ],
                                    'is_active' => 1,
                                    'user_count' => 1,
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'deactivateLocation' => [
            'tag' => 'Locations',
            'summary' => 'Deactivate a location',
            'pathParams' => ['companyId', 'locationId'],
            'method' => 'POST',
            'path' => '/companies/{companyId}/locations/{locationId}/deactivate',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'company_id' => 1,
                                    'company_name' => 1,
                                    'reseller_name' => 1,
                                    'location_name' => 1,
                                    'street' => 1,
                                    'city' => 1,
                                    'state' => 1,
                                    'country' => 1,
                                    'zipcode' => 1,
                                    'support_contact_name' => 1,
                                    'support_contact_email' => 1,
                                    'support_contact_phone' => 1,
                                    'vertical_id' => 1,
                                    'vertical_name' => 1,
                                    'enabled_products' => [
                                        'a' => 1,
                                    ],
                                    'is_active' => 1,
                                    'user_count' => 1,
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'reactivateLocation' => [
            'tag' => 'Locations',
            'summary' => 'Reactivate a location',
            'pathParams' => ['companyId', 'locationId'],
            'method' => 'POST',
            'path' => '/companies/{companyId}/locations/{locationId}/reactivate',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'company_id' => 1,
                                    'company_name' => 1,
                                    'reseller_name' => 1,
                                    'location_name' => 1,
                                    'street' => 1,
                                    'city' => 1,
                                    'state' => 1,
                                    'country' => 1,
                                    'zipcode' => 1,
                                    'support_contact_name' => 1,
                                    'support_contact_email' => 1,
                                    'support_contact_phone' => 1,
                                    'vertical_id' => 1,
                                    'vertical_name' => 1,
                                    'enabled_products' => [
                                        'a' => 1,
                                    ],
                                    'is_active' => 1,
                                    'user_count' => 1,
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'listUserGroups' => [
            'tag' => 'User groups',
            'summary' => 'List a location\'s groups',
            'pathParams' => ['locationId'],
            'method' => 'GET',
            'path' => '/locations/{locationId}/user-groups',
            'query' => [
                'search' => [
                    'type' => 'string',
                ],
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                ],
                'cursor' => [
                    'type' => 'string',
                ],
            ],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'type' => 1,
                                'attributes' => [
                                    'o' => [
                                        'id' => 1,
                                        'location_id' => 1,
                                        'group_name' => 1,
                                        'user_count' => 1,
                                        'members' => [
                                            'a' => [
                                                'o' => [
                                                    'id' => 1,
                                                    'email' => 1,
                                                    'first_name' => 1,
                                                    'last_name' => 1,
                                                    'display_name' => 1,
                                                ],
                                            ],
                                        ],
                                        'created_at' => 1,
                                        'updated_at' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'pagination' => [
                        'o' => [
                            'has_more' => 1,
                            'next_cursor' => 1,
                            'total_count' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'createUserGroup' => [
            'tag' => 'User groups',
            'summary' => 'Create a group',
            'pathParams' => ['locationId'],
            'method' => 'POST',
            'path' => '/locations/{locationId}/user-groups',
            'query' => [],
            'body' => ['group_name', 'member_ids'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'location_id' => 1,
                                    'group_name' => 1,
                                    'user_count' => 1,
                                    'members' => [
                                        'a' => [
                                            'o' => [
                                                'id' => 1,
                                                'email' => 1,
                                                'first_name' => 1,
                                                'last_name' => 1,
                                                'display_name' => 1,
                                            ],
                                        ],
                                    ],
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'getUserGroup' => [
            'tag' => 'User groups',
            'summary' => 'Read a group with its members',
            'pathParams' => ['locationId', 'groupId'],
            'method' => 'GET',
            'path' => '/locations/{locationId}/user-groups/{groupId}',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'location_id' => 1,
                                    'group_name' => 1,
                                    'user_count' => 1,
                                    'members' => [
                                        'a' => [
                                            'o' => [
                                                'id' => 1,
                                                'email' => 1,
                                                'first_name' => 1,
                                                'last_name' => 1,
                                                'display_name' => 1,
                                            ],
                                        ],
                                    ],
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'updateUserGroup' => [
            'tag' => 'User groups',
            'summary' => 'Rename a group or set its members',
            'pathParams' => ['locationId', 'groupId'],
            'method' => 'PATCH',
            'path' => '/locations/{locationId}/user-groups/{groupId}',
            'query' => [],
            'body' => ['group_name', 'member_ids'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'location_id' => 1,
                                    'group_name' => 1,
                                    'user_count' => 1,
                                    'members' => [
                                        'a' => [
                                            'o' => [
                                                'id' => 1,
                                                'email' => 1,
                                                'first_name' => 1,
                                                'last_name' => 1,
                                                'display_name' => 1,
                                            ],
                                        ],
                                    ],
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'deleteUserGroup' => [
            'tag' => 'User groups',
            'summary' => 'Delete a group',
            'pathParams' => ['locationId', 'groupId'],
            'method' => 'DELETE',
            'path' => '/locations/{locationId}/user-groups/{groupId}',
            'query' => [
                'force' => [
                    'type' => 'boolean',
                ],
            ],
            'body' => null,
            'hasBody' => false,
            'resp' => 1,
        ],
        'getUserGroupMembership' => [
            'tag' => 'User groups',
            'summary' => 'A user\'s groups at a location',
            'pathParams' => ['locationId', 'userId'],
            'method' => 'GET',
            'path' => '/locations/{locationId}/users/{userId}/group-membership',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'id' => 1,
                                'name' => 1,
                                'is_member' => 1,
                                'product_keys' => [
                                    'a' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'setUserGroupMembership' => [
            'tag' => 'User groups',
            'summary' => 'Set a user\'s groups at a location',
            'pathParams' => ['locationId', 'userId'],
            'method' => 'PUT',
            'path' => '/locations/{locationId}/users/{userId}/group-membership',
            'query' => [],
            'body' => ['group_ids'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'id' => 1,
                                'name' => 1,
                                'is_member' => 1,
                                'product_keys' => [
                                    'a' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'listConnectorTypes' => [
            'tag' => 'Connectors',
            'summary' => 'List connector types',
            'pathParams' => [],
            'method' => 'GET',
            'path' => '/connector-types',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'key' => 1,
                                'name' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'testConnector' => [
            'tag' => 'Connectors',
            'summary' => 'Dry-run a connector',
            'pathParams' => ['locationId'],
            'method' => 'POST',
            'path' => '/locations/{locationId}/connectors/test',
            'query' => [],
            'body' => ['config', 'connector_id', 'type'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'success' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'listLocationConnectors' => [
            'tag' => 'Connectors',
            'summary' => 'List a location\'s connectors',
            'pathParams' => ['locationId'],
            'method' => 'GET',
            'path' => '/locations/{locationId}/connectors',
            'query' => [
                'search' => [
                    'type' => 'string',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['connecting', 'active', 'error', 'deactivating', 'inactive'],
                ],
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                ],
                'cursor' => [
                    'type' => 'string',
                ],
            ],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'id' => 1,
                                'location_id' => 1,
                                'location_name' => 1,
                                'name' => 1,
                                'type' => 1,
                                'type_display_name' => 1,
                                'status' => 1,
                                'status_display' => 1,
                                'status_message' => 1,
                                'state_reason' => 1,
                                'last_synced_at' => 1,
                                'created_at' => 1,
                                'updated_at' => 1,
                            ],
                        ],
                    ],
                    'pagination' => [
                        'o' => [
                            'has_more' => 1,
                            'next_cursor' => 1,
                            'total_count' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'createConnector' => [
            'tag' => 'Connectors',
            'summary' => 'Create a connector',
            'pathParams' => ['locationId'],
            'method' => 'POST',
            'path' => '/locations/{locationId}/connectors',
            'query' => [],
            'body' => ['config', 'name', 'type'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'id' => 1,
                            'location_id' => 1,
                            'name' => 1,
                            'type' => 1,
                            'type_display_name' => 1,
                            'config' => [
                                'o' => [
                                    'api_url' => 1,
                                    'domain' => 1,
                                ],
                            ],
                            'status' => 1,
                            'status_display' => 1,
                            'status_message' => 1,
                            'state_reason' => 1,
                            'last_synced_at' => 1,
                            'created_at' => 1,
                            'updated_at' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'getConnector' => [
            'tag' => 'Connectors',
            'summary' => 'Read a connector',
            'pathParams' => ['locationId', 'connectorId'],
            'method' => 'GET',
            'path' => '/locations/{locationId}/connectors/{connectorId}',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'id' => 1,
                            'location_id' => 1,
                            'name' => 1,
                            'type' => 1,
                            'type_display_name' => 1,
                            'config' => [
                                'o' => [
                                    'api_url' => 1,
                                    'domain' => 1,
                                ],
                            ],
                            'status' => 1,
                            'status_display' => 1,
                            'status_message' => 1,
                            'state_reason' => 1,
                            'last_synced_at' => 1,
                            'created_at' => 1,
                            'updated_at' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'updateConnector' => [
            'tag' => 'Connectors',
            'summary' => 'Update a connector',
            'pathParams' => ['locationId', 'connectorId'],
            'method' => 'PATCH',
            'path' => '/locations/{locationId}/connectors/{connectorId}',
            'query' => [],
            'body' => ['config', 'name'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'id' => 1,
                            'location_id' => 1,
                            'name' => 1,
                            'type' => 1,
                            'type_display_name' => 1,
                            'config' => [
                                'o' => [
                                    'api_url' => 1,
                                    'domain' => 1,
                                ],
                            ],
                            'status' => 1,
                            'status_display' => 1,
                            'status_message' => 1,
                            'state_reason' => 1,
                            'last_synced_at' => 1,
                            'created_at' => 1,
                            'updated_at' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'deactivateConnector' => [
            'tag' => 'Connectors',
            'summary' => 'Stop a connector',
            'pathParams' => ['locationId', 'connectorId'],
            'method' => 'POST',
            'path' => '/locations/{locationId}/connectors/{connectorId}/deactivate',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'id' => 1,
                            'location_id' => 1,
                            'name' => 1,
                            'type' => 1,
                            'type_display_name' => 1,
                            'config' => [
                                'o' => [
                                    'api_url' => 1,
                                    'domain' => 1,
                                ],
                            ],
                            'status' => 1,
                            'status_display' => 1,
                            'status_message' => 1,
                            'state_reason' => 1,
                            'last_synced_at' => 1,
                            'created_at' => 1,
                            'updated_at' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'reactivateConnector' => [
            'tag' => 'Connectors',
            'summary' => 'Restart a connector',
            'pathParams' => ['locationId', 'connectorId'],
            'method' => 'POST',
            'path' => '/locations/{locationId}/connectors/{connectorId}/reactivate',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'id' => 1,
                            'location_id' => 1,
                            'name' => 1,
                            'type' => 1,
                            'type_display_name' => 1,
                            'config' => [
                                'o' => [
                                    'api_url' => 1,
                                    'domain' => 1,
                                ],
                            ],
                            'status' => 1,
                            'status_display' => 1,
                            'status_message' => 1,
                            'state_reason' => 1,
                            'last_synced_at' => 1,
                            'created_at' => 1,
                            'updated_at' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'listCompanyConnectors' => [
            'tag' => 'Connectors',
            'summary' => 'List a company\'s connectors',
            'pathParams' => ['companyId'],
            'method' => 'GET',
            'path' => '/companies/{companyId}/connectors',
            'query' => [
                'search' => [
                    'type' => 'string',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['connecting', 'active', 'error', 'deactivating', 'inactive'],
                ],
                'type' => [
                    'type' => 'string',
                    'enum' => ['netsapiens'],
                ],
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                ],
                'cursor' => [
                    'type' => 'string',
                ],
            ],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'id' => 1,
                                'location_id' => 1,
                                'location_name' => 1,
                                'name' => 1,
                                'type' => 1,
                                'type_display_name' => 1,
                                'status' => 1,
                                'status_display' => 1,
                                'status_message' => 1,
                                'state_reason' => 1,
                                'last_synced_at' => 1,
                                'created_at' => 1,
                                'updated_at' => 1,
                            ],
                        ],
                    ],
                    'pagination' => [
                        'o' => [
                            'has_more' => 1,
                            'next_cursor' => 1,
                            'total_count' => 1,
                        ],
                    ],
                    'errors' => [
                        'a' => [
                            'o' => [
                                'id' => 1,
                                'name' => 1,
                                'type' => 1,
                                'location_name' => 1,
                                'status_message' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'listAlertRules' => [
            'tag' => 'First Alert',
            'summary' => 'List a location\'s alert rules',
            'pathParams' => ['locationId'],
            'method' => 'GET',
            'path' => '/locations/{locationId}/first-alert/rules',
            'query' => [
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                ],
                'cursor' => [
                    'type' => 'string',
                ],
            ],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'type' => 1,
                                'attributes' => [
                                    'o' => [
                                        'id' => 1,
                                        'location_id' => 1,
                                        'name' => 1,
                                        'priority' => 1,
                                        'match_logic' => 1,
                                        'enabled' => 1,
                                        'is_default' => 1,
                                        'triggers' => [
                                            'a' => [
                                                'o' => [
                                                    'id' => 1,
                                                    'trigger_type' => 1,
                                                    'config' => [
                                                        'o' => [
                                                            'operator' => 1,
                                                            'threshold' => 1,
                                                            'keywords' => [
                                                                'a' => 1,
                                                            ],
                                                            'match_logic' => 1,
                                                            'topics' => [
                                                                'a' => 1,
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                        'trigger_types' => [
                                            'a' => 1,
                                        ],
                                        'groups' => [
                                            'a' => [
                                                'o' => [
                                                    'id' => 1,
                                                    'group_name' => 1,
                                                ],
                                            ],
                                        ],
                                        'created_at' => 1,
                                        'updated_at' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'pagination' => [
                        'o' => [
                            'has_more' => 1,
                            'next_cursor' => 1,
                            'total_count' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'createAlertRule' => [
            'tag' => 'First Alert',
            'summary' => 'Create an alert rule',
            'pathParams' => ['locationId'],
            'method' => 'POST',
            'path' => '/locations/{locationId}/first-alert/rules',
            'query' => [],
            'body' => ['enabled', 'group_ids', 'match_logic', 'name', 'priority', 'triggers'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'location_id' => 1,
                                    'name' => 1,
                                    'priority' => 1,
                                    'match_logic' => 1,
                                    'enabled' => 1,
                                    'is_default' => 1,
                                    'triggers' => [
                                        'a' => [
                                            'o' => [
                                                'id' => 1,
                                                'trigger_type' => 1,
                                                'config' => [
                                                    'o' => [
                                                        'operator' => 1,
                                                        'threshold' => 1,
                                                        'keywords' => [
                                                            'a' => 1,
                                                        ],
                                                        'match_logic' => 1,
                                                        'topics' => [
                                                            'a' => 1,
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    'trigger_types' => [
                                        'a' => 1,
                                    ],
                                    'groups' => [
                                        'a' => [
                                            'o' => [
                                                'id' => 1,
                                                'group_name' => 1,
                                            ],
                                        ],
                                    ],
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'getAlertRule' => [
            'tag' => 'First Alert',
            'summary' => 'Read an alert rule',
            'pathParams' => ['locationId', 'ruleId'],
            'method' => 'GET',
            'path' => '/locations/{locationId}/first-alert/rules/{ruleId}',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'location_id' => 1,
                                    'name' => 1,
                                    'priority' => 1,
                                    'match_logic' => 1,
                                    'enabled' => 1,
                                    'is_default' => 1,
                                    'triggers' => [
                                        'a' => [
                                            'o' => [
                                                'id' => 1,
                                                'trigger_type' => 1,
                                                'config' => [
                                                    'o' => [
                                                        'operator' => 1,
                                                        'threshold' => 1,
                                                        'keywords' => [
                                                            'a' => 1,
                                                        ],
                                                        'match_logic' => 1,
                                                        'topics' => [
                                                            'a' => 1,
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    'trigger_types' => [
                                        'a' => 1,
                                    ],
                                    'groups' => [
                                        'a' => [
                                            'o' => [
                                                'id' => 1,
                                                'group_name' => 1,
                                            ],
                                        ],
                                    ],
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'updateAlertRule' => [
            'tag' => 'First Alert',
            'summary' => 'Update an alert rule',
            'pathParams' => ['locationId', 'ruleId'],
            'method' => 'PATCH',
            'path' => '/locations/{locationId}/first-alert/rules/{ruleId}',
            'query' => [],
            'body' => ['enabled', 'group_ids', 'match_logic', 'name', 'priority', 'triggers'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'location_id' => 1,
                                    'name' => 1,
                                    'priority' => 1,
                                    'match_logic' => 1,
                                    'enabled' => 1,
                                    'is_default' => 1,
                                    'triggers' => [
                                        'a' => [
                                            'o' => [
                                                'id' => 1,
                                                'trigger_type' => 1,
                                                'config' => [
                                                    'o' => [
                                                        'operator' => 1,
                                                        'threshold' => 1,
                                                        'keywords' => [
                                                            'a' => 1,
                                                        ],
                                                        'match_logic' => 1,
                                                        'topics' => [
                                                            'a' => 1,
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    'trigger_types' => [
                                        'a' => 1,
                                    ],
                                    'groups' => [
                                        'a' => [
                                            'o' => [
                                                'id' => 1,
                                                'group_name' => 1,
                                            ],
                                        ],
                                    ],
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'deleteAlertRule' => [
            'tag' => 'First Alert',
            'summary' => 'Delete an alert rule',
            'pathParams' => ['locationId', 'ruleId'],
            'method' => 'DELETE',
            'path' => '/locations/{locationId}/first-alert/rules/{ruleId}',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => 1,
        ],
        'duplicateAlertRule' => [
            'tag' => 'First Alert',
            'summary' => 'Copy an alert rule',
            'pathParams' => ['locationId', 'ruleId'],
            'method' => 'POST',
            'path' => '/locations/{locationId}/first-alert/rules/{ruleId}/duplicate',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'location_id' => 1,
                                    'name' => 1,
                                    'priority' => 1,
                                    'match_logic' => 1,
                                    'enabled' => 1,
                                    'is_default' => 1,
                                    'triggers' => [
                                        'a' => [
                                            'o' => [
                                                'id' => 1,
                                                'trigger_type' => 1,
                                                'config' => [
                                                    'o' => [
                                                        'operator' => 1,
                                                        'threshold' => 1,
                                                        'keywords' => [
                                                            'a' => 1,
                                                        ],
                                                        'match_logic' => 1,
                                                        'topics' => [
                                                            'a' => 1,
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    'trigger_types' => [
                                        'a' => 1,
                                    ],
                                    'groups' => [
                                        'a' => [
                                            'o' => [
                                                'id' => 1,
                                                'group_name' => 1,
                                            ],
                                        ],
                                    ],
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'getFirstAlertConfig' => [
            'tag' => 'First Alert',
            'summary' => 'Read monitored numbers and the alert webhook',
            'pathParams' => ['locationId'],
            'method' => 'GET',
            'path' => '/locations/{locationId}/first-alert/config',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'webhook_url' => 1,
                            'connector_mappings' => [
                                'a' => [
                                    'o' => [
                                        'connector_id' => 1,
                                        'connector_name' => 1,
                                        'connector_type' => 1,
                                        'monitored_numbers' => [
                                            'a' => 1,
                                        ],
                                        'id' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'saveFirstAlertConfig' => [
            'tag' => 'First Alert',
            'summary' => 'Set monitored numbers and the alert webhook',
            'pathParams' => ['locationId'],
            'method' => 'PUT',
            'path' => '/locations/{locationId}/first-alert/config',
            'query' => [],
            'body' => ['connector_mappings', 'webhook_url'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'webhook_url' => 1,
                            'connector_mappings' => [
                                'a' => [
                                    'o' => [
                                        'connector_id' => 1,
                                        'connector_name' => 1,
                                        'connector_type' => 1,
                                        'monitored_numbers' => [
                                            'a' => 1,
                                        ],
                                        'id' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'testFirstAlertWebhook' => [
            'tag' => 'First Alert',
            'summary' => 'Test the alert webhook',
            'pathParams' => ['locationId'],
            'method' => 'POST',
            'path' => '/locations/{locationId}/first-alert/config/test-webhook',
            'query' => [],
            'body' => ['url'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'success' => 1,
                            'status_code' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'getAlertNotificationPreferences' => [
            'tag' => 'First Alert',
            'summary' => 'Read a user\'s alert email and digest',
            'pathParams' => ['locationId', 'userId'],
            'method' => 'GET',
            'path' => '/locations/{locationId}/users/{userId}/first-alert/notification-preferences',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'emailNotifications' => [
                        'o' => [
                            'masterEnabled' => 1,
                            'alerts' => [
                                'a' => [
                                    'o' => [
                                        'alertId' => 1,
                                        'alertName' => 1,
                                        'trigger' => 1,
                                        'severity' => 1,
                                        'description' => 1,
                                        'emailEnabled' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'digest' => [
                        'o' => [
                            'enabled' => 1,
                            'frequency' => 1,
                            'sendTime' => 1,
                            'timezone' => 1,
                            'timeFormat' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'setAlertEmailPreferences' => [
            'tag' => 'First Alert',
            'summary' => 'Set a user\'s alert email',
            'pathParams' => ['locationId', 'userId'],
            'method' => 'PUT',
            'path' => '/locations/{locationId}/users/{userId}/first-alert/email-preferences',
            'query' => [],
            'body' => ['alerts', 'masterEnabled'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'emailNotifications' => [
                        'o' => [
                            'masterEnabled' => 1,
                            'alerts' => [
                                'a' => [
                                    'o' => [
                                        'alertId' => 1,
                                        'alertName' => 1,
                                        'trigger' => 1,
                                        'severity' => 1,
                                        'description' => 1,
                                        'emailEnabled' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'digest' => [
                        'o' => [
                            'enabled' => 1,
                            'frequency' => 1,
                            'sendTime' => 1,
                            'timezone' => 1,
                            'timeFormat' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'setAlertDigestPreferences' => [
            'tag' => 'First Alert',
            'summary' => 'Set a user\'s alert digest',
            'pathParams' => ['locationId', 'userId'],
            'method' => 'PUT',
            'path' => '/locations/{locationId}/users/{userId}/first-alert/digest-preferences',
            'query' => [],
            'body' => ['enabled', 'frequency', 'sendTime', 'timeFormat', 'timezone'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'emailNotifications' => [
                        'o' => [
                            'masterEnabled' => 1,
                            'alerts' => [
                                'a' => [
                                    'o' => [
                                        'alertId' => 1,
                                        'alertName' => 1,
                                        'trigger' => 1,
                                        'severity' => 1,
                                        'description' => 1,
                                        'emailEnabled' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'digest' => [
                        'o' => [
                            'enabled' => 1,
                            'frequency' => 1,
                            'sendTime' => 1,
                            'timezone' => 1,
                            'timeFormat' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'getInsightsHubSetup' => [
            'tag' => 'Insights Hub',
            'summary' => 'Read Insights Hub setup',
            'pathParams' => ['locationId'],
            'method' => 'GET',
            'path' => '/locations/{locationId}/insights-hub/setup',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'connector_mappings' => [
                                        'a' => [
                                            'o' => [
                                                'connector_id' => 1,
                                                'connector_name' => 1,
                                                'connector_type' => 1,
                                                'monitored_numbers' => [
                                                    'a' => 1,
                                                ],
                                            ],
                                        ],
                                    ],
                                    'group_access' => [
                                        'a' => 1,
                                    ],
                                    'available_connectors' => [
                                        'a' => [
                                            'o' => [
                                                'id' => 1,
                                                'name' => 1,
                                                'type' => 1,
                                            ],
                                        ],
                                    ],
                                    'groups' => [
                                        'a' => [
                                            'o' => [
                                                'id' => 1,
                                                'group_name' => 1,
                                                'has_access' => 1,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'saveInsightsHubSetup' => [
            'tag' => 'Insights Hub',
            'summary' => 'Save Insights Hub setup',
            'pathParams' => ['locationId'],
            'method' => 'POST',
            'path' => '/locations/{locationId}/insights-hub/setup',
            'query' => [],
            'body' => ['connector_mappings', 'group_access'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'connector_mappings' => [
                                        'a' => [
                                            'o' => [
                                                'connector_id' => 1,
                                                'connector_name' => 1,
                                                'connector_type' => 1,
                                                'monitored_numbers' => [
                                                    'a' => 1,
                                                ],
                                            ],
                                        ],
                                    ],
                                    'group_access' => [
                                        'a' => 1,
                                    ],
                                    'available_connectors' => [
                                        'a' => [
                                            'o' => [
                                                'id' => 1,
                                                'name' => 1,
                                                'type' => 1,
                                            ],
                                        ],
                                    ],
                                    'groups' => [
                                        'a' => [
                                            'o' => [
                                                'id' => 1,
                                                'group_name' => 1,
                                                'has_access' => 1,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'updateInsightsHubSetup' => [
            'tag' => 'Insights Hub',
            'summary' => 'Update Insights Hub setup',
            'pathParams' => ['locationId'],
            'method' => 'PATCH',
            'path' => '/locations/{locationId}/insights-hub/setup',
            'query' => [],
            'body' => ['connector_mappings', 'group_access'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'connector_mappings' => [
                                        'a' => [
                                            'o' => [
                                                'connector_id' => 1,
                                                'connector_name' => 1,
                                                'connector_type' => 1,
                                                'monitored_numbers' => [
                                                    'a' => 1,
                                                ],
                                            ],
                                        ],
                                    ],
                                    'group_access' => [
                                        'a' => 1,
                                    ],
                                    'available_connectors' => [
                                        'a' => [
                                            'o' => [
                                                'id' => 1,
                                                'name' => 1,
                                                'type' => 1,
                                            ],
                                        ],
                                    ],
                                    'groups' => [
                                        'a' => [
                                            'o' => [
                                                'id' => 1,
                                                'group_name' => 1,
                                                'has_access' => 1,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'getInsightsHubConfig' => [
            'tag' => 'Insights Hub',
            'summary' => 'Read Insights Hub coverage',
            'pathParams' => ['locationId'],
            'method' => 'GET',
            'path' => '/locations/{locationId}/insights-hub/config',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'monitor_all_numbers' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'updateInsightsHubConfig' => [
            'tag' => 'Insights Hub',
            'summary' => 'Set Insights Hub coverage',
            'pathParams' => ['locationId'],
            'method' => 'PATCH',
            'path' => '/locations/{locationId}/insights-hub/config',
            'query' => [],
            'body' => ['monitor_all_numbers'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'monitor_all_numbers' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'getUserInsightsHubAccess' => [
            'tag' => 'Insights Hub',
            'summary' => 'Check a user\'s Insights Hub access',
            'pathParams' => ['locationId', 'userId'],
            'method' => 'GET',
            'path' => '/locations/{locationId}/users/{userId}/insights-hub/access',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'enabled' => 1,
                                    'group_name' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'listCompanyUsers' => [
            'tag' => 'Users',
            'summary' => 'List a company\'s users',
            'pathParams' => ['companyId'],
            'method' => 'GET',
            'path' => '/companies/{companyId}/users',
            'query' => [
                'search' => [
                    'type' => 'string',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['invited', 'active', 'deactivated'],
                ],
                'role' => [
                    'type' => 'string',
                    'enum' => ['customer_admin', 'frontline_user'],
                ],
                'location_id' => [
                    'type' => 'array',
                    'maxItems' => 100,
                    'itemFormat' => 'uuid',
                ],
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                ],
                'cursor' => [
                    'type' => 'string',
                ],
            ],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'type' => 1,
                                'attributes' => [
                                    'o' => [
                                        'id' => 1,
                                        'company_id' => 1,
                                        'reseller_id' => 1,
                                        'email' => 1,
                                        'first_name' => 1,
                                        'last_name' => 1,
                                        'display_name' => 1,
                                        'phone_number' => 1,
                                        'department' => 1,
                                        'status' => 1,
                                        'role' => 1,
                                        'auth_method' => 1,
                                        'invited_at' => 1,
                                        'invite_expires_at' => 1,
                                        'activated_at' => 1,
                                        'last_login_at' => 1,
                                        'locations' => [
                                            'a' => [
                                                'o' => [
                                                    'location_id' => 1,
                                                    'assigned_at' => 1,
                                                ],
                                            ],
                                        ],
                                        'created_at' => 1,
                                        'updated_at' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'pagination' => [
                        'o' => [
                            'has_more' => 1,
                            'next_cursor' => 1,
                            'total_count' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'createCompanyUser' => [
            'tag' => 'Users',
            'summary' => 'Create a user',
            'pathParams' => ['companyId'],
            'method' => 'POST',
            'path' => '/companies/{companyId}/users',
            'query' => [],
            'body' => ['auth_method', 'email', 'first_name', 'last_name', 'location_ids', 'phone_number', 'role', 'send_invitation'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'company_id' => 1,
                                    'reseller_id' => 1,
                                    'email' => 1,
                                    'first_name' => 1,
                                    'last_name' => 1,
                                    'display_name' => 1,
                                    'phone_number' => 1,
                                    'department' => 1,
                                    'status' => 1,
                                    'role' => 1,
                                    'auth_method' => 1,
                                    'invited_at' => 1,
                                    'invite_expires_at' => 1,
                                    'activated_at' => 1,
                                    'last_login_at' => 1,
                                    'locations' => [
                                        'a' => [
                                            'o' => [
                                                'location_id' => 1,
                                                'assigned_at' => 1,
                                            ],
                                        ],
                                    ],
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'getCompanyUser' => [
            'tag' => 'Users',
            'summary' => 'Read a user',
            'pathParams' => ['companyId', 'userId'],
            'method' => 'GET',
            'path' => '/companies/{companyId}/users/{userId}',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'company_id' => 1,
                                    'reseller_id' => 1,
                                    'email' => 1,
                                    'first_name' => 1,
                                    'last_name' => 1,
                                    'display_name' => 1,
                                    'phone_number' => 1,
                                    'department' => 1,
                                    'status' => 1,
                                    'role' => 1,
                                    'auth_method' => 1,
                                    'invited_at' => 1,
                                    'invite_expires_at' => 1,
                                    'activated_at' => 1,
                                    'last_login_at' => 1,
                                    'locations' => [
                                        'a' => [
                                            'o' => [
                                                'location_id' => 1,
                                                'assigned_at' => 1,
                                            ],
                                        ],
                                    ],
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'updateCompanyUser' => [
            'tag' => 'Users',
            'summary' => 'Update a user, role or locations',
            'pathParams' => ['companyId', 'userId'],
            'method' => 'PATCH',
            'path' => '/companies/{companyId}/users/{userId}',
            'query' => [],
            'body' => ['department', 'first_name', 'last_name', 'location_ids', 'phone_number', 'role'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'company_id' => 1,
                                    'reseller_id' => 1,
                                    'email' => 1,
                                    'first_name' => 1,
                                    'last_name' => 1,
                                    'display_name' => 1,
                                    'phone_number' => 1,
                                    'department' => 1,
                                    'status' => 1,
                                    'role' => 1,
                                    'auth_method' => 1,
                                    'invited_at' => 1,
                                    'invite_expires_at' => 1,
                                    'activated_at' => 1,
                                    'last_login_at' => 1,
                                    'locations' => [
                                        'a' => [
                                            'o' => [
                                                'location_id' => 1,
                                                'assigned_at' => 1,
                                            ],
                                        ],
                                    ],
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'deactivateCompanyUser' => [
            'tag' => 'Users',
            'summary' => 'Deactivate a user',
            'pathParams' => ['companyId', 'userId'],
            'method' => 'POST',
            'path' => '/companies/{companyId}/users/{userId}/deactivate',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'company_id' => 1,
                                    'reseller_id' => 1,
                                    'email' => 1,
                                    'first_name' => 1,
                                    'last_name' => 1,
                                    'display_name' => 1,
                                    'phone_number' => 1,
                                    'department' => 1,
                                    'status' => 1,
                                    'role' => 1,
                                    'auth_method' => 1,
                                    'invited_at' => 1,
                                    'invite_expires_at' => 1,
                                    'activated_at' => 1,
                                    'last_login_at' => 1,
                                    'locations' => [
                                        'a' => [
                                            'o' => [
                                                'location_id' => 1,
                                                'assigned_at' => 1,
                                            ],
                                        ],
                                    ],
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'reactivateCompanyUser' => [
            'tag' => 'Users',
            'summary' => 'Reactivate a user',
            'pathParams' => ['companyId', 'userId'],
            'method' => 'POST',
            'path' => '/companies/{companyId}/users/{userId}/reactivate',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'company_id' => 1,
                                    'reseller_id' => 1,
                                    'email' => 1,
                                    'first_name' => 1,
                                    'last_name' => 1,
                                    'display_name' => 1,
                                    'phone_number' => 1,
                                    'department' => 1,
                                    'status' => 1,
                                    'role' => 1,
                                    'auth_method' => 1,
                                    'invited_at' => 1,
                                    'invite_expires_at' => 1,
                                    'activated_at' => 1,
                                    'last_login_at' => 1,
                                    'locations' => [
                                        'a' => [
                                            'o' => [
                                                'location_id' => 1,
                                                'assigned_at' => 1,
                                            ],
                                        ],
                                    ],
                                    'created_at' => 1,
                                    'updated_at' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'resendInvitation' => [
            'tag' => 'Users',
            'summary' => 'Resend the invitation email',
            'pathParams' => ['companyId', 'userId'],
            'method' => 'POST',
            'path' => '/companies/{companyId}/users/{userId}/resend-invitation',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'user_id' => 1,
                                    'email' => 1,
                                    'invite_expires_at' => 1,
                                    'message' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'resetPassword' => [
            'tag' => 'Users',
            'summary' => 'Send a password reset email',
            'pathParams' => ['companyId', 'userId'],
            'method' => 'POST',
            'path' => '/companies/{companyId}/users/{userId}/reset-password',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'user_id' => 1,
                                    'email' => 1,
                                    'message' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'getUserNumberMappings' => [
            'tag' => 'Users',
            'summary' => 'Read a user\'s numbers',
            'pathParams' => ['companyId', 'userId'],
            'method' => 'GET',
            'path' => '/companies/{companyId}/users/{userId}/connector-mappings',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'connector_id' => 1,
                                'connector_name' => 1,
                                'connector_type' => 1,
                                'monitored_numbers' => [
                                    'a' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'setUserNumberMappings' => [
            'tag' => 'Users',
            'summary' => 'Set a user\'s numbers',
            'pathParams' => ['companyId', 'userId'],
            'method' => 'PUT',
            'path' => '/companies/{companyId}/users/{userId}/connector-mappings',
            'query' => [],
            'body' => ['mappings'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'connector_id' => 1,
                                'connector_name' => 1,
                                'connector_type' => 1,
                                'monitored_numbers' => [
                                    'a' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'listLocationUsers' => [
            'tag' => 'Users',
            'summary' => 'List a location\'s users with products and groups',
            'pathParams' => ['locationId'],
            'method' => 'GET',
            'path' => '/locations/{locationId}/users',
            'query' => [
                'search' => [
                    'type' => 'string',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['invited', 'active', 'deactivated'],
                ],
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                ],
                'cursor' => [
                    'type' => 'string',
                ],
            ],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'type' => 1,
                                'attributes' => [
                                    'o' => [
                                        'id' => 1,
                                        'company_id' => 1,
                                        'email' => 1,
                                        'first_name' => 1,
                                        'last_name' => 1,
                                        'display_name' => 1,
                                        'phone_number' => 1,
                                        'role' => 1,
                                        'status' => 1,
                                        'invited_at' => 1,
                                        'created_at' => 1,
                                        'location_id' => 1,
                                        'location_name' => 1,
                                        'assigned_at' => 1,
                                        'locations' => [
                                            'a' => [
                                                'o' => [
                                                    'location_id' => 1,
                                                    'assigned_at' => 1,
                                                ],
                                            ],
                                        ],
                                        'groups' => [
                                            'a' => 1,
                                        ],
                                        'enabled_products' => [
                                            'a' => 1,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'pagination' => [
                        'o' => [
                            'has_more' => 1,
                            'next_cursor' => 1,
                            'total_count' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'getLocationUser' => [
            'tag' => 'Users',
            'summary' => 'Read a user at a location',
            'pathParams' => ['locationId', 'userId'],
            'method' => 'GET',
            'path' => '/locations/{locationId}/users/{userId}',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'type' => 1,
                            'attributes' => [
                                'o' => [
                                    'id' => 1,
                                    'company_id' => 1,
                                    'email' => 1,
                                    'first_name' => 1,
                                    'last_name' => 1,
                                    'display_name' => 1,
                                    'phone_number' => 1,
                                    'role' => 1,
                                    'status' => 1,
                                    'invited_at' => 1,
                                    'created_at' => 1,
                                    'location_id' => 1,
                                    'location_name' => 1,
                                    'assigned_at' => 1,
                                    'locations' => [
                                        'a' => [
                                            'o' => [
                                                'location_id' => 1,
                                                'assigned_at' => 1,
                                            ],
                                        ],
                                    ],
                                    'groups' => [
                                        'a' => 1,
                                    ],
                                    'enabled_products' => [
                                        'a' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'getUserAfterCallSettings' => [
            'tag' => 'After Call settings',
            'summary' => 'Read a user\'s After Call settings',
            'pathParams' => ['locationId', 'userId'],
            'method' => 'GET',
            'path' => '/locations/{locationId}/users/{userId}/after-call-settings',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'user_id' => 1,
                            'eoc_enabled' => 1,
                            'eod_enabled' => 1,
                            'eod_timezone' => 1,
                            'eod_time' => 1,
                            'digest_frequency' => 1,
                            'digest_time_format' => 1,
                            'connector_mappings' => [
                                'a' => [
                                    'o' => [
                                        'connector_id' => 1,
                                        'connector_name' => 1,
                                        'connector_status' => 1,
                                        'monitored_numbers' => [
                                            'a' => 1,
                                        ],
                                    ],
                                ],
                            ],
                            'last_changed_by' => 1,
                            'last_changed_at' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'updateUserAfterCallSettings' => [
            'tag' => 'After Call settings',
            'summary' => 'Update a user\'s After Call settings',
            'pathParams' => ['locationId', 'userId'],
            'method' => 'PATCH',
            'path' => '/locations/{locationId}/users/{userId}/after-call-settings',
            'query' => [],
            'body' => ['connector_mappings', 'digest_frequency', 'digest_time_format', 'eoc_enabled', 'eod_enabled', 'eod_time', 'eod_timezone'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'user_id' => 1,
                            'eoc_enabled' => 1,
                            'eod_enabled' => 1,
                            'eod_timezone' => 1,
                            'eod_time' => 1,
                            'digest_frequency' => 1,
                            'digest_time_format' => 1,
                            'connector_mappings' => [
                                'a' => [
                                    'o' => [
                                        'connector_id' => 1,
                                        'connector_name' => 1,
                                        'connector_status' => 1,
                                        'monitored_numbers' => [
                                            'a' => 1,
                                        ],
                                    ],
                                ],
                            ],
                            'last_changed_by' => 1,
                            'last_changed_at' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'getMe' => [
            'tag' => 'Account',
            'summary' => 'Who is signed in',
            'pathParams' => [],
            'method' => 'GET',
            'path' => '/me',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'id' => 1,
                            'email' => 1,
                            'first_name' => 1,
                            'last_name' => 1,
                            'display_name' => 1,
                            'user_type' => 1,
                            'role' => 1,
                            'status' => 1,
                            'scopes' => [
                                'a' => 1,
                            ],
                            'reseller_id' => 1,
                            'company_id' => 1,
                            'location_ids' => [
                                'a' => 1,
                            ],
                            'enabled_products' => [
                                'a' => 1,
                            ],
                            'mfa_available' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'listVisibleLocations' => [
            'tag' => 'Account',
            'summary' => 'Locations this login can see',
            'pathParams' => [],
            'method' => 'GET',
            'path' => '/locations',
            'query' => [
                'search' => [
                    'type' => 'string',
                ],
                'is_active' => [
                    'type' => 'boolean',
                ],
                'company_id' => [
                    'type' => 'string',
                    'format' => 'uuid',
                ],
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                ],
                'cursor' => [
                    'type' => 'string',
                ],
                'offset' => [
                    'type' => 'integer',
                    'minimum' => 0,
                ],
            ],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'type' => 1,
                                'attributes' => [
                                    'o' => [
                                        'id' => 1,
                                        'company_id' => 1,
                                        'company_name' => 1,
                                        'reseller_name' => 1,
                                        'location_name' => 1,
                                        'street' => 1,
                                        'city' => 1,
                                        'state' => 1,
                                        'country' => 1,
                                        'zipcode' => 1,
                                        'support_contact_name' => 1,
                                        'support_contact_email' => 1,
                                        'support_contact_phone' => 1,
                                        'vertical_id' => 1,
                                        'vertical_name' => 1,
                                        'enabled_products' => [
                                            'a' => 1,
                                        ],
                                        'is_active' => 1,
                                        'user_count' => 1,
                                        'created_at' => 1,
                                        'updated_at' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'pagination' => [
                        'o' => [
                            'has_more' => 1,
                            'next_cursor' => 1,
                            'total_count' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'listCalls' => [
            'tag' => 'Calls',
            'summary' => 'List calls',
            'pathParams' => [],
            'method' => 'GET',
            'path' => '/calls',
            'query' => [
                'date_from' => [
                    'type' => 'string',
                    'format' => 'date-time',
                ],
                'date_to' => [
                    'type' => 'string',
                    'format' => 'date-time',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['completed', 'missed', 'voicemail', 'failed', 'cancelled'],
                ],
                'sentiment' => [
                    'type' => 'string',
                    'enum' => ['crisis', 'very_negative', 'negative', 'mildly_negative', 'below_neutral', 'neutral', 'positive', 'none'],
                ],
                'has_open_tasks' => [
                    'type' => 'string',
                    'enum' => ['true'],
                ],
                'multi_leg' => [
                    'type' => 'boolean',
                ],
                'location_id' => [
                    'type' => 'array',
                    'maxItems' => 100,
                    'itemFormat' => 'uuid',
                ],
                'company_id' => [
                    'type' => 'string',
                    'format' => 'uuid',
                ],
                'search' => [
                    'type' => 'string',
                    'minLength' => 2,
                ],
                'cursor' => [
                    'type' => 'string',
                ],
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                ],
            ],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'summary' => [
                                'o' => [
                                    'total_calls' => 1,
                                    'completed_calls' => 1,
                                    'missed_calls' => 1,
                                    'total_duration' => 1,
                                    'tasks_done' => 1,
                                    'pending' => 1,
                                    'total_tasks' => 1,
                                ],
                            ],
                            'calls' => [
                                'a' => [
                                    'o' => [
                                        'id' => 1,
                                        'location_id' => 1,
                                        'call_date' => 1,
                                        'caller_timezone' => 1,
                                        'caller_name' => 1,
                                        'caller_number' => 1,
                                        'callee_name' => 1,
                                        'callee_number' => 1,
                                        'call_type' => 1,
                                        'status' => 1,
                                        'sentiment' => 1,
                                        'sentiment_score' => 1,
                                        'sentiment_label' => 1,
                                        'summary' => 1,
                                        'top_highlight' => 1,
                                        'highlights' => [
                                            'a' => 1,
                                        ],
                                        'duration' => 1,
                                        'leg_count' => 1,
                                        'task_count' => 1,
                                        'completed_task_count' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'meta' => [
                        'o' => [
                            'has_more' => 1,
                            'next_cursor' => 1,
                            'limit' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'getCall' => [
            'tag' => 'Calls',
            'summary' => 'Read a call',
            'pathParams' => ['callId'],
            'method' => 'GET',
            'path' => '/calls/{callId}',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'id' => 1,
                            'call_date' => 1,
                            'caller_timezone' => 1,
                            'caller_name' => 1,
                            'caller_number' => 1,
                            'callee_name' => 1,
                            'callee_number' => 1,
                            'call_type' => 1,
                            'call_outcome' => 1,
                            'duration' => 1,
                            'leg_count' => 1,
                            'platform' => 1,
                            'sentiment' => 1,
                            'sentiment_score' => 1,
                            'sentiment_label' => 1,
                            'summary' => 1,
                            'analysis' => [
                                'o' => [
                                    'summary' => [
                                        'o' => [
                                            'text' => 1,
                                            'key_points' => [
                                                'a' => 1,
                                            ],
                                            'action_items' => [
                                                'a' => 1,
                                            ],
                                        ],
                                    ],
                                    'sentiment' => [
                                        'o' => [
                                            'overall' => 1,
                                        ],
                                    ],
                                    'topics' => [
                                        'a' => [
                                            'o' => [
                                                'name' => 1,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'vcon' => [
                                'o' => [
                                    'recording_url' => 1,
                                    'recording_url_expires_at' => 1,
                                    'recording_mime_type' => 1,
                                ],
                            ],
                            'tasks' => [
                                'a' => [
                                    'o' => [
                                        'id' => 1,
                                        'title' => 1,
                                        'description' => 1,
                                        'status' => 1,
                                        'source' => 1,
                                        'assignee_user' => [
                                            'o' => [
                                                'id' => 1,
                                                'display_name' => 1,
                                                'email' => 1,
                                            ],
                                        ],
                                        'due_date' => 1,
                                    ],
                                ],
                            ],
                            'task_count' => 1,
                            'has_more_tasks' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'getCallTranscript' => [
            'tag' => 'Calls',
            'summary' => 'Read a call\'s transcript',
            'pathParams' => ['callId'],
            'method' => 'GET',
            'path' => '/calls/{callId}/transcript',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'transcript' => [
                                'a' => [
                                    'o' => [
                                        'time' => 1,
                                        'speaker' => 1,
                                        'name' => 1,
                                        'text' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'listAlerts' => [
            'tag' => 'Alerts',
            'summary' => 'List alerts',
            'pathParams' => [],
            'method' => 'GET',
            'path' => '/alerts',
            'query' => [
                'date_from' => [
                    'type' => 'string',
                    'format' => 'date-time',
                ],
                'date_to' => [
                    'type' => 'string',
                    'format' => 'date-time',
                ],
                'keyword' => [
                    'type' => 'string',
                ],
                'topic' => [
                    'type' => 'string',
                ],
                'numbers' => [
                    'type' => 'array',
                    'maxItems' => 25,
                ],
                'sentiment' => [
                    'type' => 'string',
                    'enum' => ['crisis', 'very_negative', 'negative', 'mildly_negative', 'below_neutral', 'neutral', 'positive', 'none'],
                ],
                'search' => [
                    'type' => 'string',
                ],
                'location_id' => [
                    'type' => 'array',
                    'maxItems' => 100,
                    'itemFormat' => 'uuid',
                ],
                'company_id' => [
                    'type' => 'string',
                    'format' => 'uuid',
                ],
                'cursor' => [
                    'type' => 'string',
                ],
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                ],
            ],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'summary' => [
                                'o' => [
                                    'total' => 1,
                                ],
                            ],
                            'alerts' => [
                                'a' => [
                                    'o' => [
                                        'id' => 1,
                                        'call_id' => 1,
                                        'call_date' => 1,
                                        'caller_timezone' => 1,
                                        'caller_name' => 1,
                                        'caller_number' => 1,
                                        'callee_name' => 1,
                                        'callee_number' => 1,
                                        'summary' => 1,
                                        'sentiment' => 1,
                                        'sentiment_score' => 1,
                                        'sentiment_label' => 1,
                                        'keywords' => [
                                            'a' => 1,
                                        ],
                                        'topics' => [
                                            'a' => 1,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'meta' => [
                        'o' => [
                            'has_more' => 1,
                            'next_cursor' => 1,
                            'limit' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'getAlertsDashboard' => [
            'tag' => 'Alerts',
            'summary' => 'Alert totals',
            'pathParams' => [],
            'method' => 'GET',
            'path' => '/alerts/dashboard',
            'query' => [
                'date_from' => [
                    'type' => 'string',
                    'format' => 'date-time',
                ],
                'date_to' => [
                    'type' => 'string',
                    'format' => 'date-time',
                ],
                'location_id' => [
                    'type' => 'array',
                    'maxItems' => 100,
                    'itemFormat' => 'uuid',
                ],
                'company_id' => [
                    'type' => 'string',
                    'format' => 'uuid',
                ],
            ],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'kpis' => [
                                'o' => [
                                    'new_alerts' => 1,
                                    'monitored' => 1,
                                    'alert_rate' => 1,
                                ],
                            ],
                            'top_triggered' => [
                                'a' => [
                                    'o' => [
                                        'keyword' => 1,
                                        'count' => 1,
                                    ],
                                ],
                            ],
                            'recent_alerts' => [
                                'a' => [
                                    'o' => [
                                        'id' => 1,
                                        'call_id' => 1,
                                        'call_date' => 1,
                                        'caller_timezone' => 1,
                                        'caller_name' => 1,
                                        'caller_number' => 1,
                                        'callee_name' => 1,
                                        'callee_number' => 1,
                                        'summary' => 1,
                                        'sentiment' => 1,
                                        'sentiment_score' => 1,
                                        'sentiment_label' => 1,
                                        'keywords' => [
                                            'a' => 1,
                                        ],
                                        'topics' => [
                                            'a' => 1,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'listTasks' => [
            'tag' => 'Tasks',
            'summary' => 'List tasks',
            'pathParams' => [],
            'method' => 'GET',
            'path' => '/tasks',
            'query' => [
                'status' => [
                    'type' => 'string',
                    'enum' => ['pending', 'in_progress', 'done', 'overdue', 'open'],
                ],
                'today_start' => [
                    'type' => 'string',
                    'format' => 'date-time',
                ],
                'call_id' => [
                    'type' => 'string',
                    'format' => 'uuid',
                ],
                'task_id' => [
                    'type' => 'string',
                    'format' => 'uuid',
                ],
                'due_on' => [
                    'type' => 'string',
                    'format' => 'date',
                ],
                'date_from' => [
                    'type' => 'string',
                    'format' => 'date-time',
                ],
                'date_to' => [
                    'type' => 'string',
                    'format' => 'date-time',
                ],
                'search' => [
                    'type' => 'string',
                ],
                'location_id' => [
                    'type' => 'array',
                    'maxItems' => 100,
                    'itemFormat' => 'uuid',
                ],
                'company_id' => [
                    'type' => 'string',
                    'format' => 'uuid',
                ],
                'cursor' => [
                    'type' => 'string',
                ],
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                ],
            ],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'id' => 1,
                                'call_id' => 1,
                                'company_id' => 1,
                                'location_id' => 1,
                                'title' => 1,
                                'description' => 1,
                                'status' => 1,
                                'source' => 1,
                                'assignee_user' => [
                                    'o' => [
                                        'id' => 1,
                                        'display_name' => 1,
                                        'email' => 1,
                                    ],
                                ],
                                'due_date' => 1,
                                'completed_at' => 1,
                                'created_at' => 1,
                                'updated_at' => 1,
                            ],
                        ],
                    ],
                    'meta' => [
                        'o' => [
                            'has_more' => 1,
                            'next_cursor' => 1,
                            'limit' => 1,
                        ],
                    ],
                    'summary' => [
                        'o' => [
                            'total' => 1,
                            'done' => 1,
                            'in_progress' => 1,
                            'pending' => 1,
                            'overdue' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'getInsightsDashboard' => [
            'tag' => 'Insights',
            'summary' => 'Insights Hub dashboard',
            'pathParams' => [],
            'method' => 'GET',
            'path' => '/insights/dashboard',
            'query' => [
                'start' => [
                    'type' => 'string',
                    'format' => 'date-time',
                    'required' => true,
                ],
                'end' => [
                    'type' => 'string',
                    'format' => 'date-time',
                    'required' => true,
                ],
                'trending_limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 10,
                ],
                'location_id' => [
                    'type' => 'array',
                    'maxItems' => 100,
                    'itemFormat' => 'uuid',
                ],
                'company_id' => [
                    'type' => 'string',
                    'format' => 'uuid',
                ],
            ],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'stats' => [
                                'o' => [
                                    'total_calls' => 1,
                                    'answered_calls' => 1,
                                    'unanswered_calls' => 1,
                                ],
                            ],
                            'trending_topics' => [
                                'a' => [
                                    'o' => [
                                        'slug' => 1,
                                        'name' => 1,
                                        'call_count' => 1,
                                        'previous_call_count' => 1,
                                        'percentage_change' => 1,
                                        'trend_data' => [
                                            'a' => [
                                                'o' => [
                                                    'day' => 1,
                                                    'current' => 1,
                                                    'previous' => 1,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'topic_call_volumes' => [
                                'a' => [
                                    'o' => [
                                        'slug' => 1,
                                        'name' => 1,
                                        'call_count' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'listTopicCalls' => [
            'tag' => 'Insights',
            'summary' => 'Calls for one topic',
            'pathParams' => ['slug'],
            'method' => 'GET',
            'path' => '/insights/topics/{slug}/calls',
            'query' => [
                'date_from' => [
                    'type' => 'string',
                    'format' => 'date-time',
                ],
                'date_to' => [
                    'type' => 'string',
                    'format' => 'date-time',
                ],
                'sentiment' => [
                    'type' => 'string',
                    'enum' => ['positive', 'neutral', 'negative'],
                ],
                'outcome' => [
                    'type' => 'string',
                    'enum' => ['completed', 'missed', 'pending'],
                ],
                'search' => [
                    'type' => 'string',
                ],
                'location_id' => [
                    'type' => 'array',
                    'maxItems' => 100,
                    'itemFormat' => 'uuid',
                ],
                'company_id' => [
                    'type' => 'string',
                    'format' => 'uuid',
                ],
                'cursor' => [
                    'type' => 'string',
                ],
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                ],
            ],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'a' => [
                            'o' => [
                                'id' => 1,
                                'date' => 1,
                                'caller_name' => 1,
                                'summary' => 1,
                                'phone_number' => 1,
                                'sentiment' => 1,
                                'sentiment_label' => 1,
                                'outcome' => 1,
                            ],
                        ],
                    ],
                    'meta' => [
                        'o' => [
                            'total' => 1,
                            'has_more' => 1,
                            'next_cursor' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'getAfterCallWebhook' => [
            'tag' => 'Events and webhooks',
            'summary' => 'Read the After Call webhook',
            'pathParams' => ['locationId'],
            'method' => 'GET',
            'path' => '/locations/{locationId}/after-call/automation',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'id' => 1,
                            'location_id' => 1,
                            'webhook_url' => 1,
                            'enabled' => 1,
                            'created_at' => 1,
                            'updated_at' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'saveAfterCallWebhook' => [
            'tag' => 'Events and webhooks',
            'summary' => 'Set the After Call webhook',
            'pathParams' => ['locationId'],
            'method' => 'PUT',
            'path' => '/locations/{locationId}/after-call/automation',
            'query' => [],
            'body' => ['enabled', 'webhook_url'],
            'hasBody' => true,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'id' => 1,
                            'location_id' => 1,
                            'webhook_url' => 1,
                            'enabled' => 1,
                            'created_at' => 1,
                            'updated_at' => 1,
                        ],
                    ],
                ],
            ],
        ],
        'testAfterCallWebhook' => [
            'tag' => 'Events and webhooks',
            'summary' => 'Send a test After Call event',
            'pathParams' => ['locationId'],
            'method' => 'POST',
            'path' => '/locations/{locationId}/after-call/automation/test',
            'query' => [],
            'body' => null,
            'hasBody' => false,
            'resp' => [
                'o' => [
                    'data' => [
                        'o' => [
                            'success' => 1,
                            'http_status' => 1,
                            'response_time_ms' => 1,
                        ],
                    ],
                ],
            ],
        ],
    ];

    public const WEBHOOKS = [
        'afterCallSummary' => [
            'o' => [
                'version' => 1,
                'type' => 1,
                'is_test' => 1,
                'location_id' => 1,
                'timestamp' => 1,
                'call' => [
                    'o' => [
                        'id' => 1,
                        'caller_name' => 1,
                        'caller_number' => 1,
                        'callee_name' => 1,
                        'callee_number' => 1,
                        'call_type' => 1,
                        'duration_seconds' => 1,
                        'call_date' => 1,
                        'platform' => 1,
                    ],
                ],
                'summary' => [
                    'o' => [
                        'text' => 1,
                        'sentiment' => 1,
                        'key_points' => [
                            'a' => 1,
                        ],
                        'action_items' => [
                            'a' => 1,
                        ],
                    ],
                ],
                'location' => [
                    'o' => [
                        'id' => 1,
                        'company_id' => 1,
                        'connector_id' => 1,
                    ],
                ],
            ],
        ],
        'firstAlert' => 1,
    ];
}
