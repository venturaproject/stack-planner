<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Order;

use App\Application\Order\Command\AddOrderLineCommand;
use App\Application\Order\Command\CancelOrderCommand;
use App\Application\Order\Command\PlaceOrderCommand;
use App\Application\Order\Command\RemoveOrderLineCommand;
use App\Application\Order\Command\UpdateOrderLineQuantityCommand;
use App\Application\Order\Query\ExportOrdersQuery;
use App\Application\Order\Query\GetOrderByIdQuery;
use App\Application\Order\Query\ListOrdersQuery;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Application\Shared\DTO\TabularExport;
use App\Infrastructure\Http\Export\XlsExporter;
use App\Infrastructure\Http\Validation\ReadsJsonBody;
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

#[Route('/api/orders')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class OrderController extends AbstractController
{
    use ReadsJsonBody;

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $size = (int) ($request->query->get('size') ?? $request->query->get('per_page') ?? 20);
        $customerId = $request->query->get('customerId');

        return $this->json($this->queryBus->ask(new ListOrdersQuery(
            page: max(1, (int) $request->query->get('page', 1)),
            size: max(1, min(100, $size)),
            customerId: null !== $customerId ? (int) $customerId : null,
            status: $request->query->get('status') ?: null,
            createdFrom: self::parseDate($request->query->get('createdFrom'), '00:00:00'),
            createdTo: self::parseDate($request->query->get('createdTo'), '23:59:59'),
        )));
    }

    #[Route('/export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $all = filter_var($request->query->get('all'), \FILTER_VALIDATE_BOOLEAN);
        $ids = $this->exportIds($request, $all);
        $customerId = $request->query->get('customerId');
        /** @var TabularExport $export */
        $export = $this->queryBus->ask(new ExportOrdersQuery(
            $ids,
            null !== $customerId ? (int) $customerId : null,
            $request->query->get('status') ?: null,
            $all,
            self::parseDate($request->query->get('createdFrom'), '00:00:00'),
            self::parseDate($request->query->get('createdTo'), '23:59:59'),
        ));
        if ($export->limitExceeded) {
            throw new UnprocessableEntityHttpException('The export exceeds the 10,000-row limit. Narrow the filters and try again.');
        }

        return XlsExporter::respond(
            ['Order', 'Customer', 'Status', 'Total', 'Currency', 'Lines'],
            $export->rows,
            'orders.xls',
        );
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

    #[Route('/{id}', methods: ['GET'])]
    public function getById(int $id): JsonResponse
    {
        return $this->json($this->queryBus->ask(new GetOrderByIdQuery($id)));
    }

    #[Route('/lines', methods: ['POST'])]
    public function addLine(#[MapRequestPayload] AddOrderLineCommand $cmd): JsonResponse
    {
        return $this->json($this->commandBus->dispatch($cmd), Response::HTTP_CREATED);
    }

    #[Route('/{id}/lines/{lineId}', methods: ['PATCH'])]
    public function updateLineQuantity(int $id, int $lineId, Request $request): JsonResponse
    {
        $data = $this->decodeJsonBody($request);
        $quantity = $data['quantity'] ?? 0;

        return $this->json($this->commandBus->dispatch(
            new UpdateOrderLineQuantityCommand($id, $lineId, is_numeric($quantity) ? (int) $quantity : 0)
        ));
    }

    #[Route('/{id}/lines/{lineId}', methods: ['DELETE'])]
    public function removeLine(int $id, int $lineId): JsonResponse
    {
        return $this->json($this->commandBus->dispatch(new RemoveOrderLineCommand($id, $lineId)));
    }

    #[Route('/{id}/place', methods: ['POST'])]
    public function place(int $id): JsonResponse
    {
        return $this->json($this->commandBus->dispatch(new PlaceOrderCommand($id)));
    }

    #[Route('/{id}/cancel', methods: ['POST'])]
    public function cancel(int $id): JsonResponse
    {
        return $this->json($this->commandBus->dispatch(new CancelOrderCommand($id)));
    }
}
