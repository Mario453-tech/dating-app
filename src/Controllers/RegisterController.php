<?php

namespace App\Controllers;

use App\Models\User;
use App\Utils\Validator;
use App\Utils\FileUploader;

class RegisterController {
    private $user;
    private $validator;
    private $fileUploader;

    public function __construct() {
        $this->user = new User();
        $this->validator = new Validator();
        $this->fileUploader = new FileUploader();
    }

    public function register() {
        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Walidacja danych
            $validationRules = [
                'username' => ['required', 'min:3', 'max:50'],
                'email' => ['required', 'email'],
                'password' => ['required', 'min:6'],
                'confirm_password' => ['required', 'same:password'],
                'birth_date' => ['required', 'date', 'age:18'],
                'gender' => ['required', 'in:M,F,OTHER'],
                'seeking_gender' => ['required', 'in:M,F,OTHER,ANY']
            ];

            $validation = $this->validator->validate($_POST, $validationRules);

            if ($validation->fails()) {
                $error = $validation->getFirstError();
            } else {
                // Sprawdź czy email już istnieje
                if ($this->user->findByEmail($_POST['email'])) {
                    $error = "Ten adres email jest już zajęty.";
                } else {
                    // Obsługa przesyłania zdjęcia
                    $profilePhotoPath = null;
                    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                        $profilePhotoPath = $this->fileUploader->upload($_FILES['profile_photo'], 'profile_photos');
                        if (!$profilePhotoPath) {
                            $error = "Nie udało się przesłać zdjęcia profilowego.";
                        }
                    }

                    if (!$error) {
                        // Przygotuj dane do zapisania
                        $userData = [
                            'username' => $_POST['username'],
                            'email' => $_POST['email'],
                            'password' => $_POST['password'],
                            'first_name' => $_POST['first_name'] ?? null,
                            'last_name' => $_POST['last_name'] ?? null,
                            'birth_date' => $_POST['birth_date'],
                            'gender' => $_POST['gender'],
                            'seeking_gender' => $_POST['seeking_gender'],
                            'bio' => $_POST['bio'] ?? null,
                            'location' => $_POST['location'] ?? null,
                            'profile_photo' => $profilePhotoPath
                        ];

                        if ($this->user->create($userData)) {
                            $success = "Rejestracja zakończona pomyślnie! Możesz się teraz zalogować.";
                            // Przekieruj do strony logowania po 3 sekundach
                            header("refresh:3;url=/login");
                        } else {
                            $error = "Wystąpił błąd podczas rejestracji. Spróbuj ponownie później.";
                        }
                    }
                }
            }
        }

        // Wyświetl formularz
        require_once __DIR__ . '/../../templates/register.php';
    }
}
