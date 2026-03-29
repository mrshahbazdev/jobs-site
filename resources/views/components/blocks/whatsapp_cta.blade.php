<!-- Block: WhatsApp CTA -->
<div class="mx-auto max-w-7xl px-4 lg:px-8 my-12">
    <x-whatsapp-alert 
        :variant="$block->variant ?? 'large'" 
        :title="$block->heading_text ?? null"
        :subtitle="$block->sub_text ?? null"
        :icon="$block->icon ?? null"
    />
</div>
