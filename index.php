<?php
// index.php

// --------------------------
// DATABASE CONFIGURATION (Optional)
// --------------------------
$conn = mysqli_connect("localhost","root","","cookie");
 
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
// PROCESS UPLOADED IMAGE
// --------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['grocery_image'])) {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $uploadedFile = $uploadDir . basename($_FILES['grocery_image']['name']);

    if (move_uploaded_file($_FILES['grocery_image']['tmp_name'], $uploadedFile)) {
        // Read and encode the image file in base64.
        $imageBytes  = file_get_contents($uploadedFile);
        $imageBase64 = base64_encode($imageBytes);
        $mimeType    = $_FILES['grocery_image']['type'];

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
        if ($pdo && $groceryItems) {
            $databaseResults = [];
            foreach ($groceryItems as $item) {
                $name = $item['item'];
                $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
                $stmt = $pdo->prepare("SELECT * FROM grocery_items WHERE name LIKE ?");
                $stmt->execute(["%$name%"]);
                $dbItem = $stmt->fetch();
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
<html>
<head>
    <meta charset="UTF-8">
    <title>Grocery Item Extractor</title>
</head>
<body>
    <h1>Upload Grocery List Image</h1>
    <?php if ($error): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <!-- Upload Form -->
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="grocery_image" accept="image/*" required>
        <button type="submit">Upload and Process</button>
    </form>

    <?php if ($groceryItems): ?>
        <h2>Extracted Grocery Items</h2>
        <pre><?php echo htmlspecialchars(json_encode($groceryItems, JSON_PRETTY_PRINT)); ?></pre>
    <?php endif; ?>

    <?php if ($databaseResults): ?>
        <h2>Database Query Results</h2>
        <table border="1" cellpadding="5">
            <tr>
                <th>Item</th>
                <th>Quantity</th>
                <th>Database Info</th>
            </tr>
            <?php foreach($databaseResults as $data): ?>
            <tr>
                <td><?php echo htmlspecialchars($data['requested_item']); ?></td>
                <td><?php echo htmlspecialchars($data['quantity']); ?></td>
                <td>
                    <?php 
                    if (is_array($data['database_result'])) {
                        echo "ID: " . htmlspecialchars($data['database_result']['id']) . "<br>";
                        echo "Name: " . htmlspecialchars($data['database_result']['name']) . "<br>";
                        echo "Price: " . htmlspecialchars($data['database_result']['price']);
                    } else {
                        echo htmlspecialchars($data['database_result']);
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>
