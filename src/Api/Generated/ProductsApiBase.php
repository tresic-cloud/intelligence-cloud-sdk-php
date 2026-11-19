<?php

declare(strict_types=1);

namespace IntelligenceCloud\Api\Generated;

use IntelligenceCloud\Client;
use IntelligenceCloud\Page;

/** Products operations, generated from openapi.yaml. Do not edit by hand. */
class ProductsApiBase
{
    public function __construct(protected readonly Client $c)
    {
    }

    /** List products. `GET /products` @return array<string,mixed>|null */
    public function listProducts(): ?array
    {
        return $this->c->call('listProducts', [], [], null);
    }

    /** Company products. `GET /companies/{companyId}/products` @return array<string,mixed>|null */
    public function listCompanyProducts(string $companyId): ?array
    {
        return $this->c->call('listCompanyProducts', ['companyId' => $companyId], [], null);
    }

    /** A user's products. `GET /companies/{companyId}/users/{userId}/products` @return array<string,mixed>|null */
    public function listUserProducts(string $companyId, string $userId): ?array
    {
        return $this->c->call('listUserProducts', ['companyId' => $companyId, 'userId' => $userId], [], null);
    }

    /** Turn a product on for a user. `POST /locations/{locationId}/users/{userId}/products/{productKey}/enable` @return array<string,mixed>|null */
    public function enableUserProduct(string $locationId, string $userId, string $productKey): ?array
    {
        return $this->c->call('enableUserProduct', ['locationId' => $locationId, 'userId' => $userId, 'productKey' => $productKey], [], null);
    }

    /** Turn a product off for a user. `POST /locations/{locationId}/users/{userId}/products/{productKey}/disable` @return array<string,mixed>|null */
    public function disableUserProduct(string $locationId, string $userId, string $productKey): ?array
    {
        return $this->c->call('disableUserProduct', ['locationId' => $locationId, 'userId' => $userId, 'productKey' => $productKey], [], null);
    }
}
