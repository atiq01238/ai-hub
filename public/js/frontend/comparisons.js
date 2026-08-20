document.addEventListener('DOMContentLoaded', () => {
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
  const initialItem = new URLSearchParams(window.location.search).get('item');
  let selected = initialItem ? [initialItem] : [];

  const activeItems = () => [...root.querySelectorAll(`[data-builder-item][data-type="${type}"]`)];
  const resetSearch = () => { if (search) search.value = ''; activeItems().forEach(i => i.classList.remove('filtered-out')); };
  const render = () => {
    activeItems().forEach(item => item.classList.toggle('selected', selected.includes(item.dataset.id)));
    count.textContent = `${selected.length} / 4`;
    formType.value = type;
    formItems.innerHTML = selected.map(id => `<input type="hidden" name="items[]" value="${id}">`).join('');
    submit.disabled = selected.length < 2;
    const chosen = selected.map(id => activeItems().find(i => i.dataset.id === id)).filter(Boolean);
    slots.innerHTML = Array.from({length:4}, (_,index) => {
      const item = chosen[index];
      if (!item) return `<div class="selection-slot empty"><span>${index+1}</span><div><strong>Select a product</strong><small>${index < 2 ? 'Required':'Optional'}</small></div></div>`;
      const logo = item.querySelector('.builder-product-logo')?.innerHTML || '';
      const name = item.querySelector('.builder-product-copy strong')?.textContent || 'Product';
      const company = item.querySelector('.builder-product-copy small')?.textContent || '';
      return `<div class="selection-slot"><span>${index+1}</span><div class="tiny-logo">${logo}</div><div><strong>${name}</strong><small>${company}</small></div><button type="button" aria-label="Remove ${name}" data-remove-id="${item.dataset.id}">×</button></div>`;
    }).join('');
    if (window.lucide) lucide.createIcons();
  };

  typeButtons.forEach(btn => btn.addEventListener('click', () => {
    type = btn.dataset.builderType;
    selected = [];
    typeButtons.forEach(b => b.classList.toggle('active', b === btn));
    panels.forEach(p => p.classList.toggle('hidden', p.dataset.builderPanel !== type));
    resetSearch(); render();
  }));
  root.addEventListener('click', e => {
    const item = e.target.closest('[data-builder-item]');
    if (item && item.dataset.type === type) {
      const id = item.dataset.id;
      selected = selected.includes(id) ? selected.filter(v => v !== id) : (selected.length < 4 ? [...selected,id] : selected);
      render(); return;
    }
    const remove = e.target.closest('[data-remove-id]');
    if (remove) { selected = selected.filter(v => v !== remove.dataset.removeId); render(); }
  });
  search?.addEventListener('input', () => {
    const q = search.value.trim().toLowerCase();
    activeItems().forEach(item => item.classList.toggle('filtered-out', q && !item.dataset.search.includes(q)));
  });
  render();
});
