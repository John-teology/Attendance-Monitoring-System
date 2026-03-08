<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>User QR Codes</title>
    <style>
        @page {
            margin: 20px;
        }
        body {
            font-family: sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 15px; /* Creates the gap between cards */
            table-layout: fixed; /* Ensures equal column widths */
        }
        td {
            width: 33.33%;
            vertical-align: top;
            padding: 0;
        }
        .card {
            border: 1px solid #ddd;
            padding: 15px;
            text-align: center;
            height: 240px; /* Fixed height */
            background-color: #fff;
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
            font-size: 14px;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .details {
            color: #666;
            font-size: 11px;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <table>
        @foreach(collect($cards ?? [])->chunk(3) as $chunk)
            <tr>
                @foreach($chunk as $card)
                    <td>
                        <div class="card">
                            <div class="qr-code">
                                <img src="{{ $card['qr_data_uri'] ?? '' }}" width="140" height="140">
                            </div>
                            <div class="name">{{ $card['full_name'] ?? '' }}</div>
                            <div class="details">ID: {{ $card['id_number'] ?? '' }}</div>
                            <div class="details">{{ $card['info'] ?? '' }}</div>
                        </div>
                    </td>
                @endforeach

                {{-- Fill empty cells to maintain grid structure --}}
                @for($i = $chunk->count(); $i < 3; $i++)
                    <td></td>
                @endfor
            </tr>
        @endforeach
    </table>
</body>
</html>
