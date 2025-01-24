document.addEventListener('DOMContentLoaded', function() {
    const interestItems = document.querySelectorAll('.interest-item');
    const saveButton = document.getElementById('saveInterests');
    let hasChanges = false;

    interestItems.forEach(item => {
        item.addEventListener('click', function() {
            this.classList.toggle('selected');
            hasChanges = true;
            saveButton.disabled = false;
        });
    });

    saveButton.addEventListener('click', async function() {
        if (!hasChanges) return;

        const selectedInterests = Array.from(document.querySelectorAll('.interest-item.selected'))
            .map(item => parseInt(item.dataset.interestId));

        try {
            const response = await fetch('/profile/interests/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    interests: selectedInterests
                })
            });

            if (response.ok) {
                hasChanges = false;
                saveButton.disabled = true;
                alert('Zainteresowania zostały zaktualizowane!');
            } else {
                throw new Error('Błąd podczas zapisywania');
            }
        } catch (error) {
            alert('Wystąpił błąd podczas zapisywania zmian. Spróbuj ponownie.');
            console.error(error);
        }
    });

    // Początkowo przycisk jest wyłączony
    saveButton.disabled = true;
});

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('interests-form');
    if (!form) return;

    const checkboxes = form.querySelectorAll('input[type="checkbox"]');
    
    checkboxes.forEach(checkbox => {
        const label = checkbox.parentElement;
        
        // Ustaw początkowy stan
        if (checkbox.checked) {
            label.classList.add('selected');
        }
        
        // Obsługa kliknięcia
        label.addEventListener('click', function(e) {
            if (e.target.tagName === 'INPUT') return;
            
            checkbox.checked = !checkbox.checked;
            label.classList.toggle('selected', checkbox.checked);
        });
        
        // Obsługa zmiany stanu checkboxa
        checkbox.addEventListener('change', function() {
            label.classList.toggle('selected', this.checked);
        });
    });

    // Obsługa wysyłania formularza
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        
        // Zmień tekst przycisku na czas zapisywania
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Zapisywanie...';
        submitButton.disabled = true;
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = form.dataset.successUrl;
            } else {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
                alert(data.message);
                if (data.errors) {
                    alert(data.errors.join('\n'));
                }
            }
        })
        .catch(error => {
            console.error('Błąd:', error);
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
            alert('Wystąpił błąd podczas zapisywania zmian.');
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Obsługa przycisku dodawania zainteresowania
    const addButtons = document.querySelectorAll('.add-interest-btn');
    const categorySelect = document.getElementById('categorySelect');
    
    addButtons.forEach(button => {
        button.addEventListener('click', function() {
            const categoryId = this.dataset.categoryId;
            categorySelect.value = categoryId;
        });
    });

    // Obsługa przycisków "Pokaż więcej"
    const showMoreButtons = document.querySelectorAll('.show-more-interests');
    showMoreButtons.forEach(button => {
        button.addEventListener('click', function() {
            const categoryId = this.dataset.categoryId;
            const isShowingAll = this.dataset.showingAll === 'true';
            const category = document.querySelector(`#category-${categoryId}`);
            const items = category.querySelectorAll('.interest-checkbox');
            
            items.forEach(item => {
                const index = parseInt(item.dataset.index);
                if (index > 4) {
                    item.classList.toggle('d-none', isShowingAll);
                }
            });
            
            this.dataset.showingAll = (!isShowingAll).toString();
            if (isShowingAll) {
                this.innerHTML = '<i class="fas fa-chevron-down me-1"></i> Pokaż więcej';
            } else {
                this.innerHTML = '<i class="fas fa-chevron-up me-1"></i> Pokaż mniej';
            }
        });
    });

    // Obsługa formularza dodawania zainteresowania
    const newInterestForm = document.getElementById('newInterestForm');
    newInterestForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        try {
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    window.location.reload();
                } else {
                    alert(result.message || 'Wystąpił błąd podczas dodawania zainteresowania.');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Wystąpił błąd podczas dodawania zainteresowania.');
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const interestModal = document.getElementById('interestModal');
    const interestForm = document.getElementById('interestForm');
    const modalTitle = interestModal.querySelector('.modal-title');
    const interestNameInput = document.getElementById('interestName');
    const categorySelect = document.getElementById('categorySelect');
    let editMode = false;
    let editInterestId = null;

    // Obsługa przycisku dodawania zainteresowania
    document.querySelectorAll('.add-interest-btn').forEach(button => {
        button.addEventListener('click', function() {
            editMode = false;
            editInterestId = null;
            const categoryId = this.dataset.categoryId;
            
            modalTitle.textContent = 'Dodaj nowe zainteresowanie';
            interestNameInput.value = '';
            categorySelect.value = categoryId;
            interestForm.action = ROUTES.add;
        });
    });

    // Obsługa przycisku edycji zainteresowania
    document.querySelectorAll('.edit-interest').forEach(button => {
        button.addEventListener('click', function() {
            editMode = true;
            editInterestId = this.dataset.interestId;
            const interestName = this.dataset.interestName;
            const categoryId = this.dataset.categoryId;

            modalTitle.textContent = 'Edytuj zainteresowanie';
            interestNameInput.value = interestName;
            categorySelect.value = categoryId;
            interestForm.action = ROUTES.edit(editInterestId);
        });
    });

    // Obsługa przycisku usuwania zainteresowania
    document.querySelectorAll('.delete-interest').forEach(button => {
        button.addEventListener('click', async function() {
            if (!confirm('Czy na pewno chcesz usunąć to zainteresowanie?')) {
                return;
            }

            const action = this.dataset.action;
            try {
                const response = await fetch(action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();
                if (result.success) {
                    window.location.reload();
                } else {
                    alert(result.message || 'Wystąpił błąd podczas usuwania zainteresowania.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas usuwania zainteresowania.');
            }
        });
    });

    // Obsługa formularza dodawania/edycji zainteresowania
    interestForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        try {
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const result = await response.json();
            if (result.success) {
                window.location.reload();
            } else {
                alert(result.message || 'Wystąpił błąd podczas zapisywania zainteresowania.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Wystąpił błąd podczas zapisywania zainteresowania.');
        }
    });

    // Obsługa przycisku "Pokaż więcej"
    document.querySelectorAll('.show-more-interests').forEach(button => {
        button.addEventListener('click', function() {
            const categoryId = this.dataset.categoryId;
            const showingAll = this.dataset.showingAll === 'true';
            const category = document.querySelector(`#category-${categoryId}`);
            const hiddenInterests = category.querySelectorAll('.interest-checkbox.d-none');
            
            if (!showingAll) {
                hiddenInterests.forEach(interest => {
                    interest.classList.remove('d-none');
                });
                this.innerHTML = '<i class="fas fa-chevron-up me-1"></i> Pokaż mniej';
                this.dataset.showingAll = 'true';
            } else {
                hiddenInterests.forEach(interest => {
                    const index = parseInt(interest.dataset.index);
                    if (index > 4) {
                        interest.classList.add('d-none');
                    }
                });
                this.innerHTML = `<i class="fas fa-chevron-down me-1"></i> Pokaż więcej (${hiddenInterests.length})`;
                this.dataset.showingAll = 'false';
            }
        });
    });

    // Obsługa zaznaczania/odznaczania zainteresowań
    document.querySelectorAll('.interest-input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', async function() {
            const action = this.dataset.action;
            try {
                const response = await fetch(action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const result = await response.json();
                if (!result.success) {
                    this.checked = !this.checked; // Przywróć poprzedni stan
                    alert(result.message || 'Wystąpił błąd podczas aktualizacji zainteresowania.');
                }
            } catch (error) {
                console.error('Error:', error);
                this.checked = !this.checked; // Przywróć poprzedni stan
                alert('Wystąpił błąd podczas aktualizacji zainteresowania.');
            }
        });
    });
});
