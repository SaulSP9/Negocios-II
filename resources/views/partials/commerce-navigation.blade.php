<div class="management-subnav operation-tabs" role="tablist" aria-label="Tienda y atención" @unless ($management) hidden @endunless>
    @foreach ($operation as $key => [$label, $permission])
        <button type="button" role="tab" id="tab-{{ $key }}"
            data-operation-tab="{{ $key }}" data-permission="{{ $permission }}"
            aria-controls="operacion-{{ $key }}" aria-selected="{{ $key === $operationInitial ? 'true' : 'false' }}"
            tabindex="{{ $key === $operationInitial ? 0 : -1 }}"
            @unless ($management && auth()->user()->hasPermission($permission)) hidden @endunless>{{ $label }}</button>
    @endforeach
</div>
