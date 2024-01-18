<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\Repositories;

use Speccode\Availability\Domain\Exceptions\ResourceNotFound;
use Speccode\Kernel\Domain\ValueObjects\Identities\AggregateId;
use Speccode\Kernel\Domain\ValueObjects\Identities\BatchId;

interface BatchRepository
{
    /**
     * @param BatchId $batchId
     * @return AggregateId
     * @throws ResourceNotFound
     */
    public function findResourceByBatchId(BatchId $batchId): AggregateId;
}
