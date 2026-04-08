@php
    $branch = $branch ?? null;
@endphp

<div class="row m-t-3 mb-3">
    <div class="col-md-4 form-group">
        <label>{{ __('dashboard.tax_settings.name') }} <span class="text-danger">*</span></label>
        <input
            class="form-control mg-b-20 branch-input @error('name') is-invalid @enderror"
            name="name"
            type="text"
            value="{{ old('name', $branch->name ?? '') }}"
            autocomplete="organization"
            maxlength="255"
            data-branch-validate="1"
            data-label="{{ __('dashboard.tax_settings.name') }}"
            data-required="1"
            data-required-message="{{ __('dashboard.tax_settings.validations.name_required') }}"
        >
        @error('name')
            <span class="invalid-feedback d-block" data-feedback-for="name" role="alert"><strong>{{ $message }}</strong></span>
        @else
            <span class="invalid-feedback d-block" data-feedback-for="name" role="alert" style="display:none"></span>
        @enderror
    </div>

    <div class="col-md-4 form-group">
        <label>{{ __('dashboard.tax_settings.email') }} <span class="text-danger">*</span></label>
        <input
            class="form-control mg-b-20 branch-input @error('email') is-invalid @enderror"
            name="email"
            type="email"
            value="{{ old('email', $branch->email ?? '') }}"
            autocomplete="email"
            maxlength="255"
            inputmode="email"
            data-branch-validate="1"
            data-label="{{ __('dashboard.tax_settings.email') }}"
            data-required="1"
            data-email="1"
            data-required-message="{{ __('dashboard.tax_settings.validations.email_required') }}"
            data-email-message="{{ __('dashboard.tax_settings.validations.email_email') }}"
        >
        @error('email')
            <span class="invalid-feedback d-block" data-feedback-for="email" role="alert"><strong>{{ $message }}</strong></span>
        @else
            <span class="invalid-feedback d-block" data-feedback-for="email" role="alert" style="display:none"></span>
        @enderror
    </div>

    <div class="col-md-4 form-group">
        <label>{{ __('dashboard.tax_settings.phone') }} <span class="text-danger">*</span></label>
        <input
            class="form-control mg-b-20 branch-input @error('phone') is-invalid @enderror"
            name="phone"
            type="text"
            value="{{ old('phone', $branch->phone ?? '') }}"
            autocomplete="tel"
            maxlength="50"
            inputmode="tel"
            data-branch-validate="1"
            data-label="{{ __('dashboard.tax_settings.phone') }}"
            data-required="1"
            data-required-message="{{ __('dashboard.tax_settings.validations.phone_required') }}"
        >
        @error('phone')
            <span class="invalid-feedback d-block" data-feedback-for="phone" role="alert"><strong>{{ $message }}</strong></span>
        @else
            <span class="invalid-feedback d-block" data-feedback-for="phone" role="alert" style="display:none"></span>
        @enderror
    </div>

    <div class="col-md-4 form-group">
        <label>{{ __('dashboard.tax_settings.commercial_register') }} <span class="text-danger">*</span></label>
        <input
            class="form-control mg-b-20 branch-input @error('commercial_register') is-invalid @enderror"
            name="commercial_register"
            type="text"
            value="{{ old('commercial_register', $branch->commercial_register ?? '') }}"
            autocomplete="off"
            maxlength="10"
            inputmode="numeric"
            data-branch-validate="1"
            data-label="{{ __('dashboard.tax_settings.commercial_register') }}"
            data-required="1"
            data-digits="10"
            data-required-message="{{ __('dashboard.tax_settings.validations.commercial_register_required') }}"
            data-digits-message="{{ __('dashboard.tax_settings.validations.commercial_register_digits', ['digits' => 10]) }}"
        >
        @error('commercial_register')
            <span class="invalid-feedback d-block" data-feedback-for="commercial_register" role="alert"><strong>{{ $message }}</strong></span>
        @else
            <span class="invalid-feedback d-block" data-feedback-for="commercial_register" role="alert" style="display:none"></span>
        @enderror
    </div>

    <div class="col-md-4 form-group">
        <label>{{ __('dashboard.tax_settings.tax_number') }} <span class="text-danger">*</span></label>
        <input
            class="form-control mg-b-20 branch-input @error('tax_number') is-invalid @enderror"
            name="tax_number"
            type="text"
            value="{{ old('tax_number', $branch->tax_number ?? '') }}"
            autocomplete="off"
            maxlength="15"
            inputmode="numeric"
            data-branch-validate="1"
            data-label="{{ __('dashboard.tax_settings.tax_number') }}"
            data-required="1"
            data-digits="15"
            data-required-message="{{ __('dashboard.tax_settings.validations.tax_number_required') }}"
            data-digits-message="{{ __('dashboard.tax_settings.validations.tax_number_digits', ['digits' => 15]) }}"
        >
        @error('tax_number')
            <span class="invalid-feedback d-block" data-feedback-for="tax_number" role="alert"><strong>{{ $message }}</strong></span>
        @else
            <span class="invalid-feedback d-block" data-feedback-for="tax_number" role="alert" style="display:none"></span>
        @enderror
    </div>

    <div class="col-md-4 form-group">
        <label>{{ __('dashboard.tax_settings.street_name') }} <span class="text-danger">*</span></label>
        <input
            class="form-control mg-b-20 branch-input @error('street_name') is-invalid @enderror"
            name="street_name"
            type="text"
            value="{{ old('street_name', $branch->street_name ?? '') }}"
            autocomplete="address-line1"
            maxlength="255"
            data-branch-validate="1"
            data-label="{{ __('dashboard.tax_settings.street_name') }}"
            data-required="1"
            data-required-message="{{ __('dashboard.tax_settings.validations.street_name_required') }}"
        >
        @error('street_name')
            <span class="invalid-feedback d-block" data-feedback-for="street_name" role="alert"><strong>{{ $message }}</strong></span>
        @else
            <span class="invalid-feedback d-block" data-feedback-for="street_name" role="alert" style="display:none"></span>
        @enderror
    </div>

    <div class="col-md-4 form-group">
        <label>{{ __('dashboard.tax_settings.building_number') }} <span class="text-danger">*</span></label>
        <input
            class="form-control mg-b-20 branch-input @error('building_number') is-invalid @enderror"
            name="building_number"
            type="text"
            value="{{ old('building_number', $branch->building_number ?? '') }}"
            autocomplete="off"
            maxlength="4"
            inputmode="numeric"
            data-branch-validate="1"
            data-label="{{ __('dashboard.tax_settings.building_number') }}"
            data-required="1"
            data-digits="4"
            data-required-message="{{ __('dashboard.tax_settings.validations.building_number_required') }}"
            data-digits-message="{{ __('dashboard.tax_settings.validations.building_number_digits', ['digits' => 4]) }}"
        >
        @error('building_number')
            <span class="invalid-feedback d-block" data-feedback-for="building_number" role="alert"><strong>{{ $message }}</strong></span>
        @else
            <span class="invalid-feedback d-block" data-feedback-for="building_number" role="alert" style="display:none"></span>
        @enderror
    </div>

    <div class="col-md-4 form-group">
        <label>{{ __('dashboard.tax_settings.plot_identification') }} <span class="text-danger">*</span></label>
        <input
            class="form-control mg-b-20 branch-input @error('plot_identification') is-invalid @enderror"
            name="plot_identification"
            type="text"
            value="{{ old('plot_identification', $branch->plot_identification ?? '') }}"
            autocomplete="off"
            maxlength="4"
            inputmode="numeric"
            data-branch-validate="1"
            data-label="{{ __('dashboard.tax_settings.plot_identification') }}"
            data-required="1"
            data-digits="4"
            data-required-message="{{ __('dashboard.tax_settings.validations.plot_identification_required') }}"
            data-digits-message="{{ __('dashboard.tax_settings.validations.plot_identification_digits', ['digits' => 4]) }}"
        >
        @error('plot_identification')
            <span class="invalid-feedback d-block" data-feedback-for="plot_identification" role="alert"><strong>{{ $message }}</strong></span>
        @else
            <span class="invalid-feedback d-block" data-feedback-for="plot_identification" role="alert" style="display:none"></span>
        @enderror
    </div>

    <div class="col-md-4 form-group">
        <label>{{ __('dashboard.tax_settings.country') }} <span class="text-danger">*</span></label>
        <input
            class="form-control mg-b-20 branch-input @error('country') is-invalid @enderror"
            name="country"
            type="text"
            value="{{ old('country', $branch->country ?? '') }}"
            autocomplete="country-name"
            maxlength="255"
            data-branch-validate="1"
            data-label="{{ __('dashboard.tax_settings.country') }}"
            data-required="1"
            data-required-message="{{ __('dashboard.tax_settings.validations.country_required') }}"
        >
        @error('country')
            <span class="invalid-feedback d-block" data-feedback-for="country" role="alert"><strong>{{ $message }}</strong></span>
        @else
            <span class="invalid-feedback d-block" data-feedback-for="country" role="alert" style="display:none"></span>
        @enderror
    </div>

    <div class="col-md-4 form-group">
        <label>{{ __('dashboard.tax_settings.region') }} <span class="text-danger">*</span></label>
        <input
            class="form-control mg-b-20 branch-input @error('region') is-invalid @enderror"
            name="region"
            type="text"
            value="{{ old('region', $branch->region ?? '') }}"
            autocomplete="address-level1"
            maxlength="255"
            data-branch-validate="1"
            data-label="{{ __('dashboard.tax_settings.region') }}"
            data-required="1"
            data-required-message="{{ __('dashboard.tax_settings.validations.region_required') }}"
        >
        @error('region')
            <span class="invalid-feedback d-block" data-feedback-for="region" role="alert"><strong>{{ $message }}</strong></span>
        @else
            <span class="invalid-feedback d-block" data-feedback-for="region" role="alert" style="display:none"></span>
        @enderror
    </div>

    <div class="col-md-4 form-group">
        <label>{{ __('dashboard.tax_settings.city') }} <span class="text-danger">*</span></label>
        <input
            class="form-control mg-b-20 branch-input @error('city') is-invalid @enderror"
            name="city"
            type="text"
            value="{{ old('city', $branch->city ?? '') }}"
            autocomplete="address-level2"
            maxlength="255"
            data-branch-validate="1"
            data-label="{{ __('dashboard.tax_settings.city') }}"
            data-required="1"
            data-required-message="{{ __('dashboard.tax_settings.validations.city_required') }}"
        >
        @error('city')
            <span class="invalid-feedback d-block" data-feedback-for="city" role="alert"><strong>{{ $message }}</strong></span>
        @else
            <span class="invalid-feedback d-block" data-feedback-for="city" role="alert" style="display:none"></span>
        @enderror
    </div>

    <div class="col-md-4 form-group">
        <label>{{ __('dashboard.tax_settings.district') }} <span class="text-danger">*</span></label>
        <input
            class="form-control mg-b-20 branch-input @error('district') is-invalid @enderror"
            name="district"
            type="text"
            value="{{ old('district', $branch->district ?? '') }}"
            autocomplete="address-level3"
            maxlength="255"
            data-branch-validate="1"
            data-label="{{ __('dashboard.tax_settings.district') }}"
            data-required="1"
            data-required-message="{{ __('dashboard.tax_settings.validations.district_required') }}"
        >
        @error('district')
            <span class="invalid-feedback d-block" data-feedback-for="district" role="alert"><strong>{{ $message }}</strong></span>
        @else
            <span class="invalid-feedback d-block" data-feedback-for="district" role="alert" style="display:none"></span>
        @enderror
    </div>

    <div class="col-md-4 form-group">
        <label>{{ __('dashboard.tax_settings.postal_code') }} <span class="text-danger">*</span></label>
        <input
            class="form-control mg-b-20 branch-input @error('postal_code') is-invalid @enderror"
            name="postal_code"
            type="text"
            value="{{ old('postal_code', $branch->postal_code ?? '') }}"
            autocomplete="postal-code"
            maxlength="5"
            inputmode="numeric"
            data-branch-validate="1"
            data-label="{{ __('dashboard.tax_settings.postal_code') }}"
            data-required="1"
            data-digits="5"
            data-required-message="{{ __('dashboard.tax_settings.validations.postal_code_required') }}"
            data-digits-message="{{ __('dashboard.tax_settings.validations.postal_code_digits', ['digits' => 5]) }}"
        >
        @error('postal_code')
            <span class="invalid-feedback d-block" data-feedback-for="postal_code" role="alert"><strong>{{ $message }}</strong></span>
        @else
            <span class="invalid-feedback d-block" data-feedback-for="postal_code" role="alert" style="display:none"></span>
        @enderror
    </div>

    <div class="col-md-4 form-group">
        <label>{{ __('dashboard.tax_settings.short_address') }} <span class="text-danger">*</span></label>
        <input
            class="form-control mg-b-20 branch-input @error('short_address') is-invalid @enderror"
            name="short_address"
            type="text"
            value="{{ old('short_address', $branch->short_address ?? '') }}"
            autocomplete="street-address"
            maxlength="255"
            data-branch-validate="1"
            data-label="{{ __('dashboard.tax_settings.short_address') }}"
            data-required="1"
            data-required-message="{{ __('dashboard.tax_settings.validations.short_address_required') }}"
        >
        @error('short_address')
            <span class="invalid-feedback d-block" data-feedback-for="short_address" role="alert"><strong>{{ $message }}</strong></span>
        @else
            <span class="invalid-feedback d-block" data-feedback-for="short_address" role="alert" style="display:none"></span>
        @enderror
    </div>
</div>
