@php
    use Illuminate\Support\Number;
    use PHPViet\NumberToWords\Transformer;

    $curr = $data['currency'] ?? 'VND';
    $products = $data['products'] ?? [];

    $totalAmount = 0;
    $totalVat = 0;

    $products = array_map(function ($product) use ($curr, &$totalAmount, &$totalVat) {
        $unitPrice = $product['unit_price'] ?? 0;
        $quantity = $product['quantity'] ?? 0;
        $vatPercent = $product['vat'] ?? 0;

        $lineTotal = $unitPrice * $quantity;
        $lineVat = ($lineTotal * $vatPercent) / 100;

        // Cộng dồn
        $totalAmount += $lineTotal;
        $totalVat += $lineVat;

        // Format hiển thị
        $product['formated_price'] = Number::currency($unitPrice, $curr, 'vi');
        $product['formated_total'] = Number::currency($lineTotal, $curr, 'vi');
        $product['formated_qty'] = Number::format($quantity, locale: 'vi');
        $product['formated_vat'] = Number::currency($lineVat, $curr, 'vi');

        return $product;
    }, $products);

    $grandTotal = $totalAmount + $totalVat;

    // Format hiển thị tổng
    $formatedTotal = Number::currency($totalAmount, $curr, 'vi');
    $formatedVatTotal = Number::currency($totalVat, $curr, 'vi');
    $formatedGrandTotal = Number::currency($grandTotal, $curr, 'vi');

    $transformer = new Transformer();
    // $transformer->toCurrency(6742.7, ['đô', 'xen']);
    $transformer->toCurrency($grandTotal);
    $ammountInWords = $transformer->toWords($grandTotal) ?? '';

@endphp


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>VHL Trading - Báo giá</title>
    @vite(['resources/css/pdf.css'])
</head>

