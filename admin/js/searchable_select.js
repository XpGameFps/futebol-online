function makeSelectSearchable(selectElement) {
    if (!selectElement) {
        console.error("Searchable Select: Element not provided or not found.");
        return;
    }

    selectElement.style.display = 'none';

    const wrapper = document.createElement('div');
    wrapper.classList.add('searchable-select-wrapper');
    selectElement.parentNode.insertBefore(wrapper, selectElement.nextSibling);

    const searchInput = document.createElement('input');
    searchInput.setAttribute('type', 'text');
    searchInput.setAttribute('placeholder', selectElement.getAttribute('data-search-placeholder') || 'Pesquisar...');
    searchInput.classList.add('searchable-select-input');
    wrapper.appendChild(searchInput);

    const optionsContainer = document.createElement('div');
    optionsContainer.classList.add('searchable-select-options');
    wrapper.appendChild(optionsContainer);

    let originalOptions = [];
    Array.from(selectElement.options).forEach(option => {
        originalOptions.push({
            text: option.textContent,
            value: option.value,
            disabled: option.disabled,
            selected: option.selected,
            element: option
        });
    });

    function renderOptions(filter = '') {
        optionsContainer.innerHTML = '';
        originalOptions.forEach(opt => {
            if (opt.text.toLowerCase().includes(filter.toLowerCase()) || opt.value === "") {
                const optionDiv = document.createElement('div');
                optionDiv.classList.add('searchable-select-option');
                if (opt.disabled) {
                    optionDiv.classList.add('disabled');
                }
                if (opt.value === selectElement.value) {
                    optionDiv.classList.add('selected');
                }
                optionDiv.textContent = opt.text;
                optionDiv.dataset.value = opt.value;

                if (!opt.disabled) {
                    optionDiv.addEventListener('click', function() {
                        selectElement.value = opt.value;
                        searchInput.value = opt.text;
                        Array.from(optionsContainer.children).forEach(child => child.classList.remove('selected'));
                        this.classList.add('selected');
                        optionsContainer.style.display = 'none';
                        const event = new Event('change', { bubbles: true });
                        selectElement.dispatchEvent(event);
                    });
                }
                optionsContainer.appendChild(optionDiv);
            }
        });
        optionsContainer.style.display = originalOptions.length > 0 ? 'block' : 'none';
    }

    renderOptions();
    if (
        selectElement.value &&
        selectElement.options[selectElement.selectedIndex] &&
        selectElement.options[selectElement.selectedIndex].text !== originalOptions[0]?.text
    ) {
        searchInput.value = selectElement.options[selectElement.selectedIndex].text;
    }

    searchInput.addEventListener('input', function() {
        renderOptions(this.value);
    });

    searchInput.addEventListener('focus', function() {
        renderOptions(this.value);
        optionsContainer.style.display = 'block';
    });

    document.addEventListener('click', function(event) {
        if (!wrapper.contains(event.target)) {
            optionsContainer.style.display = 'none';
        }
    });

    optionsContainer.style.display = 'none';
}
