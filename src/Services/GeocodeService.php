<?php

declare(strict_types=1);

namespace TheWebmen\Elastica\Services;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;

final class GeocodeService
{
    use Injectable;
    private const BASE_URI = 'https://maps.googleapis.com/maps/api/';

    private CacheInterface $cache;

    private string $key;

    public function __construct(string $key)
    {
        $this->cache = Injector::inst()->get(CacheInterface::class . '.elasticaGeocode');
        $this->key = $key;
    }

    public function geocode(string $query): ?array
    {
        try {
            $data = $this->request($query);
        } catch (\Exception $e) {
            return null;
        }

        $location = $data['results'][0]['geometry']['location'];

        return [
            'lat' => $location['lat'],
            'lon' => $location['lng'],
        ];
    }

    private function request(string $query): ?array
    {
        $key = md5($query);

        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $data = file_get_contents(sprintf(
            '%sgeocode/json?key=%s&address=%s',
            self::BASE_URI,
            $this->key,
            urlencode($query),
        ));

        $data = json_decode($data, true);

        if ($data['status'] !== 'OK') {
            throw new \Exception('Geocode status not OK');
        }

        $this->cache->set($key, $data);

        return $data;
    }
}
