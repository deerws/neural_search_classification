<?php
if ($_FILES['csv']['error'] === UPLOAD_ERR_OK) {
    move_uploaded_file($_FILES['csv']['tmp_name'], 'merged_classification_approval.csv');
    header('Location: index.php');
} else {
    echo "Upload failed.";
}
?>