<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin chứng thư thẩm định giá</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body>
    <div class="container">
        <div class="banner-container">
            <img src="{{ asset('header_sba.png') }}" alt="Banner" class="banner-image">
        </div>
        <h1>CHỨNG THƯ THẨM ĐỊNH GIÁ</h1>

        @if ($qrCodeRecord)
            <div class="info-container">
                <div class="info-row">
                    <p><strong>Mã hợp đồng:</strong></p>
                    <p>{{ $qrCodeRecord->contract_code }}</p>
                </div>
                <div class="info-row">
                    <p><strong>Ngày hợp đồng:</strong></p>
                    <p>{{ \Carbon\Carbon::parse($qrCodeRecord->created_date)->format('d-m-Y') }}</p>
                </div>
                <div class="info-row">
                    <p><strong>Mã chứng thư:</strong></p>
                    <p> 316/{{ \Carbon\Carbon::parse($qrCodeRecord->created_date)->format('Y') }}/{{ $qrCodeRecord->original_number ?? '0000' }}.{{ explode('.', $qrCodeRecord->contract_code)[1] ?? '' }}</p>
                </div>
                <div class="info-row">
                    <p><strong>Ngày chứng thư:</strong></p>
                    <p>{{ \Carbon\Carbon::parse($qrCodeRecord->certificate_date)->format('d-m-Y') }}</p>
                </div>
                <div class="info-row">
                    <p><strong>Thẩm định viên:</strong></p>
                    <p>{{ $qrCodeRecord->supervisor }}</p>
                </div>
                <div class="info-row">
                    <p><strong>Giá trị thẩm định:</strong></p>
                    <p>{{ number_format($qrCodeRecord->official_value) }} đồng</p>
                </div>
            </div>
        @else
            <p>Không tìm thấy thông tin chứng thư.</p>
        @endif
    </div>

    <style>
        body {
            font-family: 'Times New Roman', serif;
            margin: 0;
            padding: 0;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f7fafc;
        }

        .container {
            max-width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .banner-container {
            width: 100%;
            height: 150px;
            max-width: 100%;
            position: relative;
        }

        .banner-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: top;
        }

        .head-container {
            width: 100%;
            padding: 20px;
            max-width: 800px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .head-container>.date {
            font-style: italic;
        }

        h1 {
            font-size: 32px;
            color: #0c4a6e;
            padding: 20px;
            text-align: center;
            margin-bottom: 0;
        }

        h3 {
            margin-top: 5px;
        }

        p {
            font-size: 20px
        }

        .info-container {
            width: 100%;
            max-width: 800px;
            padding: 10px 0px 10px 50px;
            box-sizing: border-box;
        }

        .info-row {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }

        .info-row p {
            width: 100%;
            margin: 0;
            font-size: 20px;
            line-height: 1.5;
        }

        .info-row p:first-child {
            font-weight: bold;
        }

        .qr-code {
            text-align: center;
            margin-top: 20px;
        }

        .qr-code img {
            max-width: 200px;
        }

        @media (max-width: 600px) {
            h1 {
                font-size: 24px;
                padding: 10px;
            }

            .info-container {
                padding: 20px 10px 10px 70px;
            }

            p {
                font-size: 16px;
            }
        }
    </style>
</body>

</html>
