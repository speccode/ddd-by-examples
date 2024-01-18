<?php

declare(strict_types=1);

namespace Speccode\Availability\Domain\Exceptions;

use Exception;

class MustNotReleaseBlockedTimeFromPast extends Exception
{
}
