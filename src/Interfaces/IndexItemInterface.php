<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Interfaces;

use Elastica\Document;
use Elastica\Type\Mapping;
use SilverStripe\ORM\DataObject;

/**
 * @mixin DataObject
 */
interface IndexItemInterface
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function getElasticaFields(): array;

    public function getElasticaMapping(): Mapping;

    public function getElasticaId(): string;

    public function getElasticaDocument(): Document;

    public static function getIndexName(): string;

    /**
     * @return string[]
     */
    public static function getExtendedClasses(): array;
}
