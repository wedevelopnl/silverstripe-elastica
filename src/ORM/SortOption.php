<?php

declare(strict_types=1);

namespace App\ORM;

use App\Service\ElasticaService;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\Parsers\URLSegmentFilter;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use WeDevelop\Elastica\ORM\SortOptionRule;

class SortOption extends DataObject
{
    /** @config */
    private static array $db = [
        'Name' => 'Varchar',
        'URLSegment' => 'Varchar',
        'Sort' => 'Int',
    ];

    /** @config */
    private static array $has_one = [
        'Page' => SiteTree::class,
    ];

    /** @config */
    private static array $has_many = [
        'Rules' => SortOptionRule::class,
    ];

    private static string $default_sort = '"Sort" ASC';

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->removeByName(['PageID', 'Rules', 'Sort']);
            $fields->replaceField('URLSegment', $fields->dataFieldByName('URLSegment')->performReadonlyTransformation());

            $fields->addFieldsToTab('Root.Main', [
                GridField::create('Rules', 'Rules', $this->Rules(), GridFieldConfig_Base::create()
                    ->removeComponentsByType(GridFieldDataColumns::class)
                    ->addComponent(GridFieldOrderableRows::create())
                    ->addComponent(GridFieldEditableColumns::create()->setDisplayFields([
                        'FieldName' => [
                            'title' => 'FieldName',
                            'callback' => function ($record, $column, $grid) {
                                $readable = Injector::inst()->get($this->Page()->getReadable());
                                $elasticaFields = array_merge(
                                    ['_score' => '_score'],
                                    $readable->getElasticaFields(),
                                );

                                return DropdownField::create($column)
                                    ->setSource(array_combine($elasticaFields, $elasticaFields));
                            },
                        ],
                        'Direction' => [
                            'title' => 'Direction',
                            'callback' => function ($record, $column, $grid) {
                                return DropdownField::create($column)
                                    ->setSource(['ASC' => 'ASC', 'DESC' => 'DESC']);
                            },
                        ],
                    ]))
                    ->addComponent(GridFieldAddNewInlineButton::create())
                    ->addComponent(GridFieldDeleteAction::create()))
            ]);
        });

        return parent::getCMSFields();
    }

    public function onBeforeWrite(): void
    {
        parent::onBeforeWrite();

        $this->URLSegment = URLSegmentFilter::create()->filter($this->Name);
    }

    public function toArray(): array
    {
        return array_map(function (SortOptionRule $rule) {
            return $rule->toArray();
        }, $this->Rules()->toArray());
    }
}
