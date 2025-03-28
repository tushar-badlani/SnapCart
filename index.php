<?php
// index.php

// --------------------------
// DATABASE CONFIGURATION (Optional)
// --------------------------
$con = mysqli_connect("localhost","root","","cookie");
 
// Check connection
if (mysqli_connect_errno())
  {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }

// --------------------------
// API KEY & ERROR/RESULT INIT
// --------------------------
$api_key = 'AIzaSyCVrcYnoDtbJsAD0paHYhny4KxcvOdLOro'; // Replace with your actual API key.
$error = null;
$groceryItems = null;
$databaseResults = null;
$cart = [];

// --------------------------
// HELPER FUNCTION: Extract JSON from API Response
// --------------------------
function extractJsonFromResponse($response) {
    // The API returns a response with a "candidates" array.
    if (isset($response['candidates']) && is_array($response['candidates'])) {
        foreach ($response['candidates'] as $candidate) {
            if (isset($candidate['content']['parts']) && is_array($candidate['content']['parts'])) {
                foreach ($candidate['content']['parts'] as $part) {
                    if (isset($part['text'])) {
                        // Look for the code block marked with ```json
                        if (preg_match('/```json\s*(\[[\s\S]*?\])\s*```/', $part['text'], $matches)) {
                            $json_output = $matches[1];
                            $items = json_decode($json_output, true);
                            if ($items !== null) {
                                return $items;
                            }
                        }
                    }
                }
            }
        }
    }
    return null;
}

// --------------------------
// SESSION MANAGEMENT FOR CART
// --------------------------
session_start();
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cart = &$_SESSION['cart'];

// --------------------------
// CART MANAGEMENT ACTIONS
// --------------------------
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_to_cart':
            if (isset($_POST['item']) && isset($_POST['quantity'])) {
                $newItem = [
                    'item' => $_POST['item'],
                    'quantity' => intval($_POST['quantity'])
                ];
                $cart[] = $newItem;
            }
            break;
        case 'clear_cart':
            $cart = [];
            break;
    }
    header('Content-Type: application/json');
    echo json_encode(['cart' => $cart, 'cart_count' => count($cart)]);
    exit;
}

