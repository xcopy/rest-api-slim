<?php

declare(strict_types=1);

namespace App\Assert;

use Assert\Assert as BaseAssert;

class Assert extends BaseAssert
{
    protected static $assertionClass = Assertion::class;
}
