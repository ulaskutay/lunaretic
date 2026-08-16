@props(['item'])

@php
    $columns = \App\Etic\CMS\MegaMenu::columns($item);
    $tiles = \App\Etic\CMS\MegaMenu::tiles();
@endphp

<div class="etic-header__dropdown">
    <div class="etic-header__mega">
        <div class="etic-header__mega-cols">
            @foreach($columns as $column)
                <div class="etic-header__mega-col">
                    @if(filled($column['title']))
                        <a class="etic-header__mega-heading" href="{{ $column['url'] }}">{{ $column['title'] }}</a>
                    @endif
                    @foreach($column['links'] as $link)
                        <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                    @endforeach
                </div>
            @endforeach
        </div>
        @if($tiles !== [])
            <div class="etic-header__mega-tiles">
                @foreach($tiles as $tile)
                    <a class="etic-header__mega-tile" href="{{ $tile['url'] }}">
                        <img src="{{ $tile['image'] }}" alt="{{ $tile['label'] }}">
                        <span>{{ $tile['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
