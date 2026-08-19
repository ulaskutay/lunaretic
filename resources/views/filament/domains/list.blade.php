@php
    /** @var \Illuminate\Support\Collection<int, \App\Etic\Store\Models\CustomDomain> $domains */
    $domains = $domains ?? collect();
    $target = $target ?? '';
    $statuses = $statuses ?? [];
@endphp

<style>
    .etic-domain-list { display: grid; gap: 1rem; color: #111827; }
    .etic-domain-card {
        border: 1px solid #e5e7eb;
        border-radius: .875rem;
        background: #fff;
        color: #111827;
        overflow: hidden;
        color-scheme: light;
    }
    .etic-domain-card__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f3f4f6;
    }
    .etic-domain-card__host { font-size: .95rem; font-weight: 650; word-break: break-all; color: #111827; }
    .etic-domain-card__meta { margin: .25rem 0 0; color: #4b5563; font-size: .8rem; }
    .etic-domain-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .2rem .6rem;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        background: #f3f4f6;
        color: #374151;
        white-space: nowrap;
    }
    .etic-domain-badge.is-active { background: #dcfce7; color: #166534; }
    .etic-domain-badge.is-failed { background: #fee2e2; color: #991b1b; }
    .etic-domain-badge.is-pending,
    .etic-domain-badge.is-verifying { background: #fef3c7; color: #92400e; }
    .etic-domain-card__body { padding: 1rem 1.25rem 1.15rem; }
    .etic-domain-note { margin: 0 0 .85rem; color: #4b5563; font-size: .82rem; line-height: 1.45; }
    .etic-domain-table { width: 100%; border-collapse: collapse; font-size: .8rem; color: #111827; }
    .etic-domain-table th,
    .etic-domain-table td { padding: .55rem .5rem; text-align: left; border-bottom: 1px solid #e5e7eb; vertical-align: middle; color: #111827; }
    .etic-domain-table th { color: #6b7280; font-weight: 600; }
    .etic-domain-mono {
        display: inline-block;
        max-width: 100%;
        padding: .2rem .45rem;
        border-radius: .3rem;
        background: #f3f4f6;
        color: #111827;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .78rem;
        word-break: break-all;
    }
    .etic-domain-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }
    .etic-domain-actions button {
        border: 1px solid #d1d5db;
        background: #fff;
        color: #111827;
        border-radius: .5rem;
        padding: .45rem .75rem;
        font-size: .8rem;
        font-weight: 600;
        cursor: pointer;
    }
    .etic-domain-actions button.primary { background: #111827; color: #fff; border-color: #111827; }
    .etic-domain-empty { color: #9ca3af; font-size: .9rem; padding: .25rem 0 1rem; }
</style>

<section class="etic-domain-list fi-not-prose">
    @forelse ($domains as $domain)
        @php
            $status = $domain->status;
            $records = $domain->dnsRecords($target);
        @endphp
        <article class="etic-domain-card">
            <div class="etic-domain-card__head">
                <div>
                    <div class="etic-domain-card__host">{{ $domain->hostname }}</div>
                    <p class="etic-domain-card__meta">
                        @if ($domain->isActive())
                            {{ __('etic.filament.domains.active_help') }}
                        @else
                            {{ __('etic.filament.domains.dns_help', ['target' => $target]) }}
                        @endif
                    </p>
                </div>
                <span class="etic-domain-badge is-{{ $status }}">{{ $statuses[$status] ?? $status }}</span>
            </div>
            @if (! $domain->isActive())
                <div class="etic-domain-card__body">
                    @if ($domain->usesApex())
                        <p class="etic-domain-note">{{ __('etic.filament.domains.apex_help') }}</p>
                    @endif
                    <table class="etic-domain-table">
                        <thead>
                            <tr>
                                <th>{{ __('etic.filament.domains.dns_type') }}</th>
                                <th>{{ __('etic.filament.domains.dns_name') }}</th>
                                <th>{{ __('etic.filament.domains.dns_value') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($records as $record)
                                <tr>
                                    <td>{{ $record['type'] }}</td>
                                    <td><span class="etic-domain-mono">{{ $record['name'] }}</span></td>
                                    <td><span class="etic-domain-mono">{{ $record['value'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="etic-domain-actions">
                        <button type="button" class="primary" wire:click="verifyDomain({{ $domain->id }})" wire:loading.attr="disabled">
                            {{ __('etic.filament.domains.verify') }}
                        </button>
                        <button type="button" wire:click="removeDomain({{ $domain->id }})" wire:confirm="{{ __('etic.filament.domains.remove_confirm') }}">
                            {{ __('etic.filament.domains.remove') }}
                        </button>
                    </div>
                </div>
            @else
                <div class="etic-domain-card__body">
                    <p class="etic-domain-note">{{ __('etic.filament.domains.ssl_help') }}</p>
                    <div class="etic-domain-actions">
                        <button type="button" wire:click="removeDomain({{ $domain->id }})" wire:confirm="{{ __('etic.filament.domains.remove_confirm') }}">
                            {{ __('etic.filament.domains.remove') }}
                        </button>
                    </div>
                </div>
            @endif
        </article>
    @empty
        <p class="etic-domain-empty">{{ __('etic.filament.domains.empty') }}</p>
    @endforelse
</section>
