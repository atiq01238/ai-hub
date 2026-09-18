document.addEventListener('DOMContentLoaded', () => {
  const initBuilder = () => {
    const root = document.querySelector('[data-comparison-builder]');
    if (!root) return;

    const typeButtons = [...root.querySelectorAll('[data-builder-type]')];
    const panels = [...root.querySelectorAll('[data-builder-panel]')];
    const search = root.querySelector('[data-builder-search]');
    const slots = root.querySelector('[data-selection-slots]');
    const count = root.querySelector('[data-selection-count]');
    const formType = root.querySelector('[data-form-type]');
    const formItems = root.querySelector('[data-form-items]');
    const submit = root.querySelector('[data-build-button]');
    let type = root.querySelector('[data-builder-type].active')?.dataset.builderType || 'tool';
    const params = new URLSearchParams(window.location.search);
    const initialIds = [
      ...params.getAll('items[]'),
      ...params.getAll('items'),
      ...(params.get('item') ? [params.get('item')] : []),
    ]
      .flatMap(value => String(value || '').split(','))
      .map(value => value.trim())
      .filter(Boolean);
    let selected = [...new Set(initialIds)].slice(0, 4);

    const activeItems = () => [...root.querySelectorAll(`[data-builder-item][data-type="${type}"]`)];
    const resetSearch = () => {
      if (search) search.value = '';
      activeItems().forEach(item => item.classList.remove('filtered-out'));
    };

    const render = () => {
      const validIds = new Set(activeItems().map(item => item.dataset.id));
      selected = selected.filter(id => validIds.has(id)).slice(0, 4);
      activeItems().forEach(item => item.classList.toggle('selected', selected.includes(item.dataset.id)));
      if (count) count.textContent = `${selected.length} / 4`;
      if (formType) formType.value = type;
      if (formItems) formItems.innerHTML = selected.map(id => `<input type="hidden" name="items[]" value="${id}">`).join('');
      if (submit) submit.disabled = selected.length < 2;

      const chosen = selected.map(id => activeItems().find(item => item.dataset.id === id)).filter(Boolean);
      if (slots) {
        slots.innerHTML = Array.from({ length: 4 }, (_, index) => {
          const item = chosen[index];
          if (!item) {
            return `<div class="selection-slot empty"><span>${index + 1}</span><div><strong>Select a product</strong><small>${index < 2 ? 'Required' : 'Optional'}</small></div></div>`;
          }
          const logo = item.querySelector('.builder-product-logo')?.innerHTML || '';
          const name = item.querySelector('.builder-product-copy strong')?.textContent || 'Product';
          const company = item.querySelector('.builder-product-copy small')?.textContent || '';
          return `<div class="selection-slot"><span>${index + 1}</span><div class="tiny-logo">${logo}</div><div><strong>${name}</strong><small>${company}</small></div><button type="button" aria-label="Remove ${name}" data-remove-id="${item.dataset.id}">×</button></div>`;
        }).join('');
      }

      if (window.lucide) window.lucide.createIcons();
    };

    typeButtons.forEach(button => button.addEventListener('click', () => {
      type = button.dataset.builderType;
      selected = [];
      typeButtons.forEach(candidate => candidate.classList.toggle('active', candidate === button));
      panels.forEach(panel => panel.classList.toggle('hidden', panel.dataset.builderPanel !== type));
      resetSearch();
      render();
    }));

    root.addEventListener('click', event => {
      const item = event.target.closest('[data-builder-item]');
      if (item && item.dataset.type === type) {
        const id = item.dataset.id;
        selected = selected.includes(id)
          ? selected.filter(value => value !== id)
          : (selected.length < 4 ? [...selected, id] : selected);
        render();
        return;
      }

      const remove = event.target.closest('[data-remove-id]');
      if (remove) {
        selected = selected.filter(value => value !== remove.dataset.removeId);
        render();
      }
    });

    search?.addEventListener('input', () => {
      const query = search.value.trim().toLowerCase();
      activeItems().forEach(item => item.classList.toggle('filtered-out', query && !item.dataset.search.includes(query)));
    });

    render();
  };

  const initQuickCompare = () => {
    const root = document.querySelector('[data-quick-compare]');
    if (!root) return;

    const typeButtons = [...root.querySelectorAll('[data-quick-type]')];
    const panels = [...root.querySelectorAll('[data-quick-panel]')];
    const form = root.querySelector('[data-quick-compare-form]');
    const formType = root.querySelector('[data-quick-form-type]');
    const submit = root.querySelector('[data-quick-submit]');
    const status = root.querySelector('[data-quick-status]');
    const builderLink = root.querySelector('[data-quick-builder-link]');
    let type = root.dataset.defaultType || 'model';

    const activePanel = () => root.querySelector(`[data-quick-panel="${type}"]`);

    const updateBuilderLink = () => {
      if (!builderLink) return;
      const base = builderLink.dataset.builderBase || builderLink.getAttribute('href') || '/compare/builder';
      const url = new URL(base, window.location.origin);
      url.searchParams.set('type', type);
      activePanel()?.querySelectorAll('[data-quick-select]').forEach(select => {
        if (select.value) url.searchParams.append('items[]', select.value);
      });
      builderLink.setAttribute('href', url.pathname + url.search);
    };

    const updateState = () => {
      const selects = [...(activePanel()?.querySelectorAll('[data-quick-select]') || [])];
      const values = selects.map(select => select.value).filter(Boolean);
      const valid = values.length === 2 && values[0] !== values[1];

      if (submit) submit.disabled = !valid;
      if (status) {
        status.textContent = values.length < 2
          ? 'Select two different items to continue.'
          : (values[0] === values[1] ? 'Choose two different items.' : 'Ready to compare side by side.');
        status.classList.toggle('ready', valid);
      }
      updateBuilderLink();
    };

    const setType = nextType => {
      type = nextType;
      if (formType) formType.value = type;
      typeButtons.forEach(button => button.classList.toggle('active', button.dataset.quickType === type));
      panels.forEach(panel => {
        const active = panel.dataset.quickPanel === type;
        panel.classList.toggle('hidden', !active);
        panel.querySelectorAll('[data-quick-select]').forEach(select => {
          select.disabled = !active;
          if (!active) select.value = '';
        });
      });
      updateState();
      if (window.lucide) window.lucide.createIcons();
    };

    typeButtons.forEach(button => button.addEventListener('click', () => setType(button.dataset.quickType)));

    root.addEventListener('change', event => {
      if (event.target.matches('[data-quick-select]')) updateState();
    });

    root.addEventListener('click', event => {
      const swap = event.target.closest('[data-quick-swap]');
      if (!swap) return;
      const selects = [...(activePanel()?.querySelectorAll('[data-quick-select]') || [])];
      if (selects.length !== 2) return;
      const first = selects[0].value;
      selects[0].value = selects[1].value;
      selects[1].value = first;
      updateState();
    });

    form?.addEventListener('submit', event => {
      const values = [...(activePanel()?.querySelectorAll('[data-quick-select]') || [])]
        .map(select => select.value)
        .filter(Boolean);
      if (values.length !== 2 || values[0] === values[1]) event.preventDefault();
    });

    setType(type);
  };

  initBuilder();
  initQuickCompare();
});
