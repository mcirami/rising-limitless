(function () {
    'use strict';
    const data = JSON.parse(document.getElementById('flow-data').textContent);
    const form = document.getElementById('flow-form');
    const container = form.querySelector('[data-steps]');
    const result = form.querySelector('[data-test-result]');
    // Decode legacy entity-encoded names without interpreting them as markup.
    const decode = value => {
        const box = document.createElement('textarea');
        box.innerHTML = String(value).replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return box.value;
    };
    const offers = data.offers.map(o => ({ ...o, label: o.idoffer + ' — ' + decode(o.offer_name) }));
    const offerById = id => offers.find(o => String(o.idoffer) === String(id));
    const selectedOffer = input => offers.find(o => o.label === input.value && Number(o.status) === 1);
    const codes = value => [...new Set(value.toUpperCase().split(/[\s,;/]+/).filter(Boolean).map(c => c === 'UK' ? 'GB' : c))];
    const rows = () => Array.from(container.children);
    let revision = 0;
    function dirty() { revision++; result.textContent = 'Edits changed. Test again to see the current route.'; }
    function validateOffer(input) {
        input.setCustomValidity(input.value && !selectedOffer(input) ? 'Choose an active offer from the suggestions.' : '');
        return selectedOffer(input)?.idoffer || '';
    }
    function setupOffer(input, id) {
        input.value = offerById(id)?.label || (id ? 'Unavailable offer #' + id : '');
        input.addEventListener('input', () => validateOffer(input));
        validateOffer(input);
    }
    const entry = form.querySelector('[data-entry]');
    const fallback = form.querySelector('[data-fallback]');
    setupOffer(entry, data.entry);
    setupOffer(fallback, data.fallback);
    function updateRows() {
        const seen = new Set();
        const warnings = [];
        rows().forEach((row, index) => {
            row.querySelector('[data-position]').textContent = 'Offer ' + (index + 1);
            row.querySelector('[data-up]').disabled = index === 0;
            row.querySelector('[data-down]').disabled = index === rows().length - 1;
            const overlaps = codes(row.querySelector('[data-codes]').value).filter(c => seen.has(c));
            if (overlaps.length) warnings.push('Offer ' + (index + 1) + ': ' + overlaps.join(', ') + ' already match an earlier offer.');
            codes(row.querySelector('[data-codes]').value).forEach(c => seen.add(c));
        });
        form.querySelector('[data-overlap]').textContent = warnings.join(' ');
    }
    function updateCountries(row) {
        const input = row.querySelector('[data-codes]');
        const list = codes(input.value);
        const invalid = list.filter(c => !Object.hasOwn(data.countries, c));
        input.setCustomValidity(invalid.length ? 'Unknown country codes: ' + invalid.join(', ') : '');
        const chips = row.querySelector('[data-chips]');
        chips.replaceChildren();
        list.forEach(code => {
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'flow-chip';
            button.textContent = code + ' ×';
            button.title = 'Remove ' + (data.countries[code] || code);
            button.addEventListener('click', () => {
                input.value = codes(input.value).filter(c => c !== code).join(', ');
                updateCountries(row); dirty();
            });
            chips.append(button);
        });
        updateRows();
    }
    let dragged = null;
    function addStep(step = {}) {
        const row = document.getElementById('flow-step-template').content.firstElementChild.cloneNode(true);
        const input = row.querySelector('[data-offer]');
        setupOffer(input, step.offer_id);
        const countryInput = row.querySelector('[data-codes]');
        countryInput.value = (step.countries || []).join(', ');
        countryInput.addEventListener('input', () => updateCountries(row));
        const picker = row.querySelector('[data-country-picker]');
        picker.parentElement.className = 'country-picker-label';
        picker.add(new Option('Add a country…', ''));
        Object.entries(data.countries).forEach(([code, name]) => picker.add(new Option(code + ' — ' + name, code)));
        picker.addEventListener('change', () => {
            if (picker.value) countryInput.value = [...new Set([...codes(countryInput.value), picker.value])].join(', ');
            picker.value = ''; updateCountries(row); dirty();
        });
        row.querySelector('[data-remove]').addEventListener('click', () => { row.remove(); updateRows(); dirty(); });
        row.querySelector('[data-up]').addEventListener('click', () => { if (row.previousElementSibling) container.insertBefore(row, row.previousElementSibling); updateRows(); dirty(); });
        row.querySelector('[data-down]').addEventListener('click', () => { if (row.nextElementSibling) container.insertBefore(row.nextElementSibling, row); updateRows(); dirty(); });
        row.querySelector('[data-suggest]').addEventListener('click', () => {
            const name = decode(selectedOffer(input)?.offer_name || '');
            // Only complete country-only segments; never treat a brand's two-letter word as a GEO.
            const found = name.split(/\s+[-–—]\s*/).slice(1).flatMap(segment => {
                const clean = segment.replace(/\s+only\s*$/i, '').trim();
                if (!/^[A-Z]{2}(?:[\s,/;]+[A-Z]{2})*$/.test(clean)) return [];
                const items = codes(clean);
                return items.every(code => Object.hasOwn(data.countries, code)) ? items : [];
            });
            const merged = [...new Set([...codes(countryInput.value), ...found])];
            if (found.length) countryInput.value = merged.join(', ');
            row.querySelector('[data-suggestion]').textContent = found.length ? 'Suggestions added. Review the countries before saving.' : 'No clear country list found. Add countries manually.';
            updateCountries(row); dirty();
        });
        row.querySelector('[draggable]').addEventListener('dragstart', event => { dragged = row; event.dataTransfer.setData('text/plain', 'offer-step'); event.dataTransfer.effectAllowed = 'move'; row.classList.add('is-dragging'); });
        row.addEventListener('dragover', event => { if (dragged) event.preventDefault(); });
        row.addEventListener('drop', event => {
            event.preventDefault();
            if (dragged && dragged !== row) {
                const after = event.clientY > row.getBoundingClientRect().top + row.offsetHeight / 2;
                container.insertBefore(dragged, after ? row.nextSibling : row); updateRows(); dirty();
            }
        });
        row.addEventListener('dragend', () => { row.classList.remove('is-dragging'); dragged = null; });
        container.append(row); updateCountries(row);
    }
    (data.steps.length ? data.steps : [{}]).forEach(addStep);
    form.querySelector('[data-add-step]').addEventListener('click', () => { if (rows().length < 50) { addStep(); dirty(); rows().at(-1).querySelector('input').focus(); } });
    form.addEventListener('input', dirty);
    form.addEventListener('change', dirty);
    function payload() {
        return {
            steps: rows().map(row => ({ offer_id: validateOffer(row.querySelector('[data-offer]')), countries: codes(row.querySelector('[data-codes]').value) })),
            fallback_offer_id: validateOffer(fallback)
        };
    }
    form.addEventListener('submit', event => {
        const values = payload();
        entry.required = form.querySelector('[type=checkbox][name=is_active]').checked;
        form.querySelector('[data-entry-id]').value = validateOffer(entry);
        form.querySelector('[data-fallback-id]').value = values.fallback_offer_id;
        if (!rows().length) { event.preventDefault(); result.textContent = 'Add at least one offer.'; return; }
        if (!form.reportValidity()) { event.preventDefault(); return; }
        const fields = form.querySelector('[data-step-inputs]'); fields.replaceChildren();
        function hidden(name, value) { const input = document.createElement('input'); input.type = 'hidden'; input.name = name; input.value = value; fields.append(input); }
        values.steps.forEach((step, index) => {
            hidden('steps[' + index + '][offer_id]', step.offer_id);
            step.countries.forEach(c => hidden('steps[' + index + '][countries][]', c));
        });
    });
    form.querySelector('[data-test]').addEventListener('click', async event => {
        const button = event.currentTarget;
        const current = revision;
        button.disabled = true; result.textContent = 'Testing…';
        try {
            const response = await fetch(data.previewUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value },
                body: JSON.stringify({ ...payload(), country: form.querySelector('[data-test-country]').value })
            });
            if (response.redirected) throw new Error('Your session expired. Reload the page to sign in.');
            const answer = await response.json();
            if (!response.ok) throw new Error(Object.values(answer.errors || {}).flat().join(' ') || 'Unable to test this flow.');
            if (current !== revision) return;
            result.textContent = (answer.fallback ? 'Final fallback' : 'Offer ' + answer.position) + ': ' + decode(answer.offer_name) + '. ' + (answer.skipped.length ? 'Skipped offers ' + answer.skipped.join(', ') + ' because the country did not match.' : 'Country matches the first offer.');
        } catch (error) { if (current === revision) result.textContent = error.message || 'Unable to test. Try again.'; }
        finally { button.disabled = false; }
    });
})();
