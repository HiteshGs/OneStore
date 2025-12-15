<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Tax Invoice</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        .invoice-header {
            text-align: center;
            border-bottom: 1px solid #000 !important;
            padding-bottom: 10px;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 8px;
        }

        .invoice-logo {
            width: 6%;
            position: absolute;
            left: 0;
            top: 0px;
            max-width: 80px;
            height: auto;
        }

        .invoice-header-text {
            text-align: center;
            margin-top: 0;
            width: 100%;
        }

        .tax-invoice-title {
            margin: 0 0 1px 0;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            text-decoration: none;
            letter-spacing: 1px;
        }

        .store-name {
            margin: 0 0 2px 0;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .store-address {
            margin: 4px 0;
            font-size: 10px;
        }

        .store-contact-line {
            margin: 4px 0;
            font-size: 10px;
            font-weight: 500;
            color: #333;
        }

        .store-gstin-line {
            margin: 4px 0 0 0;
            font-size: 10px;
            font-weight: 700;
            color: #000;
            letter-spacing: 0.5px;
            text-align: center;
        }

        .store-gstin-line strong {
            font-weight: 900;
            color: #000;
        }

        /* Top right contact info */
        .invoice-header-contact {
            position: absolute;
            right: 0;
            top: 0;
            text-align: right;
        }

        .invoice-header-contact .store-contact-line {
            margin: 0;
            font-size: 10px;
            font-weight: 500;
            color: #333;
        }

        /* Bill to party row */
        .bill-party-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin: 20px 0 6px;
            padding-bottom: 6px;
            /*border-bottom: 1px solid #000;*/
            page-break-inside: avoid;
        }

        .bill-party-left {
            flex: 1;
            min-width: 50%;
        }

        .address-line {
            font-weight: 500;
            margin-top: 2px;
            white-space: pre-line;
        }

        .bill-party-right {
            width: 40%;
            margin-right: 8px;
        }

        /* Invoice Details Table */
        .invoice-details-table-print-safe {
            border-collapse: collapse;
            font-size: 10px;
            background: #ffffff;
            border: 1px solid #000;
            min-width: 280px;
            float: right;
            margin: 0; /* Removed extra margin */
        }

        .invoice-details-table-print-safe th,
        .invoice-details-table-print-safe td {
            padding: 7px;
            border: 1px solid #000;
            background: #ffffff;
        }

        .invoice-details-table-print-safe .meta-label {
            font-weight: 600;
            color: #000 !important;
            width: 100px;
            text-align: left;
            background: #ffffff !important;
        }

        .invoice-details-table-print-safe .meta-value {
            font-weight: 500;
            color: #000 !important;
            text-align: left;
            padding-left: 5px !important;
            background: #ffffff !important;
        }

        .meta-label {
            font-weight: 700;
            color: #333;
            text-align: left;
            width: 100px;
            white-space: nowrap;
        }

        .meta-value {
            font-weight: 600;
            color: #000;
            text-align: left;
            padding-left: 8px;
        }

        .bill-title {
            margin: 0 0 2px 0;
            font-size: 10px;
            font-weight: bold;
            color: #000;
            border-bottom: 1px solid #000;
            display: inline-block;
            padding-bottom: 1px;
        }

        .party-line {
            margin: 2px 0;
            font-size: 10px;
        }

        .party-name {
            font-weight: 600;
        }

        /* Items table */
        .tax-invoice-items {
            margin-top: 20px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            border-width: 1px;
            padding: 4px; /* reduced padding so more rows fit on A4 */
            font-size: 9px;
            font-weight: 500;
            vertical-align: middle;
            height: 12px; /* reduced height from 25px */
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .items-table thead {
            background: #ffffff; /* Set to white for print consistency */
            font-weight: 700;
            text-transform: uppercase;
        }

        .items-table th {
            text-align: center;
            font-weight: 700;
            font-size: 9px;
            padding: 7px;
            border: 1px solid #000;
            background: #ffffff; /* Set to white for print consistency */
        }

        .item-name {
            font-weight: 500;
            font-size: 9px;
            line-height: 1.2;
        }

        .item-custom-fields {
            margin-top: 1px;
        }

        .item-custom-field-line {
            display: block;
            font-size: 9px;
            line-height: 1.1;
        }

        .item-row td {
            height: 22px; /* increased further from 18px */
            vertical-align: middle;
        }

        .item-row.blank-row td {
            height: 14px; /* reduced from 22px */
            border: 0.5px solid #000;
            vertical-align: middle;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        /* FINAL TOTALS BOX */
        .final-totals-box {
            margin: 4px 0 4px 0;
            border: 0px solid #000;
            background: #fff;
        }

        .final-totals-box .items-table {
            width: 100%;
            border-collapse: collapse !important;
            border: 0px solid #000 !important;
        }

        .final-totals-box .items-table td {
            border: 0px solid #000 !important;
            border-width: 1px !important;
            padding: 5px;
            font-size: 10px;
            font-weight: 600;
            vertical-align: middle;
            height: 18px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .final-totals-box .label-strong {
            font-weight: 400;
            color: #000;
            font-size: 9px;
            padding: 5px;
        }

        .final-totals-box .value-right {
            text-align: right;
            font-weight: 400;
            color: #000;
            font-size: 9px;
            padding: 5px;
        }

        .final-totals-box .paid-strong {
            color: green;
            font-weight: 400;
            font-size: 9px;
        }

        .final-totals-box .due-strong {
            color: red;
            font-weight: 400;
            font-size: 9px;
        }

        /* NET AMOUNT in same grid style */
        .net-amount-label,
        .net-amount-value {
            font-weight: 600 !important;
            font-size: 9px !important;
            color: #000 !important;
            background: #ffffff !important;
            padding: 5px !important;
            border: 0.5px solid #000 !important;
            border-collapse: collapse !important;
        }

        /* Bottom section: Bank + Terms + Sign */
        .bottom-section {
            margin-top: 35px;
            font-size: 10px;
            page-break-inside: avoid;
            margin-bottom: 25px;
            padding-bottom: 15px;
        }

        /* BANK DETAILS STRIP */
        .bank-details-box {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 0px;
            border: 1px solid #000;
            border-width: 1px;
            padding: 4px 8px;
            background: #fff;
            margin: 0 0 1px;
            font-size: 10px;
            font-weight: 500;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            flex-wrap: wrap;
        }

        .bank-title {
            font-weight: 900 !important;
            font-size: 10px;
            color: #000;
            min-width: 80px;
            text-align: left;
            margin-right: 10px;
        }

        .bank-items {
            display: flex;
            align-items: center;
            gap: 1px;
            flex-wrap: wrap;
            justify-content: flex-start;
            flex: 1;
        }

        .bank-items span {
            white-space: nowrap;
            font-weight: 500;
        }

        .separator {
            font-weight: bold;
            color: #000;
            font-size: 10px;
            margin: 0 3px;
        }

        /* TERMS + SIGN BOXES */
        .bottom-boxes-row {
            display: flex;
            gap: 8px;
            margin-top: 20px;
            justify-content: space-between;
        }

        .terms-box-final {
            flex: 0 0 auto;
            width: 48%;
            border: 1px solid #000;
            padding: 10px;
            height: 95px;
            position: relative;
            background: white;
            font-size: 10px;
        }

        .signature-box-final {
            flex: 0 0 auto;
            width: 48%;
            border: 1px solid #000;
            padding: 10px;
            height: 95px;
            position: relative;
            background: white;
            font-size: 10px;
        }

        .terms-header {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 2px;
            text-align: left;
        }

        .terms-content {
            line-height: 1.15;
            margin-bottom: 8px;
            font-size: 9px;
        }

        .for-company {
            position: absolute;
            bottom: 5px;
            left: 5px;
            font-weight: bold;
            font-size: 8px;
        }

        .signature-title {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 2px;
        }

        .signature-space {
            height: 20px;
            border-bottom: 1px solid #000;
            margin: 4px 8px;
        }

        .company-name-bottom {
            margin-top: 0;
            font-weight: bold;
            font-size: 9px;
        }

        /* THANK YOU */
        .thanks-details {
            margin-top: 25px;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }

        .thanks-text {
            margin: 0;
            font-size: 10px;
            font-weight: 600;
            font-style: italic;
            color: #555;
        }

        /* BARCODE */
        .barcode-details {
            margin-top: 2px;
            margin-bottom: 2px;
            text-align: center;
            font-size: 10px;
        }

        /* FOOTER BUTTONS */
        .footer-button {
            text-align: right;
            margin-top: 6px;
        }

        .footer-button button {
            padding: 4px 10px;
            font-size: 10px;
        }

        /* size previews */
        .invoice-root {
            width: 100%;
        }

        .a4-invoice {
            max-width: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .a5-invoice {
            max-width: 148mm;
        }

        .thermal-80 {
            max-width: 80mm;
        }

        .thermal-58 {
            max-width: 58mm;
        }

        .thermal-80,
        .thermal-58 {
            font-size: 12px;
        }

        /* PRINT */
        @media print {
            @page {
                margin: 15mm;
                size: A4;
            }

            body {
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background: #fff !important;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .invoice-header,
            .bill-party-row,
            .tax-invoice-items,
            .final-totals-box,
            .bottom-section,
            .bank-details-box,
            .bottom-boxes-row {
                page-break-inside: avoid;
            }

            .bottom-boxes-row {
                display: flex !important;
                align-items: stretch !important;
            }

            .invoice-header {
                border-bottom: 1px solid #000 !important;
            }

            /*.bill-party-row {
                border-bottom: 1px solid #000 !important;
            }*/

            .items-table,
            .items-table th,
            .items-table td {
                border: 1px solid #000 !important;
                border-width: 1px !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .items-table th {
                border-bottom: 1px solid #000 !important;
                background: #ffffff !important;
            }

            .final-totals-box {
                border: 0px solid #000 !important;
            }

            .final-totals-box .items-table {
                border-collapse: collapse !important;
                border: 0px solid #000 !important;
            }

            .final-totals-box .items-table td {
                border: 0px solid #000 !important;
                border-width: 0.5px !important;
            }

            .net-amount-label {
                text-align: left !important;
            }

            .net-amount-value {
                text-align: right !important;
            }

            .bank-details-box {
                border: 1px solid #000 !important;
            }

            .terms-box-final,
            .signature-box-final {
                border: 1px solid #000 !important;
            }

            .signature-space {
                border-bottom: 1px solid #000 !important;
            }

            .thanks-details {
                border-top: 1px solid #ddd !important;
            }

            .bill-party-right,
            .invoice-details-table-print-safe {
                float: right !important;
                color: #000 !important;
                background: #ffffff !important;
            }

            .invoice-details-table-print-safe {
                border-collapse: collapse !important;
                border: 1px solid #000 !important;
            }

            .invoice-details-table-print-safe td {
                border: 1px solid #000 !important;
                color: #000 !important;
                background: #ffffff !important;
            }

            .bottom-boxes-row {
                display: flex !important;
                gap: 8px !important;
                margin-top: 4px !important;
                justify-content: space-between !important;
            }

            /* Ensure all text is black and borders are visible */
            .store-name,
            .store-address,
            .store-contact-line,
            .store-gstin-line,
            .party-line,
            .item-name,
            .label-strong,
            .value-right,
            .bank-title,
            .bank-items,
            .terms-header,
            .terms-content,
            .for-company,
            .signature-title,
            .company-name-bottom,
            .thanks-text {
                color: #000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Ensure backgrounds are white */
            .items-table thead,
            .final-totals-box .label-strong,
            .final-totals-box .value-right,
            .invoice-details-table-print-safe .meta-label,
            .invoice-details-table-print-safe .meta-value {
                background: #ffffff !important;
                color: #000 !important;
            }

            /* Top right contact info */
            .invoice-header-contact {
                position: absolute !important;
                right: 0 !important;
                top: 0 !important;
                text-align: right !important;
            }
        }
    </style>
</head>

<body>
    @php
        // Helper function to format currency (assuming it exists in App\Classes\Common)
        if (!function_exists('formatAmountCurrency')) {
            function formatAmountCurrency($currency, $amount) {
                // Placeholder for actual currency formatting logic
                return number_format($amount, 2);
            }
        }

        // 1. Calculate Gross Amount (Subtotal of all items)
        $grossAmount = 0;
        $totalTaxAmount = 0;
        if (isset($order->items) && is_iterable($order->items)) {
            foreach ($order->items as $item) {
                // Assuming $item->subtotal is the item total including tax
                $grossAmount += (float)($item->subtotal ?? 0);
                $totalTaxAmount += (float)($item->total_tax ?? 0);
            }
        }

        // 2. Determine Customer State for GST Split (Simplified logic based on Vue code's keywords)
        $finalCustomerAddress = trim(strtolower($order->user->address ?? ''));
        $gujaratKeywords = [
            'gujarat','gujrat','gj','surat','ahmedabad','vadodara','rajkot',
            'bhavnagar','jamnagar','anand','gandhinagar','bharuch','valsad','navsari'
        ];
        $isGujarat = false;
        foreach ($gujaratKeywords as $kw) {
            if ($finalCustomerAddress !== '' && strpos($finalCustomerAddress, $kw) !== false) {
                $isGujarat = true;
                break;
            }
        }

        // 3. Calculate GST Split
        if ($isGujarat) {
            // Intra-state (CGST + SGST)
            $computedSGST = $totalTaxAmount / 2;
            $computedCGST = $totalTaxAmount / 2;
            $computedIGST = 0;
        } else {
            // Inter-state (IGST)
            $computedSGST = 0;
            $computedCGST = 0;
            $computedIGST = $totalTaxAmount;
        }

        // 4. Calculate Paid and Due Amounts
        $paidAmount = (float)($order->paid_amount ?? 0);
        $dueAmount = (float)($order->total ?? 0) - $paidAmount;

        // 5. Item Padding (for fixed row count)
        $totalItems = count($order->items ?? []);
        $blankRows = max(0, 20 - $totalItems);

        // 6. Helper functions removed

        // 7. Helper for Tax Rate (assuming tax_rate is available on item)
        if (!function_exists('getTaxRate')) {
            function getTaxRate($item) {
                return $item->tax_rate ?? '0';
            }
        }
    @endphp

    <div class="invoice-box invoice-root a4-invoice">
        <!-- TOP HEADER (STORE INFO) -->
        <div class="invoice-header">
            <div class="invoice-meta-center">
                <h2 class="tax-invoice-title">
                    TAX INVOICE
                </h2>
            </div>
            <div class="invoice-header-text">
                <h1 class="store-name">
                    {{ $warehouse->name }}
                </h1>
                <p class="store-address">
                    {{ $warehouse->address }}
                </p>

                <!-- GSTIN -->
                @php
                    // Prefer gst_number (to match Vue) and fall back to gstin_number, then hard-coded fallback
                    $warehouseGstin = $warehouse->gst_number
                        ?? $warehouse->gstin_number
                        ?? '24BNGPG0699R1ZD';
                @endphp
                <p class="store-gstin-line">
                    <strong>GSTIN: {{ $warehouseGstin }}</strong>
                </p>
            </div>

            <!-- TOP RIGHT CONTACT INFO -->
            <div class="invoice-header-contact">
                <p class="store-contact-line">
                    @if($warehouse->show_phone_on_invoice && $warehouse->phone)
                    <span>
                        Phone: {{ $warehouse->phone }}
                    </span>
                    @endif
                    @if($warehouse->show_email_on_invoice && $warehouse->email && $warehouse->show_phone_on_invoice && $warehouse->phone)
                    <span>
                        &nbsp;&nbsp;|&nbsp;&nbsp;Email: {{ $warehouse->email }}
                    </span>
                    @elseif($warehouse->show_email_on_invoice && $warehouse->email)
                    <span>
                        Email: {{ $warehouse->email }}
                    </span>
                    @endif
                </p>
            </div>
            @if($warehouse->logo_url)
            <img class="invoice-logo" src="{{ asset($warehouse->logo_url) }}" alt="{{ $warehouse->name }}" />
            @endif
        </div>

        <!-- BILL TO + INVOICE DETAILS - REFACTORED TO USE TABLE FOR LAYOUT -->
        <table class="bill-party-table" style="width: 100%; border-collapse: collapse; margin: 20px 0 6px;">
            <tr>
                <td style="width: 60%; padding: 0; vertical-align: top;">
                    <!-- LEFT: Customer Details -->
                    <div class="bill-party-left">
                        <h4 class="bill-title">Bill To Party</h4>
                        <p class="party-line party-name">
                            {{ $order->user->name ?? 'Walk-in Customer' }}
                        </p>
                        @if($order->user->phone)
                        <p class="party-line">
                            Mo: {{ $order->user->phone }}
                        </p>
                        @endif

                        <br />

                        <!-- GSTIN (Customer) -->
                        @if($order->user->gst_number)
                        <p class="party-line">
                            <strong>GSTIN:</strong> {{ $order->user->gst_number }}
                        </p>
                        @endif

                        @if($order->user->address)
                        <p class="party-line address-line">
                            {{ $order->user->address }}
                        </p>
                        @else
                        <p class="party-line text-muted">
                            <em>Address not available</em>
                        </p>
                        @endif
                    </div>
                </td>
                <td style="width: 40%; padding: 0; vertical-align: top; text-align: right;">
                    <!-- RIGHT: Invoice Details -->
                    <table class="invoice-details-table-print-safe" style="width: 100%; margin-top: -4px;">
                        <tbody>
                            <tr>
                                <td class="meta-label">Invoice No</td>
                                <td class="meta-value">{{ $order->invoice_number }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Date</td>
                                <td class="meta-value">{{ \Carbon\Carbon::parse($order->order_date)->format('d-m-Y') }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Generated by</td>
                                <td class="meta-value">{{ $staffMember->name ?? 'Admin' }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Generated at</td>
                                <td class="meta-value">{{ \Carbon\Carbon::now()->format('d-m-Y, h:i a') }}</td>
                            </tr>
                            @if($warehouse->city || $warehouse->state)
                            <tr>
                                <td class="meta-label">Location</td>
                                <td class="meta-value">
                                    {{ implode(', ', array_filter([$warehouse->city, $warehouse->state])) }}
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <!-- ITEMS TABLE -->
        <div class="tax-invoice-items">
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 5%">NO</th>
                        <th style="width: 35%">ITEM</th>
                        <th style="width: 8%">QTY</th>
                        <th style="width: 12%">RATE</th>
                        <th style="width: 8%">TAX %</th>
                        <th style="width: 12%; text-align: right">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $index => $item)
                    <tr class="item-row">
                        <!-- Sr No -->
                        <td class="center">{{ $index + 1 }}</td>

                        <!-- Item name + custom fields -->
                        <td>
                            <div class="item-name">
                                {{ $item->product->name }}
                            </div>
                            {{-- Custom fields logic from Vue component is omitted as it requires complex data structure not guaranteed in Blade context --}}
                        </td>


                        <!-- QTY -->
                        <td class="center">
                            {{ $item->quantity . ' ' . $item->unit->short_name }}
                        </td>

                        <!-- RATE -->
                        <td class="right">
                            {{ formatAmountCurrency($company->currency, $item->single_unit_price) }}
                        </td>

                        <!-- TAX % -->
                        <td class="center">
                            {{ getTaxRate($item) }}%
                        </td>

                        <!-- TOTAL (WITH TAX) -->
                        <td class="right">
                            {{ formatAmountCurrency($company->currency, $item->subtotal) }}
                        </td>
                    </tr>
                    @endforeach

                    <!-- Add blank rows to make it 20 rows total -->
                    @for($i = 0; $i < $blankRows; $i++)
                    <tr class="item-row blank-row">
                        <td class="center"></td>
                        <td></td>
                        <td class="center"></td>
                        <td class="center"></td>
                        <td class="right"></td>
                        <td class="center"></td>
                        <td class="right"></td>
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        <!-- FINAL TOTALS BOX - REFACTORED TO MATCH IMAGE LAYOUT -->
        <div class="final-totals-box">
            <table class="items-table" style="width: 100%; border-collapse: collapse;">
                <tbody>
                    <!-- ROW 1: GROSS / SGST / CGST -->
                    <tr>
                        <td class="label-strong" style="width: 16.66%; border-right: none;">GROSS AMT :</td>
                        <td class="value-right" style="width: 16.66%; border-left: none;">
                            {{ formatAmountCurrency($company->currency, $grossAmount) }}
                        </td>
                        <td class="label-strong" style="width: 16.66%; border-right: none;">SGST :</td>
                        <td class="value-right" style="width: 16.66%; border-left: none;">
                            {{ formatAmountCurrency($company->currency, $computedSGST) }}
                        </td>
                        <td class="label-strong" style="width: 16.66%; border-right: none;">CGST :</td>
                        <td class="value-right" style="width: 16.66%; border-left: none;">
                            {{ formatAmountCurrency($company->currency, $computedCGST) }}
                        </td>
                    </tr>

                    <!-- ROW 2: IGST / NET AMOUNT -->
                    <tr>
                        <td class="label-strong" style="border-right: none;">IGST :</td>
                        <td class="value-right" style="border-left: none;">
                            {{ formatAmountCurrency($company->currency, $computedIGST) }}
                        </td>
                        <td class="label-strong" style="border-right: none;">NET AMOUNT :</td>
                        <td class="value-right" colspan="3" style="text-align: right; border-left: none;">
                            {{ formatAmountCurrency($company->currency, $order->total) }}
                        </td>
                    </tr>

                    <!-- ROW 3: PAID / DUE -->
                    <tr>
                        <td class="label-strong" style="border-right: none;">PAID AMOUNT :</td>
                        <td class="value-right paid-strong" style="border-left: none;">
                            {{ formatAmountCurrency($company->currency, $paidAmount) }}
                        </td>

                        <td class="label-strong" style="border-right: none;">DUE AMOUNT :</td>
                        <td class="value-right due-strong" colspan="3" style="text-align: right; border-left: none;">
                            {{ formatAmountCurrency($company->currency, $dueAmount) }}
                        </td>
                    </tr>

                    <!-- RUPEES IN WORDS -->
                    @if($order->amount_in_words)
                    <tr>
                        <td colspan="6" style="
                            padding: 8px 10px;
                            font-style: italic;
                            color: #555;
                            font-size: 12px;
                            border: 1px solid #000 !important;
                          ">
                            <strong>Rupees in Words:</strong>
                            {{ $order->amount_in_words }}
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- BANK DETAILS SECTION - MOVED UP -->
        <div class="bank-details-section" style="margin: 20px 0;">
            <table class="bank-details-table" style="width: 100%; border-collapse: collapse;">
                <tbody>
                    <tr>
                        <td style="border: 1px solid #000; padding: 10px; background: #f9f9f9; font-size: 8px;">
                            <strong style="font-size: 8px;">BANK DETAIL :</strong>
                            <span style="margin-left: 10px; font-size: 8px;">
                                A/c No : {{ $warehouse->bank_account_number ?? '18650200016691' }} | 
                                Bank : {{ $warehouse->bank_name ?? 'THE FEDERAL BANK' }} | 
                                Branch : {{ $warehouse->bank_branch ?? 'SURAT VARACHHA' }} | 
                                IFSC : {{ $warehouse->bank_ifsc ?? 'FDRL0001865' }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- BOTTOM SECTION – PROPER TABLE FORMAT -->
        <div class="bottom-section">
            <!-- MAIN BOTTOM TABLE -->
            <table class="bottom-main-table" style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <tbody>
                    
                    <!-- TERMS AND SIGNATURE ROW -->
                    <tr>
                        <td style="width: 50%; border: 1px solid #000; padding: 15px; vertical-align: top;">
                            <div style="margin-bottom: 10px;">
                                <strong>Terms Of Sales :</strong>
                            </div>
                            <div style="margin-bottom: 15px; font-size: 10px; line-height: 1.4;">
                                {!! nl2br($warehouse->terms_condition ?? '1. Goods once sold will not be taken back or exchanged<br>2. All disputes are subject to [ENTER_YOUR_CITY_NAME] jurisdiction only') !!}
                            </div>
                            <div style="font-weight: bold;">
                                For : {{ $warehouse->name ?? 'One Store' }}
                            </div>
                        </td>
                        
                        <td style="width: 50%; border: 1px solid #000; padding: 15px; vertical-align: top; text-align: center;">
                            <div style="margin-bottom: 20px;">
                                <strong>Authorised Signatory</strong>
                            </div>
                            <div style="height: 40px; border-bottom: 1px solid #000; margin: 0 auto 15px auto; width: 150px;"></div>
                            <div style="font-weight: bold;">
                                {{ $warehouse->name ?? 'One Store' }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- THANK YOU -->
        <div class="thanks-details">
            <p class="thanks-text">
                Thank You For Shopping With Us. Please Come Again
            </p>
        </div>
    </div>
</body>

</html>