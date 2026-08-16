@php
    $sameBilling = filter_var(old('same_as_shipping', '1'), FILTER_VALIDATE_BOOLEAN);
    $corporateBilling = filter_var(old('billing_is_corporate', '0'), FILTER_VALIDATE_BOOLEAN);
@endphp

<div class="etic-checkout__billing" data-checkout-billing>
    <input type="hidden" name="same_as_shipping" value="0" data-same-as-shipping-fallback>

    <label class="etic-checkout__checkbox">
        <input
            type="checkbox"
            name="billing_is_corporate"
            value="1"
            data-billing-corporate
            @checked($corporateBilling)
        >
        Kurumsal fatura istiyorum
    </label>

    <div class="etic-checkout__corporate" data-corporate-panel @if(! $corporateBilling) hidden @endif>
        <div class="etic-checkout__row">
            <label class="etic-checkout__field">
                <span class="etic-checkout__label">Firma ünvanı</span>
                <input name="billing_company_name" value="{{ old('billing_company_name') }}" class="etic-checkout__input" data-corporate-field>
            </label>
        </div>
        <div class="etic-checkout__row">
            <label class="etic-checkout__field">
                <span class="etic-checkout__label">Vergi dairesi</span>
                <input name="billing_tax_office" value="{{ old('billing_tax_office') }}" class="etic-checkout__input" data-corporate-field>
            </label>
            <label class="etic-checkout__field">
                <span class="etic-checkout__label">Vergi no / TCKN</span>
                <input name="billing_tax_identifier" value="{{ old('billing_tax_identifier') }}" inputmode="numeric" class="etic-checkout__input" data-corporate-field>
            </label>
        </div>
    </div>

    <label class="etic-checkout__checkbox etic-checkout__billing-toggle">
        <input
            type="checkbox"
            name="same_as_shipping"
            value="1"
            data-billing-same
            @checked($sameBilling)
        >
        Fatura adresim teslimat adresimle aynı
    </label>

    <div class="etic-checkout__billing-panel" data-billing-panel @if($sameBilling) hidden @endif>
        <p class="etic-checkout__billing-lead">Fatura farklı bir adrese kesilecekse bilgileri girin.</p>

        <div class="etic-checkout__fields etic-checkout__fields--billing">
            <div class="etic-checkout__row">
                <label class="etic-checkout__field">
                    <span class="etic-checkout__label">Fatura adı</span>
                    <input name="billing_first_name" value="{{ old('billing_first_name') }}" class="etic-checkout__input" data-billing-field>
                </label>
                <label class="etic-checkout__field">
                    <span class="etic-checkout__label">Fatura soyadı</span>
                    <input name="billing_last_name" value="{{ old('billing_last_name') }}" class="etic-checkout__input" data-billing-field>
                </label>
            </div>
            <div class="etic-checkout__row">
                <label class="etic-checkout__field">
                    <span class="etic-checkout__label">Fatura e-postası</span>
                    <input type="email" name="billing_email" value="{{ old('billing_email') }}" class="etic-checkout__input" data-billing-field>
                </label>
                <label class="etic-checkout__field">
                    <span class="etic-checkout__label">Fatura telefonu</span>
                    <input name="billing_phone" value="{{ old('billing_phone') }}" class="etic-checkout__input" data-billing-field>
                </label>
            </div>
            <label class="etic-checkout__field">
                <span class="etic-checkout__label">Fatura adresi</span>
                <input name="billing_line_one" value="{{ old('billing_line_one') }}" class="etic-checkout__input" data-billing-field>
            </label>
            <label class="etic-checkout__field">
                <span class="etic-checkout__label">Adres satırı 2</span>
                <input name="billing_line_two" value="{{ old('billing_line_two') }}" class="etic-checkout__input" data-billing-field>
            </label>
            <div class="etic-checkout__row">
                <label class="etic-checkout__field">
                    <span class="etic-checkout__label">İl</span>
                    <input name="billing_city" value="{{ old('billing_city') }}" class="etic-checkout__input" data-billing-field>
                </label>
                <label class="etic-checkout__field">
                    <span class="etic-checkout__label">İlçe</span>
                    <input name="billing_state" value="{{ old('billing_state') }}" class="etic-checkout__input" data-billing-field>
                </label>
            </div>
            <label class="etic-checkout__field">
                <span class="etic-checkout__label">Posta kodu</span>
                <input name="billing_postcode" value="{{ old('billing_postcode') }}" class="etic-checkout__input" data-billing-field>
            </label>
        </div>
    </div>
</div>