<body class="bg-gray-200">
    <div class="tm_container">
        <div class="tm_invoice_wrap">
            <div class="tm_invoice tm_style1" id="tm_download_section">
                <div class="tm_invoice_in">
                    <div class="tm_invoice_head tm_align_center tm_mb20">
                        <div class="tm_invoice_left">
                            <div class="tm_logo"><img src="{{ asset('assets/images/VHL_logo.png') }}" alt="Logo">
                            </div>
                        </div>
                        <div class="tm_invoice_right tm_text_right">
                            <div class="tm_primary_color tm_f40 tm_text_uppercase">BẢNG BÁO GIÁ</div>
                        </div>
                    </div>
                    <div class="tm_invoice_info tm_mb20">
                        <div class="tm_invoice_seperator tm_gray_bg"></div>
                        <div class="tm_invoice_info_list">
                            <p class="tm_invoice_number tm_m0">Mã:
                                <b class="tm_primary_color">{{ $data['quote_no'] ?? '' }}</b>
                            </p>
                            <p class="tm_invoice_date tm_m0">Ngày:
                                <b class="tm_primary_color">
                                    {{ \Carbon\Carbon::createFromFormat('Y-m-d', $data['date'] ?? today())->format('d/m/Y') }}
                                </b>
                            </p>
                        </div>
                    </div>
                    <div class="tm_invoice_head">
                        <div class="tm_invoice_left">
                            <p class="tm_mb2"><b class="tm_primary_color">Kính gửi:</b></p>
                            <p>
                                {{ $data['quote_to'] ?? 'N/A' }}<br>
                                @if ($data['quote_to_phone'] ?? null)
                                    {{ $data['quote_to_phone'] }} <br>
                                @endif
                                @if ($data['quote_to_email'] ?? null)
                                    {{ $data['quote_to_email'] }}
                                @endif
                            </p>
                        </div>
                        <div class="tm_invoice_right tm_text_right">
                            <p class="tm_mb2"><b class="tm_primary_color">Liên hệ Mua hàng:</b></p>
                            <p>
                                {{ $data['quote_by'] ?? 'N/A' }}<br>
                                @if ($data['quote_by_phone'] ?? null)
                                    {{ $data['quote_by_phone'] }} <br>
                                @endif
                                @if ($data['quote_by_email'] ?? null)
                                    {{ $data['quote_by_email'] }}
                                @endif
                            </p>
                        </div>

                    </div>

                    <p class="tm_semi_bold tm_text_uppercase">{{ $data['quote_to_company'] ?? '' }}</p>

                    <div class="tm_table tm_style1 tm_mb30">
                        <div class="tm_round_border">
                            <div class="tm_table_responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="tm_width_4 tm_semi_bold tm_primary_color tm_gray_bg">
                                                Diễn giải Hàng hoá
                                            </th>
                                            <th class="tm_width_2 tm_semi_bold tm_primary_color tm_gray_bg">Đơn giá</th>
                                            <th class="tm_width_1 tm_semi_bold tm_primary_color tm_gray_bg">SL</th>
                                            <th
                                                class="tm_width_2 tm_semi_bold tm_primary_color tm_gray_bg tm_text_right">
                                                Thành tiền
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($products as $product)
                                            <tr>
                                                <td class="tm_width_4">{{ $product['product_description'] ?? '' }}</td>
                                                <td class="tm_width_2">{{ $product['formated_price'] ?? 0 }}</td>
                                                <td class="tm_width_1">{{ $product['formated_qty'] ?? 0 }}</td>
                                                <td class="tm_width_2 tm_text_right">
                                                    {{ $product['formated_total'] ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tm_invoice_footer tm_mb10">
                            <div class="tm_left_footer">
                                <p class="tm_mb2"><b class="tm_primary_color">Điều khoản:</b></p>
                                @if (count($data['terms_and_conditions'] ?? []))
                                    <ul>
                                        @foreach ($data['terms_and_conditions'] as $term)
                                            <li class="tm_italic">{{ $term }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            <div class="tm_right_footer">
                                <table>
                                    <tbody>
                                        <tr>
                                            <td class="tm_width_3 tm_primary_color tm_border_none tm_bold">Tổng:</td>
                                            <td
                                                class="tm_width_3 tm_primary_color tm_text_right tm_border_none tm_bold">
                                                {{ $formatedTotal }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="tm_width_3 tm_primary_color tm_border_none tm_pt0">Thuế:</td>
                                            <td class="tm_width_3 tm_primary_color tm_text_right tm_border_none tm_pt0">
                                                {{ $formatedVatTotal }}
                                            </td>
                                        </tr>
                                        <tr class="tm_border_top tm_border_bottom">
                                            <td class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_primary_color">
                                                Tổng cộng:
                                            </td>
                                            <td
                                                class="tm_width_3 tm_border_top_0 tm_bold tm_f16 tm_primary_color tm_text_right">
                                                {{ $formatedGrandTotal }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <p class="tm_italic tm_text_right">
                            @if ($ammountInWords)
                                Bằng chữ: {{ $ammountInWords }} ./.
                            @endif
                        </p>

                    </div>
                    <div class="tm_padd_15_20 tm_round_border">
                        <p class="tm_mb5"><b class="tm_primary_color">Ghi chú:</b></p>
                        <p>{!! $data['notes'] ?? null ? nl2br(e($data['notes'])) : '' !!}</p>
                    </div>
                </div>
            </div>
            <div class="tm_invoice_btns tm_hide_print">
                <a href="javascript:window.print()" class="tm_invoice_btn tm_color1">
                    <span class="tm_btn_icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512">
                            <path
                                d="M384 368h24a40.12 40.12 0 0040-40V168a40.12 40.12 0 00-40-40H104a40.12 40.12 0 00-40 40v160a40.12 40.12 0 0040 40h24"
                                fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32"></path>
                            <rect x="128" y="240" width="256" height="208" rx="24.32" ry="24.32"
                                fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32"></rect>
                            <path d="M384 128v-24a40.12 40.12 0 00-40-40H168a40.12 40.12 0 00-40 40v24" fill="none"
                                stroke="currentColor" stroke-linejoin="round" stroke-width="32"></path>
                            <circle cx="392" cy="184" r="24" fill="currentColor"></circle>
                        </svg>
                    </span>
                    <span class="tm_btn_text">In</span>
                </a>
            </div>
        </div>
    </div>
</body>

</html>
