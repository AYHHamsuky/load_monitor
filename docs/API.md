# Load Monitor — Read-only Live API

Base URL: `https://loadreading.kadunaelectric.cloud/api/v1`

All endpoints return JSON.  Successful responses use this envelope:

```json
{
  "success": true,
  "data": { ... },
  "meta": { "generated_at": "2026-04-28T12:34:56+01:00", ... }
}
```

On error:

```json
{
  "success": false,
  "error": { "code": "INVALID_DATE", "message": "..." }
}
```

## Authentication

Send a bearer token in the `Authorization` header:

```bash
curl -H "Authorization: Bearer <token>" \
     https://loadreading.kadunaelectric.cloud/api/v1/me
```

Create a client (run once in the PHP container's terminal):

```bash
php /var/www/html/sql/create_api_client.php "Dashboard Production"
```

The token is printed **once** at creation time.  Only the SHA-256 hash is
stored.  If lost, mint a new one.

## Endpoints

| Path | Description |
|---|---|
| `GET /health` | Liveness probe (no auth). |
| `GET /me` | Returns the calling client's name / scopes. |
| `GET /iss` | List ISS locations.  `?q=` filters by name/code. |
| `GET /transmission-stations` | List 33kV transmission stations. |
| `GET /area-offices` | List area offices. |
| `GET /feeders/11kv` | 11kV feeders.  Filters: `?iss=<code>`, `?band=<A-E>`. |
| `GET /feeders/33kv` | 33kV feeders.  Filter: `?ts=<code>`. |
| `GET /readings/11kv` | Hourly 11kV load readings.  Filters: `?date=<YYYY-MM-DD>` or `?from=&to=`, `?feeder=<code>`, `?iss=<code>`, `?limit=` (max 5000), `?offset=`. |
| `GET /readings/33kv` | Hourly 33kV load readings.  Same filters with `?ts=` instead of `?iss=`. |
| `GET /interruptions/11kv` | 11kV interruption events.  Filters: `?from=&to=`, `?iss=`. |
| `GET /interruptions/33kv` | 33kV interruption events.  Filters: `?from=&to=`, `?ts=`. |
| `GET /late-entries` | Late-entry explanations.  Filters: `?from=&to=`, `?voltage=11kV\|33kV`, `?iss=`. |
| `GET /energy/daily` | Daily MWh totals (11kV + 33kV).  Filters: `?date=` or `?from=&to=`. |
| `GET /energy/by-band` | Daily MWh grouped by band (A–E).  Filter: `?date=`. |
| `GET /energy/by-area` | Daily MWh grouped by area office.  Filter: `?date=`. |
| `GET /energy/hourly` | 24-hour breakdown for a single date.  Filter: `?date=`. |

## Filters and defaults

- Dates: `YYYY-MM-DD`.  Defaults to today when omitted.
- Ranges: `from` ≤ `to`.  Use `?date=` for a single day shortcut.
- Pagination on list endpoints: `?limit=` (default 1000, max 5000), `?offset=` (default 0).
  `meta.total` always returns the full match count regardless of paging.

## Example calls

```bash
TOKEN="<your-token>"

# Health (no auth)
curl https://loadreading.kadunaelectric.cloud/api/v1/health

# All ISS locations
curl -H "Authorization: Bearer $TOKEN" \
     https://loadreading.kadunaelectric.cloud/api/v1/iss

# All 11kV feeders for the Abakpa ISS
curl -H "Authorization: Bearer $TOKEN" \
     "https://loadreading.kadunaelectric.cloud/api/v1/feeders/11kv?iss=ABA01"

# Yesterday's daily energy
curl -H "Authorization: Bearer $TOKEN" \
     "https://loadreading.kadunaelectric.cloud/api/v1/energy/daily?date=2026-04-27"

# 30-day window of late-entry explanations
curl -H "Authorization: Bearer $TOKEN" \
     "https://loadreading.kadunaelectric.cloud/api/v1/late-entries?from=2026-03-30&to=2026-04-28&limit=5000"

# Hourly readings for a specific feeder on a specific date
curl -H "Authorization: Bearer $TOKEN" \
     "https://loadreading.kadunaelectric.cloud/api/v1/readings/11kv?date=2026-04-27&feeder=F11001"
```

## Audit log

Every request — authenticated or not — is recorded in `api_request_log`
with: client_id, method, endpoint, query string, IP, HTTP status, and
response time in milliseconds.  Query this directly via the DB for
audits.

## Status codes

| Code | Meaning |
|---|---|
| 200 | Success. |
| 400 | Bad input (invalid date, invalid range, malformed param). |
| 401 | Missing / invalid bearer token. |
| 404 | Unknown endpoint path. |
| 405 | Non-GET method. |
| 500 | Server error (also logged to `logs/php_errors.log`). |
