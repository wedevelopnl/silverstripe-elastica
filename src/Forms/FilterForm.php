<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Forms;

use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FormField;
use TheWebmen\Elastica\Extensions\FilterPageControllerExtension;
use TheWebmen\Elastica\Interfaces\AggregatableFilterInterface;

/**
 * @method FilterPageControllerExtension getController()
 */
final class FilterForm extends Form
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

        foreach ($this->getController()->getFilters() as $filter) {
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

        $actions->push(FormAction::create('', 'Zoeken')->setAttribute('name', ''));
        $this->setActions($actions);

        $this->setFormMethod('GET');

        $this->setAttribute('action', $this->getController()->getRequest()->getUrl());

        $this->disableSecurityToken();
        $this->loadDataFrom($this->getController()->getRequest()->getVars());
    }
}
