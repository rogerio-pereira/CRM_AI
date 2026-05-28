<flux:modal wire:model.self="showFormModal" class="max-w-2xl" data-test="leads-form-modal">
    <form wire:submit="saveClient" class="space-y-6">
        <flux:heading size="lg">
            @if ($editingClientId)
                {{ __('Edit lead') }}
            @else
                {{ __('New lead') }}
            @endif
        </flux:heading>

        <flux:input
            wire:model="company_name"
            name="company_name"
            :label="__('Company name')"
            required
            data-test="leads-form-company-name"
        />

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:input
                wire:model="contact_name"
                name="contact_name"
                :label="__('Contact name')"
                data-test="leads-form-contact-name"
            />
            <flux:input
                wire:model="contact_email"
                name="contact_email"
                type="email"
                :label="__('Email')"
                data-test="leads-form-contact-email"
            />
            <flux:input
                wire:model="contact_phone"
                name="contact_phone"
                :label="__('Phone')"
                data-test="leads-form-contact-phone"
            />
        </div>

        <flux:input
            wire:model="website"
            name="website"
            :label="__('Website')"
            :placeholder="__('example.com')"
            data-test="leads-form-website"
        />

        <div class="space-y-3">
            <flux:subheading>{{ __('Social links') }}</flux:subheading>

            @foreach ($social_links as $index => $link)
                <div class="grid gap-3 sm:grid-cols-2" wire:key="social-link-{{ $index }}">
                    <flux:input
                        wire:model="social_links.{{ $index }}.platform"
                        :label="__('Platform')"
                        data-test="leads-form-social-platform-{{ $index }}"
                    />
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <flux:input
                                wire:model="social_links.{{ $index }}.url"
                                :label="__('URL')"
                                :placeholder="__('example.com')"
                                data-test="leads-form-social-url-{{ $index }}"
                            />
                        </div>
                        @if (count($social_links) > 1)
                            <flux:button
                                type="button"
                                size="sm"
                                variant="ghost"
                                wire:click="removeSocialLinkRow({{ $index }})"
                                data-test="leads-form-remove-social-{{ $index }}"
                            >
                                {{ __('Remove') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endforeach

            <flux:button
                type="button"
                size="sm"
                variant="ghost"
                wire:click="addSocialLinkRow"
                data-test="leads-form-add-social"
            >
                {{ __('Add social link') }}
            </flux:button>
        </div>

        <flux:input
            wire:model="lead_source"
            name="lead_source"
            :label="__('Lead source')"
            data-test="leads-form-lead-source"
        />

        <flux:textarea
            wire:model="qualification_notes"
            name="qualification_notes"
            :label="__('Qualification notes')"
            rows="4"
            data-test="leads-form-qualification-notes"
        />

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" data-test="leads-form-submit">
                {{ __('Save') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
