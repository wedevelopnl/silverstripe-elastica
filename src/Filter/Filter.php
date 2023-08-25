<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Filter;

use Elastica\Query\AbstractQuery;
use Elastica\ResultSet;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataObject;
use SilverStripe\TagField\StringTagField;
use WeDevelop\Elastica\ORM\SearchList;

/**
 * @property string $Name
 * @property string $FieldName
 */
class Filter extends DataObject
{
    protected ?FormField $field = null;

    /** @config */
    private static string $table_name = 'WeDevelop_Elastica_Filter';

    /** @config */
    private static array $db = [
        'Name' => 'Varchar',
        'FieldName' => 'Varchar',
        'Sort' => 'Int',
    ];

    /** @config */
    private static array $has_one = [
        'Page' => SiteTree::class,
    ];

    /** @config */
    private static array $summary_fields = [
        'i18n_singular_name' => 'Type',
        'Name',
    ];

    /** @config */
    private static array $searchable_fields = [
        'Name',
    ];

    /** @config */
    private static string $default_sort = '"Sort" ASC';

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->removeByName(['PageID', 'Sort']);

            $fields->dataFieldByName('Name')->setDescription('Query parameter name');

            $readable = Injector::inst()->get($this->Page()->getReadable());
            $elasticaFields = $readable->getElasticaFields();

            $fields->addFieldsToTab('Root.Main', [
                StringTagField::create(
                    'FieldName',
                    'FieldName',
                    array_combine($elasticaFields, $elasticaFields)
                )
                    ->setCanCreate(false)
                    ->setIsMultiple(false)
                    ->setDescription('Elasticsearch field name'),
            ]);
        });

        return parent::getCMSFields();
    }

    public function getFormField(): FormField
    {
        if (!$this->field) {
            $this->field = $this->createFormField();
        }

        return $this->field;
    }

    public function createFormField(): FormField
    {
        user_error("Please implement a createFormField() on your Filter " . $this->ClassName, E_USER_ERROR);
    }

    public function createQuery(): ?AbstractQuery
    {
        user_error("Please implement a createQuery() on your Filter " . $this->ClassName, E_USER_ERROR);
    }

    public function alterList(SearchList $list, array $filters): SearchList
    {
        $query = $this->createQuery();
        if ($query) {
            $list = $list->addMust($query);
        }

        return $list;
    }

    public function applyContext(ResultSet $context): void
    {
    }

    public function canCreate($member = null, $context = []): bool
    {
        return $this->baseClass() !== $this->getClassName();
    }
}
