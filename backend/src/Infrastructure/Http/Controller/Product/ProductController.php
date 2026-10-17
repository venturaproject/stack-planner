<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Product;

use App\Application\Product\Command\AdjustStockCommand;
use App\Application\Product\Command\CreateProductCommand;
use App\Application\Product\Command\RemoveProductImageCommand;
use App\Application\Product\Command\UpdateProductCommand;
use App\Application\Product\Command\UploadProductImageCommand;
use App\Application\Product\DTO\ProductResponse;
use App\Application\Product\Query\ExportProductsQuery;
use App\Application\Product\Query\GetProductByIdQuery;
use App\Application\Product\Query\ListProductsQuery;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Application\Shared\DTO\TabularExport;
use App\Infrastructure\Http\Export\XlsExporter;
use App\Infrastructure\Http\UploadedImageValidator;
use DateTimeImmutable;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/products')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ProductController extends AbstractController
{
    private const IMAGE_MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly UploadedImageValidator $uploadedImageValidator,
        private readonly string $mediaDir = '/app/var/media',
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $size = (int) ($request->query->get('size') ?? $request->query->get('per_page') ?? 20);

        return $this->json($this->queryBus->ask(new ListProductsQuery(
            page: max(1, (int) $request->query->get('page', 1)),
            size: max(1, min(100, $size)),
            search: $request->query->get('search'),
            stockLevel: $request->query->get('stockLevel') ?: null,
            createdFrom: self::parseDate($request->query->get('createdFrom'), '00:00:00'),
            createdTo: self::parseDate($request->query->get('createdTo'), '23:59:59'),
            stockStatus: $request->query->get('stockStatus') ?: null,
            priceMin: self::parseInt($request->query->get('priceMin')),
            priceMax: self::parseInt($request->query->get('priceMax')),
        )));
    }

    private static function parseInt(?string $value): ?int
    {
        return null !== $value && '' !== $value ? (int) $value : null;
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

    #[Route('/export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $all = filter_var($request->query->get('all'), \FILTER_VALIDATE_BOOLEAN);
        $ids = $this->exportIds($request, $all);
        /** @var TabularExport $export */
        $export = $this->queryBus->ask(new ExportProductsQuery(
            $ids,
            $request->query->get('search') ?: null,
            $request->query->get('stockLevel') ?: null,
            $all,
            self::parseDate($request->query->get('createdFrom'), '00:00:00'),
            self::parseDate($request->query->get('createdTo'), '23:59:59'),
            $request->query->get('stockStatus') ?: null,
            self::parseInt($request->query->get('priceMin')),
            self::parseInt($request->query->get('priceMax')),
        ));
        if ($export->limitExceeded) {
            throw new UnprocessableEntityHttpException('The export exceeds the 10,000-row limit. Narrow the filters and try again.');
        }

        return XlsExporter::respond(
            ['SKU', 'Name', 'Description', 'Price', 'Currency', 'Stock'],
            $export->rows,
            'products.xls',
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

    #[Route('/{id}', methods: ['GET'])]
    public function getById(int $id): JsonResponse
    {
        return $this->json($this->queryBus->ask(new GetProductByIdQuery($id)));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateProductCommand $cmd): JsonResponse
    {
        return $this->json($this->commandBus->dispatch($cmd), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload] UpdateProductCommand $cmd): JsonResponse
    {
        $cmd->id = $id;

        return $this->json($this->commandBus->dispatch($cmd));
    }

    #[Route('/{id}/stock-adjustments', methods: ['POST'])]
    public function adjustStock(int $id, #[MapRequestPayload] AdjustStockCommand $cmd): JsonResponse
    {
        $cmd->id = $id;

        return $this->json($this->commandBus->dispatch($cmd));
    }

    #[Route('/{id}/image', methods: ['GET'])]
    public function getImage(int $id): BinaryFileResponse
    {
        /** @var ProductResponse $product */
        $product = $this->queryBus->ask(new GetProductByIdQuery($id));
        if (null === $product->imageFilename) {
            throw new NotFoundHttpException();
        }

        $path = $this->mediaDir.'/products/'.$product->imageFilename;
        if (!is_file($path)) {
            throw new NotFoundHttpException();
        }

        $ext = strtolower(pathinfo($path, \PATHINFO_EXTENSION));

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', self::IMAGE_MIME_TYPES[$ext] ?? 'application/octet-stream');
        $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');

        return $response;
    }

    #[Route('/{id}/image', methods: ['POST'])]
    public function uploadImage(int $id, Request $request): JsonResponse
    {
        $file = $request->files->get('image');
        if (!$file instanceof UploadedFile) {
            return $this->json(['message' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }
        $extension = $this->uploadedImageValidator->extension($file);
        if (null === $extension) {
            return $this->json(['message' => 'Image must be a supported image and at most 8 MB'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->commandBus->dispatch(
            new UploadProductImageCommand($id, $file->getPathname(), $extension)
        ));
    }

    #[Route('/{id}/image', methods: ['DELETE'])]
    public function removeImage(int $id): JsonResponse
    {
        return $this->json($this->commandBus->dispatch(new RemoveProductImageCommand($id)));
    }
}
