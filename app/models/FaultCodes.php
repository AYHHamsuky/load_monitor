<?php
/**
 * FaultCodes — shared helper that returns the master fault/interruption code
 * list from the `interruption_codes` table.  Used by both 11kV and 33kV
 * dashboards so the dropdowns stay in sync with the interruption logging
 * system.
 */
class FaultCodes
{
    private static ?array $cache = null;

    /**
     * @return array<int,array{code:string,description:string,type:string,group:string,body:string}>
     *         Ordered: Transient → Breaker Fault → Forced Outage → Limitation
     *         → Planned outage → Load shedding → others.  Within each group
     *         sorted by code.
     */
    public static function all(): array
    {
        if (self::$cache !== null) return self::$cache;

        $db = Database::connect();
        $rows = $db->query("
            SELECT interruption_code        AS code,
                   interruption_description AS description,
                   interruption_type        AS type,
                   interruption_group       AS `group`,
                   body_responsible         AS body
            FROM interruption_codes
            ORDER BY
                CASE interruption_group
                    WHEN 'By Transient Faults'                  THEN 1
                    WHEN 'Breaker Fault'                        THEN 2
                    WHEN 'Forced Outage'                        THEN 3
                    WHEN 'Limitation'                           THEN 4
                    WHEN 'Planned outage'                       THEN 5
                    WHEN 'Load shedding - Service Base Tariff'  THEN 6
                    ELSE 99
                END,
                interruption_code
        ")->fetchAll(PDO::FETCH_ASSOC);

        return self::$cache = $rows;
    }

    /**
     * Render a <select> dropdown of fault codes for forms.
     * Each <option>'s value is the code itself; the label shows
     * "CODE — Description (Body)".
     *
     * @param string $name      form field name
     * @param string $id        form field id
     * @param string $selected  currently selected code (if any)
     * @param array  $attrs     extra HTML attributes
     */
    public static function renderSelect(
        string $name,
        string $id = '',
        string $selected = '',
        array  $attrs = []
    ): string {
        $id = $id ?: $name;
        $attrPairs = '';
        foreach ($attrs as $k => $v) {
            $attrPairs .= ' ' . htmlspecialchars($k) . '="' . htmlspecialchars((string)$v) . '"';
        }

        $html  = '<select name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($id) . '"' . $attrPairs . '>';
        $html .= '<option value="">-- Select Fault Code --</option>';

        $currentGroup = null;
        foreach (self::all() as $r) {
            if ($r['group'] !== $currentGroup) {
                if ($currentGroup !== null) $html .= '</optgroup>';
                $html .= '<optgroup label="' . htmlspecialchars($r['group']) . '">';
                $currentGroup = $r['group'];
            }
            $sel = ($selected !== '' && $selected === $r['code']) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($r['code']) . '"' . $sel . '>'
                  .  htmlspecialchars($r['code']) . ' — ' . htmlspecialchars($r['description'])
                  .  ' (' . htmlspecialchars($r['body']) . ')'
                  .  '</option>';
        }
        if ($currentGroup !== null) $html .= '</optgroup>';
        $html .= '</select>';
        return $html;
    }

    /** True if $code is a known interruption_code. */
    public static function isValid(string $code): bool
    {
        foreach (self::all() as $r) {
            if ($r['code'] === $code) return true;
        }
        return false;
    }
}
