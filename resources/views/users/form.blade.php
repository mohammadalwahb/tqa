@extends('layouts.app')

@section('title', __('users.edit'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card table-card">
            <div class="card-header"><h5 class="mb-0">{{ __('users.edit_with_email', ['email' => $user->email]) }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">{{ __('users.full_name') }}</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('users.college_for_coordinator') }}</label>
                        <select name="college_id" class="form-select">
                            <option value="">{{ __('common.none') }}</option>
                            @foreach($colleges as $c)
                                <option value="{{ $c->id }}" @selected(old('college_id', $user->college_id) == $c->id)>{{ \App\Support\LocaleHelper::collegeDisplayName($c) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('common.roles') }}</label>
                        <div class="row g-2">
                            @foreach($roles as $r)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="roles[]"
                                               value="{{ $r->name }}" id="role_{{ $r->id }}"
                                               @checked(in_array($r->name, old('roles', $user->roles->pluck('name')->all())))>
                                        <label class="form-check-label" for="role_{{ $r->id }}">{{ \App\Support\LocaleHelper::roleDisplayName($r->name) }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               id="isActive" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">{{ __('users.account_active') }}</label>
                    </div>

                    <button class="btn btn-primary"><i class="bi bi-save"></i> {{ __('common.save') }}</button>
                    <a href="{{ route('users.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
