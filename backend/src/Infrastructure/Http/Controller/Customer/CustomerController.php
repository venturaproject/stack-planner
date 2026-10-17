<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Customer;

use App\Application\Customer\Command\RegisterCustomerCommand;
use App\Application\Customer\Command\UpdateCustomerAddressCommand;
use App\Application\Customer\Query\ExportCustomersQuery;
use App\Application\Customer\Query\GetCustomerByIdQuery;
use App\Application\Customer\Query\ListCustomersQuery;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Application\Shared\DTO\TabularExport;
use App\Infrastructure\Http\Export\XlsExporter;
use DateTimeImmutable;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/customers')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class CustomerController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    #[Route('/export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $all = filter_var($request->query->get('all'), \FILTER_VALIDATE_BOOLEAN);
        $ids = $this->exportIds($request, $all);
        $hasAddress = $request->query->get('hasAddress');
        /** @var TabularExport $export */
        $export = $this->queryBus->ask(new ExportCustomersQuery(
            $ids,
            $request->query->get('search') ?: null,
            null !== $hasAddress ? filter_var($hasAddress, \FILTER_VALIDATE_BOOLEAN) : null,
            $all,
            self::parseDate($request->query->get('createdFrom'), '00:00:00'),
            self::parseDate($request->query->get('createdTo'), '23:59:59'),
        ));
        if ($export->limitExceeded) {
            throw new UnprocessableEntityHttpException('The export exceeds the 10,000-row limit. Narrow the filters and try again.');
        }

        return XlsExporter::respond(['Customer', 'Name', 'Email', 'Street', 'City', 'Postal code', 'Country'], $export->rows, 'customers.xls');
    }

    /** @return int[] */
    private function exportIds(Request $request, bool $all): array
    {
        if ($all) {
            return [];
        }
        $ids = array_values(array_unique(array_filter(array_map(static fn (mixed $v): int => is_numeric($v) ? (int) $v : 0, $request->query->all('ids')), static fn (int $id): bool => $id > 0)));
        if ([] === $ids || \count($ids) > 100) {
            throw new BadRequestHttpException('Select between 1 and 100 records.');
        }

        return $ids;
    }

    private static function parseDate(?string $value, string $time): ?DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        try {
            return new DateTimeImmutable($value.' '.$time);
        } catch (Exception) {
            return null;
        }
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $size = (int) ($request->query->get('size') ?? $request->query->get('per_page') ?? 20);
        $hasAddress = $request->query->get('hasAddress');

        return $this->json($this->queryBus->ask(new ListCustomersQuery(
            page: max(1, (int) $request->query->get('page', 1)),
            size: max(1, min(100, $size)),
            search: $request->query->get('search'),
            hasAddress: null !== $hasAddress ? filter_var($hasAddress, \FILTER_VALIDATE_BOOLEAN) : null,
            createdFrom: self::parseDate($request->query->get('createdFrom'), '00:00:00'),
            createdTo: self::parseDate($request->query->get('createdTo'), '23:59:59'),
        )));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function getById(int $id): JsonResponse
    {
        return $this->json($this->queryBus->ask(new GetCustomerByIdQuery($id)));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] RegisterCustomerCommand $cmd): JsonResponse
    {
        return $this->json($this->commandBus->dispatch($cmd), Response::HTTP_CREATED);
    }

    #[Route('/{id}/address', methods: ['PATCH'])]
    public function updateAddress(int $id, #[MapRequestPayload] UpdateCustomerAddressCommand $cmd): JsonResponse
    {
        $cmd->id = $id;

        return $this->json($this->commandBus->dispatch($cmd));
    }
}
