<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>QR Code - {{ $card['full_name'] }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            background-color: #fff;
            text-align: center; /* Center content for PDF */
        }
        .card-container {
            width: 100%;
            text-align: center;
            margin-top: 50px;
        }
        .card {
            border: 1px solid #ddd;
            padding: 20px;
            text-align: center;
            background-color: #fff;
            width: 300px;
            border-radius: 8px;
            margin: 0 auto; /* Center the card */
            display: inline-block; /* Helps with centering in some contexts */
        }
        .qr-code {
            margin-bottom: 15px;
        }
        .qr-code img {
            display: block;
            margin: 0 auto;
        }
        .name {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 8px;
            color: #333;
            word-wrap: break-word;
        }
        .details {
            color: #666;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 2px;
        }
    </style>
</head>
<body>
    <div class="card-container">
        <div class="card">
            <div class="qr-code">
                <img src="{{ $card['qr_data_uri'] }}" width="200" height="200" alt="QR Code">
            </div>
            <div class="name">{{ $card['full_name'] }}</div>
            <div class="details">ID: {{ $card['id_number'] }}</div>
            <div class="details">{{ $card['info'] }}</div>
        </div>
    </div>
</body>
</html>