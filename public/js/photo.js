// Podgląd zdjęcia przed wysłaniem
function previewImage(input) {
    const preview = document.getElementById('preview');
    const placeholder = document.getElementById('previewPlaceholder');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.style.display = 'block';
            preview.src = e.target.result;
            placeholder.style.display = 'none';
        };
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
        preview.src = '#';
        placeholder.style.display = 'block';
    }
}

// Obsługa formularza
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('photoForm');
    const progressModal = new bootstrap.Modal(document.getElementById('uploadProgressModal'));
    const progressBar = document.getElementById('uploadProgress');
    
    if (form) {
        // Dodaj obsługę podglądu zdjęcia
        const photoInput = form.querySelector('input[type="file"]');
        if (photoInput) {
            photoInput.addEventListener('change', function() {
                previewImage(this);
            });
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = this.getAttribute('data-action');
            const redirectUrl = this.getAttribute('data-redirect');

            // Debug formData
            console.log('FormData contents:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            // Pokaż modal z postępem
            progressModal.show();
            
            // Wyślij formularz
            fetch(action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Błąd serwera: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    window.location.href = redirectUrl;
                } else {
                    const errorMessage = data.message || data.error || 'Wystąpił nieznany błąd podczas przesyłania zdjęcia';
                    alert(errorMessage);
                    progressModal.hide();
                }
            })
            .catch(error => {
                console.error('Błąd:', error);
                alert('Wystąpił błąd podczas przesyłania zdjęcia: ' + error.message);
                progressModal.hide();
            });
            
            // Symulacja postępu przesyłania
            let progress = 0;
            const interval = setInterval(() => {
                progress += 5;
                if (progress <= 90) {
                    progressBar.style.width = progress + '%';
                    progressBar.textContent = progress + '%';
                }
            }, 200);
        });
    }

    // Obsługa modalu ze zdjęciem
    const photoModal = document.getElementById('photoModal');
    if (photoModal) {
        photoModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const photoUrl = button.getAttribute('data-photo-url');
            const photoTitle = button.getAttribute('data-photo-title');
            const photoDescription = button.getAttribute('data-photo-description');

            const modalTitle = this.querySelector('.modal-title');
            const modalImage = this.querySelector('#modalImage');
            const modalDescription = this.querySelector('#photoDescription');

            modalTitle.textContent = photoTitle || 'Podgląd zdjęcia';
            modalImage.src = photoUrl;
            modalDescription.textContent = photoDescription || '';
        });
    }
});
