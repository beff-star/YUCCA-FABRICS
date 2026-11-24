// Enhances the class booking form (components/BookingForm.astro): live total, date chips, fresh times when
// the session length changes, the visitor's local time, and submission without a page reload.
import { actions } from 'astro:actions';
import { actionErrorMessage, notify } from './ui';

type DateSlots = { date: string; label: string; starts: { time: string; label: string }[] }[];

const NAIROBI = 'Africa/Nairobi';
const kes = (cents: number) => `KES ${Math.round(cents / 100).toLocaleString('en-KE')}`;
const visitorZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
const nairobiOffset = '+03:00';

export function setupBookingForm() {
  const form = document.querySelector<HTMLFormElement>('[data-booking-form]');
  if (!form) return;
  const rates = JSON.parse(form.dataset.rates ?? '{}') as Record<string, number>;
  const slots = form.querySelector<HTMLElement>('[data-slots]')!;
  const chipClass = slots.dataset.chipClass ?? '';
  const courseField = form.querySelector('[data-course]');
  const courseSelect = courseField instanceof HTMLSelectElement ? courseField : null;
  const courseHidden = form.querySelector<HTMLInputElement>('input[type=hidden][name=course_slug]');
  const summary = (name: string) => form.querySelector<HTMLElement>(`[data-summary-${name}]`);

  const course = () => courseSelect?.value ?? courseHidden?.value ?? '';
  const hours = () => Number(form.querySelector<HTMLInputElement>('[data-hours]:checked')?.value ?? 1);
  const rate = () => rates[course()] ?? 0;

  function updateSummary() {
    if (courseSelect) summary('course')!.textContent = courseSelect.selectedOptions[0]?.textContent?.split(' · ')[0] ?? '';
    summary('total')!.textContent = kes(rate() * hours());
    form!.querySelectorAll<HTMLElement>('[data-hours-price]').forEach((el) => { el.textContent = kes(rate() * Number(el.dataset.hoursPrice)); });
    const platform = form!.querySelector<HTMLInputElement>('[data-platform]:checked');
    summary('platform')!.textContent = platform?.closest('label')?.querySelector('strong')?.textContent ?? '';
    const slot = form!.querySelector<HTMLInputElement>('input[name=slot]:checked')?.value;
    if (!slot) { summary('when')!.textContent = 'Choose a date and time'; summary('local')!.textContent = ''; return; }
    const start = new Date(`${slot}:00${nairobiOffset}`);
    const end = new Date(start.getTime() + hours() * 3_600_000);
    const fmt = (date: Date, zone: string, withDay = true) => date.toLocaleString('en-GB', { timeZone: zone, ...(withDay ? { weekday: 'short', day: 'numeric', month: 'short' } : {}), hour: 'numeric', minute: '2-digit', hour12: true });
    summary('when')!.textContent = `${fmt(start, NAIROBI)} – ${fmt(end, NAIROBI, false)} (EAT)`;
    summary('local')!.textContent = visitorZone && visitorZone !== NAIROBI ? `Your time: ${fmt(start, visitorZone)}` : '';
  }

  /** Shows one date's times at a time behind a row of date chips. */
  function activateDate(date: string) {
    slots.querySelectorAll<HTMLButtonElement>('[data-date-chip]').forEach((chip) => {
      const active = chip.dataset.dateChip === date;
      chip.setAttribute('aria-pressed', String(active));
      chip.classList.toggle('bg-forest', active); chip.classList.toggle('text-white', active); chip.classList.toggle('border-forest', active);
      chip.classList.toggle('bg-white', !active); chip.classList.toggle('border-ink/15', !active);
    });
    slots.querySelectorAll<HTMLElement>('[data-time-group]').forEach((group) => { group.hidden = group.dataset.timeGroup !== date; });
  }

  function enhanceSlots() {
    const chips = slots.querySelector<HTMLElement>('[data-date-chips]');
    if (!chips) return;
    chips.hidden = false;
    slots.querySelector('[data-time-groups]')?.classList.remove('max-h-[28rem]', 'overflow-y-auto');
    slots.querySelectorAll<HTMLElement>('[data-group-title]').forEach((title) => title.classList.add('sr-only'));
    chips.querySelectorAll<HTMLButtonElement>('[data-date-chip]').forEach((chip) => chip.addEventListener('click', () => activateDate(chip.dataset.dateChip!)));
    const checked = slots.querySelector<HTMLInputElement>('input[name=slot]:checked')?.value.slice(0, 10);
    activateDate(checked ?? chips.querySelector<HTMLElement>('[data-date-chip]')?.dataset.dateChip ?? '');
  }

  function renderSlots(dates: DateSlots) {
    const previous = form!.querySelector<HTMLInputElement>('input[name=slot]:checked')?.value;
    if (dates.length === 0) {
      slots.innerHTML = '';
      const empty = document.createElement('p');
      empty.className = 'rounded-2xl bg-cream p-4 text-sm text-gray-600';
      empty.textContent = 'No times are open for this length right now. Try a shorter session, or contact us to arrange one.';
      slots.appendChild(empty);
      updateSummary();
      return;
    }
    const chips = document.createElement('div');
    chips.className = '-mx-1 flex gap-2 overflow-x-auto px-1 pb-2';
    chips.dataset.dateChips = '';
    const groups = document.createElement('div');
    groups.className = 'grid gap-5';
    groups.dataset.timeGroups = '';
    for (const { date, label, starts } of dates) {
      const chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'shrink-0 rounded-2xl border border-ink/15 bg-white px-4 py-2 text-center text-sm transition hover:border-forest/50';
      chip.dataset.dateChip = date;
      const day = new Date(`${date}T12:00:00${nairobiOffset}`).toLocaleDateString('en-GB', { weekday: 'short', timeZone: NAIROBI });
      chip.innerHTML = '<span class="block text-[11px] uppercase tracking-wider opacity-80"></span><span class="block font-semibold"></span>';
      chip.children[0]!.textContent = day;
      chip.children[1]!.textContent = label.replace(/ \d{4}$/, '');
      chips.appendChild(chip);

      const group = document.createElement('div');
      group.setAttribute('role', 'radiogroup');
      group.setAttribute('aria-label', `Start times on ${label}`);
      group.dataset.timeGroup = date;
      const title = document.createElement('p');
      title.className = 'mb-2 text-sm font-semibold';
      title.dataset.groupTitle = '';
      title.textContent = label;
      const grid = document.createElement('div');
      grid.className = 'grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5';
      for (const { time, label: timeLabel } of starts) {
        const option = document.createElement('label');
        option.className = chipClass;
        const input = Object.assign(document.createElement('input'), { className: 'sr-only', type: 'radio', name: 'slot', value: `${date}T${time}`, required: true, checked: `${date}T${time}` === previous });
        const text = document.createElement('span');
        text.className = 'font-semibold';
        text.textContent = timeLabel;
        option.appendChild(input);
        option.appendChild(text);
        grid.appendChild(option);
      }
      group.appendChild(title);
      group.appendChild(grid);
      groups.appendChild(group);
    }
    slots.replaceChildren(chips, groups);
    enhanceSlots();
    updateSummary();
  }

  form.querySelectorAll('[data-hours]').forEach((input) => input.addEventListener('change', async () => {
    updateSummary();
    slots.setAttribute('aria-busy', 'true');
    const { data, error } = await actions.trainingSlots({ hours: hours() });
    slots.removeAttribute('aria-busy');
    if (error) { notify(actionErrorMessage(error), true); return; }
    renderSlots(data);
  }));
  form.addEventListener('change', (event) => {
    const target = event.target as HTMLInputElement;
    if (target.name === 'slot' || target.name === 'platform' || target.name === 'course_slug') updateSummary();
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = form.querySelector<HTMLButtonElement>('button:not([type=button])');
    if (button) button.disabled = true;
    try {
      const { data, error } = await actions.trainingBook(new FormData(form));
      if (error) throw error;
      window.location.assign(data.redirect);
    } catch (error) {
      notify(actionErrorMessage(error), true);
      if (button) button.disabled = false;
    }
  });

  const zone = form.querySelector('[data-local-zone]');
  if (zone && visitorZone && visitorZone !== NAIROBI) zone.textContent = `We’ll also show the time where you are (${visitorZone.replace(/_/g, ' ')}).`;
  enhanceSlots();
  updateSummary();
}
