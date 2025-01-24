// Funkcja do logowania na serwer
const logToServer = async (message, level = 'info', context = {}) => {
    try {
        await fetch('/log', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                message,
                level,
                context: {
                    ...context,
                    component: 'gallery',
                    timestamp: new Date().toISOString()
                }
            })
        });
    } catch (error) {
        console.error('Błąd podczas logowania:', error);
    }
};

document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicjalizacja galerii...');
    
    // Inicjalizacja Swiper
    const initGallerySwiper = () => {
        try {
            console.log('Inicjalizacja Swiper...');
            logToServer('Inicjalizacja Swiper');
            
            const gallerySwiper = new Swiper(".mySwiper", {
                effect: "coverflow",
                grabCursor: true,
                centeredSlides: true,
                slidesPerView: "auto",
                coverflowEffect: {
                    rotate: 50,
                    stretch: 0,
                    depth: 100,
                    modifier: 1,
                    slideShadows: true,
                },
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true,
                },
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
                autoplay: {
                    delay: 3000,
                    disableOnInteraction: false,
                },
                on: {
                    init: function () {
                        logToServer('Swiper zainicjalizowany pomyślnie', 'info', {
                            slidesCount: this.slides.length
                        });
                    },
                    slideChange: function () {
                        logToServer('Zmiana slajdu', 'debug', {
                            activeIndex: this.activeIndex,
                            slidesCount: this.slides.length
                        });
                    },
                    error: function (error) {
                        logToServer('Błąd w Swiper', 'error', { error });
                    }
                }
            });

            return gallerySwiper;
        } catch (error) {
            console.error('Błąd podczas inicjalizacji Swiper:', error);
            logToServer('Błąd podczas inicjalizacji Swiper', 'error', { error: error.message });
            return null;
        }
    };

    // Inicjalizacja Lightbox
    const initLightbox = () => {
        try {
            console.log('Inicjalizacja Lightbox...');
            logToServer('Inicjalizacja Lightbox');
            
            lightbox.option({
                'resizeDuration': 200,
                'wrapAround': true,
                'albumLabel': "Zdjęcie %1 z %2",
                'fadeDuration': 300,
                'imageFadeDuration': 300,
                'positionFromTop': 100,
                'showImageNumberLabel': true,
                'onOpen': function() {
                    logToServer('Lightbox otwarty');
                },
                'onClose': function() {
                    logToServer('Lightbox zamknięty');
                }
            });
        } catch (error) {
            console.error('Błąd podczas inicjalizacji Lightbox:', error);
            logToServer('Błąd podczas inicjalizacji Lightbox', 'error', { error: error.message });
        }
    };

    // Inicjalizacja wszystkich komponentów galerii
    const initGallery = () => {
        console.log('Rozpoczęcie inicjalizacji galerii...');
        const galleryElement = document.querySelector('.mySwiper');
        
        if (galleryElement) {
            console.log('Znaleziono element galerii');
            logToServer('Znaleziono element galerii');
            
            const swiper = initGallerySwiper();
            if (swiper) {
                initLightbox();
            }
        } else {
            console.warn('Nie znaleziono elementu galerii');
            logToServer('Nie znaleziono elementu galerii', 'warning');
        }
    };

    // Uruchomienie inicjalizacji
    initGallery();
});
