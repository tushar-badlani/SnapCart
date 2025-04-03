<?php
// purchase_success.php
session_start();

// Check if user came from checkout
if (!isset($_SESSION['checkout_success']) || $_SESSION['checkout_success'] !== true) {
    header('Location: shop.php');
    exit;
}

// Reset checkout success flag
$_SESSION['checkout_success'] = false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Successful - SnapCart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8 text-center">
        <div class="mb-8">
            <div class="bg-green-100 rounded-full p-3 w-16 h-16 flex items-center justify-center mx-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>
        
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Purchase Successful!</h1>
        <p class="text-gray-600 mb-8">Thank you for your purchase. Your items have been processed successfully.</p>
        
        <div class="flex justify-center">
            <a href="shop.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                Return to Shop
            </a>
        </div>
    </div>
</body>
</html>