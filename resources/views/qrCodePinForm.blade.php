<!DOCTYPE html>
<html lang="vi" class="h-full bg-gray-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhập mã PIN để xem thông tin</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f7fafc;
    }

    .container {
        max-width: 28rem;
        width: 100%;
        background-color: #ffffff;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 1.5rem;
        margin: auto;
    }

    h1 {
        font-size: 1.875rem;
        font-weight: 700;
        color: #2d3748;
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .form-group label {
        width: 100%;
        font-size: 0.875rem;
        font-weight: 500;
        color: #4a5568;
        margin-bottom: 0.5rem;
        display: block;
    }

    input[type="text"] {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.375rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        outline: none;
        font-size: 0.875rem;
        transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    input[type="text"]:focus {
        border-color: #1d9b8f;
        box-shadow: 0 0 0 3px rgba(29, 155, 143, 0.3);
    }

    button {
        width: 100%;
        padding: 0.625rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #ffffff;
        background-color: #1d9b8f;
        border: none;
        border-radius: 0.375rem;
        cursor: pointer;
        margin-top: 10px;
        transition: background-color 0.2s ease-in-out;
    }

    button:hover {
        background-color: #20aa9d;
    }

    .bg-gray-50 {
        background-color: #f9fafb;
        padding: 1rem;
        border-radius: 0.375rem;
    }

    .bg-red-50 {
        background-color: #fef2f2;
        border-left: 4px solid #f87171;
        padding: 1rem;
        border-radius: 0.375rem;
    }

    .mt-2 {
        margin-top: 8px;
    }

    .text-sm {
        font-size: 0.875rem;
    }

    .text-gray-600 {
        color: #718096;
    }

    .text-gray-800 {
        color: #2d3748;
    }

    .text-red-700 {
        color: #c53030;
    }

    .text-red-800 {
        color: #9b2c2c;
    }

    .text-emerald-500 {
        color: #10b981;
    }

    .text-emerald-600 {
        color: #059669;
    }

    .text-emerald-700 {
        color: #047857;
    }

    .flex {
        display: flex;
    }

    .items-center {
        align-items: center;
    }

    .justify-center {
        justify-content: center;
    }

    .rounded-md {
        border-radius: 0.375rem;
    }

    .shadow-md {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
</style>

<body class="h-full flex items-center justify-center">
    <div class="container max-w-md w-full mx-auto p-6 bg-white rounded-lg shadow-md">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Thông tin mã QR</h1>

        <form action="{{ url('/qr/' . base64_encode($qrCodeRecord->contract_code)) }}" method="POST" class="space-y-4">
            @csrf
            <div class="bg-gray-50 p-4 rounded-md">
                <p class="text-sm text-gray-600"><strong class="font-medium">Mã hợp đồng:</strong>
                    {{ $qrCodeRecord->contract_code }}</p>
                    <p class="text-sm text-gray-600"><strong class="font-medium">Ngày tạo:</strong>
                        {{ \Carbon\Carbon::parse($qrCodeRecord->created_date)->format('d/m/Y') }}
                    </p>
            </div>

            <div class="form-group">
                <label for="pin" class="block text-sm font-medium text-gray-700 mb-1">Nhập mã PIN:</label>
                <input type="text" id="pin" name="pin" required
                    class="form-control px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-emerald-500 focus:border-emerald-500"
                    style="max-width: 300px;"
                >
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-md mt-2">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Đã xảy ra lỗi:</h3>
                            <ul class="mt-2 list-disc list-inside text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <button type="submit"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                Xác nhận
            </button>
        </form>
    </div>
</body>

</html>
