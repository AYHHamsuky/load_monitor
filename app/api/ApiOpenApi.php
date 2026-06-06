<?php
/**
 * OpenAPI 3.0 specification — single source of truth.
 *
 * Returns a PHP array; the entry point JSON-encodes it for /openapi.json.
 * Keep this in sync with ApiRouter / ApiEndpoints when adding endpoints.
 */
class ApiOpenApi
{
    public static function spec(string $serverUrl): array
    {
        return [
            'openapi' => '3.0.3',
            'info'    => [
                'title'       => 'Kaduna Electric — Load Monitor API',
                'version'     => '1.0.0',
                'description' => "Read-only live API for the Load Reading Management System.\n\n"
                              . "All endpoints return a JSON envelope:\n\n"
                              . "```json\n{\n  \"success\": true,\n  \"data\": ...,\n  \"meta\": {...}\n}\n```\n\n"
                              . "Authentication is by bearer token in the `Authorization` header.\n"
                              . "Create a client by running `php sql/create_api_client.php \"<name>\"` inside the PHP container.",
                'contact' => ['name' => 'Kaduna Electric ICT'],
            ],
            'servers' => [['url' => $serverUrl]],
            'security' => [['BearerAuth' => []]],

            'tags' => [
                ['name' => 'Meta',       'description' => 'Liveness and identity'],
                ['name' => 'Reference',  'description' => 'Static reference data — locations, feeders'],
                ['name' => 'Readings',   'description' => 'Live hourly load readings'],
                ['name' => 'Interruptions','description' => 'Outage and interruption events'],
                ['name' => 'Late entries','description' => 'Late-submission audit log'],
                ['name' => 'Energy',     'description' => 'Aggregated energy figures (MWh)'],
                ['name' => 'Docs',       'description' => 'API documentation'],
            ],

            'paths' => self::paths(),

            'components' => [
                'securitySchemes' => [
                    'BearerAuth' => [
                        'type'        => 'http',
                        'scheme'      => 'bearer',
                        'description' => 'Bearer token obtained from `sql/create_api_client.php`.',
                    ],
                ],
                'parameters' => self::commonParams(),
                'schemas'    => self::schemas(),
                'responses'  => self::commonResponses(),
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    private static function paths(): array
    {
        return [

            '/health' => ['get' => [
                'tags'    => ['Meta'],
                'summary' => 'Liveness probe (no auth)',
                'security'=> [],
                'responses' => [
                    '200' => ['description' => 'OK',
                        'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/EnvelopeOk']]]],
                ],
            ]],

            '/me' => ['get' => [
                'tags'    => ['Meta'],
                'summary' => "The authenticated client's identity",
                'responses' => self::okOr401(['$ref' => '#/components/schemas/Client']),
            ]],

            '/openapi.json' => ['get' => [
                'tags'    => ['Docs'],
                'summary' => 'This OpenAPI 3.0 specification',
                'security'=> [],
                'responses' => ['200' => ['description' => 'OpenAPI spec',
                    'content' => ['application/json' => ['schema' => ['type' => 'object']]]]],
            ]],

            '/docs' => ['get' => [
                'tags'    => ['Docs'],
                'summary' => 'Interactive Swagger UI explorer',
                'security'=> [],
                'responses' => ['200' => ['description' => 'HTML',
                    'content' => ['text/html' => ['schema' => ['type' => 'string']]]]],
            ]],

            '/iss' => ['get' => [
                'tags'      => ['Reference'],
                'summary'   => 'List ISS (injection substation) locations',
                'parameters'=> [['name' => 'q', 'in' => 'query', 'schema' => ['type' => 'string'], 'description' => 'Substring match against iss_name / iss_code']],
                'responses' => self::okOr401(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Iss']]),
            ]],

            '/transmission-stations' => ['get' => [
                'tags' => ['Reference'],
                'summary' => 'List 33kV transmission stations',
                'responses' => self::okOr401(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/TransmissionStation']]),
            ]],

            '/area-offices' => ['get' => [
                'tags' => ['Reference'],
                'summary' => 'List Kaduna Electric area offices',
                'responses' => self::okOr401(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/AreaOffice']]),
            ]],

            '/feeders/11kv' => ['get' => [
                'tags'    => ['Reference'],
                'summary' => 'List 11kV feeders',
                'parameters' => [
                    ['$ref' => '#/components/parameters/IssCode'],
                    ['name' => 'band', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['A','B','C','D','E']]],
                ],
                'responses' => self::okOr401(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Feeder11kv']]),
            ]],

            '/feeders/33kv' => ['get' => [
                'tags'    => ['Reference'],
                'summary' => 'List 33kV feeders',
                'parameters' => [['$ref' => '#/components/parameters/TsCode']],
                'responses' => self::okOr401(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Feeder33kv']]),
            ]],

            '/readings/11kv' => ['get' => [
                'tags'    => ['Readings'],
                'summary' => 'Hourly 11kV load readings',
                'parameters' => [
                    ['$ref' => '#/components/parameters/Date'],
                    ['$ref' => '#/components/parameters/From'],
                    ['$ref' => '#/components/parameters/To'],
                    ['name' => 'feeder', 'in' => 'query', 'schema' => ['type' => 'string'], 'description' => '11kV feeder code (fdr11kv_code)'],
                    ['$ref' => '#/components/parameters/IssCode'],
                    ['$ref' => '#/components/parameters/Limit'],
                    ['$ref' => '#/components/parameters/Offset'],
                ],
                'responses' => self::okPaged(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Reading11kv']]),
            ]],

            '/readings/33kv' => ['get' => [
                'tags'    => ['Readings'],
                'summary' => 'Hourly 33kV load readings',
                'parameters' => [
                    ['$ref' => '#/components/parameters/Date'],
                    ['$ref' => '#/components/parameters/From'],
                    ['$ref' => '#/components/parameters/To'],
                    ['name' => 'feeder', 'in' => 'query', 'schema' => ['type' => 'string'], 'description' => '33kV feeder code (fdr33kv_code)'],
                    ['$ref' => '#/components/parameters/TsCode'],
                    ['$ref' => '#/components/parameters/Limit'],
                    ['$ref' => '#/components/parameters/Offset'],
                ],
                'responses' => self::okPaged(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Reading33kv']]),
            ]],

            '/interruptions/11kv' => ['get' => [
                'tags'    => ['Interruptions'],
                'summary' => '11kV interruption events',
                'parameters' => [
                    ['$ref' => '#/components/parameters/From'],
                    ['$ref' => '#/components/parameters/To'],
                    ['$ref' => '#/components/parameters/IssCode'],
                ],
                'responses' => self::okOr401(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Interruption']]),
            ]],

            '/interruptions/33kv' => ['get' => [
                'tags'    => ['Interruptions'],
                'summary' => '33kV interruption events',
                'parameters' => [
                    ['$ref' => '#/components/parameters/From'],
                    ['$ref' => '#/components/parameters/To'],
                    ['$ref' => '#/components/parameters/TsCode'],
                ],
                'responses' => self::okOr401(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Interruption']]),
            ]],

            '/late-entries' => ['get' => [
                'tags'    => ['Late entries'],
                'summary' => 'Late-entry explanation audit log',
                'parameters' => [
                    ['$ref' => '#/components/parameters/From'],
                    ['$ref' => '#/components/parameters/To'],
                    ['name' => 'voltage', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['11kV','33kV']]],
                    ['$ref' => '#/components/parameters/IssCode'],
                    ['$ref' => '#/components/parameters/Limit'],
                    ['$ref' => '#/components/parameters/Offset'],
                ],
                'responses' => self::okPaged(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/LateEntry']]),
            ]],

            '/energy/daily' => ['get' => [
                'tags'    => ['Energy'],
                'summary' => 'Daily total MWh (11kV + 33kV)',
                'parameters' => [
                    ['$ref' => '#/components/parameters/Date'],
                    ['$ref' => '#/components/parameters/From'],
                    ['$ref' => '#/components/parameters/To'],
                ],
                'responses' => self::okOr401(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/EnergyDaily']]),
            ]],

            '/energy/by-band' => ['get' => [
                'tags'    => ['Energy'],
                'summary' => 'Daily MWh grouped by feeder band (A–E)',
                'parameters' => [['$ref' => '#/components/parameters/Date']],
                'responses' => self::okOr401(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/EnergyByBand']]),
            ]],

            '/energy/by-area' => ['get' => [
                'tags'    => ['Energy'],
                'summary' => 'Daily MWh grouped by area office',
                'parameters' => [['$ref' => '#/components/parameters/Date']],
                'responses' => self::okOr401(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/EnergyByArea']]),
            ]],

            '/energy/hourly' => ['get' => [
                'tags'    => ['Energy'],
                'summary' => '24-hour breakdown for a single date',
                'parameters' => [['$ref' => '#/components/parameters/Date']],
                'responses' => self::okOr401(['type' => 'array', 'items' => ['$ref' => '#/components/schemas/EnergyHourly']]),
            ]],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    private static function commonParams(): array
    {
        return [
            'Date'    => ['name'=>'date',  'in'=>'query', 'schema'=>['type'=>'string','format'=>'date'], 'example'=>'2026-04-28', 'description'=>'Single date (YYYY-MM-DD).  Defaults to today.'],
            'From'    => ['name'=>'from',  'in'=>'query', 'schema'=>['type'=>'string','format'=>'date'], 'example'=>'2026-04-21'],
            'To'      => ['name'=>'to',    'in'=>'query', 'schema'=>['type'=>'string','format'=>'date'], 'example'=>'2026-04-28'],
            'Limit'   => ['name'=>'limit', 'in'=>'query', 'schema'=>['type'=>'integer','minimum'=>1,'maximum'=>5000,'default'=>1000]],
            'Offset'  => ['name'=>'offset','in'=>'query', 'schema'=>['type'=>'integer','minimum'=>0,'default'=>0]],
            'IssCode' => ['name'=>'iss',   'in'=>'query', 'schema'=>['type'=>'string'], 'description'=>'ISS code (iss_locations.iss_code)'],
            'TsCode'  => ['name'=>'ts',    'in'=>'query', 'schema'=>['type'=>'string'], 'description'=>'Transmission station code (transmission_stations.ts_code)'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    private static function schemas(): array
    {
        return [
            'EnvelopeOk' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean', 'example' => true],
                    'data'    => ['description' => 'Endpoint-specific payload'],
                    'meta'    => ['type' => 'object',
                        'properties' => [
                            'generated_at' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                        'additionalProperties' => true,
                    ],
                ],
                'required' => ['success', 'data', 'meta'],
            ],
            'EnvelopeError' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean', 'example' => false],
                    'error'   => ['type' => 'object',
                        'properties' => [
                            'code'    => ['type' => 'string', 'example' => 'UNAUTHORIZED'],
                            'message' => ['type' => 'string'],
                        ],
                        'required' => ['code', 'message'],
                    ],
                ],
                'required' => ['success', 'error'],
            ],
            'Client' => [
                'type' => 'object',
                'properties' => [
                    'id'         => ['type' => 'integer'],
                    'name'       => ['type' => 'string'],
                    'key_prefix' => ['type' => 'string'],
                    'scopes'     => ['type' => 'string'],
                ],
            ],
            'Iss' => [
                'type' => 'object',
                'properties' => [
                    'iss_code' => ['type' => 'string', 'example' => 'ABA01'],
                    'iss_name' => ['type' => 'string', 'example' => 'Abakpa'],
                ],
            ],
            'TransmissionStation' => [
                'type' => 'object',
                'properties' => [
                    'ts_code'      => ['type' => 'string'],
                    'station_name' => ['type' => 'string'],
                ],
            ],
            'AreaOffice' => [
                'type' => 'object',
                'properties' => [
                    'ao_id'   => ['type' => 'string'],
                    'ao_name' => ['type' => 'string'],
                ],
            ],
            'Feeder11kv' => [
                'type' => 'object',
                'properties' => [
                    'fdr11kv_code' => ['type' => 'string'],
                    'fdr11kv_name' => ['type' => 'string'],
                    'band'         => ['type' => 'string', 'enum' => ['A','B','C','D','E']],
                    'max_load'     => ['type' => 'number', 'format' => 'float'],
                    'iss_code'     => ['type' => 'string'],
                    'iss_name'     => ['type' => 'string'],
                    'fdr33kv_code' => ['type' => 'string', 'nullable' => true],
                    'ao_code'      => ['type' => 'string', 'nullable' => true],
                ],
            ],
            'Feeder33kv' => [
                'type' => 'object',
                'properties' => [
                    'fdr33kv_code' => ['type' => 'string'],
                    'fdr33kv_name' => ['type' => 'string'],
                    'ts_code'      => ['type' => 'string'],
                    'station_name' => ['type' => 'string'],
                    'max_load'     => ['type' => 'number', 'format' => 'float'],
                ],
            ],
            'Reading11kv' => [
                'type' => 'object',
                'properties' => [
                    'entry_date'   => ['type' => 'string', 'format' => 'date'],
                    'entry_hour'   => ['type' => 'integer', 'minimum' => 0, 'maximum' => 23],
                    'fdr11kv_code' => ['type' => 'string'],
                    'fdr11kv_name' => ['type' => 'string'],
                    'band'         => ['type' => 'string'],
                    'iss_code'     => ['type' => 'string'],
                    'iss_name'     => ['type' => 'string'],
                    'load_read'    => ['type' => 'number', 'format' => 'float', 'description' => 'Load in MW'],
                    'fault_code'   => ['type' => 'string', 'nullable' => true],
                    'fault_remark' => ['type' => 'string', 'nullable' => true],
                    'user_id'      => ['type' => 'string', 'description' => 'Payroll ID of submitter'],
                    'timestamp'    => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Reading33kv' => [
                'type' => 'object',
                'properties' => [
                    'entry_date'   => ['type' => 'string', 'format' => 'date'],
                    'entry_hour'   => ['type' => 'integer'],
                    'fdr33kv_code' => ['type' => 'string'],
                    'fdr33kv_name' => ['type' => 'string'],
                    'ts_code'      => ['type' => 'string'],
                    'station_name' => ['type' => 'string'],
                    'load_read'    => ['type' => 'number'],
                    'fault_code'   => ['type' => 'string', 'nullable' => true],
                    'fault_remark' => ['type' => 'string', 'nullable' => true],
                    'user_id'      => ['type' => 'string'],
                    'timestamp'    => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Interruption' => [
                'type' => 'object',
                'description' => 'Schema mirrors interruptions_11kv / interruptions tables (additional fields may be present).',
                'properties' => [
                    'id'            => ['type' => 'integer'],
                    'datetime_out'  => ['type' => 'string', 'format' => 'date-time'],
                    'datetime_in'   => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'fault_code'    => ['type' => 'string', 'nullable' => true],
                    'remark'        => ['type' => 'string', 'nullable' => true],
                ],
                'additionalProperties' => true,
            ],
            'LateEntry' => [
                'type' => 'object',
                'properties' => [
                    'id'             => ['type' => 'integer'],
                    'voltage_level'  => ['type' => 'string', 'enum' => ['11kV','33kV']],
                    'log_date'       => ['type' => 'string', 'format' => 'date'],
                    'specific_hour'  => ['type' => 'integer', 'minimum' => 0, 'maximum' => 23],
                    'user_id'        => ['type' => 'string'],
                    'staff_name'     => ['type' => 'string', 'nullable' => true],
                    'iss_code'       => ['type' => 'string'],
                    'iss_name'       => ['type' => 'string', 'nullable' => true],
                    'explanation'    => ['type' => 'string'],
                    'logged_at'      => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'EnergyDaily' => [
                'type' => 'object',
                'properties' => [
                    'date'        => ['type' => 'string', 'format' => 'date'],
                    'mwh_11kv'    => ['type' => 'number', 'description' => 'Sum(load_read) on 11kV feeders for the day'],
                    'mwh_33kv'    => ['type' => 'number'],
                    'mwh_total'   => ['type' => 'number'],
                    'hours_11kv'  => ['type' => 'integer', 'description' => 'Distinct hours with at least one reading'],
                    'hours_33kv'  => ['type' => 'integer'],
                    'faults_11kv' => ['type' => 'integer'],
                    'faults_33kv' => ['type' => 'integer'],
                ],
            ],
            'EnergyByBand' => [
                'type' => 'object',
                'properties' => [
                    'band'           => ['type' => 'string'],
                    'feeders'        => ['type' => 'integer'],
                    'mwh'            => ['type' => 'number'],
                    'supply_cells'   => ['type' => 'integer', 'description' => 'Number of hour-cells with load > 0'],
                    'reading_cells'  => ['type' => 'integer'],
                ],
            ],
            'EnergyByArea' => [
                'type' => 'object',
                'properties' => [
                    'ao_id'   => ['type' => 'string'],
                    'ao_name' => ['type' => 'string'],
                    'feeders' => ['type' => 'integer'],
                    'mwh'     => ['type' => 'number'],
                ],
            ],
            'EnergyHourly' => [
                'type' => 'object',
                'properties' => [
                    'entry_hour'        => ['type' => 'integer'],
                    'mw_11kv'           => ['type' => 'number'],
                    'feeders_with_data' => ['type' => 'integer'],
                ],
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    private static function commonResponses(): array
    {
        return [
            'Unauthorized' => [
                'description' => 'Missing or invalid bearer token',
                'content'     => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/EnvelopeError']]],
            ],
            'BadRequest' => [
                'description' => 'Invalid query parameters',
                'content'     => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/EnvelopeError']]],
            ],
            'NotFound' => [
                'description' => 'Unknown endpoint',
                'content'     => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/EnvelopeError']]],
            ],
        ];
    }

    private static function okOr401(array $dataSchema): array
    {
        return [
            '200' => ['description' => 'Success',
                'content' => ['application/json' => ['schema' => [
                    'allOf' => [
                        ['$ref' => '#/components/schemas/EnvelopeOk'],
                        ['type' => 'object', 'properties' => ['data' => $dataSchema]],
                    ],
                ]]]],
            '400' => ['$ref' => '#/components/responses/BadRequest'],
            '401' => ['$ref' => '#/components/responses/Unauthorized'],
        ];
    }

    private static function okPaged(array $dataSchema): array
    {
        $r = self::okOr401($dataSchema);
        $r['200']['content']['application/json']['schema']['allOf'][1]['properties']['meta'] = [
            'type' => 'object',
            'properties' => [
                'from'   => ['type' => 'string', 'format' => 'date'],
                'to'     => ['type' => 'string', 'format' => 'date'],
                'limit'  => ['type' => 'integer'],
                'offset' => ['type' => 'integer'],
                'total'  => ['type' => 'integer'],
            ],
        ];
        return $r;
    }
}
