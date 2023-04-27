<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Interfaces;

use Elastica\Query\AbstractQuery;

interface FilterInterface
{
    public function getElasticaQuery(): ?AbstractQuery;

    public function generateFilterField(): FilterFieldInterface;
}
