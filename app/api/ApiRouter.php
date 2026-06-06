<?php
/**
 * Maps URL paths to ApiEndpoints handler methods.
 * Returns ['endpoint' => 'name', 'handler' => callable] or null.
 */
class ApiRouter
{
    private const TABLE = [
        // path                            => [endpoint-name,             method on ApiEndpoints]
        'health'                            => ['health',                 'health'],
        'me'                                => ['me',                     'me'],

        'iss'                               => ['iss',                    'iss'],
        'transmission-stations'             => ['transmission-stations',  'transmissionStations'],
        'area-offices'                      => ['area-offices',           'areaOffices'],

        'feeders/11kv'                      => ['feeders.11kv',           'feeders11kv'],
        'feeders/33kv'                      => ['feeders.33kv',           'feeders33kv'],

        'readings/11kv'                     => ['readings.11kv',          'readings11kv'],
        'readings/33kv'                     => ['readings.33kv',          'readings33kv'],

        'interruptions/11kv'                => ['interruptions.11kv',     'interruptions11kv'],
        'interruptions/33kv'                => ['interruptions.33kv',     'interruptions33kv'],

        'late-entries'                      => ['late-entries',           'lateEntries'],

        'energy/daily'                      => ['energy.daily',           'energyDaily'],
        'energy/by-band'                    => ['energy.by-band',         'energyByBand'],
        'energy/by-area'                    => ['energy.by-area',         'energyByArea'],
        'energy/hourly'                     => ['energy.hourly',          'energyHourly'],
    ];

    public static function resolve(string $path): ?array
    {
        $path = trim($path, '/');
        $row  = self::TABLE[$path] ?? null;
        if (!$row) return null;
        return [
            'endpoint' => $row[0],
            'handler'  => ['ApiEndpoints', $row[1]],
        ];
    }

    public static function knownPaths(): array
    {
        return array_keys(self::TABLE);
    }
}
