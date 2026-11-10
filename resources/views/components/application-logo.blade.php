@props(['dark' => false])

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <span class="h-9 w-9 rounded-md border flex items-center justify-center font-serif text-lg shrink-0 {{ $dark ? 'bg-paper text-ink border-line' : 'bg-ink text-paper border-ink' }}">
        DC
    </span>
    <div class="flex flex-col">
        <span class="font-serif text-xl leading-none {{ $dark ? 'text-paper' : 'text-ink' }}">
            DigitalCard
        </span>
        <span class="text-xs font-medium {{ $dark ? 'text-slate-300' : 'text-slate-600' }} mt-0.5">
            Loja universitária com cartão virtual
        </span>
    </div>
</div>
