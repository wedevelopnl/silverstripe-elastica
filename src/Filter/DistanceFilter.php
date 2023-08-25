<?php

declare(strict_types=1);

namespace WeDevelop\Elastica\Filter;

use Elastica\Query\AbstractQuery;
use Elastica\Query\GeoDistance;
use Geocoder\Exception\CollectionIsEmpty;
use Geocoder\Geocoder;
use Geocoder\Query\GeocodeQuery;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\GridField\GridField_ActionMenu;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use WeDevelop\Elastica\Filter\DistanceFilter\Option;
use WeDevelop\Elastica\Form\DistanceFilterField;

class DistanceFilter extends Filter
{
    /** @config */
    private static string $singular_name = 'Distance';

    /** @config */
    private static array $has_many = [
        'Options' => Option::class,
    ];

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            /** @var GridFieldConfig $config */
            $config = $fields->dataFieldByName('Options')->getConfig();

            $config
                ->removeComponentsByType([
                    GridFieldAddNewButton::class,
                    GridFieldAddExistingAutocompleter::class,
                    GridFieldDataColumns::class,
                    GridField_ActionMenu::class,
                    GridFieldEditButton::class,
                    GridFieldDeleteAction::class,
                ])
                ->addComponents(
                    GridFieldEditableColumns::create(),
                    GridFieldAddNewInlineButton::create(),
                    GridFieldOrderableRows::create(),
                    GridFieldDeleteAction::create()
                );
        });

        return parent::getCMSFields();
    }

    public function createFormField(): FormField
    {
        return DistanceFilterField::create($this->Name, $this->Name, $this->Name, $this->Options()->map('Value', 'Label')->toArray());
    }

    public function createQuery(): ?AbstractQuery
    {
        $value = $this->getFormField()->Value();

        if (empty($value['Address']) || empty($value['Distance'])) {
            return null;
        }

        try {
            $location = $this->getLocation($value['Address']);
        } catch (CollectionIsEmpty) {
            $this->field->getAddressField()->setMessage('Address not found');

            return null;
        }

        return new GeoDistance(
            $this->FieldName,
            $location,
            $value['Distance'],
        );
    }

    private function getLocation(string $address): array
    {
        /** @var Geocoder $geocoder */
        $geocoder = Injector::inst()->get(Geocoder::class);
        $result = $geocoder->geocodeQuery(GeocodeQuery::create($address));
        $coordinates = $result->first()->getCoordinates();

        return [
            'lat' => $coordinates->getLatitude(),
            'lon' => $coordinates->getLongitude(),
        ];
    }
}
