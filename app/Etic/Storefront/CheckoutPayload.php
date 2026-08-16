<?php

namespace App\Etic\Storefront;

final class CheckoutPayload
{
    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string', 'max:30'],
            'line_one' => ['required', 'string', 'max:191'],
            'line_two' => ['nullable', 'string', 'max:191'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'shipping' => ['nullable', 'string'],
            'payment' => ['required', 'in:cash-in-hand,iyzico'],
            'payment_token' => ['nullable', 'string'],
            'same_as_shipping' => ['nullable', 'boolean'],
            'billing_first_name' => ['required_if:same_as_shipping,0', 'required_if:same_as_shipping,false', 'nullable', 'string', 'max:80'],
            'billing_last_name' => ['required_if:same_as_shipping,0', 'required_if:same_as_shipping,false', 'nullable', 'string', 'max:80'],
            'billing_email' => ['nullable', 'email', 'max:191'],
            'billing_phone' => ['nullable', 'string', 'max:30'],
            'billing_line_one' => ['required_if:same_as_shipping,0', 'required_if:same_as_shipping,false', 'nullable', 'string', 'max:191'],
            'billing_line_two' => ['nullable', 'string', 'max:191'],
            'billing_city' => ['required_if:same_as_shipping,0', 'required_if:same_as_shipping,false', 'nullable', 'string', 'max:80'],
            'billing_state' => ['nullable', 'string', 'max:80'],
            'billing_postcode' => ['nullable', 'string', 'max:20'],
            'billing_is_corporate' => ['nullable', 'boolean'],
            'billing_company_name' => ['required_if:billing_is_corporate,1', 'required_if:billing_is_corporate,true', 'nullable', 'string', 'max:191'],
            'billing_tax_identifier' => ['required_if:billing_is_corporate,1', 'required_if:billing_is_corporate,true', 'nullable', 'string', 'max:32'],
            'billing_tax_office' => ['required_if:billing_is_corporate,1', 'required_if:billing_is_corporate,true', 'nullable', 'string', 'max:120'],
        ];
    }

    public static function usesSameBilling(array $payload): bool
    {
        return filter_var($payload['same_as_shipping'] ?? true, FILTER_VALIDATE_BOOLEAN);
    }

    public static function isCorporateBilling(array $payload): bool
    {
        return filter_var($payload['billing_is_corporate'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }
}
