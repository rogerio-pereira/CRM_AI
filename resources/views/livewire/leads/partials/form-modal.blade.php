<flux:modal wire:model.self="showFormModal" class="max-w-2xl" data-test="leads-form-modal">
    <form wire:submit="saveClient" class="space-y-6">
        <flux:heading size="lg">
            @if ($editingClientId === null)
                {{ __('New client') }}
            @else
                {{ __('Edit client') }}
            @endif
        </flux:heading>

        <flux:input wire:model="company_name" name="company_name" :label="__('Company name')" required data-test="leads-form-company-name" />

        <div class="space-y-4">
            <flux:subheading>{{ __('Contacts') }}</flux:subheading>

            @foreach ($contacts as $index => $contact)
                <div class="grid gap-4 rounded-lg border border-border-subtle p-4 md:grid-cols-3" wire:key="contact-{{ $index }}">
                    <flux:input wire:model="contacts.{{ $index }}.name" :label="__('Name')" data-test="leads-form-contact-name-{{ $index }}" />
                    <flux:input wire:model="contacts.{{ $index }}.email" :label="__('Email')" type="email" data-test="leads-form-contact-email-{{ $index }}" />
                    <flux:input wire:model="contacts.{{ $index }}.phone" :label="__('Phone')" data-test="leads-form-contact-phone-{{ $index }}" />
                    @if (count($contacts) > 1)
                        <div class="md:col-span-3">
                            <flux:button type="button" size="sm" variant="ghost" wire:click="removeContactRow({{ $index }})">
                                {{ __('Remove contact') }}
                            </flux:button>
                        </div>
                    @endif
                </div>
            @endforeach

            <flux:button type="button" size="sm" variant="ghost" wire:click="addContactRow" data-test="leads-form-add-contact">
                {{ __('Add contact') }}
            </flux:button>
        </div>

        <flux:input wire:model="website" name="website" :label="__('Website')" type="url" data-test="leads-form-website" />

        <div class="grid gap-4 md:grid-cols-3">
            <flux:input wire:model="social_links.linkedin" name="social_links.linkedin" :label="__('LinkedIn')" type="url" />
            <flux:input wire:model="social_links.twitter" name="social_links.twitter" :label="__('Twitter / X')" type="url" />
            <flux:input wire:model="social_links.facebook" name="social_links.facebook" :label="__('Facebook')" type="url" />
        </div>

        <flux:input wire:model="lead_source" name="lead_source" :label="__('Lead source')" data-test="leads-form-lead-source" />

        <flux:textarea wire:model="qualification_notes" name="qualification_notes" :label="__('Qualification notes')" rows="4" data-test="leads-form-qualification-notes" />

        <div class="flex justify-end gap-2">
            <flux:button type="button" variant="ghost" wire:click="closeFormModal">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" data-test="leads-form-submit">{{ __('Save') }}</flux:button>
        </div>
    </form>
</flux:modal>
