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
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionMenu;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\i18n\i18n;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use WeDevelop\Elastica\Filter\DistanceFilter\Option;
use WeDevelop\Elastica\Form\DistanceField;

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
            $options = $fields->dataFieldByName('Options');

            if ($options instanceof GridField) {
                $options
                    ->getConfig()
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
                        GridFieldDeleteAction::create(),
                    );

                $options->setDescription('Distance units can be found <a href="https://www.elastic.co/guide/en/elasticsearch/reference/current/api-conventions.html#distance-units" target="_blank">here</a>.');
            }
        });

        return parent::getCMSFields();
    }

    public function createFormField(): FormField
    {
        return DistanceField::create($this->Name, $this->Label, $this->Label, $this->Options()->map('Distance', 'Label')->toArray());
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
            $this->field->getAddressField()->setMessage(_t(self::class . '.NOT_FOUND', 'Address not found'));

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
        $query = GeocodeQuery::create($address)->withLocale($this->getLocale() ?: null);
        $result = $geocoder->geocodeQuery($query);
        $coordinates = $result->first()->getCoordinates();

        return [
            'lat' => $coordinates->getLatitude(),
            'lon' => $coordinates->getLongitude(),
        ];
    }

    private function getLocale(): ?string
    {
        $locale = i18n::getData()->langFromLocale(i18n::get_locale());

        $this->extend('updateLocale', $locale);

        return $locale;
    }
}