// --------------------------
// PROCESS UPLOADED IMAGE
// --------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['shopping_list'])) {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $uploadedFile = $uploadDir . basename($_FILES['shopping_list']['name']);

    if (move_uploaded_file($_FILES['shopping_list']['tmp_name'], $uploadedFile)) {
        // Read and encode the image file in base64.
        $imageBytes  = file_get_contents($uploadedFile);
        $imageBase64 = base64_encode($imageBytes);
        $mimeType    = $_FILES['shopping_list']['type'];

        // Build the payload with inline base64 image data.
        $payload = [
            "contents" => [
                [
                    "role"  => "user",
                    "parts" => [
                        [
                            "inline_data" => [
                                "mime_type" => $mimeType,
                                "data"      => $imageBase64
                            ]
                        ]
                    ]
                ]
            ],
            "systemInstruction" => [
                "role"  => "user",
                "parts" => [
                    [
                        "text" => "You are a smart assistant. I will give you an image of a shopping list; convert it to JSON with grocery items in the format {\"item\": <string>, \"quantity\": <number>} (default quantity is 1). Do not include any extra formatting."
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature"       => 1,
                "topK"              => 40,
                "topP"              => 0.95,
                "maxOutputTokens"   => 8192,
                "responseMimeType"  => "text/plain"
            ]
        ];
        
        $jsonPayload = json_encode($payload);

        // Set up the API endpoint URL.
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $api_key;

        // Initialize a cURL session.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $apiResponse = curl_exec($ch);
        if (curl_errno($ch)) {
            $error = "cURL error: " . curl_error($ch);
        }
        curl_close($ch);

        // Decode the API response.
        $decodedResponse = json_decode($apiResponse, true);
        if (!$decodedResponse) {
            $error = "Failed to decode API response: " . $apiResponse;
        } else {
            // Use our helper function to extract JSON grocery items.
            $groceryItems = extractJsonFromResponse($decodedResponse);
            if (!$groceryItems) {
                $error = "Failed to extract JSON from API response: " . $apiResponse;
            }
        }

        // (Optional) Query the database for each grocery item.
        if ($conn && $groceryItems) {
            $databaseResults = [];
            foreach ($groceryItems as $item) {
                $name = $item['item'];
                $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
                $stmt = $conn->prepare("SELECT * FROM grocery_items WHERE name LIKE ?");
                $stmt->bind_param("s", $searchName);
                $searchName = "%$name%";
                $stmt->execute();
                $result = $stmt->get_result();
                $dbItem = $result->fetch_assoc();
                $databaseResults[] = [
                    "requested_item"  => $name,
                    "quantity"        => $quantity,
                    "database_result" => $dbItem ? $dbItem : "Item not found"
                ];
            }
        }
    } else {
        $error = "Failed to move uploaded file.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SnapCart - Snap your list, shop instantly</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <div class="container mx-auto px-4 py-8 flex-grow">
        <div class="max-w-4xl mx-auto grid md:grid-cols-2 gap-8">
            <!-- Left Column: Upload Section -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <div class="text-center">
                    <h1 class="text-3xl font-bold text-gray-800 mb-4">SnapCart</h1>
                    <p class="text-gray-600 mb-6">Snap your list, shop instantly</p>
                </div>

                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                    <div class="mb-4">
                        <label for="file-upload" class="cursor-pointer">
                            <div class="inline-block bg-gray-200 rounded-full p-4 mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </label>
                    </div>
                    <p class="text-gray-600 mb-4">Upload a text file or image with your shopping list</p>
                    <form id="upload-form" method="post" enctype="multipart/form-data" class="flex flex-col items-center">
                        <input type="file" id="file-upload" name="shopping_list" accept="image/*,text/*" class="hidden" required>
                        <button type="button" onclick="document.getElementById('file-upload').click();" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                            Choose File
                        </button>
                    </form>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-4" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($groceryItems): ?>
                    <div class="mt-6">
                        <h2 class="text-xl font-semibold mb-4">Extracted Items</h2>
                        <div class="space-y-2">
                            <?php foreach($groceryItems as $item): ?>
                                <div class="flex justify-between items-center bg-gray-100 p-3 rounded-lg">
                                    <span><?php echo htmlspecialchars($item['item']); ?></span>
                                    <div class="flex items-center">
                                        <span class="mr-2">Qty: <?php echo htmlspecialchars($item['quantity'] ?? 1); ?></span>
                                        <button onclick="addToCart('<?php echo htmlspecialchars($item['item']); ?>', <?php echo htmlspecialchars($item['quantity'] ?? 1); ?>)" class="bg-green-500 text-white px-3 py-1 rounded-lg text-sm hover:bg-green-600">
                                            Add
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Cart Section -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Your Cart</h2>
                    <span id="cart-count" class="bg-blue-600 text-white text-sm px-3 py-1 rounded-full"><?php echo count($cart); ?> items</span>
                </div>

                <div id="cart-items" class="space-y-4">
                    <?php if (empty($cart)): ?>
                        <p class="text-gray-500 text-center">Your cart is empty</p>
                    <?php else: ?>
                        <?php foreach($cart as $index => $cartItem): ?>
                            <div class="flex justify-between items-center bg-gray-100 p-3 rounded-lg">
                                <span><?php echo htmlspecialchars($cartItem['item']); ?></span>
                                <span>Qty: <?php echo htmlspecialchars($cartItem['quantity']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="mt-6 flex justify-between">
                    <button onclick="clearCart()" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                        Clear Cart
                    </button>
                    <button class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                        Checkout
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('file-upload').addEventListener('change', function(e) {
            document.getElementById('upload-form').submit();
        });

        function addToCart(item, quantity) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add_to_cart&item=${encodeURIComponent(item)}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                updateCart(data.cart, data.cart_count);
            });
        }

        function clearCart() {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=clear_cart'
            })
            .then(response => response.json())
            .then(data => {
                updateCart(data.cart, data.cart_count);
            });
        }

        function updateCart(cart, count) {
            const cartItemsContainer = document.getElementById('cart-items');
            const cartCountElement = document.getElementById('cart-count');

            cartItemsContainer.innerHTML = cart.length === 0 
                ? '<p class="text-gray-500 text-center">Your cart is empty</p>'
                : cart.map(item => `
                    <div class="flex justify-between items-center bg-gray-100 p-3 rounded-lg">
                        <span>${item.item}</span>
                        <span>Qty: ${item.quantity}</span>
                    </div>
                `).join('');

            cartCountElement.textContent = `${count} items`;
        }
    </script>
</body>
</html>