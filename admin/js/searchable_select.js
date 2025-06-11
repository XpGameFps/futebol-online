function makeSelectSearchable(selectElement) {
    if (!selectElement) {
        console.error("Searchable Select: Element not provided or not found.");
        return;
    }

    // Hide the original select element
    selectElement.style.display = 'none';

    // Create wrapper for the custom searchable select
    const wrapper = document.createElement('div');
    wrapper.classList.add('searchable-select-wrapper');
    selectElement.parentNode.insertBefore(wrapper, selectElement.nextSibling);

    // Create search input
    const searchInput = document.createElement('input');
    searchInput.setAttribute('type', 'text');
    searchInput.setAttribute('placeholder', selectElement.getAttribute('data-search-placeholder') || 'Pesquisar...');
    searchInput.classList.add('searchable-select-input');
    wrapper.appendChild(searchInput);

    // Create dropdown options container
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
            element: option // Keep a reference to the original option element
        });
    });

    function renderOptions(filter = '') {
        optionsContainer.innerHTML = '';
        originalOptions.forEach(opt => {
            if (opt.text.toLowerCase().includes(filter.toLowerCase()) || opt.value === "") { // Always show the placeholder/default option if it exists
                const optionDiv = document.createElement('div');
                optionDiv.classList.add('searchable-select-option');
                if (opt.disabled) {
                    optionDiv.classList.add('disabled');
                }
                if (opt.value === selectElement.value) { // Highlight currently selected
                    optionDiv.classList.add('selected');
                }
                optionDiv.textContent = opt.text;
                optionDiv.dataset.value = opt.value;

                if (!opt.disabled) {
                    optionDiv.addEventListener('click', function() {
                        selectElement.value = opt.value;
                        searchInput.value = opt.text; // Show selected text in input
                        // Update 'selected' class on options
                        Array.from(optionsContainer.children).forEach(child => child.classList.remove('selected'));
                        this.classList.add('selected');
                        optionsContainer.style.display = 'none'; // Hide options after selection
                        // Dispatch a change event on the original select
                        const event = new Event('change', { bubbles: true });
                        selectElement.dispatchEvent(event);
                    });
                }
                optionsContainer.appendChild(optionDiv);
            }
        });
        optionsContainer.style.display = originalOptions.length > 0 ? 'block' : 'none';
    }

    // Initial render
    renderOptions();
    // Set initial input text if a value is already selected (and it's not the placeholder)
    if (selectElement.value && selectElement.options[selectElement.selectedIndex] && selectElement.options[selectElement.selectedIndex].text !== originalOptions[0]?.text) {
       searchInput.value = selectElement.options[selectElement.selectedIndex].text;
    }


    searchInput.addEventListener('input', function() {
        renderOptions(this.value);
    });

    searchInput.addEventListener('focus', function() {
        renderOptions(this.value); // Re-render to show all matching or placeholder
        optionsContainer.style.display = 'block';
    });

    // Optional: Hide options when clicking outside
    document.addEventListener('click', function(event) {
        if (!wrapper.contains(event.target)) {
            optionsContainer.style.display = 'none';
        }
    });

    // Style the options container to initially be hidden until focus or input
    optionsContainer.style.display = 'none';
}
