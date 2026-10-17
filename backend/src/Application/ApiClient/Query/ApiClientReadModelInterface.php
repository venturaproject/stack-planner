<?php

declare(strict_types=1);

namespace App\Application\ApiClient\Query;

use App\Domain\ApiClient\Entity\ApiClient;

interface ApiClientReadModelInterface
{
    /** @return list<ApiClient> */
    public function findAllClients(): array;
}
