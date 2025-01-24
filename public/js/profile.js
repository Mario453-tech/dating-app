// Obsługa modalu ze zdjęciem
document.addEventListener('DOMContentLoaded', function() {
    const photoModal = document.getElementById('photoModal');
    if (photoModal) {
        photoModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const photoUrl = button.getAttribute('data-photo-url');
            const photoTitle = button.getAttribute('data-photo-title');
            const photoDescription = button.getAttribute('data-photo-description');

            const modalTitle = this.querySelector('#photoModalLabel');
            const modalImage = this.querySelector('#modalImage');
            const modalDescription = this.querySelector('#photoDescription');

            modalTitle.textContent = photoTitle;
            modalImage.src = photoUrl;
            modalDescription.textContent = photoDescription || 'Brak opisu';
        });
    }

    // Potwierdzenie usunięcia zdjęcia
    const deletePhotoForms = document.querySelectorAll('form[action*="photo_delete"]');
    deletePhotoForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Czy na pewno chcesz usunąć to zdjęcie?')) {
                e.preventDefault();
            }
        });
    });
});
