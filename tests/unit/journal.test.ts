import { describe, expect, it } from 'vitest';
import { readMinutes } from '~/lib/format';
import { journalBodyHtml } from '~/lib/journal';

describe('journalBodyHtml', () => {
  it('turns the Markdown subset into paragraphs, headings and lists', () => {
    expect(journalBodyHtml('Intro line\nsecond line\n\n## Care\n- Vacuum weekly\n- Blot spills\nAfter list')).toBe(
      '<p>Intro line<br>second line</p><h2>Care</h2><ul><li>Vacuum weekly</li><li>Blot spills</li></ul><p>After list</p>',
    );
  });

  it('escapes HTML so post bodies can never inject markup', () => {
    expect(journalBodyHtml('<script>alert(1)</script>\n\n## <b>x</b>\n- "quoted" & \'single\''))
      .toBe('<p>&lt;script&gt;alert(1)&lt;/script&gt;</p><h2>&lt;b&gt;x&lt;/b&gt;</h2><ul><li>&quot;quoted&quot; &amp; &#39;single&#39;</li></ul>');
  });

  it('handles Windows line endings', () => {
    expect(journalBodyHtml('One\r\n\r\nTwo')).toBe('<p>One</p><p>Two</p>');
  });
});

describe('readMinutes', () => {
  it('assumes 200 words a minute, at least one', () => {
    expect(readMinutes('word')).toBe(1);
    expect(readMinutes(Array(401).fill('w').join(' '))).toBe(3);
  });
});
