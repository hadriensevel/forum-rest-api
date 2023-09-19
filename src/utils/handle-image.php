<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: handle-image.php
 */

const UPLOAD_DIR = __DIR__ . '/../../public/uploads/';

/**
 * Handle the image upload and return its name if successful
 * @return string|null
 */
function handleImageUpload(): ?string
{
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageName = uniqid() . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR . $imageName);
        return $imageName;
    }
    return null;
}

/**
 * Delete the image from the uploads folder
 * @param string $imageName The name of the image to delete
 * @return void
 */
function deleteImage(string $imageName): void
{
    unlink(UPLOAD_DIR . $imageName);
}