<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pelanggan') {
    header("Location: ../../login.php");
    exit;
}

require '../../dbconnection/dbconnection.php';

$id_users = $_SESSION['user_id'];

$sql = "SELECT nama_lengkap, role, photo_profile FROM master_user WHERE id_users = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $id_users);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

$adminName = $admin ? $admin['nama_lengkap'] : 'Pelanggan';
$adminRole = $admin ? ucfirst($admin['role']) : 'Pelanggan';

if ($admin && $admin['photo_profile']) {
    $photoUrl = '../../assets/img/uploads/profile/' . $admin['photo_profile'];
} else {
    $photoUrl = 'https://ui-avatars.com/api/?name=' . urlencode($adminName) . '&background=random';
}

$id_alamat_final = $_SESSION['id_alamat_pilihan'] ?? null;

if ($id_alamat_final) {
    $query = "SELECT a.*, u.nama_lengkap, u.no_wa
              FROM master_alamat a
              JOIN master_user u ON a.id_users = u.id_users
              WHERE a.id_alamat = ? AND a.is_deleted = 0";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $id_alamat_final);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT a.*, u.nama_lengkap, u.no_wa
              FROM master_alamat a
              JOIN master_user u ON a.id_users = u.id_users
              WHERE a.id_users = ? AND a.is_deleted = 0
              ORDER BY a.id_alamat DESC LIMIT 1";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $id_users);
    $stmt->execute();
    $result = $stmt->get_result();
}

