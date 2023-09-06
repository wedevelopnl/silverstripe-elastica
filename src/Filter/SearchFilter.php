<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Filter;

use Elastica\Query\AbstractQuery;
use Elastica\Query\MultiMatch;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;

class SearchFilter extends Filter
{
    /** @config */
    private static string $singular_name = 'Search';

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->dataFieldByName('FieldName')->setIsMultiple(true);
        });

        return parent::getCMSFields();
    }

    public function createFormField(): FormField
    {
        return TextField::create($this->Name, $this->Label);
    }

    public function createQuery(): ?AbstractQuery
    {
        if (!$this->getFormField()->Value()) {
            return null;
        }

        $match = new MultiMatch();
        $match->setFields($this->FieldName ? explode(',', $this->FieldName) : []);
        $match->setQuery($this->getFormField()->Value());

        return $match;
    }
}
