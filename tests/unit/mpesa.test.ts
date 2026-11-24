import { describe, expect, it } from 'vitest';
import { makeReference } from '~/lib/format';
import { mpesaPassword, mpesaTimestamp, normalisePhone, parseStkCallback } from '~/lib/mpesa';

describe('normalisePhone', () => {
  it.each([
    ['0712345678', '254712345678'],
    ['0712 345 678', '254712345678'],
    ['+254 712-345-678', '254712345678'],
    ['712345678', '254712345678'],
    ['0110123456', '254110123456'],
    ['(254) 712345678', '254712345678'],
  ])('%s → %s', (input, expected) => expect(normalisePhone(input)).toBe(expected));

  it.each(['0612345678', '12345', '2547123456789', 'not a phone', ''])('rejects %s', (input) => {
    expect(normalisePhone(input)).toBeNull();
  });
});

describe('Daraja credentials', () => {
  it('uses Nairobi time for the timestamp', () => {
    // 2026-11-23 09:05:07 UTC is 12:05:07 in Nairobi.
    expect(mpesaTimestamp(Date.UTC(2026, 10, 23, 9, 5, 7))).toBe('20261123120507');
  });

  it('builds the password as base64(shortcode + passkey + timestamp)', () => {
    expect(mpesaPassword('174379', 'abc', '20261123120507')).toBe(btoa('174379abc20261123120507'));
  });
});

describe('parseStkCallback', () => {
  it('reads a successful callback', () => {
    const parsed = parseStkCallback({ Body: { stkCallback: {
      MerchantRequestID: 'm1', CheckoutRequestID: 'ws_1', ResultCode: 0, ResultDesc: 'Processed',
      CallbackMetadata: { Item: [{ Name: 'Amount', Value: 5362 }, { Name: 'MpesaReceiptNumber', Value: 'TKL123' }, { Name: 'PhoneNumber', Value: 254712345678 }] },
    } } });
    expect(parsed).toEqual({ checkoutRequestId: 'ws_1', merchantRequestId: 'm1', resultCode: 0, resultDesc: 'Processed', amount: 5362, receipt: 'TKL123', phone: '254712345678' });
  });

  it('reads a cancelled callback without metadata', () => {
    expect(parseStkCallback({ Body: { stkCallback: { CheckoutRequestID: 'ws_2', ResultCode: 1032, ResultDesc: 'Cancelled by user' } } }))
      .toMatchObject({ resultCode: 1032, amount: null, receipt: null });
  });

  it('rejects malformed bodies', () => {
    expect(parseStkCallback(null)).toBeNull();
    expect(parseStkCallback({ Body: {} })).toBeNull();
  });
});

describe('makeReference', () => {
  it('is 12 characters, the M-Pesa AccountReference limit, with no ambiguous letters', () => {
    for (let i = 0; i < 200; i++) expect(makeReference('YF')).toMatch(/^YF[0-9ABCDEFGHJKMNPQRSTVWXYZ]{10}$/);
  });
});
