@if(($mode ?? 'trigger') === 'dialog')
    <dialog class="etic-pdp__size-chart" data-pdp-size-chart aria-label="{{ __('etic.storefront.product.size_chart') }}">
        <div class="etic-pdp__size-chart-panel">
            <header>
                <h2>{{ __('etic.storefront.product.size_chart') }}</h2>
                <button type="button" data-pdp-size-chart-close aria-label="Kapat">×</button>
            </header>
            <div class="etic-pdp__size-chart-table">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('etic.storefront.product.size_chart_size') }}</th>
                            <th>{{ __('etic.storefront.product.size_chart_chest') }}</th>
                            <th>{{ __('etic.storefront.product.size_chart_waist') }}</th>
                            <th>{{ __('etic.storefront.product.size_chart_hip') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><th scope="row">XS</th><td>84–88</td><td>68–72</td><td>86–90</td></tr>
                        <tr><th scope="row">S</th><td>88–92</td><td>72–76</td><td>90–94</td></tr>
                        <tr><th scope="row">M</th><td>92–96</td><td>76–80</td><td>94–98</td></tr>
                        <tr><th scope="row">L</th><td>96–100</td><td>80–84</td><td>98–102</td></tr>
                        <tr><th scope="row">XL</th><td>100–104</td><td>84–88</td><td>102–106</td></tr>
                        <tr><th scope="row">XXL</th><td>104–110</td><td>88–94</td><td>106–112</td></tr>
                    </tbody>
                </table>
            </div>
            <p>{{ __('etic.storefront.product.size_chart_note') }}</p>
        </div>
    </dialog>
@else
    <button type="button" class="etic-pdp__size-chart-trigger" data-pdp-size-chart-open>
        <span>— {{ __('etic.storefront.product.size_chart_link') }}</span>
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <rect x="2.5" y="8" width="19" height="8" rx="1.2" fill="none" stroke="currentColor" stroke-width="1.4" />
            <path d="M6 8v3.2M9.5 8v2.2M13 8v3.2M16.5 8v2.2M20 8v3.2" fill="none" stroke="currentColor" stroke-width="1.3" />
        </svg>
    </button>
@endif
