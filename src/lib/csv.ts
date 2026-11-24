/** CSV cell: quoted, with a leading quote on values a spreadsheet would run as a formula (CSV injection). */
const cell = (value: unknown) => {
  const text = value === null || value === undefined ? '' : String(value);
  const safe = /^[=+\-@\t\r]/.test(text) ? `'${text}` : text;
  return `"${safe.replace(/"/g, '""')}"`;
};

export function csvResponse(filename: string, header: string[], rows: unknown[][]) {
  // BOM so Excel opens UTF-8 (é, –) correctly.
  const body = `﻿${[header, ...rows].map((row) => row.map(cell).join(',')).join('\r\n')}\r\n`;
  return new Response(body, {
    headers: { 'Content-Type': 'text/csv; charset=utf-8', 'Content-Disposition': `attachment; filename="${filename}"`, 'Cache-Control': 'private, no-store' },
  });
}
