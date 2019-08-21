<?php

namespace TheWebmen\Elastica\Traits;

use SilverStripe\ORM\DataObject;

/**
 * @property FilterIndexItemTrait owner
 * @mixin DataObject
 */
trait FilterIndexItemTrait
{
    public function getElasticaType()
    {
        return $this->owner->baseClass();
    }

    public function getElasticaFields()
    {
        $fields = [
            'ID' => ['type' => 'integer']
        ];

        if (method_exists($this->owner, 'updateElasticaFields')) {
            $this->owner->updateElasticaFields($fields);
        }

        $this->owner->extend('updateElasticaFields', $fields);

        return $fields;
    }

    public function getElasticaMapping()
    {
        $mapping = new \Elastica\Type\Mapping();
        $mapping->setProperties($this->getElasticaFields());
        $mapping->setParam('date_detection', false);

        return $mapping;
    }

    public function getElasticaDocument()
    {
        $data = [
            'ID' => $this->owner->ID
        ];

        if (method_exists($this->owner, 'updateElasticaDocumentData')) {
            $this->owner->updateElasticaDocumentData($data);
        }

        $this->owner->extend('updateElasticaDocumentData', $data);

        return new \Elastica\Document($this->owner->getElasticaId(), $data, $this->owner->getElasticaType());
    }

    public function getElasticaId()
    {
        return implode('_', [$this->owner->ClassName, $this->owner->ID]);
    }
}
