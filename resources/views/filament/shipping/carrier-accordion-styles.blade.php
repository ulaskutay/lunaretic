<style>
    .etic-shipping-accordion-item {
        overflow: hidden;
        margin-bottom: .75rem;
        border: 1px solid #e5e7eb;
        border-radius: .875rem;
        background: #fff;
        transition: border-color 160ms ease, box-shadow 160ms ease;
    }

    .etic-shipping-accordion-item.is-open {
        margin-bottom: 0;
        border-bottom: 0;
        border-color: rgb(59 130 246 / .55);
        border-radius: .875rem .875rem 0 0;
        box-shadow: 0 0 0 3px rgb(59 130 246 / .08);
    }

    .etic-shipping-accordion-item.is-open + .etic-shipping-accordion-panel {
        display: block;
    }

    .etic-shipping-accordion-header {
        display: flex !important;
        flex-wrap: nowrap !important;
        align-items: center;
        gap: .75rem;
        width: 100%;
        min-width: 0;
        padding: .875rem 1rem;
    }

    .etic-shipping-accordion-main {
        display: flex !important;
        flex: 1 1 auto;
        flex-wrap: nowrap !important;
        align-items: center;
        gap: 1rem;
        min-width: 0;
        margin: 0;
        padding: 0;
        border: 0;
        background: transparent;
        text-align: left;
        cursor: pointer;
        color: inherit;
    }

    .etic-shipping-accordion-main:hover {
        opacity: .92;
    }

    .etic-shipping-accordion-logo {
        display: flex;
        flex: 0 0 auto;
        align-items: center;
        justify-content: center;
        width: 7.5rem;
        height: 3.25rem;
        padding: .5rem .75rem;
        border-radius: .625rem;
        background: #fff;
        box-shadow: inset 0 0 0 1px rgb(15 23 42 / .06);
    }

    .etic-shipping-accordion-logo img {
        display: block;
        width: auto;
        max-width: 100%;
        max-height: 2.25rem;
        object-fit: contain;
    }

    .etic-shipping-accordion-copy {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        gap: .35rem;
        min-width: 0;
    }

    .etic-shipping-accordion-title-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .5rem;
    }

    .etic-shipping-accordion-title {
        color: #111827;
        font-size: .9375rem;
        font-weight: 600;
        line-height: 1.25rem;
    }

    .etic-shipping-accordion-description {
        color: #6b7280;
        font-size: .8125rem;
        line-height: 1.45;
    }

    .etic-shipping-accordion-actions {
        display: flex;
        flex: 0 0 auto;
        flex-shrink: 0;
        align-items: center;
        gap: .5rem;
        margin-left: auto;
        padding-left: .25rem;
    }

    .etic-shipping-status {
        display: inline-flex;
        align-items: center;
        padding: .2rem .55rem;
        border-radius: 9999px;
        font-size: .6875rem;
        font-weight: 600;
        line-height: 1rem;
        white-space: nowrap;
    }

    .etic-shipping-status.is-enabled {
        background: rgb(220 252 231);
        color: rgb(21 128 61);
    }

    .etic-shipping-status.is-disabled {
        background: rgb(243 244 246);
        color: rgb(75 85 99);
    }

    .etic-shipping-status.is-incomplete {
        background: rgb(254 243 199);
        color: rgb(180 83 9);
    }

    .etic-shipping-chevron-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        margin: 0;
        padding: 0;
        border: 0;
        border-radius: .5rem;
        background: transparent;
        color: #9ca3af;
        cursor: pointer;
        transition: transform 180ms ease, color 180ms ease, background-color 160ms ease;
    }

    .etic-shipping-chevron-btn:hover {
        background: rgb(243 244 246);
        color: #374151;
    }

    .etic-shipping-chevron-btn.is-open {
        transform: rotate(180deg);
        color: #2563eb;
    }

    .etic-shipping-toggle {
        display: inline-flex;
        margin: 0;
        padding: 0;
        border: 0;
        background: transparent;
        cursor: pointer;
    }

    .etic-shipping-toggle-track {
        position: relative;
        display: block;
        width: 2.75rem;
        height: 1.5rem;
        border-radius: 9999px;
        background: #d1d5db;
        transition: background-color 160ms ease;
    }

    .etic-shipping-toggle.is-on .etic-shipping-toggle-track {
        background: #2563eb;
    }

    .etic-shipping-toggle-thumb {
        position: absolute;
        top: .125rem;
        left: .125rem;
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 9999px;
        background: #fff;
        box-shadow: 0 1px 2px rgb(15 23 42 / .18);
        transition: transform 160ms ease;
    }

    .etic-shipping-toggle.is-on .etic-shipping-toggle-thumb {
        transform: translateX(1.25rem);
    }

    .etic-shipping-accordion-panel {
        margin-bottom: .75rem;
        border: 1px solid rgb(59 130 246 / .55);
        border-top: 1px solid #e5e7eb;
        border-radius: 0 0 .875rem .875rem;
        padding: 1rem 1.125rem 1.25rem;
        background: #fcfcfd;
        box-shadow: 0 0 0 3px rgb(59 130 246 / .08);
    }

    .etic-shipping-rates-card {
        overflow: hidden;
        border-radius: .875rem;
    }

    .etic-shipping-carriers-section .fi-sc-section-content-ctn > .fi-sc-flex,
    .etic-payment-providers-section .fi-sc-section-content-ctn > .fi-sc-flex {
        gap: 0 !important;
    }

    .dark .etic-shipping-accordion-item {
        border-color: rgb(255 255 255 / .1);
        background: rgb(17 24 39);
    }

    .dark .etic-shipping-accordion-logo {
        background: #fff;
        box-shadow: none;
    }

    .dark .etic-shipping-accordion-title {
        color: #fff;
    }

    .dark .etic-shipping-accordion-description {
        color: #9ca3af;
    }

    .dark .etic-shipping-status.is-disabled {
        background: rgb(255 255 255 / .1);
        color: #d1d5db;
    }

    .dark .etic-shipping-status.is-enabled {
        background: rgb(20 83 45 / .45);
        color: #86efac;
    }

    .dark .etic-shipping-status.is-incomplete {
        background: rgb(146 64 14 / .35);
        color: #fcd34d;
    }

    .dark .etic-shipping-chevron-btn:hover {
        background: rgb(255 255 255 / .08);
        color: #e5e7eb;
    }

    .dark .etic-shipping-accordion-panel {
        border-color: rgb(96 165 250 / .45);
        background: rgb(255 255 255 / .02);
    }

    @media (max-width: 40rem) {
        .etic-shipping-accordion-header {
            flex-wrap: wrap !important;
            align-items: flex-start;
        }

        .etic-shipping-accordion-main {
            flex: 1 1 100%;
        }

        .etic-shipping-accordion-actions {
            width: 100%;
            justify-content: flex-end;
            margin-left: 0;
            padding-top: .25rem;
            border-top: 1px solid rgb(15 23 42 / .06);
        }

        .dark .etic-shipping-accordion-actions {
            border-top-color: rgb(255 255 255 / .08);
        }
    }
</style>
