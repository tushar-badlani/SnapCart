<?php
// No PHP processing needed for this example, but we're using PHP as requested
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SnapCart - Upload Your Shopping List</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8fafc;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        .logo span:first-child {
            color: #3b82f6;
        }
        
        .logo span:last-child {
            color: #f59e0b;
        }
        
        .cart-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #475569;
        }
        
        .cart-icon {
            position: relative;
        }
        
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #f59e0b;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        .hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 80px 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .hero-text {
            flex: 1;
        }
        
        .hero-title {
            font-size: 48px;
            color: #334155;
            margin-bottom: 10px;
            line-height: 1.2;
        }
        
        .highlight {
            color: #3b82f6;
        }
        
        .hero-subtitle {
            color: #64748b;
            font-size: 18px;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #3b82f6;
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #2563eb;
        }
        
        .btn-outline {
            background-color: transparent;
            color: #3b82f6;
            border: 1px solid #3b82f6;
            margin-left: 15px;
        }
        
        .btn-outline:hover {
            background-color: #eff6ff;
        }
        
        .hero-image {
            flex: 1;
            display: flex;
            justify-content: flex-end;
        }
        
        .list-preview {
            width: 400px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            padding: 15px;
        }
        
        .window-controls {
            display: flex;
            gap: 6px;
            margin-bottom: 15px;
        }
        
        .control {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .control-red {
            background-color: #ef4444;
        }
        
        .control-yellow {
            background-color: #f59e0b;
        }
        
        .control-green {
            background-color: #10b981;
        }
        
        .list-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8fafc;
            border-radius: 6px;
        }
        
        .item-number {
            width: 24px;
            height: 24px;
            background-color: #f59e0b;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            margin-right: 10px;
        }
        
        .item-text {
            flex: 1;
            height: 12px;
            background-color: #e2e8f0;
            border-radius: 4px;
        }
        
        .process-btn {
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-weight: 500;
            width: 100%;
            text-align: center;
            margin-top: 15px;
            cursor: pointer;
        }
        
        .process-btn:hover {
            background-color: #2563eb;
        }
        
        .how-it-works {
            padding: 80px 40px;
            text-align: center;
            background-color: white;
        }
        
        .section-title {
            font-size: 36px;
            color: #334155;
            margin-bottom: 20px;
        }
        
        .section-subtitle {
            color: #64748b;
            font-size: 18px;
            max-width: 800px;
            margin: 0 auto 50px;
            line-height: 1.5;
        }
        
        .upload-section {
            padding: 80px 40px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .upload-title {
            font-size: 24px;
            color: #334155;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .upload-box {
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 50px;
            text-align: center;
        }
        
        .upload-icon {
            color: #94a3b8;
            margin-bottom: 20px;
        }
        
        .upload-text {
            color: #334155;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .upload-hint {
            color: #64748b;
            margin-bottom: 20px;
        }
        
        .file-types {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .file-type {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            background-color: #f8fafc;
            border-radius: 20px;
            color: #64748b;
        }
        
        .browse-btn {
            background-color: white;
            color: #3b82f6;
            border: 1px solid #3b82f6;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
        }
        
        .browse-btn:hover {
            background-color: #eff6ff;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">
            <span>Snap</span><span>Cart</span>
        </div>
        <a href="#" class="cart-btn">
            <div class="cart-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span class="cart-badge">0</span>
            </div>
            Cart
        </a>
    </header>

    <section class="hero">
        <div class="hero-text">
            <h1 class="hero-title">Upload Your Shopping List, <br><span class="highlight">Fill Your Cart</span></h1>
            <p class="hero-subtitle">SnapCart automatically converts your shopping lists into cart items, saving you time and ensuring you never forget anything.</p>
            <div class="buttons">
                <a href="#upload" class="btn btn-primary" id="get-started">Get Started</a>
                <a href="#how-it-works" class="btn btn-outline" id="how-it-works-btn">How It Works</a>
            </div>
        </div>
        <div class="hero-image">
            <div class="list-preview">
                <div class="window-controls">
                    <div class="control control-red"></div>
                    <div class="control control-yellow"></div>
                    <div class="control control-green"></div>
                </div>
                <div class="list-items">
                    <div class="list-item">
                        <div class="item-number">1</div>
                        <div class="item-text"></div>
                    </div>
                    <div class="list-item">
                        <div class="item-number">2</div>
                        <div class="item-text"></div>
                    </div>
                    <div class="list-item">
                        <div class="item-number">3</div>
                        <div class="item-text"></div>
                    </div>
                    <div class="list-item">
                        <div class="item-number">4</div>
                        <div class="item-text"></div>
                    </div>
                </div>
                <button class="process-btn">Process List</button>
            </div>
        </div>
    </section>

    <section class="how-it-works" id="how-it-works">
        <h2 class="section-title">How SnapCart Works</h2>
        <p class="section-subtitle">Transform your shopping experience with our innovative features designed to save you time and effort.</p>
        <!-- Additional how-it-works content would go here -->
    </section>

   

    <script>
        // Smooth scrolling for navigation
        document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('get-started').addEventListener('click', function(e) {
        e.preventDefault(); // Prevents default behavior (if necessary)
        window.location.href = 'index.php'; // Redirect to index.php
    });


            document.getElementById('how-it-works-btn').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('how-it-works').scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>