<?php

declare(strict_types=1);

namespace App\Domain\Customer\Repository;

use App\Domain\Customer\Entity\Customer;
use App\Domain\Customer\ValueObject\Email;

interface CustomerRepositoryInterface
{
    public function findById(int $id): ?Customer;

    public function findByEmail(Email $email): ?Customer;

    public function save(Customer $customer): Customer;
}
