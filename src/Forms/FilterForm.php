<?php

namespace WeDevelop\Elastica\Forms;

use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use WeDevelop\Elastica\Extensions\SearchableObjectExtension;
use WeDevelop\Elastica\Interfaces\AggregatableFilterInterface;
use WeDevelop\Elastica\Models\SearchResultList;

class FilterForm extends Form
{
    public function __construct(RequestHandler $controller, $name = self::DEFAULT_NAME)
    {
        $fields = FieldList::create();
        $actions = FieldList::create();

        parent::__construct($controller, $name, $fields, $actions);

        $sorts = $this->getController()->config()->get('sorts');
        if ($sorts) {
            $sortKeys = array_keys($sorts);
            $fields->push(DropdownField::create('sort', '', array_combine($sortKeys, $sortKeys)));
        }

        $filters = $this->getController()->getFilters();

        foreach ($filters as $filter) {
            $field = $filter->generateFilterField();
            $field->setFilter($filter);
            $filter->setFilterField($field);
            if ($field instanceof FormField) {
                $fields->push($field);
            }
        }

        $this->setFields($fields);

        $this->loadDataFrom($this->getController()->getRequest()->getVars());

        foreach ($this->getController()->getFilters() as $filter) {
            if (!$filter instanceof AggregatableFilterInterface) {
                continue;
            }

            $aggregation = $this->getController()
                ->getFilterList()
                ->getResultSet()
                ->getAggregation((string)$filter->ID);

            $filter->addAggregation($aggregation);
        }

        $actions->push(FormAction::create('', 'Zoeken')->setAttribute('name', '')->setInputType('button'));

        $this->setActions($actions);

        $this->setFormMethod('GET');

        $this->setAttribute('action', $this->getController()->getRequest()->getUrl());

        $this->disableSecurityToken();
        $this->loadDataFrom($this->getController()->getRequest()->getVars());
    }
}
