// Site-wide behaviour, ported from the PHP site's main.js. Every feature is optional: pages work without JS.
import { actions } from 'astro:actions';
import { actionErrorMessage, notify, updateCartCount } from './ui';

const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function setupMenuAndSearch() {
  const toggle = document.getElementById('menu-toggle');
  const mobile = document.getElementById('mobile-nav');
  const searchToggle = document.querySelector<HTMLElement>('[data-search-toggle]');
  const searchPanel = document.getElementById('site-search');

  const setMenu = (open: boolean) => {
    if (!toggle || !mobile) return;
    mobile.classList.toggle('hidden', !open);
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    toggle.querySelector('[data-menu-icon="closed"]')?.classList.toggle('hidden', open);
    toggle.querySelector('[data-menu-icon="open"]')?.classList.toggle('hidden', !open);
  };
  const setSearch = (open: boolean) => {
    if (!searchToggle || !searchPanel) return;
    searchPanel.classList.toggle('hidden', !open);
    searchToggle.setAttribute('aria-expanded', String(open));
    if (open) searchPanel.querySelector('input')?.focus();
  };
  toggle?.addEventListener('click', () => { setMenu(toggle.getAttribute('aria-expanded') !== 'true'); setSearch(false); });
  searchToggle?.addEventListener('click', () => { setSearch(searchToggle.getAttribute('aria-expanded') !== 'true'); setMenu(false); });
  window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => { if (event.matches) setMenu(false); });

  // Hover opens dropdowns on desktop; the button also toggles them for touch and keyboard users.
  const dropdowns = [...document.querySelectorAll<HTMLElement>('[data-dropdown]')];
  const closeDropdowns = (except?: HTMLElement) => dropdowns.forEach((dropdown) => {
    if (dropdown === except) return;
    dropdown.classList.remove('is-open');
    dropdown.querySelector('button')?.setAttribute('aria-expanded', 'false');
  });
  dropdowns.forEach((dropdown) => {
    const button = dropdown.querySelector('button');
    button?.addEventListener('click', () => {
      const open = !dropdown.classList.contains('is-open');
      closeDropdowns(dropdown);
      dropdown.classList.toggle('is-open', open);
      button.setAttribute('aria-expanded', String(open));
    });
  });
  document.addEventListener('click', (event) => { if (!(event.target as Element).closest('[data-dropdown]')) closeDropdowns(); });
  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    if (toggle?.getAttribute('aria-expanded') === 'true') { setMenu(false); toggle.focus(); }
    if (searchToggle?.getAttribute('aria-expanded') === 'true') { setSearch(false); searchToggle.focus(); }
    const openDropdown = dropdowns.find((dropdown) => dropdown.classList.contains('is-open'));
    if (openDropdown) { closeDropdowns(); openDropdown.querySelector('button')?.focus(); }
  });
}

function setupScroll() {
  const header = document.getElementById('site-header');
  const backToTop = document.getElementById('back-to-top');
  const onScroll = () => {
    header?.classList.toggle('is-scrolled', window.scrollY > 8);
    backToTop?.classList.toggle('is-visible', window.scrollY > 600);
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
  backToTop?.addEventListener('click', (event) => {
    event.preventDefault();
    window.scrollTo({ top: 0, behavior: reducedMotion() ? 'auto' : 'smooth' });
    document.getElementById('main')?.focus({ preventScroll: true });
  });

  // Sections ease in as they scroll into view.
  if ('IntersectionObserver' in window) {
    document.documentElement.classList.add('reveal-ready');
    const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
      if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); }
    }), { rootMargin: '0px 0px -8% 0px' });
    document.querySelectorAll('main > section').forEach((section) => observer.observe(section));
  }
}

function setupForms() {
  // Bag quantities save as soon as they change; the "Update bag" button remains for no-JS visitors.
  document.querySelectorAll<HTMLFormElement>('form[data-auto-submit]').forEach((form) =>
    form.querySelectorAll<HTMLInputElement>('input[type="number"]').forEach((input) => input.addEventListener('change', () => {
      if (input.checkValidity()) form.requestSubmit(form.querySelector<HTMLButtonElement>('[data-update-button]'));
    })));

  document.querySelectorAll<HTMLButtonElement>('[data-toggle-password]').forEach((button) => button.addEventListener('click', () => {
    const input = button.parentElement?.querySelector('input');
    if (!input) return;
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(show));
    button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    button.querySelector('[data-eye="show"]')?.classList.toggle('hidden', show);
    button.querySelector('[data-eye="hide"]')?.classList.toggle('hidden', !show);
  }));

  // CTAs pre-select a form option. If the form is on another page the link just navigates there.
  document.querySelectorAll<HTMLAnchorElement>('[data-fill-select]').forEach((link) => link.addEventListener('click', (event) => {
    const select = document.querySelector(link.dataset.fillSelect!);
    const target = link.hash ? document.querySelector(link.hash) : null;
    if (!(select instanceof HTMLSelectElement) || !target) return;
    event.preventDefault();
    if (link.dataset.fillValue) {
      select.value = link.dataset.fillValue;
      select.dispatchEvent(new Event('change', { bubbles: true }));
    }
    target.scrollIntoView({ behavior: reducedMotion() ? 'auto' : 'smooth' });
    select.focus({ preventScroll: true });
  }));

  document.querySelectorAll<HTMLFormElement>('[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
    if (!confirm(form.dataset.confirm)) event.preventDefault();
  }));

  // Forms marked data-ajax-form="actionName" call that Astro Action without a page reload.
  document.querySelectorAll<HTMLFormElement>('form[data-ajax-form]').forEach((form) => form.addEventListener('submit', async (event) => {
    event.preventDefault();
    type Result = { message?: string; redirect?: string; cartCount?: number; removed?: boolean; reload?: boolean };
    const action = (actions as unknown as Record<string, (input: FormData) => Promise<{ data?: Result; error?: unknown }>>)[form.dataset.ajaxForm!];
    if (!action) { form.submit(); return; }
    const button = form.querySelector<HTMLButtonElement>('button[type="submit"],button:not([type])');
    if (button) button.disabled = true;
    try {
      const { data, error } = await action(new FormData(form));
      if (error) throw error;
      if (data?.redirect) { window.location.assign(data.redirect); return; }
      notify(data?.message ?? 'Thank you.');
      if (typeof data?.cartCount === 'number') updateCartCount(data.cartCount);
      if (data?.removed) { form.closest('[data-removable]')?.remove(); return; }
      if (form.dataset.keep === undefined) form.reset();
      if (data?.reload || form.dataset.reload === 'true') window.location.reload();
    } catch (error) {
      notify(actionErrorMessage(error), true);
    } finally {
      if (button) button.disabled = false;
    }
  }));
}

setupMenuAndSearch();
setupScroll();
setupForms();
