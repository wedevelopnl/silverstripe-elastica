<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Form;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;

class FilterForm extends Form
{
    public function __construct(Controller $controller, string $name, array $sorts, array $filters)
    {
        $fields = FieldList::create();

        if (count($sorts) > 1) {
            $fields->push(DropdownField::create('Sort', 'Sort', $sorts));
        }

        foreach ($filters as $filter) {
            $fields->push($filter->getFormField());
        }

        $actions = FieldList::create([
            FormAction::create('doSearch', 'Search')
                ->setName(''),
        ]);

        $validator = RequiredFields::create();

        parent::__construct($controller, $name, $fields, $actions, $validator);

        $this->loadDataFrom($controller->getRequest()->getVars());
        $this->setFormMethod('GET');
        $this->setFormAction($controller->getRequest()->getURL());
        $this->disableSecurityToken();
    }
}
