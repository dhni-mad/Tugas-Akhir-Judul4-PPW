<?php
session_start();

if (!isset($_SESSION['contacts'])) {
    $_SESSION['contacts'] = [];
}

$uploadDir = 'dataGambar/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$errors = [];
$editMode = false;
$editIndex = null;
$editData = ['nama' => '', 'email' => '', 'telepon' => '', 'foto' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id = $_GET['id'];
        if (isset($_SESSION['contacts'][$id])) {
            $fotoLama = $_SESSION['contacts'][$id]['foto'];
            if ($fotoLama && file_exists($fotoLama)) unlink($fotoLama);
            
            unset($_SESSION['contacts'][$id]);
            $_SESSION['contacts'] = array_values($_SESSION['contacts']);
        }
        header('Location: TA_4.php');
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
        $id = $_GET['id'];
        if (isset($_SESSION['contacts'][$id])) {
            $editMode = true;
            $editIndex = $id;
            $editData = $_SESSION['contacts'][$id];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $telepon = trim($_POST['telepon']);
    $editIndex = $_POST['edit_index'] ?? null;
    $fotoLama = $_POST['foto_lama'] ?? '';
    $fotoPath = $fotoLama;

    if (empty($nama)) {
        $errors[] = "Nama harus diisi.";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $nama)) {
        $errors[] = "Nama tidak valid! Hanya boleh huruf dan spasi.";
    }

    if (empty($email)) {
        $errors[] = "Email harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }

    if (empty($telepon)) {
        $errors[] = "Telepon harus diisi.";
    } elseif (!preg_match('/^[0-9\+\-\s]+$/', $telepon)) {
        $errors[] = "Format telepon hanya angka, spasi, +, -";
    }

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['foto'];
        $fileName = time() . '_' . basename($file['name']);
        $targetFile = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        
        if (!in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $errors[] = "Hanya file gambar (JPG, PNG, GIF) yang diperbolehkan.";
        } elseif ($file['size'] > 2000000) {
            $errors[] = "File terlalu besar (Max 2MB).";
        }

        if (empty($errors)) {
            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                $fotoPath = $targetFile;
                if ($fotoLama && file_exists($fotoLama)) unlink($fotoLama);
            } else {
                $errors[] = "Gagal upload file.";
            }
        }
    }

    if (empty($errors)) {
        $contactData = [
            'nama' => $nama,
            'email' => $email,
            'telepon' => $telepon,
            'foto' => $fotoPath
        ];

        if ($editIndex !== null) {
            $_SESSION['contacts'][$editIndex] = $contactData;
        } else {
            $_SESSION['contacts'][] = $contactData;
        }
        header('Location: TA_4.php');
        exit;
    }
    
    $editData = ['nama' => $nama, 'email' => $email, 'telepon' => $telepon, 'foto' => $fotoPath];
    $editMode = ($editIndex !== null);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugas Akhir 4 - Manajemen Kontak</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="container">
        <h1 class="header-title">Form Kontak</h1>

        <div class="main-wrapper">

            <div class="form-column">
                <div class="card">
                    <h2><?php echo $editMode ? 'Edit Kontak' : 'Tambah Kontak'; ?></h2>

                    <?php if (!empty($errors)): ?>
                        <div class="alert-error">
                            <strong>Error! :</strong>
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="TA_4.php" method="POST" enctype="multipart/form-data">
                        <?php if ($editMode): ?>
                            <input type="hidden" name="edit_index" value="<?php echo $editIndex; ?>">
                        <?php endif; ?>
                        <input type="hidden" name="foto_lama" value="<?php echo htmlspecialchars($editData['foto']); ?>">

                        <div class="form-group">
                            <label for="nama">Nama Lengkap</label>
                            <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($editData['nama']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($editData['email']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="telepon">No. Telepon</label>
                            <input type="tel" id="telepon" name="telepon" value="<?php echo htmlspecialchars($editData['telepon']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="foto">Foto Profil</label>
                            <input type="file" id="foto" name="foto">
                            <?php if ($editMode && !empty($editData['foto'])): ?>
                                <div style="margin-top: 10px; display: flex; align-items: center; gap: 10px;">
                                    <img src="<?php echo htmlspecialchars($editData['foto']); ?>" class="contact-img">
                                    <span style="font-size: 0.8rem; color: #666;">Foto saat ini</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?php echo $editMode ? 'Simpan Perubahan' : 'Simpan Kontak'; ?>
                        </button>

                        <?php if ($editMode): ?>
                            <a href="TA_4.php" class="btn btn-secondary">Batal</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="table-column">
                <div class="card">
                    <h2>Daftar Data Kontak</h2>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>No. Telepon</th>
                                    <th style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($_SESSION['contacts'])): ?>
                                    <tr>
                                        <td colspan="5" class="empty-message">
                                            Belum ada data kontak.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($_SESSION['contacts'] as $index => $contact): ?>
                                        <tr>
                                            <td>
                                                <?php $fotoSrc = $contact['foto'] ? $contact['foto'] : 'dataGambar/default.png'; ?>
                                                <img src="<?php echo htmlspecialchars($fotoSrc); ?>" 
                                                     class="contact-img" 
                                                     onerror="this.src='https://via.placeholder.com/40'">
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($contact['nama']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($contact['email']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($contact['telepon']); ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <a href="TA_4.php?action=edit&id=<?php echo $index; ?>" class="action-link link-edit">Edit</a>
                                                <a href="TA_4.php?action=delete&id=<?php echo $index; ?>" class="action-link link-delete" 
                                                   onclick="return confirm('Yakin Dihapus??');">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

</body>
</html>