<?php

namespace App\Utils;

class FileUploader {
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    private $maxSize = 5242880; // 5MB
    private $uploadDir = __DIR__ . '/../../public/uploads/';

    public function upload($file, $subDirectory = '') {
        // Sprawdź, czy nie było błędów podczas przesyłania
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // Sprawdź typ pliku
        if (!in_array($file['type'], $this->allowedTypes)) {
            return false;
        }

        // Sprawdź rozmiar pliku
        if ($file['size'] > $this->maxSize) {
            return false;
        }

        // Przygotuj ścieżkę docelową
        $targetDir = $this->uploadDir . $subDirectory;
        if (!empty($subDirectory) && !is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Generuj unikalną nazwę pliku
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $extension;
        $targetPath = $targetDir . '/' . $fileName;

        // Przenieś plik
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $subDirectory . '/' . $fileName;
        }

        return false;
    }

    public function delete($path) {
        $fullPath = $this->uploadDir . $path;
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }
}
