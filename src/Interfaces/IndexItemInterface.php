<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Interfaces;

use Elastica\Document;
use Elastica\Mapping;
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

    /**
     * @param string[] $fields
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function fillSuggest(array $fields, array $data): array;
}