$stmt->execute();
$result = $stmt->get_result();
$dataAlamat = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gawe - Pelanggan</title>
    <link rel="icon" type="image/png" href="../../assets/img/logo/gaweicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/components/sc1-alert.css">
    <script src="../../assets/components/sc1-alert.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1c19b0, #abb5e9, #dceaf6, #2584c9);
            overflow-x: hidden;
            color: #1f2937;
        }

        button,
        input,
        textarea {
            font-family: inherit;
        }

        .app-container {
            width: 100%;
            min-height: 100vh;

            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .top-bar {
            position: absolute;
            top: 0;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            z-index: 10;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
        }

        .profile-img {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s;
        }

        .profile-img2 {
            width: 85px;
            height: 85px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s;
        }

        .profile-section:hover .profile-img {
            transform: scale(1.05);
        }

        .profile-name {
            color: white;
            font-weight: 600;
            font-size: 1.125rem;
            text-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .logo-box {
            width: 3.2rem;
            height: 3.2rem;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 0.25rem;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .logo-box:hover {
            transform: scale(1.05);
        }

        .logo-box img {
            border-radius: 50%;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        @media (min-width: 768px) {
            .top-bar {
                padding: 2rem 3rem;
            }

            .profile-section {
                gap: 1rem;
            }

            .profile-img {
                width: 4.5rem;
                height: 4.5rem;
            }

            .logo-box {
                width: 5rem;
                height: 5rem;
            }

            .profile-name {
                font-size: 1.25rem;
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .typing-cursor::after {
            content: '|';
            animation: blink 1s step-end infinite;
        }

        @keyframes blink {
            50% {
                opacity: 0;
            }
        }

        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .hero-section {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
            padding: 0 1.5rem;
            max-width: 500px;
            z-index: 10;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .hero-title-container {
            width: 100%;
            margin-bottom: 1.5rem;
        }

        .hero-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            text-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .hero-badge {
            background-color: white;
            color: #4f46e5;
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 1.25rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .hero-subtitle {
            display: none;
            color: #e0e7ff;
            margin-top: 1rem;
            font-size: 1.125rem;
            font-weight: 500;
            text-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .input-container {
            width: 100%;
            position: relative;
        }

        .search-input {
            width: 100%;
            background-color: white;
            color: #374151;
            padding: 1rem 1.5rem;
            border-radius: 9999px;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            outline: none;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .search-input::placeholder {
            color: #9ca3af;
        }

        .search-input:hover {
            box-shadow: 0 25px 50px -12px rgba(99, 102, 241, 0.3);
        }

        .typing-text {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.875rem;
            font-weight: 500;
            pointer-events: none;
        }

        @media (max-width: 767px) {
            .hero-section.active-state {
                top: 20%;
            }
        }

        @media (min-width: 768px) {
            .hero-section {
                max-width: 600px;
                text-align: left;
                align-items: flex-start;
            }

            .hero-title {
                font-size: 2.25rem;
                justify-content: flex-start;
                gap: 0.75rem;
            }

            .hero-badge {
                font-size: 1.875rem;
            }

            .hero-subtitle {
                display: block;
            }

            .hero-title-container {
                margin-bottom: 2rem;
            }

            .input-container {
                width: 110%;
            }

            .search-input {
                padding: 1.25rem 2rem;
                font-size: 1.125rem;
            }

            .typing-text {
                left: 2rem;
                font-size: 1rem;
            }

            .hero-section.active-state {
                left: 30%;
            }
        }

        .form-panel {
            position: absolute;
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: #f8fafc;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 20;
        }

        .drag-handle-container {
            width: 100%;
            display: flex;
            justify-content: center;
            padding-top: 0.75rem;
            padding-bottom: 0.25rem;
            cursor: pointer;
        }

        .drag-handle {
            width: 3rem;
            height: 0.375rem;
            background-color: #d1d5db;
            border-radius: 9999px;
        }

        .desktop-close-btn {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            color: #9ca3af;
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.2s;
            z-index: 30;
            font-size: 1.5rem;
        }

        .desktop-close-btn:hover {
            color: #ef4444;
        }

        .tab-section {
            display: flex;
            justify-content: center;
            margin-top: 0.5rem;
            margin-bottom: 1rem;
            padding: 0 1rem;
            position: relative;
            z-index: 20;
        }

        .tab-bg {
            background-color: #e5e7eb;
            border-radius: 9999px;
            padding: 0.25rem;
            display: flex;
            width: 66.666667%;
        }

        .tab-btn {
            width: 50%;
            border: none;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.5rem 0;
            border-radius: 9999px;
            cursor: pointer;
            transition: color 0.2s;
        }

        .tab-btn.active {
            background-color: white;
            color: #374151;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .tab-btn:not(.active) {
            background-color: transparent;
            color: #6b7280;
        }

        .tab-btn:not(.active):hover {
            color: #374151;
        }

        .form-content {
            flex: 1;
            overflow-y: auto;
            padding: 0 1.25rem 6rem;
        }

        .card-box {
            background-color: white;
            padding: 1rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            border: 1px solid #f3f4f6;
            margin-bottom: 1rem;
            transition: all 0.2s;
        }

        .address-card {
            cursor: pointer;
        }

        .address-card:hover {
            border-color: #a5b4fc;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .address-card:active {
            transform: scale(0.98);
        }

        .address-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .address-info {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
        }

        .address-icon {
            background-color: #eef2ff;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .address-icon i {
            color: #4f46e5;
            font-size: 0.875rem;
        }

        .address-name {
            font-size: 0.875rem;
            font-weight: 700;
            color: #1f2937;
        }

        .address-phone {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 400;
            display: block;
            margin-top: 0.1rem;
        }

        .address-detail {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.375rem;
            line-height: 1.625;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .chevron-icon {
            color: #9ca3af;
            margin-left: 0.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.625rem;
        }

        .textarea-box {
            background-color: #f9fafb;
            border-radius: 0.5rem;
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
        }

        .textarea-box:focus-within {
            border-color: #818cf8;
            background-color: white;
        }

        .textarea-box textarea {
            width: 100%;
            background: transparent;
            border: none;
            outline: none;
            font-size: 0.875rem;
            color: #374151;
            resize: none;
        }

        .textarea-box textarea::placeholder {
            color: #9ca3af;
        }

        .char-count-display {
            text-align: right;
            font-size: 0.625rem;
            color: #9ca3af;
            margin-top: 0.25rem;
            font-weight: 500;
        }

        .text-red-500 {
            color: #ef4444 !important;
        }

        .text-gradient {
            background: linear-gradient(135deg, #1c19b0, #abb5e9, #dceaf6, #2584c9);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .upload-content {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .upload-box {
            border: 2px dashed #d1d5db;
            border-radius: 0.75rem;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        #image-preview {
            width: 100%;
            max-width: 100%;

            height: auto;
            object-fit: contain;
            border-radius: 0.5rem;
            display: none;

        }

        .upload-box:hover {
            border-color: #818cf8;
            background-color: rgba(238, 242, 255, 0.5);
        }

        .upload-icon {
            width: 3rem;
            height: 3rem;
            background-color: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            transition: background-color 0.2s;
        }

        .upload-icon i {
            font-size: 1.25rem;
            color: #6b7280;
            transition: color 0.2s;
        }

        .upload-box:hover .upload-icon {
            background-color: #e0e7ff;
        }

        .upload-box:hover .upload-icon i {
            color: #4f46e5;
        }

        .upload-title {
            font-size: 0.75rem;
            color: #4b5563;
            font-weight: 500;
        }

        .upload-box:hover .upload-title {
            color: #4338ca;
        }

        .upload-subtitle {
            font-size: 0.625rem;
            color: #9ca3af;
            margin-top: 0.25rem;
        }

        .bottom-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: white;
            border-top: 1px solid #f3f4f6;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .price-input-box {
            flex: 1;
            display: flex;
            align-items: center;
            background-color: #f9fafb;
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            transition: border-color 0.2s;
        }

        .price-input-box:focus-within {
            border-color: #818cf8;
        }

        .currency-symbol {
            font-size: 1.125rem;
            font-weight: 700;
            color: #1f2937;
            margin-right: 0.5rem;
        }

        .price-input {
            width: 100%;
            font-size: 0.875rem;
            border: none;
            outline: none;
            background: transparent;
            color: #374151;
            padding: 0.5rem 0;
            font-weight: 500;
        }

        .price-input::placeholder {
            color: #9ca3af;
        }

        .btn-submit {
            background-color: #4f46e5;
            color: white;
            border: none;
            font-weight: 700;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(199, 210, 254, 0.5);
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-submit:hover {
            background-color: #4338ca;
        }

        .btn-submit:active {
            transform: scale(0.95);
        }

        .riwayat-container {
            flex: 1;
            height: 1000px;
            padding: 0 1.25rem 6rem;
        }

        .sub-tab-container {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #ddd;
        }

        .sub-tab-btn {
            background: none;
            border: none;
            padding: 0.5rem 0;
            color: #666;
            font-size: 0.95rem;
            cursor: pointer;
            position: relative;
            transition: 0.3s;
        }

        .sub-tab-btn.active {
            color: #4f46e5;
            font-weight: 600;
        }

        .sub-tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background: #4f46e5;
        }

        .riwayat-card {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            align-items: flex-start;
        }

        .riwayat-img {
            width: 70px;
            height: 70px;
            border-radius: 0.5rem;
            object-fit: cover;
            flex-shrink: 0;
        }

        .riwayat-info {
            flex: 1;
        }

        .riwayat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 0.8rem;
            margin-bottom: 0.4rem;
        }

        .riwayat-pekerja {
            font-weight: 600;
            color: #333;
        }

        .riwayat-status {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 4px 12px;
            border-radius: 7px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            font-size: 0.75rem;
        }

        .riwayat-title {
            font-size: 0.95rem;
            font-weight: 600;
            margin: 0 0 0.3rem 0;
            color: #333;
        }

        .riwayat-desc {
            font-size: 0.8rem;
            color: #666;
            margin: 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .riwayat-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            margin-top: 0.8rem;
        }

        #preview-lapor {
            margin-top: 15px;
            border: 2px dashed #4f46e5;

            padding: 8px;
            border-radius: 10px;
            background-color: #f0f7ff;
            display: none;

        }

        #img-preview-lapor {
            width: 100%;
            display: block;
            border-radius: 6px;
            object-fit: contain;

            max-height: 250px;

        }

        .lapor-upload-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;

            height: 150px;
            border: 2px dashed #ddd;
            border-radius: 10px;
            overflow: hidden;
            position: relative;

            cursor: pointer;
            border: 2px solid #ddd;
            transition: all 0.3s ease;
            transition: background 0.3s;
        }

        .lapor-upload-box:hover {
            border-color: #4f46e5;
            background-color: #f9f9f9;
        }

        .lapor-upload-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;

            position: absolute;
            top: 0;
            left: 0;
        }

        .lapor-upload-box.has-image .upload-icon,
        .lapor-upload-box.has-image .upload-text-modal {
            display: none;
        }

        .btn-lapor {
            background-color: #ff4d4d;

            color: white;
            border: none;
            padding: 0.35rem 1.2rem;
            border-radius: 0.4rem;
            font-size: 0.85rem;
            border: 1px solid #ff4d4d;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-nilai {
            background-color: white;
            color: #4285f4;

            border: 1px solid #4285f4;
            padding: 0.35rem 1.2rem;
            border-radius: 0.4rem;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);

            display: none;

            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: white;
            border-radius: 1.5rem;
            width: 280px;
            padding: 1.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal-content {
            transform: scale(1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            font-size: 1.1rem;
            margin: 0;
            font-weight: 600;
        }

        .icon-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #333;
        }

        .modal-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
        }

        .modal-name {
            font-size: 1.1rem;
            margin: 0 0 0.25rem 0;
            font-weight: 500;
        }

        .modal-role {
            color: #4f46e5;

            font-weight: 700;
            font-style: italic;
            margin: 0 0 1.5rem 0;
            font-size: 0.9rem;
        }

        .logout-btn {
            width: 20px;
            height: 20px;
            background: none;
            border: 2px solid #555;
            border-radius: 0.5rem;
            padding: 0.4rem 0.6rem;
            font-size: 1.2rem;
            cursor: pointer;
            color: #555;
            transition: 0.2s ease;
        }

        .logout-btn:hover {
            background: #f5f5f5;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(3px);

            -webkit-backdrop-filter: blur(3px);
            z-index: 1000;
            display: none;

        }

        .custom-modal {
            position: fixed;
            top: 50%;
            max-height: 90vh;
            left: 50%;
            transform: translate(-50%, -50%);
            overflow-y: auto;
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            width: 85%;
            max-width: 320px;
            z-index: 1001;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            display: none;

        }

        .modal-overlay.show,
        .custom-modal.show {
            display: block;
        }

        .modal-title {
            text-align: center;
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0 0 1.2rem 0;
            color: #000;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .modal-label {
            display: block;
            font-size: 0.75rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .lapor-textarea {
            width: 100%;
            padding: 0.8rem;
            background: #f5f5f5;
            border: none;
            border-radius: 8px;
            resize: none;
            height: 90px;
            font-size: 0.75rem;
            color: #666;
            box-sizing: border-box;
            font-family: inherit;
        }

        .lapor-upload-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 1px dashed #999;
            border-radius: 8px;
            padding: 1.5rem;
            cursor: pointer;
            color: #4b5563;
            transition: 0.3s;
        }

        .lapor-upload-box:hover {
            background: #f9f9f9;
        }

        .upload-text-modal {
            font-size: 0.7rem;
            color: #666;
            font-weight: 500;
        }

        .btn-confirm {
            display: block;
            margin: 1.2rem auto 0;
            padding: 0.5rem 1.5rem;
            width: 120px;
            background-color: #4f46e5;

            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            text-align: center;
        }

        .star-rating-container {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 2rem 0;
        }

        .star-item {
            font-size: 2.2rem;
            cursor: pointer;
            color: #bbb;
            transition: 0.2s;
        }

        .star-item:hover {
            transform: scale(1.2);

        }

        .star-item.active {
            color: #ffb703;

        }

        .success {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            width: 320px;
            padding: 12px;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: start;
            background: #EDFBD8;
            border-radius: 8px;
            border: 1px solid #84D65A;
            box-shadow: 0px 0px 5px -3px #111;
        }

        .success__icon {
            width: 20px;
            height: 20px;
            transform: translateY(-2px);
            margin-right: 8px;
        }

        .success__icon path {
            fill: #84D65A;
        }

        .success__title {
            font-weight: 500;
            font-size: 14px;
            color: #2B641E;
        }

        .success__close {
            width: 20px;
            height: 20px;
            cursor: pointer;
            margin-left: auto;
        }

        .success__close path {
            fill: #2B641E;
        }

        .custom-alert-wrapper {
            position: absolute;
            bottom: 90px;

            left: 50%;
            transform: translateX(-50%);

            z-index: 999;
            display: none;

            width: 90%;
            max-width: 320px;
            animation: slideUpFade 0.3s ease forwards;
        }

        .avatar-upload-container {
            position: relative;
            width: 120px;
            /* Sesuaikan ukuran lingkaran profil Anda */
            height: 120px;
            margin: auto;
            /* Agar rata tengah */
            cursor: pointer;
        }

        .avatar-upload-container:hover .profile-img {
            filter: brightness(80%);
        }

        .edit-pencil-badge {
            position: absolute;
            bottom: 80%;
            right: 5px;
            /* Jarak dari kanan */
            background: #4f46e5;
            /* Warna indigo (sesuai tema sebelumnya) */
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            border: 2px solid #fff;
            /* Garis tepi putih */
            transition: transform 0.3s ease;
            z-index: 10;
            /* Memastikan di atas gambar */
        }

        /* Efek pensil membesar sedikit saat hover */
        .avatar-upload-container:hover .edit-pencil-badge {
            transform: scale(1.1);
        }

        .cancel-badge {
            position: absolute;
            bottom: 25px;
            left: 5px;
            /* Taruh di kiri bawah */
            background: #ff4d4d;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: none;
            /* Awalnya sembunyi */
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            border: 2px solid #fff;
            cursor: pointer;
            z-index: 1111;
            transition: transform 0.2s;
        }

        .cancel-badge:hover {
            transform: scale(1.1);
        }

        .save-check-badge {
            position: absolute;
            bottom: 25px;
            right: 5px;
            background: #25D366;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: none;
            /* Awalnya tidak terlihat */
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            border: 2px solid #fff;
            cursor: pointer;
            z-index: 1111;
            transition: transform 0.2s;
        }

        .save-check-badge:hover {
            transform: scale(1.1);
        }

        .edit-pencil-badge,
        .cancel-badge,
        .save-check-badge {
            -webkit-tap-highlight-color: transparent;
            user-select: none;
            /* Mencegah teks/icon terpilih saat klik cepat */
        }

        /* Sembunyikan input file aslinya tapi tetap bisa diklik */
        #file-input {
            display: none;
        }

        @keyframes slideUpFade {
            from {
                opacity: 0;
                transform: translate(-50%, 20px);
            }

            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }

        @media (max-width: 767px) {
            .form-panel {
                bottom: 0;
                left: 0;
                right: 0;
                height: 75vh;
                border-top-left-radius: 1.5rem;
                border-top-right-radius: 1.5rem;
                transform: translateY(100%);
                box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.1);
            }

            .form-panel.active-state {
                transform: translateY(0);
            }

            .desktop-close-btn {
                display: none;
            }
        }

        @media (min-width: 768px) {
            .form-panel {
                top: 50%;
                right: 10%;
                width: 450px;
                max-height: 85vh;
                border-radius: 1.5rem;
                transform: translateY(-50%) translateX(100px);
                opacity: 0;
                pointer-events: none;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            }

            .form-panel.active-state {
                transform: translateY(-50%) translateX(0);
                opacity: 1;
                pointer-events: auto;
            }

            .drag-handle-container {
                display: none;
            }

            .tab-section {
                margin-top: 1.5rem;
            }

            .tab-bg {
                width: 75%;
            }

            .tab-btn {
                font-size: 0.875rem;
                padding: 0.625rem 0;
            }

            .form-content {
                padding-bottom: 7rem;
                padding-left: 1.25rem;
                padding-right: 1.25rem;
            }

            .card-box {
                padding: 1.25rem;
            }

            .address-name {
                font-size: 1rem;
            }

            .address-phone {
                display: inline;
                margin-left: 0.25rem;
                margin-top: 0;
            }

            .address-detail {
                font-size: 0.875rem;
            }

            .form-label {
                font-size: 0.875rem;
            }

            .textarea-box textarea {
                font-size: 1rem;
            }

            .char-count-display {
                font-size: 0.75rem;
            }

            .upload-box {
                padding: 2rem;
            }

            .upload-title {
                font-size: 0.875rem;
            }

            .upload-subtitle {
                font-size: 0.75rem;
            }

            .bottom-bar {
                padding: 1.25rem;
            }

            .currency-symbol {
                font-size: 1.25rem;
            }

            .price-input {
                font-size: 1rem;
            }

            .btn-submit {
                padding: 0.875rem 2.5rem;
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>

    <div class="app-container">

        <div class="top-bar">
            <div class="profile-section">
                <img src="<?= $photoUrl; ?>" alt="Profile" class="profile-img">
                <span class="profile-name" id="pelangganNama"><?= $adminName ?></span>
            </div>
            <div class="logo-box">
                <img src="/assets/img/logo/gawelogo.png" alt="Logo"
                    onerror="this.src='https://ui-avatars.com/api/?name=GW&background=3b82f6&color=fff&rounded=true'">
            </div>
        </div>

        <div id="hero-section" class="hero-section">
            <div class="hero-title-container animate-fade-in-up">
                <h1 class="hero-title">
                    <span class="hero-badge"><span class="text-gradient">Bantu</span></span> Pekerjaan Kamu Sehari-hari.
                </h1>
                <p class="hero-subtitle">Temukan pekerja terpercaya untuk segala kebutuhan rumah tangga Anda dalam satu
                    klik.</p>
            </div>

            <div class="input-container animate-fade-in-up" style="animation-delay: 0.3s;">
                <input type="text" id="main-input" class="search-input" placeholder="">
                <span id="typing-text" class="typing-text typing-cursor"></span>
            </div>
        </div>

        <div id="form-panel" class="form-panel">

            <div class="drag-handle-container" id="close-sheet">
                <div class="drag-handle"></div>
            </div>

            <button id="close-desktop" class="desktop-close-btn">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="tab-section">
                <div class="tab-bg">
                    <button class="tab-btn active" id="tab-order">Order</button>
                    <button class="tab-btn" id="tab-riwayat">Riwayat</button>
                </div>
            </div>

            <div class="panel-content-wrapper hide-scrollbar" style="overflow-y: auto; height: calc(100% - 80px);">

                <div id="content-order" style="display: block;">
                    <?php include 'order.php'; ?>
                </div>

                <div id="content-riwayat" style="display: none;">
                    <?php include 'riwayat.php'; ?>
                </div>

            </div>

        </div>
    </div>

    <div id="profile-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <button id="close-profile-modal" class="icon-btn">
                    <i class="fas fa-arrow-left"></i> </button>
                <h2>Profile</h2>
                <div style="width: 24px;"></div>
            </div>

            <div class="modal-body">
                <form id="profileForm" enctype="multipart/form-data">
                    <div class="avatar-upload-container" id="avatarTrigger">

                        <input type="file" name="profile_photo" id="file-input" accept="image/*">

                        <img src="<?= $photoUrl; ?>" alt="Profile" class="profile-img2" id="previewImg">

                        <div class="edit-pencil-badge" id="btnEdit">
                            <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor;">
                                <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" />
                            </svg>
                        </div>
                        <div class="cancel-badge" id="btnCancel" title="Batalkan">
                            <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor;">
                                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                            </svg>
                        </div>
                        <button type="submit" class="save-check-badge" id="btnSave" title="Simpan Foto">
                            <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor;">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                            </svg>
                        </button>
                    </div>
                </form>
                <div class="modal-name" id="pelangganName"></div>
                <div class="modal-role" id="pelangganRole"></div>

                <a class="logout-btn" onclick="window.location.href='function/logoutUser.php'"><i
                        class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </div>

    <script>
        const btnEdit = document.getElementById('btnEdit');
        const btnSave = document.getElementById('btnSave');
        const btnCancel = document.getElementById('btnCancel'); // Baru
        const fileInputProfile = document.getElementById('file-input'); // Pastikan ID ini konsisten
        const previewImg = document.getElementById('previewImg');

        // Simpan foto asli untuk kebutuhan tombol "Batal"
        const fotoAsli = previewImg.src;

        // 1. Klik Pensil
        btnEdit.addEventListener('click', () => {
            fileInputProfile.click();
        });

        // 2. Pilih File
        fileInputProfile.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                // Cek ukuran (5MB)
                if (this.files[0].size > 2 * 1024 * 1024) {
                    SC1Alert.show('File tidak boleh lebih dari 2 MB.', 'warning');
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImg.src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);

                // Tampilkan tombol Save dan Cancel, Sembunyikan Edit
                btnEdit.style.display = 'none';
                btnSave.style.display = 'flex';
                btnCancel.style.display = 'flex';
            }
        });

        btnCancel.addEventListener('click', () => {
            previewImg.src = fotoAsli;

            // Reset tombol
            btnSave.style.display = 'none';
            btnCancel.style.display = 'none';
            btnEdit.style.display = 'flex';

            fileInput.value = '';
        });

        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            // BIKIN FORMDATA MANUAL
            const formData = new FormData();

            // Masukkan file secara eksplisit
            if (fileInput.files.length > 0) {
                formData.append('profile_photo', fileInput.files[0]);
            } else {
                SC1Alert.show('Pilih gambar dulu!', 'error');
                return;
            }

            const btnEdit = document.getElementById('btnEdit');
            const btnSave = document.getElementById('btnSave'); // 'this' sudah merujuk ke form

            btnSave.disabled = true;
            btnSave.style.opacity = '0.5';

            try {
                const response = await fetch('function/editPhotoProfile.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    await SC1Alert.show(result.message, 'success');

                    setTimeout(() => {
                        window.location.reload(); 
                    }, 1000);

                    btnSave.style.display = 'flex';
                    btnEdit.style.display = 'flex';
                } else {
                    await SC1Alert.show('Gagal: ' + result.message, 'error');
                }
            } catch (err) {
                console.error(err);
                await SC1Alert.show('Terjadi kesalahan sistem.', 'error');
            } finally {
                btnSave.disabled = false;
                btnSave.style.opacity = '1';
            }
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", async () => {

            try {
                const response = await fetch('function/getDataProfile.php');
                const result = await response.json();
                if (result.status === "success") {
                    const data = result.data;
                    const nameEl = document.getElementById('pelangganName');
                    const roleEl = document.getElementById('pelangganRole');

                    if (nameEl) nameEl.textContent = data.nama;
                    if (roleEl) {
                        roleEl.textContent = data.role.charAt(0).toUpperCase() + data.role.slice(1);
                    }
                }
            } catch (error) {
                console.error("terjadi kesalahan", error);
            }

            const tabOrderBtn = document.getElementById('tab-order');
            const tabRiwayatBtn = document.getElementById('tab-riwayat');
            const contentOrder = document.getElementById('content-order');
            const contentRiwayat = document.getElementById('content-riwayat');

            if (tabOrderBtn && tabRiwayatBtn && contentOrder && contentRiwayat) {
                tabOrderBtn.addEventListener('click', () => {
                    contentOrder.style.display = 'block';
                    contentRiwayat.style.display = 'none';
                    tabOrderBtn.classList.add('active');
                    tabRiwayatBtn.classList.remove('active');
                });

                tabRiwayatBtn.addEventListener('click', () => {
                    contentOrder.style.display = 'none';
                    contentRiwayat.style.display = 'block';
                    tabOrderBtn.classList.remove('active');
                    tabRiwayatBtn.classList.add('active');
                });
            }

            // 3. PROFILE MODAL (TETAP AMAN)
            const profileSection = document.querySelector('.profile-section');
            const profileModal = document.getElementById('profile-modal');
            const closeProfileBtn = document.getElementById('close-profile-modal');

            profileSection.addEventListener('click', () => {
                profileModal.classList.add('active');
            });

            closeProfileBtn.addEventListener('click', () => {
                profileModal.classList.remove('active');
            });

            profileModal.addEventListener('click', (e) => {
                if (e.target === profileModal) {
                    profileModal.classList.remove('active');
                }
            });

            // 4. ANIMASI NGETIK & FORM ORDER (TETAP AMAN)
            const typingTextElement = document.getElementById("typing-text");
            const mainInput = document.getElementById("main-input");
            const textToType = "Apa keluhan anda hari ini?";

            if (mainInput && mainInput.value.trim() !== "") {
                typingTextElement.style.display = "none";
            } else if (typingTextElement.getAttribute("data-animated") === "true" || typingTextElement.textContent.trim() !== "") {
                typingTextElement.textContent = textToType;
                typingTextElement.classList.remove("typing-cursor");
            } else {
                typingTextElement.setAttribute("data-animated", "true");
                let charIndex = 0;
                let isDeleting = false;
                let isTypingActive = true;

                function typeEffect() {
                    if (mainInput && mainInput.value.trim() !== "") {
                        typingTextElement.style.display = "none";
                        return;
                    }
                    if (!isTypingActive || !typingTextElement) return;

                    const currentText = textToType.substring(0, charIndex);
                    typingTextElement.textContent = currentText;

                    let typingSpeed = isDeleting ? 50 : 100;

                    if (!isDeleting && charIndex === textToType.length) {
                        typingSpeed = 2000;
                        isDeleting = true;
                    } else if (isDeleting && charIndex === 0) {
                        typingSpeed = 500;
                        isDeleting = false;
                    }

                    charIndex += isDeleting ? -1 : 1;
                    setTimeout(typeEffect, typingSpeed);
                }
                setTimeout(typeEffect, 1500);
            }

            const heroSection = document.getElementById("hero-section");
            const formPanel = document.getElementById("form-panel");
            const closeSheetBtn = document.getElementById("close-sheet");
            const closeDesktopBtn = document.getElementById("close-desktop");

            mainInput.addEventListener("focus", () => {
                if (typeof isTypingActive !== 'undefined') isTypingActive = false;
                typingTextElement.style.display = "none";
                mainInput.style.paddingLeft = window.innerWidth >= 768 ? "2rem" : "1.5rem";
            });

            mainInput.addEventListener("keypress", (e) => {
                if (e.key === "Enter") {
                    if (mainInput.value.trim() !== "") {
                        heroSection.classList.add("active-state");
                        formPanel.classList.add("active-state");
                        mainInput.blur();
                    }
                }
            });

            mainInput.addEventListener("blur", () => {
                if (mainInput.value.trim() === "") {
                    typingTextElement.style.display = "block";
                    if (typeof isTypingActive !== 'undefined') isTypingActive = true;
                    if (typeof typeEffect === 'function') typeEffect();
                    mainInput.style.paddingLeft = "";
                }
            });

            const closeForm = () => {
                heroSection.classList.remove("active-state");
                formPanel.classList.remove("active-state");
                mainInput.value = "";
                typingTextElement.style.display = "block";
                typingTextElement.classList.add("typing-cursor");
                if (typeof isTypingActive !== 'undefined') isTypingActive = true;
                if (typeof charIndex !== 'undefined') charIndex = 0;
                if (typeof isDeleting !== 'undefined') isDeleting = false;
                if (typeof typeEffect === 'function') typeEffect();
                mainInput.style.paddingLeft = "";
            };

            closeSheetBtn.addEventListener("click", closeForm);
            closeDesktopBtn.addEventListener("click", closeForm);

            // 5. VALIDASI KELUHAN & HARGA (TETAP AMAN)
            const textarea = document.getElementById("keluhan-textarea");
            const charCount = document.getElementById("char-count");

            textarea.addEventListener("input", function() {
                const currentLength = this.value.length;
                charCount.textContent = currentLength;
                if (currentLength >= 150) {
                    charCount.classList.add("text-red-500");
                } else {
                    charCount.classList.remove("text-red-500");
                }
            });

            const priceInput = document.getElementById("price-input");
            priceInput.addEventListener("keydown", function(e) {
                if (e.key === "-" || e.key === "e" || e.key === "E") {
                    e.preventDefault();
                }
            });

            priceInput.addEventListener("input", function() {
                if (this.value < 0) {
                    this.value = Math.abs(this.value);
                }
            });
        });
    </script>

</body>

</html>